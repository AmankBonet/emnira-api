'use strict';

(() => {
    const DAY_MS = 24 * 60 * 60 * 1000;

    const modal = document.getElementById('patientModal');
    const form = document.getElementById('patientForm');
    const errorBox = document.getElementById('patientErrors');
    const stateElement = document.getElementById('patientFormState');
    const usiaHelp = document.getElementById('usiaHelp');
    const durasiKehamilan = Number(form.dataset.durasiKehamilan || 280);
    const fields = [
        'id_kunjungan',
        'no_rm',
        'nama',
        'alamat',
        'tgl_lahir',
        'tgl_reg',
        'lokasi',
        'id_faskes_perujuk',
        'id_faskes_tujuan',
        'hpht'
    ];
    let pendingState = stateElement ? JSON.parse(stateElement.textContent) : null;

    // Tanggal diolah dalam UTC agar selisih hari tidak bergeser karena zona waktu.
    const parseDate = (value) => {
        if (!/^\d{4}-\d{2}-\d{2}$/.test(value || '')) {
            return null;
        }

        const [year, month, day] = value.split('-').map(Number);
        const time = Date.UTC(year, month - 1, day);

        return Number.isNaN(time) ? null : time;
    };

    const formatDate = (time) => new Date(time).toISOString().slice(0, 10);

    const today = () => {
        const now = new Date();

        return formatDate(Date.UTC(now.getFullYear(), now.getMonth(), now.getDate()));
    };

    // Perhitungan ini hanya pratinjau; nilai yang disimpan dihitung ulang oleh server.
    const updatePregnancy = () => {
        const hpht = parseDate(form.elements.hpht.value);
        const reference = parseDate(form.elements.tgl_reg.value) ?? parseDate(today());

        form.elements.hpl.value = '';
        form.elements.usia_kehamilan.value = '';
        usiaHelp.textContent = 'Otomatis per tanggal registrasi.';
        usiaHelp.classList.remove('text-danger');

        if (hpht === null) {
            return;
        }

        form.elements.hpl.value = formatDate(hpht + durasiKehamilan * DAY_MS);

        const days = Math.round((reference - hpht) / DAY_MS);
        if (days < 0) {
            usiaHelp.textContent = 'HPHT tidak boleh melebihi tanggal registrasi.';
            usiaHelp.classList.add('text-danger');

            return;
        }

        form.elements.usia_kehamilan.value = `${Math.floor(days / 7)} minggu ${days % 7} hari`;

        const sisa = durasiKehamilan - days;
        usiaHelp.textContent = sisa >= 0
            ? `${sisa} hari menuju HPL.`
            : `Melewati HPL ${-sisa} hari.`;
    };

    const showErrors = (errors) => {
        errorBox.replaceChildren();
        errorBox.classList.toggle('d-none', !errors.length);

        if (!errors.length) {
            return;
        }

        const list = document.createElement('ul');
        list.className = 'mb-0';
        errors.forEach((message) => {
            const item = document.createElement('li');
            item.textContent = message;
            list.append(item);
        });
        errorBox.append(list);
    };

    form.elements.hpht.addEventListener('input', updatePregnancy);
    form.elements.tgl_reg.addEventListener('input', updatePregnancy);

    modal.addEventListener('show.bs.modal', (event) => {
        form.reset();

        const state = pendingState;
        pendingState = null;

        const source = event.relatedTarget?.dataset.patient;
        const data = state ? state.data : (source ? JSON.parse(source) : {});
        const editing = Number(data.id_kunjungan || 0) > 0;

        fields.forEach((field) => {
            const element = form.elements[field];

            // Faskes perujuk non-admin dikunci ke faskes user dan tidak boleh ditimpa data lama.
            if (element.dataset && element.dataset.locked === '1') {
                return;
            }

            element.value = data[field] ?? (field === 'id_kunjungan' ? '0' : '');
        });

        // Pasien baru umumnya didaftarkan pada hari yang sama.
        if (!editing && !state) {
            form.elements.tgl_reg.value = today();
        }

        updatePregnancy();
        document.getElementById('patientModalTitle').textContent = editing ? 'Edit pasien' : 'Tambah pasien';
        showErrors(state?.errors ?? []);
    });

    modal.addEventListener('shown.bs.modal', () => form.elements.no_rm.focus());

    if (pendingState) {
        bootstrap.Modal.getOrCreateInstance(modal).show();
    }
})();
