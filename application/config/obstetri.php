<?php

defined('BASEPATH') or exit('No direct script access allowed');

// Label setiap nilai enum t_riwayat_kehamilan. Kunci harus sama dengan nilai enum di database.
$config['riwayat_options'] = [
    'luaran' => [
        'lahir_hidup' => 'Lahir hidup',
        'lahir_mati' => 'Lahir mati',
        'abortus' => 'Abortus / keguguran',
        'kehamilan_ektopik' => 'Kehamilan ektopik',
        'mola_hidatidosa' => 'Mola hidatidosa'
    ],
    'jenis_kelamin' => [
        'L' => 'Laki-laki',
        'P' => 'Perempuan'
    ],
    'usia_kehamilan_lahir' => [
        'lt_22' => '< 22 mgg (Abortus)',
        '22_27' => '22–27 mgg (Imatur)',
        '28_36' => '28–36 mgg (Prematur)',
        '37_41' => '37–41 mgg (Aterm)',
        'ge_42' => '≥ 42 mgg (Postterm)'
    ],
    'metode_persalinan' => [
        'spontan_belakang_kepala' => 'Spontan belakang kepala',
        'spontan_sungsang' => 'Spontan sungsang',
        'vakum' => 'Vakum',
        'forsep' => 'Forsep',
        'sectio_caesarea' => 'Sectio caesarea (SC)',
        'kuretase' => 'Kuretase',
        'lainnya' => 'Lainnya'
    ],
    'tempat_persalinan' => [
        'rumah_sakit' => 'Rumah sakit',
        'puskesmas' => 'Puskesmas',
        'klinik' => 'Klinik',
        'praktik_bidan' => 'Praktik bidan',
        'rumah' => 'Rumah',
        'lainnya' => 'Lainnya'
    ],
    'penolong_persalinan' => [
        'dokter_spog' => 'Dokter SpOG',
        'dokter_umum' => 'Dokter umum',
        'bidan' => 'Bidan',
        'dukun' => 'Dukun',
        'tanpa_penolong' => 'Tanpa penolong'
    ],
    'berat_lahir_kategori' => [
        'lt_1500' => '< 1500 gr (BBLSR)',
        '1500_2499' => '1500–2499 gr (BBLR)',
        '2500_4000' => '2500–4000 gr (Normal)',
        'gt_4000' => '> 4000 gr (Makrosomia)'
    ],
    'kondisi_bayi' => [
        'hidup' => 'Hidup',
        'meninggal_perinatal' => 'Meninggal perinatal',
        'meninggal_setelah_perinatal' => 'Meninggal setelah perinatal'
    ]
];

// Luaran yang bukan persalinan sehingga data bayi tidak berlaku.
$config['luaran_tanpa_bayi'] = [
    'abortus',
    'kehamilan_ektopik',
    'mola_hidatidosa'
];

// Batas jarak kehamilan ideal (tahun) untuk penanda kewaspadaan.
$config['jarak_kehamilan_ideal'] = [
    'min' => 2,
    'max' => 10
];
