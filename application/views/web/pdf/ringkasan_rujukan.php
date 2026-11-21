<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>
<?php
$e = function ($value) {
    return html_escape($value === null || $value === '' ? '—' : (string) $value);
};

$tanggal = function ($value) {
    $date = $value ? DateTime::createFromFormat('!Y-m-d', $value) : false;

    return $date ? $date->format('d/m/Y') : '—';
};

$waktu = function ($value) {
    $date = $value ? DateTime::createFromFormat('Y-m-d H:i:s', $value) : false;

    return $date ? $date->format('d/m/Y H:i') : '—';
};

$jam = function ($value) {
    return $value ? substr($value, 11, 5) : '—';
};

$durasi = function ($dari, $sampai) {
    if (!$dari || !$sampai) {
        return '—';
    }

    // Waktu tiba hanya sampai menit, sehingga selisih di menit yang sama bisa negatif beberapa detik.
    $menit = max(0, (int) floor((strtotime($sampai) - strtotime($dari)) / 60));

    return $menit >= 60 ? floor($menit / 60) . ' jam ' . ($menit % 60) . ' mnt' : $menit . ' mnt';
};

$status = $status_rujukan[$patient['status_rujukan']];
$zona_warna = [
    'normal' => '#198754',
    'kuning' => '#b38600',
    'merah' => '#c62828'
];
$flag_warna = [
    'bahaya' => ['#fdecee', '#c62828'],
    'risiko' => ['#fff7e0', '#b38600'],
    'data' => ['#e8f4fb', '#2f6fa8']
];
?>
<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <title>Ringkasan rujukan <?= $e($patient['nama']) ?></title>
    <style>
        @page { margin: 22mm 14mm 18mm 14mm; }
        body { font-family: "DejaVu Sans", sans-serif; font-size: 8.6pt; color: #243746; line-height: 1.35; }
        h1 { font-size: 14pt; margin: 0; color: #152d3b; }
        h2 { font-size: 10pt; margin: 14px 0 6px; padding: 4px 8px; background: #152d3b; color: #fff; }
        h2 .huruf { display: inline-block; width: 14px; margin-right: 6px; padding: 0 3px; background: #087f78; text-align: center; }
        h3 { font-size: 8.6pt; margin: 8px 0 4px; color: #087f78; text-transform: uppercase; }
        table { width: 100%; border-collapse: collapse; }
        .kop td { vertical-align: top; }
        .kop .logo { font-size: 15pt; font-weight: bold; color: #087f78; letter-spacing: 1px; }
        .kop .sub { font-size: 7.5pt; color: #617382; }
        .kop .meta { text-align: right; font-size: 7.5pt; color: #617382; }
        .garis { border-bottom: 2px solid #087f78; margin: 6px 0 10px; }
        .badge { display: inline-block; padding: 1px 6px; border-radius: 3px; font-size: 7.5pt; font-weight: bold; color: #fff; }
        .info td { padding: 3px 6px; border: 1px solid #dfe7ec; vertical-align: top; }
        .info .label { width: 23%; background: #f4f7fa; color: #4b5f6d; }
        .data th { background: #eef3f6; color: #34495a; font-size: 7.6pt; text-align: left; padding: 4px 5px; border: 1px solid #dfe7ec; }
        .data td { padding: 3px 5px; border: 1px solid #dfe7ec; vertical-align: top; }
        .flag { margin: 0 0 3px; padding: 3px 7px; border-left: 3px solid; }
        .muted { color: #7a8c99; }
        .kotak { padding: 6px 8px; border: 1px solid #dfe7ec; }
        .skor { font-size: 20pt; font-weight: bold; line-height: 1; }
        .kecil { font-size: 7.5pt; }
        .tengah { text-align: center; }
        .kanan { text-align: right; }
        .tebal { font-weight: bold; }
        .tidak-pecah, tr { page-break-inside: avoid; }
        .catatan-kaki { margin-top: 14px; padding-top: 6px; border-top: 1px solid #dfe7ec; font-size: 7pt; color: #7a8c99; }
    </style>
</head>
<body>
    <table class="kop">
        <tr>
            <td style="width: 60%;">
                <div class="logo">EMNIRA</div>
                <div class="sub">Electronic Maternal Neonatal Integrated Referral and Analysis System</div>
            </td>
            <td class="meta">
                <div>No. dokumen: <strong>RJK-<?= str_pad((int) $patient['id_kunjungan'], 6, '0', STR_PAD_LEFT) ?></strong></div>
                <div>Dicetak: <?= $e(date('d/m/Y H:i')) ?></div>
                <div>Oleh: <?= $e($dicetak_oleh) ?></div>
            </td>
        </tr>
    </table>
    <div class="garis"></div>

    <table>
        <tr>
            <td><h1>Ringkasan Rujukan Maternal</h1></td>
            <td class="kanan"><span class="badge" style="background: <?= $patient['status_rujukan'] === 'draf' ? '#7a8c99' : '#087f78' ?>;">STATUS: <?= $e(mb_strtoupper($status['label'])) ?></span></td>
        </tr>
    </table>

    <table class="info" style="margin-top: 8px;">
        <tr>
            <td class="label">Nama pasien</td>
            <td class="tebal"><?= $e($patient['nama']) ?></td>
            <td class="label">No. RM</td>
            <td><?= $e($patient['no_rm']) ?></td>
        </tr>
        <tr>
            <td class="label">Tgl. lahir / umur</td>
            <td><?= $e($tanggal($patient['tgl_lahir'])) ?> (<?= $e($umur) ?>)</td>
            <td class="label">Tgl. registrasi</td>
            <td><?= $e($tanggal($patient['tgl_reg'])) ?></td>
        </tr>
        <tr>
            <td class="label">Alamat</td>
            <td colspan="3"><?= $e($patient['alamat']) ?></td>
        </tr>
        <tr>
            <td class="label">Faskes perujuk</td>
            <td><?= $e($patient['nama_faskes_perujuk']) ?></td>
            <td class="label">Faskes tujuan</td>
            <td><?= $e($patient['nama_faskes_tujuan']) ?></td>
        </tr>
        <tr>
            <td class="label">Dikirim</td>
            <td><?= $e($waktu($patient['waktu_dikirim'])) ?><?= $patient['nama_pengirim'] ? ' · ' . $e($patient['nama_pengirim']) : '' ?></td>
            <td class="label">Diterima</td>
            <td><?= $e($waktu($patient['waktu_diterima'])) ?><?= $patient['nama_penerima'] ? ' · ' . $e($patient['nama_penerima']) : '' ?></td>
        </tr>
        <tr>
            <td class="label">Tiba</td>
            <td><?= $e($waktu($patient['waktu_tiba'])) ?></td>
            <td class="label">Waktu tempuh</td>
            <td><?= $e($durasi($patient['waktu_dikirim'], $patient['waktu_tiba'])) ?> <span class="muted">(target &lt; <?= (int) ($target_tiba_menit / 60) ?> jam)</span></td>
        </tr>
        <?php if ($patient['catatan_penerimaan']): ?>
            <tr>
                <td class="label">Catatan penerimaan</td>
                <td colspan="3"><?= $e($patient['catatan_penerimaan']) ?></td>
            </tr>
        <?php endif; ?>
    </table>

    <?php if ($flags): ?>
        <h3>Kewaspadaan</h3>
        <?php foreach ($flags as $flag): ?>
            <?php list($latar, $warna) = $flag_warna[$flag['level']]; ?>
            <div class="flag" style="background: <?= $latar ?>; border-color: <?= $warna ?>; color: <?= $warna ?>;"><?= $e($flag['text']) ?></div>
        <?php endforeach; ?>
    <?php endif; ?>

    <h2><span class="huruf">S</span>Situation</h2>
    <table class="info">
        <tr>
            <td class="label">Diagnosa kerja</td>
            <td>
                <?php if (!$diagnosis): ?>—<?php endif; ?>
                <?php foreach ($diagnosis as $item): ?>
                    <div><?= $e($item['kode'] . ' ' . $item['nama']) ?></div>
                <?php endforeach; ?>
            </td>
        </tr>
        <tr>
            <td class="label">Usia kehamilan</td>
            <td><?= $e($patient['usia_kehamilan']) ?> · HPHT <?= $e($tanggal($patient['hpht'])) ?> · HPL <?= $e($tanggal($patient['hpl'])) ?></td>
        </tr>
        <tr>
            <td class="label">Lokasi asal</td>
            <td><?= $e($patient['lokasi']) ?></td>
        </tr>
    </table>

    <h2><span class="huruf">B</span>Background</h2>
    <table class="info">
        <tr>
            <td class="label">Status obstetri</td>
            <td>
                <?php if ($gpa): ?>
                    <strong><?= $e($gpa['kode_gpa']) ?></strong> · anak hidup <?= (int) $gpa['anak_hidup'] ?> · anak meninggal <?= (int) $gpa['anak_meninggal'] ?>
                <?php else: ?>
                    <?= $e($patient['status_obstetri']) ?>
                <?php endif; ?>
            </td>
        </tr>
        <tr>
            <td class="label">Komorbid ibu</td>
            <td>
                <?php if (!$kondisi_ibu): ?>—<?php endif; ?>
                <?php foreach ($kondisi_ibu as $kode => $catatan): ?>
                    <?php $pilihan = isset($pilihan_map[$kode]) ? $pilihan_map[$kode] : null; ?>
                    <div>
                        <?= $e($pilihan ? $pilihan['label'] : $kode) ?>
                        <?php if ($pilihan && $pilihan['kode_icd10']): ?><span class="muted">[<?= $e($pilihan['kode_icd10']) ?>]</span><?php endif; ?>
                        <?php if ($catatan): ?>— <?= $e($catatan) ?><?php endif; ?>
                    </div>
                <?php endforeach; ?>
            </td>
        </tr>
    </table>

    <?php if ($riwayat): ?>
        <h3>Riwayat kehamilan terdahulu</h3>
        <table class="data">
            <tr>
                <th style="width: 6%;">Ke</th>
                <th style="width: 9%;">Tahun</th>
                <th style="width: 18%;">Luaran</th>
                <th style="width: 20%;">Metode</th>
                <th>Penyakit / komplikasi</th>
                <th style="width: 22%;">Keterangan</th>
            </tr>
            <?php foreach ($riwayat as $ke => $row): ?>
                <tr>
                    <td class="tengah"><?= (int) $ke ?></td>
                    <td><?= $e($row['tahun_persalinan']) ?></td>
                    <td><?= $e($row['luaran'] ? $riwayat_options['luaran'][$row['luaran']] : null) ?></td>
                    <td><?= $e($row['metode_persalinan'] ? $riwayat_options['metode_persalinan'][$row['metode_persalinan']] : null) ?></td>
                    <td>
                        <?php
                        $label_kondisi = array_map(function ($kode) use ($pilihan_map) {
                            return isset($pilihan_map[$kode]) ? $pilihan_map[$kode]['label'] : $kode;
                        }, $row['kondisi']);
                        ?>
                        <?= $e(implode(', ', $label_kondisi)) ?>
                    </td>
                    <td><?= $e($row['keterangan']) ?></td>
                </tr>
            <?php endforeach; ?>
        </table>
    <?php endif; ?>

    <h2><span class="huruf">A</span>Assessment</h2>
    <?php if ($observasi_terakhir && $observasi_terakhir['meows_zona'] !== null): ?>
        <?php $warna_zona = $zona_warna[$observasi_terakhir['meows_zona']]; ?>
        <table class="tidak-pecah">
            <tr>
                <td class="kotak tengah" style="width: 20%; border-color: <?= $warna_zona ?>; color: <?= $warna_zona ?>;">
                    <div class="kecil tebal">MEOWS TERAKHIR</div>
                    <div class="skor"><?= (int) $observasi_terakhir['meows_skor'] ?></div>
                    <div class="tebal">Zona <?= $e(ucfirst($observasi_terakhir['meows_zona'])) ?></div>
                </td>
                <td class="kotak" style="padding-left: 10px;">
                    <div class="tebal"><?= $e($waktu($observasi_terakhir['waktu_observasi'])) ?></div>
                    <div>
                        <?= (int) $observasi_terakhir['meows_jumlah_merah'] ?> parameter merah ·
                        <?= (int) $observasi_terakhir['meows_jumlah_kuning'] ?> kuning ·
                        <?= (int) $observasi_terakhir['meows_parameter_terisi'] ?>/<?= count($meows_rules['parameter']) ?> terisi
                    </div>
                    <div>
                        MAP <?= $e($observasi_terakhir['map_mmhg']) ?> · SI <?= $e($observasi_terakhir['shock_index']) ?>
                        · DJJ <?= $e($observasi_terakhir['djj_per_menit']) ?> · TBJ <?= $e($observasi_terakhir['tbj_gram'] !== null ? $observasi_terakhir['tbj_gram'] . ' g' : null) ?>
                        · Urin <?= $e($observasi_terakhir['urin_ml_kg_jam'] !== null ? $observasi_terakhir['urin_ml_kg_jam'] . ' mL/kgBB/jam' : null) ?>
                    </div>
                </td>
            </tr>
        </table>

        <table class="data" style="margin-top: 6px;">
            <tr>
                <th>Parameter MEOWS</th>
                <th style="width: 22%;">Nilai</th>
                <th style="width: 10%;" class="tengah">Skor</th>
                <th style="width: 14%;">Zona</th>
            </tr>
            <?php foreach ($meows_rules['parameter'] as $field => $parameter): ?>
                <?php
                $hasil = $observasi_terakhir_hasil['parameter'][$field];
                $nilai = $observasi_terakhir[$field];
                if ($nilai !== null && $parameter['tipe'] === 'pilihan') {
                    $nilai = isset($parameter['pilihan'][$nilai]) ? $parameter['pilihan'][$nilai]['label'] : $nilai;
                } elseif ($nilai !== null) {
                    $nilai = (float) $nilai == floor((float) $nilai) ? (int) $nilai : str_replace('.', ',', (string) (float) $nilai);
                    $nilai .= isset($parameter['satuan_tampil']) ? $parameter['satuan_tampil'] : ' ' . $parameter['satuan'];
                }
                ?>
                <tr>
                    <td><?= $e($parameter['label']) ?></td>
                    <td><?= $e($nilai) ?></td>
                    <td class="tengah"><?= $hasil ? (int) $hasil['skor'] : '—' ?></td>
                    <td style="color: <?= $hasil ? $zona_warna[$hasil['zona']] : '#7a8c99' ?>;" class="tebal"><?= $hasil ? $e(ucfirst($hasil['zona'])) : '—' ?></td>
                </tr>
            <?php endforeach; ?>
        </table>
    <?php else: ?>
        <p class="muted">Belum ada observasi MEOWS.</p>
    <?php endif; ?>

    <?php if (count($observasi) > 1): ?>
        <h3>Tren observasi</h3>
        <table class="data">
            <tr>
                <th>Waktu</th>
                <th class="tengah">TD</th>
                <th class="tengah">Nadi</th>
                <th class="tengah">RR</th>
                <th class="tengah">SpO2</th>
                <th class="tengah">Suhu</th>
                <th class="tengah">MEOWS</th>
            </tr>
            <?php foreach (array_reverse(array_slice($observasi, 0, 12)) as $row): ?>
                <tr>
                    <td><?= $e($waktu($row['waktu_observasi'])) ?></td>
                    <td class="tengah"><?= $e($row['td_sistolik_mmhg']) ?>/<?= $e($row['td_diastolik_mmhg']) ?></td>
                    <td class="tengah"><?= $e($row['nadi_per_menit']) ?></td>
                    <td class="tengah"><?= $e($row['rr_per_menit']) ?></td>
                    <td class="tengah"><?= $e($row['spo2_persen']) ?></td>
                    <td class="tengah"><?= $e($row['suhu_celsius']) ?></td>
                    <td class="tengah tebal" style="color: <?= $row['meows_zona'] ? $zona_warna[$row['meows_zona']] : '#7a8c99' ?>;"><?= $e($row['meows_skor']) ?></td>
                </tr>
            <?php endforeach; ?>
        </table>
    <?php endif; ?>

    <?php if ($sbar['kriteria']): ?>
        <h3>Red flag terpenuhi</h3>
        <?php foreach ($sbar['kriteria'] as $teks): ?>
            <div>• <?= $e($teks) ?></div>
        <?php endforeach; ?>
    <?php endif; ?>

    <h2><span class="huruf">R</span>Recommendation — stabilisasi pra-rujukan</h2>
    <?php if (!$stabilisasi_items): ?>
        <p class="muted">Checklist stabilisasi belum diisi (diagnosa kerja belum dipilih).</p>
    <?php else: ?>
        <p>
            Kepatuhan stabilisasi:
            <strong><?= $stabilisasi_ringkasan['persen'] === null ? '—' : (int) $stabilisasi_ringkasan['persen'] . '%' ?></strong>
            (<?= (int) $stabilisasi_ringkasan['selesai'] ?> dari <?= (int) $stabilisasi_ringkasan['total'] ?> tindakan)
        </p>

        <h3>Sudah dilakukan</h3>
        <?php if ($sbar['dilakukan']): ?>
            <table class="data">
                <tr>
                    <th style="width: 10%;">Jam</th>
                    <th>Tindakan</th>
                </tr>
                <?php foreach ($sbar['dilakukan'] as $row): ?>
                    <tr>
                        <td class="tebal"><?= $e($jam($row['waktu'])) ?></td>
                        <td><?= $e($row['teks']) ?><?= $row['proses'] ? ' <span class="muted">(dalam proses)</span>' : '' ?></td>
                    </tr>
                <?php endforeach; ?>
            </table>
        <?php else: ?>
            <p class="muted">Belum ada tindakan tercatat.</p>
        <?php endif; ?>

        <?php if ($sbar['belum']): ?>
            <h3 style="color: #c62828;">Belum dilakukan (perlu dilanjutkan)</h3>
            <?php foreach ($sbar['belum'] as $row): ?>
                <div>• <?= $e($row['teks']) ?><?= $row['alasan'] ? ' <span class="muted">— alasan: ' . $e($row['alasan']) . '</span>' : '' ?></div>
            <?php endforeach; ?>
        <?php endif; ?>
    <?php endif; ?>

    <div class="catatan-kaki">
        Dokumen ini dihasilkan otomatis oleh EMNIRA dari data yang tercatat saat dicetak.
        Skor MEOWS memakai aturan <?= $e($meows_rules['versi']) ?>. Gunakan sebagai ringkasan rujukan; keputusan klinis tetap pada tenaga kesehatan yang menangani.
    </div>
</body>
</html>
