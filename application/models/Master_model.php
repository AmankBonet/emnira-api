<?php
defined('BASEPATH') or exit('No direct script access allowed');

class Master_model extends CI_Model
{
    function __construct()
    {
        parent::__construct();
    }

    public function getFaskes()
    {
        return $this->db->get('m_faskes')->result_array();
    }
}
