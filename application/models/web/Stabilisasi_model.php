<?php

defined('BASEPATH') or exit('No direct script access allowed');

class Stabilisasi_model extends CI_Model
{
    public function diagnosis_options()
    {
        return $this->db
            ->select('d.id_diagnosis, d.kode, d.nama')
            ->select('(SELECT COUNT(*) FROM m_stabilisasi_item i WHERE i.id_diagnosis = d.id_diagnosis AND i.id_active = 1) AS jumlah_item', false)
            ->from('m_diagnosis_ponek d')
            ->where('d.id_active', 1)
            ->order_by('d.nama', 'ASC')
            ->get()
            ->result_array();
    }

    public function diagnosis_kunjungan($id_kunjungan)
    {
        $rows = $this->db
            ->select('kd.id_diagnosis, kd.waktu_ditetapkan, d.kode, d.nama')
            ->from('t_kunjungan_diagnosis kd')
            ->join('m_diagnosis_ponek d', 'd.id_diagnosis = kd.id_diagnosis')
            ->where('kd.id_kunjungan', $id_kunjungan)
            ->order_by('d.nama', 'ASC')
            ->get()
            ->result_array();

        return array_column($rows, null, 'id_diagnosis');
    }

    public function items(array $diagnosis_ids)
    {
        if (!$diagnosis_ids) {
            return [];
        }

        // Kolom enum diurutkan MySQL berdasarkan urutan definisi: red flag lalu A-E.
        $rows = $this->db
            ->where_in('id_diagnosis', $diagnosis_ids)
            ->where('id_active', 1)
            ->order_by('id_diagnosis', 'ASC')
            ->order_by('komponen', 'ASC')
            ->order_by('urutan', 'ASC')
            ->get('m_stabilisasi_item')
            ->result_array();

        return array_column($rows, null, 'id_item');
    }

    public function pelaksanaan($id_kunjungan)
    {
        $rows = $this->db
            ->get_where('t_kunjungan_stabilisasi', ['id_kunjungan' => $id_kunjungan])
            ->result_array();

        return array_column($rows, null, 'id_item');
    }

    /**
     * Waktu trigger merah MEOWS pertama; titik nol untuk target waktu (contoh: evaluasi < 10 menit).
     */
    public function trigger_merah($id_kunjungan)
    {
        $row = $this->db
            ->select_min('waktu_observasi', 'waktu')
            ->where('id_kunjungan', $id_kunjungan)
            ->where('meows_zona', 'merah')
            ->get('t_kunjungan_observasi')
            ->row_array();

        return $row ? $row['waktu'] : null;
    }

    public function save_diagnosis($id_kunjungan, array $diagnosis_ids, $id_user)
    {
        return $this->transaction(function () use ($id_kunjungan, $diagnosis_ids, $id_user) {
            $existing = array_keys($this->diagnosis_kunjungan($id_kunjungan));

            // Data pelaksanaan diagnosa yang dilepas tetap disimpan agar kembali bila dipilih ulang.
            $removed = array_diff($existing, $diagnosis_ids);
            if ($removed) {
                $this->db
                    ->where('id_kunjungan', $id_kunjungan)
                    ->where_in('id_diagnosis', $removed)
                    ->delete('t_kunjungan_diagnosis');
            }

            foreach (array_diff($diagnosis_ids, $existing) as $id_diagnosis) {
                $this->db->insert('t_kunjungan_diagnosis', [
                    'id_kunjungan' => $id_kunjungan,
                    'id_diagnosis' => $id_diagnosis,
                    'id_user' => $id_user
                ]);
            }
        });
    }

    /**
     * @param array $rows [id_item => data atau null untuk menghapus catatan]
     */
    public function save_pelaksanaan($id_kunjungan, array $rows)
    {
        return $this->transaction(function () use ($id_kunjungan, $rows) {
            $existing = $this->pelaksanaan($id_kunjungan);

            foreach ($rows as $id_item => $data) {
                $where = [
                    'id_kunjungan' => $id_kunjungan,
                    'id_item' => $id_item
                ];

                if ($data === null) {
                    if (isset($existing[$id_item])) {
                        $this->db->delete('t_kunjungan_stabilisasi', $where);
                    }

                    continue;
                }

                if (isset($existing[$id_item])) {
                    $this->db
                        ->where($where)
                        ->update('t_kunjungan_stabilisasi', $data);
                } else {
                    $this->db->insert('t_kunjungan_stabilisasi', $data + $where);
                }
            }
        });
    }

    /**
     * Menilai kode_aturan dari observasi terakhir.
     * Mengembalikan null bila data observasi belum cukup untuk dinilai.
     */
    public function evaluasi_aturan($kode_aturan, $observasi)
    {
        if (!$kode_aturan || !$observasi) {
            return null;
        }

        if ($kode_aturan === 'td_berat') {
            $sis = $observasi['td_sistolik_mmhg'];
            $dia = $observasi['td_diastolik_mmhg'];

            if ($sis === null && $dia === null) {
                return null;
            }

            return [
                'terpenuhi' => (int) $sis >= 160 || (int) $dia >= 110,
                'teks' => 'TD ' . ($sis ?? '—') . '/' . ($dia ?? '—') . ' mmHg'
            ];
        }

        if ($kode_aturan === 'proteinuria_plus2') {
            if ($observasi['proteinuria'] === null) {
                return null;
            }

            return [
                'terpenuhi' => in_array($observasi['proteinuria'], ['plus_2', 'plus_3', 'plus_4'], true),
                'teks' => 'Proteinuria ' . str_replace('plus_', '+', $observasi['proteinuria'])
            ];
        }

        return null;
    }

    /**
     * Menghitung kepatuhan, status target waktu, dan peringatan jeda dosis.
     *
     * Aturan kepatuhan:
     * - kriteria tidak dihitung;
     * - wajib dihitung, selesai bila dilakukan;
     * - alternatif dihitung satu per kelompok, selesai bila salah satu dilakukan,
     *   dan dikecualikan bila seluruh anggotanya tidak diperlukan;
     * - kondisional hanya dihitung bila petugas menyatakan perlu (status selain belum/tidak diperlukan).
     */
    public function ringkasan(array $items, array $pelaksanaan, $trigger_merah, $now)
    {
        $status = function ($id_item) use ($pelaksanaan) {
            return isset($pelaksanaan[$id_item]) ? $pelaksanaan[$id_item]['status'] : 'belum';
        };

        $unit = [];
        $alternatif = [];

        foreach ($items as $id_item => $item) {
            if ($item['jenis'] === 'kriteria') {
                continue;
            }

            $current = $status($id_item);

            if ($item['sifat'] === 'alternatif') {
                $alternatif[$item['kelompok_alternatif']][] = $current;

                continue;
            }

            if ($item['sifat'] === 'kondisional' && in_array($current, ['belum', 'tidak_diperlukan'], true)) {
                continue;
            }

            $unit[] = $current === 'dilakukan';
        }

        foreach ($alternatif as $statuses) {
            if (count(array_diff($statuses, ['tidak_diperlukan'])) === 0) {
                continue;
            }

            $unit[] = in_array('dilakukan', $statuses, true);
        }

        $trigger_ts = $trigger_merah ? strtotime($trigger_merah) : null;
        $target = [];
        $peringatan = [];

        foreach ($items as $id_item => $item) {
            if ($item['target_menit'] === null || !$trigger_ts) {
                continue;
            }

            $deadline = $trigger_ts + (int) $item['target_menit'] * 60;
            $row = isset($pelaksanaan[$id_item]) ? $pelaksanaan[$id_item] : null;

            if ($row && $row['status'] === 'dilakukan' && $row['waktu_pelaksanaan']) {
                $hasil = strtotime($row['waktu_pelaksanaan']) <= $deadline ? 'tercapai' : 'terlambat';
            } else {
                $hasil = $now > $deadline ? 'terlewat' : 'berjalan';
            }

            $target[$id_item] = [
                'deadline' => $deadline,
                'status' => $hasil
            ];

            if ($hasil === 'terlewat' || $hasil === 'terlambat') {
                $peringatan[] = [
                    'level' => 'bahaya',
                    'text' => 'Target ' . (int) $item['target_menit'] . ' menit sejak trigger merah '
                        . ($hasil === 'terlewat' ? 'terlewat' : 'tidak tercapai')
                        . ': ' . mb_strimwidth($item['teks_standar'], 0, 70, '…')
                ];
            }
        }

        // Jeda antar dosis berurutan (contoh Nifedipin ke-2 minimal 30 menit setelah ke-1).
        $dosis = [];
        foreach ($items as $id_item => $item) {
            if ($item['kelompok_dosis'] !== null && $status($id_item) === 'dilakukan') {
                $dosis[$item['kelompok_dosis']][(int) $item['dosis_ke']] = $id_item;
            }
        }

        foreach ($dosis as $urutan) {
            foreach ($urutan as $ke => $id_item) {
                $item = $items[$id_item];
                $sebelumnya = isset($urutan[$ke - 1]) ? $pelaksanaan[$urutan[$ke - 1]] : null;
                $sekarang = $pelaksanaan[$id_item];

                if (
                    !$sebelumnya
                    || !$item['jeda_menit']
                    || !$sebelumnya['waktu_pelaksanaan']
                    || !$sekarang['waktu_pelaksanaan']
                ) {
                    continue;
                }

                $jeda = (strtotime($sekarang['waktu_pelaksanaan']) - strtotime($sebelumnya['waktu_pelaksanaan'])) / 60;
                if ($jeda < (int) $item['jeda_menit']) {
                    $peringatan[] = [
                        'level' => 'risiko',
                        'text' => 'Dosis ke-' . $ke . ' diberikan ' . (int) $jeda . ' menit setelah dosis sebelumnya (minimal '
                            . (int) $item['jeda_menit'] . ' menit): ' . mb_strimwidth($item['teks_standar'], 0, 50, '…')
                    ];
                }
            }
        }

        $selesai = count(array_filter($unit));

        return [
            'total' => count($unit),
            'selesai' => $selesai,
            'persen' => $unit ? (int) floor($selesai * 100 / count($unit)) : null,
            'target' => $target,
            'peringatan' => $peringatan
        ];
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
            log_message('error', 'Operasi stabilisasi gagal: ' . $exception->getMessage());

            return false;
        } finally {
            $this->db->db_debug = $debug;
        }
    }
}
