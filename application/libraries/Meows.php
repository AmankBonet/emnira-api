<?php

defined('BASEPATH') or exit('No direct script access allowed');

/**
 * Penghitung skor MEOWS berdasarkan application/config/meows.php.
 * Logika yang sama dijalankan di assets/web/rm-detail.js untuk tampilan realtime;
 * nilai yang disimpan selalu berasal dari kelas ini.
 */
class Meows
{
    private $rules;

    public function __construct()
    {
        $ci = &get_instance();
        $ci->config->load('meows');
        $this->rules = $ci->config->item('meows');
    }

    public function rules()
    {
        return $this->rules;
    }

    public function versi()
    {
        return $this->rules['versi'];
    }

    /**
     * Menilai satu parameter. Mengembalikan null bila nilai kosong atau tidak masuk rentang.
     */
    public function nilai_parameter($field, $value)
    {
        if ($value === null || $value === '' || !isset($this->rules['parameter'][$field])) {
            return null;
        }

        $parameter = $this->rules['parameter'][$field];

        if ($parameter['tipe'] === 'pilihan') {
            return isset($parameter['pilihan'][$value])
                ? [
                    'zona' => $parameter['pilihan'][$value]['zona'],
                    'skor' => $parameter['pilihan'][$value]['skor']
                ]
                : null;
        }

        $value = (float) $value;
        foreach ($parameter['rentang'] as $rentang) {
            if (
                ($rentang['min'] === null || $value >= $rentang['min'])
                && ($rentang['max'] === null || $value <= $rentang['max'])
            ) {
                return [
                    'zona' => $rentang['zona'],
                    'skor' => $rentang['skor']
                ];
            }
        }

        return null;
    }

    /**
     * Menghitung skor total dan zona dari seluruh parameter yang terisi.
     */
    public function hitung(array $data)
    {
        $hasil = [
            'skor' => 0,
            'jumlah_merah' => 0,
            'jumlah_kuning' => 0,
            'terisi' => 0,
            'parameter' => []
        ];

        foreach (array_keys($this->rules['parameter']) as $field) {
            $nilai = $this->nilai_parameter($field, isset($data[$field]) ? $data[$field] : null);
            $hasil['parameter'][$field] = $nilai;

            if ($nilai === null) {
                continue;
            }

            $hasil['terisi']++;
            $hasil['skor'] += $nilai['skor'];

            if ($nilai['zona'] === 'merah') {
                $hasil['jumlah_merah']++;
            } elseif ($nilai['zona'] === 'kuning') {
                $hasil['jumlah_kuning']++;
            }
        }

        $hasil['zona'] = $this->zona($hasil['jumlah_merah'], $hasil['jumlah_kuning']);

        return $hasil;
    }

    /**
     * Penanda kewaspadaan dari observasi terakhir (dipakai rekam medis dan ringkasan rujukan).
     */
    public function flags($observasi)
    {
        if (!$observasi || $observasi['meows_zona'] === null || $observasi['meows_zona'] === 'normal') {
            return [];
        }

        $waktu = DateTime::createFromFormat('Y-m-d H:i:s', $observasi['waktu_observasi']);
        $text = 'MEOWS terakhir ' . (int) $observasi['meows_skor']
            . ' (zona ' . $observasi['meows_zona'] . ')'
            . ($waktu ? ' pada ' . $waktu->format('d/m/Y H:i') : '');

        if ($observasi['meows_zona'] === 'merah') {
            $text .= ' — segera evaluasi dan laporkan ke dokter.';
        }

        return [[
            'level' => $observasi['meows_zona'] === 'merah' ? 'bahaya' : 'risiko',
            'text' => $text
        ]];
    }

    public function zona($jumlah_merah, $jumlah_kuning)
    {
        $pemicu = $this->rules['pemicu'];

        if (
            $jumlah_merah >= $pemicu['merah_min_merah']
            || $jumlah_kuning >= $pemicu['merah_min_kuning']
        ) {
            return 'merah';
        }

        return $jumlah_kuning > 0 ? 'kuning' : 'normal';
    }

    /**
     * Teks ambang batas per parameter, dibentuk dari rentang agar selalu sama dengan aturan.
     */
    public function ambang_teks($field)
    {
        $parameter = $this->rules['parameter'][$field];
        $nama_zona = [
            'normal' => 'Normal',
            'kuning' => 'Kuning',
            'merah' => 'Merah'
        ];
        $per_zona = [];

        if ($parameter['tipe'] === 'pilihan') {
            foreach ($parameter['pilihan'] as $pilihan) {
                $per_zona[$pilihan['zona']][] = $pilihan['label'];
            }
        } else {
            foreach ($parameter['rentang'] as $rentang) {
                $per_zona[$rentang['zona']][] = $this->format_rentang($rentang);
            }
        }

        $parts = [];
        foreach ($nama_zona as $zona => $nama) {
            if (isset($per_zona[$zona])) {
                $parts[] = $nama . ': ' . implode(' / ', $per_zona[$zona]);
            }
        }

        return implode(' | ', $parts);
    }

    private function format_rentang(array $rentang)
    {
        if ($rentang['min'] === null) {
            return '≤ ' . $this->format_angka($rentang['max']);
        }

        if ($rentang['max'] === null) {
            return '≥ ' . $this->format_angka($rentang['min']);
        }

        if ($rentang['min'] == $rentang['max']) {
            return $this->format_angka($rentang['min']);
        }

        return $this->format_angka($rentang['min']) . '–' . $this->format_angka($rentang['max']);
    }

    private function format_angka($value)
    {
        $value = (float) $value;

        // Bilangan bulat ditampilkan tanpa desimal; selain itu satu desimal dengan koma.
        if ($value == floor($value)) {
            return (string) (int) $value;
        }

        return number_format($value, 1, ',', '');
    }
}
