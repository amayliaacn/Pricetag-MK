<?php
$e = static fn($s) => htmlspecialchars((string) $s, ENT_QUOTES, 'UTF-8');
$box = $L['box'];
?>
<style>
    body  { margin: 0; padding: 0; }
    .abs  { position: absolute; margin: 0; padding: 0; white-space: nowrap; }
    .r    { text-align: right; }
    .c    { text-align: center; }
    .red  { color: #FF0000; }
    .outline-red { text-shadow: .5pt .5pt 0 #FF0000, -.5pt -.5pt 0 #FF0000, .5pt -.5pt 0 #FF0000, -.5pt .5pt 0 #FF0000; }
</style>

<!-- GAMBAR KOTAK KUNING: Picture1.jpg, 19,51 x 27,94 cm -->
<div class="abs" style="left: <?= $box['x'] ?>cm; top: <?= $box['y'] ?>cm; width: <?= $box['w'] ?>cm; height: <?= $box['h'] ?>cm;">
    <img src="<?= $e($img) ?>" style="width: <?= $box['w'] ?>cm; height: <?= $box['h'] ?>cm;">
</div>

<!-- Akhir Periode -->
<div class="abs r" style="left: <?= $L['periode']['left'] ?>cm; top: <?= $L['periode']['top'] ?>cm; width: <?= $L['periode']['w'] ?>cm;
     font-family: <?= $F['note']['family'] ?>; font-style: italic; font-size: <?= $F['note']['pt'] ?>pt; line-height: <?= $F['note']['pt'] ?>pt;">
    Akhir Periode : <?= $e($T['periode']) ?>
</div>

<!-- Nama barang / merk (serif, hitam) -->
<div class="abs c" style="left: <?= $L['merk']['left'] ?>cm; top: <?= $L['merk']['top'] ?>cm; width: <?= $L['merk']['w'] ?>cm;
     font-family: <?= $F['merk']['family'] ?>; font-size: <?= $F['merk']['pt'] ?>pt; line-height: <?= $F['merk']['pt'] ?>pt; color: #000000;">
    <?= $e($T['merk']) ?>
</div>

<!-- PLU -->
<div class="abs r" style="left: <?= $L['plu']['left'] ?>cm; top: <?= $L['plu']['top'] ?>cm; width: <?= $L['plu']['w'] ?>cm;
     font-family: <?= $F['plu']['family'] ?>; font-style: italic; font-size: <?= $F['plu']['pt'] ?>pt; line-height: <?= $F['plu']['pt'] ?>pt;">
    ( PLU : <?= $e($T['plu']) ?>)
</div>

<!-- Harga normal (dicoret miring) -->
<div class="abs red outline-red" style="left: <?= $L['rp_small']['left'] ?>cm; top: <?= $L['rp_small']['top'] ?>cm; width: 3cm;
     font-family: <?= $F['rp']['family'] ?>; font-weight: bold; font-style: italic; font-size: <?= $F['rp_small']['pt'] ?>pt; line-height: <?= $F['rp_small']['pt'] ?>pt;">Rp</div>

<div class="abs red outline-red" style="left: <?= $L['normal']['left'] ?>cm; top: <?= $L['normal']['top'] ?>cm; width: 12cm;
     font-family: <?= $F['normal']['family'] ?>; font-weight: bold; font-size: <?= $F['normal']['pt'] ?>pt; line-height: <?= $F['normal']['pt'] ?>pt;">
    <?= $e($T['normal']) ?>
</div>

<!-- Garis coret (miring) -->
<div class="abs" style="left: <?= $L['strike']['left'] ?>cm; top: <?= $L['strike']['top'] ?>cm; width: <?= $L['strike']['w'] ?>cm; height: <?= $L['strike']['h'] ?>cm;">
    <svg xmlns="http://www.w3.org/2000/svg" width="<?= $L['strike']['w'] ?>cm" height="<?= $L['strike']['h'] ?>cm"
         viewBox="0 0 <?= $L['strike']['vw'] ?> <?= $L['strike']['vh'] ?>">
        <line x1="0" y1="<?= $L['strike']['y1'] ?>" x2="<?= $L['strike']['vw'] ?>" y2="<?= $L['strike']['y2'] ?>"
              stroke="#000000" stroke-width="<?= $L['strike']['sw'] ?>" />
    </svg>
</div>

<!-- Harga promo: "Rp" (angka besar & varian ada di blok SVG di bawah) -->
<div class="abs red outline-red" style="left: <?= $L['rp_big']['left'] ?>cm; top: <?= $L['rp_big']['top'] ?>cm; width: 3cm;
     font-family: <?= $F['rp']['family'] ?>; font-weight: bold; font-style: italic; font-size: <?= $F['rp_big']['pt'] ?>pt; line-height: <?= $F['rp_big']['pt'] ?>pt;">Rp</div>

<?php if ($T['small'] !== ''): ?>
<div class="abs red outline-red r" style="left: <?= $L['small']['left'] ?>cm; top: <?= $L['small']['top'] ?>cm; width: <?= $L['small']['w'] ?>cm;
     font-family: <?= $F['small']['family'] ?>; font-weight: bold; font-size: <?= $F['small']['pt'] ?>pt; line-height: <?= $F['small']['pt'] ?>pt;">
    .<?= $e($T['small']) ?>
</div>
<?php endif; ?>

<!-- VARIAN + ANGKA BESAR: diregangkan seperti WordArt (SVG, 1 satuan = 1 mm).
     Ukuran diatur di PopA4Pdf.php -> BIG_BOX, VARIANT_BOX, BIG_BOLD -->
<div class="abs" style="left: <?= $box['x'] ?>cm; top: <?= $box['y'] ?>cm; width: <?= $box['w'] ?>cm; height: <?= $box['h'] ?>cm;">
    <svg xmlns="http://www.w3.org/2000/svg" width="<?= $box['w'] ?>cm" height="<?= $box['h'] ?>cm" viewBox="0 0 <?= $S['w'] ?> <?= $S['h'] ?>">
        <?php $t = $S['variant']; ?>
        <g transform="translate(<?= $t['x'] ?>,<?= $t['y'] ?>) scale(<?= $t['sx'] ?>,<?= $t['sy'] ?>)">
            <text x="0" y="0" font-family="<?= $t['family'] ?>" font-size="<?= $t['fs'] ?>"
                  fill="#00B0F0" stroke="#000000" stroke-width="0.25"><?= $e($t['text']) ?></text>
        </g>
        <?php $t = $S['big']; ?>
        <g transform="translate(<?= $t['x'] ?>,<?= $t['y'] ?>) scale(<?= $t['sx'] ?>,<?= $t['sy'] ?>)">
            <text x="0" y="0" font-family="<?= $t['family'] ?>" font-size="<?= $t['fs'] ?>"
                  fill="#FF0000" stroke="#FF0000" stroke-width="<?= $S['bold'] ?>"><?= $e($t['text']) ?></text>
        </g>
    </svg>
</div>