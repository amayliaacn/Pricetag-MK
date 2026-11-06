<?php
/** POP One — Frisian Flag HALF PRICE (disc2 only), top of A4 portrait page. */
$e = static fn($v) => htmlspecialchars((string) $v, ENT_QUOTES | ENT_XML1, 'UTF-8');
$f = static fn($v) => number_format((float) $v, 4, '.', '');
$d = $data ?? [];
$box = $box ?? ['x' => 0.0, 'y' => 0.0, 'w' => 21.0, 'h' => 15.0];
$name = trim((string) ($d['name'] ?? ''));
$variant = trim((string) ($d['variant'] ?? ''));
$normal = trim((string) ($d['normal'] ?? ''));
$promo = trim((string) ($d['promo'] ?? ''));
$percent = trim((string) ($d['percent'] ?? '0'));
$period = trim((string) ($d['period'] ?? ''));
$allocation = trim((string) ($d['allocation'] ?? ''));
// Area kiri sebelum panel abu-abu. Nama dibagi berdasarkan estimasi lebar visual,
// bukan jumlah karakter mentah, dan tidak pernah dipaksa memakai textLength.
$leftMax = 108.0;
$nameSize = 12.5;
$nameLines = [$name];
$measure = static function (string $text, float $size): float {
    $total = 0.0;
    foreach (preg_split('/\s+/u', trim($text)) ?: [] as $word) {
        // Conservative estimate for Arial Black as rendered by mPDF SVG.
        $total += mb_strlen($word) * $size * 0.70;
    }
    return max(0.0, $total + max(0, substr_count(trim($text), ' ')) * $size * 0.70);
};
$words = preg_split('/\s+/u', $name, -1, PREG_SPLIT_NO_EMPTY) ?: [];
if (count($words) > 1) {
    // Try every word boundary and choose the most balanced valid pair.
    $best = null;
    for ($cut = 1; $cut < count($words); $cut++) {
        $first = implode(' ', array_slice($words, 0, $cut));
        $second = implode(' ', array_slice($words, $cut));
        $w1 = $measure($first, $nameSize);
        $w2 = $measure($second, $nameSize);
        $overflow = max(0.0, $w1 - $leftMax) + max(0.0, $w2 - $leftMax);
        $score = ($overflow * 1000) + abs($w1 - $w2);
        if ($best === null || $score < $best['score']) {
            $best = ['first' => $first, 'second' => $second, 'score' => $score];
        }
    }
    if ($best !== null) $nameLines = [$best['first'], $best['second']];
}
while ($nameSize > 6.5 && max(array_map(fn($line) => $measure($line, $nameSize), $nameLines)) > $leftMax) {
    $nameSize -= 0.5;
}
$nameY = count($nameLines) === 1 ? [38] : [32, 47];
$variantY = count($nameLines) === 1 ? 52 : 59;
$variantSize = 10.5;
while ($variant !== '' && $variantSize > 6.0 && $measure($variant, $variantSize) > $leftMax) $variantSize -= 0.5;
$pctSize = mb_strlen($percent) >= 3 ? 43 : (mb_strlen($percent) === 2 ? 55 : 61);
$promoSize = mb_strlen($promo) > 8 ? 14 : 17;
$normalStrikeEnd = min(121, 50 + (mb_strlen($normal) * 7.1));
$panelLeft = 132.0;
$panelRight = 199.0;
$panelCenterX = ($panelLeft + $panelRight) / 2;
$halfX = $panelCenterX - 27.0;
$priceX = $panelCenterX - 28.0;
$discX = $panelCenterX - 13.0;
$pctRenderSize = mb_strlen($percent) >= 3 ? 36 : (mb_strlen($percent) === 2 ? 46 : 53);
$pctVisualWidth = mb_strlen($percent) >= 3 ? 62.0 : (mb_strlen($percent) === 2 ? 48.0 : 28.0);
$pctX = $panelCenterX - ($pctVisualWidth / 2);
$percentSignX = $panelCenterX - 6.0;
?>
<style>
body { margin: 0; padding: 0; }
.ds2-layer { position: absolute; margin: 0; padding: 0; }
</style>
<?php if (!empty($img)): ?>
<div class="ds2-layer" style="left:<?= $f($box['x']) ?>cm;top:<?= $f($box['y']) ?>cm;width:<?= $f($box['w']) ?>cm;height:<?= $f($box['h']) ?>cm;">
  <img src="<?= $e($img) ?>" style="width:<?= $f($box['w']) ?>cm;height:<?= $f($box['h']) ?>cm;" />
</div>
<?php endif; ?>
<div class="ds2-layer" style="left:<?= $f($box['x']) ?>cm;top:<?= $f($box['y']) ?>cm;width:<?= $f($box['w']) ?>cm;height:<?= $f($box['h']) ?>cm;">
<svg xmlns="http://www.w3.org/2000/svg" width="<?= $f($box['w']) ?>cm" height="<?= $f($box['h']) ?>cm" viewBox="0 0 210 150" preserveAspectRatio="none">
  <!-- Picture4.jpg supplies yellow border, white rounded frame and MURAH logo. -->
  <!-- Right gray panel: square left corners, large rounded right corners. -->
  <path d="M126 7 H193 C200 7 205 14 205 24 V126 C205 136 200 143 193 143 H126 Z" fill="#E5E5E5"/>

  <!-- Left product block; first baseline below logo. -->
  <?php foreach ($nameLines as $index => $line): ?>
  <text x="12" y="<?= $f($nameY[$index]) ?>" font-family="arialblack,Arial" font-weight="bold" font-size="<?= $f($nameSize) ?>" fill="#0800FF"><?= $e($line) ?></text>
  <?php endforeach; ?>
  <?php if ($variant !== ''): ?>
  <text x="12" y="<?= $f($variantY) ?>" font-family="arial,Arial" font-size="<?= $f($variantSize) ?>" fill="#0800FF"><?= $e($variant) ?></text>
  <?php endif; ?>

  <!-- Old price -->
  <text x="12" y="73" font-family="centurygothic,Arial" font-size="7.5" fill="#ED1C24">Harga</text>
  <text x="12" y="80" font-family="centurygothic,Arial" font-size="7.5" fill="#ED1C24">Normal</text>
  <?php if ($normal !== ''): ?>
  <text x="41" y="73" font-family="arial,Arial" font-size="5.2" fill="#ED1C24">Rp</text>
  <text x="50" y="80" font-family="arial,Arial" font-size="10.8" fill="#ED1C24"><?= $e($normal) ?></text>
  <?php if (!empty($d['strike'])): ?>
  <line x1="50" y1="79" x2="<?= $f($normalStrikeEnd) ?>" y2="70" stroke="#111111" stroke-width="1.15" />
  <?php endif; ?>
  <?php endif; ?>

  <!-- Discount price -->
  <text x="12" y="99" font-family="centurygothic,Arial" font-size="7.5" fill="#ED1C24">Harga</text>
  <text x="12" y="106" font-family="centurygothic,Arial" font-size="7.5" fill="#ED1C24">Diskon</text>
  <text x="41" y="98" font-family="arial,Arial" font-weight="bold" font-size="6" fill="#ED1C24">Rp</text>
  <text x="50" y="106" font-family="arialblack,Arial" font-weight="bold" font-size="<?= $f($promoSize) ?>" fill="#ED1C24"><?= $e($promo) ?></text>

  <!-- Yellow period/allocation capsule inside existing white frame -->
  <rect x="10" y="115" width="113" height="24" rx="11" fill="#FFF000" />
  <text x="15" y="124.5" font-family="arialblack,Arial" font-weight="bold" font-size="5.7" fill="#ED1C24">Akhir Periode: <?= $e($period) ?></text>
  <text x="15" y="133" font-family="arialblack,Arial" font-weight="bold" font-size="5.7" fill="#ED1C24">Alokasi: <?= $e($allocation) ?></text>

  <!-- HALF PRICE: centered within the gray panel, with a thin safe margin. -->
  <text x="<?= $f($halfX) ?>" y="32" font-family="arial,Arial" font-weight="normal" font-size="21" fill="#ED1C24">HALF</text>
  <text x="<?= $f($priceX) ?>" y="54" font-family="arial,Arial" font-weight="normal" font-size="20" fill="#ED1C24">PRICE</text>
  <text x="<?= $f($discX) ?>" y="75" font-family="arial,Arial" font-weight="bold" font-size="10.5" fill="#ED1C24">Disc.</text>
  <?php
  // mPDF SVG builds may ignore text-anchor; use the shared panel center and
  // an adaptive visual width to place the value explicitly.
  ?>
  <text x="<?= $f($pctX) ?>" y="115" font-family="arial,Arial" font-weight="bold" font-size="<?= $f($pctRenderSize) ?>" fill="#ED1C24"><?= $e($percent) ?></text>
  <text x="<?= $f($percentSignX) ?>" y="137" font-family="arial,Arial" font-weight="bold" font-size="13" fill="#ED1C24">%</text>
</svg>
</div>
