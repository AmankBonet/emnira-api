<?php

defined('BASEPATH') or exit('No direct script access allowed');

use Firebase\JWT\JWT;
use Firebase\JWT\Key;

if (!function_exists('validate_custom_token')) {
    // Parameter opsional untuk kompatibilitas controller lama.
    function validate_custom_token($username_from_header = null)
    {
        $CI =& get_instance();
        $CI->load->database();

        $headers = array_change_key_case($CI->input->request_headers(), CASE_LOWER);
        $token = isset($headers['x-token']) ? $headers['x-token'] : null;

        if (isset($headers['authorization'])) {
            $token = preg_match('/^Bearer\s+(\S+)$/i', $headers['authorization'], $matches)
                ? $matches[1]
                : null;
        }

        try {
            if (!is_string($token) || $token === '') {
                throw new UnexpectedValueException('Missing token');
            }

            $claims = (array) JWT::decode(
                $token,
                new Key($CI->config->item('jwt_key'), 'HS256')
            );

            if (
                empty($claims['uid'])
                || empty($claims['sid'])
                || empty($claims['exp'])
                || empty($claims['jti'])
            ) {
                throw new UnexpectedValueException('Missing claims');
            }

            $session = $CI->db->get_where('auth_sessions', [
                'id_session' => $claims['sid'],
                'id_user' => $claims['uid'],
                'revoked_at' => null,
                'expires_at >' => time()
            ])->row_array();

            $user = $CI->db->get_where('m_user', [
                'id_user' => $claims['uid'],
                'id_active' => 1
            ])->row_array();

            if (
                !$session
                || !$user
                || ($username_from_header !== null && $username_from_header !== $user['username'])
            ) {
                throw new UnexpectedValueException('Invalid session');
            }

            $claims['username'] = $user['username'];
            $claims['id_role'] = $user['id_role'];
            $claims['id_faskes'] = $user['id_faskes'];

            unset($user['password_hash']);
            $claims['user'] = $user;

            return $claims;
        } catch (Exception $e) {
            set_status_header(401);
            header('Content-Type: application/json');

            echo json_encode([
                'metadata' => [
                    'code' => 401,
                    'message' => 'Token tidak valid, kedaluwarsa, atau sesi telah berakhir.'
                ]
            ]);
            exit;
        }
    }
}
