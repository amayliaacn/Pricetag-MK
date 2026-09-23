<?php

namespace App\Libraries;

class PriceTagTemplates
{
    protected static array $templates = [
        'turun-harga-kcl' => [
            'label'  => 'Pricetag Turun Harga - Kecil',
            'docx'   => FCPATH . 'templates/pricetag/turun-harga-kcl.docx',
            'fields' => [
                'PLU'         => 'sku_plu',
                'Variant'     => 'name_variant',
                'HargaNormal' => 'normal_price:currency_id',
                'HargaPromo'  => 'promo_price:currency_id',
                'Periode'     => 'end_period:date_id_short',
            ],
        ],

        'turun-harga-kcl-allocation' => [
            'label'  => 'Pricetag Turun Harga - Kecil (Alokasi Terbatas)',
            'docx'   => FCPATH . 'templates/pricetag/turun-harga-kcl-allocation.docx',
            'fields' => [
                'PLU'         => 'sku_plu',
                'Variant'     => 'name_variant',
                'HargaNormal' => 'normal_price:currency_id',
                'HargaPromo'  => 'promo_price:currency_id',
                'Periode'     => 'end_period:date_id_short',
                'Alokasi'     => 'allocation_pcs',
            ],
        ],

        'turun-harga-tgg' => [
            'label'  => 'Pricetag Turun Harga - Tanggung',
            'docx'   => FCPATH . 'templates/pricetag/turun-harga-tgg.docx',
            'fields' => [
                'NamaBarang'  => 'full_label',
                'PLU'         => 'sku_plu',
                'HargaNormal' => 'normal_price:currency_id',
                'Ribuan'      => 'promo_price:thousands_id',
                'Ratusan'     => 'promo_price:hundreds_id',
                'Periode'     => 'end_period:date_id_short',
            ],
        ],

        'turun-harga-tgg-allvar' => [
            'label'  => 'Pricetag Turun Harga - Tanggung (All Varian)',
            'docx'   => FCPATH . 'templates/pricetag/turun-harga-tgg-allvar.docx',
            'fields' => [
                'NamaBarang'  => 'full_label',
                'HargaNormal' => 'normal_price:currency_id',
                'Ribuan'      => 'promo_price:thousands_id',
                'Ratusan'     => 'promo_price:hundreds_id',
                'Periode'     => 'end_period:date_id_short',
            ],
        ],

        'turun-harga-tgg-allocation' => [
            'label'  => 'Pricetag Turun Harga - Tanggung (Alokasi Terbatas)',
            'docx'   => FCPATH . 'templates/pricetag/turun-harga-tgg-allocation.docx',
            'fields' => [
                'NamaBarang'  => 'full_label',
                'PLU'         => 'sku_plu',
                'HargaNormal' => 'normal_price:currency_id',
                'Ribuan'      => 'promo_price:thousands_id',
                'Ratusan'     => 'promo_price:hundreds_id',
                'Periode'     => 'end_period:date_id_short',
                'Alokasi'     => 'allocation_pcs',
            ],
        ],

        'disc-reg-kcl' => [
            'label'  => 'Pricetag Diskon Reguler - Kecil',
            'docx'   => FCPATH . 'templates/pricetag/disc-reg-kcl.docx',
            'fields' => [
                'PLU'         => 'sku_plu',
                'Variant'     => 'name_variant',
                'Program'     => 'discount_percent:percent',
                'HargaNormal' => 'normal_price:currency_id',
                'HargaPromo'  => 'promo_price:currency_id',
                'Periode'     => 'end_period:date_id_short',
            ],
        ],

        'disc-reg-kcl-allocation' => [
            'label'  => 'Pricetag Diskon Reguler - Kecil (Alokasi Terbatas)',
            'docx'   => FCPATH . 'templates/pricetag/disc-reg-kcl-allocation.docx',
            'fields' => [
                'PLU'         => 'sku_plu',
                'Variant'     => 'name_variant',
                'Program'     => 'discount_percent:percent',
                'HargaNormal' => 'normal_price:currency_id',
                'HargaPromo'  => 'promo_price:currency_id',
                'Periode'     => 'end_period:date_id_short',
                'Alokasi'     => 'allocation_pcs',
            ],
        ],
         'disc-reg-tgg' => [
            'label'  => 'Pricetag Diskon Reguler - Tanggung',
            'docx'   => FCPATH . 'templates/pricetag/disc-reg-tgg.docx',
            'fields' => [
                'NamaBarang'  => 'full_label',
                'PLU'         => 'sku_plu',
                'Program'     => 'discount_percent:percent',
                'HargaNormal' => 'normal_price:currency_id',
                'HargaPromo'  => 'promo_price:currency_id',
                'Periode'     => 'end_period:date_id_short',
            ],
        ],

        'disc-reg-kcl-allvar' => [
            'label'  => 'Pricetag Diskon Reguler - Tanggung (All Varian)',
            'docx'   => FCPATH . 'templates/pricetag/disc-reg-tgg-allvar.docx',
            'fields' => [
                'NamaBarang'  => 'full_label',
                'Program'     => 'discount_percent:percent',
                'HargaNormal' => 'normal_price:currency_id',
                'HargaPromo'  => 'promo_price:currency_id',
                'Periode'     => 'end_period:date_id_short',
            ],
        ],

       

        'disc-reg-tgg-allocation' => [
            'label'  => 'Pricetag Diskon Reguler - Tanggung (Alokasi Terbatas)',
            'docx'   => FCPATH . 'templates/pricetag/disc-reg-tgg-allocation.docx',
            'fields' => [
                'NamaBarang'  => 'full_label',
                'PLU'         => 'sku_plu',
                'Program'     => 'discount_percent:percent',
                'HargaNormal' => 'normal_price:currency_id',
                'HargaPromo'  => 'promo_price:currency_id',
                'Periode'     => 'end_period:date_id_short',
                'Alokasi'     => 'allocation_pcs',
            ],
        ],

            'disc-kcl-per-100gr' => [
            'label'  => 'Pricetag Diskon - Kecil (Per 100 gr)',
            'docx'   => FCPATH . 'templates/pricetag/disc-kcl-per-100gr.docx',
            'fields' => [
                'NamaBarang'  => 'full_label',
                'PLU'         => 'sku_plu',
                'Program'     => 'discount_percent:percent',
                'HargaNormal' => 'normal_price:currency_id',
                'HargaPromo'  => 'promo_price:currency_id',
                'Periode'     => 'end_period:date_id_short',
            ],
        ],
    ];

    public static function all(): array
    {
        return self::$templates;
    }

    public static function find(string $key): ?array
    {
        return self::$templates[$key] ?? null;
    }
}
