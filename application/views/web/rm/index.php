<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>
<?php
$format_date = function ($value) {
    if (!$value) {
        return '—';
    }

    $date = DateTime::createFromFormat('!Y-m-d', $value);

    return $date ? $date->format('d/m/Y') : $value;
};

$faskes_label = function (array $faskes) {
    $label = $faskes['nama_faskes'] ?: 'Faskes #' . $faskes['id_faskes'];

    return $label . ($faskes['kategori'] ? ' (' . $faskes['kategori'] . ')' : '');
};
?>
<div class="d-flex flex-wrap align-items-center justify-content-between gap-3 mb-4">
    <div>
        <div class="text-secondary small mb-2">Rekam medis / Identitas pasien</div>
        <h1 class="h3 fw-bold mb-2">Identitas pasien</h1>
        <p class="text-secondary mb-0">Kelola data identitas dan kunjungan pasien.</p>
    </div>
    <button type="button" class="btn btn-primary px-4 py-2" data-bs-toggle="modal" data-bs-target="#patientModal">+ Tambah pasien</button>
</div>

<section class="card shadow-sm" aria-label="Daftar identitas pasien">
    <div class="card-body d-flex flex-wrap gap-3 align-items-center justify-content-between p-4">
        <div class="fw-semibold">Daftar pasien <span class="badge text-bg-light border ms-2"><?= $total ?></span></div>
        <form method="get" action="<?= html_escape(site_url('web/rm')) ?>" class="input-group search-input" role="search">
            <input type="search" class="form-control" name="q" value="<?= html_escape($search) ?>" placeholder="Cari no. RM atau nama…" aria-label="Cari no. RM atau nama" maxlength="255">
            <button class="btn btn-outline-secondary" type="submit">Cari</button>
            <?php if ($search !== ''): ?>
                <a class="btn btn-outline-secondary" href="<?= html_escape(site_url('web/rm')) ?>">Reset</a>
            <?php endif; ?>
        </form>
    </div>

    <div class="table-responsive">
        <table class="table align-middle mb-0">
            <thead>
                <tr>
                    <th scope="col">Pasien</th>
                    <th scope="col">Tgl. lahir</th>
                    <th scope="col">Tgl. registrasi</th>
                    <th scope="col">Kehamilan</th>
                    <th scope="col">Lokasi</th>
                    <th scope="col">Rujukan</th>
                    <th scope="col" class="text-end">Tindakan</th>
                </tr>
            </thead>
            <tbody>
            <?php foreach ($patients as $patient): ?>
                <?php
                $json = $patient;
                unset($json['nama_faskes_perujuk'], $json['nama_faskes_tujuan']);
                ?>
                <tr>
                    <td>
                        <div class="d-flex gap-3 align-items-center">
                            <span class="user-avatar" aria-hidden="true"><?= html_escape(mb_strtoupper(mb_substr((string) $patient['nama'], 0, 1))) ?></span>
                            <div class="user-name">
                                <a class="fw-semibold text-decoration-none" href="<?= html_escape(site_url('web/rm/' . (int) $patient['id_kunjungan'])) ?>"><?= html_escape($patient['nama'] ?: '—') ?></a>
                                <small class="text-secondary">RM <?= html_escape($patient['no_rm'] ?: '—') ?></small>
                                <?php if ($patient['alamat']): ?>
                                    <small class="d-block text-secondary text-truncate" style="max-width: 16rem;"><?= html_escape($patient['alamat']) ?></small>
                                <?php endif; ?>
                            </div>
                        </div>
                    </td>
                    <td class="text-nowrap"><?= html_escape($format_date($patient['tgl_lahir'])) ?></td>
                    <td class="text-nowrap"><?= html_escape($format_date($patient['tgl_reg'])) ?></td>
                    <td class="text-nowrap">
                        <?php if ($patient['status_obstetri']): ?>
                            <span class="badge text-bg-light border"><?= html_escape($patient['status_obstetri']) ?></span>
                        <?php endif; ?>
                        <?php if ($patient['hpht']): ?>
                            <div class="fw-semibold"><?= html_escape($patient['usia_kehamilan'] ?: '—') ?></div>
                            <small class="text-secondary">HPHT <?= html_escape($format_date($patient['hpht'])) ?> · HPL <?= html_escape($format_date($patient['hpl'])) ?></small>
                        <?php elseif (!$patient['status_obstetri']): ?>
                            —
                        <?php endif; ?>
                    </td>
                    <td><?= html_escape($patient['lokasi'] ?: '—') ?></td>
                    <td>
                        <?php $status = $status_rujukan[$patient['status_rujukan']]; ?>
                        <span class="badge <?= $status['badge'] ?> mb-1"><?= html_escape($status['label']) ?></span>
                        <div class="small"><?= html_escape($patient['nama_faskes_perujuk'] ?: '—') ?></div>
                        <small class="text-secondary">→ <?= html_escape($patient['nama_faskes_tujuan'] ?: '—') ?></small>
                    </td>
                    <td class="text-end text-nowrap">
                        <a class="btn btn-sm btn-primary" href="<?= html_escape(site_url('web/rm/' . (int) $patient['id_kunjungan'])) ?>" aria-label="Buka rekam medis <?= html_escape($patient['nama'] ?? '') ?>">Rekam medis</a>
                        <?php if ($patient['can_edit']): ?>
                            <button class="btn btn-sm btn-outline-secondary ms-1" type="button" data-bs-toggle="modal" data-bs-target="#patientModal" data-patient="<?= html_escape(json_encode($json, JSON_INVALID_UTF8_SUBSTITUTE)) ?>" aria-label="Edit <?= html_escape($patient['nama'] ?? '') ?>">Edit</button>
                            <?= form_open('web/rm/delete/' . $patient['id_kunjungan'], [
                                'class' => 'd-inline',
                                'data-confirm-title' => 'Hapus identitas pasien?',
                                'data-confirm' => html_escape('Data pasien ' . ($patient['nama'] ?? '') . ' akan dihapus secara permanen. Tindakan ini tidak dapat dibatalkan.')
                            ]) ?>
                                <button class="btn btn-sm btn-outline-danger ms-1" type="submit" aria-label="Hapus <?= html_escape($patient['nama'] ?? '') ?>">Hapus</button>
                            <?= form_close() ?>
                        <?php endif; ?>
                    </td>
                </tr>
            <?php endforeach; ?>
            <?php if (!$patients): ?>
                <tr>
                    <td colspan="7" class="text-center py-5">
                        <h2 class="h5">Belum ada pasien<?= $search !== '' ? ' yang sesuai' : '' ?></h2>
                        <p class="text-secondary mb-0"><?= $search !== '' ? 'Coba kata pencarian lain.' : 'Klik Tambah pasien untuk menambahkan data pertama.' ?></p>
                    </td>
                </tr>
            <?php endif; ?>
            </tbody>
        </table>
    </div>

    <div class="card-body d-flex flex-wrap justify-content-between align-items-center gap-3 px-4">
        <small class="text-secondary"><?= $total ? (($page - 1) * $per_page + 1) : 0 ?>–<?= min($page * $per_page, $total) ?> dari <?= $total ?> pasien</small>
        <nav class="d-flex align-items-center gap-2" aria-label="Halaman pasien">
            <?php if ($page > 1): ?>
                <a class="btn btn-sm btn-outline-secondary" href="<?= html_escape(site_url('web/rm') . '?' . http_build_query(['q' => $search, 'page' => $page - 1])) ?>">Sebelumnya</a>
            <?php endif; ?>
            <span class="small text-secondary">Halaman <?= $page ?> / <?= $pages ?></span>
            <?php if ($page < $pages): ?>
                <a class="btn btn-sm btn-outline-secondary" href="<?= html_escape(site_url('web/rm') . '?' . http_build_query(['q' => $search, 'page' => $page + 1])) ?>">Berikutnya</a>
            <?php endif; ?>
        </nav>
    </div>
</section>

<div class="modal fade" id="patientModal" tabindex="-1" aria-labelledby="patientModalTitle" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable">
        <?= form_open('web/rm/save', [
            'class' => 'modal-content',
            'id' => 'patientForm',
            'data-durasi-kehamilan' => $durasi_kehamilan
        ]) ?>
            <div class="modal-header">
                <h2 class="modal-title fs-5" id="patientModalTitle">Tambah pasien</h2>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Tutup"></button>
            </div>
            <div class="modal-body p-4">
                <div class="alert alert-danger d-none" id="patientErrors" role="alert"></div>
                <input type="hidden" name="id_kunjungan" value="0">

                <h3 class="h6 text-secondary mb-3">Identitas</h3>
                <div class="row g-3">
                    <div class="col-md-4">
                        <label for="no_rm" class="form-label">No. RM <span class="text-danger">*</span></label>
                        <input class="form-control" id="no_rm" name="no_rm" maxlength="255" required autocomplete="off">
                    </div>
                    <div class="col-md-8">
                        <label for="nama" class="form-label">Nama pasien <span class="text-danger">*</span></label>
                        <input class="form-control" id="nama" name="nama" maxlength="255" required autocomplete="off">
                    </div>
                    <div class="col-12">
                        <label for="alamat" class="form-label">Alamat</label>
                        <textarea class="form-control" id="alamat" name="alamat" rows="2"></textarea>
                    </div>
                    <div class="col-md-6">
                        <label for="tgl_lahir" class="form-label">Tanggal lahir</label>
                        <input type="date" class="form-control" id="tgl_lahir" name="tgl_lahir" max="<?= date('Y-m-d') ?>">
                    </div>
                    <div class="col-md-6">
                        <label for="tgl_reg" class="form-label">Tanggal registrasi <span class="text-danger">*</span></label>
                        <input type="date" class="form-control" id="tgl_reg" name="tgl_reg" required>
                    </div>
                    <div class="col-12">
                        <label for="lokasi" class="form-label">Lokasi</label>
                        <input class="form-control" id="lokasi" name="lokasi" maxlength="255" placeholder="Contoh: UGD PUSKES">
                        <div class="form-text">Status obstetri (GPA) diisi dari halaman rekam medis pasien.</div>
                    </div>
                </div>

                <h3 class="h6 text-secondary mt-4 mb-3">Kehamilan</h3>
                <div class="row g-3">
                    <div class="col-md-4">
                        <label for="hpht" class="form-label">HPHT</label>
                        <input type="date" class="form-control" id="hpht" name="hpht" max="<?= date('Y-m-d') ?>" aria-describedby="hphtHelp">
                        <div class="form-text" id="hphtHelp">Hari pertama haid terakhir.</div>
                    </div>
                    <div class="col-md-4">
                        <label for="hpl" class="form-label">HPL</label>
                        <input type="date" class="form-control bg-body-tertiary" id="hpl" name="hpl" readonly tabindex="-1" aria-describedby="hplHelp">
                        <div class="form-text" id="hplHelp">Otomatis: HPHT + 40 minggu.</div>
                    </div>
                    <div class="col-md-4">
                        <label for="usia_kehamilan" class="form-label">Usia kehamilan</label>
                        <input class="form-control bg-body-tertiary" id="usia_kehamilan" name="usia_kehamilan" readonly tabindex="-1" aria-describedby="usiaHelp">
                        <div class="form-text" id="usiaHelp" aria-live="polite">Otomatis per tanggal registrasi.</div>
                    </div>
                </div>

                <h3 class="h6 text-secondary mt-4 mb-3">Faskes</h3>
                <div class="row g-3">
                    <div class="col-md-6">
                        <label for="<?= $faskes_user === null ? 'id_faskes_perujuk' : 'faskes_perujuk_tampil' ?>" class="form-label">Faskes perujuk</label>
                        <?php if ($faskes_user === null): ?>
                            <select class="form-select" id="id_faskes_perujuk" name="id_faskes_perujuk">
                                <option value="">Tanpa faskes</option>
                                <?php foreach ($faskes_options as $faskes): ?>
                                    <option value="<?= (int) $faskes['id_faskes'] ?>"><?= html_escape($faskes_label($faskes)) ?></option>
                                <?php endforeach; ?>
                            </select>
                        <?php else: ?>
                            <?php
                            // Perujuk selalu faskes user; server juga memaksa nilai ini.
                            $faskes_sendiri = array_values(array_filter($faskes_options, function ($faskes) use ($faskes_user) {
                                return (int) $faskes['id_faskes'] === $faskes_user;
                            }));
                            ?>
                            <input type="hidden" name="id_faskes_perujuk" value="<?= (int) $faskes_user ?>" data-locked="1">
                            <input class="form-control" id="faskes_perujuk_tampil" value="<?= html_escape($faskes_sendiri ? $faskes_label($faskes_sendiri[0]) : '—') ?>" aria-label="Faskes perujuk" disabled>
                            <div class="form-text">Otomatis sesuai faskes Anda.</div>
                        <?php endif; ?>
                    </div>
                    <div class="col-md-6">
                        <label for="id_faskes_tujuan" class="form-label">Faskes tujuan</label>
                        <select class="form-select" id="id_faskes_tujuan" name="id_faskes_tujuan">
                            <option value="">Belum ditentukan</option>
                            <?php foreach ($faskes_options as $faskes): ?>
                                <?php if ((int) $faskes['id_faskes'] === $faskes_user) { continue; } ?>
                                <option value="<?= (int) $faskes['id_faskes'] ?>"><?= html_escape($faskes_label($faskes)) ?></option>
                            <?php endforeach; ?>
                        </select>
                        <div class="form-text">Wajib diisi sebelum rujukan dikirim.</div>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-light border" data-bs-dismiss="modal">Batal</button>
                <button type="submit" class="btn btn-primary">Simpan pasien</button>
            </div>
        <?= form_close() ?>
    </div>
</div>

<?php if ($form_state): ?>
    <script type="application/json" id="patientFormState"><?= json_encode($form_state, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_INVALID_UTF8_SUBSTITUTE) ?></script>
<?php endif; ?>
