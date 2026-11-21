<?php

defined('BASEPATH') or exit('No direct script access allowed');

require_once APPPATH . 'core/Web_Controller.php';

/**
 * CRUD master data untuk administrator. Tabel dan kolom didefinisikan di config/master.php.
 */
class Master extends Web_Controller
{
    const PER_PAGE = 20;

    private $master;

    public function __construct()
    {
        parent::__construct();

        $this->require_menu('admin');

        $this->config->load('master');
        $this->master = $this->config->item('master');
        $this->load->model('web/Master_data_model', 'master_data');
    }

    public function index()
    {
        $ringkasan = [];
        foreach ($this->master as $key => $def) {
            $ringkasan[$key] = [
                'judul' => $def['judul'],
                'keterangan' => $def['keterangan'],
                'jumlah' => $this->master_data->count_all($def['tabel'])
            ];
        }

        $this->render('web/master/index', [
            'page_title' => 'Master data',
            'active_menu' => 'master',
            'ringkasan' => $ringkasan
        ]);
    }

    public function tabel($key = '')
    {
        $def = $this->definisi($key);

        $search = $this->input->get('q');
        $search = is_string($search) ? mb_substr(trim($search), 0, 255) : '';
        $filter = $this->input->get('f');
        $filter = is_string($filter) ? trim($filter) : '';

        $page = $this->input->get('page');
        $page = is_string($page) && ctype_digit($page) ? max(1, (int) $page) : 1;
        $total = $this->master_data->count($def, $search, $filter);
        $pages = max(1, (int) ceil($total / self::PER_PAGE));
        $page = min($page, $pages);

        $this->render('web/master/tabel', [
            'page_title' => $def['judul'],
            'active_menu' => 'master',
            'key' => $key,
            'def' => $def,
            'rows' => $this->master_data->listing($def, $search, $filter, self::PER_PAGE, ($page - 1) * self::PER_PAGE),
            'opsi_relasi' => $this->opsi_relasi($def),
            'search' => $search,
            'filter' => $filter,
            'total' => $total,
            'page' => $page,
            'pages' => $pages,
            'per_page' => self::PER_PAGE,
            'form_state' => $this->session->flashdata('master_form'),
            'page_scripts' => [
                'assets/web/master.js'
            ]
        ]);
    }

    public function save($key = '')
    {
        $this->require_post();
        $def = $this->definisi($key);
        $pk = $def['pk'];
        $pk_manual = !empty($def['pk_manual']);

        // pk_lama kosong = data baru; selain itu kunci data yang sedang diubah.
        $pk_lama = $this->input->post('pk_lama');
        $pk_lama = is_string($pk_lama) ? trim($pk_lama) : '';
        $id = $pk_lama === '' ? null : $pk_lama;

        if ($id !== null && !$this->master_data->find($def, $id)) {
            show_404();
        }

        $opsi_relasi = $this->opsi_relasi($def);
        $data = [];
        $errors = [];

        foreach ($def['kolom'] as $kolom => $spec) {
            // Kunci manual tidak boleh diubah karena dipakai sebagai referensi di tabel lain.
            if ($pk_manual && $kolom === $pk && $id !== null) {
                continue;
            }

            $nilai = $this->nilai_kolom($kolom, $spec, $opsi_relasi, $errors);
            $data[$kolom] = $nilai;
        }

        $this->validasi_tambahan($def, $id, $data, $errors);

        if ($errors) {
            $this->form_error($key, $pk_lama, $data, $errors);

            return;
        }

        if (!$this->master_data->save($def, $id, $data)) {
            $this->form_error($key, $pk_lama, $data, ['Data gagal disimpan. Periksa kembali isian (kemungkinan melanggar aturan database).']);

            return;
        }

        $this->session->set_flashdata('success', $def['judul'] . ($id !== null ? ' berhasil diperbarui.' : ' berhasil ditambahkan.'));
        redirect('web/master/' . $key);
    }

    public function delete($key = '', $id = '')
    {
        $this->require_post();
        $def = $this->definisi($key);
        $id = rawurldecode((string) $id);

        $row = $this->master_data->find($def, $id);
        if (!$row) {
            show_404();
        }

        if (isset($def['terkunci']) && in_array((int) $id, $def['terkunci'], true)) {
            $this->session->set_flashdata('error', 'Data ini dipakai oleh aplikasi dan tidak dapat dihapus.');
            redirect('web/master/' . $key);
        }

        // Data yang masih dipakai tidak dihapus; tawarkan nonaktif bila tabel mendukung.
        $pemakaian = $this->master_data->pemakaian($def, $id);
        if ($pemakaian) {
            $rincian = [];
            foreach ($pemakaian as $keterangan => $jumlah) {
                $rincian[] = $jumlah . ' ' . $keterangan;
            }

            $saran = isset($def['kolom']['id_active']) ? ' Nonaktifkan saja agar tidak muncul sebagai pilihan.' : '';
            $this->session->set_flashdata('error', 'Tidak dapat dihapus karena masih dipakai: ' . implode(', ', $rincian) . '.' . $saran);
            redirect('web/master/' . $key);
        }

        $berhasil = $this->master_data->delete($def, $id);
        $this->session->set_flashdata(
            $berhasil ? 'success' : 'error',
            $berhasil ? $def['judul'] . ' berhasil dihapus.' : 'Data gagal dihapus karena masih terhubung dengan data lain.'
        );
        redirect('web/master/' . $key);
    }

    private function nilai_kolom($kolom, array $spec, array $opsi_relasi, array &$errors)
    {
        $label = $spec['label'];
        $wajib = !empty($spec['wajib']);
        $raw = $this->input->post($kolom);

        if ($raw !== null && !is_string($raw)) {
            show_error('Format data tidak valid.', 400);
        }

        $raw = trim((string) $raw);

        if ($spec['tipe'] === 'ya_tidak') {
            return $raw === '1' ? 1 : 0;
        }

        if ($raw === '') {
            if ($wajib && !isset($spec['bawaan'])) {
                $errors[] = $label . ' wajib diisi.';
            }

            return isset($spec['bawaan']) ? $spec['bawaan'] : null;
        }

        if ($spec['tipe'] === 'angka') {
            if (!preg_match('/^[0-9]{1,6}$/D', $raw) || (int) $raw < $spec['min'] || (int) $raw > $spec['max']) {
                $errors[] = $label . ' harus angka ' . $spec['min'] . '–' . $spec['max'] . '.';
            }

            return (int) $raw;
        }

        if ($spec['tipe'] === 'pilihan' && !isset($spec['pilihan'][$raw])) {
            $errors[] = $label . ': pilihan tidak valid.';
        }

        if ($spec['tipe'] === 'relasi' && !isset($opsi_relasi[$kolom][$raw])) {
            $errors[] = $label . ': pilihan tidak tersedia.';
        }

        if (isset($spec['maks']) && mb_strlen($raw) > $spec['maks']) {
            $errors[] = $label . ' maksimal ' . $spec['maks'] . ' karakter.';
        }

        return $raw;
    }

    private function validasi_tambahan(array $def, $id, array $data, array &$errors)
    {
        $pk = $def['pk'];

        // Kunci manual hanya huruf kecil, angka, dan garis bawah agar aman dipakai di URL dan kode.
        if (!empty($def['pk_manual']) && $id === null) {
            if ($data[$pk] !== null && !preg_match('/^[a-z0-9_]{1,60}$/D', $data[$pk])) {
                $errors[] = $def['kolom'][$pk]['label'] . ' hanya boleh huruf kecil, angka, dan garis bawah.';
            } elseif ($data[$pk] !== null && $this->master_data->find($def, $data[$pk])) {
                $errors[] = $def['kolom'][$pk]['label'] . ' sudah dipakai.';
            }
        }

        foreach (isset($def['unik']) ? $def['unik'] : [] as $kolom) {
            if ($data[$kolom] !== null && $this->master_data->nilai_dipakai($def, $kolom, $data[$kolom], $id)) {
                $errors[] = $def['kolom'][$kolom]['label'] . ' "' . $data[$kolom] . '" sudah dipakai.';
            }
        }

        $pasangan_dicek = [];

        foreach ($def['kolom'] as $kolom => $spec) {
            if (!array_key_exists($kolom, $data)) {
                continue;
            }

            // Contoh: kelompok alternatif wajib diisi bila sifat = alternatif.
            if (isset($spec['wajib_jika'])) {
                list($kolom_acuan, $nilai_acuan) = $spec['wajib_jika'];
                if ($data[$kolom_acuan] === $nilai_acuan && $data[$kolom] === null) {
                    $errors[] = $spec['label'] . ' wajib diisi bila ' . mb_strtolower($def['kolom'][$kolom_acuan]['label']) . ' "' . $nilai_acuan . '".';
                }
            }

            // Contoh: kelompok dosis dan dosis ke- harus diisi bersamaan (dicek sekali per pasangan).
            if (isset($spec['pasangan']) && !isset($pasangan_dicek[$kolom])) {
                $pasangan_dicek[$spec['pasangan']] = true;

                if (($data[$kolom] === null) !== ($data[$spec['pasangan']] === null)) {
                    $errors[] = $spec['label'] . ' dan ' . mb_strtolower($def['kolom'][$spec['pasangan']]['label']) . ' harus diisi bersamaan.';
                }
            }
        }

        $errors = array_values(array_unique($errors));
    }

    private function opsi_relasi(array $def)
    {
        $opsi = [];
        foreach ($def['kolom'] as $kolom => $spec) {
            if ($spec['tipe'] === 'relasi') {
                $opsi[$kolom] = $this->master_data->relasi_options($spec['relasi']);
            }
        }

        return $opsi;
    }

    private function definisi($key)
    {
        if (!is_string($key) || !isset($this->master[$key])) {
            show_404();
        }

        return $this->master[$key];
    }

    private function form_error($key, $pk_lama, array $data, array $errors)
    {
        $this->session->set_flashdata('master_form', [
            'pk_lama' => $pk_lama,
            'data' => $data,
            'errors' => $errors
        ]);
        redirect('web/master/' . $key);
    }

    private function require_post()
    {
        if ($this->input->method() !== 'post') {
            $this->output->set_header('Allow: POST');
            show_error('Gunakan metode POST.', 405);
        }
    }
}
