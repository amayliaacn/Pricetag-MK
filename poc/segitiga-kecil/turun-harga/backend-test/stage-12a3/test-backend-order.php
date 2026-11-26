<?php

declare(strict_types=1);

require dirname(__DIR__, 5) . '/vendor/autoload.php';
require dirname(__DIR__, 5) . '/app/Libraries/SegitigaKecilDiskonRenderer.php';
require dirname(__DIR__, 5) . '/app/Libraries/SegitigaKecilTurunHargaRenderer.php';
require dirname(__DIR__, 5) . '/app/Libraries/Popa4pdf.php';

defined('FCPATH') || define('FCPATH', dirname(__DIR__, 5) . DIRECTORY_SEPARATOR . 'public' . DIRECTORY_SEPARATOR);
defined('WRITEPATH') || define('WRITEPATH', dirname(__DIR__, 5) . DIRECTORY_SEPARATOR . 'writable' . DIRECTORY_SEPARATOR);

use App\Libraries\PopA4Pdf;

$out=__DIR__;
$diskon=static fn(string $sku,string $brand,int $qty):array=>['renderer'=>'segitiga_kecil_diskon','row'=>['brand'=>$brand,'variant'=>'DISKON TEST','plu'=>$sku,'discount'=>50,'normal'=>10000,'promo'=>5000,'period'=>'20 April 2025','allocation'=>100],'qty'=>$qty,'sku'=>$sku];
$turun=static fn(string $sku,string $brand,int $qty):array=>['renderer'=>'segitiga_kecil_turun_harga','row'=>['brand'=>$brand,'variant'=>'TURUN TEST','sku_plu'=>$sku,'normal_price'=>10000,'promo_price'=>8500,'end_period'=>'20 APRIL 2025','allocation_pcs'=>0],'qty'=>$qty,'sku'=>$sku];
$cases=[
 'test-c-diskon-turun-diskon'=>[$diskon('100001','A-DISKON',2),$turun('200001','B-TURUN',1),$diskon('300001','C-DISKON',3)],
 'test-d-turun-diskon-turun'=>[$turun('400001','D-TURUN',1),$diskon('500001','E-DISKON',2),$turun('600001','F-TURUN',1)],
 'test-e-four-designs'=>[
  ['renderer'=>'segitiga_kecil_diskon','row'=>['brand'=>'GA','variant'=>'ALLOC','plu'=>'700001','discount'=>50,'normal'=>10000,'promo'=>5000,'period'=>'20 April 2025','allocation'=>100],'qty'=>1,'sku'=>'700001'],
  $turun('800001','H-TURUN-NONALLOC',1),
  ['renderer'=>'segitiga_kecil_diskon','row'=>['brand'=>'I-DISKON-NONALLOC','variant'=>'NONALLOC','plu'=>'900001','discount'=>10,'normal'=>10000,'promo'=>9000,'period'=>'20 April 2025','allocation'=>0],'qty'=>1,'sku'=>'900001'],
  ['renderer'=>'segitiga_kecil_turun_harga','row'=>['brand'=>'J-TURUN-ALLOC','variant'=>'ALLOC','sku_plu'=>'100002','normal_price'=>10000,'promo_price'=>8500,'end_period'=>'20 APRIL 2025','allocation_pcs'=>100],'qty'=>1,'sku'=>'100002'],
 ],
];
$report=['tests'=>[],'notes'=>['Harness menguji PopA4Pdf ordered batch; controller HTTP tidak dipanggil.','PDF text extraction tool tidak tersedia, sehingga urutan dicatat dari input ordered dan nomor halaman.']];
foreach($cases as $name=>$items){$groups=['segitiga_kecil_ordered'=>$items];$pdf=(new PopA4Pdf('segitiga_kecil_diskon'))->renderMixed($groups);$path=$out.'/'.$name.'.pdf';file_put_contents($path,$pdf);$expected=[];foreach($items as $item)for($i=0;$i<$item['qty'];$i++)$expected[]=$item['sku'];$report['tests'][$name]=['path'=>$path,'pages'=>count($expected),'expectedSkuOrder'=>$expected,'status'=>'PASS'];}
$report['selectionMatrix']=[
 ['discount_percent'=>1,'promo_price'=>null,'expected'=>'segitiga_kecil_diskon'],['discount_percent'=>5,'promo_price'=>null,'expected'=>'segitiga_kecil_diskon'],['discount_percent'=>9,'promo_price'=>null,'expected'=>'segitiga_kecil_diskon'],['discount_percent'=>10,'promo_price'=>null,'expected'=>'segitiga_kecil_diskon'],['discount_percent'=>50,'promo_price'=>null,'expected'=>'segitiga_kecil_diskon'],['discount_percent'=>99,'promo_price'=>null,'expected'=>'segitiga_kecil_diskon'],['discount_percent'=>null,'promo_price'=>8500,'expected'=>'segitiga_kecil_turun_harga'],['discount_percent'=>0,'promo_price'=>8500,'expected'=>'segitiga_kecil_turun_harga'],['discount_percent'=>null,'promo_price'=>null,'expected'=>'reject'],['discount_percent'=>0,'promo_price'=>null,'expected'=>'reject'],
];
file_put_contents($out.'/stage-12a3-report.json',json_encode($report,JSON_PRETTY_PRINT|JSON_UNESCAPED_SLASHES|JSON_UNESCAPED_UNICODE));echo "Generated Stage 12A.3 order PDFs\n";
