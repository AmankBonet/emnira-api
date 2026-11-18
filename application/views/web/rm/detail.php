<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>
<?php
$id = (int) $patient['id_kunjungan'];
$state_data = $form_state ? $form_state['data'] : null;

$format_date = function ($value) {
    if (!$value) {
        return '—';
    }

    $date = DateTime::createFromFormat('!Y-m-d', $value);

    return $date ? $date->format('d/m/Y') : $value;
};

// Umur dihitung per tanggal registrasi agar sesuai kondisi saat kunjungan.
$umur = '—';
if ($patient['tgl_lahir']) {
    $lahir = DateTime::createFromFormat('!Y-m-d', $patient['tgl_lahir']);
    $acuan = DateTime::createFromFormat('!Y-m-d', $patient['tgl_reg'] ?: date('Y-m-d'));
    if ($lahir && $acuan && $lahir <= $acuan) {
        $umur = $lahir->diff($acuan)->y . ' tahun';
    }
}

$jumlah_riwayat = $gpa ? (int) $gpa['gravida'] - 1 : 0;
$riwayat_terisi = count(array_filter($riwayat, function ($row) {
    return $row['luaran'] !== null;
}));

$tab_status = [
    'identitas' => null,
    'obstetri' => $gpa ? $gpa['kode_gpa'] : 'Belum',
    'riwayat' => $gpa ? ($jumlah_riwayat > 0 ? $riwayat_terisi . '/' . $jumlah_riwayat : '—') : null,
    'kondisi' => $kondisi_ibu ? count($kondisi_ibu) : null
];

$flag_class = [
    'bahaya' => 'danger',
    'risiko' => 'warning',
    'data' => 'info'
];
?>
<nav aria-label="breadcrumb" class="mb-3">
    <ol class="breadcrumb small mb-0">
        <li class="breadcrumb-item"><a href="<?= html_escape(site_url('web/rm')) ?>">Identitas pasien</a></li>
        <li class="breadcrumb-item active" aria-current="page"><?= html_escape($patient['nama'] ?: 'Pasien') ?></li>
    </ol>
</nav>

<section class="card shadow-sm mb-4" aria-label="Ringkasan pasien">
    <div class="card-body p-4 d-flex flex-wrap gap-4 align-items-start justify-content-between">
        <div class="d-flex gap-3 align-items-center">
            <span class="user-avatar patient-avatar" aria-hidden="true"><?= html_escape(mb_strtoupper(mb_substr((string) $patient['nama'], 0, 1))) ?></span>
            <div>
                <h1 class="h4 fw-bold mb-1"><?= html_escape($patient['nama'] ?: '—') ?></h1>
                <div class="text-secondary small">
                    RM <?= html_escape($patient['no_rm'] ?: '—') ?>
                    · <?= html_escape($umur) ?>
                    · Registrasi <?= html_escape($format_date($patient['tgl_reg'])) ?>
                </div>
            </div>
        </div>
        <dl class="patient-facts mb-0">
            <div>
                <dt>Status obstetri</dt>
                <dd><?= html_escape($gpa ? $gpa['kode_gpa'] : ($patient['status_obstetri'] ?: '—')) ?></dd>
            </div>
            <div>
                <dt>Usia kehamilan</dt>
                <dd><?= html_escape($patient['usia_kehamilan'] ?: '—') ?></dd>
            </div>
            <div>
                <dt>HPL</dt>
                <dd><?= html_escape($format_date($patient['hpl'])) ?></dd>
            </div>
            <div>
                <dt>Rujukan</dt>
                <dd><?= html_escape($patient['nama_faskes_perujuk'] ?: '—') ?> → <?= html_escape($patient['nama_faskes_tujuan'] ?: '—') ?></dd>
            </div>
        </dl>
    </div>

    <?php if ($flags): ?>
        <div class="border-top px-4 py-3" role="region" aria-label="Kewaspadaan medis">
            <div class="small fw-semibold text-secondary mb-2">KEWASPADAAN</div>
            <ul class="list-unstyled d-flex flex-column gap-1 mb-0">
                <?php foreach ($flags as $flag): ?>
                    <li class="flag flag-<?= $flag_class[$flag['level']] ?>"><?= html_escape($flag['text']) ?></li>
                <?php endforeach; ?>
            </ul>
        </div>
    <?php endif; ?>
</section>

<ul class="nav nav-tabs rm-tabs" role="tablist">
    <?php foreach ($tabs as $key => $label): ?>
        <li class="nav-item">
            <a class="nav-link <?= $tab === $key ? 'active' : '' ?>" <?= $tab === $key ? 'aria-current="page"' : '' ?> href="<?= html_escape(site_url('web/rm/' . $id) . '?tab=' . $key) ?>">
                <?= html_escape($label) ?>
                <?php if ($tab_status[$key] !== null): ?>
                    <span class="badge rounded-pill text-bg-light border ms-1"><?= html_escape($tab_status[$key]) ?></span>
                <?php endif; ?>
            </a>
        </li>
    <?php endforeach; ?>
</ul>

<div class="card shadow-sm rm-tab-card">
    <?php if ($form_state && $form_state['errors']): ?>
        <div class="alert alert-danger m-4 mb-0" role="alert">
            <ul class="mb-0">
                <?php foreach ($form_state['errors'] as $error): ?>
                    <li><?= html_escape($error) ?></li>
                <?php endforeach; ?>
            </ul>
        </div>
    <?php endif; ?>

    <?php $this->load->view('web/rm/tabs/' . $tab, [
        'id' => $id,
        'state_data' => $state_data,
        'format_date' => $format_date,
        'umur' => $umur,
        'jumlah_riwayat' => $jumlah_riwayat
    ]); ?>
</div>
