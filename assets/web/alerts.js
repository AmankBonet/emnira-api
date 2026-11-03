'use strict';

(() => {
    const toast = window.Swal ? Swal.mixin({
        toast: true,
        position: 'top-end',
        showConfirmButton: false,
        showCloseButton: true,
        timer: 3500,
        timerProgressBar: true,
        didOpen: (element) => {
            element.addEventListener('mouseenter', Swal.stopTimer);
            element.addEventListener('mouseleave', Swal.resumeTimer);
        }
    }) : null;

    window.AppAlert = {
        toast(message, icon = 'success') {
            if (!toast) {
                window.alert(message);
                return Promise.resolve();
            }
            return toast.fire({ titleText: message, icon });
        },

        async confirm({ title = 'Hapus data?', text = 'Tindakan ini tidak dapat dibatalkan.', confirmText = 'Ya, hapus' } = {}) {
            if (!window.Swal) {
                return window.confirm(`${title}\n${text}`);
            }
            const result = await Swal.fire({
                titleText: title,
                text,
                icon: 'warning',
                showCancelButton: true,
                confirmButtonText: confirmText,
                cancelButtonText: 'Batal',
                confirmButtonColor: '#dc3545',
                cancelButtonColor: '#617382',
                focusCancel: true,
                reverseButtons: true
            });
            return result.isConfirmed;
        }
    };

    // Form lain cukup memakai data-confirm; token CSRF tetap dikirim lewat POST biasa.
    const pending = new WeakSet();
    const approved = new WeakSet();
    document.addEventListener('submit', async (event) => {
        const form = event.target;
        if (!form.matches('form[data-confirm]')) {
            return;
        }
        if (approved.has(form)) {
            approved.delete(form);
            return;
        }
        event.preventDefault();
        if (pending.has(form)) {
            return;
        }
        pending.add(form);
        try {
            const confirmed = await AppAlert.confirm({
                title: form.dataset.confirmTitle || 'Hapus data?',
                text: form.dataset.confirm,
                confirmText: form.dataset.confirmButton || 'Ya, hapus'
            });
            if (confirmed) {
                approved.add(form);
                form.requestSubmit();
            }
        } finally {
            pending.delete(form);
        }
    });

    if (toast) {
        (async () => {
            for (const element of document.querySelectorAll('[data-toast-message]')) {
                element.hidden = true;
                await AppAlert.toast(element.dataset.toastMessage, element.dataset.toastIcon);
            }
        })();
    }
})();
