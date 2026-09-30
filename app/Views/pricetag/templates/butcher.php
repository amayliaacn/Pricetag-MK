<?php
/**
 * View: POP "Manna Kampus FRESH" - 6 kartu per halaman A4 (untuk mPDF)
 * Dipakai desain 'fresh' di App\Libraries\PopA4Pdf::pageHtmlFresh
 * $cards = daftar kartu: ['x' => cm, 'y' => cm, 'S' => teks + transform SVG]
 * $size  = ukuran satu kartu ['w' => cm, 'h' => cm]
 * $img   = path gambar latar satu kartu (Picture5.jpg)
 */
$e = static fn($s) => htmlspecialchars((string) $s, ENT_QUOTES, 'UTF-8');
?>
<style>
    body { margin: 0; padding: 0; }
    .abs { position: absolute; margin: 0; padding: 0; }
</style>

<?php foreach ($cards as $c): $S = $c['S']; ?>
<!-- Gambar latar kartu -->
<div class="abs" style="left: <?= $c['x'] ?>cm; top: <?= $c['y'] ?>cm; width: <?= $size['w'] ?>cm; height: <?= $size['h'] ?>cm;">
    <img src="<?= $e($img) ?>" style="width: <?= $size['w'] ?>cm; height: <?= $size['h'] ?>cm;">
</div>

<!-- Teks kartu: diregangkan (scaleX/scaleY) mengisi kotak WordArt-nya -->
<div class="abs" style="left: <?= $c['x'] ?>cm; top: <?= $c['y'] ?>cm; width: <?= $size['w'] ?>cm; height: <?= $size['h'] ?>cm;">
    <svg xmlns="http://www.w3.org/2000/svg" width="<?= $size['w'] ?>cm" height="<?= $size['h'] ?>cm" viewBox="0 0 <?= $S['w'] ?> <?= $S['h'] ?>">
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
<?php endforeach; ?>