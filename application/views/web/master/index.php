<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>
<div class="mb-4">
    <div class="text-secondary small mb-2">Administrasi / Master data</div>
    <h1 class="h3 fw-bold mb-2">Master data</h1>
    <p class="text-secondary mb-0">Kelola data acuan yang dipakai di rekam medis dan rujukan.</p>
</div>

<div class="master-grid">
    <a class="master-card" href="<?= html_escape(site_url('web/users')) ?>">
        <span class="master-card-count"><?php $this->load->view('template/icon', ['icon' => 'users']); ?></span>
        <span class="fw-semibold">Pengguna</span>
        <small>Akun login, role, dan faskes petugas.</small>
    </a>
    <?php foreach ($ringkasan as $key => $item): ?>
        <a class="master-card" href="<?= html_escape(site_url('web/master/' . $key)) ?>">
            <span class="master-card-count"><?= (int) $item['jumlah'] ?></span>
            <span class="fw-semibold"><?= html_escape($item['judul']) ?></span>
            <small><?= html_escape($item['keterangan']) ?></small>
        </a>
    <?php endforeach; ?>
</div>
