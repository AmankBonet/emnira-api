<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>
<?php $this->load->view('template/header'); ?>
<body>
    <main class="min-vh-100 d-flex align-items-center justify-content-center p-3">
        <div class="card shadow-sm w-100" style="max-width: 440px;">
            <div class="card-body p-4 p-sm-5">
                <div class="text-uppercase fw-bold text-success mb-4">EMNIRA</div>
                <h1 class="h3 fw-bold">Selamat datang</h1>
                <p class="text-secondary mb-4">Masuk untuk mengelola data EMNIRA.</p>
                <?php if ($error) { ?>
                    <div class="alert alert-danger" role="alert"><?= html_escape($error) ?></div>
                <?php } ?>
                <?= form_open('web/login') ?>
                    <div class="mb-3">
                        <label for="username" class="form-label">Username</label>
                        <input class="form-control" id="username" name="username" value="<?= html_escape($username) ?>" required maxlength="255" autocomplete="username" autofocus>
                    </div>
                    <div class="mb-4">
                        <label for="password" class="form-label">Password</label>
                        <input type="password" class="form-control" id="password" name="password" required autocomplete="current-password">
                    </div>
                    <button class="btn btn-primary w-100 py-2" type="submit">Masuk</button>
                <?= form_close() ?>
            </div>
        </div>
    </main>
</body>
</html>
