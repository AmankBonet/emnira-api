# Rancangan rekam rujukan

File t_rm.sql adalah rancangan CREATE lengkap, belum dijalankan dan bukan migrasi
untuk t_rm yang sudah ada. Ekspor SHOW CREATE TABLE t_rm untuk menyusun ALTER yang
mempertahankan data. Generated column memerlukan versi MySQL/MariaDB yang mendukung.

- t_rm: data utama bagian 2-7, identitas rujukan dan audit dasar.
- m_diagnosis_ponek: isi 11 diagnosis yang disepakati; daftar belum diberikan.
- m_rm_pilihan + t_rm_pilihan: pilihan checkbox dan relasi ke pemeriksaan.
- t_rm_lampiran: metadata berkas; upload, kompresi dan PDF dikerjakan aplikasi.

id_pasien, id_faskes, dan id_user belum diberi foreign key karena DDL master terkait
belum tersedia. Cocokkan tipe/unsigned dan nama kunci sebelum menambah relasi.
Bagian identitas pasien (bagian 1) belum diberikan, sehingga tidak ditebak di sini.

Validasi API yang perlu diterapkan saat membuat endpoint simpan:

- Angka pemeriksaan tidak boleh negatif; persentase dibatasi 0-100.
- Durasi urin dan berat badan harus positif bila laju akan dihitung.
- Jika data belum lengkap, laju urin NULL, bukan nol.
- Status urin dapat ditampilkan dari nilai laju: >= 0.5 / < 0.5 mengikuti
  spesifikasi formulir; ambang dan penggunaannya harus disahkan tim klinis.
- Riwayat tidak ada tidak boleh dipilih bersamaan dengan riwayat penyakit/alergi.
- Tidak ada perdarahan tidak boleh disertai volume/karakteristik perdarahan.
- Belum dinilai berbeda dari sudah dinilai tanpa checkbox terpilih.
- Skor Bishop tidak dihitung dari kategori VT/Hodge secara perkiraan. Lengkapi
  lima komponen dan aturan skor yang disetujui; simpan versi aturan bersama skor.
- Pilihan his pada spesifikasi belum mencakup tepat 2 kali/10 menit atau >5;
  konfirmasi kategori sebelum formulir produksi.
- Label 'stelsel' mengikuti input pengguna; konfirmasi istilah sebelum produksi.
- Detail terapi/dosis disimpan sebagai dokumentasi tindakan aktual pada catatan,
  bukan perintah terapi atau dosis otomatis. Tim klinis perlu memvalidasi opsi.
- id_user_pembuat diambil dari JWT tervalidasi dan id_faskes_asal dari akun;
  jangan mempercayai nilai tersebut dari body Android.
- Batasi baca/ubah rujukan dan unduhan lampiran menurut fasilitas dan role.
- Simpan t_rm, pilihan dan metadata lampiran menggunakan transaksi.

DDL tidak otomatis menjalankan perhitungan Bishop, upload, kompresi atau pembuatan
PDF. Laju mL/kg/jam adalah generated column; input mL/jam pada stabilisasi disimpan
terpisah karena satuannya berbeda.
