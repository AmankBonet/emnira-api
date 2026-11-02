<?php

define('BASEPATH', __DIR__);
define('APPPATH', dirname(__DIR__) . '/application/');
require APPPATH . 'helpers/rujukan_helper.php';

function expect($condition, $label)
{
    if (!$condition) {
        throw new RuntimeException($label);
    }
}

$base = ['id_kunjungan' => 1, 'id_faskes_tujuan' => 2];
expect(validate_rujukan_input($base) === [], 'Input minimal valid');
expect(isset(validate_rujukan_input([])['id_kunjungan']), 'ID wajib');
expect(isset(validate_rujukan_input($base + ['id_user_pembuat' => 99])['id_user_pembuat']), 'Tolak identitas palsu');
expect(isset(validate_rujukan_input($base + ['urin_output_ml_kg_jam' => 5])['urin_output_ml_kg_jam']), 'Tolak generated column');
expect(isset(validate_rujukan_input($base + ['spo2_persen' => 101])['spo2_persen']), 'Persentase');
expect(isset(validate_rujukan_input($base + ['berat_badan_kg' => 0])['berat_badan_kg']), 'Berat nol');
expect(isset(validate_rujukan_input($base + ['waktu_rujukan' => '2026-02-30 10:00:00'])['waktu_rujukan']), 'Tanggal tidak valid');
expect(isset(validate_rujukan_input($base + ['ketuban' => 'salah'])['ketuban']), 'Enum');
expect(isset(validate_rujukan_input($base + ['pilihan' => ['riwayat_tidak_ada', 'riwayat_dm']])['pilihan']), 'Riwayat kontradiktif');
expect(isset(validate_rujukan_input($base + ['pilihan' => ['bahaya_kejang', 'bahaya_kejang']])['pilihan']), 'Checkbox duplikat');
expect(isset(validate_rujukan_input($base + ['perdarahan_status' => 'tidak_ada', 'perdarahan_volume' => 'lt_500'])['perdarahan_status']), 'Perdarahan kontradiktif');
expect(validate_rujukan_input($base + ['skrining_bahaya_sudah_dinilai' => 1, 'pilihan' => ['bahaya_kejang']]) === [], 'Checkbox valid');
echo "12 pemeriksaan validasi berhasil.\n";
