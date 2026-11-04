<?php

defined('BASEPATH') or exit('No direct script access allowed');

use chriskacerguis\RestServer\RestController;
use Firebase\JWT\JWT;

require_once FCPATH . 'vendor/autoload.php';

class Auth extends RestController
{
    public function __construct()
    {
        parent::__construct();

        $this->load->database();
        $this->load->helper('token');
    }

    private function reply($code, $message, $data = null)
    {
        $body = [
            'metadata' => [
                'code' => $code,
                'message' => $message
            ]
        ];

        if ($data !== null) {
            $body['response'] = $data;
        }

        return $this->response($body, $code);
    }

    private function profile($user)
    {
        $faskes = null;
        if (!empty($user['id_faskes'])) {
            $faskesData = $this->db
                ->get_where('m_faskes', ['id_faskes' => $user['id_faskes']])
                ->row_array();
            $faskes = $faskesData && isset($faskesData['nama_faskes']) ? $faskesData['nama_faskes'] : null;
        }

        $role = null;
        if (!empty($user['id_role'])) {
            $roleData = $this->db
                ->get_where('m_role', ['id_role' => $user['id_role']])
                ->row_array();
            $role = $roleData && isset($roleData['nama_role']) ? $roleData['nama_role'] : null;
        }

        $status = (int) $user['id_active'] === 1 ? 'aktif' : 'non aktif';

        return [
            'id_user'  => $user['id_user'],
            'nama'     => $user['nama'],
            'username' => $user['username'],
            'faskes'   => $faskes,
            'role'     => $role,
            'status'   => $status
        ];
    }

    private function tokens($user, $sid, $refresh)
    {
        $now = time();
        $token = JWT::encode([
            'iat' => $now,
            'exp' => $now + 600,
            'uid' => (int) $user['id_user'],
            'username' => $user['username'],
            'id_role' => $user['id_role'],
            'id_faskes' => $user['id_faskes'],
            'sid' => $sid,
            'jti' => bin2hex(random_bytes(16))
        ], $this->config->item('jwt_key'), 'HS256');

        return [
            'access_token' => $token,
            'token_type' => 'Bearer',
            'expires_in' => 600,
            'refresh_token' => $refresh,
            'user' => $this->profile($user)
        ];
    }

    public function login_post()
    {
        $data = $this->post();
        $username = isset($data['username']) ? $data['username'] : null;
        $password = isset($data['password']) ? $data['password'] : null;

        if (
            !is_string($username)
            || trim($username) === ''
            || !is_string($password)
            || $password === ''
        ) {
            return $this->reply(400, 'Username dan password wajib diisi.');
        }

        $users = $this->db
            ->get_where('m_user', ['username' => $username])
            ->result_array();
        $user = count($users) === 1 ? $users[0] : null;

        // Akun baru memakai hash; akun lama tetap kompatibel selama transisi.
        $valid = $user
            && is_string($user['password_hash'])
            && (password_get_info($user['password_hash'])['algoName'] !== 'unknown'
                ? password_verify($password, $user['password_hash'])
                : hash_equals($user['password_hash'], $password));

        if (!$valid || (int) $user['id_active'] !== 1) {
            return $this->reply(401, 'Username/password tidak sesuai atau akun tidak aktif.');
        }

        $sid = bin2hex(random_bytes(32));
        $refresh = bin2hex(random_bytes(32));
        $result = $this->tokens($user, $sid, $refresh);

        if (!$this->db->insert('auth_sessions', [
            'id_session' => $sid,
            'id_user' => $user['id_user'],
            'refresh_hash' => hash('sha256', $refresh),
            'expires_at' => time() + 7 * 86400,
            'revoked_at' => null
        ])) {
            return $this->reply(500, 'Gagal membuat sesi.');
        }

        return $this->reply(200, 'Login berhasil.', $result);
    }

    public function refresh_post()
    {
        $data = $this->post();

        if (
            !isset($data['refresh_token'])
            || !is_string($data['refresh_token'])
            || $data['refresh_token'] === ''
        ) {
            return $this->reply(400, 'Refresh token wajib diisi.');
        }

        $hash = hash('sha256', $data['refresh_token']);
        $session = $this->db->get_where('auth_sessions', [
            'refresh_hash' => $hash,
            'revoked_at' => null,
            'expires_at >' => time()
        ])->row_array();

        if (!$session) {
            return $this->reply(401, 'Sesi berakhir. Silakan login kembali.');
        }

        $user = $this->db->get_where('m_user', [
            'id_user' => $session['id_user'],
            'id_active' => 1
        ])->row_array();

        if (!$user) {
            return $this->reply(401, 'Akun tidak aktif atau tidak ditemukan.');
        }

        $refresh = bin2hex(random_bytes(32));
        $result = $this->tokens($user, $session['id_session'], $refresh);

        // Update bersyarat mencegah dua request memakai refresh token yang sama.
        $saved = $this->db
            ->where([
                'id_session' => $session['id_session'],
                'refresh_hash' => $hash,
                'revoked_at' => null,
                'expires_at >' => time()
            ])
            ->update('auth_sessions', [
                'refresh_hash' => hash('sha256', $refresh)
            ]);

        if (!$saved || $this->db->affected_rows() !== 1) {
            return $this->reply(401, 'Refresh token sudah digunakan atau sesi berakhir.');
        }

        return $this->reply(200, 'Token diperbarui.', $result);
    }

    public function me_get()
    {
        $claims = validate_custom_token();

        return $this->reply(200, 'Ok', [
            'user' => $this->profile($claims['user'])
        ]);
    }

    public function logout_post()
    {
        $claims = validate_custom_token();

        if (!$this->db
            ->where('id_session', $claims['sid'])
            ->update('auth_sessions', ['revoked_at' => time()])
        ) {
            return $this->reply(500, 'Gagal mengakhiri sesi.');
        }

        return $this->reply(200, 'Logout berhasil.');
    }

    public function push_token_get()
    {
        $claims = validate_custom_token();

        return $this->reply(200, 'Ok', [
            'id_user' => $claims['user']['id_user'],
            'token_expo_push' => isset($claims['user']['token_expo_push'])
                ? $claims['user']['token_expo_push']
                : null
        ]);
    }

    public function push_token_post()
    {
        return $this->save_push_token();
    }

    public function push_token_put()
    {
        return $this->save_push_token();
    }

    public function push_token_delete()
    {
        $claims = validate_custom_token();

        $saved = $this->db
            ->where('id_user', $claims['uid'])
            ->update('m_user', [
                'token_expo_push' => null
            ]);

        if (!$saved)
        {
            return $this->reply(500, 'Gagal menghapus token push notification.');
        }

        return $this->reply(200, 'Token push notification berhasil dihapus.', [
            'id_user' => $claims['user']['id_user'],
            'token_expo_push' => null
        ]);
    }

    private function save_push_token()
    {
        $claims = validate_custom_token();
        $payload = json_decode($this->input->raw_input_stream, true);

        if (!is_array($payload))
        {
            $payload = $this->post() ?: $this->put();
        }

        $token = null;

        if (is_array($payload))
        {
            if (array_key_exists('token_expo_push', $payload))
            {
                $token = $payload['token_expo_push'];
            }
            elseif (array_key_exists('token', $payload))
            {
                $token = $payload['token'];
            }
        }

        if (!is_string($token) || trim($token) === '')
        {
            return $this->reply(400, 'Token Expo Push wajib diisi.');
        }

        $token = trim($token);

        if (strlen($token) > 255)
        {
            return $this->reply(422, 'Token Expo Push maksimal 255 karakter.');
        }

        // Format standar Expo Push Token adalah ExponentPushToken[...] atau ExpoPushToken[...]
        if (!preg_match('/^(ExponentPushToken|ExpoPushToken)\[[^\]]+\]$/', $token))
        {
            return $this->reply(422, 'Format token Expo Push tidak valid (gunakan format ExponentPushToken[...] atau ExpoPushToken[...]).');
        }

        $saved = $this->db
            ->where('id_user', $claims['uid'])
            ->update('m_user', [
                'token_expo_push' => $token
            ]);

        if (!$saved)
        {
            return $this->reply(500, 'Gagal menyimpan token push notification.');
        }

        return $this->reply(200, 'Token push notification berhasil disimpan.', [
            'id_user' => $claims['user']['id_user'],
            'token_expo_push' => $token
        ]);
    }
}
