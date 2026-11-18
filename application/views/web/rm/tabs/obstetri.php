<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>
<?php
$values = $state_data ?: ($gpa ?: [
    'gravida' => 1,
    'partus' => 0,
    'abortus' => 0,
    'anak_hidup' => 0
]);

$steppers = [
    'gravida' => [
        'label' => 'Gravida (G)',
        'help' => 'Jumlah kehamilan, termasuk kehamilan saat ini.',
        'min' => 1
    ],
    'partus' => [
        'label' => 'Partus (P)',
        'help' => 'Persalinan viabel (≥ 22 minggu), hidup maupun mati.',
        'min' => 0
    ],
    'abortus' => [
        'label' => 'Abortus (A)',
        'help' => 'Keguguran sebelum 22 minggu.',
        'min' => 0
    ],
    'anak_hidup' => [
        'label' => 'Anak hidup (AH)',
        'help' => 'Jumlah anak yang masih hidup saat ini.',
        'min' => 0
    ]
];
?>
<?= form_open('web/rm/' . $id . '/save_gpa', [
    'id' => 'gpaForm',
    'data-dirty-guard' => '1',
    'data-riwayat-tersimpan' => count($riwayat) ? max(array_keys($riwayat)) : 0
]) ?>
    <div class="card-body p-4">
        <h2 class="h5 mb-1">Ringkasan status obstetri (GPA) &amp; paritas</h2>
        <p class="text-secondary small mb-4">Gunakan tombol − / + untuk mengubah nilai. Validasi dan interpretasi diperbarui otomatis.</p>

        <div class="d-flex flex-column gap-3">
            <?php foreach ($steppers as $field => $stepper): ?>
                <div class="gpa-row">
                    <div>
                        <label class="fw-semibold" for="<?= $field ?>"><?= $stepper['label'] ?></label>
                        <div class="text-secondary small"><?= $stepper['help'] ?></div>
                    </div>
                    <div class="input-group stepper">
                        <button class="btn btn-outline-secondary" type="button" data-step="-1" data-target="<?= $field ?>" aria-label="Kurangi <?= $stepper['label'] ?>">−</button>
                        <input class="form-control text-center fw-semibold" type="number" id="<?= $field ?>" name="<?= $field ?>" value="<?= html_escape($values[$field]) ?>" min="<?= $stepper['min'] ?>" max="<?= $max_gravida ?>" required inputmode="numeric">
                        <button class="btn btn-outline-secondary" type="button" data-step="1" data-target="<?= $field ?>" aria-label="Tambah <?= $stepper['label'] ?>">+</button>
                    </div>
                    <div class="small" data-gpa-note="<?= $field ?>" aria-live="polite"></div>
                </div>
            <?php endforeach; ?>

            <div class="gpa-row">
                <div>
                    <div class="fw-semibold">Anak meninggal</div>
                    <div class="text-secondary small">Dihitung otomatis: P − AH.</div>
                </div>
                <div class="text-center">
                    <span class="badge fs-6 px-3 py-2 text-bg-light border" id="anakMeninggal">0</span>
                </div>
                <div class="small" data-gpa-note="anak_meninggal" aria-live="polite"></div>
            </div>
        </div>

        <div class="alert alert-info mt-4 mb-0" id="gpaRouting" aria-live="polite"></div>
    </div>
    <div class="card-footer bg-white d-flex flex-wrap justify-content-between align-items-center gap-3 p-4">
        <span class="text-secondary small">Kode tersimpan sebagai <strong id="kodeGpa">—</strong> pada status obstetri pasien.</span>
        <button type="submit" class="btn btn-primary px-4">Simpan status obstetri</button>
    </div>
<?= form_close() ?>
