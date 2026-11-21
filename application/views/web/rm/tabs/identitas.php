<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>
<?php
$rows = [
    'No. RM' => $patient['no_rm'],
    'Nama pasien' => $patient['nama'],
    'Alamat' => $patient['alamat'],
    'Tanggal lahir' => $format_date($patient['tgl_lahir']) . ($patient['tgl_lahir'] ? ' (' . $umur . ')' : ''),
    'Tanggal registrasi' => $format_date($patient['tgl_reg']),
    'Lokasi' => $patient['lokasi'],
    'Faskes perujuk' => $patient['nama_faskes_perujuk'],
    'Faskes tujuan' => $patient['nama_faskes_tujuan'],
    'HPHT' => $format_date($patient['hpht']),
    'HPL' => $format_date($patient['hpl']),
    'Usia kehamilan' => $patient['usia_kehamilan']
];
?>
<div class="card-body p-4">
    <div class="d-flex flex-wrap justify-content-between align-items-center gap-3 mb-4">
        <div>
            <h2 class="h5 mb-1">Identitas pasien</h2>
            <p class="text-secondary small mb-0">Data kunjungan dari menu Identitas pasien.</p>
        </div>
        <?php if ($boleh_edit): ?>
            <a class="btn btn-outline-secondary" href="<?= html_escape(site_url('web/rm') . '?' . http_build_query(['q' => $patient['no_rm']])) ?>">Ubah identitas</a>
        <?php endif; ?>
    </div>
    <dl class="row mb-0">
        <?php foreach ($rows as $label => $value): ?>
            <dt class="col-sm-4 col-lg-3 text-secondary fw-normal mb-2"><?= html_escape($label) ?></dt>
            <dd class="col-sm-8 col-lg-9 mb-2"><?= html_escape($value !== null && $value !== '' ? $value : '—') ?></dd>
        <?php endforeach; ?>
    </dl>
</div>
