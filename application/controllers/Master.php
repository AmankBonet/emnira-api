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
}
