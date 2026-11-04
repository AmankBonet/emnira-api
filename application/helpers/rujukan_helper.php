<?php

defined('BASEPATH') or exit('No direct script access allowed');

function normalize_rujukan_pilihan($pilihan)
{
    if (!is_array($pilihan))
    {
        return [];
    }

    $normalized = [];

    foreach ($pilihan as $item)
    {
        if (is_string($item))
        {
            $normalized[] = [
                'kode_pilihan' => $item,
                'catatan' => null
            ];
        }
        elseif (is_array($item))
        {
            $code = isset($item['kode'])
                ? $item['kode']
                : (isset($item['kode_pilihan']) ? $item['kode_pilihan'] : null);

            $normalized[] = [
                'kode_pilihan' => $code,
                'catatan' => isset($item['catatan']) ? $item['catatan'] : null
            ];
        }
    }

    return $normalized;
}

function validate_rujukan_input($input)
{
    $rules = json_decode(file_get_contents(APPPATH . 'config/rujukan_fields.json'), true);
    $errors = [];

    foreach (['id_kunjungan', 'id_faskes_tujuan'] as $field)
    {
        if (!isset($input[$field]))
        {
            $errors[$field] = 'Wajib diisi.';
        }
    }

    foreach ($input as $field => $value)
    {
        if ($field === 'pilihan')
        {
            if (!is_array($value) || array_values($value) !== $value)
            {
                $errors[$field] = 'Harus berupa array kode checkbox.';
                continue;
            }

            $seen = [];

            foreach ($value as $item)
            {
                $code = null;
                $catatan = null;

                if (is_string($item))
                {
                    $code = $item;
                }
                elseif (is_array($item))
                {
                    $allowedKeys = ['kode', 'kode_pilihan', 'catatan'];

                    if (array_diff(array_keys($item), $allowedKeys))
                    {
                        $errors[$field] = 'Objek pilihan hanya boleh memuat kode/kode_pilihan dan catatan.';
                        break;
                    }

                    $code = isset($item['kode'])
                        ? $item['kode']
                        : (isset($item['kode_pilihan']) ? $item['kode_pilihan'] : null);
                    $catatan = isset($item['catatan']) ? $item['catatan'] : null;
                }

                if (!is_string($code) || trim($code) === '' || strlen($code) > 60 || isset($seen[$code]))
                {
                    $errors[$field] = 'Kode harus berupa string unik maksimal 60 karakter.';
                    break;
                }

                if ($catatan !== null && (!is_string($catatan) || strlen($catatan) > 65535))
                {
                    $errors[$field] = 'Catatan pilihan harus berupa teks maksimal 65535 karakter.';
                    break;
                }

                $seen[$code] = true;
            }

            continue;
        }

        if (!isset($rules[$field]))
        {
            $errors[$field] = 'Kolom tidak dikenal atau dikelola server.';
            continue;
        }

        if ($value === null)
        {
            continue;
        }

        $type = $rules[$field];

        if (strpos($type, 'enum') === 0)
        {
            preg_match_all("/'([^']*)'/", $type, $matches);

            if (!is_string($value) || !in_array($value, $matches[1], true))
            {
                $errors[$field] = 'Pilihan tidak valid.';
            }
        }
        elseif (in_array($type, ['int', 'tinyint', 'smallint', 'bigint'], true))
        {
            $min = $field === 'bishop_station' ? -128 : 0;
            $max = $type === 'smallint' ? 65535 : 2147483647;

            if ($type === 'tinyint')
            {
                $max = $field === 'bishop_station' ? 127 : 1;
            }

            if ($field === 'bishop_penipisan_persen')
            {
                $max = 100;
            }

            if (!is_int($value) || $value < $min || $value > $max || (strpos($field, 'id_') === 0 && $value < 1))
            {
                $errors[$field] = 'Bilangan bulat di luar rentang yang diperbolehkan.';
            }
        }
        elseif (strpos($type, 'decimal') === 0)
        {
            preg_match('/decimal\((\d+),(\d+)\)/', $type, $matches);
            $max = pow(10, (int) $matches[1] - (int) $matches[2]);

            if ((!is_int($value) && !is_float($value)) || !is_finite((float) $value) || $value < 0 || $value >= $max)
            {
                $errors[$field] = 'Angka tidak valid atau melampaui kapasitas kolom.';
            }
            elseif (strpos($field, 'persen') !== false && $value > 100)
            {
                $errors[$field] = 'Persentase maksimal 100.';
            }
            elseif (in_array($field, ['berat_badan_kg', 'durasi_pengamatan_urin_jam'], true) && $value <= 0)
            {
                $errors[$field] = 'Harus lebih besar dari nol.';
            }
            elseif (abs($value - round($value, (int) $matches[2])) > 0.0000001)
            {
                $errors[$field] = 'Jumlah angka desimal melebihi kapasitas kolom.';
            }
        }
        elseif ($type === 'datetime')
        {
            $date = is_string($value) ? DateTime::createFromFormat('!Y-m-d H:i:s', $value) : false;

            if (!$date || $date->format('Y-m-d H:i:s') !== $value || $date->format('Y') < '1000')
            {
                $errors[$field] = 'Gunakan format Y-m-d H:i:s yang valid.';
            }
        }
        else
        {
            $max = $type === 'text' ? 65535 : (int) filter_var($type, FILTER_SANITIZE_NUMBER_INT);

            if (!is_string($value) || strlen($value) > $max)
            {
                $errors[$field] = 'Teks terlalu panjang atau bukan string.';
            }
        }
    }

    $normalizedPilihan = isset($input['pilihan']) ? normalize_rujukan_pilihan($input['pilihan']) : [];
    $codes = array_column($normalizedPilihan, 'kode_pilihan');

    if (in_array('riwayat_tidak_ada', $codes, true))
    {
        foreach ($codes as $code)
        {
            if (is_string($code) && strpos($code, 'riwayat_') === 0 && $code !== 'riwayat_tidak_ada')
            {
                $errors['pilihan'] = 'Tidak ada riwayat tidak boleh digabung dengan riwayat penyakit.';
            }
        }
    }

    if (isset($input['perdarahan_status']) && $input['perdarahan_status'] === 'tidak_ada')
    {
        if (isset($input['perdarahan_volume']) || isset($input['perdarahan_karakteristik']))
        {
            $errors['perdarahan_status'] = 'Tanpa perdarahan tidak boleh disertai volume atau karakteristik.';
        }
    }

    foreach (['tanda_bahaya' => 'skrining_bahaya_sudah_dinilai', 'riwayat_' => 'riwayat_medis_sudah_dinilai', 'terapi_' => 'terapi_sudah_dinilai'] as $prefix => $flag)
    {
        $prefix = $prefix === 'tanda_bahaya' ? 'bahaya_' : $prefix;

        foreach ($codes as $code)
        {
            if (is_string($code) && strpos($code, $prefix) === 0 && (!isset($input[$flag]) || $input[$flag] !== 1))
            {
                $errors[$flag] = 'Harus bernilai 1 jika checkbox terkait dipilih.';
            }
        }
    }

    return $errors;
}
