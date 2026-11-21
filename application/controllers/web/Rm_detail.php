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
        'kondisi' => 'Kondisi medis ibu',
        'vital' => 'Tanda vital & MEOWS',
        'stabilisasi' => 'Stabilisasi pra-rujukan'
    ];

    // Data obstetri pendukung pada observasi (di luar skor MEOWS): [label, min, max, desimal].
    private $observasi_angka = [
        'berat_badan_kg' => ['Berat badan ibu', 20, 300, 2],
        'tfu_cm' => ['Tinggi fundus uteri', 0, 60, 1],
        'djj_per_menit' => ['Denyut jantung janin', 0, 250, 0]
    ];

    // Toleransi jam perangkat yang sedikit berbeda dengan server.
    const TOLERANSI_WAKTU_DETIK = 300;

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
        $this->config->load('stabilisasi');
        $this->config->load('rujukan_status');
        $this->load->model('web/Kunjungan_model', 'kunjungan');
        $this->load->model('web/Obstetri_model', 'obstetri');
        $this->load->model('web/Observasi_model', 'observasi');
        $this->load->model('web/Stabilisasi_model', 'stabilisasi');
        $this->load->library('meows');
    }

    public function index($id = '')
    {
        $patient = $this->load_patient($id);
        $id = (int) $patient['id_kunjungan'];

        $tab = $this->input->get('tab');
        $tab = is_string($tab) && isset($this->tabs[$tab]) ? $tab : 'identitas';

        $gpa = $this->obstetri->find_gpa($id);
        $riwayat = $this->obstetri->riwayat($id);
        $kondisi_ibu = $this->obstetri->kondisi_ibu($id);
        $pilihan_map = $this->obstetri->pilihan_map();

        $observasi = $this->observasi->listing($id);
        $observasi_terakhir = $observasi ? $observasi[0] : null;

        $diagnosis_kunjungan = $this->stabilisasi->diagnosis_kunjungan($id);
        $stabilisasi_items = $this->stabilisasi->items(array_keys($diagnosis_kunjungan));
        $pelaksanaan = $this->stabilisasi->pelaksanaan($id);
        $trigger_merah = $this->stabilisasi->trigger_merah($id);
        $stabilisasi_ringkasan = $this->stabilisasi->ringkasan(
            $stabilisasi_items,
            $pelaksanaan,
            $trigger_merah,
            time()
        );

        // Urutan penanda: MEOWS dan target waktu stabilisasi paling mendesak, lalu riwayat.
        $flags = $this->obstetri->analisis($gpa, $riwayat, $kondisi_ibu, $pilihan_map, $patient['tgl_reg']);
        $flags = array_merge(
            $this->meows->flags($observasi_terakhir),
            $stabilisasi_ringkasan['peringatan'],
            $flags
        );

        $aturan_otomatis = [];
        foreach ($stabilisasi_items as $id_item => $item) {
            $aturan_otomatis[$id_item] = $this->stabilisasi->evaluasi_aturan($item['kode_aturan'], $observasi_terakhir);
        }

        $form_state = $this->session->flashdata('rm_detail_form');
        if (!$form_state || $form_state['tab'] !== $tab) {
            $form_state = null;
        }

        $meows_rules = $this->meows->rules();
        $ambang = [];
        foreach (array_keys($meows_rules['parameter']) as $field) {
            $ambang[$field] = $this->meows->ambang_teks($field);
        }

        $peran = $this->akses->peran($patient);

        $this->render('web/rm/detail', [
            'page_title' => 'Rekam medis · ' . $patient['nama'],
            // Faskes tujuan membuka halaman ini dari menu Rujukan masuk.
            'active_menu' => $peran === 'tujuan' ? 'rujukan' : 'rm',
            'patient' => $patient,
            'akses_rm' => [
                'peran' => $peran,
                'edit' => $this->akses->can_edit($patient),
                'observasi' => $this->akses->can_add_observasi($patient),
                'kirim' => $this->akses->can_send($patient),
                'batal_kirim' => $this->akses->can_cancel_send($patient)
            ],
            'status_rujukan' => $this->config->item('status_rujukan'),
            'tabs' => $this->tabs,
            'tab' => $tab,
            'gpa' => $gpa,
            'riwayat' => $riwayat,
            'flags' => $flags,
            'observasi' => $observasi,
            'observasi_terakhir' => $observasi_terakhir,
            // Zona per parameter untuk kartu observasi terakhir; skor total tetap memakai nilai tersimpan.
            'observasi_terakhir_hasil' => $observasi_terakhir ? $this->meows->hitung($observasi_terakhir) : null,
            'meows_rules' => $meows_rules,
            'meows_ambang' => $ambang,
            'observasi_pilihan' => $this->config->item('observasi_pilihan'),
            'pilihan_map' => $pilihan_map,
            'pilihan_penyakit' => $this->obstetri->pilihan('penyakit_hamil'),
            'pilihan_komplikasi' => $this->obstetri->pilihan('komplikasi_persalinan_nifas'),
            'pilihan_kondisi_ibu' => $this->obstetri->pilihan('kondisi_ibu'),
            'kondisi_ibu' => $kondisi_ibu,
            'riwayat_options' => $this->config->item('riwayat_options'),
            'luaran_tanpa_bayi' => $this->config->item('luaran_tanpa_bayi'),
            'max_gravida' => self::MAX_GRAVIDA,
            'diagnosis_options' => $this->stabilisasi->diagnosis_options(),
            'diagnosis_kunjungan' => $diagnosis_kunjungan,
            'stabilisasi_items' => $stabilisasi_items,
            'pelaksanaan' => $pelaksanaan,
            'trigger_merah' => $trigger_merah,
            'stabilisasi_ringkasan' => $stabilisasi_ringkasan,
            'aturan_otomatis' => $aturan_otomatis,
            'stabilisasi_komponen' => $this->config->item('stabilisasi_komponen'),
            'stabilisasi_status' => $this->config->item('stabilisasi_status'),
            'stabilisasi_status_beralasan' => $this->config->item('stabilisasi_status_beralasan'),
            'form_state' => $form_state,
            'page_scripts' => [
                'assets/web/rm-detail.js'
            ]
        ]);
    }

    public function save_gpa($id = '')
    {
        $this->require_post();
        $patient = $this->load_patient($id, 'edit');
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
        $patient = $this->load_patient($id, 'edit');
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
        $patient = $this->load_patient($id, 'edit');
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

            // Contoh: alergi obat tidak berguna tanpa nama obat penyebabnya.
            if ((int) $pilihan_map[$kode]['wajib_catatan'] === 1 && $catatan === '') {
                $errors[] = $pilihan_map[$kode]['label'] . ': catatan wajib diisi.';
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

    public function save_vital($id = '')
    {
        $this->require_post();
        $patient = $this->load_patient($id, 'observasi');
        $id = (int) $patient['id_kunjungan'];

        $rules = $this->meows->rules();
        $pilihan_pendukung = $this->config->item('observasi_pilihan');
        $data = [];
        $errors = [];

        $waktu = $this->input->post('waktu_observasi');
        $waktu = is_string($waktu) ? trim($waktu) : '';
        $waktu_obj = DateTime::createFromFormat('!Y-m-d\TH:i', $waktu);
        $data['waktu_observasi'] = $waktu;

        if (!$waktu_obj || $waktu_obj->format('Y-m-d\TH:i') !== $waktu) {
            $errors[] = 'Waktu observasi tidak valid.';
        } elseif ($waktu_obj->getTimestamp() > time() + self::TOLERANSI_WAKTU_DETIK) {
            $errors[] = 'Waktu observasi tidak boleh melebihi waktu sekarang.';
        }

        // Parameter MEOWS: angka divalidasi terhadap batas config, pilihan terhadap daftar config.
        foreach ($rules['parameter'] as $field => $parameter) {
            $raw = $this->input->post($field);
            $raw = is_string($raw) ? trim(str_replace(',', '.', $raw)) : '';

            if ($raw === '') {
                if ($parameter['wajib']) {
                    $errors[] = $parameter['label'] . ' wajib diisi.';
                }

                $data[$field] = null;

                continue;
            }

            if ($parameter['tipe'] === 'pilihan') {
                if (!isset($parameter['pilihan'][$raw])) {
                    $errors[] = $parameter['label'] . ': pilihan tidak valid.';
                }

                $data[$field] = $raw;

                continue;
            }

            $desimal = $parameter['step'] < 1 ? 1 : 0;
            $data[$field] = $raw;

            if (!$this->is_valid_number($raw, $parameter['min'], $parameter['max'])) {
                $errors[] = $parameter['label'] . ' harus angka ' . $parameter['min'] . '–' . $parameter['max'] . '.';

                continue;
            }

            $data[$field] = round((float) $raw, $desimal);
        }

        foreach ($this->observasi_angka as $field => list($label, $min, $max, $desimal)) {
            $raw = $this->input->post($field);
            $raw = is_string($raw) ? trim(str_replace(',', '.', $raw)) : '';
            $data[$field] = $raw === '' ? null : $raw;

            if ($raw !== '' && !$this->is_valid_number($raw, $min, $max)) {
                $errors[] = $label . ' harus angka ' . $min . '–' . $max . '.';

                continue;
            }

            if ($raw !== '') {
                $data[$field] = round((float) $raw, $desimal);
            }
        }

        foreach ($pilihan_pendukung as $field => $choices) {
            $raw = $this->input->post($field);
            $raw = is_string($raw) ? trim($raw) : '';

            if ($raw !== '' && !isset($choices[$raw])) {
                $errors[] = 'Pilihan ' . str_replace('_', ' ', $field) . ' tidak valid.';
            }

            $data[$field] = $raw === '' ? null : $raw;
        }

        if (
            !$errors
            && $data['td_sistolik_mmhg'] !== null
            && $data['td_diastolik_mmhg'] !== null
            && $data['td_diastolik_mmhg'] >= $data['td_sistolik_mmhg']
        ) {
            $errors[] = 'Tekanan darah diastolik harus lebih kecil dari sistolik.';
        }

        if ($errors) {
            $this->form_error($id, 'vital', $data, $errors);

            return;
        }

        // Skor disimpan bersama versi aturan agar hasil lama tetap dapat ditelusuri.
        $hasil = $this->meows->hitung($data);
        $data['waktu_observasi'] = $waktu_obj->format('Y-m-d H:i:00');
        $data['id_kunjungan'] = $id;
        $data['id_user_pencatat'] = (int) $this->web_user['id_user'];
        $data['meows_skor'] = $hasil['skor'];
        $data['meows_jumlah_merah'] = $hasil['jumlah_merah'];
        $data['meows_jumlah_kuning'] = $hasil['jumlah_kuning'];
        $data['meows_zona'] = $hasil['zona'];
        $data['meows_parameter_terisi'] = $hasil['terisi'];
        $data['meows_versi_aturan'] = $this->meows->versi();

        if ($data['kepala_masuk_panggul'] !== null) {
            $data['kepala_masuk_panggul'] = (int) $data['kepala_masuk_panggul'];
        }

        if (!$this->observasi->insert($data)) {
            $this->form_error($id, 'vital', $data, ['Observasi gagal disimpan. Coba lagi.']);

            return;
        }

        $this->session->set_flashdata(
            'success',
            'Observasi tersimpan. MEOWS ' . $hasil['skor'] . ' (zona ' . $hasil['zona'] . ').'
        );
        $this->redirect_tab($id, 'vital');
    }

    public function delete_vital($id = '', $id_observasi = '')
    {
        $this->require_post();
        $patient = $this->load_patient($id, 'edit');
        $id = (int) $patient['id_kunjungan'];

        if (
            !preg_match('/^[1-9][0-9]*$/D', (string) $id_observasi)
            || !$this->observasi->find($id, (int) $id_observasi)
        ) {
            show_404();
        }

        if ($this->observasi->delete($id, (int) $id_observasi)) {
            $this->session->set_flashdata('success', 'Observasi berhasil dihapus.');
        } else {
            $this->session->set_flashdata('error', 'Observasi gagal dihapus.');
        }

        $this->redirect_tab($id, 'vital');
    }

    public function save_diagnosis($id = '')
    {
        $this->require_post();
        $patient = $this->load_patient($id, 'edit');
        $id = (int) $patient['id_kunjungan'];

        $input = $this->input->post('diagnosis');
        $input = is_array($input) ? $input : [];
        $options = array_column($this->stabilisasi->diagnosis_options(), null, 'id_diagnosis');
        $diagnosis_ids = [];

        foreach ($input as $value) {
            if (!is_string($value) || !ctype_digit($value) || !isset($options[(int) $value])) {
                show_error('Diagnosa tidak valid.', 400);
            }

            $diagnosis_ids[(int) $value] = (int) $value;
        }

        if (!$this->stabilisasi->save_diagnosis($id, array_values($diagnosis_ids), (int) $this->web_user['id_user'])) {
            $this->session->set_flashdata('error', 'Diagnosa kerja gagal disimpan. Coba lagi.');
        } else {
            $this->session->set_flashdata('success', 'Diagnosa kerja berhasil disimpan.');
        }

        $this->redirect_tab($id, 'stabilisasi');
    }

    public function save_stabilisasi($id = '')
    {
        $this->require_post();
        $patient = $this->load_patient($id, 'edit');
        $id = (int) $patient['id_kunjungan'];

        $items = $this->stabilisasi->items(array_keys($this->stabilisasi->diagnosis_kunjungan($id)));
        if (!$items) {
            $this->session->set_flashdata('error', 'Pilih diagnosa kerja terlebih dahulu.');
            $this->redirect_tab($id, 'stabilisasi');

            return;
        }

        $input = $this->input->post('item');
        $input = is_array($input) ? $input : [];
        $status_labels = $this->config->item('stabilisasi_status');
        $beralasan = $this->config->item('stabilisasi_status_beralasan');

        $state = [];
        $rows = [];
        $errors = [];

        foreach ($items as $id_item => $item) {
            $raw = isset($input[$id_item]) && is_array($input[$id_item]) ? $input[$id_item] : [];
            $value = function ($key) use ($raw) {
                return isset($raw[$key]) && is_string($raw[$key]) ? trim($raw[$key]) : '';
            };

            $label = mb_strimwidth($item['teks_standar'], 0, 60, '…');
            $status = $value('status') ?: 'belum';
            $detail = $value('detail_tindakan');
            $alasan = $value('alasan');
            $waktu = $value('waktu_pelaksanaan');

            $state[$id_item] = [
                'status' => $status,
                'detail_tindakan' => $detail,
                'alasan' => $alasan,
                'waktu_pelaksanaan' => $waktu
            ];

            // Status yang sah bergantung pada jenis butir; "tidak diperlukan" khusus butir opsional.
            $allowed = array_keys($status_labels[$item['jenis']]);
            if ($item['jenis'] === 'tindakan' && $item['sifat'] !== 'wajib') {
                $allowed[] = 'tidak_diperlukan';
            }

            if (!in_array($status, $allowed, true)) {
                $errors[] = $label . ': status tidak valid.';

                continue;
            }

            if (mb_strlen($detail) > 500) {
                $errors[] = $label . ': detail maksimal 500 karakter.';
            }

            if (mb_strlen($alasan) > 255) {
                $errors[] = $label . ': alasan maksimal 255 karakter.';
            }

            if (in_array($status, $beralasan, true) && $alasan === '') {
                $errors[] = $label . ': alasan wajib diisi.';
            }

            $waktu_db = null;
            if ($waktu !== '') {
                $waktu_obj = DateTime::createFromFormat('!Y-m-d\TH:i', $waktu);

                if (!$waktu_obj || $waktu_obj->format('Y-m-d\TH:i') !== $waktu) {
                    $errors[] = $label . ': waktu pelaksanaan tidak valid.';
                } elseif ($waktu_obj->getTimestamp() > time() + self::TOLERANSI_WAKTU_DETIK) {
                    $errors[] = $label . ': waktu pelaksanaan tidak boleh melebihi waktu sekarang.';
                } else {
                    $waktu_db = $waktu_obj->format('Y-m-d H:i:00');
                }
            } elseif ($status === 'dilakukan') {
                $errors[] = $label . ': waktu pelaksanaan wajib diisi.';
            }

            // Butir yang kembali ke "belum" tanpa catatan dihapus agar tidak tercatat sebagai pelaksanaan.
            $kosong = $status === 'belum' && $detail === '' && $alasan === '' && $waktu === '';
            $rows[$id_item] = $kosong ? null : [
                'status' => $status,
                'detail_tindakan' => $detail === '' ? null : $detail,
                'alasan' => $alasan === '' ? null : $alasan,
                'waktu_pelaksanaan' => $waktu_db,
                'id_user' => (int) $this->web_user['id_user']
            ];
        }

        // Dosis berurutan: dosis ke-n hanya boleh dicatat setelah dosis ke-(n-1) dilakukan lebih dulu.
        foreach ($items as $id_item => $item) {
            if (
                $item['kelompok_dosis'] === null
                || (int) $item['dosis_ke'] < 2
                || $state[$id_item]['status'] !== 'dilakukan'
            ) {
                continue;
            }

            $sebelumnya = null;
            foreach ($items as $id_lain => $lain) {
                if (
                    $lain['kelompok_dosis'] === $item['kelompok_dosis']
                    && (int) $lain['dosis_ke'] === (int) $item['dosis_ke'] - 1
                ) {
                    $sebelumnya = $id_lain;
                }
            }

            if ($sebelumnya === null || $state[$sebelumnya]['status'] !== 'dilakukan') {
                $errors[] = mb_strimwidth($item['teks_standar'], 0, 60, '…') . ': dosis sebelumnya belum dicatat dilakukan.';
            } elseif ($state[$sebelumnya]['waktu_pelaksanaan'] > $state[$id_item]['waktu_pelaksanaan']) {
                $errors[] = mb_strimwidth($item['teks_standar'], 0, 60, '…') . ': waktu lebih awal dari dosis sebelumnya.';
            }
        }

        if ($errors) {
            $this->form_error($id, 'stabilisasi', ['item' => $state], array_values(array_unique($errors)));

            return;
        }

        if (!$this->stabilisasi->save_pelaksanaan($id, $rows)) {
            $this->form_error($id, 'stabilisasi', ['item' => $state], ['Checklist stabilisasi gagal disimpan. Coba lagi.']);

            return;
        }

        $this->session->set_flashdata('success', 'Checklist stabilisasi berhasil disimpan.');
        $this->redirect_tab($id, 'stabilisasi');
    }

    private function is_valid_number($value, $min, $max)
    {
        return preg_match('/^[0-9]{1,4}(\.[0-9]{1,2})?$/D', $value) === 1
            && (float) $value >= $min
            && (float) $value <= $max;
    }

    public function kirim($id = '')
    {
        $this->require_post();
        $patient = $this->load_patient($id);
        $id = (int) $patient['id_kunjungan'];

        if (!$this->akses->can_send($patient)) {
            $this->session->set_flashdata('error', 'Rujukan ini tidak dapat dikirim.');
            $this->redirect_tab($id, 'identitas');

            return;
        }

        // Tanpa tujuan yang jelas, rujukan tidak akan muncul di faskes mana pun.
        if (!$patient['id_faskes_tujuan'] || !$patient['id_faskes_perujuk']) {
            $this->session->set_flashdata('error', 'Lengkapi faskes perujuk dan faskes tujuan di identitas pasien sebelum mengirim.');
            $this->redirect_tab($id, 'identitas');

            return;
        }

        $berhasil = $this->kunjungan->ubah_status($id, 'draf', [
            'status_rujukan' => 'dikirim',
            'waktu_dikirim' => date('Y-m-d H:i:s'),
            'id_user_pengirim' => (int) $this->web_user['id_user']
        ]);

        $this->session->set_flashdata(
            $berhasil ? 'success' : 'error',
            $berhasil
                ? 'Rujukan dikirim ke ' . $patient['nama_faskes_tujuan'] . '. Data terkunci; observasi masih dapat ditambahkan.'
                : 'Rujukan gagal dikirim. Muat ulang halaman dan coba lagi.'
        );
        $this->redirect_tab($id, 'identitas');
    }

    public function batal_kirim($id = '')
    {
        $this->require_post();
        $patient = $this->load_patient($id);
        $id = (int) $patient['id_kunjungan'];

        // Pembatalan hanya sah selama faskes tujuan belum menerima.
        $berhasil = $this->akses->can_cancel_send($patient)
            && $this->kunjungan->ubah_status($id, 'dikirim', [
                'status_rujukan' => 'draf',
                'waktu_dikirim' => null,
                'id_user_pengirim' => null
            ]);

        $this->session->set_flashdata(
            $berhasil ? 'success' : 'error',
            $berhasil
                ? 'Pengiriman dibatalkan; data dapat diubah kembali.'
                : 'Pengiriman tidak dapat dibatalkan karena rujukan sudah diterima faskes tujuan.'
        );
        $this->redirect_tab($id, 'identitas');
    }

    /**
     * Memuat kunjungan dan memeriksa hak user.
     *
     * @param string $hak lihat | edit | observasi
     */
    private function load_patient($id, $hak = 'lihat')
    {
        if (!preg_match('/^[1-9][0-9]*$/D', (string) $id) || (float) $id > 2147483647) {
            show_404();
        }

        $patient = $this->kunjungan->find_detail((int) $id);

        // Kunjungan faskes lain diperlakukan seperti tidak ada agar keberadaannya tidak bocor.
        if (!$patient || !$this->akses->can_view($patient)) {
            show_404();
        }

        $boleh = $hak === 'edit'
            ? $this->akses->can_edit($patient)
            : ($hak === 'observasi' ? $this->akses->can_add_observasi($patient) : true);

        if (!$boleh) {
            $this->session->set_flashdata('error', 'Data terkunci karena rujukan sudah dikirim atau Anda tidak berhak mengubahnya.');
            $this->redirect_tab((int) $patient['id_kunjungan'], 'identitas');
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
