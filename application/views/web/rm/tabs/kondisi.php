<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>
<?php
$values = $state_data ? $state_data['kondisi'] : $kondisi_ibu;

// Kelompokkan pilihan per kategori klinis; urutan mengikuti kolom urutan di master.
$groups = [];
foreach ($pilihan_kondisi_ibu as $choice) {
    $kategori = $choice['kategori_klinis'] ?: 'Lainnya';
    $groups[$kategori][] = $choice;
}
?>
<?php if (!$pilihan_kondisi_ibu): ?>
    <div class="card-body p-5 text-center">
        <h2 class="h5">Belum ada pilihan kondisi medis ibu</h2>
        <p class="text-secondary mb-0">Jalankan <code>database/obstetri_kondisi_ibu.sql</code> untuk mengisi master kondisi ibu.</p>
    </div>
<?php else: ?>
    <?= form_open('web/rm/' . $id . '/save_kondisi', [
        'id' => 'kondisiForm',
        'data-dirty-guard' => '1'
    ]) ?>
        <div class="card-body p-4">
            <h2 class="h5 mb-1">Riwayat penyakit &amp; kondisi medis ibu</h2>
            <p class="text-secondary small mb-4">Centang semua kondisi yang sesuai. Dampak dan tindakan wajib PPK I tampil saat kondisi dipilih.</p>

            <div class="kondisi-summary mb-4" aria-live="polite">
                <span class="fw-semibold" id="kondisiCount">0 kondisi terpilih</span>
                <span class="text-secondary" id="kondisiList"></span>
            </div>

            <?php $nomor_kategori = 0; ?>
            <?php foreach ($groups as $kategori => $choices): ?>
                <?php $nomor_kategori++; ?>
                <h3 class="h6 text-secondary mt-4 mb-2"><?= $nomor_kategori ?>. <?= html_escape($kategori) ?></h3>
                <div class="d-flex flex-column gap-2">
                    <?php foreach ($choices as $index => $choice): ?>
                        <?php
                        $kode = $choice['kode'];
                        $nomor = $nomor_kategori . '.' . ($index + 1);
                        $checked = array_key_exists($kode, $values);
                        $field_id = 'kondisi_' . $kode;
                        $wajib = (int) $choice['wajib_catatan'] === 1;
                        ?>
                        <div class="kondisi-item <?= $checked ? 'is-checked' : '' ?>" data-kondisi-row>
                            <div class="kondisi-row">
                                <div class="form-check mb-0">
                                    <input class="form-check-input" type="checkbox" id="<?= html_escape($field_id) ?>" name="kondisi[]" value="<?= html_escape($kode) ?>" data-label="<?= html_escape($nomor . ' ' . $choice['label']) ?>" <?= $checked ? 'checked' : '' ?>>
                                    <label class="form-check-label" for="<?= html_escape($field_id) ?>">
                                        <span class="text-secondary me-1"><?= $nomor ?></span>
                                        <?= html_escape($choice['label']) ?>
                                        <?php if ($choice['tingkat_risiko'] === 'bahaya'): ?>
                                            <span class="badge text-bg-danger ms-1">Bahaya</span>
                                        <?php endif; ?>
                                    </label>
                                </div>
                                <div class="d-flex align-items-center gap-2">
                                    <?php if ($choice['kode_icd10']): ?>
                                        <span class="badge text-bg-light border font-monospace" title="Kode ICD-10"><?= html_escape($choice['kode_icd10']) ?></span>
                                    <?php endif; ?>
                                    <input class="form-control form-control-sm" name="catatan[<?= html_escape($kode) ?>]" value="<?= html_escape($checked ? (string) $values[$kode] : '') ?>" maxlength="255" placeholder="<?= $wajib ? 'Wajib: nama obat / keterangan' : 'Catatan (opsional)' ?>" aria-label="Catatan <?= html_escape($choice['label']) ?>" data-wajib="<?= $wajib ? '1' : '0' ?>" <?= $checked ? '' : 'disabled' ?> <?= $checked && $wajib ? 'required' : '' ?>>
                                </div>
                            </div>
                            <?php if ($choice['dampak_risiko'] || $choice['rekomendasi']): ?>
                                <dl class="kondisi-detail small mb-0">
                                    <?php if ($choice['dampak_risiko']): ?>
                                        <dt>Dampak &amp; risiko</dt>
                                        <dd><?= html_escape($choice['dampak_risiko']) ?></dd>
                                    <?php endif; ?>
                                    <?php if ($choice['rekomendasi']): ?>
                                        <dt>Tindakan / skrining PPK I</dt>
                                        <dd><?= html_escape($choice['rekomendasi']) ?></dd>
                                    <?php endif; ?>
                                </dl>
                            <?php endif; ?>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endforeach; ?>
        </div>
        <div class="card-footer bg-white d-flex justify-content-end p-4">
            <button type="submit" class="btn btn-primary px-4">Simpan kondisi medis ibu</button>
        </div>
    <?= form_close() ?>
<?php endif; ?>
