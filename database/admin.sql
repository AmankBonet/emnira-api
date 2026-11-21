-- Role dan akun administrator web EMNIRA.
-- Administrator mengelola master data dan pengguna, serta dapat melihat semua kunjungan.
-- Aman dijalankan ulang: role di-upsert, akun hanya dibuat bila username "admin" belum ada.
--
-- Password awal TIDAK disimpan di file ini (hanya hash bcrypt); segera ganti setelah login
-- pertama melalui menu Pengguna.

INSERT INTO `m_role` (`id_role`, `nama_role`)
VALUES (3, 'ADMINISTRATOR')
ON DUPLICATE KEY UPDATE `nama_role` = VALUES(`nama_role`);

-- Administrator tidak terikat faskes sehingga tidak dibatasi per faskes.
INSERT INTO `m_user` (`nama`, `username`, `password_hash`, `id_role`, `id_faskes`, `id_active`)
SELECT 'Administrator', 'admin', '$2y$12$7lKkofGEWNmen3kBY9FYo.5DQRqClvTsKY0VlPcr.FwsNmwY0dZZO', 3, NULL, 1
FROM DUAL
WHERE NOT EXISTS (
    SELECT 1 FROM `m_user` WHERE `username` = 'admin'
);

SELECT `id_user`, `nama`, `username`, `id_role`, `id_faskes`, `id_active`
FROM `m_user`
WHERE `username` = 'admin';
