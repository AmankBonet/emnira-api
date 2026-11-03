<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>
<?php $this->load->view('template/header'); ?>
<body>
    <a class="visually-hidden-focusable position-absolute p-3 bg-white" href="#main-content">Lewati ke konten</a>
    <?php $this->load->view('template/sidebar'); ?>
    <div class="workspace">
        <?php $this->load->view('template/navbar'); ?>
        <main id="main-content" class="container-fluid p-3 p-lg-4">
            <?php foreach (['success' => 'success', 'error' => 'danger'] as $key => $color): ?>
                <?php if ($message = $this->session->flashdata($key)): ?>
                    <div class="alert alert-<?= $color ?> alert-dismissible fade show" role="alert" data-toast-icon="<?= $key ?>" data-toast-message="<?= html_escape($message) ?>">
                        <?= html_escape($message) ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Tutup"></button>
                    </div>
                <?php endif; ?>
            <?php endforeach; ?>
            <?php $this->load->view($content_view); ?>
        </main>
        <?php $this->load->view('template/footer'); ?>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/js/bootstrap.bundle.min.js" integrity="sha384-FKyoEForCGlyvwx9Hj09JcYn3nv7wiPVlz7YYwJrWVcXK/BmnVDxM+D2scQbITxI" crossorigin="anonymous"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11.23.0/dist/sweetalert2.all.min.js" defer></script>
    <script src="<?= html_escape(base_url('assets/web/alerts.js')) ?>" defer></script>
    <?php foreach (isset($page_scripts) ? $page_scripts : [] as $script): ?>
        <script src="<?= html_escape(base_url($script)) ?>" defer></script>
    <?php endforeach; ?>
</body>
</html>
