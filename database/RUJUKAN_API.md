# POST rujukan Android

POST {{base_url}}/rujukan
Authorization: Bearer <access_token>
Content-Type: application/json

```json
{
    "id_kunjungan": 10,
    "id_faskes_tujuan": 2,
    "id_diagnosis": 1,
    "waktu_rujukan": "2026-11-01 10:30:00",
    "skrining_bahaya_sudah_dinilai": 1,
    "pilihan": ["bahaya_kejang"],
    "perdarahan_status": "tidak_ada",
    "td_sistolik_mmhg": 120,
    "td_diastolik_mmhg": 80,
    "berat_badan_kg": 60,
    "produksi_urin_total_ml": 120,
    "durasi_pengamatan_urin_jam": 2
}
```

Contoh adalah payload teknis, bukan rekomendasi klinis. Ganti ID sesuai database.
Hanya id_kunjungan dan id_faskes_tujuan yang wajib pada tahap ini; field lain dapat
bernilai null atau tidak dikirim. Angka dikirim sebagai angka JSON, bukan string.
Daftar nama kolom dan tipe yang diterima ada di application/config/rujukan_fields.json.
Enum harus menggunakan kode persis seperti DDL. Checkbox menggunakan array kode
m_rm_pilihan; flag sudah_dinilai terkait harus 1 jika checkbox dipilih.

Respons berhasil HTTP 201:

```json
{
    "metadata": {"code": 201, "message": "Rujukan berhasil disimpan."},
    "response": {"id_rm": "1"}
}
```

id_rm dapat berupa string karena bertipe bigint. Validasi field menghasilkan 422
beserta response.errors; token gagal 401; izin gagal 403; konfigurasi pemilik
kunjungan belum tersedia 503. Simpan token baru melalui auth/refresh jika kedaluwarsa.

## Persiapan database

1. Gunakan t_rm sesuai revisi pengguna: id_kunjungan menggantikan id_pasien dan
   nomor_rujukan. database/t_rm.sql telah disesuaikan sebagai referensi CREATE,
   bukan migrasi untuk tabel yang sudah ada.
2. Pastikan m_diagnosis_ponek sudah diisi. Contoh id_diagnosis=1 hanya bekerja bila ada.
3. Jalankan database/rujukan_setup.sql untuk menambah t_kunjungan.id_faskes jika belum
   ada, lalu isi ID faskes pemilik kunjungan lama berdasarkan data terverifikasi.
   Query contoh UPDATE di file sengaja berupa komentar.
4. Untuk checkbox, buat m_rm_pilihan dan t_rm_pilihan dari database/t_rm.sql bila
   belum tersedia. Jangan menjalankan ulang CREATE tabel yang sudah ada.

SQL tidak dijalankan otomatis. Saat membuat kunjungan baru nanti, id_faskes harus
berasal dari akun login. t_kunjungan.asal_faskes berupa nama tidak digunakan untuk
otorisasi. Kunjungan lama tanpa id_faskes yang sesuai akun akan ditolak.

## Aturan akses dan penyimpanan

- Akun harus aktif, memiliki role terdaftar pada m_role, dan berada di FKTP.
- Semua akun FKTP dengan role terdaftar saat ini diizinkan. Belum ada pembatasan
  angka id_role tertentu karena isi master role belum diberikan.
- Kunjungan harus dimiliki faskes pengirim. Tujuan harus FKRTL.
- id_faskes_asal dan id_user_pembuat diambil dari token/akun, bukan body.
- Kolom audit, skor Bishop, output urin generated dan status PDF tidak boleh
  dikirim klien. Kolom asing juga ditolak agar salah ketik tidak diabaikan.
- Waktu rujukan default waktu Jakarta bila tidak dikirim.
- t_rm dan t_rm_pilihan disimpan dalam satu transaksi. Kegagalan membatalkan semuanya.
- Output urin dihitung database. Skor Bishop belum dihitung.
- Endpoint ini memakai kunjungan yang sudah ada; tidak membuat/mengubah t_kunjungan.
- Upload berkas dan pembuatan PDF belum termasuk endpoint ini.
- POST berulang membuat rujukan baru; Android perlu mencegah submit ganda.

Uji lokal: php tests/rujukan_validation.php. Pengujian HTTP/database perlu dilakukan
setelah persiapan SQL. Pemeriksaan yang disarankan: akun FKRTL ditolak, kunjungan
faskes lain ditolak, tujuan FKTP ditolak, token logout ditolak, serta rollback jika
insert pilihan gagal. Tidak ada data pasien yang perlu dicatat pada log kegagalan.
