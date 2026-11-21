<?php

defined('BASEPATH') or exit('No direct script access allowed');

class Obstetri_model extends CI_Model
{
    public function pilihan($kelompok)
    {
        return $this->db
            ->select('kode, kelompok, kategori_klinis, label, tingkat_risiko, dampak_risiko, rekomendasi, kode_icd10, wajib_catatan')
            ->where('kelompok', $kelompok)
            ->where('id_active', 1)
            ->order_by('urutan', 'ASC')
            ->order_by('label', 'ASC')
            ->get('m_obstetri_pilihan')
            ->result_array();
    }

    public function pilihan_map()
    {
        $rows = $this->db
            ->select('kode, kelompok, kategori_klinis, label, tingkat_risiko, dampak_risiko, rekomendasi, kode_icd10, wajib_catatan')
            ->get('m_obstetri_pilihan')
            ->result_array();

        return array_column($rows, null, 'kode');
    }

    public function find_gpa($id_kunjungan)
    {
        return $this->db
            ->get_where('t_kunjungan_obstetri', ['id_kunjungan' => $id_kunjungan])
            ->row_array();
    }

    public function riwayat($id_kunjungan)
    {
        $rows = $this->db
            ->where('id_kunjungan', $id_kunjungan)
            ->order_by('kehamilan_ke', 'ASC')
            ->get('t_riwayat_kehamilan')
            ->result_array();

        if (!$rows) {
            return [];
        }

        $kondisi = $this->db
            ->select('id_riwayat_kehamilan, kode_pilihan')
            ->where_in('id_riwayat_kehamilan', array_column($rows, 'id_riwayat_kehamilan'))
            ->get('t_riwayat_kehamilan_kondisi')
            ->result_array();

        $result = [];
        foreach ($rows as $row) {
            $row['kondisi'] = [];
            $result[(int) $row['kehamilan_ke']] = $row;
        }

        $by_id = array_column($rows, 'kehamilan_ke', 'id_riwayat_kehamilan');
        foreach ($kondisi as $item) {
            $result[(int) $by_id[$item['id_riwayat_kehamilan']]]['kondisi'][] = $item['kode_pilihan'];
        }

        return $result;
    }

    public function kondisi_ibu($id_kunjungan)
    {
        // Urutan mengikuti nomor pada master agar ringkasan sesuai checklist.
        $rows = $this->db
            ->select('k.kode_pilihan, k.catatan')
            ->from('t_kunjungan_kondisi_ibu k')
            ->join('m_obstetri_pilihan p', 'p.kode = k.kode_pilihan', 'left')
            ->where('k.id_kunjungan', $id_kunjungan)
            ->order_by('p.urutan', 'ASC')
            ->get()
            ->result_array();

        return array_column($rows, 'catatan', 'kode_pilihan');
    }

    public function save_gpa($id_kunjungan, array $data)
    {
        return $this->transaction(function () use ($id_kunjungan, $data) {
            if ($this->find_gpa($id_kunjungan)) {
                $this->db
                    ->where('id_kunjungan', $id_kunjungan)
                    ->update('t_kunjungan_obstetri', $data);
            } else {
                $this->db->insert('t_kunjungan_obstetri', $data + ['id_kunjungan' => $id_kunjungan]);
            }

            // Riwayat di luar jumlah kehamilan terdahulu (G - 1) tidak lagi relevan.
            $this->db
                ->where('id_kunjungan', $id_kunjungan)
                ->where('kehamilan_ke >=', $data['gravida'])
                ->delete('t_riwayat_kehamilan');

            // status_obstetri tetap diisi agar API lama yang membacanya tidak berubah.
            $this->db
                ->where('id_kunjungan', $id_kunjungan)
                ->update('t_kunjungan', [
                    'status_obstetri' => 'G' . $data['gravida'] . 'P' . $data['partus'] . 'A' . $data['abortus']
                ]);
        });
    }

    public function save_riwayat($id_kunjungan, array $rows)
    {
        return $this->transaction(function () use ($id_kunjungan, $rows) {
            $existing = $this->db
                ->select('id_riwayat_kehamilan, kehamilan_ke')
                ->get_where('t_riwayat_kehamilan', ['id_kunjungan' => $id_kunjungan])
                ->result_array();
            $existing = array_column($existing, 'id_riwayat_kehamilan', 'kehamilan_ke');

            foreach ($rows as $ke => $row) {
                $kondisi = $row['kondisi'];
                unset($row['kondisi']);

                if (isset($existing[$ke])) {
                    $id_riwayat = (int) $existing[$ke];
                    $this->db
                        ->where('id_riwayat_kehamilan', $id_riwayat)
                        ->update('t_riwayat_kehamilan', $row);
                } else {
                    $this->db->insert('t_riwayat_kehamilan', $row + [
                        'id_kunjungan' => $id_kunjungan,
                        'kehamilan_ke' => $ke
                    ]);
                    $id_riwayat = (int) $this->db->insert_id();
                }

                $this->db->delete('t_riwayat_kehamilan_kondisi', ['id_riwayat_kehamilan' => $id_riwayat]);

                foreach ($kondisi as $kode) {
                    $this->db->insert('t_riwayat_kehamilan_kondisi', [
                        'id_riwayat_kehamilan' => $id_riwayat,
                        'kode_pilihan' => $kode
                    ]);
                }
            }
        });
    }

    public function save_kondisi_ibu($id_kunjungan, array $items)
    {
        return $this->transaction(function () use ($id_kunjungan, $items) {
            $this->db->delete('t_kunjungan_kondisi_ibu', ['id_kunjungan' => $id_kunjungan]);

            foreach ($items as $kode => $catatan) {
                $this->db->insert('t_kunjungan_kondisi_ibu', [
                    'id_kunjungan' => $id_kunjungan,
                    'kode_pilihan' => $kode,
                    'catatan' => $catatan
                ]);
            }
        });
    }

    /**
     * Menyusun penanda kewaspadaan dari kondisi ibu, GPA, dan riwayat kehamilan.
     * Hasil hanya informasi untuk tenaga kesehatan, bukan keputusan klinis.
     */
    public function analisis($gpa, array $riwayat, array $kondisi_ibu, array $pilihan_map, $tgl_reg)
    {
        $flags = [];

        // Komorbid ibu tetap ditampilkan walaupun GPA belum diisi.
        foreach ($kondisi_ibu as $kode => $catatan) {
            if (!isset($pilihan_map[$kode]) || $pilihan_map[$kode]['tingkat_risiko'] === 'normal') {
                continue;
            }

            $pilihan = $pilihan_map[$kode];
            $flags[] = [
                'level' => $pilihan['tingkat_risiko'],
                'text' => 'Komorbid: ' . $pilihan['label']
                    . ($pilihan['kode_icd10'] ? ' [' . $pilihan['kode_icd10'] . ']' : '')
                    . ($catatan ? ' — ' . $catatan : '')
            ];
        }

        if (!$gpa) {
            return $flags;
        }

        if ((int) $gpa['anak_hidup'] > (int) $gpa['partus']) {
            $flags[] = [
                'level' => 'risiko',
                'text' => 'Anak hidup melebihi partus. Pastikan ada riwayat kehamilan kembar.'
            ];
        }

        if ((int) $gpa['anak_meninggal'] > 0) {
            $flags[] = [
                'level' => 'risiko',
                'text' => 'Riwayat obstetri buruk: ' . (int) $gpa['anak_meninggal'] . ' anak meninggal.'
            ];
        }

        if ((int) $gpa['abortus'] > 0) {
            $flags[] = [
                'level' => 'risiko',
                'text' => 'Riwayat abortus ' . (int) $gpa['abortus'] . '×.'
            ];
        }

        $jumlah_sc = 0;
        $jumlah_abortus = 0;
        $jumlah_partus = 0;
        $luaran_lengkap = true;
        $tahun_terakhir = null;

        foreach ($riwayat as $row) {
            if ($row['metode_persalinan'] === 'sectio_caesarea') {
                $jumlah_sc++;
            }

            if ($row['luaran'] === null) {
                $luaran_lengkap = false;
            } elseif ($row['luaran'] === 'abortus') {
                $jumlah_abortus++;
            } elseif (in_array($row['luaran'], ['lahir_hidup', 'lahir_mati'], true)) {
                $jumlah_partus++;
            }

            if ($row['tahun_persalinan'] !== null) {
                $tahun_terakhir = max((int) $tahun_terakhir, (int) $row['tahun_persalinan']);
            }

            foreach ($row['kondisi'] as $kode) {
                $pilihan = isset($pilihan_map[$kode]) ? $pilihan_map[$kode] : null;
                if (!$pilihan || $pilihan['tingkat_risiko'] === 'normal') {
                    continue;
                }

                $flags[] = [
                    'level' => $pilihan['tingkat_risiko'],
                    'text' => 'Kehamilan ke-' . $row['kehamilan_ke'] . ': ' . $pilihan['label']
                        . ($pilihan['rekomendasi'] ? ' — ' . $pilihan['rekomendasi'] : '')
                ];
            }
        }

        if ($jumlah_sc > 0) {
            $flags[] = [
                'level' => 'risiko',
                'text' => 'Bekas SC ' . $jumlah_sc . '× (uterus berparut, pantau ruptur).'
            ];
        }

        if ($tahun_terakhir && $tgl_reg) {
            $jarak = (int) substr($tgl_reg, 0, 4) - $tahun_terakhir;
            $ideal = $this->config->item('jarak_kehamilan_ideal');

            if ($jarak < $ideal['min'] || $jarak > $ideal['max']) {
                $flags[] = [
                    'level' => 'risiko',
                    'text' => 'Jarak kehamilan terakhir ' . $jarak . ' tahun (ideal '
                        . $ideal['min'] . '–' . $ideal['max'] . ' tahun).'
                ];
            }
        }

        // Konsistensi hanya dicek bila semua luaran sudah diisi agar tidak muncul saat entri bertahap.
        if ($riwayat && $luaran_lengkap && count($riwayat) === (int) $gpa['gravida'] - 1) {
            if ($jumlah_abortus !== (int) $gpa['abortus']) {
                $flags[] = [
                    'level' => 'data',
                    'text' => 'Jumlah riwayat abortus (' . $jumlah_abortus . ') tidak sama dengan A ('
                        . (int) $gpa['abortus'] . ').'
                ];
            }

            if ($jumlah_partus !== (int) $gpa['partus']) {
                $flags[] = [
                    'level' => 'data',
                    'text' => 'Jumlah riwayat persalinan (' . $jumlah_partus . ') tidak sama dengan P ('
                        . (int) $gpa['partus'] . ').'
                ];
            }
        }

        return $flags;
    }

    private function transaction($operation)
    {
        // Kesalahan database ditampilkan sebagai pesan form, bukan detail SQL.
        $debug = $this->db->db_debug;
        $this->db->db_debug = false;
        $this->db->trans_begin();

        try {
            $operation();

            if ($this->db->trans_status() === false) {
                $this->db->trans_rollback();

                return false;
            }

            $this->db->trans_commit();

            return true;
        } catch (Throwable $exception) {
            $this->db->trans_rollback();
            log_message('error', 'Operasi data obstetri gagal: ' . $exception->getMessage());

            return false;
        } finally {
            $this->db->db_debug = $debug;
        }
    }
}
