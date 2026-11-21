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
        const rows = document.querySelectorAll('[data-kondisi-row]');
        const count = document.getElementById('kondisiCount');
        const list = document.getElementById('kondisiList');

        if (!rows.length) {
            return;
        }

        const updateSummary = () => {
            const selected = [...rows]
                .map((row) => row.querySelector('input[type="checkbox"]'))
                .filter((checkbox) => checkbox.checked)
                .map((checkbox) => checkbox.dataset.label);

            count.textContent = `${selected.length} kondisi terpilih`;
            list.textContent = selected.length ? `: ${selected.join(' | ')}` : '';
        };

        rows.forEach((row) => {
            const checkbox = row.querySelector('input[type="checkbox"]');
            const catatan = row.querySelector('input[name^="catatan"]');

            checkbox.addEventListener('change', () => {
                row.classList.toggle('is-checked', checkbox.checked);
                catatan.disabled = !checkbox.checked;

                // Catatan wajib (contoh: nama obat alergi) hanya berlaku saat kondisi dipilih.
                catatan.required = checkbox.checked && catatan.dataset.wajib === '1';

                if (checkbox.checked && catatan.required) {
                    catatan.focus();
                }

                updateSummary();
            });
        });

        updateSummary();
    };

    const initVital = () => {
        const form = document.getElementById('vitalForm');
        if (!form) {
            return;
        }

        const rules = JSON.parse(form.dataset.meows);
        const turunan = rules.turunan;
        const zonaClass = { normal: 'success', kuning: 'warning', merah: 'danger' };
        const zonaLabel = { normal: 'Normal', kuning: 'Kuning', merah: 'Merah' };
        const numberFormat = (value, digits = 1) => value.toLocaleString('id-ID', {
            minimumFractionDigits: 0,
            maximumFractionDigits: digits
        });

        const read = (name) => {
            const raw = (form.elements[name]?.value ?? '').trim().replace(',', '.');

            return raw === '' ? null : raw;
        };

        const readNumber = (name) => {
            const raw = read(name);
            const number = raw === null ? NaN : Number(raw);

            return Number.isFinite(number) ? number : null;
        };

        // Logika sama dengan libraries/Meows.php::nilai_parameter.
        const evaluate = (field, parameter) => {
            const raw = read(field);
            if (raw === null) {
                return null;
            }

            if (parameter.tipe === 'pilihan') {
                const pilihan = parameter.pilihan[raw];

                return pilihan ? { zona: pilihan.zona, skor: pilihan.skor } : null;
            }

            const digits = parameter.step < 1 ? 1 : 0;
            const value = Number(Number(raw).toFixed(digits));
            if (!Number.isFinite(value)) {
                return null;
            }

            const rentang = parameter.rentang.find((item) => (item.min === null || value >= item.min)
                && (item.max === null || value <= item.max));

            return rentang ? { zona: rentang.zona, skor: rentang.skor } : null;
        };

        const setDerived = (name, text, tone = '') => {
            const element = form.querySelector(`[data-derived="${name}"]`);
            element.textContent = text;
            element.className = tone ? `text-${tone}` : '';
        };

        const updateDerived = () => {
            const sis = readNumber('td_sistolik_mmhg');
            const dia = readNumber('td_diastolik_mmhg');
            const nadi = readNumber('nadi_per_menit');
            const spo2 = readNumber('spo2_persen');
            const urin = readNumber('urin_ml_jam');
            const bb = readNumber('berat_badan_kg');
            const tfu = readNumber('tfu_cm');
            const djj = readNumber('djj_per_menit');
            const kepala = read('kepala_masuk_panggul');

            if (sis !== null && dia !== null) {
                const map = (sis + 2 * dia) / 3;
                const [low, high] = turunan.map_normal;
                const status = map < low ? 'rendah' : (map > high ? 'tinggi' : 'normal');
                setDerived('map', `${numberFormat(map)} mmHg (${status})`, status === 'normal' ? 'success' : 'danger');
            } else {
                setDerived('map', 'Isi TDS & TDD');
            }

            if (tfu !== null && kepala !== null) {
                const tbj = Math.max(Math.round((tfu - (kepala === '1' ? 11 : 12)) * 155), 0);
                setDerived('tbj', `${numberFormat(tbj, 0)} gram`);
            } else {
                setDerived('tbj', 'Isi TFU & posisi kepala');
            }

            const parts = [];
            let tone = 'success';
            if (djj !== null) {
                const [low, high] = turunan.djj_normal;
                const status = djj < low ? 'Bradikardia' : (djj > high ? 'Takikardia' : 'Normal');
                parts.push(`DJJ ${status}`);
                if (status !== 'Normal') {
                    tone = 'danger';
                }
            }
            if (nadi !== null && sis) {
                const si = nadi / sis;
                parts.push(`SI: ${numberFormat(si, 2)}`);
                if (si >= turunan.shock_index_waspada) {
                    tone = 'danger';
                }
            }
            setDerived('djj_si', parts.length ? parts.join(' | ') : 'Isi DJJ / nadi & TDS', parts.length ? tone : '');

            if (urin !== null && bb) {
                const rate = urin / bb;
                const oliguria = rate < turunan.urin_oliguria_ml_kg_jam;
                setDerived(
                    'uo',
                    `${numberFormat(rate, 2)} mL/kgBB/jam${oliguria ? ' (OLIGURIA)' : ''}`,
                    oliguria ? 'danger' : 'success'
                );
            } else {
                setDerived('uo', 'Isi urin & BB');
            }

            const o2Parts = [];
            if (spo2 !== null) {
                if (spo2 < turunan.o2_mulai_bila_spo2_kurang_dari) {
                    const flow = Math.max(
                        turunan.o2_min_lpm,
                        Math.ceil((turunan.o2_spo2_target - spo2) / turunan.o2_pembagi)
                    );
                    o2Parts.push(`${flow} L/m`);
                } else {
                    o2Parts.push('O2 tidak perlu');
                }
            }
            if (bb) {
                const [min, max] = turunan.cairan_ml_kg;
                o2Parts.push(`Resusitasi: ${numberFormat(min * bb, 0)}–${numberFormat(max * bb, 0)} mL`);
            }
            setDerived('o2', o2Parts.length ? o2Parts.join(' | ') : 'Isi SpO2 / BB');
        };

        const update = () => {
            let skor = 0;
            let merah = 0;
            let kuning = 0;
            let terisi = 0;
            const total = Object.keys(rules.parameter).length;

            Object.entries(rules.parameter).forEach(([field, parameter]) => {
                const row = form.querySelector(`[data-meows-param="${field}"]`);
                const hasil = evaluate(field, parameter);
                const skorElement = row.querySelector('[data-skor]');
                const zonaElement = row.querySelector('[data-zona]');

                row.dataset.zona = hasil ? hasil.zona : '';

                if (!hasil) {
                    skorElement.textContent = '—';
                    zonaElement.className = 'badge text-bg-light border';
                    zonaElement.textContent = '—';

                    return;
                }

                terisi++;
                skor += hasil.skor;
                merah += hasil.zona === 'merah' ? 1 : 0;
                kuning += hasil.zona === 'kuning' ? 1 : 0;

                skorElement.textContent = String(hasil.skor);
                zonaElement.className = `badge text-bg-${zonaClass[hasil.zona]}`;
                zonaElement.textContent = `${zonaLabel[hasil.zona]} (Skor ${hasil.skor})`;
            });

            // Logika pemicu sama dengan libraries/Meows.php::zona.
            const zona = merah >= rules.pemicu.merah_min_merah || kuning >= rules.pemicu.merah_min_kuning
                ? 'merah'
                : (kuning > 0 ? 'kuning' : 'normal');

            const zonaElement = document.getElementById('meowsZona');
            document.getElementById('meowsSkor').textContent = terisi ? String(skor) : '—';
            zonaElement.className = terisi ? `badge text-bg-${zonaClass[zona]}` : 'badge text-bg-light border';
            zonaElement.textContent = terisi ? `Zona ${zonaLabel[zona]}` : 'Belum dinilai';
            document.getElementById('meowsTotal').dataset.zona = terisi ? zona : '';
            document.getElementById('meowsRincian').textContent = terisi
                ? `${merah} merah · ${kuning} kuning · ${terisi}/${total} parameter`
                : '';

            updateDerived();
        };

        form.addEventListener('input', update);
        form.addEventListener('change', update);
        update();
    };

    const initStabilisasi = () => {
        const form = document.getElementById('stabilisasiForm');
        const beralasan = ['tidak_dilakukan', 'tidak_diperlukan'];

        const nowInput = () => {
            const now = new Date();
            now.setMinutes(now.getMinutes() - now.getTimezoneOffset());

            return now.toISOString().slice(0, 16);
        };

        if (form) {
            form.querySelectorAll('[data-stab-row]').forEach((row) => {
                const status = row.querySelector('[data-stab-status]');
                const waktu = row.querySelector('[data-stab-waktu]');
                const alasan = row.querySelector('[data-stab-alasan]');

                const update = () => {
                    row.dataset.status = status.value;

                    // Alasan hanya relevan dan wajib untuk status yang tidak dilaksanakan.
                    const perluAlasan = beralasan.includes(status.value);
                    alasan.hidden = !perluAlasan && alasan.value === '';
                    alasan.required = perluAlasan;
                    waktu.required = status.value === 'dilakukan';
                };

                status.addEventListener('change', () => {
                    if (status.value === 'dilakukan' && waktu.value === '') {
                        waktu.value = nowInput();
                    }

                    update();
                });

                update();
            });
        }

        // Hitung mundur target waktu; status final tetap ditentukan server saat halaman dimuat ulang.
        const countdowns = document.querySelectorAll('[data-deadline]');
        const tick = () => {
            countdowns.forEach((badge) => {
                const sisa = Math.round((Number(badge.dataset.deadline) - Date.now()) / 60000);

                badge.className = `badge text-bg-${sisa < 0 ? 'danger' : 'warning'}`;
                badge.textContent = sisa < 0 ? `Terlewat ${-sisa} mnt` : `Sisa ${sisa} mnt`;
            });
        };

        if (countdowns.length) {
            tick();
            window.setInterval(tick, 15000);
        }

        document.querySelectorAll('[data-copy-target]').forEach((button) => {
            button.addEventListener('click', async () => {
                const text = document.getElementById(button.dataset.copyTarget).value;

                try {
                    await navigator.clipboard.writeText(text);
                    window.AppAlert?.toast('Teks SBAR disalin.');
                } catch (error) {
                    window.AppAlert?.toast('Gagal menyalin; salin manual dari kotak teks.', 'error');
                }
            });
        });
    };

    // Di layar sempit navigasi langkah digulir menyamping; pastikan langkah aktif terlihat.
    const revealActiveStep = () => {
        const steps = document.querySelector('.rm-steps');
        const active = steps?.querySelector('.rm-step.active');

        if (steps && active && steps.scrollWidth > steps.clientWidth) {
            steps.scrollLeft = active.offsetLeft - (steps.clientWidth - active.offsetWidth) / 2;
        }
    };

    guardDirtyForms();
    revealActiveStep();
    initVital();
    initStabilisasi();
    initGpa();
    initRiwayat();
    initKondisi();
})();
