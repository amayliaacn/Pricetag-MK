<?php

use CodeIgniter\Router\RouteCollection;

/** @var RouteCollection $routes */

// --- Route Public & Auth ---
$routes->get('/', 'Auth::login');
$routes->get('/login', 'Auth::login');
$routes->post('/auth/processLogin', 'Auth::processLogin');
$routes->get('/logout', 'Auth::logout');

// Inisialisasi awal Super Admin (Bisa dihapus/dinonaktifkan jika sudah dipakai)
$routes->get('/setup-admin', 'Auth::createSuperAdmin');

// --- Route Terproteksi (Bisa diakses Semua User yang sudah Login) ---
$routes->get('/dashboard', 'Dashboard::index', ['filter' => 'auth']);
$routes->get('/pricetag', 'PriceTag::index', ['filter' => 'auth']);
$routes->post('/pricetag/import', 'PriceTag::import', ['filter' => 'auth']);
$routes->post('print-pdf', 'PrintPdf::index', ['filter' => 'auth']);
$routes->get('print-pdf/(:segment)', 'PrintPdf::download/$1', ['filter' => 'auth']);

// --- Route Khusus SUPER ADMIN (Menggunakan filter auth:super_admin) ---
$routes->get('admin/users', 'Admin\UserAdmin::index', ['filter' => 'auth:super_admin']);
$routes->get('admin/users/create', 'Admin\UserAdmin::create', ['filter' => 'auth:super_admin']);
$routes->post('admin/users/store', 'Admin\UserAdmin::store', ['filter' => 'auth:super_admin']);
$routes->get('admin/users/delete/(:num)', 'Admin\UserAdmin::delete/$1', ['filter' => 'auth:super_admin']);
