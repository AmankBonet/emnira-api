'use strict';

(() => {
    // Peringatan bila meninggalkan tab dengan isian yang belum disimpan.
    const guardDirtyForms = () => {
        const forms = document.querySelectorAll('form[data-dirty-guard]');
        let dirty = false;
        let submitting = false;

        forms.forEach((form) => {
            form.addEventListener('input', () => { dirty = true; });
            form.addEventListener('change', () => { dirty = true; });
            form.addEventListener('submit', () => { submitting = true; });
        });

        window.addEventListener('beforeunload', (event) => {
            if (dirty && !submitting) {
                event.preventDefault();
                event.returnValue = '';
            }
        });
    };

    const initGpa = () => {
        const form = document.getElementById('gpaForm');
        if (!form) {
            return;
        }

        const fields = ['gravida', 'partus', 'abortus', 'anak_hidup'];
        const input = (name) => form.elements[name];
        const value = (name) => Math.max(0, parseInt(input(name).value, 10) || 0);
        const riwayatTersimpan = Number(form.dataset.riwayatTersimpan || 0);

        const note = (name, text, tone = 'secondary') => {
            const element = form.querySelector(`[data-gpa-note="${name}"]`);
            element.textContent = text;
            element.className = `small text-${tone}`;
        };

        const update = () => {
            const g = value('gravida');
            const p = value('partus');
            const a = value('abortus');
            const ah = value('anak_hidup');
            const meninggal = Math.max(p - ah, 0);
            const gpaValid = g >= 1 && p + a < g;

            input('gravida').setCustomValidity(g >= 1 ? '' : 'Gravida minimal 1.');
            input('partus').setCustomValidity(gpaValid ? '' : 'Partus + abortus harus lebih kecil dari gravida.');

            if (g <= 1) {
                note('gravida', 'Primigravida (hamil ke-1).');
            } else if (g >= 5) {
                note('gravida', `Grande multigravida (hamil ke-${g}).`, 'warning');
            } else {
                note('gravida', `Multigravida (hamil ke-${g}).`);
            }

            note(
                'partus',
                gpaValid
                    ? (p > 0 ? `Pernah melahirkan ${p} kali.` : 'Belum pernah melahirkan.')
                    : 'Tidak valid: P + A harus lebih kecil dari G.',
                gpaValid ? 'secondary' : 'danger'
            );

            note(
                'abortus',
                a > 0 ? `Riwayat abortus ${a}× terdata.` : 'Tidak ada riwayat abortus.',
                a > 0 ? 'warning' : 'secondary'
            );

            // AH > P sah pada kehamilan kembar, jadi hanya diberi peringatan.
            note(
                'anak_hidup',
                ah > p ? 'AH melebihi P. Pastikan ada riwayat kehamilan kembar.' : 'Konsisten (AH ≤ P).',
                ah > p ? 'warning' : 'secondary'
            );

            document.getElementById('anakMeninggal').textContent = String(meninggal);
            note(
                'anak_meninggal',
                meninggal > 0 ? 'Riwayat obstetri buruk: ada anak meninggal.' : 'Tidak ada riwayat kematian anak.',
                meninggal > 0 ? 'danger' : 'secondary'
            );

            document.getElementById('kodeGpa').textContent = `G${g}P${p}A${a}`;

            const routing = document.getElementById('gpaRouting');
            routing.className = 'alert mt-4 mb-0';
            if (g - 1 < riwayatTersimpan) {
                routing.classList.add('alert-warning');
                routing.textContent = `Riwayat kehamilan ke-${g} s.d. ke-${riwayatTersimpan} yang sudah tersimpan akan dihapus saat disimpan.`;
            } else if (g > 1) {
                routing.classList.add('alert-info');
                routing.textContent = `Tersedia ${g - 1} kolom riwayat kehamilan terdahulu setelah disimpan.`;
            } else {
                routing.classList.add('alert-info');
                routing.textContent = 'Primigravida: tidak ada riwayat kehamilan terdahulu.';
            }

            // Konfirmasi lewat alerts.js hanya bila ada riwayat yang akan terhapus.
            if (g - 1 < riwayatTersimpan) {
                form.dataset.confirmTitle = 'Hapus sebagian riwayat kehamilan?';
                form.dataset.confirm = routing.textContent;
                form.dataset.confirmButton = 'Ya, simpan';
            } else {
                delete form.dataset.confirm;
            }
        };

        form.querySelectorAll('[data-step]').forEach((button) => {
            button.addEventListener('click', () => {
                const target = input(button.dataset.target);
                const min = Number(target.min || 0);
                const max = Number(target.max || 20);
                const next = (parseInt(target.value, 10) || 0) + Number(button.dataset.step);

                target.value = String(Math.min(max, Math.max(min, next)));
                target.dispatchEvent(new Event('input', { bubbles: true }));
            });
        });

        fields.forEach((name) => input(name).addEventListener('input', update));
        update();
    };

    const initRiwayat = () => {
        const form = document.getElementById('riwayatForm');
        if (!form) {
            return;
        }

        const tanpaBayi = JSON.parse(form.dataset.tanpaBayi || '[]');

        form.querySelectorAll('[data-riwayat]').forEach((card) => {
            const field = (name) => card.querySelector(`[data-field="${name}"]`);
            const summary = card.querySelector('[data-riwayat-summary]');
            const selectedText = (name) => {
                const select = field(name);

                return select.value ? select.options[select.selectedIndex].text : '';
            };

            const update = () => {
                const noBaby = tanpaBayi.includes(field('luaran').value);

                // Kolom bayi tidak berlaku untuk abortus/ektopik/mola; server juga mengosongkannya.
                card.querySelectorAll('[data-bayi] select').forEach((select) => {
                    select.disabled = noBaby;
                    if (noBaby) {
                        select.value = '';
                    }
                });

                const parts = [
                    field('tahun_persalinan').value,
                    selectedText('luaran'),
                    selectedText('metode_persalinan')
                ].filter(Boolean);

                summary.textContent = parts.length ? `· ${parts.join(' · ')}` : '· belum diisi';
            };

            card.addEventListener('input', update);
            card.addEventListener('change', update);
            update();
        });
    };

    const initKondisi = () => {
        document.querySelectorAll('[data-kondisi-row]').forEach((row) => {
            const checkbox = row.querySelector('input[type="checkbox"]');
            const catatan = row.querySelector('input[name^="catatan"]');

            checkbox.addEventListener('change', () => {
                catatan.disabled = !checkbox.checked;
                if (checkbox.checked) {
                    catatan.focus();
                }
            });
        });
    };

    guardDirtyForms();
    initGpa();
    initRiwayat();
    initKondisi();
})();
