<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>
<?php
// Subjudul menandai ruang kerja user: FKTP (puskesmas) atau rumah sakit (FKRTL).
if ($menu_admin) {
    $ruang = 'Administrator';
} elseif ($menu_fkrtl) {
    $ruang = 'Rumah sakit · FKRTL';
} elseif ($menu_fktp) {
    $ruang = 'Puskesmas · FKTP';
} else {
    $ruang = 'Ruang administrasi';
}

// Tag pembuka link menu; ikon dan label ditulis langsung di markup.
$menu_buka = function ($key, $url) use ($active_menu) {
    $aktif = $active_menu === $key;

    return '<a class="nav-link d-flex align-items-center gap-2 ' . ($aktif ? 'active' : '') . '" '
        . ($aktif ? 'aria-current="page" ' : '')
        . 'href="' . html_escape(site_url($url)) . '">';
};
?>
<aside class="offcanvas-lg offcanvas-start app-sidebar" tabindex="-1" id="sidebar" aria-labelledby="sidebar-title">
    <div class="offcanvas-header p-4">
        <a href="<?= html_escape(site_url($beranda)) ?>" class="brand text-decoration-none" id="sidebar-title">EMNIRA<span class="d-block small fw-normal mt-1"><?= html_escape($ruang) ?></span></a>
        <button type="button" class="btn-close btn-close-white d-lg-none" data-bs-dismiss="offcanvas" data-bs-target="#sidebar" aria-label="Tutup menu"></button>
    </div>
    <div class="offcanvas-body d-flex flex-column p-3">
        <?php if (!empty($web_user['nama_faskes'])): ?>
            <div class="sidebar-faskes mx-3 mb-4">
                <span><?= html_escape($web_user['nama_role'] ?: 'Tanpa role') ?></span>
                <strong><?= html_escape($web_user['nama_faskes']) ?></strong>
            </div>
        <?php endif; ?>

        <?php if ($menu_fktp): ?>
            <div class="menu-label px-3 mb-3">RUJUKAN KELUAR</div>
            <nav class="nav nav-pills flex-column mb-4" aria-label="Menu FKTP">
                <?= $menu_buka('rm', 'web/rm') ?>
                    <?php $this->load->view('template/icon', ['icon' => 'patient']); ?> Identitas pasien
                </a>
            </nav>
        <?php endif; ?>

        <?php if ($menu_fkrtl): ?>
            <div class="menu-label px-3 mb-3">RUJUKAN MASUK</div>
            <nav class="nav nav-pills flex-column mb-4" aria-label="Menu rumah sakit">
                <?= $menu_buka('rujukan', 'web/rujukan') ?>
                    <?php $this->load->view('template/icon', ['icon' => 'inbox']); ?> Rujukan masuk
                    <?php if (!empty($rujukan_baru)): ?>
                        <span class="badge rounded-pill text-bg-danger ms-auto" title="Rujukan menunggu diterima"><?= (int) $rujukan_baru ?></span>
                    <?php endif; ?>
                </a>
            </nav>
        <?php endif; ?>

        <?php if ($menu_admin): ?>
            <div class="menu-label px-3 mb-3">ADMINISTRASI</div>
            <nav class="nav nav-pills flex-column" aria-label="Menu administrasi">
                <?= $menu_buka('master', 'web/master') ?>
                    <?php $this->load->view('template/icon', ['icon' => 'database']); ?> Master data
                </a>
                <?= $menu_buka('users', 'web/users') ?>
                    <?php $this->load->view('template/icon', ['icon' => 'users']); ?> Pengguna
                </a>
            </nav>
        <?php endif; ?>

        <?= form_open('web/login/logout', ['class' => 'mt-3']) ?>
            <button class="nav-link d-flex align-items-center gap-2 w-100 border-0 bg-transparent text-start" type="submit"><?php $this->load->view('template/icon', ['icon' => 'logout']); ?> Keluar</button>
        <?= form_close() ?>
        <div class="sidebar-note mt-auto px-3 pt-5 small">Electronic Maternal Neonatal<br>Integrated Referral and Analysis System</div>
    </div>
</aside>
