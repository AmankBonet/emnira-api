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

        // Hak akses rekam medis ditentukan oleh faskes user terhadap tiap kunjungan.
        $this->load->library('akses_rujukan', $this->web_user, 'akses');
    }

    /**
     * Menolak akses menu yang bukan milik role user (tidak cukup hanya menyembunyikan menu).
     *
     * @param string $menu fktp | fkrtl | admin
     */
    protected function require_menu($menu)
    {
        $cek = [
            'fktp' => $this->akses->is_fktp(),
            'fkrtl' => $this->akses->is_fkrtl(),
            'admin' => $this->akses->is_admin()
        ];
        $boleh = isset($cek[$menu]) && $cek[$menu];

        if (!$boleh) {
            $this->session->set_flashdata('error', 'Menu tersebut tidak tersedia untuk peran Anda.');
            redirect($this->akses->beranda());
            exit;
        }
    }

    protected function render($view, array $data = [])
    {
        $this->load->model('web/Kunjungan_model', 'kunjungan');

        $data['content_view'] = $view;
        $data['web_user'] = $this->web_user;
        $data['menu_fktp'] = $this->akses->is_fktp();
        $data['menu_fkrtl'] = $this->akses->is_fkrtl();
        $data['menu_admin'] = $this->akses->is_admin();
        $data['beranda'] = $this->akses->beranda();

        // Jumlah rujukan masuk yang belum diterima ditampilkan sebagai badge di menu rumah sakit.
        $data['rujukan_baru'] = $data['menu_fkrtl']
            ? $this->kunjungan->count_masuk_belum_diterima($this->akses->faskes_id())
            : 0;
        $data['page_title'] = isset($data['page_title']) ? $data['page_title'] : 'EMNIRA';
        $data['active_menu'] = isset($data['active_menu']) ? $data['active_menu'] : '';

        $this->load->view('template/layout', $data);
    }
}
