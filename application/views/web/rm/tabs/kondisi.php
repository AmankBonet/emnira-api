<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>
<?php $values = $state_data ? $state_data['kondisi'] : $kondisi_ibu; ?>
<?php if (!$pilihan_kondisi_ibu): ?>
    <div class="card-body p-5 text-center">
        <h2 class="h5">Belum ada pilihan kondisi medis ibu</h2>
        <p class="text-secondary mb-0">Tambahkan data dengan kelompok <code>kondisi_ibu</code> pada tabel <code>m_obstetri_pilihan</code>.</p>
    </div>
<?php else: ?>
    <?= form_open('web/rm/' . $id . '/save_kondisi', [
        'id' => 'kondisiForm',
        'data-dirty-guard' => '1'
    ]) ?>
        <div class="card-body p-4">
            <h2 class="h5 mb-1">Riwayat penyakit &amp; kondisi medis ibu</h2>
            <p class="text-secondary small mb-4">Pilih semua kondisi yang sesuai. Catatan opsional.</p>

            <div class="d-flex flex-column gap-2">
                <?php foreach ($pilihan_kondisi_ibu as $choice): ?>
                    <?php
                    $kode = $choice['kode'];
                    $checked = array_key_exists($kode, $values);
                    $field_id = 'kondisi_' . $kode;
                    ?>
                    <div class="kondisi-row" data-kondisi-row>
                        <div class="form-check mb-0">
                            <input class="form-check-input" type="checkbox" id="<?= html_escape($field_id) ?>" name="kondisi[]" value="<?= html_escape($kode) ?>" <?= $checked ? 'checked' : '' ?>>
                            <label class="form-check-label" for="<?= html_escape($field_id) ?>">
                                <?= html_escape($choice['label']) ?>
                                <?php if ($choice['tingkat_risiko'] !== 'normal'): ?>
                                    <span class="badge text-bg-<?= $choice['tingkat_risiko'] === 'bahaya' ? 'danger' : 'warning' ?> ms-1"><?= $choice['tingkat_risiko'] === 'bahaya' ? 'Bahaya' : 'Risiko' ?></span>
                                <?php endif; ?>
                            </label>
                            <?php if ($choice['rekomendasi']): ?>
                                <div class="small text-secondary"><?= html_escape($choice['rekomendasi']) ?></div>
                            <?php endif; ?>
                        </div>
                        <input class="form-control form-control-sm" name="catatan[<?= html_escape($kode) ?>]" value="<?= html_escape($checked ? (string) $values[$kode] : '') ?>" maxlength="255" placeholder="Catatan" aria-label="Catatan <?= html_escape($choice['label']) ?>" <?= $checked ? '' : 'disabled' ?>>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
        <div class="card-footer bg-white d-flex justify-content-end p-4">
            <button type="submit" class="btn btn-primary px-4">Simpan kondisi medis ibu</button>
        </div>
    <?= form_close() ?>
<?php endif; ?>
