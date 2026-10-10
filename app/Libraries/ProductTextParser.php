<?php

namespace App\Libraries;

class ProductTextParser
{
    public function parse(string $text): array
    {
        $text = trim($text);
        $result = ['name' => $text, 'variant' => null, 'allocation_pcs' => null];

        // Gunakan angka setelah tanda '=' terakhir. Satuan pc/pcs bersifat opsional.
        // Kata penanda yang diterima: Al, Alok, Alokasi, serta awalan Sisa.
        // Contoh: "Alok 100Q= 2000pc+1320pc= 3320" -> 3320.
        $posisiSamaDengan = strrpos($text, '=');
        if ($posisiSamaDengan !== false) {
            $sebelumSamaDengan = substr($text, 0, $posisiSamaDengan);
            $bagianAlokasi = substr($text, $posisiSamaDengan + 1);
            $penandaAlokasi = '/(?:^|\s)(?:sisa\s+)?al(?:ok(?:asi)?)?\b/iu';
            if (preg_match($penandaAlokasi, $sebelumSamaDengan) === 1
                && preg_match('/^\s*(\d+)\s*(?:pcs?)?\s*$/i', $bagianAlokasi, $match) === 1) {
                $result['allocation_pcs'] = (int) $match[1];

                // Hilangkan keterangan alokasi dari nama/varian sebelum dipisahkan.
                $text = preg_replace(
                    '/\s+(?:sisa\s+)?al(?:ok(?:asi)?)?\b.*$/iu',
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

        $result['name'] = trim($name);
        $result['variant'] = trim($variant) ?: null;
        return $result;
    }
}
