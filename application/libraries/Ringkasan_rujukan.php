<?php

defined('BASEPATH') or exit('No direct script access allowed');

/**
 * Menyusun data ringkasan rujukan (SBAR) satu kunjungan.
 * Dipakai halaman Rujukan masuk dan dokumen PDF agar isi keduanya selalu sama.
 */
class Ringkasan_rujukan
{
    private $ci;

    public function __construct()
    {
        $this->ci = &get_instance();

        $this->ci->config->load('obstetri');
        $this->ci->config->load('stabilisasi');
        $this->ci->config->load('rujukan_status');
        $this->ci->load->model('web/Obstetri_model', 'obstetri');
        $this->ci->load->model('web/Observasi_model', 'observasi');
        $this->ci->load->model('web/Stabilisasi_model', 'stabilisasi');
        $this->ci->load->library('meows');
    }

    public function build(array $patient)
    {
        $ci = $this->ci;
        $id = (int) $patient['id_kunjungan'];

        $gpa = $ci->obstetri->find_gpa($id);
        $riwayat = $ci->obstetri->riwayat($id);
        $kondisi_ibu = $ci->obstetri->kondisi_ibu($id);
        $pilihan_map = $ci->obstetri->pilihan_map();
        $observasi = $ci->observasi->listing($id);
        $observasi_terakhir = $observasi ? $observasi[0] : null;

        $diagnosis = $ci->stabilisasi->diagnosis_kunjungan($id);
        $items = $ci->stabilisasi->items(array_keys($diagnosis));
        $pelaksanaan = $ci->stabilisasi->pelaksanaan($id);
        $ringkasan = $ci->stabilisasi->ringkasan(
            $items,
            $pelaksanaan,
            $ci->stabilisasi->trigger_merah($id),
            time()
        );

        $flags = array_merge(
            $ci->meows->flags($observasi_terakhir),
            $ringkasan['peringatan'],
            $ci->obstetri->analisis($gpa, $riwayat, $kondisi_ibu, $pilihan_map, $patient['tgl_reg'])
        );

        return [
            'umur' => $this->umur($patient),
            'gpa' => $gpa,
            'riwayat' => $riwayat,
            'riwayat_options' => $ci->config->item('riwayat_options'),
            'kondisi_ibu' => $kondisi_ibu,
            'pilihan_map' => $pilihan_map,
            'observasi' => $observasi,
            'observasi_terakhir' => $observasi_terakhir,
            'observasi_terakhir_hasil' => $observasi_terakhir ? $ci->meows->hitung($observasi_terakhir) : null,
            'meows_rules' => $ci->meows->rules(),
            'diagnosis' => $diagnosis,
            'stabilisasi_items' => $items,
            'pelaksanaan' => $pelaksanaan,
            'stabilisasi_ringkasan' => $ringkasan,
            'stabilisasi_komponen' => $ci->config->item('stabilisasi_komponen'),
            'sbar' => $this->pisah_stabilisasi($items, $pelaksanaan),
            'flags' => $flags,
            'status_rujukan' => $ci->config->item('status_rujukan'),
            'target_tiba_menit' => (int) $ci->config->item('target_tiba_menit')
        ];
    }

    /**
     * Memisahkan butir stabilisasi menjadi kriteria terpenuhi, tindakan terlaksana (kronologis),
     * dan tindakan wajib/alternatif yang belum terlaksana.
     */
    private function pisah_stabilisasi(array $items, array $pelaksanaan)
    {
        $dilakukan = [];
        $belum = [];
        $kriteria = [];
        $alternatif_tertangani = [];

        // Kelompok alternatif dianggap tertangani bila salah satu anggotanya dilakukan atau sedang berjalan.
        foreach ($items as $id_item => $item) {
            $row = isset($pelaksanaan[$id_item]) ? $pelaksanaan[$id_item] : null;
            if ($item['sifat'] === 'alternatif' && $row && in_array($row['status'], ['dilakukan', 'dalam_proses'], true)) {
                $alternatif_tertangani[$item['kelompok_alternatif']] = true;
            }
        }

        foreach ($items as $id_item => $item) {
            $row = isset($pelaksanaan[$id_item]) ? $pelaksanaan[$id_item] : null;
            $status = $row ? $row['status'] : 'belum';

            if ($item['jenis'] === 'kriteria') {
                if ($status === 'terpenuhi') {
                    $kriteria[] = $item['teks_standar'] . ($row['detail_tindakan'] ? ' (' . $row['detail_tindakan'] . ')' : '');
                }

                continue;
            }

            if (in_array($status, ['dilakukan', 'dalam_proses'], true)) {
                $dilakukan[] = [
                    'waktu' => $row['waktu_pelaksanaan'],
                    'teks' => $row['detail_tindakan'] ?: $item['teks_standar'],
                    'proses' => $status === 'dalam_proses'
                ];

                continue;
            }

            $perlu = $item['sifat'] === 'wajib'
                || ($item['sifat'] === 'alternatif' && empty($alternatif_tertangani[$item['kelompok_alternatif']]));

            if ($perlu && $status !== 'tidak_diperlukan') {
                $belum[] = [
                    'teks' => $item['teks_standar'],
                    'alasan' => $row ? $row['alasan'] : null,
                    'status' => $status
                ];
            }
        }

        usort($dilakukan, function ($a, $b) {
            return strcmp($a['waktu'] ?: '9999', $b['waktu'] ?: '9999');
        });

        return [
            'dilakukan' => $dilakukan,
            'belum' => $belum,
            'kriteria' => $kriteria
        ];
    }

    /**
     * Umur dihitung per tanggal registrasi agar sesuai kondisi saat kunjungan.
     */
    private function umur(array $patient)
    {
        if (!$patient['tgl_lahir']) {
            return '—';
        }

        $lahir = DateTime::createFromFormat('!Y-m-d', $patient['tgl_lahir']);
        $acuan = DateTime::createFromFormat('!Y-m-d', $patient['tgl_reg'] ?: date('Y-m-d'));

        return $lahir && $acuan && $lahir <= $acuan ? $lahir->diff($acuan)->y . ' tahun' : '—';
    }
}
