<?php

defined('BASEPATH') or exit('No direct script access allowed');

class Web_Controller extends CI_Controller
{
    protected $web_user;

    public function __construct()
    {
        parent::__construct();

        $this->load->helper('form');
        $this->output->set_header('Cache-Control: no-store');
        $this->load->model('web/Web_auth_model', 'web_auth');

        $id = $this->session->userdata('web_user_id');
        $this->web_user = $id ? $this->web_auth->active_user($id) : null;

        // Periksa kembali status akun agar akun nonaktif atau terhapus kehilangan akses.
        if (!$this->web_user) {
            $this->session->unset_userdata('web_user_id');
            redirect('web/login');
            exit;
        }
    }

    protected function render($view, array $data = [])
    {
        $data['content_view'] = $view;
        $data['web_user'] = $this->web_user;
        $data['page_title'] = isset($data['page_title']) ? $data['page_title'] : 'EMNIRA';
        $data['active_menu'] = isset($data['active_menu']) ? $data['active_menu'] : '';

        $this->load->view('template/layout', $data);
    }
}
