<?php

defined('BASEPATH') or exit('No direct script access allowed');

class Login extends CI_Controller
{
    public function __construct()
    {
        parent::__construct();

        $this->load->helper('form');
        $this->load->model('web/Web_auth_model', 'web_auth');
        $this->output->set_header('Cache-Control: no-store');
    }

    public function index()
    {
        if (!in_array($this->input->method(), ['get', 'post'], true)) {
            $this->output->set_header('Allow: GET, POST');
            show_error('Metode tidak diizinkan.', 405);
        }

        $id = $this->session->userdata('web_user_id');
        $aktif = $id ? $this->web_auth->active_user($id) : null;
        if ($aktif) {
            redirect($this->beranda($aktif));
            return;
        }

        $error = null;
        $username = '';
        if ($this->input->method() === 'post') {
            $username = $this->input->post('username');
            $password = $this->input->post('password');
            $username = is_string($username) ? trim($username) : '';
            $user = null;

            if ($username !== '' && is_string($password) && $password !== '') {
                $user = $this->web_auth->authenticate($username, $password);
            }

            if ($user) {
                // Ganti ID session saat login untuk mencegah session fixation.
                $this->session->sess_regenerate(true);
                $this->session->set_userdata('web_user_id', (int) $user['id_user']);
                redirect($this->beranda($user));
                return;
            }

            $error = 'Username/password tidak sesuai atau akun tidak aktif.';
            $this->output->set_status_header(401);
        }

        $this->load->view('web/auth/login', [
            'page_title' => 'Login',
            'username' => $username,
            'error' => $error
        ]);
    }

    /**
     * Halaman awal sesuai role: FKTP ke Identitas pasien, FKRTL ke Rujukan masuk.
     */
    private function beranda(array $user)
    {
        $this->load->library('akses_rujukan', $user, 'akses');

        return $this->akses->beranda();
    }

    public function logout()
    {
        if ($this->input->method() !== 'post') {
            $this->output->set_header('Allow: POST');
            show_error('Gunakan metode POST.', 405);
        }

        $this->session->sess_destroy();
        redirect('web/login');
    }
}
