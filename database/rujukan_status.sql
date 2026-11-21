-- Status rujukan per kunjungan: draf -> dikirim -> diterima -> tiba.
-- Faskes perujuk mengisi selama draf lalu mengirim; faskes tujuan menerima dan mencatat kedatangan.
-- Aman dijalankan ulang (ADD COLUMN/INDEX IF NOT EXISTS; MariaDB 10.0.2+).
-- Semua kunjungan yang sudah ada otomatis berstatus 'draf'.

ALTER TABLE `t_kunjungan`
    ADD COLUMN IF NOT EXISTS `status_rujukan` enum('draf','dikirim','diterima','tiba') NOT NULL DEFAULT 'draf'
        COMMENT 'draf = diisi perujuk; dikirim = terkunci, tampil di faskes tujuan'
        AFTER `usia_kehamilan`,
    ADD COLUMN IF NOT EXISTS `waktu_dikirim` datetime DEFAULT NULL AFTER `status_rujukan`,
    ADD COLUMN IF NOT EXISTS `id_user_pengirim` int(11) DEFAULT NULL AFTER `waktu_dikirim`,
    ADD COLUMN IF NOT EXISTS `waktu_diterima` datetime DEFAULT NULL AFTER `id_user_pengirim`,
    ADD COLUMN IF NOT EXISTS `id_user_penerima` int(11) DEFAULT NULL AFTER `waktu_diterima`,
    ADD COLUMN IF NOT EXISTS `catatan_penerimaan` varchar(500) DEFAULT NULL
        COMMENT 'Catatan singkat faskes tujuan saat menerima (contoh: VK siap, Sp.OG on site)'
        AFTER `id_user_penerima`,
    ADD COLUMN IF NOT EXISTS `waktu_tiba` datetime DEFAULT NULL AFTER `catatan_penerimaan`,
    ADD COLUMN IF NOT EXISTS `id_user_kedatangan` int(11) DEFAULT NULL AFTER `waktu_tiba`,
    ADD INDEX IF NOT EXISTS `idx_kunjungan_tujuan_status` (`id_faskes_tujuan`, `status_rujukan`),
    ADD INDEX IF NOT EXISTS `idx_kunjungan_perujuk` (`id_faskes_perujuk`);

-- Pemeriksaan data: kunjungan tanpa faskes perujuk tidak dapat diakses user faskes mana pun.
-- Lengkapi lewat menu Identitas pasien atau UPDATE manual setelah dicek.
SELECT `id_kunjungan`, `no_rm`, `nama`, `id_faskes_perujuk`, `id_faskes_tujuan`
FROM `t_kunjungan`
WHERE `id_faskes_perujuk` IS NULL
    OR `id_faskes_tujuan` IS NULL
    OR `id_faskes_perujuk` = `id_faskes_tujuan`;
