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
        return $this->db
            ->select('id_user, nama, username, id_role, id_faskes')
            ->get_where('m_user', ['id_user' => $id, 'id_active' => 1])
            ->row_array();
    }
}
