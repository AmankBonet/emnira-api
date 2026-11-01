# Autentikasi Android

Jalankan `database/auth_sessions.sql` pada database aplikasi sebelum menggunakan Auth.
Tidak ada perubahan data/database yang dijalankan otomatis.

URL berikut relatif ke base URL API. Jika rewrite URL belum aktif, gunakan
`index.php/auth/login`, dan seterusnya.

| Method | Endpoint | Input |
| --- | --- | --- |
| POST | /auth/login | JSON username dan password |
| POST | /auth/refresh | JSON refresh_token |
| GET | /auth/me | Authorization: Bearer <access_token> |
| POST | /auth/logout | Authorization: Bearer <access_token> |

Login tidak membutuhkan token. Contoh body dengan Content-Type: application/json:

```json
{"username":"bidan01","password":"password_uji"}
```

Token dan profil berada di properti `response`: access_token, token (alias untuk
kompatibilitas), token_type, expires_in, refresh_token, user. Password tidak
disertakan. Profil memakai id_user, nama, username, id_faskes, id_role, id_active.

Access token berlaku 600 detik. Sesi/refresh token berlaku 7 hari sejak login,
tanpa perpanjangan otomatis. Setiap refresh mengganti refresh token; Android
harus menyimpan token baru dan menghindari request refresh paralel. Refresh token
disimpan sebagai SHA-256 di database. Logout mencabut sesi perangkat saat ini,
termasuk access token yang pernah diterbitkan pada sesi tersebut. Jika access
token kedaluwarsa, refresh terlebih dahulu sebelum memanggil logout.

Endpoint GET /auth lama diganti POST /auth/login. Token lama tanpa sid tidak
diterima; pengguna perlu login kembali. Header x-token masih didukung helper
untuk controller lama; untuk Android gunakan Authorization: Bearer.

Password sementara dibandingkan langsung dengan kolom password_hash sesuai
kebutuhan uji coba. Setelah semua password di-hash memakai password_hash(),
ganti baris validasi dengan komentar password_verify() di Auth.php.

Username harus unik; login menolak username duplikat. id_active harus 1.
id_role dan id_faskes dibaca ulang saat validasi token. Pemetaan role dan aturan
akses rujukan harus diterapkan pada endpoint rujukan sesuai data master aplikasi;
Auth tidak menebak arti angka id_role.

Pemeriksaan manual setelah SQL dijalankan:

1. Login akun aktif: 200; password salah/akun nonaktif: 401; input kosong: 400.
2. Panggil me dengan access token: profil sesuai akun tanpa password_hash.
3. Refresh: token baru diterbitkan; refresh token lama ditolak dengan 401.
4. Logout: token sesi tersebut ditolak oleh me dan refresh dengan 401.
5. Login dua kali: logout satu sesi tidak mencabut sesi lainnya.
6. Nonaktifkan akun: me dan refresh harus mengembalikan 401.

Gunakan HTTPS saat diintegrasikan. Pembatasan percobaan login belum disertakan.
