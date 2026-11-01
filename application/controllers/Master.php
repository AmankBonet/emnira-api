<?php
defined('BASEPATH') or exit('No direct script access allowed');

use chriskacerguis\RestServer\RestController;
use \Firebase\JWT\JWT;

class Master extends RestController
{
    public function __construct()
    {
        parent::__construct();
        $this->load->model('Master_model', 'master');
        $this->load->helper('token');
    }

    private function gagal($pesan = 'Anda tidak memiliki akses / Layanan tidak tersedia.')
    {
        $status = parent::HTTP_CREATED;
        $response = array(
            'metadata' => array(
                'code' => $status,
                'message' => $pesan
            )
        );
        $this->response($response, $status);
    }

    public function lokasi_get()
    {
        $header_username = $this->input->get_request_header('x-username', TRUE);
        $header_token = $this->input->get_request_header('x-token', TRUE);
        $profil = $this->db->get_where('api_login', ['username' => $header_username])->row_array();

        $token = validate_custom_token($header_username);
        
        if ($token <> false) {
            $status = parent::HTTP_OK;
            $res = $this->master->getLokasi();

            $status = parent::HTTP_OK;
            $response = array(
                'response' => $res,
                'metadata' => array(
                    'message' => 'Ok',
                    'code' => $status
                )
            );
            $this->response($response, $status);

        } else {
            $status = parent::HTTP_NOT_FOUND;
            $response = array(
                'metadata' => array(
                    'message' => 'Gagal',
                    'code' => $status
                )
            );
            $this->response($response, $status);
        }
    }
    
    
}
