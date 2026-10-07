<?php
/**
 * View: label rak "price tag" 8 x 3,5 cm, hitam-putih (untuk mPDF)
 * Dipakai desain 'pricetag' di App\Libraries\PopA4Pdf::pageHtmlPriceTag
 * $tags = daftar label: ['x' => cm, 'y' => cm, 'S' => teks + transform SVG, 'lines' => garis bawah (mm)]
 * $size = ['w' => cm, 'h' => cm, 'border' => tebal bingkai (mm)]
 */
$e  = static fn($s) => htmlspecialchars((string) $s, ENT_QUOTES, 'UTF-8');
$bw = $size['border'];            // tebal bingkai (mm)
$vw = $size['w'] * 10;            // lebar label (mm)
$vh = $size['h'] * 10;            // tinggi label (mm)
?>
<style>
    body { margin: 0; padding: 0; }
    .abs { position: absolute; margin: 0; padding: 0; }
</style>

<?php foreach ($tags as $t): $S = $t['S']; ?>
<div class="abs" style="left: <?= $t['x'] ?>cm; top: <?= $t['y'] ?>cm; width: <?= $size['w'] ?>cm; height: <?= $size['h'] ?>cm;">
    <svg xmlns="http://www.w3.org/2000/svg" width="<?= $size['w'] ?>cm" height="<?= $size['h'] ?>cm" viewBox="0 0 <?= $vw ?> <?= $vh ?>">
        <!-- Bingkai hitam -->
        <rect x="<?= $bw / 2 ?>" y="<?= $bw / 2 ?>" width="<?= $vw - $bw ?>" height="<?= $vh - $bw ?>"
              fill="#FFFFFF" stroke="#000000" stroke-width="<?= $bw ?>" />

        <!-- Garis bawah nama produk dan tulisan "PLU :" -->
        <?php foreach ($t['lines'] as $ln): ?>
        <line x1="<?= round($ln['x1'], 2) ?>" y1="<?= round($ln['y1'], 2) ?>" x2="<?= round($ln['x2'], 2) ?>" y2="<?= round($ln['y2'], 2) ?>"
              stroke="#000000" stroke-width="<?= $ln['w'] ?>" />
        <?php endforeach; ?>

        <!-- Teks: diregangkan (scaleX/scaleY) mengisi kotaknya -->
        <?php foreach ($S['el'] as $el): if ($el['text'] === '') continue; ?>
        <g transform="translate(<?= $el['x'] ?>,<?= $el['y'] ?>) scale(<?= $el['sx'] ?>,<?= $el['sy'] ?>)">
            <text x="0" y="0" font-family="<?= $el['family'] ?>" font-size="<?= $el['fs'] ?>"
                  fill="<?= $el['color'] ?>"><?= $e($el['text']) ?></text>
        </g>
        <?php endforeach; ?>
    </svg>
</div>
<?php endforeach; ?>