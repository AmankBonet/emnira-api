<?php

defined('BASEPATH') or exit('No direct script access allowed');

require_once APPPATH . 'core/Web_Controller.php';

class Users extends Web_Controller
{
    public function __construct()
    {
        parent::__construct();

        $this->load->model('web/User_model', 'web_users');
        $this->load->library('form_validation');
    }

    public function index()
    {
        $search = $this->input->get('q');
        $search = is_string($search) ? mb_substr(trim($search), 0, 255) : '';
        $page = $this->input->get('page');
        $page = is_string($page) && ctype_digit($page) ? max(1, (int) $page) : 1;
        $total = $this->web_users->count_filtered($search);
        $pages = max(1, (int) ceil($total / 15));
        $page = min($page, $pages);

        $this->render('web/users/index', [
            'page_title' => 'Manajemen pengguna',
            'active_menu' => 'users',
            'users' => $this->web_users->listing($search, 15, ($page - 1) * 15),
            'roles' => $this->web_users->role_options(),
            'faskes_options' => $this->web_users->faskes_options(),
            'search' => $search,
            'total' => $total,
            'page' => $page,
            'pages' => $pages,
            'form_state' => $this->session->flashdata('user_form'),
            'page_scripts' => ['assets/web/users.js']
        ]);
    }

    public function save()
    {
        $this->require_post();
        $raw_id = $this->input->post('id_user');
        if (!is_string($raw_id) || !preg_match('/^(0|[1-9][0-9]*)$/D', $raw_id) || (float) $raw_id > 2147483647) {
            show_error('ID pengguna tidak valid.', 400);
        }

        $id = (int) $raw_id;
        if ($id > 0 && !$this->web_users->find($id)) {
            show_404();
        }

        $data = [];
        foreach (['nama', 'username', 'id_faskes', 'id_role', 'id_active', 'password'] as $field) {
            $value = $this->input->post($field);
            if ($value !== null && !is_string($value)) {
                show_error('Format data tidak valid.', 400);
            }
            $data[$field] = $field === 'password' ? (string) $value : trim((string) $value);
        }

        $this->form_validation->set_data($data);
        $this->form_validation->set_rules('nama', 'Nama', 'required|max_length[255]');
        $this->form_validation->set_rules('username', 'Username', 'required|max_length[255]');
        $this->form_validation->set_rules('id_faskes', 'ID faskes', 'integer|greater_than[0]|less_than_equal_to[2147483647]');
        $this->form_validation->set_rules('id_role', 'ID role', 'integer|greater_than[0]|less_than_equal_to[2147483647]');
        $this->form_validation->set_rules('id_active', 'Status', 'required|in_list[0,1]');
        $this->form_validation->set_rules('password', 'Password', ($id === 0 ? 'required|' : '') . 'max_length[72]');
        $valid = $this->form_validation->run();
        $errors = array_values($this->form_validation->error_array());

        // Validasi relasi tetap diperlukan karena tabel pengguna belum memiliki foreign key.
        if ($valid) {
            if ($data['id_role'] !== '' && !$this->web_users->role_exists((int) $data['id_role'])) {
                $errors[] = 'Role yang dipilih tidak tersedia. Silakan pilih kembali.';
            }

            if ($data['id_faskes'] !== '' && !$this->web_users->faskes_exists((int) $data['id_faskes'])) {
                $errors[] = 'Faskes yang dipilih tidak tersedia. Silakan pilih kembali.';
            }
        }

        if (strlen($data['password']) > 72 || strpos($data['password'], "\0") !== false) {
            $errors[] = 'Password maksimal 72 byte dan tidak boleh mengandung karakter null.';
        }
        if ($this->web_users->username_exists($data['username'], $id)) {
            $errors[] = 'Username sudah digunakan pengguna lain.';
        }

        if (!$valid || $errors) {
            $this->form_error($id, $data, $errors);
            return;
        }

        $password = $data['password'];
        unset($data['password']);
        $data['id_faskes'] = $data['id_faskes'] === '' ? null : (int) $data['id_faskes'];
        $data['id_role'] = $data['id_role'] === '' ? null : (int) $data['id_role'];
        $data['id_active'] = (int) $data['id_active'];

        // Password kosong pada edit mempertahankan password yang sedang digunakan.
        if ($password !== '') {
            $data['password_hash'] = password_hash($password, PASSWORD_DEFAULT);
        }

        if (!$this->web_users->save($id, $data)) {
            $this->form_error($id, $data, ['Data gagal disimpan. Periksa ID faskes/role dan coba kembali.']);
            return;
        }

        $this->session->set_flashdata('success', $id > 0 ? 'Pengguna berhasil diperbarui.' : 'Pengguna berhasil ditambahkan.');
        redirect('web/users');
    }

    public function delete($id = '')
    {
        $this->require_post();
        if (!ctype_digit((string) $id) || (float) $id > 2147483647 || !$this->web_users->find((int) $id)) {
            show_404();
        }

        if ($this->web_users->delete((int) $id)) {
            $this->session->set_flashdata('success', 'Pengguna berhasil dihapus.');
        } else {
            $this->session->set_flashdata('error', 'Pengguna tidak dapat dihapus. Data mungkin masih digunakan oleh data lain.');
        }

        redirect('web/users');
    }

    private function form_error($id, array $data, array $errors)
    {
        // Password maupun hash tidak boleh disimpan ke flashdata atau dikirim ke view.
        unset($data['password'], $data['password_hash']);
        $data['id_user'] = $id;
        $this->session->set_flashdata('user_form', ['data' => $data, 'errors' => $errors]);
        redirect('web/users');
    }

    private function require_post()
    {
        if ($this->input->method() !== 'post') {
            $this->output->set_header('Allow: POST');
            show_error('Gunakan metode POST.', 405);
        }
    }
}
