<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>
<?php
$state_items = $state_data ? $state_data['item'] : null;

$to_input_time = function ($value) {
    $date = $value ? DateTime::createFromFormat('Y-m-d H:i:s', $value) : false;

    return $date ? $date->format('Y-m-d\TH:i') : '';
};

$format_waktu = function ($value) {
    $date = $value ? DateTime::createFromFormat('Y-m-d H:i:s', $value) : false;

    return $date ? $date->format('d/m/Y H:i') : '—';
};

// Nilai form: isian yang gagal divalidasi, lalu data tersimpan, lalu saran otomatis dari observasi.
$values = [];
foreach ($stabilisasi_items as $id_item => $item) {
    if ($state_items && isset($state_items[$id_item])) {
        $values[$id_item] = $state_items[$id_item] + ['otomatis' => false];
    } elseif (isset($pelaksanaan[$id_item])) {
        $row = $pelaksanaan[$id_item];
        $values[$id_item] = [
            'status' => $row['status'],
            'detail_tindakan' => (string) $row['detail_tindakan'],
            'alasan' => (string) $row['alasan'],
            'waktu_pelaksanaan' => $to_input_time($row['waktu_pelaksanaan']),
            'otomatis' => false
        ];
    } else {
        $auto = $aturan_otomatis[$id_item];
        $saran = $item['jenis'] === 'kriteria' && $auto !== null;
        $values[$id_item] = [
            'status' => $saran ? ($auto['terpenuhi'] ? 'terpenuhi' : 'tidak_terpenuhi') : 'belum',
            'detail_tindakan' => '',
            'alasan' => '',
            'waktu_pelaksanaan' => '',
            'otomatis' => $saran
        ];
    }
}

// Kelompokkan butir per diagnosa lalu per komponen (red flag, A-E).
$groups = [];
foreach ($stabilisasi_items as $id_item => $item) {
    $groups[$item['id_diagnosis']][$item['komponen']][$id_item] = $item;
}

$sifat_badge = [
    'wajib' => ['Wajib', 'text-bg-secondary'],
    'kondisional' => ['Kondisional', 'text-bg-light border'],
    'alternatif' => ['Pilih salah satu', 'text-bg-light border']
];

$target_label = [
    'tercapai' => ['Target tercapai', 'success'],
    'terlambat' => ['Terlambat', 'danger'],
    'terlewat' => ['Target terlewat', 'danger'],
    'berjalan' => ['Berjalan', 'warning']
];

$persen = $stabilisasi_ringkasan['persen'];
$persen_class = $persen === null ? 'secondary' : ($persen >= 100 ? 'success' : ($persen >= 50 ? 'warning' : 'danger'));
?>
<div class="card-body p-4 border-bottom">
    <h2 class="h5 mb-1">Diagnosa kerja</h2>
    <p class="text-secondary small mb-3">Checklist stabilisasi mengikuti diagnosa yang dipilih. Boleh lebih dari satu.</p>
    <?= form_open('web/rm/' . $id . '/save_diagnosis', ['class' => 'd-flex flex-wrap align-items-center gap-2']) ?>
        <?php foreach ($diagnosis_options as $option): ?>
            <?php $checked = isset($diagnosis_kunjungan[$option['id_diagnosis']]); ?>
            <input type="checkbox" class="btn-check" id="diagnosis_<?= (int) $option['id_diagnosis'] ?>" name="diagnosis[]" value="<?= (int) $option['id_diagnosis'] ?>" <?= $checked ? 'checked' : '' ?> autocomplete="off">
            <label class="btn btn-sm btn-outline-primary" for="diagnosis_<?= (int) $option['id_diagnosis'] ?>">
                <span class="font-monospace"><?= html_escape($option['kode']) ?></span>
                <?= html_escape($option['nama']) ?>
                <span class="badge text-bg-light border ms-1"><?= (int) $option['jumlah_item'] ? (int) $option['jumlah_item'] . ' butir' : 'protokol belum ada' ?></span>
            </label>
        <?php endforeach; ?>
        <button type="submit" class="btn btn-sm btn-primary ms-auto">Terapkan diagnosa</button>
    <?= form_close() ?>
</div>

<?php if (!$diagnosis_kunjungan): ?>
    <div class="card-body p-5 text-center">
        <h2 class="h5">Belum ada diagnosa kerja</h2>
        <p class="text-secondary mb-0">Pilih diagnosa di atas untuk menampilkan checklist stabilisasi pra-rujukan.</p>
    </div>
<?php elseif (!$stabilisasi_items): ?>
    <div class="card-body p-5 text-center">
        <h2 class="h5">Protokol belum tersedia</h2>
        <p class="text-secondary mb-0">Diagnosa terpilih belum memiliki butir pada <code>m_stabilisasi_item</code>.</p>
    </div>
<?php else: ?>
    <div class="card-body p-4 border-bottom">
        <div class="row g-3">
            <div class="col-md-6">
                <div class="stab-stat">
                    <span>Kepatuhan stabilisasi</span>
                    <strong class="text-<?= $persen_class ?>"><?= $persen === null ? '—' : $persen . '%' ?></strong>
                    <div class="progress" role="progressbar" aria-label="Kepatuhan" aria-valuenow="<?= (int) $persen ?>" aria-valuemin="0" aria-valuemax="100">
                        <div class="progress-bar bg-<?= $persen_class ?>" style="width: <?= (int) $persen ?>%"></div>
                    </div>
                    <small><?= (int) $stabilisasi_ringkasan['selesai'] ?> dari <?= (int) $stabilisasi_ringkasan['total'] ?> tindakan wajib/terpilih sudah dilakukan</small>
                </div>
            </div>
            <div class="col-md-6">
                <div class="stab-stat">
                    <span>Trigger merah MEOWS pertama</span>
                    <strong class="<?= $trigger_merah ? 'text-danger' : '' ?>"><?= html_escape($format_waktu($trigger_merah)) ?></strong>
                    <small><?= $trigger_merah ? 'Titik nol target waktu (contoh: evaluasi dokter < 10 menit).' : 'Belum ada observasi zona merah; target waktu belum berjalan.' ?></small>
                </div>
            </div>
        </div>
    </div>

    <?= form_open('web/rm/' . $id . '/save_stabilisasi', [
        'id' => 'stabilisasiForm',
        'data-dirty-guard' => '1'
    ]) ?>
        <div class="card-body p-4">
            <?php foreach ($groups as $id_diagnosis => $komponen_list): ?>
                <?php if (count($groups) > 1): ?>
                    <h2 class="h5 mt-2 mb-3"><?= html_escape($diagnosis_kunjungan[$id_diagnosis]['kode'] . ' · ' . $diagnosis_kunjungan[$id_diagnosis]['nama']) ?></h2>
                <?php endif; ?>

                <?php foreach ($komponen_list as $komponen => $komponen_items): ?>
                    <h3 class="h6 text-secondary mt-4 mb-2"><?= html_escape($stabilisasi_komponen[$komponen]) ?></h3>
                    <div class="d-flex flex-column gap-2">
                        <?php foreach ($komponen_items as $id_item => $item): ?>
                            <?php
                            $value = $values[$id_item];
                            $name = 'item[' . $id_item . ']';
                            $field_id = 'item_' . $id_item;
                            $options = $stabilisasi_status[$item['jenis']];
                            if ($item['jenis'] === 'tindakan' && $item['sifat'] !== 'wajib') {
                                $options['tidak_diperlukan'] = $stabilisasi_status['tidak_diperlukan'];
                            }
                            $auto = $aturan_otomatis[$id_item];
                            $target = isset($stabilisasi_ringkasan['target'][$id_item]) ? $stabilisasi_ringkasan['target'][$id_item] : null;
                            ?>
                            <div class="stab-row" data-stab-row data-status="<?= html_escape($value['status']) ?>">
                                <div>
                                    <label class="fw-semibold d-block" for="<?= $field_id ?>_status"><?= html_escape($item['teks_standar']) ?></label>
                                    <div class="d-flex flex-wrap gap-1 mt-1 small">
                                        <?php if ($item['jenis'] === 'tindakan'): ?>
                                            <span class="badge <?= $sifat_badge[$item['sifat']][1] ?>"><?= $sifat_badge[$item['sifat']][0] ?></span>
                                        <?php endif; ?>
                                        <?php if ($item['dosis_ke']): ?>
                                            <span class="badge text-bg-light border">Dosis ke-<?= (int) $item['dosis_ke'] ?><?= $item['jeda_menit'] ? ' · jeda ≥ ' . (int) $item['jeda_menit'] . ' mnt' : '' ?></span>
                                        <?php endif; ?>
                                        <?php if ($item['target_menit']): ?>
                                            <span class="badge text-bg-light border">Target ≤ <?= (int) $item['target_menit'] ?> mnt sejak trigger merah</span>
                                        <?php endif; ?>
                                        <?php if ($target): ?>
                                            <span class="badge text-bg-<?= $target_label[$target['status']][1] ?>" <?= $target['status'] === 'berjalan' ? 'data-deadline="' . (int) $target['deadline'] * 1000 . '"' : '' ?>><?= $target_label[$target['status']][0] ?></span>
                                        <?php endif; ?>
                                    </div>
                                    <?php if ($item['syarat']): ?>
                                        <div class="small text-secondary mt-1">Syarat: <?= html_escape($item['syarat']) ?></div>
                                    <?php endif; ?>
                                    <?php if ($auto !== null): ?>
                                        <?php
                                        // Kriteria: terpenuhi berarti red flag ada. Tindakan: terpenuhi berarti syarat pemberian ada.
                                        $auto_teks = $item['jenis'] === 'kriteria'
                                            ? ($auto['terpenuhi'] ? 'memenuhi kriteria' : 'tidak memenuhi kriteria')
                                            : ($auto['terpenuhi'] ? 'syarat pemberian terpenuhi' : 'syarat pemberian tidak terpenuhi');
                                        ?>
                                        <div class="small mt-1 text-<?= $auto['terpenuhi'] ? 'danger' : 'success' ?>">
                                            Observasi terakhir: <?= html_escape($auto['teks']) ?> → <?= $auto_teks ?>
                                            <?php if ($value['otomatis']): ?>(status diisi otomatis, simpan untuk mencatat)<?php endif; ?>
                                        </div>
                                    <?php endif; ?>
                                </div>
                                <div class="stab-controls">
                                    <select class="form-select form-select-sm" id="<?= $field_id ?>_status" name="<?= $name ?>[status]" data-stab-status>
                                        <?php foreach ($options as $key => $text): ?>
                                            <option value="<?= $key ?>" <?= $value['status'] === $key ? 'selected' : '' ?>><?= html_escape($text) ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                    <input class="form-control form-control-sm" type="datetime-local" name="<?= $name ?>[waktu_pelaksanaan]" value="<?= html_escape($value['waktu_pelaksanaan']) ?>" max="<?= date('Y-m-d\TH:i', time() + 300) ?>" aria-label="Waktu pelaksanaan" data-stab-waktu>
                                    <input class="form-control form-control-sm stab-detail" name="<?= $name ?>[detail_tindakan]" value="<?= html_escape($value['detail_tindakan']) ?>" maxlength="500" placeholder="<?= $item['jenis'] === 'kriteria' ? 'Temuan / catatan' : 'Detail dosis / tindakan terlaksana' ?>" aria-label="Detail">
                                    <input class="form-control form-control-sm stab-detail" name="<?= $name ?>[alasan]" value="<?= html_escape($value['alasan']) ?>" maxlength="255" placeholder="Alasan (wajib)" aria-label="Alasan" data-stab-alasan>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endforeach; ?>
            <?php endforeach; ?>
        </div>
        <div class="card-footer bg-white d-flex flex-wrap justify-content-between align-items-center gap-3 p-4">
            <span class="small text-secondary">Status "Sudah dilakukan" mengisi waktu sekarang bila kosong.</span>
            <button type="submit" class="btn btn-primary px-4">Simpan checklist</button>
        </div>
    <?= form_close() ?>

    <?php
    // Ringkasan SBAR hanya dari data tersimpan, bukan isian yang belum disimpan.
    $sbar_assessment = [];
    $sbar_tindakan = [];
    foreach ($stabilisasi_items as $id_item => $item) {
        $row = isset($pelaksanaan[$id_item]) ? $pelaksanaan[$id_item] : null;
        if (!$row || !(int) $item['masuk_sbar']) {
            continue;
        }

        if ($item['jenis'] === 'kriteria' && $row['status'] === 'terpenuhi') {
            $sbar_assessment[] = '- ' . $item['teks_standar'] . ($row['detail_tindakan'] ? ' (' . $row['detail_tindakan'] . ')' : '');
        } elseif (in_array($row['status'], ['dilakukan', 'dalam_proses'], true)) {
            $waktu = $row['waktu_pelaksanaan'] ? substr($row['waktu_pelaksanaan'], 11, 5) . ' ' : '';
            $sbar_tindakan[] = [
                // Tindakan tanpa waktu (dalam proses) diletakkan paling akhir.
                'urut' => $row['waktu_pelaksanaan'] ?: '9999',
                'teks' => '- ' . $waktu . ($row['detail_tindakan'] ?: $item['teks_standar'])
                    . ($row['status'] === 'dalam_proses' ? ' [dalam proses]' : '')
            ];
        }
    }

    // SBAR dibaca sebagai kronologi tindakan, bukan urutan protokol.
    usort($sbar_tindakan, function ($a, $b) {
        return strcmp($a['urut'], $b['urut']);
    });
    $sbar_tindakan = array_column($sbar_tindakan, 'teks');

    $sbar = "A (Assessment) - kriteria terpenuhi:\n"
        . ($sbar_assessment ? implode("\n", $sbar_assessment) : '- (belum ada)')
        . "\n\nR (Recommendation) - stabilisasi yang sudah dilakukan:\n"
        . ($sbar_tindakan ? implode("\n", $sbar_tindakan) : '- (belum ada)');
    ?>
    <div class="card-body border-top p-4">
        <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-2">
            <h2 class="h5 mb-0">Ringkasan untuk SBAR</h2>
            <button type="button" class="btn btn-sm btn-outline-secondary" data-copy-target="sbarText">Salin teks</button>
        </div>
        <p class="small text-secondary">Dibentuk dari checklist yang sudah disimpan.</p>
        <textarea class="form-control font-monospace small" id="sbarText" rows="8" readonly><?= html_escape($sbar) ?></textarea>
    </div>
<?php endif; ?>
