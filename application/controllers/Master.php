<?php
defined('BASEPATH') or exit('No direct script access allowed');

use chriskacerguis\RestServer\RestController;

class Master extends RestController
{
    public function __construct()
    {
        parent::__construct();
        $this->load->model('Master_model', 'master');
        $this->load->helper('token');
    }

    public function faskes_get()
    {
        validate_custom_token();

        $status = parent::HTTP_OK;

        return $this->response([
            'response' => $this->master->getFaskes(),
            'metadata' => [
                'message' => 'Ok',
                'code' => $status
            ]
        ], $status);
    }

    public function rm_pilihan_get()
    {
        validate_custom_token();

        $kelompok = $this->get('kelompok');
        $allowedKelompok = [
            'tanda_bahaya',
            'riwayat_medis',
            'terapi'
        ];

        if ($kelompok !== null && (!is_string($kelompok) || !in_array($kelompok, $allowedKelompok, true)))
        {
            return $this->response([
                'metadata' => [
                    'message' => 'Kelompok pilihan tidak valid (gunakan: tanda_bahaya, riwayat_medis, atau terapi).',
                    'code' => 422
                ]
            ], 422);
        }

        $status = parent::HTTP_OK;

        return $this->response([
            'response' => $this->master->getRmPilihan($kelompok),
            'metadata' => [
                'message' => 'Ok',
                'code' => $status
            ]
        ], $status);
    }
}
