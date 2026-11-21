<?php

defined('BASEPATH') or exit('No direct script access allowed');

/**
 * Akses data generik untuk CRUD master data (definisi di config/master.php).
 * Nama tabel dan kolom hanya berasal dari config, bukan dari input pengguna.
 */
class Master_data_model extends CI_Model
{
    public function listing(array $def, $search, $filter, $limit, $offset)
    {
        $this->kondisi($def, $search, $filter);

        foreach (explode(',', $def['urutan']) as $urutan) {
            $this->db->order_by(trim($urutan), '', false);
        }

        return $this->db
            ->get($def['tabel'], $limit, $offset)
            ->result_array();
    }

    public function count(array $def, $search, $filter)
    {
        $this->kondisi($def, $search, $filter);

        return $this->db->count_all_results($def['tabel']);
    }

    public function count_all($tabel)
    {
        return $this->db->count_all($tabel);
    }

    private function kondisi(array $def, $search, $filter)
    {
        if ($search !== '') {
            $this->db->group_start();
            foreach ($def['cari'] as $i => $kolom) {
                $i === 0 ? $this->db->like($kolom, $search) : $this->db->or_like($kolom, $search);
            }
            $this->db->group_end();
        }

        if ($filter !== '' && !empty($def['filter'])) {
            $this->db->where($def['filter'], $filter);
        }
    }

    public function find(array $def, $id)
    {
        return $this->db
            ->get_where($def['tabel'], [$def['pk'] => $id])
            ->row_array();
    }

    public function nilai_dipakai(array $def, $kolom, $nilai, $kecuali)
    {
        if ($kecuali !== null) {
            $this->db->where($def['pk'] . ' !=', $kecuali);
        }

        return $this->db
            ->where($kolom, $nilai)
            ->count_all_results($def['tabel']) > 0;
    }

    /**
     * Pilihan untuk kolom relasi: [id => label].
     */
    public function relasi_options(array $relasi)
    {
        list($tabel, $kolom_id, $kolom_label) = $relasi;

        if (isset($relasi[3])) {
            $this->db->where($relasi[3]);
        }

        $rows = $this->db
            ->select($kolom_id . ' AS id', false)
            ->select($kolom_label . ' AS label', false)
            ->order_by('label', 'ASC')
            ->get($tabel)
            ->result_array();

        return array_column($rows, 'label', 'id');
    }

    /**
     * Jumlah pemakaian data di tabel lain: [keterangan => jumlah], hanya yang > 0.
     */
    public function pemakaian(array $def, $id)
    {
        $hasil = [];

        foreach (isset($def['dipakai']) ? $def['dipakai'] : [] as $pakai) {
            list($tabel, $kolom, $keterangan) = $pakai;
            $jumlah = $this->db
                ->where($kolom, $id)
                ->count_all_results($tabel);

            if ($jumlah > 0) {
                $hasil[$keterangan] = $jumlah;
            }
        }

        return $hasil;
    }

    public function save(array $def, $id, array $data)
    {
        return $this->write(function () use ($def, $id, $data) {
            if ($id !== null) {
                return $this->db
                    ->where($def['pk'], $id)
                    ->update($def['tabel'], $data);
            }

            return $this->db->insert($def['tabel'], $data);
        });
    }

    public function delete(array $def, $id)
    {
        return $this->write(function () use ($def, $id) {
            return $this->db->delete($def['tabel'], [$def['pk'] => $id]);
        });
    }

    private function write($operation)
    {
        // Pelanggaran constraint database ditampilkan sebagai pesan umum, bukan detail SQL.
        $debug = $this->db->db_debug;
        $this->db->db_debug = false;

        try {
            return $operation();
        } catch (Throwable $exception) {
            log_message('error', 'Operasi master data gagal: ' . $exception->getMessage());

            return false;
        } finally {
            $this->db->db_debug = $debug;
        }
    }
}
