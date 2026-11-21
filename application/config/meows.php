<?php

defined('BASEPATH') or exit('No direct script access allowed');

/*
 * Aturan MEOWS (Modified Early Obstetric Warning Score).
 * Satu-satunya sumber ambang batas: dipakai server saat menyimpan dan dikirim ke browser
 * untuk perhitungan realtime. Naikkan 'versi' setiap kali ambang batas diubah.
 *
 * PERLU VALIDASI KLINIS — spesifikasi terpotong pada beberapa ambang. Nilai bertanda
 * [ASUMSI] diisi dari praktik MEOWS umum dan wajib dikonfirmasi tim klinis:
 *   - RR merah atas >= 25
 *   - Suhu kuning 35.0–35.9 dan 37.5–37.9
 *   - TDS kuning 80–99 dan 140–159
 *   - TDD < 90 dianggap normal (spesifikasi menulis normal 90–99)
 *   - Nadi kuning 50–59, merah >= 130
 *   - Perdarahan "masif" = merah
 *   - Pemicu zona: 1 parameter merah atau >= 2 parameter kuning
 *
 * Rentang min/max bersifat inklusif; null berarti tidak dibatasi.
 */
$config['meows'] = [
    'versi' => 'meows-2026.11-draft',

    'parameter' => [
        'rr_per_menit' => [
            'label' => 'Laju respirasi (RR)',
            'satuan' => 'x/menit',
            'tipe' => 'angka',
            'min' => 0,
            'max' => 80,
            'step' => 1,
            'wajib' => true,
            'rentang' => [
                ['min' => null, 'max' => 11, 'zona' => 'merah', 'skor' => 3],
                ['min' => 12, 'max' => 20, 'zona' => 'normal', 'skor' => 0],
                ['min' => 21, 'max' => 24, 'zona' => 'kuning', 'skor' => 1],
                ['min' => 25, 'max' => null, 'zona' => 'merah', 'skor' => 3]
            ]
        ],
        'spo2_persen' => [
            'label' => 'Saturasi oksigen (SpO2)',
            'satuan' => '%',
            'tipe' => 'angka',
            'min' => 0,
            'max' => 100,
            'step' => 1,
            'wajib' => true,
            'rentang' => [
                ['min' => null, 'max' => 91, 'zona' => 'merah', 'skor' => 3],
                ['min' => 92, 'max' => 96, 'zona' => 'kuning', 'skor' => 1],
                ['min' => 97, 'max' => null, 'zona' => 'normal', 'skor' => 0]
            ]
        ],
        'suhu_celsius' => [
            'label' => 'Suhu tubuh',
            'satuan' => '°C',
            'tipe' => 'angka',
            'min' => 25,
            'max' => 45,
            'step' => 0.1,
            'wajib' => true,
            'rentang' => [
                ['min' => null, 'max' => 34.9, 'zona' => 'merah', 'skor' => 3],
                ['min' => 35.0, 'max' => 35.9, 'zona' => 'kuning', 'skor' => 1],
                ['min' => 36.0, 'max' => 37.4, 'zona' => 'normal', 'skor' => 0],
                ['min' => 37.5, 'max' => 37.9, 'zona' => 'kuning', 'skor' => 1],
                ['min' => 38.0, 'max' => null, 'zona' => 'merah', 'skor' => 3]
            ]
        ],
        'td_sistolik_mmhg' => [
            'label' => 'Tekanan darah sistolik (TDS)',
            'satuan' => 'mmHg',
            'tipe' => 'angka',
            'min' => 30,
            'max' => 300,
            'step' => 1,
            'wajib' => true,
            'rentang' => [
                ['min' => null, 'max' => 79, 'zona' => 'merah', 'skor' => 3],
                ['min' => 80, 'max' => 99, 'zona' => 'kuning', 'skor' => 1],
                ['min' => 100, 'max' => 139, 'zona' => 'normal', 'skor' => 0],
                ['min' => 140, 'max' => 159, 'zona' => 'kuning', 'skor' => 1],
                ['min' => 160, 'max' => null, 'zona' => 'merah', 'skor' => 3]
            ]
        ],
        'td_diastolik_mmhg' => [
            'label' => 'Tekanan darah diastolik (TDD)',
            'satuan' => 'mmHg',
            'tipe' => 'angka',
            'min' => 10,
            'max' => 200,
            'step' => 1,
            'wajib' => true,
            'rentang' => [
                ['min' => null, 'max' => 99, 'zona' => 'normal', 'skor' => 0],
                ['min' => 100, 'max' => 109, 'zona' => 'kuning', 'skor' => 1],
                ['min' => 110, 'max' => null, 'zona' => 'merah', 'skor' => 3]
            ]
        ],
        'nadi_per_menit' => [
            'label' => 'Frekuensi nadi (HR)',
            'satuan' => 'bpm',
            'tipe' => 'angka',
            'min' => 0,
            'max' => 300,
            'step' => 1,
            'wajib' => true,
            'rentang' => [
                ['min' => null, 'max' => 49, 'zona' => 'merah', 'skor' => 3],
                ['min' => 50, 'max' => 59, 'zona' => 'kuning', 'skor' => 1],
                ['min' => 60, 'max' => 99, 'zona' => 'normal', 'skor' => 0],
                ['min' => 100, 'max' => 129, 'zona' => 'kuning', 'skor' => 1],
                ['min' => 130, 'max' => null, 'zona' => 'merah', 'skor' => 3]
            ]
        ],
        'avpu' => [
            'label' => 'Tingkat kesadaran (AVPU)',
            'satuan' => 'AVPU',
            'tipe' => 'pilihan',
            'wajib' => true,
            'pilihan' => [
                'A' => ['label' => 'Alert (A)', 'zona' => 'normal', 'skor' => 0],
                'V' => ['label' => 'Verbal (V)', 'zona' => 'merah', 'skor' => 3],
                'P' => ['label' => 'Pain (P)', 'zona' => 'merah', 'skor' => 3],
                'U' => ['label' => 'Unresponsive (U)', 'zona' => 'merah', 'skor' => 3]
            ]
        ],
        'urin_ml_jam' => [
            'label' => 'Produksi urin',
            'satuan' => 'mL/jam',
            'tipe' => 'angka',
            'min' => 0,
            'max' => 2000,
            'step' => 0.1,
            'wajib' => false,
            'rentang' => [
                ['min' => null, 'max' => 29.9, 'zona' => 'merah', 'skor' => 3],
                ['min' => 30, 'max' => 35, 'zona' => 'kuning', 'skor' => 1],
                ['min' => 35.1, 'max' => null, 'zona' => 'normal', 'skor' => 0]
            ]
        ],
        'perdarahan' => [
            'label' => 'Lochia / perdarahan pervaginam',
            'satuan' => 'Kategori',
            'tipe' => 'pilihan',
            'wajib' => false,
            'pilihan' => [
                'minimal' => ['label' => 'Normal / minimal', 'zona' => 'normal', 'skor' => 0],
                'sedang' => ['label' => 'Sedang', 'zona' => 'kuning', 'skor' => 1],
                'banyak' => ['label' => 'Banyak', 'zona' => 'kuning', 'skor' => 1],
                'masif' => ['label' => 'Masif / bergumpal', 'zona' => 'merah', 'skor' => 3]
            ]
        ],
        'proteinuria' => [
            'label' => 'Protein urin (dipstick)',
            'satuan' => 'Dipstick',
            'tipe' => 'pilihan',
            'wajib' => false,
            'pilihan' => [
                'negatif' => ['label' => 'Negatif', 'zona' => 'normal', 'skor' => 0],
                'trace' => ['label' => 'Trace', 'zona' => 'normal', 'skor' => 0],
                'plus_1' => ['label' => '+1', 'zona' => 'kuning', 'skor' => 1],
                'plus_2' => ['label' => '+2', 'zona' => 'merah', 'skor' => 3],
                'plus_3' => ['label' => '+3', 'zona' => 'merah', 'skor' => 3],
                'plus_4' => ['label' => '+4', 'zona' => 'merah', 'skor' => 3]
            ]
        ],
        'nyeri_nrs' => [
            'label' => 'Tingkat nyeri (NRS)',
            'satuan' => 'Skor 0–10',
            'satuan_tampil' => '/10',
            'tipe' => 'angka',
            'min' => 0,
            'max' => 10,
            'step' => 1,
            'wajib' => false,
            'rentang' => [
                ['min' => 0, 'max' => 0, 'zona' => 'normal', 'skor' => 0],
                ['min' => 1, 'max' => 7, 'zona' => 'kuning', 'skor' => 2],
                ['min' => 8, 'max' => null, 'zona' => 'merah', 'skor' => 3]
            ]
        ]
    ],

    // Pemicu zona total: zona merah bila ada >= 1 parameter merah atau >= 2 parameter kuning.
    'pemicu' => [
        'merah_min_merah' => 1,
        'merah_min_kuning' => 2
    ],

    // Ambang nilai turunan untuk interpretasi tampilan.
    'turunan' => [
        'map_normal' => [70, 100],
        'djj_normal' => [120, 160],
        'shock_index_waspada' => 0.9,
        'urin_oliguria_ml_kg_jam' => 0.5,
        // [ASUMSI] Rumus spesifikasi (99 - SpO2) / 4 dengan aliran minimal 2 L/menit bila SpO2 < 97.
        'o2_spo2_target' => 99,
        'o2_pembagi' => 4,
        'o2_min_lpm' => 2,
        'o2_mulai_bila_spo2_kurang_dari' => 97,
        'cairan_ml_kg' => [20, 30]
    ]
];

// Pilihan data obstetri pendukung; nilai harus sama dengan enum t_kunjungan_observasi.
$config['observasi_pilihan'] = [
    'vt_pembukaan' => [
        'belum' => 'Belum ada pembukaan',
        '1_3' => '1–3 cm (Fase laten)',
        '4_7' => '4–7 cm (Fase aktif)',
        '8_9' => '8–9 cm (Fase aktif lanjut)',
        'lengkap' => '10 cm (Lengkap)'
    ],
    'ketuban' => [
        'utuh' => 'Utuh (+)',
        'pecah_jernih' => 'Pecah, jernih',
        'pecah_keruh_meconium' => 'Pecah, keruh / mekonium',
        'pecah_berbau' => 'Pecah, berbau'
    ],
    'kepala_masuk_panggul' => [
        '1' => 'Ya (sudah masuk panggul)',
        '0' => 'Belum masuk panggul'
    ]
];
