<?php

defined('BASEPATH') or exit('No direct script access allowed');

require_once APPPATH . 'core/Web_Controller.php';

/**
 * Rujukan masuk untuk faskes tujuan: daftar, ringkasan SBAR (baca saja),
 * menerima rujukan, dan mencatat kedatangan pasien.
 */
class Rujukan_masuk extends Web_Controller
{
    // Toleransi jam perangkat yang sedikit berbeda dengan server.
    const TOLERANSI_WAKTU_DETIK = 300;

    // Urutan kegawatan untuk mengurutkan daftar: zona merah paling atas.
    private $urutan_zona = [
        'merah' => 0,
        'kuning' => 1,
        'normal' => 2
    ];

    public function __construct()
    {
        parent::__construct();

        // Rujukan masuk adalah menu rumah sakit (FKRTL).
        $this->require_menu('fkrtl');

        $this->config->load('rujukan_status');
        $this->config->load('obstetri');
        $this->config->load('stabilisasi');
        $this->load->model('web/Kunjungan_model', 'kunjungan');
        $this->load->model('web/Obstetri_model', 'obstetri');
        $this->load->model('web/Observasi_model', 'observasi');
        $this->load->model('web/Stabilisasi_model', 'stabilisasi');
        $this->load->library('meows');
    }

    public function index()
    {
        $mode = $this->input->get('status');
        $mode = $mode === 'selesai' ? 'selesai' : 'aktif';
        $faskes_id = $this->akses->faskes_id();

        $rows = $this->kunjungan->listing_masuk($faskes_id, $mode);
        $observasi = $this->observasi->terakhir_per_kunjungan(array_column($rows, 'id_kunjungan'));

        foreach ($rows as &$row) {
            $id = (int) $row['id_kunjungan'];
            $row['observasi'] = isset($observasi[$id]) ? $observasi[$id] : [];
            $row['diagnosis'] = array_column($this->stabilisasi->diagnosis_kunjungan($id), 'nama');
            $row['kepatuhan'] = $this->kepatuhan($id);
        }
        unset($row);

        // Yang belum diterima paling atas, lalu zona paling gawat, lalu yang paling baru dikirim.
        if ($mode === 'aktif') {
            usort($rows, function ($a, $b) {
                $status = strcmp($a['status_rujukan'] === 'dikirim' ? '0' : '1', $b['status_rujukan'] === 'dikirim' ? '0' : '1');
                if ($status !== 0) {
                    return $status;
                }

                $zona = $this->rank_zona($a) - $this->rank_zona($b);

                return $zona !== 0 ? $zona : strcmp($b['waktu_dikirim'], $a['waktu_dikirim']);
            });
        }

        $this->render('web/rujukan/index', [
            'page_title' => 'Rujukan masuk',
            'active_menu' => 'rujukan',
            'rows' => $rows,
            'mode' => $mode,
            'status_rujukan' => $this->config->item('status_rujukan'),
            'target_tiba_menit' => (int) $this->config->item('target_tiba_menit'),
            'page_scripts' => [
                'assets/web/rujukan.js'
            ]
        ]);
    }

    public function detail($id = '')
    {
        $patient = $this->load_kunjungan($id);

        // Data ringkasan disusun library yang sama dengan dokumen PDF.
        $this->load->library('ringkasan_rujukan');
        $data = $this->ringkasan_rujukan->build($patient);

        $this->render('web/rujukan/detail', array_merge($data, [
            'page_title' => 'Rujukan · ' . $patient['nama'],
            'active_menu' => 'rujukan',
            'patient' => $patient,
            'peran' => $this->akses->peran($patient),
            'boleh_terima' => $this->akses->can_receive($patient),
            'boleh_tiba' => $this->akses->can_record_arrival($patient)
        ]));
    }

    public function terima($id = '')
    {
        $this->require_post();
        $patient = $this->load_kunjungan($id);
        $id = (int) $patient['id_kunjungan'];

        if (!$this->akses->can_receive($patient)) {
            $this->session->set_flashdata('error', 'Rujukan ini tidak dapat diterima (mungkin sudah diterima atau dibatalkan).');
            redirect('web/rujukan/' . $id);
        }

        $catatan = $this->input->post('catatan_penerimaan');
        $catatan = is_string($catatan) ? trim($catatan) : '';

        if (mb_strlen($catatan) > 500) {
            $this->session->set_flashdata('error', 'Catatan penerimaan maksimal 500 karakter.');
            redirect('web/rujukan/' . $id);
        }

        $berhasil = $this->kunjungan->ubah_status($id, 'dikirim', [
            'status_rujukan' => 'diterima',
            'waktu_diterima' => date('Y-m-d H:i:s'),
            'id_user_penerima' => (int) $this->web_user['id_user'],
            'catatan_penerimaan' => $catatan === '' ? null : $catatan
        ]);

        $this->session->set_flashdata(
            $berhasil ? 'success' : 'error',
            $berhasil
                ? 'Rujukan ' . $patient['nama'] . ' diterima. Faskes perujuk dapat melihat statusnya.'
                : 'Rujukan gagal diterima; status sudah berubah. Muat ulang halaman.'
        );
        redirect('web/rujukan/' . $id);
    }

    public function tiba($id = '')
    {
        $this->require_post();
        $patient = $this->load_kunjungan($id);
        $id = (int) $patient['id_kunjungan'];

        if (!$this->akses->can_record_arrival($patient)) {
            $this->session->set_flashdata('error', 'Kedatangan hanya dapat dicatat untuk rujukan yang sudah diterima.');
            redirect('web/rujukan/' . $id);
        }

        $waktu = $this->input->post('waktu_tiba');
        $waktu = is_string($waktu) ? trim($waktu) : '';
        $waktu_obj = DateTime::createFromFormat('!Y-m-d\TH:i', $waktu);

        $error = null;
        if (!$waktu_obj || $waktu_obj->format('Y-m-d\TH:i') !== $waktu) {
            $error = 'Waktu kedatangan tidak valid.';
        } elseif ($waktu_obj->getTimestamp() > time() + self::TOLERANSI_WAKTU_DETIK) {
            $error = 'Waktu kedatangan tidak boleh melebihi waktu sekarang.';
        } elseif ($waktu_obj->getTimestamp() < strtotime(substr($patient['waktu_dikirim'], 0, 16))) {
            // Input hanya sampai menit, jadi pembanding juga dibulatkan ke menit pengiriman.
            $error = 'Waktu kedatangan tidak boleh sebelum rujukan dikirim.';
        }

        if ($error) {
            $this->session->set_flashdata('error', $error);
            redirect('web/rujukan/' . $id);
        }

        $berhasil = $this->kunjungan->ubah_status($id, 'diterima', [
            'status_rujukan' => 'tiba',
            'waktu_tiba' => $waktu_obj->format('Y-m-d H:i:00'),
            'id_user_kedatangan' => (int) $this->web_user['id_user']
        ]);

        $this->session->set_flashdata(
            $berhasil ? 'success' : 'error',
            $berhasil ? 'Kedatangan ' . $patient['nama'] . ' tercatat.' : 'Kedatangan gagal dicatat; muat ulang halaman.'
        );
        redirect('web/rujukan/' . $id);
    }

    private function kepatuhan($id_kunjungan)
    {
        $items = $this->stabilisasi->items(array_keys($this->stabilisasi->diagnosis_kunjungan($id_kunjungan)));
        if (!$items) {
            return null;
        }

        $ringkasan = $this->stabilisasi->ringkasan(
            $items,
            $this->stabilisasi->pelaksanaan($id_kunjungan),
            $this->stabilisasi->trigger_merah($id_kunjungan),
            time()
        );

        return $ringkasan['persen'];
    }

    private function rank_zona(array $row)
    {
        $zona = $row['observasi'] ? $row['observasi'][0]['meows_zona'] : null;

        return isset($this->urutan_zona[$zona]) ? $this->urutan_zona[$zona] : 3;
    }

    private function load_kunjungan($id)
    {
        if (!preg_match('/^[1-9][0-9]*$/D', (string) $id) || (float) $id > 2147483647) {
            show_404();
        }

        $patient = $this->kunjungan->find_detail((int) $id);

        // Ringkasan rujukan hanya untuk kunjungan yang sudah dikirim dan boleh dilihat user.
        if (!$patient || $patient['status_rujukan'] === 'draf' || !$this->akses->can_view($patient)) {
            show_404();
        }

        return $patient;
    }

    private function require_post()
    {
        if ($this->input->method() !== 'post') {
            $this->output->set_header('Allow: POST');
            show_error('Gunakan metode POST.', 405);
        }
    }
}
