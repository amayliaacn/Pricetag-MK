<?php
/**
 * View: POP "Segitiga Diskon" (untuk mPDF) - desain 'diskon' di App\Libraries\PopA4Pdf::pageHtmlDiskon
 * $S = tiap teks + hasil hitung transform SVG (dari PopA4Pdf::stretchEl)
 * $L = elemen non-teks (garis coret), $box = posisi/ukuran gambar kartu, $img = path gambar kartu
 */
$e = static fn($s) => htmlspecialchars((string) $s, ENT_QUOTES, 'UTF-8');
?>
<style>
    body { margin: 0; padding: 0; }
    .abs { position: absolute; margin: 0; padding: 0; }
</style>

<!-- Gambar kartu: kuning + border hitam + logo MURAH -->
<div class="abs" style="left: <?= $box['x'] ?>cm; top: <?= $box['y'] ?>cm; width: <?= $box['w'] ?>cm; height: <?= $box['h'] ?>cm;">
    <img src="<?= $e($img) ?>" style="width: <?= $box['w'] ?>cm; height: <?= $box['h'] ?>cm;">
</div>

<!-- Semua teks: masing-masing diregangkan (scaleX/scaleY) persis mengisi kotak WordArt-nya -->
<div class="abs" style="left: <?= $box['x'] ?>cm; top: <?= $box['y'] ?>cm; width: <?= $box['w'] ?>cm; height: <?= $box['h'] ?>cm;">
    <svg xmlns="http://www.w3.org/2000/svg" width="<?= $box['w'] ?>cm" height="<?= $box['h'] ?>cm" viewBox="0 0 <?= $S['w'] ?> <?= $S['h'] ?>">
        <?php foreach ($S['el'] as $t): ?>
        <?php if ((string) ($t['text'] ?? '') === '') continue; ?>
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

<!-- Garis coret miring pada harga lama -->
<div class="abs" style="left: <?= $L['strike']['left'] ?>cm; top: <?= $L['strike']['top'] ?>cm; width: <?= $L['strike']['w'] ?>cm; height: <?= $L['strike']['h'] ?>cm;">
    <svg xmlns="http://www.w3.org/2000/svg" width="<?= $L['strike']['w'] ?>cm" height="<?= $L['strike']['h'] ?>cm"
         viewBox="0 0 <?= $L['strike']['w'] ?> <?= $L['strike']['h'] ?>">
        <line x1="0" y1="<?= $L['strike']['y1'] ?>" x2="<?= $L['strike']['w'] ?>" y2="<?= $L['strike']['y2'] ?>"
              stroke="#FF0000" stroke-width="<?= $L['strike']['sw'] ?>" />
    </svg>
</div>
