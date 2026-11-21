<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>
<?php
$id = (int) $patient['id_kunjungan'];
$status = $status_rujukan[$patient['status_rujukan']];

$format_tanggal = function ($value) {
    $date = $value ? DateTime::createFromFormat('!Y-m-d', $value) : false;

    return $date ? $date->format('d/m/Y') : '—';
};

$format_waktu = function ($value) {
    $date = $value ? DateTime::createFromFormat('Y-m-d H:i:s', $value) : false;

    return $date ? $date->format('d/m/Y H:i') : '—';
};

$jam = function ($value) {
    return $value ? substr($value, 11, 5) : '—';
};

$selisih_menit = function ($dari, $sampai) {
    // Waktu tiba hanya sampai menit; selisih di menit yang sama tidak boleh tampil negatif.
    return $dari && $sampai ? max(0, (int) floor((strtotime($sampai) - strtotime($dari)) / 60)) : null;
};

$durasi = function ($menit) {
    if ($menit === null) {
        return '—';
    }

    return $menit >= 60 ? floor($menit / 60) . ' jam ' . ($menit % 60) . ' mnt' : $menit . ' mnt';
};

// $umur disediakan libraries/Ringkasan_rujukan.php.

$zona_class = [
    'normal' => 'success',
    'kuning' => 'warning',
    'merah' => 'danger'
];

$flag_class = [
    'bahaya' => 'danger',
    'risiko' => 'warning',
    'data' => 'info'
];

// Waktu respon dan waktu tempuh dibandingkan dengan target tiba < 2 jam.
$menit_respon = $selisih_menit($patient['waktu_dikirim'], $patient['waktu_diterima']);
$menit_tempuh = $patient['waktu_tiba']
    ? $selisih_menit($patient['waktu_dikirim'], $patient['waktu_tiba'])
    : $selisih_menit($patient['waktu_dikirim'], date('Y-m-d H:i:s'));

// R: dipisah oleh libraries/Ringkasan_rujukan.php (sama dengan isi dokumen PDF).
$dilakukan = $sbar['dilakukan'];
$belum = $sbar['belum'];
$kriteria = $sbar['kriteria'];

// Tren MEOWS: maksimal 12 observasi terakhir, urut waktu.
$tren = array_reverse(array_slice($observasi, 0, 12));
$skor_maks = max(10, $tren ? max(array_map('intval', array_column($tren, 'meows_skor'))) : 0);
?>
<nav aria-label="breadcrumb" class="mb-3">
    <ol class="breadcrumb small mb-0">
        <li class="breadcrumb-item"><a href="<?= html_escape(site_url('web/rujukan')) ?>">Rujukan masuk</a></li>
        <li class="breadcrumb-item active" aria-current="page"><?= html_escape($patient['nama'] ?: 'Pasien') ?></li>
    </ol>
</nav>

<div class="sbar-layout">
    <div class="sbar-main">
        <section class="card shadow-sm mb-3">
            <div class="card-body p-4">
                <div class="d-flex flex-wrap justify-content-between align-items-start gap-3">
                    <div>
                        <h1 class="h4 fw-bold mb-1"><?= html_escape($patient['nama'] ?: '—') ?></h1>
                        <div class="text-secondary small">
                            RM <?= html_escape($patient['no_rm'] ?: '—') ?> · <?= html_escape($umur) ?>
                            · dari <strong><?= html_escape($patient['nama_faskes_perujuk'] ?: '—') ?></strong>
                        </div>
                    </div>
                    <span class="badge <?= $status['badge'] ?> fs-6"><?= html_escape($status['label']) ?></span>
                </div>

                <?php if ($flags): ?>
                    <ul class="list-unstyled d-flex flex-column gap-1 mt-3 mb-0">
                        <?php foreach ($flags as $flag): ?>
                            <li class="flag flag-<?= $flag_class[$flag['level']] ?>"><?= html_escape($flag['text']) ?></li>
                        <?php endforeach; ?>
                    </ul>
                <?php endif; ?>
            </div>
        </section>

        <section class="card shadow-sm mb-3 sbar-section">
            <div class="card-body p-4">
                <h2 class="sbar-title"><span>S</span> Situation</h2>
                <dl class="sbar-dl">
                    <dt>Diagnosa kerja</dt>
                    <dd><?= $diagnosis ? html_escape(implode(', ', array_map(function ($d) { return $d['kode'] . ' ' . $d['nama']; }, $diagnosis))) : '—' ?></dd>
                    <dt>Usia kehamilan</dt>
                    <dd><?= html_escape($patient['usia_kehamilan'] ?: '—') ?> · HPHT <?= html_escape($format_tanggal($patient['hpht'])) ?> · HPL <?= html_escape($format_tanggal($patient['hpl'])) ?></dd>
                    <dt>Lokasi asal</dt>
                    <dd><?= html_escape($patient['lokasi'] ?: '—') ?></dd>
                    <dt>Alamat</dt>
                    <dd><?= html_escape($patient['alamat'] ?: '—') ?></dd>
                </dl>
            </div>
        </section>

        <section class="card shadow-sm mb-3 sbar-section">
            <div class="card-body p-4">
                <h2 class="sbar-title"><span>B</span> Background</h2>
                <dl class="sbar-dl">
                    <dt>Status obstetri</dt>
                    <dd>
                        <?php if ($gpa): ?>
                            <strong><?= html_escape($gpa['kode_gpa']) ?></strong>
                            · anak hidup <?= (int) $gpa['anak_hidup'] ?> · anak meninggal <?= (int) $gpa['anak_meninggal'] ?>
                        <?php else: ?>
                            <?= html_escape($patient['status_obstetri'] ?: '—') ?>
                        <?php endif; ?>
                    </dd>
                    <dt>Komorbid ibu</dt>
                    <dd>
                        <?php if (!$kondisi_ibu): ?>
                            —
                        <?php endif; ?>
                        <?php foreach ($kondisi_ibu as $kode => $catatan): ?>
                            <?php $pilihan = isset($pilihan_map[$kode]) ? $pilihan_map[$kode] : null; ?>
                            <div>
                                <?= html_escape($pilihan ? $pilihan['label'] : $kode) ?>
                                <?php if ($pilihan && $pilihan['kode_icd10']): ?><span class="badge text-bg-light border font-monospace"><?= html_escape($pilihan['kode_icd10']) ?></span><?php endif; ?>
                                <?php if ($catatan): ?><span class="text-secondary">— <?= html_escape($catatan) ?></span><?php endif; ?>
                            </div>
                        <?php endforeach; ?>
                    </dd>
                </dl>

                <?php if ($riwayat): ?>
                    <div class="table-responsive mt-2">
                        <table class="table table-sm align-middle mb-0 sbar-table">
                            <thead>
                                <tr>
                                    <th scope="col">Ke</th>
                                    <th scope="col">Tahun</th>
                                    <th scope="col">Luaran</th>
                                    <th scope="col">Metode</th>
                                    <th scope="col">Penyakit / komplikasi</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($riwayat as $ke => $row): ?>
                                    <tr>
                                        <td><?= (int) $ke ?></td>
                                        <td><?= html_escape($row['tahun_persalinan'] ?: '—') ?></td>
                                        <td><?= html_escape($row['luaran'] ? $riwayat_options['luaran'][$row['luaran']] : '—') ?></td>
                                        <td><?= html_escape($row['metode_persalinan'] ? $riwayat_options['metode_persalinan'][$row['metode_persalinan']] : '—') ?></td>
                                        <td>
                                            <?php foreach ($row['kondisi'] as $kode): ?>
                                                <?php $pilihan = isset($pilihan_map[$kode]) ? $pilihan_map[$kode] : null; ?>
                                                <span class="badge <?= $pilihan && $pilihan['tingkat_risiko'] === 'bahaya' ? 'text-bg-danger' : 'text-bg-light border' ?>"><?= html_escape($pilihan ? $pilihan['label'] : $kode) ?></span>
                                            <?php endforeach; ?>
                                            <?= $row['kondisi'] ? '' : '—' ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>
        </section>

        <section class="card shadow-sm mb-3 sbar-section">
            <div class="card-body p-4">
                <h2 class="sbar-title"><span>A</span> Assessment</h2>

                <?php if ($observasi_terakhir && $observasi_terakhir['meows_zona'] !== null): ?>
                    <?php $zona_terakhir = $observasi_terakhir['meows_zona']; ?>
                    <div class="meows-last meows-last-<?= $zona_class[$zona_terakhir] ?>">
                        <div class="meows-last-score">
                            <span>MEOWS terakhir</span>
                            <strong><?= (int) $observasi_terakhir['meows_skor'] ?></strong>
                            <em>Zona <?= html_escape(ucfirst($zona_terakhir)) ?></em>
                        </div>
                        <div class="meows-last-info">
                            <div class="fw-semibold"><?= html_escape($format_waktu($observasi_terakhir['waktu_observasi'])) ?></div>
                            <div class="small">
                                TD <?= html_escape(($observasi_terakhir['td_sistolik_mmhg'] ?? '—') . '/' . ($observasi_terakhir['td_diastolik_mmhg'] ?? '—')) ?>
                                · Nadi <?= html_escape($observasi_terakhir['nadi_per_menit'] ?? '—') ?>
                                · RR <?= html_escape($observasi_terakhir['rr_per_menit'] ?? '—') ?>
                                · SpO2 <?= html_escape($observasi_terakhir['spo2_persen'] ?? '—') ?>%
                                · Suhu <?= html_escape($observasi_terakhir['suhu_celsius'] ?? '—') ?>
                            </div>
                            <div class="small">
                                MAP <?= html_escape($observasi_terakhir['map_mmhg'] ?? '—') ?>
                                · SI <?= html_escape($observasi_terakhir['shock_index'] ?? '—') ?>
                                · DJJ <?= html_escape($observasi_terakhir['djj_per_menit'] ?? '—') ?>
                                · TBJ <?= html_escape($observasi_terakhir['tbj_gram'] !== null ? $observasi_terakhir['tbj_gram'] . ' g' : '—') ?>
                            </div>
                        </div>
                    </div>
                <?php else: ?>
                    <p class="text-secondary">Belum ada observasi MEOWS.</p>
                <?php endif; ?>

                <?php if (count($tren) > 1): ?>
                    <h3 class="sbar-sub mt-3">Tren MEOWS (<?= count($tren) ?> observasi)</h3>
                    <?php $lebar = count($tren) * 44; ?>
                    <svg class="meows-trend" viewBox="0 0 <?= $lebar ?> 110" preserveAspectRatio="xMinYMid meet" role="img" aria-label="Tren skor MEOWS">
                        <?php foreach ($tren as $i => $row): ?>
                            <?php
                            $skor = (int) $row['meows_skor'];
                            $tinggi = max(2, round($skor / $skor_maks * 70));
                            $x = $i * 44 + 8;
                            ?>
                            <rect class="bar-<?= html_escape((string) $row['meows_zona']) ?>" x="<?= $x ?>" y="<?= 85 - $tinggi ?>" width="28" height="<?= $tinggi ?>" rx="4"></rect>
                            <text x="<?= $x + 14 ?>" y="<?= 80 - $tinggi ?>" text-anchor="middle" class="trend-value"><?= $skor ?></text>
                            <text x="<?= $x + 14 ?>" y="103" text-anchor="middle" class="trend-label"><?= html_escape($jam($row['waktu_observasi'])) ?></text>
                        <?php endforeach; ?>
                    </svg>
                <?php endif; ?>

                <?php if ($kriteria): ?>
                    <h3 class="sbar-sub mt-3">Red flag terpenuhi</h3>
                    <ul class="mb-0 small">
                        <?php foreach ($kriteria as $teks): ?>
                            <li><?= html_escape($teks) ?></li>
                        <?php endforeach; ?>
                    </ul>
                <?php endif; ?>
            </div>
        </section>

        <section class="card shadow-sm mb-3 sbar-section">
            <div class="card-body p-4">
                <h2 class="sbar-title"><span>R</span> Recommendation — stabilisasi pra-rujukan</h2>
                <?php if (!$stabilisasi_items): ?>
                    <p class="text-secondary mb-0">Checklist stabilisasi belum diisi (diagnosa kerja belum dipilih).</p>
                <?php else: ?>
                    <p class="small mb-2">
                        Kepatuhan stabilisasi:
                        <strong><?= $stabilisasi_ringkasan['persen'] === null ? '—' : (int) $stabilisasi_ringkasan['persen'] . '%' ?></strong>
                        (<?= (int) $stabilisasi_ringkasan['selesai'] ?> dari <?= (int) $stabilisasi_ringkasan['total'] ?> tindakan)
                    </p>

                    <h3 class="sbar-sub">Sudah dilakukan</h3>
                    <?php if ($dilakukan): ?>
                        <ul class="sbar-timeline">
                            <?php foreach ($dilakukan as $row): ?>
                                <li>
                                    <span class="sbar-time"><?= html_escape($jam($row['waktu'])) ?></span>
                                    <span><?= html_escape($row['teks']) ?><?php if ($row['proses']): ?> <span class="badge text-bg-warning">dalam proses</span><?php endif; ?></span>
                                </li>
                            <?php endforeach; ?>
                        </ul>
                    <?php else: ?>
                        <p class="small text-secondary">Belum ada tindakan tercatat.</p>
                    <?php endif; ?>

                    <?php if ($belum): ?>
                        <h3 class="sbar-sub text-danger mt-3">Belum dilakukan (perlu dilanjutkan)</h3>
                        <ul class="small mb-0">
                            <?php foreach ($belum as $row): ?>
                                <li>
                                    <?= html_escape($row['teks']) ?>
                                    <?php if ($row['alasan']): ?><span class="text-secondary">— alasan: <?= html_escape($row['alasan']) ?></span><?php endif; ?>
                                </li>
                            <?php endforeach; ?>
                        </ul>
                    <?php endif; ?>
                <?php endif; ?>
            </div>
        </section>

        <div class="d-flex flex-wrap gap-2">
            <a class="btn btn-sm btn-primary" href="<?= html_escape(site_url('web/rm/' . $id . '/pdf')) ?>" target="_blank" rel="noopener">Buka PDF</a>
            <a class="btn btn-sm btn-outline-primary" href="<?= html_escape(site_url('web/rm/' . $id . '/pdf?unduh=1')) ?>">Unduh PDF</a>
            <a class="btn btn-sm btn-outline-secondary" href="<?= html_escape(site_url('web/rm/' . $id)) ?>">Lihat rekam medis lengkap (baca saja)</a>
        </div>
    </div>

    <aside class="sbar-aside">
        <section class="card shadow-sm">
            <div class="card-body p-3">
                <h2 class="h6 fw-bold mb-3">Status rujukan</h2>
                <ol class="sbar-steps">
                    <li class="is-done">
                        <strong>Dikirim</strong>
                        <span><?= html_escape($format_waktu($patient['waktu_dikirim'])) ?></span>
                        <small><?= html_escape($patient['nama_pengirim'] ?: '—') ?> · <?= html_escape($patient['nama_faskes_perujuk'] ?: '—') ?></small>
                    </li>
                    <li class="<?= $patient['waktu_diterima'] ? 'is-done' : 'is-current' ?>">
                        <strong>Diterima</strong>
                        <?php if ($patient['waktu_diterima']): ?>
                            <span><?= html_escape($format_waktu($patient['waktu_diterima'])) ?> · respon <?= html_escape($durasi($menit_respon)) ?></span>
                            <small><?= html_escape($patient['nama_penerima'] ?: '—') ?></small>
                        <?php else: ?>
                            <span>Menunggu</span>
                        <?php endif; ?>
                    </li>
                    <li class="<?= $patient['waktu_tiba'] ? 'is-done' : ($patient['waktu_diterima'] ? 'is-current' : '') ?>">
                        <strong>Tiba</strong>
                        <?php if ($patient['waktu_tiba']): ?>
                            <span><?= html_escape($format_waktu($patient['waktu_tiba'])) ?></span>
                            <small><?= html_escape($patient['nama_pencatat_tiba'] ?: '—') ?></small>
                        <?php else: ?>
                            <span>Belum</span>
                        <?php endif; ?>
                    </li>
                </ol>

                <div class="sbar-metric <?= $menit_tempuh !== null && $menit_tempuh > $target_tiba_menit ? 'is-late' : '' ?>">
                    <span><?= $patient['waktu_tiba'] ? 'Waktu tempuh' : 'Sejak dikirim' ?></span>
                    <strong><?= html_escape($durasi($menit_tempuh)) ?></strong>
                    <small>Target tiba &lt; <?= (int) ($target_tiba_menit / 60) ?> jam</small>
                </div>

                <?php if ($patient['catatan_penerimaan']): ?>
                    <div class="small mt-3">
                        <div class="text-secondary fw-semibold">Catatan penerimaan</div>
                        <?= html_escape($patient['catatan_penerimaan']) ?>
                    </div>
                <?php endif; ?>

                <?php if ($boleh_terima): ?>
                    <?= form_open('web/rujukan/' . $id . '/terima', ['class' => 'mt-3']) ?>
                        <label class="form-label small" for="catatan_penerimaan">Catatan untuk perujuk (opsional)</label>
                        <textarea class="form-control form-control-sm mb-2" id="catatan_penerimaan" name="catatan_penerimaan" rows="2" maxlength="500" placeholder="Contoh: VK siap, Sp.OG on site"></textarea>
                        <button type="submit" class="btn btn-primary w-100">Terima rujukan</button>
                    <?= form_close() ?>
                <?php endif; ?>

                <?php if ($boleh_tiba): ?>
                    <?= form_open('web/rujukan/' . $id . '/tiba', ['class' => 'mt-3']) ?>
                        <label class="form-label small" for="waktu_tiba">Waktu pasien tiba</label>
                        <input class="form-control form-control-sm mb-2" type="datetime-local" id="waktu_tiba" name="waktu_tiba" value="<?= date('Y-m-d\TH:i') ?>" max="<?= date('Y-m-d\TH:i', time() + 300) ?>" required>
                        <button type="submit" class="btn btn-primary w-100">Catat kedatangan</button>
                    <?= form_close() ?>
                <?php endif; ?>
            </div>
        </section>
    </aside>
</div>
