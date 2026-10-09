<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Cetak Price Tag</title>
    <style>
        @page { 
            /* Ukuran fisik price tag: 8 cm x 3,5 cm. */
            size: 80mm 35mm;
            margin: 0;
        }
        body { 
            font-family: Arial, sans-serif; 
            font-weight: 600;
            color: #111; 
            margin: 0; 
            padding: 0; 
        }
        .tag-cell {
            width: 80mm;
            height: 35mm;
            padding: 1.5mm !important;
            vertical-align: top;
            border: .3mm dashed #666;
            box-sizing: border-box;
            background-color: #fff;
            overflow: hidden;
        }
        .tag-box {
            border: 0;
            padding: 0;
            overflow: hidden;
        }
        .store-name {
            font-size: 5.5pt;
            font-weight: bold;
            text-transform: uppercase;
            letter-spacing: .1mm;
            height: 2.5mm;
            overflow: hidden;
        }
        .product-name {
            font-size: 7pt;
            font-weight: bold;
            height: 5.5mm;
            overflow: hidden;
            line-height: 1;
            text-transform: uppercase;
        }
        .variant {
            font-size: 5.5pt;
            font-weight: 600;
            height: 3mm;
            line-height: 1;
            overflow: hidden;
            color: #444;
        }
        .old-price { 
            height: 3mm; 
            font-size: 5.5pt; 
            font-weight: 600;
            text-decoration: line-through; 
            color: #555;
        }
        .price-line { 
            width: 100%; 
            border-collapse: collapse; 
        }
        .price-line td { 
            padding: 0; 
            vertical-align: bottom; 
        }
        .currency { 
            width: 8mm; 
            font-size: 9pt; 
            font-weight: bold;
        }
        .price-normal, .price-promo {
            font-size: 18pt;
            font-weight: bold;
            color: #000;
            text-align: right;
            line-height: 0.85;
            white-space: nowrap;
        }
        .sku {
            font-size: 5.5pt;
            font-weight: 600;
            height: 3.5mm;
            margin-top: 1mm;
            text-align: right;
            color: #333;
        }
        .promo-strip {
            background: #c83339;
            color: #fff;
            font-size: 6pt;
            font-weight: bold;
            letter-spacing: .4mm;
            text-align: center;
            line-height: 3.5mm;
            margin: 0.5mm 0 0;
            height: 3.5mm;
        }
    </style>
</head>
<body>

    <?php 
    $total = count($tags);
    $i = 0;
    foreach ($tags as $tag): 
        $i++;
        $isPromo = !empty($tag['promo_price']);
    ?>
        <div class="tag-cell" <?= $isPromo ? 'style="background-color: #f4df40;"' : '' ?>>
            <div class="tag-box">
                <div class="store-name">PRICE TAG</div>
                <div class="product-name"><?= esc($tag['name']) ?></div>
                <div class="variant"><?= esc($tag['variant'] ?? '') ?></div>
                
                <?php if ($isPromo): ?>
                    <div class="old-price">Rp <?= number_format($tag['normal_price'], 0, ',', '.') ?></div>
                    <table class="price-line">
                        <tr><td class="currency">Rp</td><td class="price-promo"><?= number_format($tag['promo_price'], 0, ',', '.') ?></td></tr>
                    </table>
                <?php else: ?>
                    <div class="old-price">&nbsp;</div>
                    <table class="price-line">
                        <tr><td class="currency">Rp</td><td class="price-normal"><?= number_format($tag['normal_price'], 0, ',', '.') ?></td></tr>
                    </table>
                <?php endif; ?>

                <div class="sku">PLU <?= esc($tag['sku_plu']) ?></div>
                <?php if ($isPromo): ?>
                    <div class="promo-strip">PROMO&nbsp;&nbsp;PROMO&nbsp;&nbsp;PROMO</div>
                <?php endif; ?>
            </div>
        </div>

        <?php if ($i < $total): ?>
            <pagebreak />
        <?php endif; ?>

    <?php endforeach; ?>

</body>
</html>
