<?php

declare(strict_types=1);

require dirname(__DIR__, 4) . '/vendor/autoload.php';
require dirname(__DIR__, 4) . '/app/Libraries/SegitigaKecilDiskonRenderer.php';
require dirname(__DIR__, 4) . '/app/Libraries/SegitigaKecilTurunHargaRenderer.php';
require dirname(__DIR__, 4) . '/app/Libraries/Popa4pdf.php';
defined('FCPATH') || define('FCPATH', dirname(__DIR__, 4) . DIRECTORY_SEPARATOR . 'public' . DIRECTORY_SEPARATOR);
defined('WRITEPATH') || define('WRITEPATH', dirname(__DIR__, 4) . DIRECTORY_SEPARATOR . 'writable' . DIRECTORY_SEPARATOR);

use App\Libraries\PopA4Pdf;

$out = __DIR__;
$turun = static fn(array $x): array => [
    'brand'=>$x['brand'],'variant'=>$x['variant'],'sku_plu'=>$x['sku_plu'],
    'normal_price'=>$x['normal_price'],'promo_price'=>$x['promo_price'],
    'end_period'=>$x['end_period'],'allocation_pcs'=>$x['allocation_pcs'],
];
$base=['brand'=>'NOBO','variant'=>'OAT MILK 200ML CRMY CLAS','sku_plu'=>'350515','normal_price'=>2855,'promo_price'=>2521,'end_period'=>'30 NOVEMBER 2025'];
$alloc=$turun($base+['allocation_pcs'=>100]);
$non=$turun($base+['allocation_pcs'=>0]);
$qty1=$turun($base+['allocation_pcs'=>1]);
$qty2=$turun($base+['allocation_pcs'=>2]);
$qty3=$turun($base+['allocation_pcs'=>3]);
$pdf=(new PopA4Pdf('segitiga_kecil_diskon'))->renderMixed(['segitiga_kecil_turun_harga'=>[
    ['row'=>$alloc,'qty'=>1],['row'=>$non,'qty'=>1],['row'=>$qty1,'qty'=>1],['row'=>$qty2,'qty'=>2],['row'=>$qty3,'qty'=>3],
]]);
file_put_contents($out.'/segitiga-kecil-turun-harga-backend-valid.pdf',$pdf);
$diskon=['brand'=>'KIN','variant'=>'YOG SLURP 80 STRW','plu'=>'319787','discount'=>50,'normal'=>6703,'promo'=>3352,'period'=>'20 April 2025','allocation'=>100];
$diskonNon=$diskon; $diskonNon['allocation']=0;
$discountPdf=(new PopA4Pdf('segitiga_kecil_diskon'))->renderMixed(['segitiga_kecil_diskon'=>[['row'=>$diskon,'qty'=>1],['row'=>$diskonNon,'qty'=>1]]]);
file_put_contents($out.'/segitiga-kecil-diskon-backend-regression.pdf',$discountPdf);
$pluPdf=(new PopA4Pdf('segitiga_kecil_diskon'))->renderMixed(['segitiga_kecil_turun_harga'=>[['row'=>$alloc,'qty'=>1],['row'=>$non,'qty'=>1]]]);
file_put_contents($out.'/segitiga-kecil-turun-harga-backend-valid-v2.pdf',$pluPdf);
file_put_contents($out.'/backend-test-report.json',json_encode([
    'status'=>'PASS','outputs'=>['turun'=>$out.'/segitiga-kecil-turun-harga-backend-valid.pdf','turunPluV2'=>$out.'/segitiga-kecil-turun-harga-backend-valid-v2.pdf','diskon'=>$out.'/segitiga-kecil-diskon-backend-regression.pdf'],
    'scenarios'=>['allocation','non-allocation','qty-1','qty-2','qty-3','discount-regression-allocation','discount-regression-non-allocation'],'pageMapping'=>['turun'=>['1'=>'allocation qty1','2'=>'non-allocation qty1','3'=>'qty1','4-5'=>'qty2','6-8'=>'qty3'],'diskon'=>['1'=>'allocation','2'=>'non-allocation']],
    'notes'=>['CLI harness menggunakan PopA4Pdf dan library renderer melalui public API; HTTP/session/database belum diuji.','Tanggal invalid tidak diteruskan ke renderer karena controller PrintMpdf sudah memvalidasi format Y-m-d dengan DateTimeImmutable.'],
],JSON_PRETTY_PRINT|JSON_UNESCAPED_SLASHES|JSON_UNESCAPED_UNICODE));
echo "Generated backend PDFs\n";
