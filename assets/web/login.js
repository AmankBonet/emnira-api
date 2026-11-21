'use strict';

(() => {
    const form = document.getElementById('loginForm');
    const password = document.getElementById('password');
    const toggle = document.getElementById('togglePassword');
    const capsHint = document.getElementById('capsHint');
    const submit = document.getElementById('loginSubmit');

    toggle.addEventListener('click', () => {
        const tampil = password.type === 'password';
        password.type = tampil ? 'text' : 'password';
        toggle.setAttribute('aria-pressed', String(tampil));
        toggle.setAttribute('aria-label', tampil ? 'Sembunyikan password' : 'Tampilkan password');
        password.focus();
    });

    // Peringatan Caps Lock mengurangi salah ketik password.
    const cekCaps = (event) => {
        if (typeof event.getModifierState === 'function') {
            capsHint.hidden = !event.getModifierState('CapsLock');
        }
    };
    password.addEventListener('keydown', cekCaps);
    password.addEventListener('keyup', cekCaps);
    password.addEventListener('blur', () => { capsHint.hidden = true; });

    form.addEventListener('submit', (event) => {
        // Validasi sederhana di browser; server tetap memvalidasi ulang.
        const kosong = [...form.querySelectorAll('input[required]')].filter((input) => input.value.trim() === '');
        form.querySelectorAll('.login-input').forEach((wrap) => wrap.classList.remove('is-invalid'));

        if (kosong.length) {
            event.preventDefault();
            kosong.forEach((input) => input.closest('.login-input').classList.add('is-invalid'));
            kosong[0].focus();

            return;
        }

        // Cegah pengiriman ganda saat koneksi lambat.
        submit.disabled = true;
        submit.classList.add('is-loading');
        submit.querySelector('.login-submit-text').textContent = 'Memproses…';
    });
})();
