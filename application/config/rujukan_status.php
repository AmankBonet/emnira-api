<?php

defined('BASEPATH') or exit('No direct script access allowed');

// Label dan warna badge status rujukan; kunci sama dengan enum t_kunjungan.status_rujukan.
$config['status_rujukan'] = [
    'draf' => [
        'label' => 'Draf',
        'badge' => 'text-bg-light border'
    ],
    'dikirim' => [
        'label' => 'Dikirim',
        'badge' => 'text-bg-warning'
    ],
    'diterima' => [
        'label' => 'Diterima',
        'badge' => 'text-bg-info'
    ],
    'tiba' => [
        'label' => 'Tiba',
        'badge' => 'text-bg-success'
    ]
];

// Target waktu rujukan tiba di FKRTL sejak dikirim (dokumen STABILISASI: < 2 jam).
$config['target_tiba_menit'] = 120;
