<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>
<?php
$format_waktu = function ($value) {
    $date = $value ? DateTime::createFromFormat('Y-m-d H:i:s', $value) : false;

    return $date ? $date->format('d/m H:i') : '—';
};

$menit_sejak = function ($value) {
    return $value ? (int) floor((time() - strtotime($value)) / 60) : null;
};

$durasi = function ($menit) {
    if ($menit === null) {
        return '—';
    }

    return $menit >= 60 ? floor($menit / 60) . ' j ' . ($menit % 60) . ' mnt' : $menit . ' mnt';
};

$zona_class = [
    'merah' => 'danger',
    'kuning' => 'warning',
    'normal' => 'success'
];
?>
<div class="d-flex flex-wrap align-items-center justify-content-between gap-3 mb-4">
    <div>
        <div class="text-secondary small mb-2">Rujukan / Masuk</div>
        <h1 class="h3 fw-bold mb-2">Rujukan masuk</h1>
        <p class="text-secondary mb-0">Pasien yang dirujuk ke faskes Anda, diurutkan berdasarkan kegawatan.</p>
    </div>
    <div class="btn-group" role="group" aria-label="Filter status">
        <a class="btn btn-sm <?= $mode === 'aktif' ? 'btn-primary' : 'btn-outline-primary' ?>" href="<?= html_escape(site_url('web/rujukan')) ?>">Aktif</a>
        <a class="btn btn-sm <?= $mode === 'selesai' ? 'btn-primary' : 'btn-outline-primary' ?>" href="<?= html_escape(site_url('web/rujukan') . '?status=selesai') ?>">Sudah tiba</a>
    </div>
</div>

<div class="rujukan-list" data-auto-refresh="<?= $mode === 'aktif' ? '60' : '' ?>">
    <?php foreach ($rows as $row): ?>
        <?php
        $status = $status_rujukan[$row['status_rujukan']];
        $terakhir = $row['observasi'] ? $row['observasi'][0] : null;
        $sebelumnya = isset($row['observasi'][1]) ? $row['observasi'][1] : null;
        $zona = $terakhir ? $terakhir['meows_zona'] : null;

        $tren = '';
        if ($terakhir && $sebelumnya) {
            $selisih = (int) $terakhir['meows_skor'] - (int) $sebelumnya['meows_skor'];
            $tren = $selisih > 0 ? '↑ dari ' . (int) $sebelumnya['meows_skor'] : ($selisih < 0 ? '↓ dari ' . (int) $sebelumnya['meows_skor'] : '→ tetap');
        }

        // Rujukan yang masih di perjalanan dibandingkan dengan target tiba < 2 jam.
        $menit_perjalanan = $row['status_rujukan'] === 'tiba'
            ? (int) floor((strtotime($row['waktu_tiba']) - strtotime($row['waktu_dikirim'])) / 60)
            : $menit_sejak($row['waktu_dikirim']);
        $lewat_target = $menit_perjalanan !== null && $menit_perjalanan > $target_tiba_menit;
        ?>
        <a class="rujukan-card rujukan-<?= $zona ? $zona_class[$zona] : 'none' ?> <?= $row['status_rujukan'] === 'dikirim' ? 'is-new' : '' ?>" href="<?= html_escape(site_url('web/rujukan/' . (int) $row['id_kunjungan'])) ?>">
            <div class="rujukan-meows">
                <?php if ($terakhir): ?>
                    <span>MEOWS</span>
                    <strong><?= (int) $terakhir['meows_skor'] ?></strong>
                    <small><?= html_escape($tren ?: ucfirst((string) $zona)) ?></small>
                <?php else: ?>
                    <span>MEOWS</span>
                    <strong>—</strong>
                    <small>belum ada</small>
                <?php endif; ?>
            </div>
            <div class="rujukan-body">
                <div class="d-flex flex-wrap align-items-center gap-2">
                    <span class="fw-bold"><?= html_escape($row['nama'] ?: '—') ?></span>
                    <span class="text-secondary small">RM <?= html_escape($row['no_rm'] ?: '—') ?></span>
                    <?php if ($row['status_obstetri']): ?>
                        <span class="badge text-bg-light border"><?= html_escape($row['status_obstetri']) ?></span>
                    <?php endif; ?>
                    <?php if ($row['usia_kehamilan']): ?>
                        <span class="small text-secondary"><?= html_escape($row['usia_kehamilan']) ?></span>
                    <?php endif; ?>
                </div>
                <div class="small mt-1">
                    <?= $row['diagnosis'] ? html_escape(implode(', ', $row['diagnosis'])) : '<span class="text-secondary">Diagnosa kerja belum dipilih</span>' ?>
                </div>
                <div class="small text-secondary mt-1">
                    Dari <?= html_escape($row['nama_faskes_perujuk'] ?: '—') ?> · dikirim <?= html_escape($format_waktu($row['waktu_dikirim'])) ?>
                    · <?= $row['status_rujukan'] === 'tiba' ? 'waktu tempuh' : 'sudah' ?>
                    <span class="<?= $lewat_target ? 'text-danger fw-semibold' : '' ?>"><?= html_escape($durasi($menit_perjalanan)) ?></span>
                    <?php if ($row['kepatuhan'] !== null): ?>
                        · stabilisasi <?= (int) $row['kepatuhan'] ?>%
                    <?php endif; ?>
                </div>
            </div>
            <div class="rujukan-status">
                <span class="badge <?= $status['badge'] ?>"><?= $row['status_rujukan'] === 'dikirim' ? 'Menunggu diterima' : html_escape($status['label']) ?></span>
            </div>
        </a>
    <?php endforeach; ?>

    <?php if (!$rows): ?>
        <div class="card shadow-sm">
            <div class="card-body text-center py-5">
                <h2 class="h5"><?= $mode === 'aktif' ? 'Tidak ada rujukan aktif' : 'Belum ada rujukan yang tiba' ?></h2>
                <p class="text-secondary mb-0"><?= $mode === 'aktif' ? 'Rujukan baru akan muncul di sini setelah faskes perujuk mengirimnya.' : 'Riwayat rujukan yang sudah tiba tampil di sini.' ?></p>
            </div>
        </div>
    <?php endif; ?>
</div>

<?php if ($mode === 'aktif'): ?>
    <p class="small text-secondary mt-3">Daftar diperbarui otomatis setiap 60 detik.</p>
<?php endif; ?>
