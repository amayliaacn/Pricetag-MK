<?php
/**
 * View: POP Turun Harga (template turun-harga-tgg di MpdfLibreTemplate::buildTgg). TANPA latar:
 * kuning + logo MURAH sudah tercetak di kertas.
 * $S   = tiap teks + hasil hitung transform SVG
 * $L   = ['strike' => garis coret harga normal]
 * $box = posisi/ukuran halaman (cm)
 */
$e = static fn($s) => htmlspecialchars((string) $s, ENT_QUOTES, 'UTF-8');
?>
<style>
    body { margin: 0; padding: 0; }
    .abs { position: absolute; margin: 0; padding: 0; }
</style>

<?php if (! empty($L['strike'])): $k = $L['strike']; ?>
<!-- Garis coret MERAH harga normal: digambar LEBIH DULU supaya berada di belakang angka (angka hitam tampil di atasnya) -->
<div class="abs" style="left: <?= $k['left'] ?>cm; top: <?= $k['top'] ?>cm; width: <?= $k['w'] ?>cm; height: <?= $k['h'] ?>cm;">
    <svg xmlns="http://www.w3.org/2000/svg" width="<?= $k['w'] ?>cm" height="<?= $k['h'] ?>cm" viewBox="0 0 <?= $k['w'] ?> <?= $k['h'] ?>">
        <line x1="0" y1="<?= $k['y1'] ?>" x2="<?= $k['w'] ?>" y2="<?= $k['y2'] ?>" stroke="#FF0000" stroke-width="<?= $k['sw'] ?>"/>
    </svg>
</div>
<?php endif; ?>

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