<?php

defined('BASEPATH') or exit('No direct script access allowed');

class User_model extends CI_Model
{
    public function role_options()
    {
        return $this->db
            ->select('id_role, nama_role')
            ->order_by('nama_role', 'ASC')
            ->get('m_role')
            ->result_array();
    }

    public function faskes_options()
    {
        return $this->db
            ->select('id_faskes, nama_faskes, kategori')
            ->order_by('nama_faskes', 'ASC')
            ->get('m_faskes')
            ->result_array();
    }

    public function role_exists($id)
    {
        return $this->db
            ->where('id_role', $id)
            ->count_all_results('m_role') > 0;
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
            ->select('id_user, nama, username, id_faskes, id_role, id_active')
            ->order_by('id_user', 'DESC')
            ->get('m_user', $limit, $offset)
            ->result_array();
    }

    public function count_filtered($search)
    {
        $this->search($search);

        return $this->db->count_all_results('m_user');
    }

    private function search($search)
    {
        if ($search !== '') {
            $this->db
                ->group_start()
                ->like('nama', $search)
                ->or_like('username', $search)
                ->group_end();
        }
    }

    public function find($id)
    {
        return $this->db
            ->select('id_user, nama, username, id_faskes, id_role, id_active')
            ->get_where('m_user', ['id_user' => $id])
            ->row_array();
    }

    public function username_exists($username, $except_id)
    {
        return $this->db
            ->where('username', $username)
            ->where('id_user !=', $except_id)
            ->count_all_results('m_user') > 0;
    }

    public function save($id, array $data)
    {
        return $this->write(function () use ($id, $data) {
            if ($id > 0) {
                return $this->db->where('id_user', $id)->update('m_user', $data);
            }

            return $this->db->insert('m_user', $data);
        });
    }

    public function delete($id)
    {
        return $this->write(function () use ($id) {
            return $this->db->delete('m_user', ['id_user' => $id]);
        });
    }

    private function write($operation)
    {
        // Kesalahan relasi database ditampilkan sebagai pesan form, bukan detail SQL.
        $debug = $this->db->db_debug;
        $this->db->db_debug = false;

        try {
            return $operation();
        } catch (Throwable $exception) {
            log_message('error', 'Operasi CRUD pengguna web gagal.');

            return false;
        } finally {
            $this->db->db_debug = $debug;
        }
    }
}
