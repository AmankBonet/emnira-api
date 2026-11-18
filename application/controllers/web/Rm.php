<?php

defined('BASEPATH') or exit('No direct script access allowed');

require_once APPPATH . 'core/Web_Controller.php';

class Rm extends Web_Controller
{
    const PER_PAGE = 15;

    // Rumus Naegele: HPL = HPHT + 280 hari (40 minggu).
    const DURASI_KEHAMILAN_HARI = 280;

    private $fields = [
        'no_rm',
        'nama',
        'alamat',
        'tgl_lahir',
        'tgl_reg',
        'lokasi',
        'id_faskes_perujuk',
        'id_faskes_tujuan',
        'hpht'
    ];

    private $faskes_fields = [
        'id_faskes_perujuk' => 'Faskes perujuk',
        'id_faskes_tujuan' => 'Faskes tujuan'
    ];

    public function __construct()
    {
        parent::__construct();

        $this->load->model('web/Kunjungan_model', 'kunjungan');
        $this->load->library('form_validation');
    }

    public function index()
    {
        $search = $this->input->get('q');
        $search = is_string($search) ? mb_substr(trim($search), 0, 255) : '';

        $page = $this->input->get('page');
        $page = is_string($page) && ctype_digit($page) ? max(1, (int) $page) : 1;

        $total = $this->kunjungan->count_filtered($search);
        $pages = max(1, (int) ceil($total / self::PER_PAGE));
        $page = min($page, $pages);

        $this->render('web/rm/index', [
            'page_title' => 'Identitas pasien',
            'active_menu' => 'rm',
            'patients' => $this->kunjungan->listing($search, self::PER_PAGE, ($page - 1) * self::PER_PAGE),
            'faskes_options' => $this->kunjungan->faskes_options(),
            'search' => $search,
            'total' => $total,
            'page' => $page,
            'pages' => $pages,
            'per_page' => self::PER_PAGE,
            'durasi_kehamilan' => self::DURASI_KEHAMILAN_HARI,
            'form_state' => $this->session->flashdata('patient_form'),
            'page_scripts' => [
                'assets/web/rm.js'
            ]
        ]);
    }

    public function save()
    {
        $this->require_post();

        $raw_id = $this->input->post('id_kunjungan');
        if (!is_string($raw_id) || !$this->is_valid_id($raw_id, true)) {
            show_error('ID kunjungan tidak valid.', 400);
        }

        $id = (int) $raw_id;
        if ($id > 0 && !$this->kunjungan->find($id)) {
            show_404();
        }

        $data = [];
        foreach ($this->fields as $field) {
            $value = $this->input->post($field);
            if ($value !== null && !is_string($value)) {
                show_error('Format data tidak valid.', 400);
            }

            $data[$field] = trim((string) $value);
        }

        $this->form_validation->set_data($data);
        $this->form_validation->set_rules('no_rm', 'No. RM', 'required|max_length[255]');
        $this->form_validation->set_rules('nama', 'Nama pasien', 'required|max_length[255]');
        $this->form_validation->set_rules('alamat', 'Alamat', 'max_length[65535]');
        $this->form_validation->set_rules('tgl_reg', 'Tanggal registrasi', 'required');
        $this->form_validation->set_rules('lokasi', 'Lokasi', 'max_length[255]');

        foreach ($this->faskes_fields as $field => $label) {
            $this->form_validation->set_rules(
                $field,
                $label,
                'integer|greater_than[0]|less_than_equal_to[2147483647]'
            );
        }

        $valid = $this->form_validation->run();
        $errors = array_values($this->form_validation->error_array());
        $errors = array_merge($errors, $this->validate_dates($data));

        // Faskes perujuk tidak memiliki foreign key, jadi keberadaannya dicek di sini.
        if ($valid) {
            foreach ($this->faskes_fields as $field => $label) {
                if (
                    $data[$field] !== ''
                    && !$this->kunjungan->faskes_exists((int) $data[$field])
                ) {
                    $errors[] = $label . ' yang dipilih tidak tersedia. Silakan pilih kembali.';
                }
            }
        }

        if (!$valid || $errors) {
            $this->form_error($id, $data, $errors);

            return;
        }

        // Kolom opsional disimpan sebagai NULL agar konsisten dengan data dari API.
        foreach ($data as $field => $value) {
            if ($value === '' && $field !== 'no_rm' && $field !== 'nama') {
                $data[$field] = null;
            }
        }

        foreach (array_keys($this->faskes_fields) as $field) {
            if ($data[$field] !== null) {
                $data[$field] = (int) $data[$field];
            }
        }

        // HPL dan usia kehamilan selalu dihitung ulang di server agar tidak bergantung pada browser.
        $data = array_merge($data, $this->calculate_pregnancy($data['hpht'], $data['tgl_reg']));

        if (!$this->kunjungan->save($id, $data)) {
            $this->form_error($id, $data, ['Data gagal disimpan. Periksa kembali isian dan coba lagi.']);

            return;
        }

        $this->session->set_flashdata(
            'success',
            $id > 0 ? 'Identitas pasien berhasil diperbarui.' : 'Identitas pasien berhasil ditambahkan.'
        );
        redirect('web/rm');
    }

    public function delete($id = '')
    {
        $this->require_post();

        if (!$this->is_valid_id((string) $id, false) || !$this->kunjungan->find((int) $id)) {
            show_404();
        }

        // t_rm tidak memiliki foreign key ke t_kunjungan, jadi relasinya dijaga di sini.
        if ($this->kunjungan->used_by_rm((int) $id)) {
            $this->session->set_flashdata(
                'error',
                'Identitas pasien tidak dapat dihapus karena sudah memiliki data rujukan.'
            );
            redirect('web/rm');

            return;
        }

        if ($this->kunjungan->delete((int) $id)) {
            $this->session->set_flashdata('success', 'Identitas pasien berhasil dihapus.');
        } else {
            $this->session->set_flashdata(
                'error',
                'Identitas pasien tidak dapat dihapus. Data mungkin masih digunakan oleh data lain.'
            );
        }

        redirect('web/rm');
    }

    private function validate_dates(array $data)
    {
        $errors = [];
        $today = date('Y-m-d');

        if ($data['tgl_reg'] !== '' && !$this->is_valid_date($data['tgl_reg'])) {
            $errors[] = 'Tanggal registrasi tidak valid.';
        }

        if ($data['tgl_lahir'] !== '') {
            if (!$this->is_valid_date($data['tgl_lahir'])) {
                $errors[] = 'Tanggal lahir tidak valid.';
            } elseif ($data['tgl_lahir'] > $today) {
                $errors[] = 'Tanggal lahir tidak boleh melebihi hari ini.';
            }
        }

        if ($data['hpht'] !== '') {
            if (!$this->is_valid_date($data['hpht'])) {
                $errors[] = 'HPHT tidak valid.';
            } elseif ($data['hpht'] > $today) {
                $errors[] = 'HPHT tidak boleh melebihi hari ini.';
            } elseif (
                $this->is_valid_date($data['tgl_reg'])
                && $data['hpht'] > $data['tgl_reg']
            ) {
                $errors[] = 'HPHT tidak boleh melebihi tanggal registrasi.';
            }
        }

        return $errors;
    }

    private function calculate_pregnancy($hpht, $tgl_reg)
    {
        if ($hpht === null) {
            return [
                'hpl' => null,
                'usia_kehamilan' => null
            ];
        }

        $start = DateTime::createFromFormat('!Y-m-d', $hpht);
        $hpl = (clone $start)->modify('+' . self::DURASI_KEHAMILAN_HARI . ' days');

        // Usia kehamilan dicatat per tanggal registrasi kunjungan, bukan per hari ini.
        $reference = DateTime::createFromFormat('!Y-m-d', $tgl_reg);
        $days = (int) $start->diff($reference)->days;

        return [
            'hpl' => $hpl->format('Y-m-d'),
            'usia_kehamilan' => intdiv($days, 7) . ' minggu ' . ($days % 7) . ' hari'
        ];
    }

    private function is_valid_id($value, $allow_zero)
    {
        $pattern = $allow_zero ? '/^(0|[1-9][0-9]*)$/D' : '/^[1-9][0-9]*$/D';

        return preg_match($pattern, $value) === 1 && (float) $value <= 2147483647;
    }

    private function is_valid_date($value)
    {
        $date = DateTime::createFromFormat('!Y-m-d', $value);

        return $date !== false && $date->format('Y-m-d') === $value;
    }

    private function form_error($id, array $data, array $errors)
    {
        $data['id_kunjungan'] = $id;
        $this->session->set_flashdata('patient_form', [
            'data' => $data,
            'errors' => $errors
        ]);
        redirect('web/rm');
    }

    private function require_post()
    {
        if ($this->input->method() !== 'post') {
            $this->output->set_header('Allow: POST');
            show_error('Gunakan metode POST.', 405);
        }
    }
}
