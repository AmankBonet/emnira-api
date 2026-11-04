<?php

defined('BASEPATH') or exit('No direct script access allowed');

class Rujukan_model extends CI_Model
{
    public function findKunjungan($id)
    {
        return $this->db->get_where('t_kunjungan', ['id_kunjungan' => $id])->row_array();
    }

    public function diagnosisExists($id)
    {
        return $this->db->get_where('m_diagnosis_ponek', ['id_diagnosis' => $id])->num_rows() === 1;
    }

    public function save($data, $pilihan)
    {
        // Hindari halaman error HTML dan jangan bocorkan detail SQL ke Android.
        $debug = $this->db->db_debug;
        $this->db->db_debug = false;
        $id = null;

        try {
            if (!$this->db->trans_begin()) {
                throw new RuntimeException('Tidak dapat memulai transaksi.');
            }

            if (!$this->db->insert('t_rm', $data)) {
                throw new RuntimeException('Insert rujukan gagal.');
            }

            $id = $this->db->insert_id();

            foreach ($pilihan as $item) {
                $kode = is_array($item) ? $item['kode_pilihan'] : $item;
                $catatan = is_array($item) && isset($item['catatan']) ? $item['catatan'] : null;

                if (!$this->db->insert('t_rm_pilihan', [
                    'id_rm' => $id,
                    'kode_pilihan' => $kode,
                    'catatan' => $catatan
                ])) {
                    throw new RuntimeException('Insert pilihan gagal.');
                }
            }

            if (!$this->db->trans_status() || !$this->db->trans_commit()) {
                throw new RuntimeException('Commit rujukan gagal.');
            }
        } catch (Exception $e) {
            $this->db->trans_rollback();
            log_message('error', 'Penyimpanan rujukan gagal.');
            $id = null;
        } finally {
            $this->db->db_debug = $debug;
        }

        return $id;
    }
}
