<?php

defined('BASEPATH') or exit('No direct script access allowed');

class Web_auth_model extends CI_Model
{
    public function authenticate($username, $password)
    {
        $users = $this->db
            ->get_where('m_user', ['username' => $username])
            ->result_array();

        if (count($users) !== 1 || (int) $users[0]['id_active'] !== 1) {
            return null;
        }

        $user = $users[0];
        $hash = $user['password_hash'];
        if (!is_string($hash) || $hash === '') {
            return null;
        }

        // Akun lama masih memakai teks biasa; kompatibilitas mengikuti login API.
        $valid = password_get_info($hash)['algoName'] !== 'unknown'
            ? password_verify($password, $hash)
            : hash_equals($hash, $password);

        return $valid ? $user : null;
    }

    public function active_user($id)
    {
        // Nama faskes dan role ditampilkan di sidebar sebagai penanda ruang kerja user.
        return $this->db
            ->select('u.id_user, u.nama, u.username, u.id_role, u.id_faskes')
            ->select('f.nama_faskes, f.kategori, r.nama_role')
            ->from('m_user u')
            ->join('m_faskes f', 'f.id_faskes = u.id_faskes', 'left')
            ->join('m_role r', 'r.id_role = u.id_role', 'left')
            ->where('u.id_user', $id)
            ->where('u.id_active', 1)
            ->get()
            ->row_array();
    }
}
