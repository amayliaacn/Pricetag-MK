<?php
/**
 * View: POP "DISC 50%" landscape (untuk mPDF) - desain 'disc' di App\Libraries\PopA4Pdf::pageHtmlDisc
 * $S     = tiap teks + hasil hitung transform SVG
 * $box   = posisi/ukuran kartu di halaman A4 (cm)
 * $img   = path gambar latar, atau null -> bingkai digambar sendiri dengan HTML/CSS
 * $frame = pengaturan bingkai cadangan (dari PopA4Pdf::DSC_FRAME)
 */
$e = static fn($s) => htmlspecialchars((string) $s, ENT_QUOTES, 'UTF-8');
?>
<style>
    body { margin: 0; padding: 0; }
    .abs { position: absolute; margin: 0; padding: 0; }
</style>

<?php if ($img): ?>
<!-- Gambar latar: kuning + kotak putih rounded + logo MURAH -->
<div class="abs" style="left: <?= $box['x'] ?>cm; top: <?= $box['y'] ?>cm; width: <?= $box['w'] ?>cm; height: <?= $box['h'] ?>cm;">
    <img src="<?= $e($img) ?>" style="width: <?= $box['w'] ?>cm; height: <?= $box['h'] ?>cm;">
</div>
<?php else: ?>
<!-- Bingkai HTML/CSS: kotak kuning -->
<div class="abs" style="left: <?= $box['x'] ?>cm; top: <?= $box['y'] ?>cm; width: <?= $box['w'] ?>cm; height: <?= $box['h'] ?>cm; background-color: <?= $frame['kuning'] ?>;"></div>

<!-- Kotak putih rounded -->
<div class="abs" style="left: <?= $box['x'] + $frame['in_x'] ?>cm; top: <?= $box['y'] + $frame['in_top'] ?>cm;
     width: <?= $box['w'] - 2 * $frame['in_x'] ?>cm; height: <?= $box['h'] - $frame['in_top'] - $frame['in_bot'] ?>cm;
     background-color: #FFFFFF; border-radius: <?= $frame['radius'] ?>cm;"></div>

<!-- Badge "MURAH..!" -->
<div class="abs" style="left: <?= $box['x'] + $frame['badge']['x'] ?>cm; top: <?= $box['y'] + $frame['badge']['y'] ?>cm;
     width: <?= $frame['badge']['w'] - 0.2 ?>cm; height: <?= $frame['badge']['h'] - 0.2 ?>cm;
     background-color: #F7A21B; border: 0.1cm solid #F3E6C4; border-radius: 50%;
     text-align: center; line-height: <?= $frame['badge']['h'] - 0.2 ?>cm;
     font-family: arialblack; font-size: 15pt; font-style: italic; color: #E0202B;">MURAH..!</div>
<?php endif; ?>

<!-- Semua teks: masing-masing diregangkan (scaleX/scaleY) mengisi kotak WordArt-nya -->
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