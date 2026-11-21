-- Protokol stabilisasi pra-rujukan: Preeklampsia Berat (PEB) / Eklampsia.
-- Sumber: dokumen STABILISASI, baris 2 "Preeklampsia Berat (PEB) / Eklampsia
-- (Protokol MEOWS & PNPK HDK 2026)". Teks disalin dari dokumen; jangan diubah tanpa tim klinis.
-- Jalankan setelah database/stabilisasi.sql. Aman dijalankan ulang (upsert berdasarkan kode).
--
-- Catatan yang perlu dikonfirmasi tim klinis:
--   - Nifedipin ke-1/2/3 pada baris PEB tidak mencantumkan syarat TD dan jeda; syarat dan jeda
--     30 menit di bawah mengikuti baris 1 (HDK non-preeklampsia) pada dokumen yang sama.
--   - target_menit = 10 untuk evaluasi dokter dihitung sejak trigger merah MEOWS pertama.

-- Diagnosa PEB sudah ada (O14.1); pastikan aktif tanpa mengubah ID-nya.
INSERT INTO `m_diagnosis_ponek` (`kode`, `nama`, `id_active`)
VALUES ('O14.1', 'Preeklampsia Berat (PEB)', 1)
ON DUPLICATE KEY UPDATE `id_active` = 1;

SET @peb := (SELECT `id_diagnosis` FROM `m_diagnosis_ponek` WHERE `kode` = 'O14.1');

INSERT INTO `m_stabilisasi_item`
    (`kode`, `id_diagnosis`, `komponen`, `urutan`, `teks_standar`, `jenis`, `sifat`,
     `kelompok_alternatif`, `syarat`, `kelompok_dosis`, `dosis_ke`, `jeda_menit`,
     `target_menit`, `kode_aturan`, `kode_terapi_rm`, `masuk_sbar`)
VALUES
    -- Red flag / kriteria trigger MEOWS.
    ('peb_r_td_berat', @peb, 'red_flag', 10,
        'Trigger Merah Mutlak: TDS ≥ 160 mmHg ATAU TDD ≥ 110 mmHg.',
        'kriteria', 'wajib', NULL, NULL, NULL, NULL, NULL,
        NULL, 'td_berat', NULL, 1),
    ('peb_r_proteinuria_lab', @peb, 'red_flag', 20,
        'Proteinuria ≥ +2, ATAU hipertensi de novo disertai trombositopenia (< 100.000/µL), kreatinin > 1,1 mg/dL, SGOT/SGPT > 2× normal, atau edema paru.',
        'kriteria', 'wajib', NULL, NULL, NULL, NULL, NULL,
        NULL, 'proteinuria_plus2', NULL, 1),
    ('peb_r_impending', @peb, 'red_flag', 30,
        'Gejala Impending / Eklampsia: nyeri kepala berat menetap, gangguan penglihatan (skotoma/kabur), nyeri epigastrium/kuadran kanan atas, mual-muntah hebat, klonus, penurunan kesadaran, atau kejang tonik-klonik.',
        'kriteria', 'wajib', NULL, NULL, NULL, NULL, NULL,
        NULL, NULL, NULL, 1),

    -- A. Oksigenasi & posisi.
    ('peb_a_posisi_lateral', @peb, 'oksigenasi_posisi', 10,
        'Posisi: lateral kiri (Left Lateral); bila kejang, lindungi dari trauma dan jangan menahan gerakan kejang secara paksa.',
        'tindakan', 'wajib', NULL, NULL, NULL, NULL, NULL,
        NULL, NULL, NULL, 1),
    ('peb_a_suction', @peb, 'oksigenasi_posisi', 20,
        'Airway & Breathing: suction sekret bila perlu.',
        'tindakan', 'kondisional', NULL, 'Bila terdapat sekret / sumbatan jalan napas', NULL, NULL, NULL,
        NULL, NULL, NULL, 1),
    ('peb_a_o2_nasal', @peb, 'oksigenasi_posisi', 30,
        'Oksigen via Nasal Kanul 2–4 L/m.',
        'tindakan', 'alternatif', 'peb_oksigen', NULL, NULL, NULL, NULL,
        NULL, NULL, NULL, 1),
    ('peb_a_o2_nrm', @peb, 'oksigenasi_posisi', 40,
        'Oksigen NRM 6–10 L/m (target SpO2 ≥ 95–97%).',
        'tindakan', 'alternatif', 'peb_oksigen', NULL, NULL, NULL, NULL,
        NULL, NULL, NULL, 1),

    -- B. Akses IV & resusitasi cairan.
    ('peb_b_iv_1_jalur', @peb, 'akses_iv_cairan', 10,
        'Pasang 1 jalur akses IV (G18).',
        'tindakan', 'wajib', NULL, NULL, NULL, NULL, NULL,
        NULL, NULL, NULL, 1),
    ('peb_b_iv_2_jalur', @peb, 'akses_iv_cairan', 20,
        'Pasang 2 jalur IV (terjadi kejang/eklampsia).',
        'tindakan', 'kondisional', NULL, 'Bila terjadi kejang / eklampsia', NULL, NULL, NULL,
        NULL, NULL, NULL, 1),
    ('peb_b_batasi_cairan', @peb, 'akses_iv_cairan', 30,
        'Batasi cairan (target asupan total ~80 mL/jam) menggunakan NaCl 0,9% atau Ringer Laktat tetesan terkontrol; hindari pemberian cairan agresif untuk mencegah edema paru.',
        'tindakan', 'wajib', NULL, NULL, NULL, NULL, NULL,
        NULL, NULL, NULL, 1),

    -- C. Terapi medikamentosa spesifik.
    ('peb_c_mgso4_loading_iv', @peb, 'medikamentosa', 10,
        'Loading Dose: 4 g MgSO4 IV dalam 100 mL NaCl 0,9% diinfuskan selama 20 menit (atau pelan 10–15 mnt; bila sulit akses IV, berikan IM).',
        'tindakan', 'alternatif', 'peb_mgso4_loading', NULL, NULL, NULL, NULL,
        NULL, NULL, 'terapi_mgso4_loading', 1),
    ('peb_c_mgso4_loading_im', @peb, 'medikamentosa', 20,
        'Loading Dose: 4 g MgSO4 secara IM (akses IV line sulit).',
        'tindakan', 'alternatif', 'peb_mgso4_loading', NULL, NULL, NULL, NULL,
        NULL, NULL, 'terapi_mgso4_loading', 1),
    ('peb_c_mgso4_maint_iv', @peb, 'medikamentosa', 30,
        'Maintenance: 1 g/jam IV dalam 500 mL NaCl 0,9% (28 tpm).',
        'tindakan', 'alternatif', 'peb_mgso4_maintenance', NULL, NULL, NULL, NULL,
        NULL, NULL, 'terapi_mgso4_drips', 1),
    ('peb_c_mgso4_maint_im', @peb, 'medikamentosa', 40,
        'Maintenance: 5 g MgSO4 IM ke satu bokong tiap 4 jam.',
        'tindakan', 'alternatif', 'peb_mgso4_maintenance', NULL, NULL, NULL, NULL,
        NULL, NULL, 'terapi_mgso4_drips', 1),
    ('peb_c_mgso4_bolus_kejang', @peb, 'medikamentosa', 50,
        'Kejang Berulang: bolus tambahan 2 g MgSO4 (20%) IV perlahan 5–10 menit.',
        'tindakan', 'kondisional', NULL, 'Bila kejang berulang', NULL, NULL, NULL,
        NULL, NULL, NULL, 1),
    ('peb_c_kalsium_glukonas', @peb, 'medikamentosa', 60,
        'Antidotum Toksisitas: Kalsium Glukonas 1 g (10 mL) IV pelan 10 menit.',
        'tindakan', 'kondisional', NULL, 'Bila RR < 12 x/m, refleks patella hilang, atau urin < 30 mL/jam', NULL, NULL, NULL,
        NULL, NULL, NULL, 1),
    ('peb_c_nifedipin_1', @peb, 'medikamentosa', 70,
        'Nifedipin immediate release 10 mg oral pemberian ke-1.',
        'tindakan', 'kondisional', NULL, 'TD ≥ 160/110', 'peb_nifedipin', 1, NULL,
        NULL, 'td_berat', 'terapi_nifedipine', 1),
    ('peb_c_nifedipin_2', @peb, 'medikamentosa', 80,
        'Nifedipin immediate release 10 mg oral pemberian ke-2.',
        'tindakan', 'kondisional', NULL, 'TD masih ≥ 160/110 setelah 30 menit dari pemberian ke-1', 'peb_nifedipin', 2, 30,
        NULL, 'td_berat', 'terapi_nifedipine', 1),
    ('peb_c_nifedipin_3', @peb, 'medikamentosa', 90,
        'Nifedipin immediate release 10 mg oral pemberian ke-3.',
        'tindakan', 'kondisional', NULL, 'TD masih ≥ 160/110 setelah 30 menit dari pemberian ke-2', 'peb_nifedipin', 3, 30,
        NULL, 'td_berat', 'terapi_nifedipine', 1),

    -- D. Monitoring & kateter urin.
    ('peb_d_kateter_urin', @peb, 'monitoring', 10,
        'Pasang Kateter Urin menetap (output urin > 30–35 mL/jam).',
        'tindakan', 'wajib', NULL, NULL, NULL, NULL, NULL,
        NULL, NULL, NULL, 1),
    ('peb_d_pantau_berkala', @peb, 'monitoring', 20,
        'Pantau RR, SpO2, TD tiap 30 menit, refleks patella, dan DJJ.',
        'tindakan', 'wajib', NULL, NULL, NULL, NULL, NULL,
        NULL, NULL, NULL, 1),

    -- E. Target waktu & eskalasi PPK I.
    ('peb_e_evaluasi_dokter', @peb, 'target_eskalasi', 10,
        'Algoritme Akut 0–10 Menit: Evaluasi Dokter Umum < 10 menit & mulai MgSO4 tanpa menunggu hasil lab bila eklampsia dicurigai.',
        'tindakan', 'wajib', NULL, NULL, NULL, NULL, NULL,
        10, NULL, NULL, 1),
    ('peb_e_rujuk_emergensi', @peb, 'target_eskalasi', 20,
        'Rujuk Emergensi Segera: Aktivasi Code Blue Maternal & rujuk via SBAR ke FKRTL (RS PONEK).',
        'tindakan', 'wajib', NULL, NULL, NULL, NULL, NULL,
        NULL, NULL, NULL, 1)
ON DUPLICATE KEY UPDATE
    `id_diagnosis` = VALUES(`id_diagnosis`),
    `komponen` = VALUES(`komponen`),
    `urutan` = VALUES(`urutan`),
    `teks_standar` = VALUES(`teks_standar`),
    `jenis` = VALUES(`jenis`),
    `sifat` = VALUES(`sifat`),
    `kelompok_alternatif` = VALUES(`kelompok_alternatif`),
    `syarat` = VALUES(`syarat`),
    `kelompok_dosis` = VALUES(`kelompok_dosis`),
    `dosis_ke` = VALUES(`dosis_ke`),
    `jeda_menit` = VALUES(`jeda_menit`),
    `target_menit` = VALUES(`target_menit`),
    `kode_aturan` = VALUES(`kode_aturan`),
    `kode_terapi_rm` = VALUES(`kode_terapi_rm`),
    `masuk_sbar` = VALUES(`masuk_sbar`),
    `id_active` = 1;
