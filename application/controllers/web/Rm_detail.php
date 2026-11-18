<?php

defined('BASEPATH') or exit('No direct script access allowed');

require_once APPPATH . 'core/Web_Controller.php';

/**
 * Halaman entri rekam medis per kunjungan pasien.
 * Setiap tab memiliki form dan endpoint simpan sendiri agar isian tidak saling menimpa.
 */
class Rm_detail extends Web_Controller
{
    const MAX_GRAVIDA = 20;

    private $tabs = [
        'identitas' => 'Identitas',
        'obstetri' => 'Status obstetri',
        'riwayat' => 'Riwayat kehamilan',
        'kondisi' => 'Kondisi medis ibu'
    ];

    private $gpa_fields = [
        'gravida' => 'Gravida',
        'partus' => 'Partus',
        'abortus' => 'Abortus',
        'anak_hidup' => 'Anak hidup'
    ];

    // Kelompok pilihan yang boleh dipakai pada riwayat per kehamilan.
    private $kelompok_riwayat = [
        'penyakit_hamil',
        'komplikasi_persalinan_nifas'
    ];

    public function __construct()
    {
        parent::__construct();

        $this->config->load('obstetri');
        $this->load->model('web/Kunjungan_model', 'kunjungan');
        $this->load->model('web/Obstetri_model', 'obstetri');
    }

    public function index($id = '')
    {
        $patient = $this->load_patient($id);
        $id = (int) $patient['id_kunjungan'];

        $tab = $this->input->get('tab');
        $tab = is_string($tab) && isset($this->tabs[$tab]) ? $tab : 'identitas';

        $gpa = $this->obstetri->find_gpa($id);
        $riwayat = $this->obstetri->riwayat($id);
        $pilihan_map = $this->obstetri->pilihan_map();

        $form_state = $this->session->flashdata('rm_detail_form');
        if (!$form_state || $form_state['tab'] !== $tab) {
            $form_state = null;
        }

        $this->render('web/rm/detail', [
            'page_title' => 'Rekam medis · ' . $patient['nama'],
            'active_menu' => 'rm',
            'patient' => $patient,
            'tabs' => $this->tabs,
            'tab' => $tab,
            'gpa' => $gpa,
            'riwayat' => $riwayat,
            'flags' => $this->obstetri->analisis($gpa, $riwayat, $pilihan_map, $patient['tgl_reg']),
            'pilihan_map' => $pilihan_map,
            'pilihan_penyakit' => $this->obstetri->pilihan('penyakit_hamil'),
            'pilihan_komplikasi' => $this->obstetri->pilihan('komplikasi_persalinan_nifas'),
            'pilihan_kondisi_ibu' => $this->obstetri->pilihan('kondisi_ibu'),
            'kondisi_ibu' => $this->obstetri->kondisi_ibu($id),
            'riwayat_options' => $this->config->item('riwayat_options'),
            'luaran_tanpa_bayi' => $this->config->item('luaran_tanpa_bayi'),
            'max_gravida' => self::MAX_GRAVIDA,
            'form_state' => $form_state,
            'page_scripts' => [
                'assets/web/rm-detail.js'
            ]
        ]);
    }

    public function save_gpa($id = '')
    {
        $this->require_post();
        $patient = $this->load_patient($id);
        $id = (int) $patient['id_kunjungan'];

        $data = [];
        $errors = [];

        foreach ($this->gpa_fields as $field => $label) {
            $value = $this->input->post($field);

            if (!is_string($value) || !preg_match('/^[0-9]{1,2}$/D', $value) || (int) $value > self::MAX_GRAVIDA) {
                $errors[] = $label . ' harus berupa angka 0–' . self::MAX_GRAVIDA . '.';
                $data[$field] = is_string($value) ? $value : '';

                continue;
            }

            $data[$field] = (int) $value;
        }

        // Aturan konsistensi GPA sama dengan CHECK di database.
        if (!$errors) {
            if ($data['gravida'] < 1) {
                $errors[] = 'Gravida minimal 1 (termasuk kehamilan saat ini).';
            }

            if ($data['partus'] + $data['abortus'] >= $data['gravida']) {
                $errors[] = 'Partus + abortus harus lebih kecil dari gravida.';
            }
        }

        if ($errors) {
            $this->form_error($id, 'obstetri', $data, $errors);

            return;
        }

        if (!$this->obstetri->save_gpa($id, $data)) {
            $this->form_error($id, 'obstetri', $data, ['Status obstetri gagal disimpan. Coba lagi.']);

            return;
        }

        $this->session->set_flashdata('success', 'Status obstetri berhasil disimpan.');
        $this->redirect_tab($id, $data['gravida'] > 1 ? 'riwayat' : 'obstetri');
    }

    public function save_riwayat($id = '')
    {
        $this->require_post();
        $patient = $this->load_patient($id);
        $id = (int) $patient['id_kunjungan'];

        $gpa = $this->obstetri->find_gpa($id);
        $jumlah = $gpa ? (int) $gpa['gravida'] - 1 : 0;

        if ($jumlah < 1) {
            $this->session->set_flashdata('error', 'Isi status obstetri dengan gravida lebih dari 1 terlebih dahulu.');
            $this->redirect_tab($id, 'obstetri');

            return;
        }

        $input = $this->input->post('riwayat');
        if (!is_array($input)) {
            show_error('Format data tidak valid.', 400);
        }

        $options = $this->config->item('riwayat_options');
        $tanpa_bayi = $this->config->item('luaran_tanpa_bayi');
        $pilihan_map = $this->obstetri->pilihan_map();
        $tahun_maks = (int) substr($patient['tgl_reg'] ?: date('Y-m-d'), 0, 4);

        $rows = [];
        $errors = [];

        for ($ke = 1; $ke <= $jumlah; $ke++) {
            $raw = isset($input[$ke]) && is_array($input[$ke]) ? $input[$ke] : [];
            $label = 'Kehamilan ke-' . $ke;
            $row = [];

            foreach ($options as $field => $choices) {
                $value = isset($raw[$field]) && is_string($raw[$field]) ? trim($raw[$field]) : '';

                if ($value !== '' && !isset($choices[$value])) {
                    $errors[] = $label . ': pilihan ' . str_replace('_', ' ', $field) . ' tidak valid.';
                    $value = '';
                }

                $row[$field] = $value === '' ? null : $value;
            }

            $tahun = isset($raw['tahun_persalinan']) && is_string($raw['tahun_persalinan'])
                ? trim($raw['tahun_persalinan'])
                : '';

            if ($tahun !== '' && (!preg_match('/^[0-9]{4}$/D', $tahun) || $tahun < 1950 || $tahun > $tahun_maks)) {
                $errors[] = $label . ': tahun persalinan harus antara 1950 dan ' . $tahun_maks . '.';
            }

            $row['tahun_persalinan'] = $tahun === '' ? null : (int) $tahun;

            $keterangan = isset($raw['keterangan']) && is_string($raw['keterangan'])
                ? trim($raw['keterangan'])
                : '';

            if (mb_strlen($keterangan) > 2000) {
                $errors[] = $label . ': keterangan maksimal 2000 karakter.';
            }

            $row['keterangan'] = $keterangan === '' ? null : $keterangan;

            // Data bayi tidak berlaku bila kehamilan tidak berakhir dengan persalinan.
            if (in_array($row['luaran'], $tanpa_bayi, true)) {
                $row['jenis_kelamin'] = null;
                $row['berat_lahir_kategori'] = null;
                $row['kondisi_bayi'] = null;
            }

            $row['kondisi'] = [];
            $kondisi = isset($raw['kondisi']) && is_array($raw['kondisi']) ? $raw['kondisi'] : [];

            foreach ($kondisi as $kode) {
                if (
                    !is_string($kode)
                    || !isset($pilihan_map[$kode])
                    || !in_array($pilihan_map[$kode]['kelompok'], $this->kelompok_riwayat, true)
                ) {
                    $errors[] = $label . ': pilihan penyakit/komplikasi tidak valid.';

                    continue;
                }

                $row['kondisi'][$kode] = $kode;
            }

            $row['kondisi'] = array_values($row['kondisi']);
            $rows[$ke] = $row;
        }

        if ($errors) {
            $this->form_error($id, 'riwayat', ['riwayat' => $rows], array_values(array_unique($errors)));

            return;
        }

        if (!$this->obstetri->save_riwayat($id, $rows)) {
            $this->form_error($id, 'riwayat', ['riwayat' => $rows], ['Riwayat kehamilan gagal disimpan. Coba lagi.']);

            return;
        }

        $this->session->set_flashdata('success', 'Riwayat kehamilan berhasil disimpan.');
        $this->redirect_tab($id, 'riwayat');
    }

    public function save_kondisi($id = '')
    {
        $this->require_post();
        $patient = $this->load_patient($id);
        $id = (int) $patient['id_kunjungan'];

        $kode_list = $this->input->post('kondisi');
        $catatan_list = $this->input->post('catatan');
        $kode_list = is_array($kode_list) ? $kode_list : [];
        $catatan_list = is_array($catatan_list) ? $catatan_list : [];

        $pilihan_map = $this->obstetri->pilihan_map();
        $items = [];
        $errors = [];

        foreach ($kode_list as $kode) {
            if (
                !is_string($kode)
                || !isset($pilihan_map[$kode])
                || $pilihan_map[$kode]['kelompok'] !== 'kondisi_ibu'
            ) {
                $errors[] = 'Pilihan kondisi medis tidak valid.';

                continue;
            }

            $catatan = isset($catatan_list[$kode]) && is_string($catatan_list[$kode])
                ? trim($catatan_list[$kode])
                : '';

            if (mb_strlen($catatan) > 255) {
                $errors[] = $pilihan_map[$kode]['label'] . ': catatan maksimal 255 karakter.';
            }

            $items[$kode] = $catatan === '' ? null : $catatan;
        }

        if ($errors) {
            $this->form_error($id, 'kondisi', ['kondisi' => $items], array_values(array_unique($errors)));

            return;
        }

        if (!$this->obstetri->save_kondisi_ibu($id, $items)) {
            $this->form_error($id, 'kondisi', ['kondisi' => $items], ['Kondisi medis ibu gagal disimpan. Coba lagi.']);

            return;
        }

        $this->session->set_flashdata('success', 'Kondisi medis ibu berhasil disimpan.');
        $this->redirect_tab($id, 'kondisi');
    }

    private function load_patient($id)
    {
        if (!preg_match('/^[1-9][0-9]*$/D', (string) $id) || (float) $id > 2147483647) {
            show_404();
        }

        $patient = $this->kunjungan->find_detail((int) $id);
        if (!$patient) {
            show_404();
        }

        return $patient;
    }

    private function form_error($id, $tab, array $data, array $errors)
    {
        $this->session->set_flashdata('rm_detail_form', [
            'tab' => $tab,
            'data' => $data,
            'errors' => $errors
        ]);
        $this->redirect_tab($id, $tab);
    }

    private function redirect_tab($id, $tab)
    {
        redirect('web/rm/' . $id . '?tab=' . $tab);
    }

    private function require_post()
    {
        if ($this->input->method() !== 'post') {
            $this->output->set_header('Allow: POST');
            show_error('Gunakan metode POST.', 405);
        }
    }
}
