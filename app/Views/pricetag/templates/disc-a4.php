<?php
/**
 * View: POP A4 "DISKON 50%" + harga normal dicoret + harga sesudah diskon (untuk mPDF)
 * Contoh: BELFOOD / FAV.500G CRUNCHY NUGGET.
 *
 * $S      = tiap teks + hasil hitung transform SVG (dari PopA4Pdf::pageHtmlDiscA4)
 * $L      = ['strike' => garis coret dalam mm relatif kartu, atau null]
 * $box    = posisi/ukuran kartu di halaman (cm)
 * $img    = path gambar latar (kuning + kotak putih + logo MURAH, tanpa teks) atau null
 * $frame  = bingkai cadangan kalau $img null
 *
 * Simpan di: app/Views/pricetag/templates/disc-a4.php
 */
$e = static fn($s) => htmlspecialchars((string) $s, ENT_QUOTES, 'UTF-8');
?>
<style>
    body { margin: 0; padding: 0; }
    .abs { position: absolute; margin: 0; padding: 0; }
</style>

<!-- Latar kartu -->
<?php if ($img): ?>
    <div class="abs" style="left: <?= $box['x'] ?>cm; top: <?= $box['y'] ?>cm; width: <?= $box['w'] ?>cm; height: <?= $box['h'] ?>cm;">
        <img src="<?= $e($img) ?>" style="width: <?= $box['w'] ?>cm; height: <?= $box['h'] ?>cm;">
    </div>
<?php else: ?>
    <!-- Bingkai cadangan: kuning + kotak putih rounded + badge MURAH (pengganti logo) -->
    <div class="abs" style="left: <?= $box['x'] ?>cm; top: <?= $box['y'] ?>cm; width: <?= $box['w'] ?>cm; height: <?= $box['h'] ?>cm;
         background-color: <?= $frame['kuning'] ?>;"></div>
    <div class="abs" style="left: <?= $box['x'] + $frame['in_x'] ?>cm; top: <?= $box['y'] + $frame['in_top'] ?>cm;
         width: <?= $box['w'] - 2 * $frame['in_x'] ?>cm; height: <?= $box['h'] - $frame['in_top'] - $frame['in_bot'] ?>cm;
         background-color: #FFFFFF; border-radius: <?= $frame['radius'] ?>cm;"></div>
    <div class="abs" style="left: <?= $box['x'] + 0.25 ?>cm; top: <?= $box['y'] + 0.25 ?>cm; width: 5.6cm; height: 2.0cm;
         background-color: #F08A1C; border-radius: 1cm; color: #E30613; font-family: arialblack; font-size: 22pt;
         text-align: center; line-height: 2.0cm;">MURAH..!</div>
<?php endif; ?>

<!-- Semua teks + garis coret: diregangkan (scaleX/scaleY) mengisi kotak WordArt-nya -->
<div class="abs" style="left: <?= $box['x'] ?>cm; top: <?= $box['y'] ?>cm; width: <?= $box['w'] ?>cm; height: <?= $box['h'] ?>cm;">
    <svg xmlns="http://www.w3.org/2000/svg" width="<?= $box['w'] ?>cm" height="<?= $box['h'] ?>cm" viewBox="0 0 <?= $S['w'] ?> <?= $S['h'] ?>">
        <?php foreach ($S['el'] as $t): ?>
            <?php if (($t['text'] ?? '') === '') continue; ?>
        <g transform="translate(<?= $t['x'] ?>,<?= $t['y'] ?>) scale(<?= $t['sx'] ?>,<?= $t['sy'] ?>)">
            <text x="0" y="0" font-family="<?= $t['family'] ?>" font-size="<?= $t['fs'] ?>"
                  fill="<?= $t['color'] ?>"
                  <?php if ($t['outline']): [$oc, $ow] = explode(' ', $t['outline']); ?>
                  stroke="<?= $oc ?>" stroke-width="<?= (float) $ow ?>"
                  <?php endif; ?>><?= $e($t['text']) ?></text>
        </g>
        <?php endforeach; ?>

        <?php if (! empty($L['strike'])): $st = $L['strike']; ?>
        <!-- Garis coret merah miring pada harga normal (mm) -->
        <line x1="<?= $st['x1'] ?>" y1="<?= $st['y1'] ?>" x2="<?= $st['x2'] ?>" y2="<?= $st['y2'] ?>"
              stroke="#FF0000" stroke-width="<?= $st['sw'] ?>" />
        <?php endif; ?>
    </svg>
</div>