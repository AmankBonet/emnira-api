<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>
<aside class="offcanvas-lg offcanvas-start app-sidebar" tabindex="-1" id="sidebar" aria-labelledby="sidebar-title">
    <div class="offcanvas-header p-4">
        <a href="<?= html_escape(site_url('web/users')) ?>" class="brand text-decoration-none" id="sidebar-title">EMNIRA<span class="d-block small fw-normal mt-1">Ruang administrasi</span></a>
        <button type="button" class="btn-close btn-close-white d-lg-none" data-bs-dismiss="offcanvas" data-bs-target="#sidebar" aria-label="Tutup menu"></button>
    </div>
    <div class="offcanvas-body d-flex flex-column p-3">
        <div class="menu-label px-3 mb-3">MASTER DATA</div>
        <nav class="nav nav-pills flex-column" aria-label="Menu utama">
            <a class="nav-link d-flex align-items-center gap-2 <?= $active_menu === 'users' ? 'active' : '' ?>" <?= $active_menu === 'users' ? 'aria-current="page"' : '' ?> href="<?= html_escape(site_url('web/users')) ?>"><?php $this->load->view('template/icon', ['icon' => 'users']); ?> Pengguna</a>
        </nav>
        <?= form_open('web/login/logout', ['class' => 'mt-3']) ?>
            <button class="nav-link d-flex align-items-center gap-2 w-100 border-0 bg-transparent text-start" type="submit"><?php $this->load->view('template/icon', ['icon' => 'logout']); ?> Keluar</button>
        <?= form_close() ?>
        <div class="sidebar-note mt-auto px-3 pt-5 small">Electronic Maternal Neonatal<br>Integrated Referral and Analysis System</div>
    </div>
</aside>
