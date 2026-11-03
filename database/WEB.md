# Modul web EMNIRA

Buka `/web/users` pada base URL aplikasi, misalnya `http://localhost/emnira-api/web/users`.
Login melalui `/web/login` memakai username dan password dari `m_user` dengan
`id_active = 1`. Semua akun aktif dapat mengelola pengguna; pembatasan berdasarkan
role belum diterapkan. Session web terpisah dari JWT API. Logout melalui tombol
Keluar menggunakan POST dengan CSRF dan menghapus session web.

Semua controller turunan `Web_Controller` otomatis memerlukan login. Status akun
diperiksa setiap request sehingga akun nonaktif atau terhapus kehilangan akses.
Password hash dan password teks akun lama didukung sesuai kompatibilitas API.

## Struktur

- `application/controllers/web/`: controller halaman web, terpisah dari API.
- `application/models/web/`: model web, terpisah dari model API.
- `application/core/Web_Controller.php`: controller dasar dengan fungsi `render()`.
- `application/views/template/`: layout, header, navbar, sidebar, dan footer bersama.
- `application/views/web/users/`: konten CRUD pengguna beserta modal.
- `assets/web/`: CSS template dan JavaScript halaman pengguna.

Bootstrap 5.3.8 dimuat melalui CDN jsDelivr dengan integrity hash. Koneksi internet
diperlukan untuk memuat CSS dan JavaScript Bootstrap.

## Menambah halaman

Buat controller di `application/controllers/web`, gunakan nama class yang tidak
bertabrakan dengan controller lain, lalu panggil `render()` dengan path view statis:

```php
<?php

defined('BASEPATH') or exit('No direct script access allowed');

require_once APPPATH . 'core/Web_Controller.php';

class Laporan extends Web_Controller
{
    public function index()
    {
        $this->render('web/laporan/index', [
            'page_title' => 'Laporan',
            'active_menu' => 'laporan'
        ]);
    }
}
```

Buat `application/views/web/laporan/index.php` berisi konten saja. Header, navbar,
sidebar, footer, dan pesan flash otomatis dimuat oleh template. Tambahkan tautan
menu ke `template/sidebar.php`. Halaman dapat diakses melalui `/web/laporan` tanpa
mengubah routing API. Gunakan `page_scripts` untuk daftar path JavaScript halaman.

## Notifikasi SweetAlert2

Template memuat SweetAlert2 11.23.0 dari CDN dan `assets/web/alerts.js` secara otomatis.
Konfigurasi mengikuti [dokumentasi SweetAlert2](https://sweetalert2.github.io/).
Pesan flash `success` atau `error` otomatis tampil sebagai toast `Swal.mixin()`:

```php
$this->session->set_flashdata('success', 'Data berhasil disimpan.');
redirect('web/users');
```

Untuk JavaScript halaman:

```javascript
AppAlert.toast('Data berhasil disimpan.');
AppAlert.toast('Data gagal disimpan.', 'error');
const confirmed = await AppAlert.confirm({
    title: 'Hapus data?',
    text: 'Data akan dihapus permanen.',
    confirmText: 'Ya, hapus'
});
```

Untuk form hapus cukup gunakan `form_open()` dengan atribut `data-confirm` berisi
pesan yang sudah di-escape dengan `html_escape()`. Opsional: `data-confirm-title`
dan `data-confirm-button`. Helper otomatis
menahan submit sampai pengguna mengonfirmasi; CSRF tetap dikirim. Pesan ditampilkan
sebagai teks, bukan HTML. Jika CDN gagal dimuat, pesan flash tetap terlihat dan
konfirmasi memakai dialog browser. Validasi tambah/edit tetap tampil pada modal.

## Aturan data pengguna

- Nama, username, status, dan password untuk akun baru wajib diisi.
- Username diperiksa agar tidak duplikat. DDL belum memiliki UNIQUE index, sehingga
  perlindungan duplikasi lintas request bersamaan membutuhkan indeks database
  setelah data username lama diperiksa; modul ini tidak mengubah skema.
- Faskes dan role berupa dropdown dari `m_faskes` dan `m_role`, diurutkan berdasarkan
  nama. Nama faskes disertai kategori FKTP/FKRTL. Keduanya opsional; kosong disimpan
  sebagai NULL. ID yang tidak ada pada tabel master ditolak oleh server.
- Password disimpan melalui `password_hash()`. Edit dengan password kosong
  mempertahankan password lama. Hash tidak dimuat pada daftar atau modal.
- Login API menerima hash baru dan tetap mendukung password teks akun lama,
  tanpa mengubah URL, request, atau respons API.
- Form menggunakan CSRF CodeIgniter. Jangan mengecualikan `web/*` dari CSRF.
- Simpan dan hapus hanya menerima POST; hapus bersifat permanen, memakai konfirmasi,
  dan menampilkan pesan jika ditolak oleh relasi database.
- Model dan controller web terpisah, tetapi tetap memakai tabel `m_user` yang sama:
  perubahan password/status dan penghapusan memengaruhi akun yang digunakan API.

## Pemeriksaan HTTP lokal

```powershell
php tests/web_auth_smoke.php
```

Pengujian membuat akun login dan pengguna CRUD dengan username acak lalu menghapusnya
dalam blok `finally`. Jalankan dari root proyek pada database pengembangan dengan
URL `http://localhost/emnira-api`. Pengujian memeriksa login, logout, akun nonaktif,
password lama/hash, CRUD, validasi, escaping, CSRF, dan metode HTTP. Untuk menjalankan
`web_users_smoke.ps1` sendiri, isi environment variable `EMNIRA_WEB_TEST_USERNAME`
dan `EMNIRA_WEB_TEST_PASSWORD` dengan akun pengujian aktif.
