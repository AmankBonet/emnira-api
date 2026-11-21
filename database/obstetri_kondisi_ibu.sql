-- Bagian 2C: master checklist riwayat penyakit & kondisi medis ibu.
-- Jalankan setelah database/obstetri.sql. Aman dijalankan ulang (ADD COLUMN IF NOT EXISTS dan
-- INSERT ... ON DUPLICATE KEY UPDATE; butuh MariaDB 10.0.2+).
--
-- PERHATIAN: kolom "Dampak & Risiko" dan "Tindakan / Skrining Wajib PPK I" pada spesifikasi
-- terpotong di tangkapan layar. Teks di bawah melengkapi potongan tersebut seminimal mungkin
-- dan WAJIB dicocokkan dengan dokumen sumber / tim klinis sebelum dieksekusi.
-- tingkat_risiko tidak ada di spesifikasi; semua diisi 'risiko' sebagai nilai awal.

-- -----------------------------------------------------------------------------
-- 1. Struktur: kolom tambahan untuk kategori klinis, dampak, ICD-10, dan catatan wajib.
-- -----------------------------------------------------------------------------
ALTER TABLE `m_obstetri_pilihan`
    ADD COLUMN IF NOT EXISTS `kategori_klinis` varchar(100) DEFAULT NULL
        COMMENT 'Sub-kelompok tampilan, contoh: Penyakit Kronis & Sistemik'
        AFTER `kelompok`,
    ADD COLUMN IF NOT EXISTS `dampak_risiko` text DEFAULT NULL
        COMMENT 'Dampak & risiko terhadap kehamilan'
        AFTER `tingkat_risiko`,
    MODIFY COLUMN `rekomendasi` text DEFAULT NULL
        COMMENT 'Tindakan / skrining wajib PPK I',
    ADD COLUMN IF NOT EXISTS `kode_icd10` varchar(50) DEFAULT NULL
        COMMENT 'Kode ICD-10 komorbid, boleh lebih dari satu dipisah /'
        AFTER `rekomendasi`,
    ADD COLUMN IF NOT EXISTS `wajib_catatan` tinyint NOT NULL DEFAULT 0
        COMMENT '1 = catatan wajib diisi saat dipilih (contoh: nama obat alergi)'
        AFTER `kode_icd10`;

-- -----------------------------------------------------------------------------
-- 2. Data: 15 kondisi medis ibu sesuai spesifikasi Bagian 2C.
-- urutan = nomor pada spesifikasi (1.1 -> 101, 2.3 -> 203, dst.).
-- -----------------------------------------------------------------------------
INSERT INTO `m_obstetri_pilihan`
    (`kode`, `kelompok`, `kategori_klinis`, `label`, `tingkat_risiko`, `dampak_risiko`, `rekomendasi`, `kode_icd10`, `wajib_catatan`, `urutan`, `id_active`)
VALUES
    -- 1. Penyakit Kronis & Sistemik
    ('ibu_hipertensi', 'kondisi_ibu', 'Penyakit Kronis & Sistemik',
        'Hipertensi (tekanan darah tinggi)', 'risiko',
        'Risiko tinggi preeklampsia superimposed.',
        'Pantau TD ketat, proteinuria, Aspirin 80–150 mg.',
        'O10 / I10', 0, 101, 1),
    ('ibu_diabetes', 'kondisi_ibu', 'Penyakit Kronis & Sistemik',
        'Diabetes melitus (DM / DMG)', 'risiko',
        'Risiko makrosomia, polihidramnion.',
        'Cek GDS/GDP, diet DM gestasional, rujuk Sp.PD / Sp.OG.',
        'O24', 0, 102, 1),
    ('ibu_jantung_ginjal', 'kondisi_ibu', 'Penyakit Kronis & Sistemik',
        'Penyakit jantung dan ginjal', 'risiko',
        'Risiko gagal jantung akut / edema paru.',
        'Batasi cairan ketat, evaluasi NYHA, wajib rujuk.',
        'O99.4 / O99.8', 0, 103, 1),
    ('ibu_tiroid', 'kondisi_ibu', 'Penyakit Kronis & Sistemik',
        'Penyakit tiroid (hipotiroid atau hipertiroid)', 'risiko',
        'Risiko badai tiroid, abortus, prematuritas.',
        'Cek TSH/FT4, lanjutkan PTU/Levotiroksin sesuai anjuran dokter.',
        'O99.2', 0, 104, 1),
    ('ibu_asma_paru', 'kondisi_ibu', 'Penyakit Kronis & Sistemik',
        'Asma dan penyakit paru (termasuk TBC)', 'risiko',
        'Risiko hipoksia janin saat serangan.',
        'Pastikan inhaler tersedia, skrining BTA/TCM.',
        'O99.5 / O98.0', 0, 105, 1),
    ('ibu_autoimun', 'kondisi_ibu', 'Penyakit Kronis & Sistemik',
        'Penyakit autoimun (seperti lupus / SLE)', 'risiko',
        'Risiko flare lupus, APS, trombosis.',
        'Kolaborasi multidisiplin Sp.OG & Sp.PD-KR.',
        'M32 / O99.8', 0, 106, 1),

    -- 2. Riwayat Infeksi Berbahaya
    ('ibu_torch', 'kondisi_ibu', 'Riwayat Infeksi Berbahaya',
        'Infeksi TORCH (Toksoplasmosis, Rubella, CMV, Herpes)', 'risiko',
        'Risiko kelainan kongenital janin.',
        'Konseling & pemeriksaan serologi IgG/IgM.',
        'O98.5 / P35', 0, 201, 1),
    ('ibu_hepatitis_b', 'kondisi_ibu', 'Riwayat Infeksi Berbahaya',
        'Hepatitis B (HBsAg reaktif)', 'risiko',
        'Risiko penularan vertikal ibu ke bayi.',
        'Siapkan HBIG < 12 jam + vaksin Hep B0 saat lahir.',
        'O98.4', 0, 202, 1),
    ('ibu_hiv_sifilis', 'kondisi_ibu', 'Riwayat Infeksi Berbahaya',
        'HIV / AIDS dan sifilis', 'risiko',
        'Risiko transmisi vertikal HIV / sifilis.',
        'Pastikan terapi ARV / Benzathine Penicillin.',
        'O98.7 / O98.1', 0, 203, 1),
    ('ibu_malaria', 'kondisi_ibu', 'Riwayat Infeksi Berbahaya',
        'Malaria', 'risiko',
        'Risiko anemia berat maternal.',
        'Pemeriksaan RDT/mikroskopis darah, terapi sesuai pedoman.',
        'O98.6', 0, 204, 1),

    -- 3. Gangguan Darah & Gizi
    ('ibu_anemia', 'kondisi_ibu', 'Gangguan Darah & Gizi',
        'Anemia (kurang darah / Hb < 11 g/dL)', 'risiko',
        'Risiko syok pada perdarahan masif.',
        'Cek Hb lengkap, berikan TTD dosis terapeutik.',
        'O99.0', 0, 301, 1),
    ('ibu_kelainan_darah', 'kondisi_ibu', 'Gangguan Darah & Gizi',
        'Kelainan darah bawaan (talasemia, hemofilia)', 'risiko',
        'Risiko krisis hemolitik, perdarahan.',
        'Cek elektroforesis Hb / faktor pembekuan, rujuk.',
        'O99.1', 0, 302, 1),
    ('ibu_obesitas_kek', 'kondisi_ibu', 'Gangguan Darah & Gizi',
        'Obesitas (IMT >= 30) atau Kurang Energi Kronis (KEK)', 'risiko',
        'Obesitas: risiko PEB/DMG/SC | KEK: risiko BBLR.',
        'Ukur LiLA & IMT awal, PMT ibu hamil (KEK).',
        'O99.21 / E44', 0, 303, 1),

    -- 4. Kesehatan Mental & Alergi
    ('ibu_psikologis', 'kondisi_ibu', 'Kesehatan Mental & Alergi',
        'Gangguan psikologis (depresi atau kecemasan)', 'risiko',
        'Risiko depresi postpartum.',
        'Skrining EPDS, dukungan psikososial keluarga.',
        'O99.34', 0, 401, 1),
    ('ibu_alergi_obat', 'kondisi_ibu', 'Kesehatan Mental & Alergi',
        'Riwayat alergi obat (tuliskan nama obat di catatan)', 'risiko',
        'Risiko syok anafilaksis bila diberikan obat pemicu.',
        'Pasang gelang risiko alergi & catat jelas di rekam medis.',
        'Z88', 1, 402, 1)
ON DUPLICATE KEY UPDATE
    `kelompok` = VALUES(`kelompok`),
    `kategori_klinis` = VALUES(`kategori_klinis`),
    `label` = VALUES(`label`),
    `tingkat_risiko` = VALUES(`tingkat_risiko`),
    `dampak_risiko` = VALUES(`dampak_risiko`),
    `rekomendasi` = VALUES(`rekomendasi`),
    `kode_icd10` = VALUES(`kode_icd10`),
    `wajib_catatan` = VALUES(`wajib_catatan`),
    `urutan` = VALUES(`urutan`),
    `id_active` = VALUES(`id_active`);
