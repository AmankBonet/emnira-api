<?php

defined('BASEPATH') or exit('No direct script access allowed');

// Urutan kunci mengikuti enum m_stabilisasi_item.komponen.
$config['stabilisasi_komponen'] = [
    'red_flag' => 'Red flag / kriteria trigger',
    'oksigenasi_posisi' => 'A. Oksigenasi & posisi',
    'akses_iv_cairan' => 'B. Akses IV & resusitasi cairan',
    'medikamentosa' => 'C. Terapi medikamentosa spesifik',
    'monitoring' => 'D. Monitoring & kateter urin',
    'target_eskalasi' => 'E. Target waktu & eskalasi'
];

// Status yang boleh dipilih bergantung pada jenis dan sifat butir.
$config['stabilisasi_status'] = [
    'kriteria' => [
        'belum' => 'Belum dinilai',
        'terpenuhi' => 'Terpenuhi',
        'tidak_terpenuhi' => 'Tidak terpenuhi'
    ],
    'tindakan' => [
        'belum' => 'Belum',
        'dilakukan' => 'Sudah dilakukan',
        'dalam_proses' => 'Dalam proses',
        'tidak_dilakukan' => 'Tidak dilakukan'
    ],
    // Hanya untuk butir kondisional/alternatif yang syaratnya tidak terpenuhi.
    'tidak_diperlukan' => 'Tidak diperlukan'
];

// Status yang wajib disertai alasan.
$config['stabilisasi_status_beralasan'] = [
    'tidak_dilakukan',
    'tidak_diperlukan'
];
