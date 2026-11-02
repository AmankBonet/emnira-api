-- Jalankan hanya jika kolom ini belum ada. Tidak dijalankan otomatis.
ALTER TABLE `t_kunjungan`
    ADD COLUMN `id_faskes` int(11) DEFAULT NULL,
    ADD KEY `idx_kunjungan_faskes` (`id_faskes`),
    ADD CONSTRAINT `fk_kunjungan_faskes`
        FOREIGN KEY (`id_faskes`) REFERENCES `m_faskes` (`id_faskes`);

-- Isi pemilik kunjungan lama berdasarkan data yang telah diverifikasi.
-- Jangan memetakan asal_faskes berupa teks secara otomatis tanpa verifikasi.
-- Contoh (ganti ID):
-- UPDATE t_kunjungan SET id_faskes = 1 WHERE id_kunjungan = 10;

-- Opsional setelah seluruh id_kunjungan pada t_rm valid:
-- ALTER TABLE t_rm ADD KEY idx_rm_kunjungan (id_kunjungan),
-- ADD CONSTRAINT fk_rm_kunjungan FOREIGN KEY (id_kunjungan)
-- REFERENCES t_kunjungan (id_kunjungan);
