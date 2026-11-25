<?php

declare(strict_types=1);

require dirname(__DIR__, 2) . '/vendor/autoload.php';
require dirname(__DIR__, 2) . '/app/Libraries/SegitigaKecilDiskonRenderer.php';

use App\Libraries\SegitigaKecilDiskonRenderer;
use Mpdf\Config\ConfigVariables;
use Mpdf\Config\FontVariables;
use Mpdf\Mpdf;

$root = dirname(__DIR__, 2);
$fontDir = $root . '/public/assets/fonts';
$fontPaths = [
    'calibri' => $fontDir . '/calibrib.ttf', 'poppins' => $fontDir . '/Poppins-SemiBold.ttf',
    'extra' => $fontDir . '/Poppins-ExtraBold.ttf', 'century' => $fontDir . '/CENTURY.TTF',
];
$fontConfig = SegitigaKecilDiskonRenderer::fontConfig($fontPaths);
$d=(new ConfigVariables())->getDefaults(); $fd=(new FontVariables())->getDefaults();
$fontdata=array_merge($fd['fontdata'],$fontConfig['fontdata']);
$mpdf=new Mpdf(['mode'=>'utf-8','format'=>'A4','orientation'=>'L','margin_left'=>0,'margin_right'=>0,'margin_top'=>0,'margin_bottom'=>0,'autoPageBreak'=>false,'tempDir'=>$root.'/writable/mpdf','fontDir'=>array_values(array_unique(array_merge($d['fontDir'],$fontConfig['fontDir'],[$fontDir]))),'fontdata'=>$fontdata,'default_font'=>'arial']);
$renderer=new SegitigaKecilDiskonRenderer($fontPaths,$root.'/public/assets/img/Picture3-ketinggian.png');
$cases=[
 ['A','KIN','YOG SLURP 80 STRW','319787',50,6703,3352,'20 April 2025',100,1],
 ['B','KIN','YOG SLURP 80 STRW','319787',50,6703,3352,'20 April 2025',0,1],
 ['C','ABC','KECAP MANIS','022130',5,1000,950,'1 Mei 2026',0,1],
 ['D','INDOMIE','MIE GORENG 85 GR','012345',20,3500,2800,'30 September 2026',null,1],
 ['E','FRISIAN FLAG','SUSU UHT STRAWBERRY 225 ML','123456',25,125000,93750,'15 November 2026',50,1],
 ['F','DUA KELINCI','KACANG ATOM ORIGINAL','654321',50,250000,125000,'31 Desember 2026',0,2],
 ['G','OREO','ORIGINAL','000789',10,10000,9000,'30 September 2026',25,1],
];
$n=0;
foreach($cases as $row){for($q=0;$q<(int)$row[9];$q++){if($n++>0)$mpdf->AddPage('L');$renderer->renderOnPage($mpdf,['brand'=>$row[1],'variant'=>$row[2],'plu'=>$row[3],'discount'=>$row[4],'normal'=>$row[5],'promo'=>$row[6],'period'=>$row[7],'allocation'=>$row[8]]);}}
$out=__DIR__.'/segitiga-kecil-diskon-library.pdf';$mpdf->Output($out,'F');fwrite(STDOUT,"Generated: {$out}\nPages: {$n}\n");
