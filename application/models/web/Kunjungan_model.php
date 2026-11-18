<?php

defined('BASEPATH') or exit('No direct script access allowed');

class Kunjungan_model extends CI_Model
{
    private $columns = [
        'k.id_kunjungan',
        'k.no_rm',
        'k.nama',
        'k.alamat',
        'k.tgl_lahir',
        'k.tgl_reg',
        'k.lokasi',
        'k.id_faskes_perujuk',
        'k.id_faskes_tujuan',
        'k.status_obstetri',
        'k.hpht',
        'k.hpl',
        'k.usia_kehamilan'
    ];

    public function faskes_options()
    {
        return $this->db
            ->select('id_faskes, nama_faskes, kategori')
            ->order_by('nama_faskes', 'ASC')
            ->get('m_faskes')
            ->result_array();
    }

    public function faskes_exists($id)
    {
        return $this->db
            ->where('id_faskes', $id)
            ->count_all_results('m_faskes') > 0;
    }

    public function listing($search, $limit, $offset)
    {
        $this->search($search);

        return $this->db
            ->select($this->columns)
            ->select('fp.nama_faskes AS nama_faskes_perujuk')
            ->select('ft.nama_faskes AS nama_faskes_tujuan')
            ->from('t_kunjungan k')
            ->join('m_faskes fp', 'fp.id_faskes = k.id_faskes_perujuk', 'left')
            ->join('m_faskes ft', 'ft.id_faskes = k.id_faskes_tujuan', 'left')
            ->order_by('k.id_kunjungan', 'DESC')
            ->limit($limit, $offset)
            ->get()
            ->result_array();
    }

    public function count_filtered($search)
    {
        $this->search($search);

        return $this->db
            ->from('t_kunjungan k')
            ->count_all_results();
    }

    private function search($search)
    {
        if ($search !== '') {
            $this->db
                ->group_start()
                ->like('k.no_rm', $search)
                ->or_like('k.nama', $search)
                ->group_end();
        }
    }

    public function find($id)
    {
        return $this->db
            ->select($this->columns)
            ->from('t_kunjungan k')
            ->where('k.id_kunjungan', $id)
            ->get()
            ->row_array();
    }

    public function find_detail($id)
    {
        return $this->db
            ->select($this->columns)
            ->select('fp.nama_faskes AS nama_faskes_perujuk')
            ->select('ft.nama_faskes AS nama_faskes_tujuan')
            ->from('t_kunjungan k')
            ->join('m_faskes fp', 'fp.id_faskes = k.id_faskes_perujuk', 'left')
            ->join('m_faskes ft', 'ft.id_faskes = k.id_faskes_tujuan', 'left')
            ->where('k.id_kunjungan', $id)
            ->get()
            ->row_array();
    }

    public function used_by_rm($id)
    {
        return $this->db
            ->where('id_kunjungan', $id)
            ->count_all_results('t_rm') > 0;
    }

    public function save($id, array $data)
    {
        return $this->write(function () use ($id, $data) {
            if ($id > 0) {
                return $this->db
                    ->where('id_kunjungan', $id)
                    ->update('t_kunjungan', $data);
            }

            return $this->db->insert('t_kunjungan', $data);
        });
    }

    public function delete($id)
    {
        return $this->write(function () use ($id) {
            return $this->db->delete('t_kunjungan', ['id_kunjungan' => $id]);
        });
    }

    private function write($operation)
    {
        // Kesalahan database ditampilkan sebagai pesan form, bukan detail SQL.
        $debug = $this->db->db_debug;
        $this->db->db_debug = false;

        try {
            return $operation();
        } catch (Throwable $exception) {
            log_message('error', 'Operasi CRUD identitas pasien gagal.');

            return false;
        } finally {
            $this->db->db_debug = $debug;
        }
    }
}
