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
        'k.usia_kehamilan',
        'k.status_rujukan',
        'k.waktu_dikirim',
        'k.waktu_diterima',
        'k.catatan_penerimaan',
        'k.waktu_tiba'
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

    /**
     * Daftar pasien milik faskes perujuk; $faskes_id null = semua (administrator).
     */
    public function listing($search, $limit, $offset, $faskes_id = null)
    {
        $this->search($search);
        $this->filter_perujuk($faskes_id);

        return $this->with_faskes()
            ->order_by('k.id_kunjungan', 'DESC')
            ->limit($limit, $offset)
            ->get()
            ->result_array();
    }

    public function count_filtered($search, $faskes_id = null)
    {
        $this->search($search);
        $this->filter_perujuk($faskes_id);

        return $this->db
            ->from('t_kunjungan k')
            ->count_all_results();
    }

    /**
     * Rujukan yang sudah dikirim ke faskes tujuan.
     *
     * @param string $mode aktif (dikirim/diterima) atau selesai (tiba)
     */
    public function listing_masuk($faskes_id, $mode, $limit = 100)
    {
        if ($faskes_id !== null) {
            $this->db->where('k.id_faskes_tujuan', $faskes_id);
        }

        if ($mode === 'selesai') {
            $this->db
                ->where('k.status_rujukan', 'tiba')
                ->order_by('k.waktu_tiba', 'DESC');
        } else {
            // Rujukan yang belum diterima tampil paling atas agar segera ditanggapi.
            $this->db
                ->where_in('k.status_rujukan', ['dikirim', 'diterima'])
                ->order_by("FIELD(k.status_rujukan, 'dikirim', 'diterima')", '', false)
                ->order_by('k.waktu_dikirim', 'DESC');
        }

        return $this->with_faskes()
            ->limit($limit)
            ->get()
            ->result_array();
    }

    public function count_masuk_belum_diterima($faskes_id)
    {
        if ($faskes_id !== null) {
            $this->db->where('id_faskes_tujuan', $faskes_id);
        }

        return $this->db
            ->where('status_rujukan', 'dikirim')
            ->count_all_results('t_kunjungan');
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

    private function filter_perujuk($faskes_id)
    {
        if ($faskes_id !== null) {
            $this->db->where('k.id_faskes_perujuk', $faskes_id);
        }
    }

    private function with_faskes()
    {
        return $this->db
            ->select($this->columns)
            ->select('fp.nama_faskes AS nama_faskes_perujuk')
            ->select('ft.nama_faskes AS nama_faskes_tujuan')
            ->from('t_kunjungan k')
            ->join('m_faskes fp', 'fp.id_faskes = k.id_faskes_perujuk', 'left')
            ->join('m_faskes ft', 'ft.id_faskes = k.id_faskes_tujuan', 'left');
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
        return $this->with_faskes()
            ->select('up.nama AS nama_pengirim')
            ->select('ut.nama AS nama_penerima')
            ->select('uk.nama AS nama_pencatat_tiba')
            ->join('m_user up', 'up.id_user = k.id_user_pengirim', 'left')
            ->join('m_user ut', 'ut.id_user = k.id_user_penerima', 'left')
            ->join('m_user uk', 'uk.id_user = k.id_user_kedatangan', 'left')
            ->where('k.id_kunjungan', $id)
            ->get()
            ->row_array();
    }

    /**
     * Mengubah status hanya bila status saat ini masih $dari, sehingga dua petugas yang
     * menekan tombol bersamaan tidak saling menimpa.
     */
    public function ubah_status($id, $dari, array $data)
    {
        return $this->write(function () use ($id, $dari, $data) {
            $this->db
                ->where('id_kunjungan', $id)
                ->where('status_rujukan', $dari)
                ->update('t_kunjungan', $data);

            return $this->db->affected_rows() === 1;
        });
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
            log_message('error', 'Operasi data kunjungan gagal.');

            return false;
        } finally {
            $this->db->db_debug = $debug;
        }
    }
}
