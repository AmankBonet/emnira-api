<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>
<?php
$pk = $def['pk'];
$pk_manual = !empty($def['pk_manual']);
$kolom_tampil = array_filter($def['kolom'], function ($spec) {
    return !empty($spec['tampil']);
});
$url_tabel = site_url('web/master/' . $key);

// Nilai sel tabel: label untuk pilihan/relasi, badge untuk ya/tidak, teks panjang dipotong.
$tampil = function ($kolom, array $spec, $nilai) use ($opsi_relasi) {
    if ($spec['tipe'] === 'ya_tidak') {
        $ya = (int) $nilai === 1;
        $teks = $kolom === 'id_active' ? ($ya ? 'Aktif' : 'Nonaktif') : ($ya ? 'Ya' : 'Tidak');

        return '<span class="badge ' . ($ya ? 'text-bg-success' : 'text-bg-secondary') . '">' . $teks . '</span>';
    }

    if ($nilai === null || $nilai === '') {
        return '<span class="text-secondary">—</span>';
    }

    if ($spec['tipe'] === 'pilihan') {
        $nilai = isset($spec['pilihan'][$nilai]) ? $spec['pilihan'][$nilai] : $nilai;
    } elseif ($spec['tipe'] === 'relasi') {
        $nilai = isset($opsi_relasi[$kolom][$nilai]) ? $opsi_relasi[$kolom][$nilai] : $nilai;
    }

    return html_escape(mb_strimwidth((string) $nilai, 0, 120, '…'));
};

$query = function (array $tambahan) use ($search, $filter) {
    return http_build_query(array_filter(array_merge(['q' => $search, 'f' => $filter], $tambahan), 'strlen'));
};
?>
<div class="d-flex flex-wrap align-items-center justify-content-between gap-3 mb-4">
    <div>
        <div class="text-secondary small mb-2 breadcrumb-text"><a href="<?= html_escape(site_url('web/master')) ?>">Master data</a> / <?= html_escape($def['judul']) ?></div>
        <h1 class="h3 fw-bold mb-2"><?= html_escape($def['judul']) ?></h1>
        <p class="text-secondary mb-0"><?= html_escape($def['keterangan']) ?></p>
    </div>
    <button type="button" class="btn btn-primary px-4 py-2" data-bs-toggle="modal" data-bs-target="#masterModal">+ Tambah data</button>
</div>

<section class="card shadow-sm" aria-label="Daftar <?= html_escape($def['judul']) ?>">
    <div class="card-body d-flex flex-wrap gap-3 align-items-center justify-content-between p-4">
        <div class="fw-semibold">Daftar data <span class="badge text-bg-light border ms-2"><?= (int) $total ?></span></div>
        <form method="get" action="<?= html_escape($url_tabel) ?>" class="d-flex flex-wrap gap-2" role="search">
            <?php if (!empty($def['filter'])): ?>
                <?php
                $spec_filter = $def['kolom'][$def['filter']];
                $opsi_filter = $spec_filter['tipe'] === 'relasi' ? $opsi_relasi[$def['filter']] : $spec_filter['pilihan'];
                ?>
                <select class="form-select master-filter" name="f" aria-label="Filter <?= html_escape(mb_strtolower($spec_filter['label'])) ?>" onchange="this.form.submit()">
                    <option value="">Semua <?= html_escape(mb_strtolower($spec_filter['label'])) ?></option>
                    <?php foreach ($opsi_filter as $nilai => $label): ?>
                        <option value="<?= html_escape($nilai) ?>" <?= (string) $filter === (string) $nilai ? 'selected' : '' ?>><?= html_escape($label) ?></option>
                    <?php endforeach; ?>
                </select>
            <?php endif; ?>
            <div class="input-group search-input">
                <input type="search" class="form-control" name="q" value="<?= html_escape($search) ?>" placeholder="Cari…" aria-label="Cari" maxlength="255">
                <button class="btn btn-outline-secondary" type="submit">Cari</button>
            </div>
            <?php if ($search !== '' || $filter !== ''): ?>
                <a class="btn btn-outline-secondary" href="<?= html_escape($url_tabel) ?>">Reset</a>
            <?php endif; ?>
        </form>
    </div>

    <div class="table-responsive">
        <table class="table align-middle mb-0 master-table">
            <thead>
                <tr>
                    <?php if (!$pk_manual): ?>
                        <th scope="col">ID</th>
                    <?php endif; ?>
                    <?php foreach ($kolom_tampil as $spec): ?>
                        <th scope="col"><?= html_escape($spec['label']) ?></th>
                    <?php endforeach; ?>
                    <th scope="col" class="text-end">Tindakan</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($rows as $row): ?>
                    <?php $terkunci = isset($def['terkunci']) && in_array((int) $row[$pk], $def['terkunci'], true); ?>
                    <tr>
                        <?php if (!$pk_manual): ?>
                            <td class="text-secondary">#<?= html_escape($row[$pk]) ?></td>
                        <?php endif; ?>
                        <?php foreach ($kolom_tampil as $kolom => $spec): ?>
                            <td class="<?= $spec['tipe'] === 'teks_panjang' ? 'master-cell-long' : '' ?>"><?= $tampil($kolom, $spec, $row[$kolom]) ?></td>
                        <?php endforeach; ?>
                        <td class="text-end text-nowrap">
                            <button class="btn btn-sm btn-outline-secondary" type="button" data-bs-toggle="modal" data-bs-target="#masterModal" data-row="<?= html_escape(json_encode($row, JSON_INVALID_UTF8_SUBSTITUTE)) ?>">Edit</button>
                            <?php if ($terkunci): ?>
                                <span class="badge text-bg-light border ms-1" title="Dipakai aplikasi">Sistem</span>
                            <?php else: ?>
                                <?= form_open('web/master/' . $key . '/delete/' . rawurlencode($row[$pk]), [
                                    'class' => 'd-inline',
                                    'data-confirm-title' => 'Hapus data?',
                                    'data-confirm' => 'Data akan dihapus permanen. Data yang masih dipakai tidak dapat dihapus.'
                                ]) ?>
                                    <button class="btn btn-sm btn-outline-danger ms-1" type="submit">Hapus</button>
                                <?= form_close() ?>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
                <?php if (!$rows): ?>
                    <tr>
                        <td colspan="<?= count($kolom_tampil) + 2 ?>" class="text-center py-5">
                            <h2 class="h5">Belum ada data<?= $search !== '' || $filter !== '' ? ' yang sesuai' : '' ?></h2>
                            <p class="text-secondary mb-0">Klik Tambah data untuk menambahkan.</p>
                        </td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>

    <div class="card-body d-flex flex-wrap justify-content-between align-items-center gap-3 px-4">
        <small class="text-secondary"><?= $total ? (($page - 1) * $per_page + 1) : 0 ?>–<?= min($page * $per_page, $total) ?> dari <?= (int) $total ?> data</small>
        <nav class="d-flex align-items-center gap-2" aria-label="Halaman">
            <?php if ($page > 1): ?>
                <a class="btn btn-sm btn-outline-secondary" href="<?= html_escape($url_tabel . '?' . $query(['page' => $page - 1])) ?>">Sebelumnya</a>
            <?php endif; ?>
            <span class="small text-secondary">Halaman <?= (int) $page ?> / <?= (int) $pages ?></span>
            <?php if ($page < $pages): ?>
                <a class="btn btn-sm btn-outline-secondary" href="<?= html_escape($url_tabel . '?' . $query(['page' => $page + 1])) ?>">Berikutnya</a>
            <?php endif; ?>
        </nav>
    </div>
</section>

<div class="modal fade" id="masterModal" tabindex="-1" aria-labelledby="masterModalTitle" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable">
        <?= form_open('web/master/' . $key . '/save', [
            'class' => 'modal-content master-form',
            'id' => 'masterForm',
            'data-pk' => $pk,
            'data-pk-manual' => $pk_manual ? '1' : '0',
            'data-judul' => html_escape($def['judul'])
        ]) ?>
            <div class="modal-header">
                <h2 class="modal-title fs-5" id="masterModalTitle">Tambah data</h2>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Tutup"></button>
            </div>
            <div class="modal-body p-4">
                <div class="alert alert-danger d-none" id="masterErrors" role="alert"></div>
                <input type="hidden" name="pk_lama" value="">
                <div class="row g-3">
                    <?php foreach ($def['kolom'] as $kolom => $spec): ?>
                        <?php
                        $field_id = 'master_' . $kolom;
                        $wajib = !empty($spec['wajib']);
                        $bawaan = isset($spec['bawaan']) ? (string) $spec['bawaan'] : '';
                        ?>
                        <div class="col-md-<?= isset($spec['lebar']) ? (int) $spec['lebar'] : 6 ?>">
                            <?php if ($spec['tipe'] === 'ya_tidak'): ?>
                                <div class="form-check form-switch mt-md-4">
                                    <input class="form-check-input" type="checkbox" role="switch" id="<?= $field_id ?>" name="<?= $kolom ?>" value="1" data-bawaan="<?= html_escape($bawaan) ?>">
                                    <label class="form-check-label" for="<?= $field_id ?>"><?= html_escape($spec['label']) ?></label>
                                </div>
                            <?php else: ?>
                                <label class="form-label" for="<?= $field_id ?>"><?= html_escape($spec['label']) ?><?= $wajib ? ' <span class="text-danger">*</span>' : '' ?></label>
                                <?php if ($spec['tipe'] === 'teks_panjang'): ?>
                                    <textarea class="form-control" id="<?= $field_id ?>" name="<?= $kolom ?>" rows="3" maxlength="<?= (int) $spec['maks'] ?>" data-bawaan="<?= html_escape($bawaan) ?>" <?= $wajib ? 'required' : '' ?>></textarea>
                                <?php elseif ($spec['tipe'] === 'pilihan' || $spec['tipe'] === 'relasi'): ?>
                                    <?php $opsi = $spec['tipe'] === 'relasi' ? $opsi_relasi[$kolom] : $spec['pilihan']; ?>
                                    <select class="form-select" id="<?= $field_id ?>" name="<?= $kolom ?>" data-bawaan="<?= html_escape($bawaan) ?>" <?= $wajib ? 'required' : '' ?>>
                                        <option value=""><?= $wajib ? '— Pilih —' : '— Tidak ada —' ?></option>
                                        <?php foreach ($opsi as $nilai => $label): ?>
                                            <option value="<?= html_escape($nilai) ?>"><?= html_escape($label) ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                <?php elseif ($spec['tipe'] === 'angka'): ?>
                                    <input class="form-control" type="number" id="<?= $field_id ?>" name="<?= $kolom ?>" min="<?= (int) $spec['min'] ?>" max="<?= (int) $spec['max'] ?>" step="1" data-bawaan="<?= html_escape($bawaan) ?>" <?= $wajib ? 'required' : '' ?>>
                                <?php else: ?>
                                    <input class="form-control" id="<?= $field_id ?>" name="<?= $kolom ?>" maxlength="<?= (int) $spec['maks'] ?>" data-bawaan="<?= html_escape($bawaan) ?>" <?= $wajib ? 'required' : '' ?> <?= $pk_manual && $kolom === $pk ? 'pattern="[a-z0-9_]+" data-pk-field="1"' : '' ?>>
                                <?php endif; ?>
                                <?php if (isset($spec['bantuan'])): ?>
                                    <div class="form-text"><?= html_escape($spec['bantuan']) ?></div>
                                <?php endif; ?>
                            <?php endif; ?>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-light border" data-bs-dismiss="modal">Batal</button>
                <button type="submit" class="btn btn-primary">Simpan</button>
            </div>
        <?= form_close() ?>
    </div>
</div>

<?php if ($form_state): ?>
    <script type="application/json" id="masterFormState"><?= json_encode($form_state, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_INVALID_UTF8_SUBSTITUTE) ?></script>
<?php endif; ?>
