<?php
defined('BASEPATH') or exit('No direct script access allowed');

use chriskacerguis\RestServer\RestController;
use \Firebase\JWT\JWT;

class Api extends RestController
{
    public function __construct()
    {
        parent::__construct();
        $this->load->model('Klinik_model', 'klinik');
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

    private function check($data)
    {
        $obj = new stdClass;
        if (count($data) == 0) {
            $obj->status = false;
            $obj->message = "Data tidak tersedia!";
        } else {
            $obj->status = true;
        }
        return $obj;
    }

    private function tomiliseconds($tanggal)
    {
        date_default_timezone_set('Asia/Jakarta');
        return strtotime($tanggal) * 1000;
    }


    public function simpan_post()
    {
        date_default_timezone_set('Asia/Jakarta');

        $header_username    = $this->input->get_request_header('x-username', TRUE);
        $header_token       = $this->input->get_request_header('x-token', TRUE);

        $token = validate_custom_token($header_username);

        if ($token <> false) 
        {
            
        } else {
            $status = parent::HTTP_CREATED;
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
