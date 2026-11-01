<?php
defined('BASEPATH') or exit('No direct script access allowed');

use chriskacerguis\RestServer\RestController;
use \Firebase\JWT\JWT;

require_once 'vendor/autoload.php';

class Auth extends RestController
{
    public function __construct()
    {
        parent::__construct();
        $this->load->helper('token');
    }

    private function gagal($pesan = 'Anda tidak memiliki akses / Layanan tidak tersedia.')
    {
        $status = parent::HTTP_CREATED;
        // $status = parent::HTTP_UNAUTHORIZED;
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

    public function index_get()
    {
        $username = $this->input->get_request_header('x-username', TRUE) 
                ?? $this->input->get_request_header('X-Username', TRUE);
                
        $password = $this->input->get_request_header('x-password', TRUE) 
                ?? $this->input->get_request_header('X-Password', TRUE);

        if (empty($username) || empty($password)) {
            $status = parent::HTTP_BAD_REQUEST; 
            $response = [
                'metadata' => [
                    'code' => $status,
                    'message' => 'Header x-username dan x-password wajib diisi.'
                ]
            ];
            return $this->response($response, $status);
        }


        $user = $this->db->get_where('m_api_login', ['username' => $username])->row_array();

        // $isPasswordValid = $user && password_verify($password, $user['password']);
        $isPasswordValid = $user && $password === $user['password'];

        if ($isPasswordValid) {
            $issuedAt = time();
            $expirationTime = $issuedAt + (10 * 60); // Token berlaku 10 menit

            $payload = [
                'iat'      => $issuedAt,
                'exp'      => $expirationTime,
                'uid'      => $user['id'],
                'username' => $user['username'],
                'jti'      => uniqid('', true)
            ];

            $token = JWT::encode($payload, $this->config->item('jwt_key'), 'HS256');
            $status = parent::HTTP_OK; // 200

            $response = [
                'response' => [
                    'token' => $token
                ],
                'metadata' => [
                    'message' => 'Ok',
                    'code'    => $status
                ]
            ];
            $this->response($response, $status);

        } else {
            $status = parent::HTTP_UNAUTHORIZED; // 401 Unauthorized (Lebih tepat daripada HTTP_CREATED)
            $response = [
                'metadata' => [
                    'code'    => $status,
                    'message' => 'User dan Password tidak sesuai.'
                ]
            ];
            $this->response($response, $status);
        }
    }

    
    
}
