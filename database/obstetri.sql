-- Status obstetri (GPA), riwayat kehamilan terdahulu, dan kondisi medis ibu.
-- Rancangan CREATE TABLE baru; tidak mengubah atau menghapus tabel yang sudah ada.
-- Semua data menempel pada t_kunjungan karena belum ada tabel master pasien.
-- Dibutuhkan MariaDB 10.2+ / MySQL 8.0.16+ agar CHECK dan generated column berlaku.

-- -----------------------------------------------------------------------------
-- Master pilihan penyakit/komplikasi (pola sama dengan m_rm_pilihan).
-- Dipisah dari m_rm_pilihan karena membutuhkan tingkat risiko dan rekomendasi.
-- -----------------------------------------------------------------------------
CREATE TABLE `m_obstetri_pilihan` (
    `kode` varchar(60) NOT NULL,
    `kelompok` enum(
        'penyakit_hamil',
        'komplikasi_persalinan_nifas',
        'kondisi_ibu'
    ) NOT NULL,
    `label` varchar(255) NOT NULL,
    `tingkat_risiko` enum('normal','risiko','bahaya') NOT NULL DEFAULT 'risiko'
        COMMENT 'Dipakai untuk flagging kewaspadaan medis di UI',
    `rekomendasi` varchar(255) DEFAULT NULL
        COMMENT 'Contoh: Wajib Aspirin + Kalsium',
    `urutan` smallint unsigned NOT NULL DEFAULT 0,
    `id_active` tinyint NOT NULL DEFAULT 1,
    PRIMARY KEY (`kode`),
    KEY `idx_obstetri_pilihan_kelompok` (`kelompok`, `id_active`, `urutan`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- -----------------------------------------------------------------------------
-- Bagian 2A: ringkasan GPA, satu baris per kunjungan (relasi 1:1).
-- -----------------------------------------------------------------------------
CREATE TABLE `t_kunjungan_obstetri` (
    `id_kunjungan` int(11) NOT NULL,
    `gravida` tinyint unsigned NOT NULL COMMENT 'SIMRS: obs_gravida; termasuk kehamilan saat ini',
    `partus` tinyint unsigned NOT NULL DEFAULT 0 COMMENT 'SIMRS: obs_partus; persalinan >= 22 minggu',
    `abortus` tinyint unsigned NOT NULL DEFAULT 0 COMMENT 'SIMRS: obs_abortus; keguguran < 22 minggu',
    `anak_hidup` tinyint unsigned NOT NULL DEFAULT 0 COMMENT 'SIMRS: obs_living_children',

    -- Dihitung database agar tidak pernah berbeda dari P dan AH.
    -- GREATEST mencegah nilai negatif pada kehamilan kembar (AH bisa > P).
    `anak_meninggal` tinyint unsigned AS (
        GREATEST(CAST(`partus` AS SIGNED) - CAST(`anak_hidup` AS SIGNED), 0)
    ) STORED COMMENT 'SIMRS: obs_perinatal_death; P - AH',
    `kode_gpa` varchar(20) AS (
        CONCAT('G', `gravida`, 'P', `partus`, 'A', `abortus`)
    ) STORED COMMENT 'Contoh: G3P1A1; disalin ke t_kunjungan.status_obstetri',

    `created_at` datetime NOT NULL DEFAULT current_timestamp(),
    `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
    PRIMARY KEY (`id_kunjungan`),
    CONSTRAINT `fk_obstetri_kunjungan` FOREIGN KEY (`id_kunjungan`)
        REFERENCES `t_kunjungan` (`id_kunjungan`) ON DELETE CASCADE,

    -- Aturan konsistensi GPA sesuai spesifikasi: P + A < G.
    CONSTRAINT `chk_obstetri_gravida` CHECK (`gravida` BETWEEN 1 AND 20),
    CONSTRAINT `chk_obstetri_gpa` CHECK (`partus` + `abortus` < `gravida`)
    -- AH <= P sengaja tidak dikunci di database karena kehamilan kembar
    -- membuat AH > P sah; cukup ditampilkan sebagai peringatan di aplikasi.
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- -----------------------------------------------------------------------------
-- Bagian 2B: riwayat kehamilan terdahulu, satu baris per kehamilan (1:N).
-- Jumlah baris yang diisi mengikuti gravida - 1 (routing UI).
-- -----------------------------------------------------------------------------
CREATE TABLE `t_riwayat_kehamilan` (
    `id_riwayat_kehamilan` int(11) NOT NULL AUTO_INCREMENT,
    `id_kunjungan` int(11) NOT NULL,
    `kehamilan_ke` tinyint unsigned NOT NULL,
    `tahun_persalinan` smallint unsigned DEFAULT NULL COMMENT 'Tahun persalinan/terminasi',
    `luaran` enum(
        'lahir_hidup',
        'lahir_mati',
        'abortus',
        'kehamilan_ektopik',
        'mola_hidatidosa'
    ) DEFAULT NULL,
    `jenis_kelamin` enum('L','P') DEFAULT NULL COMMENT 'NULL = tidak berlaku (abortus)',
    `usia_kehamilan_lahir` enum(
        'lt_22',
        '22_27',
        '28_36',
        '37_41',
        'ge_42'
    ) DEFAULT NULL COMMENT 'Minggu: abortus / imatur / prematur / aterm / postterm',
    `metode_persalinan` enum(
        'spontan_belakang_kepala',
        'spontan_sungsang',
        'vakum',
        'forsep',
        'sectio_caesarea',
        'kuretase',
        'lainnya'
    ) DEFAULT NULL,
    `tempat_persalinan` enum(
        'rumah_sakit',
        'puskesmas',
        'klinik',
        'praktik_bidan',
        'rumah',
        'lainnya'
    ) DEFAULT NULL,
    `penolong_persalinan` enum(
        'dokter_spog',
        'dokter_umum',
        'bidan',
        'dukun',
        'tanpa_penolong'
    ) DEFAULT NULL,
    `berat_lahir_kategori` enum(
        'lt_1500',
        '1500_2499',
        '2500_4000',
        'gt_4000'
    ) DEFAULT NULL COMMENT 'Gram; NULL = tidak berlaku',
    `kondisi_bayi` enum(
        'hidup',
        'meninggal_perinatal',
        'meninggal_setelah_perinatal'
    ) DEFAULT NULL COMMENT 'NULL = tidak berlaku (abortus)',
    `keterangan` text DEFAULT NULL COMMENT 'Jarak kehamilan, ASI, KB, atau catatan lain',
    `created_at` datetime NOT NULL DEFAULT current_timestamp(),
    `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
    PRIMARY KEY (`id_riwayat_kehamilan`),
    UNIQUE KEY `uq_riwayat_kunjungan_ke` (`id_kunjungan`, `kehamilan_ke`),
    CONSTRAINT `fk_riwayat_kunjungan` FOREIGN KEY (`id_kunjungan`)
        REFERENCES `t_kunjungan` (`id_kunjungan`) ON DELETE CASCADE,
    CONSTRAINT `chk_riwayat_kehamilan_ke` CHECK (`kehamilan_ke` BETWEEN 1 AND 20),
    CONSTRAINT `chk_riwayat_tahun` CHECK (
        `tahun_persalinan` IS NULL
        OR `tahun_persalinan` BETWEEN 1950 AND 2100
    )
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Bagian 2B baris 8 & 9: penyakit saat hamil dan komplikasi per kehamilan (multi-select).
CREATE TABLE `t_riwayat_kehamilan_kondisi` (
    `id_riwayat_kehamilan` int(11) NOT NULL,
    `kode_pilihan` varchar(60) NOT NULL,
    `catatan` varchar(255) DEFAULT NULL,
    PRIMARY KEY (`id_riwayat_kehamilan`, `kode_pilihan`),
    KEY `idx_riwayat_kondisi_pilihan` (`kode_pilihan`),
    CONSTRAINT `fk_riwayat_kondisi_riwayat` FOREIGN KEY (`id_riwayat_kehamilan`)
        REFERENCES `t_riwayat_kehamilan` (`id_riwayat_kehamilan`) ON DELETE CASCADE,
    CONSTRAINT `fk_riwayat_kondisi_pilihan` FOREIGN KEY (`kode_pilihan`)
        REFERENCES `m_obstetri_pilihan` (`kode`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- -----------------------------------------------------------------------------
-- Bagian 2C: checklist riwayat penyakit & kondisi medis ibu (multi-select).
-- -----------------------------------------------------------------------------
CREATE TABLE `t_kunjungan_kondisi_ibu` (
    `id_kunjungan` int(11) NOT NULL,
    `kode_pilihan` varchar(60) NOT NULL,
    `catatan` varchar(255) DEFAULT NULL,
    PRIMARY KEY (`id_kunjungan`, `kode_pilihan`),
    KEY `idx_kondisi_ibu_pilihan` (`kode_pilihan`),
    CONSTRAINT `fk_kondisi_ibu_kunjungan` FOREIGN KEY (`id_kunjungan`)
        REFERENCES `t_kunjungan` (`id_kunjungan`) ON DELETE CASCADE,
    CONSTRAINT `fk_kondisi_ibu_pilihan` FOREIGN KEY (`kode_pilihan`)
        REFERENCES `m_obstetri_pilihan` (`kode`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- -----------------------------------------------------------------------------
-- Data awal sesuai contoh pada spesifikasi. Daftar lengkap, tingkat risiko,
-- dan rekomendasi wajib divalidasi tim klinis sebelum dipakai.
-- -----------------------------------------------------------------------------
INSERT INTO `m_obstetri_pilihan`
    (`kode`, `kelompok`, `label`, `tingkat_risiko`, `rekomendasi`, `urutan`)
VALUES
    ('hipertensi_gestasional', 'penyakit_hamil', 'Hipertensi gestasional', 'risiko', 'Pantau rekurensi hipertensi dalam kehamilan', 10),
    ('hipertensi_kronis', 'penyakit_hamil', 'Hipertensi kronis', 'risiko', 'Pantau rekurensi hipertensi dalam kehamilan', 20),
    ('anemia', 'penyakit_hamil', 'Anemia (Hb < 11 g/dL)', 'risiko', NULL, 30),
    ('preeklampsia', 'komplikasi_persalinan_nifas', 'Preeklampsia', 'bahaya', 'Wajib Aspirin + Kalsium', 10),
    ('eklampsia', 'komplikasi_persalinan_nifas', 'Eklampsia', 'bahaya', 'Wajib Aspirin + Kalsium', 20),
    ('tidak_ada_komplikasi', 'komplikasi_persalinan_nifas', 'Tidak ada komplikasi', 'normal', NULL, 0);
