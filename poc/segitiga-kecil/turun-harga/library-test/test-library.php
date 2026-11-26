<?php

declare(strict_types=1);

require dirname(__DIR__, 4) . '/vendor/autoload.php';
require dirname(__DIR__, 4) . '/app/Libraries/SegitigaKecilTurunHargaRenderer.php';
require dirname(__DIR__, 4) . '/app/Libraries/SegitigaKecilDiskonRenderer.php';

use App\Libraries\SegitigaKecilDiskonRenderer;
use App\Libraries\SegitigaKecilTurunHargaRenderer;
use Mpdf\Config\ConfigVariables;
use Mpdf\Config\FontVariables;
use Mpdf\Mpdf;

$root = dirname(__DIR__, 4);
$out = __DIR__;
$fontDir = $root . '/public/assets/fonts';
$background = $root . '/public/assets/img/Picture3-ketinggian.png';
$turunFonts = ['calibri'=>$fontDir.'/calibrib.ttf','poppins'=>$fontDir.'/Poppins-SemiBold.ttf','extra'=>$fontDir.'/Poppins-ExtraBold.ttf'];
$diskonFonts = ['calibri'=>$fontDir.'/calibrib.ttf','poppins'=>$fontDir.'/Poppins-SemiBold.ttf','extra'=>$fontDir.'/Poppins-ExtraBold.ttf','century'=>$fontDir.'/CENTURY.TTF'];
$baseDirs = (new ConfigVariables())->getDefaults()['fontDir'];
$baseData = (new FontVariables())->getDefaults()['fontdata'];
$tc = SegitigaKecilTurunHargaRenderer::fontConfig($turunFonts);
$dc = SegitigaKecilDiskonRenderer::fontConfig($diskonFonts);
$fontdata = array_merge($baseData, $tc['fontdata'], $dc['fontdata']);
$fontdirs = array_values(array_unique(array_merge($baseDirs, $tc['fontDir'], $dc['fontDir'])));
$tempDir = $root . '/writable/mpdf-turun-harga-library-test';

foreach (array_merge(array_values($turunFonts), array_values($diskonFonts), [$background]) as $asset) if (!is_readable($asset)) throw new RuntimeException('Asset tidak tersedia: '.$asset);
if (count(array_intersect(array_values($tc['aliases']), array_values($dc['aliases']))) !== 0) throw new RuntimeException('Alias font collision terdeteksi.');

$makePdf = static function (string $path) use ($fontdata, $fontdirs, $tempDir): Mpdf {
    return new Mpdf(['mode'=>'utf-8','format'=>'A4','orientation'=>'L','margin_left'=>0,'margin_right'=>0,'margin_top'=>0,'margin_bottom'=>0,'autoPageBreak'=>false,'tempDir'=>$tempDir,'fontDir'=>$fontdirs,'fontdata'=>$fontdata,'default_font'=>'arial']);
};
$turun = new SegitigaKecilTurunHargaRenderer($turunFonts, $background);
$diskon = new SegitigaKecilDiskonRenderer($diskonFonts, $background);

$dynamic = [
 ['A','NOBO','OAT MILK 200ML CRMY CLAS','350515',2855,2521,'30 NOVEMBER 2025',100],
 ['B','NOBO','OAT MILK 200ML CRMY CLAS','350515',2855,2521,'30 NOVEMBER 2025',null],
 ['C','ABC','KECAP MANIS 20ML','001234',950,500,'31 JANUARI 2027',10],
 ['D','INDOMIE','MI GORENG ORIGINAL 85GR','022130',12500,9900,'28 FEBRUARI 2027',0],
 ['E','KAPAL API SPECIAL','KOPI BUBUK SPECIAL MIX 10 X 25GR','12345678',35900,29900,'30 SEPTEMBER 2027',250],
 ['F','ULTRA MILK',null,'000125',150000,125900,'31 DESEMBER 2027',null],
 ['G','SUN','PAKET PRODUK PROMO','000001',1250000,999900,'31 AGUSTUS 2027',1000],
 ['H','TEST PRODUCT','','000010',10,0,'30 NOVEMBER 2027',null],
];
$toProduct = static fn(array $r): array => ['brand'=>$r[1],'variant'=>$r[2],'sku_plu'=>$r[3],'normal_price'=>$r[4],'promo_price'=>$r[5],'end_period'=>$r[6],'allocation_pcs'=>$r[7]];
$renderCases = static function (string $path, array $cases, object $renderer, callable $mapper) use ($makePdf): array {
    $pdf=$makePdf($path);$log=[];
    foreach ($cases as $i=>$case) { if ($pdf->page>0) $pdf->AddPage('L'); $renderer->renderOnPage($pdf,$mapper($case)); $log[]=['id'=>$case[0]??$i+1,'page'=>$i+1]; }
    $pdf->Output($path,'F'); return $log;
};

$report=['files'=>[],'regression'=>[],'variation'=>[],'invalid'=>[],'audit'=>[],'mixed'=>[],'dependency'=>[],'notes'=>[]];
$regPath=$out.'/segitiga-kecil-turun-harga-library-v1.pdf';
$report['regression']=$renderCases($regPath,$dynamic,$turun,$toProduct);

$base=['brand'=>'NOBO','variant'=>'OAT MILK 200ML CRMY CLAS','sku_plu'=>'350515','normal_price'=>2855,'promo_price'=>2521,'end_period'=>'30 NOVEMBER 2025','allocation_pcs'=>100];
$valid=[]; foreach ([9=>5,99=>79,999=>799,9999=>7999,99999=>79999,999999=>799999,9999999=>7999999,99999999=>79999999,10=>0] as $n=>$p){$x=$base;$x['normal_price']=$n;$x['promo_price']=$p;$valid[]=['V'.str_pad((string)(count($valid)+1),2,'0',STR_PAD_LEFT),$x];}
foreach ([['V10',['brand'=>'X']],['V11',['brand'=>str_repeat('LONG BRAND ',5)]],['V12',['variant'=>str_repeat('VERY LONG VARIANT ',6)]],['V13',['variant'=>null]],['V14',['variant'=>'']],['V15',['variant'=>'   ']],['V16',['brand'=>'A&B "C" (D) + E < F > G','variant'=>'A&B \'C\' (X) + Y < Z >']]] as [$id,$change]){$x=$base;foreach($change as $k=>$v)$x[$k]=$v;$valid[]=[$id,$x];}
foreach ([['V17','000001'],['V18','001234567890123']] as [$id,$plu]){$x=$base;$x['sku_plu']=$plu;$valid[]=[$id,$x];}
foreach ([['V19',null],['V20',''],['V21',0],['V22',1],['V23',999999]] as [$id,$a]){$x=$base;$x['allocation_pcs']=$a;$valid[]=[$id,$x];}
$months=['JANUARI','FEBRUARI','MARET','APRIL','MEI','JUNI','JULI','AGUSTUS','SEPTEMBER','OKTOBER','NOVEMBER','DESEMBER'];foreach($months as $i=>$m){$x=$base;$x['end_period']='15 '.$m.' 2028';$valid[]=['V'.(24+$i),$x];}
$varPath=$out.'/segitiga-kecil-turun-harga-library-variation.pdf';
$report['variation']=$renderCases($varPath,array_map(static fn($x)=>[$x[0],$x[1]],$valid),$turun,static fn($x)=>$x[1]);

$invalid=[['I01',['brand'=>'']],['I02',['brand'=>null]],['I03',['brand'=>'   ']],['I04',['sku_plu'=>'']],['I05',['sku_plu'=>null]],['I06',['normal_price'=>'']],['I07',['normal_price'=>-1]],['I08',['normal_price'=>'abc']],['I09',['promo_price'=>null]],['I10',['promo_price'=>-1]],['I11',['promo_price'=>'abc']],['I12',['end_period'=>'']],['I13',['end_period'=>'31 FEBRUARI 2028']],['I14',['allocation_pcs'=>-1]],['I15',['allocation_pcs'=>'abc']],['I16',['allocation_pcs'=>1.5]]];
foreach($invalid as [$id,$change]){$input=$base;foreach($change as $k=>$v)$input[$k]=$v;$pdf=$makePdf($out.'/invalid-'.$id.'.pdf');$pdf->AddPage('L');$before=$pdf->page;try{$turun->renderOnPage($pdf,$input);$report['invalid'][]=['id'=>$id,'status'=>'FAIL','exception'=>null,'pageBefore'=>$before,'pageAfter'=>$pdf->page];}catch(Throwable $e){$report['invalid'][]=['id'=>$id,'status'=>'PASS','exception'=>get_class($e),'message'=>$e->getMessage(),'pageBefore'=>$before,'pageAfter'=>$pdf->page];}}
$audit=[['A01',['normal_price'=>0]],['A02',['promo_price'=>2855]],['A03',['promo_price'=>3000]],['A04',['normal_price'=>10.5]],['A05',['allocation_pcs'=>'12']],['A06',['allocation_pcs'=>'  ']],['A07',['_unset'=>'promo_price']],['A08',['normal_price'=>[]]],['A09',['brand'=>[]]]];
foreach($audit as [$id,$change]){$input=$base;if(isset($change['_unset']))unset($input[$change['_unset']]);else foreach($change as $k=>$v)$input[$k]=$v;$pdf=$makePdf($out.'/audit-'.$id.'.pdf');$pdf->AddPage('L');try{$turun->renderOnPage($pdf,$input);$report['audit'][]=['id'=>$id,'status'=>'OBSERVED_ACCEPTED'];}catch(Throwable $e){$report['audit'][]=['id'=>$id,'status'=>'OBSERVED_REJECTED','message'=>$e->getMessage()];}}

$diskonData=['brand'=>'KIN','variant'=>'YOG SLURP 80 STRW','plu'=>'319787','discount'=>50,'normal'=>6703,'promo'=>3352,'period'=>'20 April 2025','allocation'=>100];
$turunData=$toProduct($dynamic[0]);
$mixedPath=$out.'/segitiga-kecil-library-mixed-test.pdf';$pdf=$makePdf($mixedPath);$mixed=[['diskon',$diskonData],['turun',$turunData],['diskon',array_merge($diskonData,['allocation'=>0])],['turun',$turunData],['turun',$turunData],['diskon',$diskonData]];foreach($mixed as $i=>[$kind,$data]){if($i>0)$pdf->AddPage('L');($kind==='diskon'?$diskon:$turun)->renderOnPage($pdf,$data);$report['mixed'][]=['page'=>$i+1,'renderer'=>$kind];}$pdf->Output($mixedPath,'F');

$report['files']=['regression'=>$regPath,'variation'=>$varPath,'mixed'=>$mixedPath];$report['dependency']=['fontDir'=>$fontDir,'background'=>$background,'pocRuntimeDependency'=>false,'windowsFonts'=>false,'mingliuTtc'=>false,'aliasCollision'=>false];$report['notes'][]='Pixel comparison tidak dilakukan karena rasterizer tidak tersedia; hasil PDF memerlukan pemeriksaan visual independen.';$report['notes'][]='Invalid test PDF sementara dibuat per case untuk memastikan tidak ada output parsial; file-file tersebut tidak termasuk output utama.';
file_put_contents($out.'/library-test-report.json',json_encode($report,JSON_PRETTY_PRINT|JSON_UNESCAPED_SLASHES|JSON_UNESCAPED_UNICODE));

$regV2=$out.'/segitiga-kecil-turun-harga-library-v2.pdf';
$varV2=$out.'/segitiga-kecil-turun-harga-library-variation-v2.pdf';
$reportV2=$report;
$reportV2['regressionV2']=$renderCases($regV2,$dynamic,$turun,$toProduct);
$reportV2['variationV2']=$renderCases($varV2,array_map(static fn($x)=>[$x[0],$x[1]],$valid),$turun,static fn($x)=>$x[1]);
$turunNonAlloc=array_merge($turunData,['allocation_pcs'=>0]);
$mixedV2Path=$out.'/segitiga-kecil-library-mixed-test-v2.pdf';$pdf=$makePdf($mixedV2Path);$mixedV2=[['diskon',$diskonData],['turun',$turunData],['diskon',array_merge($diskonData,['allocation'=>0])],['turun',$turunNonAlloc],['turun',$turunData],['diskon',$diskonData]];foreach($mixedV2 as $i=>[$kind,$data]){if($i>0)$pdf->AddPage('L');($kind==='diskon'?$diskon:$turun)->renderOnPage($pdf,$data);$reportV2['mixedV2'][]=['page'=>$i+1,'renderer'=>$kind,'mode'=>$kind==='turun'&&($data['allocation_pcs']??0)>0?'Allocation':($kind==='turun'?'Non-Allocation':'n/a')];}$pdf->Output($mixedV2Path,'F');
$reportV2['filesV2']=['regression'=>$regV2,'variation'=>$varV2,'mixed'=>$mixedV2Path];$reportV2['notes'][]='Tahap 11A: mixed page 4 menggunakan allocation_pcs=0; PDF v1 dipertahankan sebagai pembanding.';$reportV2['notes'][]='I13 tetap pending: library belum memvalidasi validitas kalender.';
file_put_contents($out.'/library-test-report-v2.json',json_encode($reportV2,JSON_PRETTY_PRINT|JSON_UNESCAPED_SLASHES|JSON_UNESCAPED_UNICODE));
echo "Generated library regression, variation, mixed, and report\n";
