<?php

defined('BASEPATH') or exit('No direct script access allowed');

class Observasi_model extends CI_Model
{
    public function listing($id_kunjungan, $limit = 50)
    {
        return $this->db
            ->select('o.*, u.nama AS nama_pencatat')
            ->from('t_kunjungan_observasi o')
            ->join('m_user u', 'u.id_user = o.id_user_pencatat', 'left')
            ->where('o.id_kunjungan', $id_kunjungan)
            ->order_by('o.waktu_observasi', 'DESC')
            ->order_by('o.id_observasi', 'DESC')
            ->limit($limit)
            ->get()
            ->result_array();
    }

    /**
     * Beberapa observasi terakhir per kunjungan untuk daftar rujukan (tren MEOWS naik/turun).
     *
     * @return array [id_kunjungan => [observasi terbaru, observasi sebelumnya, ...]]
     */
    public function terakhir_per_kunjungan(array $ids, $jumlah = 2)
    {
        if (!$ids) {
            return [];
        }

        $rows = $this->db
            ->select('id_kunjungan, waktu_observasi, meows_skor, meows_zona')
            ->where_in('id_kunjungan', $ids)
            ->order_by('waktu_observasi', 'DESC')
            ->order_by('id_observasi', 'DESC')
            ->get('t_kunjungan_observasi')
            ->result_array();

        $result = [];
        foreach ($rows as $row) {
            $id = (int) $row['id_kunjungan'];
            if (!isset($result[$id]) || count($result[$id]) < $jumlah) {
                $result[$id][] = $row;
            }
        }

        return $result;
    }

    public function find($id_kunjungan, $id_observasi)
    {
        return $this->db
            ->get_where('t_kunjungan_observasi', [
                'id_kunjungan' => $id_kunjungan,
                'id_observasi' => $id_observasi
            ])
            ->row_array();
    }

    public function insert(array $data)
    {
        return $this->write(function () use ($data) {
            return $this->db->insert('t_kunjungan_observasi', $data);
        });
    }

    public function delete($id_kunjungan, $id_observasi)
    {
        return $this->write(function () use ($id_kunjungan, $id_observasi) {
            return $this->db->delete('t_kunjungan_observasi', [
                'id_kunjungan' => $id_kunjungan,
                'id_observasi' => $id_observasi
            ]);
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
            log_message('error', 'Operasi observasi gagal: ' . $exception->getMessage());

            return false;
        } finally {
            $this->db->db_debug = $debug;
        }
    }
}
