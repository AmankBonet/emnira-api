<?php
defined('BASEPATH') or exit('No direct script access allowed');

class Master_model extends CI_Model
{
    function __construct()
    {
        parent::__construct();
    }

    public function getLokasi()
    {
        return $this->db->get('m_lokasi')->result_array();
    }
    
}
