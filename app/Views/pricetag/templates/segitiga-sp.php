<?php
/**
 * View: POP "Segitiga Special Price" (untuk mPDF)
 * $S   = tiap teks + hasil hitung transform SVG (dari PopA4Pdf::pageHtmlSpecial)
 * $box = posisi/ukuran gambar kartu, $img = path gambar kartu
 */
$e = static fn($s) => htmlspecialchars((string) $s, ENT_QUOTES, 'UTF-8');
?>
<style>
    body { margin: 0; padding: 0; }
    .abs { position: absolute; margin: 0; padding: 0; }
</style>

<!-- Gambar kartu: kuning + border hitam + kotak putih + logo MURAH -->
<div class="abs" style="left: <?= $box['x'] ?>cm; top: <?= $box['y'] ?>cm; width: <?= $box['w'] ?>cm; height: <?= $box['h'] ?>cm;">
    <img src="<?= $e($img) ?>" style="width: <?= $box['w'] ?>cm; height: <?= $box['h'] ?>cm;">
</div>

<!-- Semua teks: diregangkan (scaleX/scaleY) mengisi kotak WordArt-nya -->
<div class="abs" style="left: <?= $box['x'] ?>cm; top: <?= $box['y'] ?>cm; width: <?= $box['w'] ?>cm; height: <?= $box['h'] ?>cm;">
    <svg xmlns="http://www.w3.org/2000/svg" width="<?= $box['w'] ?>cm" height="<?= $box['h'] ?>cm" viewBox="0 0 <?= $S['w'] ?> <?= $S['h'] ?>">
        <?php foreach ($S['el'] as $t): ?>
        <g transform="translate(<?= $t['x'] ?>,<?= $t['y'] ?>) scale(<?= $t['sx'] ?>,<?= $t['sy'] ?>)">
            <text x="0" y="0" font-family="<?= $t['family'] ?>" font-size="<?= $t['fs'] ?>"
                  fill="<?= $t['color'] ?>"
                  <?php if ($t['outline']): [$oc, $ow] = explode(' ', $t['outline']); ?>
                  stroke="<?= $oc ?>" stroke-width="<?= (float) $ow ?>"
                  <?php endif; ?>><?= $e($t['text']) ?></text>
        </g>
        <?php endforeach; ?>
    </svg>
</div>