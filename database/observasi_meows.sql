-- Bagian 4: input data objektif (tanda vital, urin, perdarahan, nyeri & obstetri) + skor MEOWS.
-- Satu baris = satu kali observasi, sehingga pemantauan berkala (tren MEOWS) dapat dicatat.
-- Jalankan setelah database/obstetri.sql. Dibutuhkan MariaDB 10.2+ (CHECK & generated column).
--
-- Skor MEOWS dihitung server dari application/config/meows.php lalu disimpan bersama versi
-- aturannya, karena ambang batas bisa berubah dan skor lama harus tetap dapat ditelusuri.
-- Nilai turunan murni rumus (MAP, shock index, urine output, TBJ) dihitung oleh database.

CREATE TABLE `t_kunjungan_observasi` (
    `id_observasi` int(11) NOT NULL AUTO_INCREMENT,
    `id_kunjungan` int(11) NOT NULL,
    `waktu_observasi` datetime NOT NULL,
    `id_user_pencatat` int(11) DEFAULT NULL,

    -- Parameter MEOWS.
    `rr_per_menit` tinyint unsigned DEFAULT NULL COMMENT 'LOINC 9279-1',
    `spo2_persen` tinyint unsigned DEFAULT NULL COMMENT 'LOINC 2708-6',
    `suhu_celsius` decimal(3,1) DEFAULT NULL COMMENT 'LOINC 8310-5',
    `td_sistolik_mmhg` smallint unsigned DEFAULT NULL COMMENT 'LOINC 8480-6',
    `td_diastolik_mmhg` smallint unsigned DEFAULT NULL COMMENT 'LOINC 8462-4',
    `nadi_per_menit` smallint unsigned DEFAULT NULL COMMENT 'LOINC 8867-4',
    `avpu` enum('A','V','P','U') DEFAULT NULL COMMENT 'LOINC 80288-4',
    `urin_ml_jam` decimal(6,1) DEFAULT NULL COMMENT 'LOINC 9187-6; produksi urin mL/jam',
    `perdarahan` enum('minimal','sedang','banyak','masif') DEFAULT NULL COMMENT 'SIMRS: obs_bleeding; lochia / perdarahan pervaginam',
    `proteinuria` enum('negatif','trace','plus_1','plus_2','plus_3','plus_4') DEFAULT NULL COMMENT 'LOINC 5804-0; dipstick',
    `nyeri_nrs` tinyint unsigned DEFAULT NULL COMMENT 'LOINC 72514-3; skala 0-10',

    -- Data obstetri pendukung (tidak masuk skor MEOWS).
    `berat_badan_kg` decimal(5,2) DEFAULT NULL COMMENT 'LOINC 29463-7',
    `tfu_cm` decimal(4,1) DEFAULT NULL COMMENT 'LOINC 11881-0',
    `kepala_masuk_panggul` tinyint DEFAULT NULL COMMENT '1=ya, 0=tidak; menentukan konstanta TBJ',
    `djj_per_menit` smallint unsigned DEFAULT NULL COMMENT 'LOINC 55283-6',
    `vt_pembukaan` enum('belum','1_3','4_7','8_9','lengkap') DEFAULT NULL COMMENT 'SIMRS: obs_cervix_dilation',
    `ketuban` enum('utuh','pecah_jernih','pecah_keruh_meconium','pecah_berbau') DEFAULT NULL COMMENT 'SIMRS: obs_membranes',

    -- Hasil MEOWS yang dihitung server.
    `meows_skor` tinyint unsigned DEFAULT NULL,
    `meows_jumlah_merah` tinyint unsigned DEFAULT NULL,
    `meows_jumlah_kuning` tinyint unsigned DEFAULT NULL,
    `meows_zona` enum('normal','kuning','merah') DEFAULT NULL,
    `meows_parameter_terisi` tinyint unsigned DEFAULT NULL COMMENT 'Dari 11 parameter MEOWS',
    `meows_versi_aturan` varchar(30) DEFAULT NULL,

    -- Turunan rumus; dihitung database agar selalu konsisten dengan nilai input.
    `map_mmhg` decimal(5,1) AS (
        (`td_sistolik_mmhg` + 2 * `td_diastolik_mmhg`) / 3
    ) STORED COMMENT 'LOINC 8478-0; (TDS + 2 x TDD) / 3',
    `shock_index` decimal(4,2) AS (
        `nadi_per_menit` / NULLIF(`td_sistolik_mmhg`, 0)
    ) STORED COMMENT 'Nadi / TDS',
    `urin_ml_kg_jam` decimal(6,2) AS (
        `urin_ml_jam` / NULLIF(`berat_badan_kg`, 0)
    ) STORED COMMENT 'Produksi urin / BB',
    `tbj_gram` smallint AS (
        CASE
            WHEN `tfu_cm` IS NULL OR `kepala_masuk_panggul` IS NULL THEN NULL
            ELSE GREATEST(ROUND((`tfu_cm` - IF(`kepala_masuk_panggul` = 1, 11, 12)) * 155), 0)
        END
    ) STORED COMMENT 'Johnson-Toshach: (TFU - 11 bila kepala masuk panggul, 12 bila belum) x 155',

    `created_at` datetime NOT NULL DEFAULT current_timestamp(),
    `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
    PRIMARY KEY (`id_observasi`),
    KEY `idx_observasi_kunjungan_waktu` (`id_kunjungan`, `waktu_observasi`),
    CONSTRAINT `fk_observasi_kunjungan` FOREIGN KEY (`id_kunjungan`)
        REFERENCES `t_kunjungan` (`id_kunjungan`) ON DELETE CASCADE,

    -- Batas fisiologis kasar untuk menolak salah ketik; batas klinis ada di config MEOWS.
    CONSTRAINT `chk_observasi_rr` CHECK (`rr_per_menit` IS NULL OR `rr_per_menit` BETWEEN 0 AND 80),
    CONSTRAINT `chk_observasi_spo2` CHECK (`spo2_persen` IS NULL OR `spo2_persen` BETWEEN 0 AND 100),
    CONSTRAINT `chk_observasi_suhu` CHECK (`suhu_celsius` IS NULL OR `suhu_celsius` BETWEEN 25 AND 45),
    CONSTRAINT `chk_observasi_tds` CHECK (`td_sistolik_mmhg` IS NULL OR `td_sistolik_mmhg` BETWEEN 30 AND 300),
    CONSTRAINT `chk_observasi_tdd` CHECK (`td_diastolik_mmhg` IS NULL OR `td_diastolik_mmhg` BETWEEN 10 AND 200),
    CONSTRAINT `chk_observasi_nadi` CHECK (`nadi_per_menit` IS NULL OR `nadi_per_menit` BETWEEN 0 AND 300),
    CONSTRAINT `chk_observasi_nyeri` CHECK (`nyeri_nrs` IS NULL OR `nyeri_nrs` BETWEEN 0 AND 10),
    CONSTRAINT `chk_observasi_bb` CHECK (`berat_badan_kg` IS NULL OR `berat_badan_kg` BETWEEN 20 AND 300),
    CONSTRAINT `chk_observasi_tfu` CHECK (`tfu_cm` IS NULL OR `tfu_cm` BETWEEN 0 AND 60),
    CONSTRAINT `chk_observasi_djj` CHECK (`djj_per_menit` IS NULL OR `djj_per_menit` BETWEEN 0 AND 250)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
