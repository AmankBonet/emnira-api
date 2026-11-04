<?php
defined('BASEPATH') or exit('No direct script access allowed');

class Master_model extends CI_Model
{
    public function __construct()
    {
        parent::__construct();
    }

    public function getFaskes()
    {
        return $this->db->get('m_faskes')->result_array();
    }

    public function getRmPilihan($kelompok = null)
    {
        if (is_string($kelompok) && $kelompok !== '')
        {
            $this->db->where('kelompok', $kelompok);
        }

        return $this->db
            ->order_by('kelompok', 'ASC')
            ->order_by('kode', 'ASC')
            ->get('m_rm_pilihan')
            ->result_array();
    }
}
