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
    'kondisi' => $kondisi_ibu ? count($kondisi_ibu) : null,
    'vital' => $observasi_terakhir && $observasi_terakhir['meows_zona'] !== null
        ? 'MEOWS ' . (int) $observasi_terakhir['meows_skor']
        : null,
    'stabilisasi' => $stabilisasi_ringkasan['persen'] !== null
        ? $stabilisasi_ringkasan['persen'] . '%'
        : null
];

$meows_badge = [
    'normal' => 'success',
    'kuning' => 'warning',
    'merah' => 'danger'
];

$flag_class = [
    'bahaya' => 'danger',
    'risiko' => 'warning',
    'data' => 'info'
];

// Penanda paling berbahaya ditampilkan lebih dulu; sisanya dilipat agar form tidak tertimbun.
$flag_order = [
    'bahaya' => 0,
    'risiko' => 1,
    'data' => 2
];
usort($flags, function ($a, $b) use ($flag_order) {
    return $flag_order[$a['level']] - $flag_order[$b['level']];
});
$flags_tampil = array_slice($flags, 0, 3);
$flags_lain = array_slice($flags, 3);
$jumlah_bahaya = count(array_filter($flags, function ($flag) {
    return $flag['level'] === 'bahaya';
}));

$fakta = [
    'Status obstetri' => $gpa ? $gpa['kode_gpa'] : ($patient['status_obstetri'] ?: '—'),
    'Usia kehamilan' => $patient['usia_kehamilan'] ?: '—',
    'HPL' => $format_date($patient['hpl']),
    'Faskes perujuk' => $patient['nama_faskes_perujuk'] ?: '—',
    'Faskes tujuan' => $patient['nama_faskes_tujuan'] ?: '—'
];
?>
<nav aria-label="breadcrumb" class="mb-3">
    <ol class="breadcrumb small mb-0">
        <li class="breadcrumb-item"><a href="<?= html_escape(site_url('web/rm')) ?>">Identitas pasien</a></li>
        <li class="breadcrumb-item active" aria-current="page"><?= html_escape($patient['nama'] ?: 'Pasien') ?></li>
    </ol>
</nav>

<div class="rm-layout">
    <aside class="rm-aside">
        <section class="card shadow-sm rm-patient" aria-label="Ringkasan pasien">
            <div class="card-body p-3">
                <div class="d-flex gap-3 align-items-center mb-3">
                    <span class="user-avatar" aria-hidden="true"><?= html_escape(mb_strtoupper(mb_substr((string) $patient['nama'], 0, 1))) ?></span>
                    <div class="min-w-0">
                        <h1 class="h6 fw-bold mb-0 text-truncate"><?= html_escape($patient['nama'] ?: '—') ?></h1>
                        <div class="text-secondary small">RM <?= html_escape($patient['no_rm'] ?: '—') ?> · <?= html_escape($umur) ?></div>
                    </div>
                </div>

                <?php if ($observasi_terakhir && $observasi_terakhir['meows_zona'] !== null): ?>
                    <div class="rm-meows rm-meows-<?= $meows_badge[$observasi_terakhir['meows_zona']] ?>">
                        <span>MEOWS terakhir</span>
                        <strong><?= (int) $observasi_terakhir['meows_skor'] ?></strong>
                        <em><?= html_escape(ucfirst($observasi_terakhir['meows_zona'])) ?></em>
                    </div>
                <?php endif; ?>

                <dl class="rm-facts mb-0">
                    <?php foreach ($fakta as $label => $value): ?>
                        <dt><?= html_escape($label) ?></dt>
                        <dd><?= html_escape($value) ?></dd>
                    <?php endforeach; ?>
                </dl>
            </div>
        </section>

        <nav class="rm-steps" aria-label="Langkah rekam medis">
            <?php $nomor = 0; ?>
            <?php foreach ($tabs as $key => $label): ?>
                <?php
                $nomor++;
                $status = $tab_status[$key];
                $terisi = $key === 'identitas' || ($status !== null && $status !== 'Belum');
                ?>
                <a class="rm-step <?= $tab === $key ? 'active' : '' ?> <?= $terisi ? 'is-done' : '' ?>" <?= $tab === $key ? 'aria-current="page"' : '' ?> href="<?= html_escape(site_url('web/rm/' . $id) . '?tab=' . $key) ?>">
                    <span class="rm-step-no" aria-hidden="true"><?= $terisi && $tab !== $key ? '✓' : $nomor ?></span>
                    <span class="rm-step-label"><?= html_escape($label) ?></span>
                    <?php if ($status !== null && $key !== 'identitas'): ?>
                        <span class="rm-step-status"><?= html_escape($status) ?></span>
                    <?php endif; ?>
                </a>
            <?php endforeach; ?>
        </nav>
    </aside>

    <div class="rm-main">
        <?php
        $status = $status_rujukan[$patient['status_rujukan']];
        $format_waktu = function ($value) {
            $date = $value ? DateTime::createFromFormat('Y-m-d H:i:s', $value) : false;

            return $date ? $date->format('d/m/Y H:i') : '—';
        };

        // Isi peringatan pengiriman: data yang belum lengkap tetap boleh dikirim, tetapi harus disadari.
        $kurang = [];
        if (!$patient['id_faskes_tujuan']) {
            $kurang[] = 'faskes tujuan belum ditentukan';
        }
        if (!$observasi_terakhir) {
            $kurang[] = 'belum ada observasi MEOWS';
        }
        if (!$diagnosis_kunjungan) {
            $kurang[] = 'diagnosa kerja belum dipilih';
        }
        ?>
        <section class="rm-status mb-3" aria-label="Status rujukan">
            <div class="rm-status-main">
                <span class="badge <?= $status['badge'] ?>"><?= html_escape($status['label']) ?></span>
                <span class="small">
                    <?php if ($patient['status_rujukan'] === 'draf'): ?>
                        <?= $akses_rm['edit'] ? 'Lengkapi data lalu kirim rujukan ke faskes tujuan.' : 'Rujukan belum dikirim.' ?>
                    <?php else: ?>
                        Dikirim <?= html_escape($format_waktu($patient['waktu_dikirim'])) ?> oleh <?= html_escape($patient['nama_pengirim'] ?: '—') ?>
                        <?php if ($patient['waktu_diterima']): ?>
                            · diterima <?= html_escape($format_waktu($patient['waktu_diterima'])) ?> (<?= html_escape($patient['nama_penerima'] ?: '—') ?>)
                        <?php endif; ?>
                        <?php if ($patient['waktu_tiba']): ?>
                            · tiba <?= html_escape($format_waktu($patient['waktu_tiba'])) ?>
                        <?php endif; ?>
                    <?php endif; ?>
                </span>
            </div>
            <div class="d-flex gap-2">
                <?php if ($akses_rm['kirim']): ?>
                    <?= form_open('web/rm/' . $id . '/kirim', [
                        'data-confirm-title' => 'Kirim rujukan ke ' . html_escape($patient['nama_faskes_tujuan'] ?: 'faskes tujuan') . '?',
                        'data-confirm' => html_escape(
                            'Setelah dikirim, data terkunci dan hanya observasi yang dapat ditambahkan.'
                            . ($kurang ? ' Perhatian: ' . implode(', ', $kurang) . '.' : '')
                        ),
                        'data-confirm-button' => 'Ya, kirim'
                    ]) ?>
                        <button type="submit" class="btn btn-sm btn-primary">Kirim rujukan</button>
                    <?= form_close() ?>
                <?php endif; ?>
                <?php if ($akses_rm['batal_kirim']): ?>
                    <?= form_open('web/rm/' . $id . '/batal_kirim', [
                        'data-confirm-title' => 'Batalkan pengiriman?',
                        'data-confirm' => 'Rujukan ditarik dari faskes tujuan dan data dapat diubah kembali.',
                        'data-confirm-button' => 'Ya, batalkan'
                    ]) ?>
                        <button type="submit" class="btn btn-sm btn-outline-secondary">Batalkan pengiriman</button>
                    <?= form_close() ?>
                <?php endif; ?>
                <?php if ($akses_rm['peran'] === 'tujuan'): ?>
                    <a class="btn btn-sm btn-outline-primary" href="<?= html_escape(site_url('web/rujukan/' . $id)) ?>">Ringkasan SBAR</a>
                <?php endif; ?>
            </div>
        </section>

        <?php if (!$akses_rm['edit']): ?>
            <div class="rm-readonly-note mb-3">
                <?php if ($akses_rm['peran'] === 'tujuan'): ?>
                    Mode baca: data diisi oleh <?= html_escape($patient['nama_faskes_perujuk'] ?: 'faskes perujuk') ?> dan tidak dapat diubah.
                <?php elseif ($akses_rm['observasi']): ?>
                    Rujukan sudah dikirim: data terkunci. Observasi tanda vital masih dapat ditambahkan selama pasien belum tiba.
                <?php else: ?>
                    Data terkunci dan hanya dapat dilihat.
                <?php endif; ?>
            </div>
        <?php endif; ?>

        <?php if ($flags): ?>
            <section class="rm-alerts mb-3" aria-label="Kewaspadaan medis">
                <div class="rm-alerts-head">
                    <span class="fw-semibold">Kewaspadaan</span>
                    <?php if ($jumlah_bahaya): ?>
                        <span class="badge text-bg-danger"><?= $jumlah_bahaya ?> bahaya</span>
                    <?php endif; ?>
                    <span class="badge text-bg-light border"><?= count($flags) ?> penanda</span>
                </div>
                <ul class="list-unstyled d-flex flex-column gap-1 mb-0">
                    <?php foreach ($flags_tampil as $flag): ?>
                        <li class="flag flag-<?= $flag_class[$flag['level']] ?>"><?= html_escape($flag['text']) ?></li>
                    <?php endforeach; ?>
                </ul>
                <?php if ($flags_lain): ?>
                    <details class="mt-1">
                        <summary class="small text-secondary">Tampilkan <?= count($flags_lain) ?> penanda lainnya</summary>
                        <ul class="list-unstyled d-flex flex-column gap-1 mt-1 mb-0">
                            <?php foreach ($flags_lain as $flag): ?>
                                <li class="flag flag-<?= $flag_class[$flag['level']] ?>"><?= html_escape($flag['text']) ?></li>
                            <?php endforeach; ?>
                        </ul>
                    </details>
                <?php endif; ?>
            </section>
        <?php endif; ?>

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

            <?php
            // Form dinonaktifkan untuk mode baca; tab vital tetap aktif selama observasi masih boleh ditambah.
            $form_aktif = $tab === 'vital' ? $akses_rm['observasi'] : $akses_rm['edit'];
            ?>
            <fieldset class="rm-fieldset <?= $form_aktif ? '' : 'is-readonly' ?>" <?= $form_aktif ? '' : 'disabled' ?>>
                <?php $this->load->view('web/rm/tabs/' . $tab, [
                    'id' => $id,
                    'state_data' => $state_data,
                    'format_date' => $format_date,
                    'umur' => $umur,
                    'jumlah_riwayat' => $jumlah_riwayat,
                    'boleh_edit' => $akses_rm['edit']
                ]); ?>
            </fieldset>
        </div>
    </div>
</div>
