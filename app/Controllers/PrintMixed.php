<?php

namespace App\Controllers;

use App\Libraries\DocxLabelMerger;
use App\Libraries\PopA4Pdf;
use App\Libraries\PriceTagTemplates;
use App\Models\PriceTagModel;
use App\Models\ImportHistoryModel;

class PrintMixed extends BaseController
{
    public function index()
    {
        $ids = (array) $this->request->getPost('tag_ids');
        $qty = (array) $this->request->getPost('qty');
        $sizes = (array) $this->request->getPost('size');
        $allvar = (array) $this->request->getPost('allvar');
        $discountMode = (array) $this->request->getPost('discount_mode');
        $importId = (int) $this->request->getPost('import_id');
        if ($ids === []) return redirect()->back()->with('error', 'Pilih minimal 1 produk untuk dicetak.');
        $selectedSizes = array_values(array_unique(array_filter(array_map('strval', array_intersect_key($sizes, array_flip($ids))))));
        if (count($selectedSizes) > 1) {
            return redirect()->back()->with('error', 'Tidak dapat mencetak beberapa ukuran template sekaligus. Silakan pilih produk dengan ukuran template yang sama.');
        }

        if ($importId > 0 && !(new ImportHistoryModel())->findVisibleImport($importId, (string) session()->get('role'), session()->get('outlet_id') === null ? null : (int) session()->get('outlet_id'))) {
            return redirect()->to('/import-history')->with('error', 'Data impor tidak ditemukan.');
        }

        $model = new PriceTagModel();
        $mpdfItems = [];
        $libreGroups = [];
        $mergers = [];
        try {
            foreach ($ids as $id) {
                $query = $model->where('id', (int) $id);
                if ($importId > 0) $query->where('import_id', $importId);
                else $query->where('uploaded_by', (int) session()->get('id'))->where('import_date', date('Y-m-d'));
                $row = $query->first();
                if (!$row) continue;
                $size = (string) ($sizes[$id] ?? '');
                $count = max(1, (int) ($qty[$id] ?? 1));
                if ($size === 'a5' && (float) ($row['promo_price'] ?? 0) <= 0 && (float) ($row['discount_percent'] ?? 0) <= 0) {
                    return redirect()->back()->with('error', 'A5 Discount/Turun Harga memerlukan Diskon atau Harga Promo.');
                }
                if ($size === 'curah' && (float) ($row['promo_price'] ?? 0) <= 0 && (float) ($row['discount_percent'] ?? 0) <= 0) {
                    return redirect()->back()->with('error', 'Template Vegetable memerlukan Diskon atau Harga Promo.');
                }
                // Samakan dengan jalur PDF kecil/tanggung: harga promo hasil
                // diskon dihitung sementara untuk kebutuhan template cetak.
                // A4 tidak memiliki template diskon; selalu gunakan Turun Harga.
                $mode = $size === 'mpdf' ? 'auto' : (string) ($discountMode[$id] ?? 'auto');
                $showDiscount = $this->showDiscount($row, $mode);
                if (in_array($size, ['mpdf', 'a5', 'segitiga'], true) && ! $showDiscount && (float) ($row['discount_percent'] ?? 0) > 0) {
                    $row['promo_price'] = $this->promoPrice($row);
                }
                if (in_array($size, ['mpdf', 'pricetag', 'a5', 'fresh', 'curah', 'segitiga'], true)) {
                    $template = match ($size) {
                        'pricetag' => 'pricetag',
                        'segitiga' => ($showDiscount ? 'diskon' : ((float) ($row['promo_price'] ?? 0) > 0 ? 'segitiga' : 'special')),
                        'a5' => ((float) ($row['discount_percent'] ?? 0) > 0
                            ? ($showDiscount ? ((float) ($row['normal_price'] ?? 0) > 0 ? 'disc2' : 'disc') : 'a5')
                            : 'a5'),
                        'fresh' => 'fresh',
                        'curah' => 'curah',
                        default => 'a4',
                    };
                    $mpdfItems[$template][] = ['row' => $row, 'qty' => $count];
                    continue;
                }
                if (!in_array($size, ['kcl','tgg'], true)) continue;
                $row['promo_price'] = $this->promoPrice($row);
                $program = $this->showDiscount($row, (string) ($discountMode[$id] ?? 'auto')) ? 'disc-reg' : 'turun-harga';
                $allocation = (int) ($row['allocation_pcs'] ?? 0) > 0;
                $key = $size === 'tgg'
                    ? $program . '-tgg' . (($allvar[$id] ?? '') === '1' && !$allocation ? '-allvar' : ($allocation ? '-allocation' : ''))
                    : $program . '-kcl' . ($allocation ? '-allocation' : '');
                $tpl = PriceTagTemplates::find($key);
                if ($tpl) $libreGroups[$key][] = ['sku' => (string) $row['sku_plu'], 'qty' => $count, 'field_values' => DocxLabelMerger::buildFieldValues($row, $tpl['fields'])];
            }

            $pdfs = [];
            if ($mpdfItems !== []) {
                $pdfs[] = $this->writeMpdfMixed($mpdfItems);
            }
            foreach ($libreGroups as $key => $items) { $mergers[$key] = new DocxLabelMerger(PriceTagTemplates::find($key)['docx']); $pdfs[] = $mergers[$key]->generate($items); }
            if ($pdfs === []) return redirect()->back()->with('error', 'Produk yang dipilih tidak ditemukan.');
            $path = $this->combine($pdfs);
            $dir = WRITEPATH . 'pricetag_downloads/'; if (!is_dir($dir)) mkdir($dir, 0775, true);
            $token = bin2hex(random_bytes(16)); copy($path, $dir . $token . '.pdf');
            file_put_contents($dir . $token . '.json', json_encode(['user_id'=>(int)session()->get('id'),'expires_at'=>time()+3600]), LOCK_EX);
            return redirect()->to(site_url('print-pdf/' . $token));
        } catch (\Throwable $e) { log_message('error', 'Cetak campuran gagal: '.$e->getMessage()); return redirect()->back()->with('error', 'Gagal membuat PDF: '.$e->getMessage()); }
        finally { foreach ($mergers as $merger) $merger->cleanup(); }
    }

    private function writeMpdf(array $items, string $template): string
    {
        $pdf = (new PopA4Pdf($template))->render($items, $template);
        $dir = WRITEPATH . 'pricetag_tmp/';
        if (! is_dir($dir) && ! mkdir($dir, 0775, true) && ! is_dir($dir)) {
            throw new \RuntimeException('Folder PDF sementara tidak dapat dibuat.');
        }

        $path = $dir . 'mpdf_' . bin2hex(random_bytes(8)) . '.pdf';
        if (file_put_contents($path, $pdf, LOCK_EX) === false) {
            throw new \RuntimeException('Hasil mPDF sementara tidak dapat disimpan.');
        }

        return $path;
    }
    private function writeMpdfMixed(array $groups): string
    {
        $pdf = (new PopA4Pdf())->renderMixed($groups);
        $dir = WRITEPATH . 'pricetag_tmp/';
        if (! is_dir($dir) && ! mkdir($dir, 0775, true) && ! is_dir($dir)) {
            throw new \RuntimeException('Folder PDF sementara tidak dapat dibuat.');
        }
        $path = $dir . 'mpdf_' . bin2hex(random_bytes(8)) . '.pdf';
        if (file_put_contents($path, $pdf, LOCK_EX) === false) {
            throw new \RuntimeException('Hasil mPDF sementara tidak dapat disimpan.');
        }
        return $path;
    }
    private function promoPrice(array $r): ?int { $p=(float)($r['promo_price']??0); $n=(float)($r['normal_price']??0); $d=(float)($r['discount_percent']??0); return $p>0?(int)round($p):($n>0&&$d>0?(int)round($n*(1-$d/100)):null); }
    private function showDiscount(array $row, string $mode): bool { $d=(float)($row['discount_percent']??0); return $d>0 && ($d>=10 || $mode==='show'); }
    private function combine(array $paths): string { if(count($paths)===1)return $paths[0]; $out=WRITEPATH.'pricetag_tmp/combined_'.bin2hex(random_bytes(8)).'.pdf'; $pdftk='C:\\Program Files (x86)\\PDFtk Server\\bin\\pdftk.exe'; exec('"'.$pdftk.'" '.implode(' ',array_map('escapeshellarg',$paths)).' cat output '.escapeshellarg($out).' 2>&1',$o,$c); if($c!==0||!is_file($out))throw new \RuntimeException('Gagal menggabungkan hasil PDF: '.implode("\n",$o)); return $out; }
}
