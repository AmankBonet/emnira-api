<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>
<?php $this->load->view('template/header'); ?>
<body class="login-body">
    <main class="login-shell">
        <section class="login-brand" aria-label="Tentang EMNIRA">
            <div class="login-brand-inner">
                <div class="login-logo">
                    <svg viewBox="0 0 48 48" width="44" height="44" aria-hidden="true" focusable="false">
                        <rect width="48" height="48" rx="14" fill="#087f78"/>
                        <path d="M9 26h7l3.5-8 5 15 4-11 2.5 4H39" fill="none" stroke="#fff" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"/>
                    </svg>
                    <span>EMNIRA</span>
                </div>

                <h1 class="login-headline">Rujukan maternal neonatal yang terhubung, cepat, dan terukur.</h1>
                <p class="login-sub">Electronic Maternal Neonatal Integrated Referral and Analysis System</p>

                <ul class="login-features">
                    <li>
                        <span class="login-feature-icon" aria-hidden="true">
                            <svg viewBox="0 0 24 24" width="20" height="20" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M17 3l4 4-4 4"/><path d="M3 7h18"/><path d="M7 21l-4-4 4-4"/><path d="M21 17H3"/></svg>
                        </span>
                        <div>
                            <strong>Rujukan terintegrasi</strong>
                            <span>Puskesmas mengirim, rumah sakit menerima dan mencatat kedatangan secara langsung.</span>
                        </div>
                    </li>
                    <li>
                        <span class="login-feature-icon" aria-hidden="true">
                            <svg viewBox="0 0 24 24" width="20" height="20" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M22 12h-4l-3 9L9 3l-3 9H2"/></svg>
                        </span>
                        <div>
                            <strong>Pemantauan MEOWS</strong>
                            <span>Skor peringatan dini obstetri dihitung otomatis dari setiap observasi.</span>
                        </div>
                    </li>
                    <li>
                        <span class="login-feature-icon" aria-hidden="true">
                            <svg viewBox="0 0 24 24" width="20" height="20" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M9 11l3 3L22 4"/><path d="M21 12v7a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11"/></svg>
                        </span>
                        <div>
                            <strong>Stabilisasi pra-rujukan</strong>
                            <span>Checklist protokol per diagnosa dengan target waktu dan ringkasan SBAR.</span>
                        </div>
                    </li>
                </ul>
            </div>
            <p class="login-copy">© <?= date('Y') ?> EMNIRA</p>
        </section>

        <section class="login-panel" aria-labelledby="login-title">
            <div class="login-card">
                <h2 class="login-title" id="login-title">Masuk ke akun Anda</h2>
                <p class="login-desc">Gunakan akun yang diberikan administrator EMNIRA.</p>

                <?php if ($error): ?>
                    <div class="login-alert" role="alert">
                        <svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="12" cy="12" r="10"/><path d="M12 8v4m0 4h.01"/></svg>
                        <span><?= html_escape($error) ?></span>
                    </div>
                <?php endif; ?>

                <?= form_open('web/login', ['id' => 'loginForm', 'novalidate' => 'novalidate']) ?>
                    <div class="login-field">
                        <label for="username">Username</label>
                        <div class="login-input">
                            <svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>
                            <input id="username" name="username" value="<?= html_escape($username) ?>" required maxlength="255" autocomplete="username" placeholder="Masukkan username" <?= $username === '' ? 'autofocus' : '' ?>>
                        </div>
                    </div>

                    <div class="login-field">
                        <label for="password">Password</label>
                        <div class="login-input">
                            <svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><rect x="3" y="11" width="18" height="11" rx="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/></svg>
                            <input type="password" id="password" name="password" required autocomplete="current-password" placeholder="Masukkan password" <?= $username !== '' ? 'autofocus' : '' ?>>
                            <button type="button" class="login-toggle" id="togglePassword" aria-label="Tampilkan password" aria-pressed="false">
                                <svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg>
                            </button>
                        </div>
                        <div class="login-hint" id="capsHint" hidden>Caps Lock sedang aktif.</div>
                    </div>

                    <button class="login-submit" type="submit" id="loginSubmit">
                        <span class="login-spinner" aria-hidden="true"></span>
                        <span class="login-submit-text">Masuk</span>
                    </button>
                <?= form_close() ?>

                <p class="login-help">Lupa password atau akun terkunci? Hubungi administrator EMNIRA di faskes Anda.</p>
            </div>
        </section>
    </main>
    <script src="<?= html_escape(asset_url('assets/web/login.js')) ?>" defer></script>
</body>
</html>
