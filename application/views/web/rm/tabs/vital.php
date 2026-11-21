<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>
<?php
// Berat badan dan posisi kepala jarang berubah antar observasi, jadi diambil dari observasi terakhir.
$values = $state_data ?: [
    'waktu_observasi' => date('Y-m-d\TH:i'),
    'berat_badan_kg' => $observasi_terakhir ? $observasi_terakhir['berat_badan_kg'] : '',
    'kepala_masuk_panggul' => $observasi_terakhir ? $observasi_terakhir['kepala_masuk_panggul'] : ''
];

$value = function ($field) use ($values) {
    return isset($values[$field]) && $values[$field] !== null ? (string) $values[$field] : '';
};

$zona_class = [
    'normal' => 'success',
    'kuning' => 'warning',
    'merah' => 'danger'
];

$pendukung_angka = [
    'berat_badan_kg' => ['Berat badan ibu (BB)', 'kg', 0.1, 20, 300],
    'tfu_cm' => ['Tinggi fundus uteri (TFU)', 'cm', 0.1, 0, 60],
    'djj_per_menit' => ['Denyut jantung janin (DJJ)', 'x/menit', 1, 0, 250]
];

$pendukung_pilihan = [
    'kepala_masuk_panggul' => 'Kepala masuk panggul',
    'vt_pembukaan' => 'Pembukaan serviks (VT)',
    'ketuban' => 'Kondisi selaput ketuban'
];

$format_waktu = function ($value) {
    $date = DateTime::createFromFormat('Y-m-d H:i:s', $value);

    return $date ? $date->format('d/m/Y H:i') : $value;
};

// Nilai tampilan per parameter pada kartu observasi terakhir.
$tampil_nilai = function ($field, $parameter, $row) {
    $value = $row[$field];
    if ($value === null) {
        return '—';
    }

    if ($parameter['tipe'] === 'pilihan') {
        return isset($parameter['pilihan'][$value]) ? $parameter['pilihan'][$value]['label'] : $value;
    }

    // Desimal dari database (contoh 36.80) dirapikan tanpa memotong nol pada bilangan bulat (contoh 80).
    $angka = (float) $value;
    $teks = $angka == floor($angka) ? (string) (int) $angka : str_replace('.', ',', (string) round($angka, 2));

    // satuan_tampil dipakai bila satuan input terlalu panjang untuk ringkasan (contoh nyeri 9/10).
    return isset($parameter['satuan_tampil'])
        ? $teks . $parameter['satuan_tampil']
        : $teks . ' ' . $parameter['satuan'];
};
?>
<?php if ($observasi_terakhir && $observasi_terakhir['meows_zona'] !== null): ?>
    <?php $zona_terakhir = $observasi_terakhir['meows_zona']; ?>
    <section class="card-body p-4 border-bottom" aria-label="Observasi terakhir">
        <div class="meows-last meows-last-<?= $zona_class[$zona_terakhir] ?>">
            <div class="meows-last-score">
                <span>MEOWS terakhir</span>
                <strong><?= (int) $observasi_terakhir['meows_skor'] ?></strong>
                <em>Zona <?= html_escape(ucfirst($zona_terakhir)) ?></em>
            </div>
            <div class="meows-last-info">
                <div class="fw-semibold"><?= html_escape($format_waktu($observasi_terakhir['waktu_observasi'])) ?></div>
                <div class="small">
                    <?= (int) $observasi_terakhir['meows_jumlah_merah'] ?> merah ·
                    <?= (int) $observasi_terakhir['meows_jumlah_kuning'] ?> kuning ·
                    <?= (int) $observasi_terakhir['meows_parameter_terisi'] ?>/<?= count($meows_rules['parameter']) ?> parameter
                    <?php if ($observasi_terakhir['map_mmhg'] !== null): ?>
                        · MAP <?= html_escape(str_replace('.', ',', $observasi_terakhir['map_mmhg'])) ?>
                    <?php endif; ?>
                    <?php if ($observasi_terakhir['shock_index'] !== null): ?>
                        · SI <?= html_escape(str_replace('.', ',', $observasi_terakhir['shock_index'])) ?>
                    <?php endif; ?>
                </div>
                <?php if ($zona_terakhir === 'merah'): ?>
                    <div class="small fw-semibold mt-1">Segera evaluasi dan laporkan ke dokter.</div>
                <?php endif; ?>
            </div>
        </div>
        <div class="meows-chips mt-3">
            <?php foreach ($meows_rules['parameter'] as $field => $parameter): ?>
                <?php $hasil = $observasi_terakhir_hasil['parameter'][$field]; ?>
                <div class="meows-chip meows-chip-<?= $hasil ? $hasil['zona'] : 'kosong' ?>">
                    <span><?= html_escape($parameter['label']) ?></span>
                    <strong><?= html_escape($tampil_nilai($field, $parameter, $observasi_terakhir)) ?></strong>
                    <?php if ($hasil): ?>
                        <small>Skor <?= (int) $hasil['skor'] ?></small>
                    <?php endif; ?>
                </div>
            <?php endforeach; ?>
        </div>
    </section>
<?php endif; ?>
<?= form_open('web/rm/' . $id . '/save_vital', [
    'id' => 'vitalForm',
    'data-dirty-guard' => '1',
    // form_open tidak meng-escape atribut, sehingga JSON harus di-escape manual.
    'data-meows' => html_escape(json_encode($meows_rules))
]) ?>
    <div class="card-body p-4">
        <div class="d-flex flex-wrap justify-content-between align-items-start gap-3 mb-4">
            <div>
                <h2 class="h5 mb-1"><?= $observasi_terakhir ? 'Input observasi baru' : 'Input data objektif' ?></h2>
                <p class="text-secondary small mb-0">Tanda vital, urin, perdarahan, nyeri &amp; obstetri. Skor MEOWS dihitung otomatis saat mengisi.</p>
            </div>
            <div class="meows-total" id="meowsTotal" aria-live="polite">
                <div class="small text-secondary">Skor isian baru</div>
                <div class="d-flex align-items-baseline gap-2">
                    <span class="fs-3 fw-bold" id="meowsSkor">—</span>
                    <span class="badge" id="meowsZona">Belum dinilai</span>
                </div>
                <div class="small text-secondary" id="meowsRincian"></div>
            </div>
        </div>

        <div class="row g-3 mb-4">
            <div class="col-sm-6 col-lg-4">
                <label class="form-label" for="waktu_observasi">Waktu observasi <span class="text-danger">*</span></label>
                <input class="form-control" type="datetime-local" id="waktu_observasi" name="waktu_observasi" value="<?= html_escape($value('waktu_observasi')) ?>" max="<?= date('Y-m-d\TH:i', time() + 300) ?>" required>
            </div>
        </div>

        <h3 class="h6 text-secondary mb-2">Parameter MEOWS</h3>
        <div class="vital-head d-none d-md-grid small text-secondary">
            <span>Parameter &amp; ambang batas</span>
            <span>Nilai</span>
            <span>Skor</span>
            <span>Zona</span>
        </div>
        <div class="d-flex flex-column gap-2">
            <?php foreach ($meows_rules['parameter'] as $field => $parameter): ?>
                <div class="vital-row" data-meows-param="<?= $field ?>">
                    <div>
                        <label class="fw-semibold" for="<?= $field ?>">
                            <?= html_escape($parameter['label']) ?>
                            <?php if ($parameter['wajib']): ?><span class="text-danger">*</span><?php endif; ?>
                        </label>
                        <div class="small text-secondary"><?= html_escape($meows_ambang[$field]) ?></div>
                    </div>
                    <div class="input-group input-group-sm">
                        <?php if ($parameter['tipe'] === 'pilihan'): ?>
                            <select class="form-select" id="<?= $field ?>" name="<?= $field ?>" <?= $parameter['wajib'] ? 'required' : '' ?>>
                                <option value="">— Pilih —</option>
                                <?php foreach ($parameter['pilihan'] as $key => $pilihan): ?>
                                    <option value="<?= html_escape($key) ?>" <?= $value($field) === (string) $key ? 'selected' : '' ?>><?= html_escape($pilihan['label']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        <?php else: ?>
                            <input class="form-control" type="number" id="<?= $field ?>" name="<?= $field ?>" value="<?= html_escape($value($field)) ?>" min="<?= $parameter['min'] ?>" max="<?= $parameter['max'] ?>" step="<?= $parameter['step'] ?>" inputmode="decimal" <?= $parameter['wajib'] ? 'required' : '' ?>>
                            <span class="input-group-text"><?= html_escape($parameter['satuan']) ?></span>
                        <?php endif; ?>
                    </div>
                    <div class="fw-bold text-center" data-skor>—</div>
                    <div><span class="badge text-bg-light border" data-zona>—</span></div>
                </div>
            <?php endforeach; ?>
        </div>

        <h3 class="h6 text-secondary mt-4 mb-2">Data obstetri pendukung</h3>
        <div class="row g-3">
            <?php foreach ($pendukung_angka as $field => list($label, $satuan, $step, $min, $max)): ?>
                <div class="col-sm-6 col-lg-4">
                    <label class="form-label small" for="<?= $field ?>"><?= html_escape($label) ?></label>
                    <div class="input-group">
                        <input class="form-control" type="number" id="<?= $field ?>" name="<?= $field ?>" value="<?= html_escape($value($field)) ?>" min="<?= $min ?>" max="<?= $max ?>" step="<?= $step ?>" inputmode="decimal">
                        <span class="input-group-text"><?= html_escape($satuan) ?></span>
                    </div>
                </div>
            <?php endforeach; ?>
            <?php foreach ($pendukung_pilihan as $field => $label): ?>
                <div class="col-sm-6 col-lg-4">
                    <label class="form-label small" for="<?= $field ?>"><?= html_escape($label) ?></label>
                    <select class="form-select" id="<?= $field ?>" name="<?= $field ?>">
                        <option value="">— Pilih —</option>
                        <?php foreach ($observasi_pilihan[$field] as $key => $label_pilihan): ?>
                            <option value="<?= html_escape($key) ?>" <?= $value($field) === (string) $key ? 'selected' : '' ?>><?= html_escape($label_pilihan) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
            <?php endforeach; ?>
        </div>

        <h3 class="h6 text-secondary mt-4 mb-2">Nilai turunan (otomatis)</h3>
        <div class="derived-grid" aria-live="polite">
            <div><span>MAP</span><strong data-derived="map">—</strong><small>(TDS + 2 × TDD) / 3 · normal 70–100</small></div>
            <div><span>Taksiran berat janin</span><strong data-derived="tbj">—</strong><small>Johnson-Toshach: (TFU − 11/12) × 155</small></div>
            <div><span>DJJ &amp; shock index</span><strong data-derived="djj_si">—</strong><small>DJJ normal 120–160 · SI = nadi / TDS</small></div>
            <div><span>Urine output rate</span><strong data-derived="uo">—</strong><small>Urin / BB · oliguria &lt; 0,5 mL/kgBB/jam</small></div>
            <div><span>Estimasi O2 &amp; cairan awal</span><strong data-derived="o2">—</strong><small>O2 = (99 − SpO2) / 4 · cairan 20–30 mL/kgBB</small></div>
        </div>
        <p class="small text-secondary mt-2 mb-0">Nilai turunan adalah alat bantu hitung, bukan instruksi terapi.</p>
    </div>
    <div class="card-footer bg-white d-flex flex-wrap justify-content-between align-items-center gap-3 p-4">
        <span class="small text-secondary">Aturan MEOWS: <code><?= html_escape($meows_rules['versi']) ?></code></span>
        <button type="submit" class="btn btn-primary px-4">Simpan observasi</button>
    </div>
<?= form_close() ?>

<div class="card-body border-top p-4">
    <h2 class="h5 mb-3">Riwayat observasi <span class="badge text-bg-light border ms-1"><?= count($observasi) ?></span></h2>
    <?php if (!$observasi): ?>
        <p class="text-secondary mb-0">Belum ada observasi untuk kunjungan ini.</p>
    <?php else: ?>
        <div class="table-responsive">
            <table class="table table-sm align-middle mb-0">
                <thead>
                    <tr>
                        <th scope="col">Waktu</th>
                        <th scope="col">RR</th>
                        <th scope="col">SpO2</th>
                        <th scope="col">Suhu</th>
                        <th scope="col">TD</th>
                        <th scope="col">Nadi</th>
                        <th scope="col">AVPU</th>
                        <th scope="col">Urin</th>
                        <th scope="col">MEOWS</th>
                        <th scope="col">Pencatat</th>
                        <?php if ($boleh_edit): ?><th scope="col" class="text-end">Tindakan</th><?php endif; ?>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($observasi as $row): ?>
                        <tr>
                            <td class="text-nowrap"><?= html_escape($format_waktu($row['waktu_observasi'])) ?></td>
                            <td><?= html_escape($row['rr_per_menit'] ?? '—') ?></td>
                            <td><?= html_escape($row['spo2_persen'] !== null ? $row['spo2_persen'] . '%' : '—') ?></td>
                            <td><?= html_escape($row['suhu_celsius'] ?? '—') ?></td>
                            <td class="text-nowrap"><?= html_escape(($row['td_sistolik_mmhg'] ?? '—') . '/' . ($row['td_diastolik_mmhg'] ?? '—')) ?></td>
                            <td><?= html_escape($row['nadi_per_menit'] ?? '—') ?></td>
                            <td><?= html_escape($row['avpu'] ?? '—') ?></td>
                            <td><?= html_escape($row['urin_ml_jam'] ?? '—') ?></td>
                            <td class="text-nowrap">
                                <?php if ($row['meows_zona']): ?>
                                    <span class="badge text-bg-<?= $zona_class[$row['meows_zona']] ?>"><?= (int) $row['meows_skor'] ?> · <?= html_escape(ucfirst($row['meows_zona'])) ?></span>
                                    <?php if ((int) $row['meows_parameter_terisi'] < count($meows_rules['parameter'])): ?>
                                        <small class="text-secondary d-block"><?= (int) $row['meows_parameter_terisi'] ?>/<?= count($meows_rules['parameter']) ?> parameter</small>
                                    <?php endif; ?>
                                <?php else: ?>
                                    —
                                <?php endif; ?>
                            </td>
                            <td class="small"><?= html_escape($row['nama_pencatat'] ?: '—') ?></td>
                            <?php if ($boleh_edit): ?>
                                <td class="text-end">
                                    <?= form_open('web/rm/' . $id . '/delete_vital/' . (int) $row['id_observasi'], [
                                        'class' => 'd-inline',
                                        'data-confirm-title' => 'Hapus observasi?',
                                        'data-confirm' => html_escape('Observasi ' . $format_waktu($row['waktu_observasi']) . ' akan dihapus permanen.')
                                    ]) ?>
                                        <button class="btn btn-sm btn-outline-danger" type="submit">Hapus</button>
                                    <?= form_close() ?>
                                </td>
                            <?php endif; ?>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</div>
