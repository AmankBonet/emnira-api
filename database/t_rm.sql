-- Rancangan CREATE TABLE, bukan ALTER untuk t_rm yang sudah ada.
-- Bandingkan dengan SHOW CREATE TABLE t_rm sebelum diterapkan.
-- Tidak menjalankan DROP atau mengubah tabel secara otomatis.
-- Nilai NULL berarti belum diisi/belum dinilai, bukan jawaban Tidak.

CREATE TABLE `m_diagnosis_ponek` (
    `id_diagnosis` int(11) NOT NULL AUTO_INCREMENT,
    `kode` varchar(30) NOT NULL,
    `nama` varchar(255) NOT NULL,
    `id_active` tinyint NOT NULL DEFAULT 1,
    PRIMARY KEY (`id_diagnosis`),
    UNIQUE KEY `uq_diagnosis_kode` (`kode`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Isi 11 diagnosis sesuai daftar resmi yang disepakati tim klinis.
CREATE TABLE `t_rm` (
    `id_rm` bigint unsigned NOT NULL AUTO_INCREMENT,
    `id_kunjungan` int(11) DEFAULT NULL,
    `id_faskes_asal` int(11) NOT NULL,
    `id_faskes_tujuan` int(11) NOT NULL,
    `id_user_pembuat` int(11) NOT NULL,
    `id_user_pengubah` int(11) DEFAULT NULL,
    `waktu_rujukan` datetime DEFAULT NULL,
    `id_diagnosis` int(11) DEFAULT NULL,

    -- 2. Diagnosis dan skrining; jawaban checkbox disimpan di t_rm_pilihan.
    `skrining_bahaya_sudah_dinilai` tinyint DEFAULT NULL COMMENT '1=sudah, 0=belum',

    -- 3. Perdarahan dan obstetri.
    `perdarahan_status` enum('tidak_ada','flek','aktif') DEFAULT NULL,
    `perdarahan_volume` enum('lt_500','500_1000','gt_1000') DEFAULT NULL COMMENT 'mL',
    `perdarahan_karakteristik` enum('darah_segar','stelsel','sisa_jaringan') DEFAULT NULL,
    `tfu_cm` decimal(5,2) DEFAULT NULL,
    `kepala_masuk_panggul` tinyint DEFAULT NULL COMMENT '1=ya, 0=tidak',
    `djj_per_menit` smallint unsigned DEFAULT NULL,
    `djj_irama` enum('teratur','ireguler','unheard') DEFAULT NULL,
    `his_frekuensi` enum('tidak_ada','lt_2','3','4','5') DEFAULT NULL COMMENT 'Per 10 menit; kategori 2 belum ada pada spesifikasi',
    `his_durasi` enum('relaksasi','lt_20','20_40','gt_40') DEFAULT NULL COMMENT 'Detik',
    `vt_pembukaan` enum('belum','1_3','4_7','8_9','lengkap') DEFAULT NULL,
    `ketuban` enum('utuh','pecah_jernih','pecah_keruh_meconium','pecah_berbau') DEFAULT NULL,
    `penurunan_kepala` enum('floating','hodge_1_2','hodge_3_4') DEFAULT NULL,
    -- Komponen berikut diperlukan bila skor Bishop akan dihitung.
    `bishop_dilatasi_cm` decimal(4,1) DEFAULT NULL,
    `bishop_penipisan_persen` tinyint unsigned DEFAULT NULL,
    `bishop_station` tinyint DEFAULT NULL,
    `bishop_konsistensi` varchar(30) DEFAULT NULL,
    `bishop_posisi` varchar(30) DEFAULT NULL,
    `bishop_skor` tinyint unsigned DEFAULT NULL COMMENT 'Dihitung server hanya jika komponen lengkap dan aturan disetujui',
    `bishop_versi_aturan` varchar(50) DEFAULT NULL,

    -- 4. Riwayat medis; pilihan multipel disimpan di t_rm_pilihan.
    `riwayat_medis_sudah_dinilai` tinyint DEFAULT NULL,
    `alergi_obat_detail` text DEFAULT NULL,
    `riwayat_sc` enum('tidak','1_kali','ge_2_kali') DEFAULT NULL,
    `jarak_sc_tahun` decimal(4,1) DEFAULT NULL,
    `operasi_ginekologi` tinyint DEFAULT NULL COMMENT '1=ya, 0=tidak',
    `operasi_ginekologi_detail` text DEFAULT NULL,

    -- 5. Tanda vital dan urin.
    `td_sistolik_mmhg` smallint unsigned DEFAULT NULL,
    `td_diastolik_mmhg` smallint unsigned DEFAULT NULL,
    `nadi_per_menit` smallint unsigned DEFAULT NULL,
    `rr_per_menit` smallint unsigned DEFAULT NULL,
    `suhu_celsius` decimal(4,1) DEFAULT NULL,
    `spo2_persen` decimal(5,2) DEFAULT NULL,
    `kateter_urin_terpasang` tinyint DEFAULT NULL COMMENT 'Dipakai bersama oleh bagian 5 dan 6',
    `berat_badan_kg` decimal(6,2) DEFAULT NULL COMMENT 'Diperlukan untuk mL/kg/jam',
    `produksi_urin_total_ml` decimal(10,2) DEFAULT NULL,
    `durasi_pengamatan_urin_jam` decimal(6,2) DEFAULT NULL,
    `urin_output_tercatat_ml_jam` decimal(10,2) DEFAULT NULL COMMENT 'Isian mL/jam pada stabilisasi; berbeda dari mL/kg/jam',
    `urin_output_ml_kg_jam` decimal(10,4)
        GENERATED ALWAYS AS (
            CASE
                WHEN berat_badan_kg > 0 AND durasi_pengamatan_urin_jam > 0
                THEN produksi_urin_total_ml / berat_badan_kg / durasi_pengamatan_urin_jam
                ELSE NULL
            END
        ) STORED,
    `proteinuria` enum('negatif','trace','plus_1','plus_2','plus_3','plus_4') DEFAULT NULL,
    `hematuria` tinyint DEFAULT NULL COMMENT '1=ya, 0=tidak',

    -- 6. Dokumentasi stabilisasi, bukan instruksi terapi.
    `oksigenasi` enum('tidak_diberikan','nasal_kanul','nrm','bvm') DEFAULT NULL,
    `oksigen_aliran_lpm` decimal(5,2) DEFAULT NULL,
    `oksigen_konsentrasi_persen` decimal(5,2) DEFAULT NULL,
    `akses_iv` enum('single_line_18_20','double_line_16_18') DEFAULT NULL,
    `jenis_cairan` enum('rl','nacl_0_9','d5','rd5') DEFAULT NULL,
    `cairan_total_ml` decimal(10,2) DEFAULT NULL,
    `tetesan_mode` enum('maintenance','guyur','fast_drip') DEFAULT NULL,
    `tetesan_per_menit` smallint unsigned DEFAULT NULL,
    `terapi_sudah_dinilai` tinyint DEFAULT NULL,

    -- 7. Status pembuatan resume PDF; berkas disimpan di t_rm_lampiran.
    `resume_status` enum('belum','antri','diproses','selesai','gagal') NOT NULL DEFAULT 'belum',
    `resume_dibuat_pada` datetime DEFAULT NULL,
    `resume_error` text DEFAULT NULL,
    `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id_rm`),
    KEY `idx_rm_asal_waktu` (`id_faskes_asal`,`waktu_rujukan`),
    KEY `idx_rm_tujuan_waktu` (`id_faskes_tujuan`,`waktu_rujukan`),
    KEY `idx_rm_kunjungan` (`id_kunjungan`),
    CONSTRAINT `fk_rm_diagnosis` FOREIGN KEY (`id_diagnosis`)
        REFERENCES `m_diagnosis_ponek` (`id_diagnosis`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Satu baris untuk setiap checkbox terpilih; tidak disimpan sebagai teks dipisah koma.
CREATE TABLE `m_rm_pilihan` (
    `kode` varchar(60) NOT NULL,
    `kelompok` enum('tanda_bahaya','riwayat_medis','terapi') NOT NULL,
    `label` varchar(255) NOT NULL,
    PRIMARY KEY (`kode`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

INSERT INTO `m_rm_pilihan` (`kode`,`kelompok`,`label`) VALUES
('bahaya_neurologis','tanda_bahaya','Nyeri kepala hebat / Pandangan kabur / Nyeri ulu hati'),
('bahaya_kejang','tanda_bahaya','Kejang / Penurunan kesadaran'),
('bahaya_pernapasan','tanda_bahaya','Sesak napas berat / Sianosis'),
('bahaya_perdarahan','tanda_bahaya','Perdarahan pervaginam masif / Aktif'),
('bahaya_infeksi','tanda_bahaya','Demam tinggi / Menggigil / Ketuban berbau'),
('bahaya_bekas_sc','tanda_bahaya','Nyeri lokal menetap pada bekas SC'),
('bahaya_mules','tanda_bahaya','Mules terus menerus tanpa relaksasi'),
('riwayat_tidak_ada','riwayat_medis','Tidak Ada Riwayat Penyakit'),
('riwayat_hipertensi','riwayat_medis','Hipertensi Sebelum Hamil / Gestasional'),
('riwayat_dm','riwayat_medis','Diabetes Mellitus (DM)'),
('riwayat_asma_jantung','riwayat_medis','Asma / Penyakit Jantung'),
('riwayat_alergi','riwayat_medis','Alergi Obat'),
('terapi_mgso4_loading','terapi','MgSO4 Loading Dose'),
('terapi_mgso4_drips','terapi','MgSO4 Drips'),
('terapi_nifedipine','terapi','Antihipertensi (Nifedipine)'),
('terapi_oksitosin','terapi','Oksitosin'),
('terapi_ergometrin','terapi','Ergometrin'),
('terapi_misoprostol','terapi','Misoprostol'),
('terapi_traneksamat','terapi','Asam Traneksamat'),
('terapi_tampon','terapi','Tampon Kondom Kateter'),
('terapi_antibiotik','terapi','Antibiotik Dosis Awal (Ampisilin / Amoksisilin IV)');

CREATE TABLE `t_rm_pilihan` (
    `id_rm` bigint unsigned NOT NULL,
    `kode_pilihan` varchar(60) NOT NULL,
    `catatan` text DEFAULT NULL COMMENT 'Dokumentasi dosis, rute, waktu pemberian atau keterangan tambahan',
    PRIMARY KEY (`id_rm`,`kode_pilihan`),
    CONSTRAINT `fk_rm_pilihan_rm` FOREIGN KEY (`id_rm`) REFERENCES `t_rm` (`id_rm`),
    CONSTRAINT `fk_rm_pilihan_master` FOREIGN KEY (`kode_pilihan`) REFERENCES `m_rm_pilihan` (`kode`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE `t_rm_lampiran` (
    `id_lampiran` bigint unsigned NOT NULL AUTO_INCREMENT,
    `id_rm` bigint unsigned NOT NULL,
    `jenis` enum('partograf','lainnya','resume_pdf') NOT NULL,
    `nama_asli` varchar(255) NOT NULL,
    `storage_key` varchar(512) NOT NULL COMMENT 'Lokasi privat; akses melalui endpoint terautentikasi',
    `mime_type` varchar(100) NOT NULL,
    `ukuran_byte` bigint unsigned NOT NULL,
    `sha256` char(64) DEFAULT NULL,
    `id_user_pengunggah` int(11) NOT NULL,
    `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id_lampiran`),
    KEY `idx_lampiran_rm_jenis` (`id_rm`,`jenis`),
    CONSTRAINT `fk_rm_lampiran_rm` FOREIGN KEY (`id_rm`) REFERENCES `t_rm` (`id_rm`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

