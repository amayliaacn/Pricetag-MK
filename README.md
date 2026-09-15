# Price Tag

Aplikasi web berbasis CodeIgniter 4 untuk mengelola data harga dan membuat label harga. Aplikasi mendukung autentikasi pengguna, pengelolaan price tag, impor data Excel, serta pembuatan dokumen PDF dan DOCX berdasarkan template.

## Teknologi

- PHP `^8.2`
- CodeIgniter 4
- MySQL (direkomendasikan)
- PhpSpreadsheet untuk impor Excel
- mPDF untuk pembuatan PDF
- PHPUnit untuk pengujian

## Persyaratan

- PHP 8.2 atau lebih baru
- Composer
- MySQL atau database yang kompatibel dengan konfigurasi CodeIgniter
- Ekstensi PHP `intl`, `mbstring`, dan `json`
- Ekstensi `mysqli` jika menggunakan MySQL

## Instalasi

1. Clone repository lalu masuk ke direktori proyek.
2. Install dependency: `composer install`
3. Sesuaikan `.env` untuk URL aplikasi serta koneksi database. File ini bersifat lokal dan tidak boleh di-commit.
4. Jalankan migration: `php spark migrate`
5. Jalankan server development: `php spark serve`

Buka `http://localhost:8080` di browser. Untuk XAMPP, arahkan virtual host atau document root ke folder `public/`, bukan ke root proyek.

## Struktur penting

- `app/` — controller, model, migration, library, dan view aplikasi
- `public/` — document root dan template DOCX
- `writable/` — cache, log, session, dan file upload runtime
- `tests/` — pengujian otomatis

## Pengujian

```bash
composer test
```

## Keamanan

Jangan commit kredensial database, secret key, session, hasil upload, atau file runtime. Gunakan `CI_ENVIRONMENT = production` di production dan pastikan web server menunjuk langsung ke folder `public/`.
