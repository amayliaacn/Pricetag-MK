<?php

declare(strict_types=1);

require dirname(__DIR__, 4) . '/vendor/autoload.php';

use Mpdf\Config\ConfigVariables;
use Mpdf\Config\FontVariables;
use Mpdf\Mpdf;

$root = dirname(__DIR__, 4);
$fontDir = $root . '/public/assets/fonts';
$background = $root . '/public/assets/img/Picture3-ketinggian.png';
$outDir = __DIR__;
$tempDir = $root . '/writable/mpdf-turun-harga-variation-test';
$refPt = 10 / .352778;
$esc = static fn(string $v): string => htmlspecialchars($v, ENT_QUOTES, 'UTF-8');
$fontdata = (new FontVariables())->getDefaults()['fontdata'];
$fontdata += ['vcalibri' => ['R'=>'calibrib.ttf'], 'vpoppins'=>['R'=>'Poppins-SemiBold.ttf'], 'vextra'=>['R'=>'Poppins-ExtraBold.ttf']];
$fontdirs = array_values(array_unique(array_merge((new ConfigVariables())->getDefaults()['fontDir'], [$fontDir])));

$boxes = [
 'plu'=>[84,11,43,8,'poppins','#000000','right'], 'brand'=>[10.45,30,121.6,28.58,'calibri','#0000FF','center'],
 'variant'=>[19.25,64,104,10,'poppins','#000000','center'], 'normalRp'=>[5.75,90.475,10,9,'poppins','#FF0000','left'],
 'normal'=>[18,87.475,54,19.05,'extra','#000000','left'], 'promoRp'=>[5.75,138,17,14,'poppins','#FF0000','left'],
 'promo'=>[36,120,92,47.63,'extra','#FF0000','left'], 'periodAlloc'=>[8,173,125,5.29,'poppins','#000000','center'],
 'periodNoAlloc'=>[8,179.28,125,5.29,'poppins','#000000','center'], 'allocation'=>[8.25,184.5,126,6.35,'poppins','#FF0000','center'],
];

$monthMap = ['JANUARI','FEBRUARI','MARET','APRIL','MEI','JUNI','JULI','AGUSTUS','SEPTEMBER','OKTOBER','NOVEMBER','DESEMBER'];
$formatDate = static function (mixed $v) use ($monthMap): string {
    if (!is_string($v) || !preg_match('/^\s*(\d{1,2})\s+([^\s]+)\s+(\d{4})\s*$/u', $v, $m)) throw new InvalidArgumentException('end_period format tidak valid');
    $month = mb_strtoupper($m[2], 'UTF-8'); if (!in_array($month, $monthMap, true)) throw new InvalidArgumentException('bulan tidak valid');
    $dt = DateTime::createFromFormat('!j F Y', $m[1] . ' ' . ucfirst(mb_strtolower($month)) . ' ' . $m[3]);
    if (!$dt || $dt->format('j') !== (string)(int)$m[1] || mb_strtoupper($dt->format('F'), 'UTF-8') !== $month) { /* month locale-independent fallback below */ }
    if ((int)$m[1] < 1 || (int)$m[1] > 31) throw new InvalidArgumentException('tanggal tidak valid');
    $monthNo = array_search($month, $monthMap, true) + 1; if (!checkdate($monthNo, (int)$m[1], (int)$m[3])) throw new InvalidArgumentException('tanggal kalender tidak valid');
    return (int)$m[1] . ' ' . $month . ' ' . $m[3];
};

$normalize = static function (array $p) use ($formatDate): array {
    foreach (['brand','sku_plu','normal_price','promo_price','end_period'] as $key) if (!array_key_exists($key, $p)) throw new InvalidArgumentException("field {$key} wajib");
    if (!is_string($p['brand']) || trim($p['brand']) === '') throw new InvalidArgumentException('brand harus teks');
    if (!is_string($p['sku_plu']) || !preg_match('/^\d+$/', $p['sku_plu'])) throw new InvalidArgumentException('sku_plu harus string digit');
    foreach (['normal_price','promo_price'] as $key) if (!is_int($p[$key]) && !(is_float($p[$key]) && is_finite($p[$key])) || $p[$key] < 0) throw new InvalidArgumentException("{$key} harus numerik non-negatif");
    if (array_key_exists('allocation_pcs',$p) && $p['allocation_pcs'] !== null && $p['allocation_pcs'] !== '' && (!is_int($p['allocation_pcs']) && !is_float($p['allocation_pcs']) && !is_string($p['allocation_pcs']) || !is_numeric($p['allocation_pcs']) || (float)$p['allocation_pcs'] < 0 || floor((float)$p['allocation_pcs']) !== (float)$p['allocation_pcs'])) throw new InvalidArgumentException('allocation_pcs invalid');
    $a = $p['allocation_pcs'] ?? null; $mode = is_numeric($a) && (float)$a > 0 ? 'Allocation' : 'Non-Allocation';
    return ['brand'=>mb_strtoupper(trim($p['brand']),'UTF-8'),'variant'=>is_string($p['variant']??null)&&trim($p['variant'])!==''?mb_strtoupper(trim($p['variant']),'UTF-8'):null,'plu'=>'PLU : '.$p['sku_plu'],'normal'=>number_format((int)$p['normal_price'],0,',','.'),'promo'=>number_format((int)$p['promo_price'],0,',','.'),'period'=>'AKHIR PERIODE : '.$formatDate($p['end_period']),'allocation'=>'( PROGRAM SELAMA ALOKASI MASIH ADA )','mode'=>$mode];
};

$makePdf = static function(string $file) use ($fontdata,$fontdirs,$tempDir): Mpdf { return new Mpdf(['mode'=>'utf-8','format'=>'A4','orientation'=>'L','margin_left'=>0,'margin_right'=>0,'margin_top'=>0,'margin_bottom'=>0,'autoPageBreak'=>false,'tempDir'=>$tempDir,'fontDir'=>$fontdirs,'fontdata'=>$fontdata,'default_font'=>'arial']); };

$renderCase = static function(Mpdf $pdf, array $d, bool $debug) use (&$boxes,$background,$refPt,$esc): array {
    $texts=['plu'=>$d['plu'],'brand'=>$d['brand'],'normalRp'=>'Rp','normal'=>$d['normal'],'promoRp'=>'Rp','promo'=>$d['promo'],'period'=>$d['period']]; if($d['variant']!==null)$texts['variant']=$d['variant']; if($d['mode']==='Allocation')$texts['allocation']=$d['allocation']; $periodKey=$d['mode']==='Allocation'?'periodAlloc':'periodNoAlloc'; $metrics=[];
$pop=static function(float $px,float $py)use(&$pdf,&$metrics,$texts,$periodKey,$boxes,$background,$refPt,$esc,$debug):void{$img='<img src="'.htmlspecialchars($background,ENT_QUOTES,'UTF-8').'" style="display:block;width:142.5mm;height:200mm;" />';$pdf->WriteFixedPosHTML($img,$px,$py,142.5,200,'auto');$svg='<svg xmlns="http://www.w3.org/2000/svg" width="142.5mm" height="200mm" viewBox="0 0 142.5 200" preserveAspectRatio="none">';foreach($texts as $key=>$text){$bk=$key==='period'?$periodKey:$key;[$x,$y,$w,$h,$font,$color,$align]=$boxes[$bk];$pdf->SetFont('v'.$font,'',$refPt);$natural=max(.01,(float)$pdf->GetStringWidth($text));$cap=(($pdf->CurrentFont['desc']['CapHeight']??700)/1000)*10;$ref=match($key){'brand'=>'NOBO','variant'=>'OAT MILK 200ML CRMY CLAS','normal'=>'2.855','promo'=>'2.521',default=>$text};$pdf->SetFont('v'.$font,'',$refPt);$refW=max(.01,(float)$pdf->GetStringWidth($ref));$width=in_array($key,['brand','variant','normal','promo'],true)?min($w,max($key==='variant'?18:12,$natural*($w/$refW))):$w;$sx=$width/$natural;$sy=$h/max(.01,$cap);$base=$y+$h-.7;$start=match($align){'center'=>$x+(($w-$width)/2),'right'=>$x+$w-$width,default=>$x};$svg.='<g transform="translate('.$start.' '.$base.') scale('.$sx.' '.$sy.')" fill="'.$color.'"><text x="0" y="0" font-family="v'.$font.'" font-size="10">'.$esc($text).'</text></g>';if($debug)$svg.='<rect x="'.$x.'" y="'.$y.'" width="'.$w.'" height="'.$h.'" fill="none" stroke="#ff8800" stroke-width=".2" stroke-dasharray="1,1"/>';$metrics[$key]=['naturalW'=>$natural,'actualW'=>$width,'actualX'=>$start,'sx'=>$sx,'sy'=>$sy,'baseline'=>$base];}$n=$metrics['normal'];$strike=['x1'=>$n['actualX']-.5,'y1'=>$boxes['normal'][1]+$boxes['normal'][3]-1,'x2'=>$n['actualX']+$n['actualW']+.5,'y2'=>$boxes['normal'][1]+2];$metrics['strike']=$strike;$svg.='<line x1="'.$strike['x1'].'" y1="'.$strike['y1'].'" x2="'.$strike['x2'].'" y2="'.$strike['y2'].'" stroke="#FF0000" stroke-width="1.4111" stroke-linecap="round"/>';if($debug)$svg.='<rect x="'.$n['actualX'].'" y="'.$boxes['normal'][1].'" width="'.$n['actualW'].'" height="'.$boxes['normal'][3].'" fill="none" stroke="#00a060" stroke-width=".25" stroke-dasharray="1,1"/>';$svg.='</svg>';$pdf->WriteFixedPosHTML($svg,$px,$py,142.5,200,'auto');};$pop(3,5);$pop(151.5,5);return $metrics; };

$valid = [];
$base = ['brand'=>'NOBO','variant'=>'OAT MILK 200ML CRMY CLAS','sku_plu'=>'350515','normal_price'=>2855,'promo_price'=>2521,'end_period'=>'30 NOVEMBER 2025','allocation_pcs'=>100];
foreach ([9=>5,99=>79,999=>799,9999=>7999,99999=>79999,999999=>799999,9999999=>7999999,99999999=>79999999,10=>0] as $n=>$p) {$c=$base;$c['normal_price']=$n;$c['promo_price']=$p;$valid[]=['V'.str_pad((string)(count($valid)+1),2,'0',STR_PAD_LEFT),$c];}
$extras=[['V10',['brand'=>'X']],['V11',['brand'=>str_repeat('LONG BRAND ',5)]],['V12',['variant'=>str_repeat('VERY LONG VARIANT ',6)]],['V13',['variant'=>null]],['V14',['variant'=>'']],['V15',['variant'=>'   ']],['V16',['brand'=>'A&B "C" (D) + E < F > G','variant'=>'A&B \'C\' (X) + Y < Z >']]];
foreach($extras as [$id,$chg]){$c=$base;foreach($chg as $k=>$v)$c[$k]=$v;$valid[]=[$id,$c];}
foreach([['V17','000001'],['V18','001234567890123']] as [$id,$plu]){$c=$base;$c['sku_plu']=$plu;$valid[]=[$id,$c];}
foreach([['V19',null],['V20',''],['V21',0],['V22',1],['V23',999999]] as [$id,$a]){$c=$base;$c['allocation_pcs']=$a;$valid[]=[$id,$c];}
foreach(array_slice($monthMap,0,12) as $i=>$month){$c=$base;$c['end_period']='15 '.$month.' 2028';$valid[]=['V'.(24+$i),$c];}

$regression=[
 ['A',['brand'=>'NOBO','variant'=>'OAT MILK 200ML CRMY CLAS','sku_plu'=>'350515','normal_price'=>2855,'promo_price'=>2521,'end_period'=>'30 NOVEMBER 2025','allocation_pcs'=>100]],
 ['B',['brand'=>'NOBO','variant'=>'OAT MILK 200ML CRMY CLAS','sku_plu'=>'350515','normal_price'=>2855,'promo_price'=>2521,'end_period'=>'30 NOVEMBER 2025','allocation_pcs'=>null]],
 ['C',['brand'=>'ABC','variant'=>'KECAP MANIS 20ML','sku_plu'=>'001234','normal_price'=>950,'promo_price'=>500,'end_period'=>'31 JANUARI 2027','allocation_pcs'=>10]],
 ['D',['brand'=>'INDOMIE','variant'=>'MI GORENG ORIGINAL 85GR','sku_plu'=>'022130','normal_price'=>12500,'promo_price'=>9900,'end_period'=>'28 FEBRUARI 2027','allocation_pcs'=>0]],
 ['E',['brand'=>'KAPAL API SPECIAL','variant'=>'KOPI BUBUK SPECIAL MIX 10 X 25GR','sku_plu'=>'12345678','normal_price'=>35900,'promo_price'=>29900,'end_period'=>'30 SEPTEMBER 2027','allocation_pcs'=>250]],
 ['F',['brand'=>'ULTRA MILK','variant'=>null,'sku_plu'=>'000125','normal_price'=>150000,'promo_price'=>125900,'end_period'=>'31 DESEMBER 2027','allocation_pcs'=>null]],
 ['G',['brand'=>'SUN','variant'=>'PAKET PRODUK PROMO','sku_plu'=>'000001','normal_price'=>1250000,'promo_price'=>999900,'end_period'=>'31 AGUSTUS 2027','allocation_pcs'=>1000]],
 ['H',['brand'=>'TEST PRODUCT','variant'=>'','sku_plu'=>'000010','normal_price'=>10,'promo_price'=>0,'end_period'=>'30 NOVEMBER 2027','allocation_pcs'=>null]],
];

$invalid=[['I01',['brand'=>'']],['I02',['brand'=>null]],['I03',['brand'=>'   ']],['I04',['sku_plu'=>'']],['I05',['sku_plu'=>null]],['I06',['normal_price'=>'']],['I07',['normal_price'=>-1]],['I08',['normal_price'=>'abc']],['I09',['promo_price'=>null]],['I10',['promo_price'=>-1]],['I11',['promo_price'=>'abc']],['I12',['end_period'=>'']],['I13',['end_period'=>'31 FEBRUARI 2028']],['I14',['allocation_pcs'=>-1]],['I15',['allocation_pcs'=>'abc']],['I16',['allocation_pcs'=>1.5]]];

$runPdf = static function(string $path,array $cases,bool $debug)use($makePdf,$normalize,$renderCase):array{$pdf=$makePdf($path);$log=[];foreach($cases as $i=>[$id,$raw]){if($pdf->page>0)$pdf->AddPage('L');$d=$normalize($raw);$m=$renderCase($pdf,$d,$debug);$log[]=['id'=>$id,'page'=>$i+1,'mode'=>$d['mode'],'metrics'=>$m];}$pdf->Output($path,'F');return $log;};

$report=['valid'=>[],'regression'=>[],'invalid'=>[],'audit'=>[],'generated'=>[],'notes'=>[]];
$report['valid']=$runPdf($outDir.'/segitiga-kecil-turun-harga-variation-valid.pdf',$valid,false);
$runPdf($outDir.'/segitiga-kecil-turun-harga-variation-valid-debug.pdf',$valid,true);
$report['regressionV2']=$runPdf($outDir.'/segitiga-kecil-turun-harga-regression-v2.pdf',$regression,false);
$report['regressionDataset'] = array_map(static fn(array $row): array => ['id'=>$row[0],'data'=>$row[1]], $regression);
foreach($invalid as [$id,$chg]){$raw=$base;foreach($chg as $k=>$v)$raw[$k]=$v;try{$normalize($raw);$report['invalid'][]=['id'=>$id,'status'=>'FAIL','message'=>'Input diterima'];}catch(Throwable $e){$report['invalid'][]=['id'=>$id,'status'=>'PASS','message'=>$e->getMessage()];}}
$auditCases=[
 ['A01','normal_price=0',['normal_price'=>0]],
 ['A02','promo_price=normal_price',['promo_price'=>2855]],
 ['A03','promo_price>normal_price',['promo_price'=>3000]],
 ['A04','normal_price desimal',['normal_price'=>10.5]],
 ['A05','allocation_pcs numeric string',['allocation_pcs'=>'12']],
 ['A06','allocation_pcs whitespace',['allocation_pcs'=>'  ']],
 ['A07','promo_price missing',['_unset'=>'promo_price']],
 ['A08','normal_price array',['normal_price'=>[]]],
 ['A09','brand array',['brand'=>[]]],
];
foreach($auditCases as [$id,$description,$chg]){$raw=$base;if(isset($chg['_unset']))unset($raw[$chg['_unset']]);else foreach($chg as $k=>$v)$raw[$k]=$v;try{$d=$normalize($raw);$report['audit'][]=['id'=>$id,'description'=>$description,'status'=>'OBSERVED_ACCEPTED','mode'=>$d['mode']];}catch(Throwable $e){$report['audit'][]=['id'=>$id,'description'=>$description,'status'=>'OBSERVED_REJECTED','message'=>$e->getMessage()];}}
$report['notes'][]='Renderer harness menyalin konfigurasi box, font, alignment eksplisit, CapHeight scaling, dan dynamic strike line Dynamic V2; master tidak diubah.';
$report['notes'][]='Rasterizer/pixel diff tidak tersedia; validasi visual otomatis tidak dilakukan.';
file_put_contents($outDir.'/variation-test-report.json',json_encode($report,JSON_PRETTY_PRINT|JSON_UNESCAPED_UNICODE|JSON_INVALID_UTF8_SUBSTITUTE));
fwrite(STDOUT,"Generated valid=".$outDir."/segitiga-kecil-turun-harga-variation-valid.pdf\nGenerated debug=".$outDir."/segitiga-kecil-turun-harga-variation-valid-debug.pdf\nGenerated regression=".$outDir."/segitiga-kecil-turun-harga-regression.pdf\nGenerated regressionV2=".$outDir."/segitiga-kecil-turun-harga-regression-v2.pdf\nReport=".$outDir."/variation-test-report.json\n");
