<?php

namespace App\Controllers;

use App\Libraries\DiscountValidator;
use CodeIgniter\Controller;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;
use Psr\Log\LoggerInterface;

/**
 * BaseController provides a convenient place for loading components
 * and performing functions that are needed by all your controllers.
 *
 * Extend this class in any new controllers:
 * ```
 *     class Home extends BaseController
 * ```
 *
 * For security, be sure to declare any new methods as protected or private.
 */
abstract class BaseController extends Controller
{
    /**
     * Be sure to declare properties for any property fetch you initialized.
     * The creation of dynamic property is deprecated in PHP 8.2.
     */

    // protected $session;

    /**
     * @return void
     */
    public function initController(RequestInterface $request, ResponseInterface $response, LoggerInterface $logger)
    {
        // Load here all helpers you want to be available in your controllers that extend BaseController.
        // Caution: Do not put the this below the parent::initController() call below.
        // $this->helpers = ['form', 'url'];

        // Caution: Do not edit this line.
        parent::initController($request, $response, $logger);

        // Preload any models, libraries, etc, here.
        // $this->session = service('session');
    }
    protected function rejectInvalidPrintDiscounts(array $products): ?ResponseInterface
    {
        $errors = [];
        foreach ($products as $product) {
            $discount = $product['discount_percent'] ?? null;
            $result = DiscountValidator::forPrinting($discount);
            if (!$result['valid']) {
                $errors[] = [
                    'id' => (int) $product['id'],
                    'plu' => (string) $product['sku_plu'],
                    'name' => (string) $product['name'],
                    'discount_percent' => $discount,
                    'reason' => $result['message'],
                ];
            }
        }
        if ($errors === []) {
            return null;
        }

        $message = 'Gagal mencetak. Terdapat produk dengan diskon tidak valid.';
        $guidance = 'Silakan perbaiki diskon produk sebelum mencetak kembali. Diskon harus berupa bilangan bulat antara 1% dan 99%.';
        if ($this->request->isAJAX()) {
            return $this->response->setStatusCode(422)->setJSON([
                'code' => 'invalid_print_discount',
                'message' => $message,
                'products' => $errors,
                'guidance' => $guidance,
            ]);
        }

        $details = array_map(static fn (array $error): string => sprintf(
            'ID: %d | PLU: %s | Produk: %s | Diskon: %s | %s',
            $error['id'],
            $error['plu'],
            $error['name'],
            (string) $error['discount_percent'],
            $error['reason']
        ), $errors);
        return redirect()->back()->with('error', implode("\n", [$message, ...$details, $guidance]));
    }
}
