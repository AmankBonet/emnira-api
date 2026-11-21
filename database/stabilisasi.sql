-- Bagian 6: checklist kepatuhan stabilisasi pra-rujukan (dinamis sesuai diagnosa terpilih).
-- Master butir protokol diambil dari dokumen STABILISASI (PPK I); hasil pelaksanaan dicatat per kunjungan.
-- Bergantung pada t_kunjungan, m_diagnosis_ponek, dan m_rm_pilihan. Dibutuhkan MariaDB 10.2+.

-- -----------------------------------------------------------------------------
-- Master butir protokol per diagnosis.
-- Satu baris = satu tanda □ pada dokumen protokol.
-- -----------------------------------------------------------------------------
CREATE TABLE `m_stabilisasi_item` (
    `id_item` int(11) NOT NULL AUTO_INCREMENT,
    `kode` varchar(60) NOT NULL COMMENT 'Kode stabil untuk DML/upsert, contoh: peb_c_mgso4_loading_iv',
    `id_diagnosis` int(11) NOT NULL,
    `komponen` enum(
        'red_flag',
        'oksigenasi_posisi',
        'akses_iv_cairan',
        'medikamentosa',
        'monitoring',
        'target_eskalasi'
    ) NOT NULL COMMENT 'red_flag = kriteria; lainnya = komponen A-E Bagian 6',
    `urutan` smallint unsigned NOT NULL DEFAULT 0,
    `teks_standar` text NOT NULL COMMENT 'Standar protokol klinis (auto-lookup)',

    -- Cara butir dinilai.
    `jenis` enum('kriteria','tindakan') NOT NULL DEFAULT 'tindakan'
        COMMENT 'kriteria = terpenuhi/tidak; tindakan = dilaksanakan/tidak',
    `sifat` enum('wajib','kondisional','alternatif') NOT NULL DEFAULT 'wajib'
        COMMENT 'kondisional = hanya bila syarat terpenuhi; alternatif = cukup satu dari kelompok_alternatif',
    `kelompok_alternatif` varchar(40) DEFAULT NULL
        COMMENT 'Butir dengan nilai sama saling menggantikan, contoh: loading MgSO4 IV atau IM',
    `syarat` varchar(255) DEFAULT NULL COMMENT 'Syarat klinis untuk butir kondisional',

    -- Dosis berurutan (contoh: Nifedipin ke-1, ke-2, ke-3).
    `kelompok_dosis` varchar(40) DEFAULT NULL,
    `dosis_ke` tinyint unsigned DEFAULT NULL,
    `jeda_menit` smallint unsigned DEFAULT NULL COMMENT 'Jeda minimal dari dosis sebelumnya',

    -- Target waktu dihitung sejak trigger merah MEOWS pertama pada kunjungan.
    `target_menit` smallint unsigned DEFAULT NULL,

    -- Penilaian otomatis dari observasi terakhir (diimplementasikan di aplikasi).
    `kode_aturan` varchar(40) DEFAULT NULL
        COMMENT 'td_berat | proteinuria_plus2 | ...; NULL = dinilai manual',

    `kode_terapi_rm` varchar(60) DEFAULT NULL
        COMMENT 'Penghubung ke m_rm_pilihan kelompok terapi (formulir rujukan API)',
    `masuk_sbar` tinyint NOT NULL DEFAULT 1 COMMENT '1 = dikirim otomatis ke laporan SBAR',
    `id_active` tinyint NOT NULL DEFAULT 1,
    PRIMARY KEY (`id_item`),
    UNIQUE KEY `uq_stabilisasi_kode` (`kode`),
    KEY `idx_stabilisasi_diagnosis` (`id_diagnosis`, `id_active`, `komponen`, `urutan`),
    KEY `idx_stabilisasi_terapi` (`kode_terapi_rm`),
    CONSTRAINT `fk_stabilisasi_diagnosis` FOREIGN KEY (`id_diagnosis`)
        REFERENCES `m_diagnosis_ponek` (`id_diagnosis`),
    CONSTRAINT `fk_stabilisasi_terapi` FOREIGN KEY (`kode_terapi_rm`)
        REFERENCES `m_rm_pilihan` (`kode`),
    CONSTRAINT `chk_stabilisasi_alternatif` CHECK (
        `sifat` <> 'alternatif' OR `kelompok_alternatif` IS NOT NULL
    ),
    CONSTRAINT `chk_stabilisasi_dosis` CHECK (
        (`kelompok_dosis` IS NULL AND `dosis_ke` IS NULL)
        OR (`kelompok_dosis` IS NOT NULL AND `dosis_ke` >= 1)
    )
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- -----------------------------------------------------------------------------
-- Diagnosa kerja per kunjungan; satu kunjungan boleh lebih dari satu diagnosa
-- (contoh PEB + HPP) sehingga checklist dapat digabung.
-- -----------------------------------------------------------------------------
CREATE TABLE `t_kunjungan_diagnosis` (
    `id_kunjungan` int(11) NOT NULL,
    `id_diagnosis` int(11) NOT NULL,
    `waktu_ditetapkan` datetime NOT NULL DEFAULT current_timestamp(),
    `id_user` int(11) DEFAULT NULL,
    PRIMARY KEY (`id_kunjungan`, `id_diagnosis`),
    KEY `idx_kunjungan_diagnosis_master` (`id_diagnosis`),
    CONSTRAINT `fk_kunjungan_diagnosis_kunjungan` FOREIGN KEY (`id_kunjungan`)
        REFERENCES `t_kunjungan` (`id_kunjungan`) ON DELETE CASCADE,
    CONSTRAINT `fk_kunjungan_diagnosis_master` FOREIGN KEY (`id_diagnosis`)
        REFERENCES `m_diagnosis_ponek` (`id_diagnosis`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- -----------------------------------------------------------------------------
-- Pelaksanaan butir stabilisasi per kunjungan.
-- -----------------------------------------------------------------------------
CREATE TABLE `t_kunjungan_stabilisasi` (
    `id_kunjungan` int(11) NOT NULL,
    `id_item` int(11) NOT NULL,
    `status` enum(
        'belum',
        'dilakukan',
        'dalam_proses',
        'tidak_dilakukan',
        'tidak_diperlukan',
        'terpenuhi',
        'tidak_terpenuhi'
    ) NOT NULL DEFAULT 'belum'
        COMMENT 'terpenuhi/tidak_terpenuhi hanya untuk jenis kriteria',
    `detail_tindakan` varchar(500) DEFAULT NULL COMMENT 'Detail dosis / tindakan terlaksana',
    `alasan` varchar(255) DEFAULT NULL COMMENT 'Wajib bila tidak_dilakukan atau tidak_diperlukan',
    `waktu_pelaksanaan` datetime DEFAULT NULL,
    `id_user` int(11) DEFAULT NULL,
    `created_at` datetime NOT NULL DEFAULT current_timestamp(),
    `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
    PRIMARY KEY (`id_kunjungan`, `id_item`),
    KEY `idx_kunjungan_stabilisasi_item` (`id_item`),
    CONSTRAINT `fk_kunjungan_stabilisasi_kunjungan` FOREIGN KEY (`id_kunjungan`)
        REFERENCES `t_kunjungan` (`id_kunjungan`) ON DELETE CASCADE,
    CONSTRAINT `fk_kunjungan_stabilisasi_item` FOREIGN KEY (`id_item`)
        REFERENCES `m_stabilisasi_item` (`id_item`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
