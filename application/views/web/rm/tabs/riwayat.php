<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>
<?php
$values = $state_data ? $state_data['riwayat'] : $riwayat;

$select_fields = [
    'luaran' => 'Luaran kehamilan',
    'usia_kehamilan_lahir' => 'Usia kehamilan saat lahir',
    'metode_persalinan' => 'Metode persalinan',
    'tempat_persalinan' => 'Tempat persalinan',
    'penolong_persalinan' => 'Penolong persalinan'
];

// Kolom bayi dinonaktifkan di UI bila luaran bukan persalinan.
$bayi_fields = [
    'jenis_kelamin' => 'Jenis kelamin anak',
    'berat_lahir_kategori' => 'Berat lahir',
    'kondisi_bayi' => 'Kondisi bayi'
];

$checkbox_groups = [
    'Riwayat penyakit saat hamil' => $pilihan_penyakit,
    'Komplikasi persalinan & nifas' => $pilihan_komplikasi
];

$select = function ($ke, $field, $label, $value, $choices) {
    $id = 'riwayat_' . $ke . '_' . $field;
    $html = '<label class="form-label small" for="' . $id . '">' . html_escape($label) . '</label>';
    $html .= '<select class="form-select" id="' . $id . '" name="riwayat[' . $ke . '][' . $field . ']" data-field="' . $field . '">';
    $html .= '<option value="">— Pilih —</option>';

    foreach ($choices as $key => $text) {
        $selected = (string) $value === (string) $key ? ' selected' : '';
        $html .= '<option value="' . html_escape($key) . '"' . $selected . '>' . html_escape($text) . '</option>';
    }

    return $html . '</select>';
};
?>
<?php if (!$gpa): ?>
    <div class="card-body p-5 text-center">
        <h2 class="h5">Status obstetri belum diisi</h2>
        <p class="text-secondary">Jumlah kolom riwayat kehamilan mengikuti nilai gravida.</p>
        <a class="btn btn-primary" href="<?= html_escape(site_url('web/rm/' . $id) . '?tab=obstetri') ?>">Isi status obstetri</a>
    </div>
<?php elseif ($jumlah_riwayat < 1): ?>
    <div class="card-body p-5 text-center">
        <h2 class="h5">Primigravida</h2>
        <p class="text-secondary mb-0">Ini kehamilan pertama, sehingga tidak ada riwayat kehamilan terdahulu.</p>
    </div>
<?php else: ?>
    <?= form_open('web/rm/' . $id . '/save_riwayat', [
        'id' => 'riwayatForm',
        'data-dirty-guard' => '1',
        // form_open tidak meng-escape atribut, sehingga JSON harus di-escape manual.
        'data-tanpa-bayi' => html_escape(json_encode($luaran_tanpa_bayi))
    ]) ?>
        <div class="card-body p-4">
            <h2 class="h5 mb-1">Riwayat kehamilan terdahulu</h2>
            <p class="text-secondary small mb-4">
                <?= html_escape($gpa['kode_gpa']) ?>: tersedia <?= $jumlah_riwayat ?> kolom kehamilan terdahulu.
                Isian boleh disimpan bertahap.
            </p>

            <div class="d-flex flex-column gap-4">
                <?php for ($ke = 1; $ke <= $jumlah_riwayat; $ke++): ?>
                    <?php $row = isset($values[$ke]) ? $values[$ke] : []; ?>
                    <fieldset class="riwayat-card" data-riwayat="<?= $ke ?>">
                        <legend class="riwayat-head">
                            <span class="fw-semibold">Kehamilan ke-<?= $ke ?></span>
                            <span class="text-secondary small" data-riwayat-summary></span>
                        </legend>
                        <div class="row g-3">
                            <div class="col-sm-6 col-lg-3">
                                <label class="form-label small" for="riwayat_<?= $ke ?>_tahun">Tahun persalinan / terminasi</label>
                                <input class="form-control" type="number" id="riwayat_<?= $ke ?>_tahun" name="riwayat[<?= $ke ?>][tahun_persalinan]" value="<?= html_escape(isset($row['tahun_persalinan']) ? $row['tahun_persalinan'] : '') ?>" min="1950" max="<?= html_escape(substr($patient['tgl_reg'] ?: date('Y-m-d'), 0, 4)) ?>" inputmode="numeric" data-field="tahun_persalinan">
                            </div>
                            <?php foreach ($select_fields as $field => $label): ?>
                                <div class="col-sm-6 col-lg-3">
                                    <?= $select($ke, $field, $label, isset($row[$field]) ? $row[$field] : '', $riwayat_options[$field]) ?>
                                </div>
                            <?php endforeach; ?>
                            <?php foreach ($bayi_fields as $field => $label): ?>
                                <div class="col-sm-6 col-lg-3" data-bayi>
                                    <?= $select($ke, $field, $label, isset($row[$field]) ? $row[$field] : '', $riwayat_options[$field]) ?>
                                </div>
                            <?php endforeach; ?>

                            <?php foreach ($checkbox_groups as $group_label => $choices): ?>
                                <div class="col-lg-6">
                                    <div class="form-label small"><?= html_escape($group_label) ?></div>
                                    <?php if (!$choices): ?>
                                        <div class="text-secondary small">Belum ada pilihan pada master data.</div>
                                    <?php endif; ?>
                                    <div class="d-flex flex-wrap gap-2">
                                        <?php foreach ($choices as $choice): ?>
                                            <?php
                                            $checkbox_id = 'riwayat_' . $ke . '_' . $choice['kode'];
                                            $checked = isset($row['kondisi']) && in_array($choice['kode'], $row['kondisi'], true);
                                            ?>
                                            <input type="checkbox" class="btn-check" id="<?= html_escape($checkbox_id) ?>" name="riwayat[<?= $ke ?>][kondisi][]" value="<?= html_escape($choice['kode']) ?>" <?= $checked ? 'checked' : '' ?> autocomplete="off">
                                            <label class="btn btn-sm btn-outline-<?= $choice['tingkat_risiko'] === 'bahaya' ? 'danger' : ($choice['tingkat_risiko'] === 'risiko' ? 'warning' : 'secondary') ?>" for="<?= html_escape($checkbox_id) ?>" <?= $choice['rekomendasi'] ? 'title="' . html_escape($choice['rekomendasi']) . '"' : '' ?>><?= html_escape($choice['label']) ?></label>
                                        <?php endforeach; ?>
                                    </div>
                                </div>
                            <?php endforeach; ?>

                            <div class="col-12">
                                <label class="form-label small" for="riwayat_<?= $ke ?>_keterangan">Keterangan tambahan</label>
                                <textarea class="form-control" id="riwayat_<?= $ke ?>_keterangan" name="riwayat[<?= $ke ?>][keterangan]" rows="2" maxlength="2000" placeholder="Contoh: Jarak 3 th | ASI eksklusif | KB suntik"><?= html_escape(isset($row['keterangan']) ? $row['keterangan'] : '') ?></textarea>
                            </div>
                        </div>
                    </fieldset>
                <?php endfor; ?>
            </div>
        </div>
        <div class="card-footer bg-white d-flex justify-content-end p-4">
            <button type="submit" class="btn btn-primary px-4">Simpan riwayat kehamilan</button>
        </div>
    <?= form_close() ?>
<?php endif; ?>
