<?php

namespace App\Libraries;

class ProductTextParser
{
    public function parse(string $text): array
    {
        $text = trim($text);
        $result = ['name' => $text, 'variant' => null, 'allocation_pcs' => null];

        // Untuk format alokasi berantai, gunakan angka setelah tanda '=' terakhir.
        // Contoh: "Alok 100Q= 2000pc+1320pc= 3320pc" -> 3320.
        $posisiSamaDengan = strrpos($text, '=');
        if ($posisiSamaDengan !== false) {
            $bagianAlokasi = substr($text, $posisiSamaDengan + 1);
            if (preg_match('/(\d+)\s*pc\b/i', $bagianAlokasi, $match)) {
                $result['allocation_pcs'] = (int) $match[1];

                // Hilangkan keterangan alokasi dari nama/varian sebelum dipisahkan.
                $text = preg_replace(
                    '/\s+(?:sisa\s+)?alok(?:asi)?\b.*$/iu',
                    '',
                    $text
                ) ?? $text;
                $text = trim($text);
            }
        }

        $name = $text;
        $variant = '';
        if (str_contains($text, '#')) {
            [$name, $variant] = explode('#', $text, 2);
            $variant = trim($variant);
        }

        $allocationText = $variant !== '' ? $variant : $name;
        if ($result['allocation_pcs'] === null && preg_match('/\b(?:sisa\s+)?alok(?:asi)?(?:\s+[^=]+)?\s*=\s*(\d+)\s*pcs?\b/iu', $allocationText, $match)) {
            $result['allocation_pcs'] = (int) $match[1];
            $allocationText = preg_replace('/\b(?:sisa\s+)?alok(?:asi)?(?:\s+[^=]+)?\s*=\s*\d+\s*pcs?\b/iu', '', $allocationText) ?? $allocationText;
            if ($variant !== '') $variant = $allocationText; else $name = $allocationText;
        }

        $result['name'] = trim($name);
        $result['variant'] = trim($variant) ?: null;
        return $result;
    }
}
