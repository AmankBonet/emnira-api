<?php

defined('BASEPATH') or exit('No direct script access allowed');

/*
 * Definisi CRUD master data untuk administrator.
 *
 * Kunci tabel dipakai di URL: web/master/<kunci>.
 * Tipe kolom:
 *   teks, teks_panjang  : input teks; 'maks' = panjang maksimal
 *   angka               : bilangan bulat; 'min' / 'max'
 *   pilihan             : nilai enum; 'pilihan' => [nilai => label]
 *   relasi              : pilihan dari tabel lain; 'relasi' => [tabel, kolom id, kolom label]
 *   ya_tidak            : checkbox 1/0
 *
 * 'pk_manual' => true berarti kunci diketik admin (contoh kode) dan tidak dapat diubah setelah dibuat,
 * karena dipakai sebagai referensi di tabel transaksi.
 * 'dipakai' => [[tabel, kolom, keterangan]] dicek sebelum menghapus.
 * 'terkunci' => daftar id yang tidak boleh dihapus karena dipakai oleh kode aplikasi.
 */
$config['master'] = [
    'faskes' => [
        'judul' => 'Fasilitas kesehatan',
        'keterangan' => 'Puskesmas (FKTP) dan rumah sakit (FKRTL) yang terhubung dalam rujukan.',
        'tabel' => 'm_faskes',
        'pk' => 'id_faskes',
        'urutan' => 'nama_faskes ASC',
        'cari' => ['nama_faskes'],
        'kolom' => [
            'nama_faskes' => ['label' => 'Nama faskes', 'tipe' => 'teks', 'maks' => 255, 'wajib' => true, 'tampil' => true, 'lebar' => 8],
            'kategori' => ['label' => 'Kategori', 'tipe' => 'pilihan', 'wajib' => true, 'tampil' => true, 'lebar' => 4, 'pilihan' => [
                'FKTP' => 'FKTP (puskesmas / klinik)',
                'FKRTL' => 'FKRTL (rumah sakit)'
            ]]
        ],
        'dipakai' => [
            ['m_user', 'id_faskes', 'pengguna'],
            ['t_kunjungan', 'id_faskes_perujuk', 'kunjungan (perujuk)'],
            ['t_kunjungan', 'id_faskes_tujuan', 'kunjungan (tujuan)'],
            ['t_rm', 'id_faskes_asal', 'rujukan API (asal)'],
            ['t_rm', 'id_faskes_tujuan', 'rujukan API (tujuan)']
        ]
    ],

    'role' => [
        'judul' => 'Role pengguna',
        'keterangan' => 'Role 1 (FKTP), 2 (FKRTL), dan 3 (Administrator) dipakai aplikasi dan tidak dapat dihapus.',
        'tabel' => 'm_role',
        'pk' => 'id_role',
        'urutan' => 'id_role ASC',
        'cari' => ['nama_role'],
        'kolom' => [
            'nama_role' => ['label' => 'Nama role', 'tipe' => 'teks', 'maks' => 255, 'wajib' => true, 'tampil' => true, 'lebar' => 12]
        ],
        'terkunci' => [1, 2, 3],
        'dipakai' => [
            ['m_user', 'id_role', 'pengguna']
        ]
    ],

    'diagnosis' => [
        'judul' => 'Diagnosa PONEK',
        'keterangan' => 'Diagnosa kerja yang menentukan checklist stabilisasi pra-rujukan.',
        'tabel' => 'm_diagnosis_ponek',
        'pk' => 'id_diagnosis',
        'urutan' => 'kode ASC',
        'cari' => ['kode', 'nama'],
        'unik' => ['kode'],
        'kolom' => [
            'kode' => ['label' => 'Kode ICD-10', 'tipe' => 'teks', 'maks' => 30, 'wajib' => true, 'tampil' => true, 'lebar' => 4],
            'nama' => ['label' => 'Nama diagnosa', 'tipe' => 'teks', 'maks' => 255, 'wajib' => true, 'tampil' => true, 'lebar' => 8],
            'id_active' => ['label' => 'Aktif', 'tipe' => 'ya_tidak', 'tampil' => true, 'bawaan' => 1, 'lebar' => 12]
        ],
        'dipakai' => [
            ['m_stabilisasi_item', 'id_diagnosis', 'butir protokol stabilisasi'],
            ['t_kunjungan_diagnosis', 'id_diagnosis', 'diagnosa kerja kunjungan'],
            ['t_rm', 'id_diagnosis', 'rujukan API']
        ]
    ],

    'stabilisasi' => [
        'judul' => 'Protokol stabilisasi',
        'keterangan' => 'Butir checklist stabilisasi pra-rujukan per diagnosa (satu baris = satu tanda □ di protokol).',
        'tabel' => 'm_stabilisasi_item',
        'pk' => 'id_item',
        'urutan' => 'id_diagnosis ASC, komponen ASC, urutan ASC',
        'cari' => ['kode', 'teks_standar'],
        'filter' => 'id_diagnosis',
        'unik' => ['kode'],
        'kolom' => [
            'id_diagnosis' => ['label' => 'Diagnosa', 'tipe' => 'relasi', 'wajib' => true, 'tampil' => true, 'lebar' => 6, 'relasi' => ['m_diagnosis_ponek', 'id_diagnosis', "CONCAT(kode, ' ', nama)"]],
            'kode' => ['label' => 'Kode butir', 'tipe' => 'teks', 'maks' => 60, 'wajib' => true, 'lebar' => 6, 'bantuan' => 'Unik, contoh: peb_c_mgso4_loading_iv'],
            'komponen' => ['label' => 'Komponen', 'tipe' => 'pilihan', 'wajib' => true, 'tampil' => true, 'lebar' => 6, 'pilihan' => [
                'red_flag' => 'Red flag / kriteria',
                'oksigenasi_posisi' => 'A. Oksigenasi & posisi',
                'akses_iv_cairan' => 'B. Akses IV & cairan',
                'medikamentosa' => 'C. Medikamentosa',
                'monitoring' => 'D. Monitoring',
                'target_eskalasi' => 'E. Target & eskalasi'
            ]],
            'urutan' => ['label' => 'Urutan', 'tipe' => 'angka', 'min' => 0, 'max' => 65535, 'bawaan' => 0, 'lebar' => 3],
            'jenis' => ['label' => 'Jenis', 'tipe' => 'pilihan', 'wajib' => true, 'lebar' => 3, 'bawaan' => 'tindakan', 'pilihan' => [
                'tindakan' => 'Tindakan',
                'kriteria' => 'Kriteria'
            ]],
            'teks_standar' => ['label' => 'Teks standar protokol', 'tipe' => 'teks_panjang', 'maks' => 2000, 'wajib' => true, 'tampil' => true, 'lebar' => 12],
            'sifat' => ['label' => 'Sifat', 'tipe' => 'pilihan', 'wajib' => true, 'tampil' => true, 'lebar' => 4, 'bawaan' => 'wajib', 'pilihan' => [
                'wajib' => 'Wajib',
                'kondisional' => 'Kondisional',
                'alternatif' => 'Alternatif (pilih salah satu)'
            ]],
            'kelompok_alternatif' => ['label' => 'Kelompok alternatif', 'tipe' => 'teks', 'maks' => 40, 'lebar' => 4, 'wajib_jika' => ['sifat', 'alternatif'], 'bantuan' => 'Wajib bila sifat alternatif'],
            'syarat' => ['label' => 'Syarat', 'tipe' => 'teks', 'maks' => 255, 'lebar' => 4],
            'kelompok_dosis' => ['label' => 'Kelompok dosis', 'tipe' => 'teks', 'maks' => 40, 'lebar' => 3, 'pasangan' => 'dosis_ke'],
            'dosis_ke' => ['label' => 'Dosis ke-', 'tipe' => 'angka', 'min' => 1, 'max' => 20, 'lebar' => 3, 'pasangan' => 'kelompok_dosis'],
            'jeda_menit' => ['label' => 'Jeda (menit)', 'tipe' => 'angka', 'min' => 0, 'max' => 1440, 'lebar' => 3],
            'target_menit' => ['label' => 'Target (menit)', 'tipe' => 'angka', 'min' => 0, 'max' => 1440, 'lebar' => 3],
            'kode_aturan' => ['label' => 'Penilaian otomatis', 'tipe' => 'pilihan', 'lebar' => 6, 'pilihan' => [
                'td_berat' => 'TD ≥ 160/110 dari observasi',
                'proteinuria_plus2' => 'Proteinuria ≥ +2 dari observasi'
            ]],
            'kode_terapi_rm' => ['label' => 'Terapi formulir rujukan', 'tipe' => 'relasi', 'lebar' => 6, 'relasi' => ['m_rm_pilihan', 'kode', 'label', ['kelompok' => 'terapi']]],
            'masuk_sbar' => ['label' => 'Masuk SBAR', 'tipe' => 'ya_tidak', 'bawaan' => 1, 'lebar' => 6],
            'id_active' => ['label' => 'Aktif', 'tipe' => 'ya_tidak', 'tampil' => true, 'bawaan' => 1, 'lebar' => 6]
        ],
        'dipakai' => [
            ['t_kunjungan_stabilisasi', 'id_item', 'checklist kunjungan']
        ]
    ],

    'obstetri_pilihan' => [
        'judul' => 'Pilihan obstetri',
        'keterangan' => 'Penyakit saat hamil, komplikasi persalinan/nifas, dan checklist kondisi medis ibu.',
        'tabel' => 'm_obstetri_pilihan',
        'pk' => 'kode',
        'pk_manual' => true,
        'urutan' => 'kelompok ASC, urutan ASC, label ASC',
        'cari' => ['kode', 'label', 'kode_icd10'],
        'filter' => 'kelompok',
        'kolom' => [
            'kode' => ['label' => 'Kode', 'tipe' => 'teks', 'maks' => 60, 'wajib' => true, 'tampil' => true, 'lebar' => 6, 'bantuan' => 'Huruf kecil, angka, dan garis bawah; tidak dapat diubah setelah dibuat'],
            'kelompok' => ['label' => 'Kelompok', 'tipe' => 'pilihan', 'wajib' => true, 'tampil' => true, 'lebar' => 6, 'pilihan' => [
                'penyakit_hamil' => 'Penyakit saat hamil',
                'komplikasi_persalinan_nifas' => 'Komplikasi persalinan & nifas',
                'kondisi_ibu' => 'Kondisi medis ibu'
            ]],
            'label' => ['label' => 'Label', 'tipe' => 'teks', 'maks' => 255, 'wajib' => true, 'tampil' => true, 'lebar' => 8],
            'kode_icd10' => ['label' => 'Kode ICD-10', 'tipe' => 'teks', 'maks' => 50, 'tampil' => true, 'lebar' => 4],
            'kategori_klinis' => ['label' => 'Kategori klinis', 'tipe' => 'teks', 'maks' => 100, 'lebar' => 6, 'bantuan' => 'Pengelompokan tampilan, contoh: Penyakit Kronis & Sistemik'],
            'tingkat_risiko' => ['label' => 'Tingkat risiko', 'tipe' => 'pilihan', 'wajib' => true, 'tampil' => true, 'lebar' => 3, 'bawaan' => 'risiko', 'pilihan' => [
                'normal' => 'Normal',
                'risiko' => 'Risiko',
                'bahaya' => 'Bahaya'
            ]],
            'urutan' => ['label' => 'Urutan', 'tipe' => 'angka', 'min' => 0, 'max' => 65535, 'bawaan' => 0, 'lebar' => 3],
            'dampak_risiko' => ['label' => 'Dampak & risiko', 'tipe' => 'teks_panjang', 'maks' => 2000, 'lebar' => 12],
            'rekomendasi' => ['label' => 'Tindakan / rekomendasi', 'tipe' => 'teks_panjang', 'maks' => 2000, 'lebar' => 12],
            'wajib_catatan' => ['label' => 'Catatan wajib diisi', 'tipe' => 'ya_tidak', 'bawaan' => 0, 'lebar' => 6],
            'id_active' => ['label' => 'Aktif', 'tipe' => 'ya_tidak', 'tampil' => true, 'bawaan' => 1, 'lebar' => 6]
        ],
        'dipakai' => [
            ['t_riwayat_kehamilan_kondisi', 'kode_pilihan', 'riwayat kehamilan'],
            ['t_kunjungan_kondisi_ibu', 'kode_pilihan', 'kondisi ibu kunjungan']
        ]
    ],

    'rm_pilihan' => [
        'judul' => 'Pilihan formulir rujukan',
        'keterangan' => 'Pilihan checkbox formulir rujukan API (tanda bahaya, riwayat medis, terapi).',
        'tabel' => 'm_rm_pilihan',
        'pk' => 'kode',
        'pk_manual' => true,
        'urutan' => 'kelompok ASC, label ASC',
        'cari' => ['kode', 'label'],
        'filter' => 'kelompok',
        'kolom' => [
            'kode' => ['label' => 'Kode', 'tipe' => 'teks', 'maks' => 60, 'wajib' => true, 'tampil' => true, 'lebar' => 6, 'bantuan' => 'Huruf kecil, angka, dan garis bawah; tidak dapat diubah setelah dibuat'],
            'kelompok' => ['label' => 'Kelompok', 'tipe' => 'pilihan', 'wajib' => true, 'tampil' => true, 'lebar' => 6, 'pilihan' => [
                'tanda_bahaya' => 'Tanda bahaya',
                'riwayat_medis' => 'Riwayat medis',
                'terapi' => 'Terapi'
            ]],
            'label' => ['label' => 'Label', 'tipe' => 'teks', 'maks' => 255, 'wajib' => true, 'tampil' => true, 'lebar' => 12]
        ],
        'dipakai' => [
            ['t_rm_pilihan', 'kode_pilihan', 'formulir rujukan'],
            ['m_stabilisasi_item', 'kode_terapi_rm', 'butir protokol stabilisasi']
        ]
    ]
];
