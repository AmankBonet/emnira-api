<?php

defined('BASEPATH') or exit('No direct script access allowed');

use chriskacerguis\RestServer\RestController;

class Rujukan extends RestController
{
    public function __construct()
    {
        parent::__construct();

        $this->load->helper(['token', 'rujukan']);
        $this->load->model('Rujukan_model', 'rujukan');
        $this->config->load('rujukan');
    }

    private function reply($code, $message, $data = null)
    {
        $body = ['metadata' => ['code' => $code, 'message' => $message]];

        if ($data !== null) {
            $body['response'] = $data;
        }

        return $this->response($body, $code);
    }

    public function index_post()
    {
        $claims = validate_custom_token();
        $role = $this->db->get_where('m_role', ['id_role' => $claims['id_role']])->row_array();
        $asal = $this->db->get_where('m_faskes', ['id_faskes' => $claims['id_faskes']])->row_array();

        // Pengirim rujukan harus akun terdaftar dari fasilitas tingkat pertama.
        if (!$role || !$asal || $asal['kategori'] !== 'FKTP') {
            return $this->reply(403, 'Akun tidak memiliki izin membuat rujukan.');
        }

        $body = json_decode($this->input->raw_input_stream);

        if (!is_object($body)) {
            return $this->reply(400, 'Body harus berupa JSON object yang valid.');
        }

        $input = json_decode($this->input->raw_input_stream, true);
        $errors = validate_rujukan_input($input);

        if ($errors) {
            return $this->reply(422, 'Validasi gagal.', ['errors' => $errors]);
        }

        $kunjungan = $this->rujukan->findKunjungan($input['id_kunjungan']);

        if (!$kunjungan) {
            return $this->reply(422, 'Kunjungan tidak ditemukan.');
        }

        // DDL kunjungan belum memiliki ID pemilik fasilitas. Jangan cocokkan nama
        // asal_faskes secara perkiraan atau izinkan akses lintas fasilitas.
        $ownerColumn = $this->config->item('rujukan_kunjungan_owner_column');

        if (!$ownerColumn || !array_key_exists($ownerColumn, $kunjungan)) {
            return $this->reply(503, 'Relasi kepemilikan faskes pada kunjungan belum dikonfigurasi.');
        }

        if ((int) $kunjungan[$ownerColumn] !== (int) $claims['id_faskes']) {
            return $this->reply(403, 'Kunjungan bukan milik faskes Anda.');
        }

        $tujuan = $this->db->get_where('m_faskes', [
            'id_faskes' => $input['id_faskes_tujuan'],
            'kategori' => 'FKRTL'
        ])->row_array();

        if (!$tujuan) {
            return $this->reply(422, 'Faskes tujuan harus terdaftar sebagai FKRTL.');
        }

        if ((int) $input['id_faskes_tujuan'] === (int) $claims['id_faskes']) {
            return $this->reply(422, 'Faskes tujuan harus berbeda dari faskes asal.');
        }

        if (isset($input['id_diagnosis']) && !$this->rujukan->diagnosisExists($input['id_diagnosis'])) {
            return $this->reply(422, 'Diagnosis tidak ditemukan.');
        }

        $pilihan = isset($input['pilihan']) ? $input['pilihan'] : [];

        foreach ($pilihan as $kode) {
            if (!$this->db->get_where('m_rm_pilihan', ['kode' => $kode])->row_array()) {
                return $this->reply(422, 'Kode checkbox tidak ditemukan.');
            }
        }

        unset($input['pilihan']);
        $input['id_user_pembuat'] = (int) $claims['uid'];
        $input['id_faskes_asal'] = (int) $claims['id_faskes'];

        if (empty($input['waktu_rujukan'])) {
            $input['waktu_rujukan'] = (new DateTime('now', new DateTimeZone('Asia/Jakarta')))->format('Y-m-d H:i:s');
        }

        $id = $this->rujukan->save($input, $pilihan);

        if ($id === null) {
            return $this->reply(500, 'Rujukan gagal disimpan.');
        }

        // Kirim notifikasi Expo Push ke seluruh petugas aktif pada faskes tujuan
        $this->notifyFaskesTujuan(
            (int) $input['id_faskes_tujuan'],
            isset($asal['nama_faskes']) ? $asal['nama_faskes'] : 'FKTP'
        );

        return $this->reply(201, 'Rujukan berhasil disimpan.', ['id_rm' => $id]);
    }

    private function notifyFaskesTujuan($idFaskesTujuan, $namaFaskesAsal)
    {
        $recipients = $this->db
            ->select('token_expo_push')
            ->where('id_faskes', $idFaskesTujuan)
            ->where('id_active', 1)
            ->where('token_expo_push IS NOT NULL', null, false)
            ->where('token_expo_push !=', '')
            ->get('m_user')
            ->result_array();

        if (empty($recipients))
        {
            return;
        }

        $title = 'Rujukan PONEK Baru Masuk';
        $body = 'Rujukan baru dari ' . $namaFaskesAsal . ' membutuhkan penanganan segera.';
        $sentTokens = [];

        foreach ($recipients as $row)
        {
            $token = trim((string) $row['token_expo_push']);

            if ($token === '' || isset($sentTokens[$token]))
            {
                continue;
            }

            $sentTokens[$token] = true;
            $this->sendPushNotification($token, $title, $body);
        }
    }

    private function sendPushNotification($token, $title, $body)
    {
        $payload = [
            'to'        => $token,
            'title'     => $title,
            'body'      => $body,
            'priority'  => 'high',
            'channelId' => 'emnira_custom_sound',
            'ttl'       => 86400
        ];

        $ch = curl_init();

        curl_setopt_array($ch, [
            CURLOPT_URL            => 'https://exp.host/--/api/v2/push/send',
            CURLOPT_POST           => true,
            CURLOPT_POSTFIELDS     => json_encode($payload),
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER     => [
                'Accept: application/json',
                'Content-Type: application/json',
                'Accept-Encoding: gzip, deflate'
            ],
            CURLOPT_TIMEOUT        => 30
        ]);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curlError = curl_error($ch);

        // Lepaskan handle tanpa curl_close() yang deprecated pada PHP 8.5.
        unset($ch);

        if ($response === false)
        {
            return [
                'status'  => false,
                'message' => $curlError
            ];
        }

        return [
            'status'    => $httpCode == 200,
            'http_code' => $httpCode,
            'response'  => json_decode($response, true)
        ];
    }
}
