# EMNIRA API

EMNIRA adalah singkatan dari **Electronic Maternal Neonatal Integrated Referral and Analysis System**. Repositori ini berisi backend API yang digunakan oleh aplikasi Android EMNIRA untuk mengakses layanan dan data melalui HTTP.

## Teknologi

- PHP 8.0 atau lebih baru
- CodeIgniter 3
- MySQL atau MariaDB dengan ekstensi PHP `mysqli`
- Composer
- Apache dengan `mod_rewrite` untuk URL tanpa `index.php`
- CodeIgniter REST Server dan `firebase/php-jwt`

Versi dependency yang digunakan dikunci di `composer.lock`.

## Menjalankan secara lokal

1. Siapkan PHP dan Composer, lalu clone repositori.
2. Dari direktori proyek, pasang dependency:

   ```bash
   composer install
   ```

3. Siapkan konfigurasi CodeIgniter lokal:
   - Atur `application/config/config.php`, terutama `base_url` dan `index_page`.
   - Atur `application/config/database.php` untuk koneksi MySQL/MariaDB.
   - Atur `application/config/jwt.php` dengan secret JWT yang kuat dan khusus untuk lingkungan ini. Jangan gunakan secret development di production.
4. Siapkan database yang digunakan aplikasi. Endpoint autentikasi membaca tabel `api_login`, sedangkan endpoint lokasi membaca tabel `m_lokasi`. Skema dan data database perlu disediakan terpisah; repositori ini tidak menyertakan dump database.
5. Arahkan Apache VirtualHost atau document root ke direktori proyek. Pastikan `mod_rewrite` aktif agar aturan di `.htaccess` dapat meneruskan request ke `index.php`.
6. Akses API melalui base URL yang sudah dikonfigurasi, misalnya `http://localhost/`.

File konfigurasi berisi pengaturan mesin lokal dan kredensial tidak boleh dimasukkan ke commit. Aturan `.gitignore` disediakan untuk file konfigurasi database, konfigurasi aplikasi lokal, dependency Composer, serta file runtime.

## Endpoint

Semua endpoint menggunakan base URL aplikasi. Nama controller dan method CodeIgniter REST Server menentukan path dan metode HTTP berikut:

| Metode | Path | Keterangan |
| --- | --- | --- |
| `GET` | `/auth` | Membuat JWT dengan kredensial yang dikirim melalui header `x-username` dan `x-password`. Token berlaku selama 10 menit. |
| `GET` | `/master/lokasi` | Mengambil data lokasi. Memerlukan header `x-username` dan `x-token`. |
| `POST` | `/api/simpan` | Endpoint POST yang tersedia pada controller API; pemrosesan bisnis untuk request bertoken belum diimplementasikan. |

Contoh meminta token:

```http
GET /auth
x-username: nama-pengguna
x-password: kata-sandi
```

Contoh menggunakan token:

```http
GET /master/lokasi
x-username: nama-pengguna
x-token: <jwt>
```

Respons API umumnya menyertakan `metadata` yang berisi kode dan pesan. Respons autentikasi yang berhasil menyertakan token pada `response.token`. Jangan mengirim kredensial atau token melalui URL; gunakan HTTPS di luar lingkungan lokal.

## Struktur penting

- `application/controllers/` — controller endpoint API
- `application/models/` — akses data
- `application/config/` — konfigurasi CodeIgniter
- `application/helpers/token_helper.php` — validasi token JWT
- `system/` — framework CodeIgniter
- `vendor/` — dependency Composer (dihasilkan oleh `composer install`)
- `index.php` — entry point aplikasi

## Lisensi

Lihat [license.txt](license.txt).
