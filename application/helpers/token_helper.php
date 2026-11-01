<?php
use \Firebase\JWT\JWT;
use \Firebase\JWT\Key;

if (!function_exists('validate_custom_token')) {
    function validate_custom_token($username_from_header) {
        $CI =& get_instance();
        $CI->config->item('jwt_key');
        $headers = $CI->input->request_headers();
        $headers = array_change_key_case($headers, CASE_LOWER); 
        // log_message('debug', 'Received headers: ' . print_r($headers, true));

        if (!isset($headers['x-token']) || !isset($headers['x-username'])) {
            header('Content-Type: application/json');
            $response = array(
                'metadata' => array(
                    'message' => 'Token atau username header tidak ditemukan',
                    'code' => 401
                )
            );
            echo json_encode($response);
            http_response_code(401);
            exit;
        }

        try {
            // Decode JWT
            $decoded = JWT::decode($headers['x-token'], new Key($CI->config->item('jwt_key'), 'HS256'));
            $decoded_array = (array) $decoded;

            if (isset($decoded_array['exp']) && time() > $decoded_array['exp']) {
                header('Content-Type: application/json');
                $response = array(
                    'metadata' => array(
                        'message' => 'Token telah kadaluarsa.',
                        'code' => 401
                    )
                );
                echo json_encode($response);
                http_response_code(401);
                exit;
            }

            if (isset($decoded_array['username']) && $decoded_array['username'] === $username_from_header) {
                return $decoded_array;
            } else {
                header('Content-Type: application/json');
                $response = array(
                    'metadata' => array(
                        'message' => 'Username dalam token tidak cocok.',
                        'code' => 401
                    )
                );
                echo json_encode($response);
                http_response_code(401);
                exit;
            }
        } catch (Exception $e) {
            header('Content-Type: application/json');
            $response = array(
                'metadata' => array(
                    'message' => 'Token: ' . $e->getMessage(),
                    'code' => 401
                )
            );
            echo json_encode($response);
            http_response_code(401);
            exit;
        }
    }
}
