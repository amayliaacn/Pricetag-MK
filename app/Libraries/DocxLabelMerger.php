<?php

namespace App\Libraries;

use RuntimeException;
use ZipArchive;

/**
 * DocxLabelMerger
 * ----------------
 * Mesin inti pencetakan price tag. Prinsip kerja:
 *
 *   1. Template .docx TIDAK PERNAH diubah strukturnya. Kita hanya mengganti
 *      teks placeholder "<<Nama>>" yang ada di dalam word/document.xml dengan
 *      nilai data produk, lalu menulis ulang menjadi file .docx baru.
 *   2. File .docx hasil merge dikonversi ke PDF memakai LibreOffice headless
 *      (soffice), yang membaca dokumen Word apa adanya sehingga tampilan
 *      (WordArt, warna, posisi) identik dengan aslinya di Microsoft Word.
 *   3. Jika satu produk dicetak beberapa pcs (qty), halaman PDF-nya
 *      diduplikasi memakai pdftk (bukan render ulang berkali-kali -> jauh
 *      lebih cepat).
 *   4. Semua PDF produk digabung jadi satu file PDF akhir untuk dicetak user.
 *
 * Requirement server: `soffice` (LibreOffice) dan `pdftk` harus terpasang &
 * bisa dipanggil lewat shell (shell_exec / proc_open harus diizinkan).
 */
class DocxLabelMerger
{
    protected string $templatePath;
    protected string $workDir;

    public function __construct(string $templatePath)
    {
        if (! is_file($templatePath)) {
            throw new RuntimeException("Template docx tidak ditemukan: {$templatePath}");
        }

        $this->templatePath = $templatePath;

        $this->workDir = WRITEPATH . 'pricetag_tmp/' . bin2hex(random_bytes(8));
        mkdir($this->workDir, 0775, true);
    }

    public function generate(array $items): string
    {
        if (empty($items)) {
            throw new RuntimeException('Tidak ada produk yang dipilih untuk dicetak.');
        }

        $docxPaths = [];
        foreach ($items as $i => $item) {
            $docxPaths[$i] = $this->mergeToDocx($item['field_values'], "label_{$i}.docx");
        }

        $pdfPaths = $this->convertBatchToPdf($docxPaths);

        $finalPerItem = [];
        foreach ($items as $i => $item) {
            $qty = max(1, (int) ($item['qty'] ?? 1));
            $finalPerItem[] = $this->duplicatePages($pdfPaths[$i], $qty, "final_{$i}.pdf");
        }

        $combined = $this->concatPdfs($finalPerItem, 'gabungan_pricetag.pdf');

        return $combined;
    }

    protected function mergeToDocx(array $fieldValues, string $outName): string
    {
        $outPath = $this->workDir . '/' . $outName;
        copy($this->templatePath, $outPath);

        $zip = new ZipArchive();
        if ($zip->open($outPath) !== true) {
            throw new RuntimeException("Gagal membuka docx sementara: {$outPath}");
        }

        $xml = $zip->getFromName('word/document.xml');
        if ($xml === false) {
            $zip->close();
            throw new RuntimeException('word/document.xml tidak ditemukan di dalam template.');
        }

        $xml = $this->mergeSimpleSplitRuns($xml);

        foreach ($fieldValues as $placeholder => $value) {
            $needle = '<<' . $placeholder . '>>';
            $escapedNeedle = htmlspecialchars($needle, ENT_XML1);
            $safeValue     = htmlspecialchars((string) $value, ENT_XML1);

            $xml = str_replace($escapedNeedle, $safeValue, $xml);
            $xml = str_replace($needle, $safeValue, $xml);
        }

        $zip->addFromString('word/document.xml', $xml);
        $zip->close();

        return $outPath;
    }

    protected function mergeSimpleSplitRuns(string $xml): string
    {
        $pattern = '/<w:r>(<w:rPr>.*?<\/w:rPr>)?<w:t(?: xml:space="preserve")?>(.*?)<\/w:t><\/w:r><w:r>\1<w:t(?: xml:space="preserve")?>(.*?)<\/w:t><\/w:r>/s';

        $prev = null;
        while ($prev !== $xml) {
            $prev = $xml;
            $xml  = preg_replace_callback($pattern, function ($m) {
                $rpr = $m[1] ?? '';
                return '<w:r>' . $rpr . '<w:t xml:space="preserve">' . $m[2] . $m[3] . '</w:t></w:r>';
            }, $xml);
        }

        return $xml;
    }

    protected function convertBatchToPdf(array $docxPaths): array
    {
        $profileDir = $this->workDir . '/lo_profile';
        $profileUri = 'file:///' . ltrim(str_replace('\\', '/', $profileDir), '/');

        $files  = array_map('escapeshellarg', $docxPaths);
        $outDir = escapeshellarg($this->workDir);

        $cmd = sprintf(
            'soffice --headless --norestore -env:UserInstallation=%s --convert-to pdf --outdir %s %s 2>&1',
            escapeshellarg($profileUri),
            $outDir,
            implode(' ', $files)
        );

        exec($cmd, $output, $returnCode);

        if ($returnCode !== 0) {
            throw new RuntimeException('Konversi ke PDF gagal: ' . implode("\n", $output));
        }

        $pdfPaths = [];
        foreach ($docxPaths as $i => $docxPath) {
            $pdfPath = $this->workDir . '/' . pathinfo($docxPath, PATHINFO_FILENAME) . '.pdf';
            if (! is_file($pdfPath)) {
                throw new RuntimeException("Hasil PDF tidak ditemukan untuk: {$docxPath}");
            }
            $pdfPaths[$i] = $pdfPath;
        }

        return $pdfPaths;
    }

    protected function duplicatePages(string $pdfPath, int $qty, string $outName): string
    {
        if ($qty <= 1) {
            return $pdfPath;
        }

        $outPath   = $this->workDir . '/' . $outName;
        $pageSpec  = implode(' ', array_fill(0, $qty, '1'));
        $cmd       = sprintf(
            'pdftk %s cat %s output %s 2>&1',
            escapeshellarg($pdfPath),
            $pageSpec,
            escapeshellarg($outPath)
        );

        exec($cmd, $output, $returnCode);

        if ($returnCode !== 0) {
            throw new RuntimeException('Gagal menduplikasi halaman PDF: ' . implode("\n", $output));
        }

        return $outPath;
    }

    protected function concatPdfs(array $pdfPaths, string $outName): string
    {
        $outPath = $this->workDir . '/' . $outName;

        if (count($pdfPaths) === 1) {
            copy($pdfPaths[0], $outPath);
            return $outPath;
        }

        $files = array_map('escapeshellarg', $pdfPaths);
        $cmd   = sprintf(
            'pdftk %s cat output %s 2>&1',
            implode(' ', $files),
            escapeshellarg($outPath)
        );

        exec($cmd, $output, $returnCode);

        if ($returnCode !== 0) {
            throw new RuntimeException('Gagal menggabungkan PDF: ' . implode("\n", $output));
        }

        return $outPath;
    }

    public function cleanup(): void
    {
        $this->deleteDir($this->workDir);
    }

    protected function deleteDir(string $dir): void
    {
        if (! is_dir($dir)) {
            return;
        }
        $items = array_diff(scandir($dir), ['.', '..']);
        foreach ($items as $item) {
            $path = $dir . '/' . $item;
            is_dir($path) ? $this->deleteDir($path) : unlink($path);
        }
        rmdir($dir);
    }

    public static function buildFieldValues(array $product, array $fieldMap): array
    {
        foreach (['brand', 'name', 'variant'] as $column) {
            $product[$column] = mb_strtoupper((string) ($product[$column] ?? ''), 'UTF-8');
        }

        $product['name_variant'] = trim(
            trim($product['name'] ?? '') . ' ' . trim($product['variant'] ?? '')
        );

        $parts = array_filter([
            $product['brand'] ?? '',
            $product['name'] ?? '',
            $product['variant'] ?? '',
        ], fn ($v) => trim((string) $v) !== '');
        $product['full_label'] = trim(implode(' - ', $parts));

        $result = [];
        foreach ($fieldMap as $placeholder => $spec) {
            [$column, $formatter] = array_pad(explode(':', $spec, 2), 2, null);
            $rawValue = $product[$column] ?? '';
            $result[$placeholder] = self::format($rawValue, $formatter);
        }

        return $result;
    }

    protected static function format($value, ?string $formatter): string
    {
        if ($value === null || $value === '') {
            return '';
        }

        switch ($formatter) {
            case 'currency_id':
                return number_format((float) $value, 0, ',', '.');

            case 'thousands_id':
                return number_format((int) floor(((float) $value) / 1000), 0, ',', '.');

            case 'hundreds_id':
                return str_pad((string) ((int) $value % 1000), 3, '0', STR_PAD_LEFT);

            case 'date_id_short':
                try {
                    $dt = new \DateTime($value);
                } catch (\Exception $e) {
                    return (string) $value;
                }
                $bulanIndo = [
                    'Jan' => 'JAN', 'Feb' => 'FEB', 'Mar' => 'MAR', 'Apr' => 'APR',
                    'May' => 'MEI', 'Jun' => 'JUN', 'Jul' => 'JUL', 'Aug' => 'AGU',
                    'Sep' => 'SEP', 'Oct' => 'OKT', 'Nov' => 'NOV', 'Dec' => 'DES',
                ];
                $bulan = $bulanIndo[$dt->format('M')] ?? strtoupper($dt->format('M'));
                return $dt->format('d') . ' ' . $bulan . " '" . $dt->format('y');

            case 'percent':
                return rtrim(rtrim(number_format((float) $value, 2), '0'), '.');

            default:
                return (string) $value;
        }
    }
}