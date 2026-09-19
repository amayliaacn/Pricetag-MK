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
$routes->get('/profile/password', 'Dashboard::password', ['filter' => 'auth']);
$routes->post('/profile/password', 'Dashboard::updatePassword', ['filter' => 'auth']);
$routes->get('/pricetag', 'PriceTag::index', ['filter' => 'auth']);
$routes->post('/pricetag/import', 'PriceTag::import', ['filter' => 'auth']);
$routes->post('print-pdf', 'PrintPdf::index');

// --- Route Khusus SUPER ADMIN (Menggunakan filter auth:super_admin) ---
$routes->get('admin/outlets', 'Admin\OutletAdmin::index', ['filter' => 'auth:super_admin']);
$routes->get('admin/outlets/create', 'Admin\OutletAdmin::create', ['filter' => 'auth:super_admin']);
$routes->post('admin/outlets/store', 'Admin\OutletAdmin::store', ['filter' => 'auth:super_admin']);
$routes->get('admin/outlets/edit/(:num)', 'Admin\OutletAdmin::edit/$1', ['filter' => 'auth:super_admin']);
$routes->post('admin/outlets/update/(:num)', 'Admin\OutletAdmin::update/$1', ['filter' => 'auth:super_admin']);
$routes->get('admin/outlets/toggle/(:num)', 'Admin\OutletAdmin::toggle/$1', ['filter' => 'auth:super_admin']);
$routes->get('admin/outlets/delete/(:num)', 'Admin\OutletAdmin::delete/$1', ['filter' => 'auth:super_admin']);

$routes->get('admin/users', 'Admin\UserAdmin::index', ['filter' => 'auth:super_admin']);
$routes->get('admin/users/create', 'Admin\UserAdmin::create', ['filter' => 'auth:super_admin']);
$routes->post('admin/users/store', 'Admin\UserAdmin::store', ['filter' => 'auth:super_admin']);
$routes->get('admin/users/edit/(:num)', 'Admin\UserAdmin::edit/$1', ['filter' => 'auth:super_admin']);
$routes->post('admin/users/update/(:num)', 'Admin\UserAdmin::update/$1', ['filter' => 'auth:super_admin']);
$routes->get('admin/users/password/(:num)', 'Admin\UserAdmin::password/$1', ['filter' => 'auth:super_admin']);
$routes->post('admin/users/password/(:num)', 'Admin\UserAdmin::updatePassword/$1', ['filter' => 'auth:super_admin']);
$routes->get('admin/users/toggle/(:num)', 'Admin\UserAdmin::toggle/$1', ['filter' => 'auth:super_admin']);
$routes->get('admin/users/delete/(:num)', 'Admin\UserAdmin::delete/$1', ['filter' => 'auth:super_admin']);
