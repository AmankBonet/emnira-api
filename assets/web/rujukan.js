'use strict';

(() => {
    const list = document.querySelector('[data-auto-refresh]');
    const detik = Number(list?.dataset.autoRefresh || 0);

    if (!detik) {
        return;
    }

    // Muat ulang hanya saat tab terlihat, agar rujukan baru segera tampil tanpa membebani server.
    window.setInterval(() => {
        if (document.visibilityState === 'visible') {
            window.location.reload();
        }
    }, detik * 1000);
})();
