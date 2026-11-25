<?php

declare(strict_types=1);
require dirname(__DIR__, 2) . '/vendor/autoload.php';
use Mpdf\Config\ConfigVariables;
use Mpdf\Config\FontVariables;
use Mpdf\Mpdf;

$root = dirname(__DIR__, 2);
$out = __DIR__ . '/segitiga-kecil-diskon-dynamic.pdf';
$fontDir = __DIR__ . '/fonts'; $win = 'C:/Windows/Fonts';
$bg = $root . '/public/assets/img/Picture3-ketinggian.png';
$fonts = [
 'calibri'=>['file'=>'calibrib.ttf','path'=>$win.'/calibrib.ttf'],
 'poppins'=>['file'=>'Poppins-SemiBold.ttf','path'=>$fontDir.'/Poppins-SemiBold.ttf'],
 'extra'=>['file'=>'Poppins-ExtraBold.ttf','path'=>$root.'/public/assets/fonts/Poppins-ExtraBold.ttf'],
 'century'=>['file'=>'CENTURY.TTF','path'=>$win.'/CENTURY.TTF'],
];
foreach($fonts as $f) if(!is_file($f['path'])) throw new RuntimeException('Font missing: '.$f['path']);
if(!is_file($bg)) throw new RuntimeException('Background missing: '.$bg);
$d=(new ConfigVariables())->getDefaults(); $fd=(new FontVariables())->getDefaults(); $fontdata=$fd['fontdata'];
foreach($fonts as $a=>$f) $fontdata[$a]=['R'=>$f['file']];
$pdf=new Mpdf(['mode'=>'utf-8','format'=>'A4','orientation'=>'L','margin_left'=>0,'margin_right'=>0,'margin_top'=>0,'margin_bottom'=>0,'autoPageBreak'=>false,'tempDir'=>$root.'/writable/mpdf','fontDir'=>array_values(array_unique(array_merge($d['fontDir'],[$fontDir,$root.'/public/assets/fonts',$win]))),'fontdata'=>$fontdata,'default_font'=>'arial']);
$e=static fn(string $v):string=>htmlspecialchars($v,ENT_QUOTES,'UTF-8');

// Values copied from the final V1/V2 source, not from old audit notes.
$common=[
 'brand'=>[10.45,30,121.6,28.58,'calibri','#0000FF'], 'plu'=>[84,8,43,8,'poppins','#111111'],
 'product'=>[19.25,64,104,10,'poppins','#111111'], 'disc'=>[12,84,29,10,'poppins','#111111'],
 'discount'=>[47,81,64,47,'century','#FF0000'], 'percent'=>[116,114,17,14,'poppins','#FF0000'],
 'normalRp'=>[4.5,138,10,9,'poppins','#FF0000'], 'normal'=>[18,138,52,16,'extra','#111111'],
 'promoRp'=>[59,167,10,9,'poppins','#FF0000'], 'promo'=>[72,154,62,35,'extra','#FF0000'],
];
$conditional=[
 'v1'=>['period'=>[8.5,160,51,5.1,'poppins','#111111'],'date'=>[12.5,168,43,5.1,'poppins','#111111'],'allocation'=>[8.5,176.5,45,5.1,'poppins','#FF0000'],'still'=>[16.5,184,29,4.65,'poppins','#FF0000']],
 'v2'=>['period'=>[11.05,167.775,45.9,5.1,'poppins','#111111'],'date'=>[12.5,175.775,43,5.1,'poppins','#111111']],
];
$dataCases=[
 ['A','KIN','YOG SLURP 80 STRW','319787',50,6703,3352,'20 April 2025',100,1],
 ['B','KIN','YOG SLURP 80 STRW','319787',50,6703,3352,'20 April 2025',0,1],
 ['C','ABC','KECAP MANIS','022130',5,1000,950,'1 Mei 2026',0,1],
 ['D','INDOMIE','MIE GORENG 85 GR','012345',20,3500,2800,'30 September 2026',null,1],
 ['E','FRISIAN FLAG','SUSU UHT STRAWBERRY 225 ML','123456',25,125000,93750,'15 November 2026',50,1],
 ['F','DUA KELINCI','KACANG ATOM ORIGINAL','654321',50,250000,125000,'31 Desember 2026',0,2],
 ['G','OREO','ORIGINAL','000789',10,10000,9000,'30 September 2026',25,1],
];
$hasAlloc=static fn(mixed $v):bool=>is_numeric($v)&&(float)$v>0;
$normDate=static function(string $s):string{ $m=['januari'=>'JANUARI','februari'=>'FEBRUARI','maret'=>'MARET','april'=>'APRIL','mei'=>'MEI','juni'=>'JUNI','juli'=>'JULI','agustus'=>'AGUSTUS','september'=>'SEPTEMBER','oktober'=>'OKTOBER','november'=>'NOVEMBER','desember'=>'DESEMBER']; return preg_replace_callback('/^\s*(\d{1,2})\s+([^\s]+)\s+(\d{4})\s*$/u',static fn($x)=>$x[1].' '.($m[mb_strtolower($x[2])]??mb_strtoupper($x[2])).' '.$x[3],$s)??mb_strtoupper($s); };

$set=static function(Mpdf $p,string $a):void{$p->SetFont($a,'',10/.352778);};
$measure=static function(Mpdf $p,string $a,string $text,float $w,float $h,float $x,float $y)use($set):array{$set($p,$a);$w0=max(.01,(float)$p->GetStringWidth($text));$cap=(($p->CurrentFont['desc']['CapHeight']??700)/1000)*10;return ['family'=>$a,'fs'=>10,'x'=>$x,'y'=>$y+$h+3,'sx'=>$w/$w0,'sy'=>$h/max(.01,$cap),'w0'=>$w0,'w'=>$w,'h'=>$h];};
$render=static function(Mpdf $p,float $px,float $py,array $row)use($bg,$common,$conditional,$measure,$set,$e,$hasAlloc,$normDate):void{
 [$case,$brand,$variant,$plu,$disc,$normal,$promo,$date,$allocation,$qty]=$row; $v=$hasAlloc($allocation)?'v1':'v2'; $boxes=$common+$conditional[$v];
 $texts=['brand'=>$brand,'plu'=>'PLU : '.$plu,'product'=>$variant,'disc'=>'DISC.','discount'=>(string)$disc,'percent'=>'%','normalRp'=>'Rp','normal'=>number_format((int)$normal,0,',','.'),'promoRp'=>'Rp','promo'=>number_format((int)$promo,0,',','.'),'period'=>'AKHIR PERIODE :','date'=>$normDate($date),'allocation'=>'SELAMA ALOKASI','still'=>'MASIH ADA'];
 $img='<img src="'.$e($bg).'" style="display:block;width:142.5mm;height:200mm;" />';$p->WriteFixedPosHTML($img,$px,$py,142.5,200,'auto');
 $svg='<svg xmlns="http://www.w3.org/2000/svg" width="142.5mm" height="200mm" viewBox="0 0 142.5 200" preserveAspectRatio="none" style="background:transparent">';
 foreach($boxes as $key=>$b){[$x,$y,$w,$h,$font,$color]=$b;$text=$texts[$key];
  // Adaptive fields retain the approved reference scale but follow content length.
  if($key==='product'){ $ref=$common['product']; $set($p,$font); $refW=max(.01,(float)$p->GetStringWidth('YOG SLURP 80 STRW')); $set($p,$font); $natural=(float)$p->GetStringWidth($text)*($ref[2]/$refW); $w=min($ref[2],max(18,$natural)); $x=71.25-$w/2; }
  if($key==='normal'){ $set($p,$font);$refW=max(.01,(float)$p->GetStringWidth('6.703'));$w=min(52,max(12,(float)$p->GetStringWidth($text)*(52/$refW)));$x=18; }
  if($key==='promo'){ $set($p,$font);$refW=max(.01,(float)$p->GetStringWidth('3.352'));$w=min(62,max(12,(float)$p->GetStringWidth($text)*(62/$refW)));$x=134-$w; }
  if($key==='date'){ $set($p,$font);$refW=max(.01,(float)$p->GetStringWidth('20 APRIL 2025'));$w=min($w,max(12,(float)$p->GetStringWidth($text)*($w/$refW)));$center=($v==='v1'?34:34);$x=$center-$w/2; }
  $t=$measure($p,$font,$text,$w,$h,$x,$y);$svg.='<g transform="translate('.$t['x'].' '.$t['y'].') scale('.$t['sx'].' '.$t['sy'].')" fill="'.$color.'"><text x="0" y="0" font-family="'.$t['family'].'" font-size="10">'.$e($text).'</text>';
  if($key==='discount'||in_array($key,['period','date','allocation','still'],true))$svg.='<text x="0.18" y="0" font-family="'.$t['family'].'" font-size="10">'.$e($text).'</text>';$svg.='</g>';
 }
 $normalBox=$boxes['normal'];$svg.='<line x1="'.($normalBox[0]-.5).'" y1="154" x2="'.($normalBox[0]+$normalBox[2]+1.5).'" y2="140" stroke="#FF0000" stroke-width="0.8"/></svg>';$p->WriteFixedPosHTML($svg,$px,$py,142.5,200,'auto');
};

foreach($dataCases as $row){for($n=0;$n<(int)$row[9];$n++){if($pdf->page>0)$pdf->AddPage('L');$render($pdf,3,5,$row);$render($pdf,151.5,5,$row);}}
$pdf->Output($out,'F');
fwrite(STDOUT,"Generated: {$out}\n");
foreach([1,0,'0','5',null,'','  ','-1','abc'] as $v)fwrite(STDOUT,'allocation '.var_export($v,true).' => '.($hasAlloc($v)?'V1':'V2')."\n");
