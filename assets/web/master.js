'use strict';

(() => {
    const modal = document.getElementById('masterModal');
    const form = document.getElementById('masterForm');
    const errorBox = document.getElementById('masterErrors');
    const stateElement = document.getElementById('masterFormState');
    const pk = form.dataset.pk;
    const pkManual = form.dataset.pkManual === '1';
    let pendingState = stateElement ? JSON.parse(stateElement.textContent) : null;

    const fields = [...form.querySelectorAll('[name]')].filter((element) => !['pk_lama', 'csrf_test_name'].includes(element.name)
        && element.type !== 'hidden');

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

    modal.addEventListener('show.bs.modal', (event) => {
        form.reset();

        const state = pendingState;
        pendingState = null;

        const source = event.relatedTarget?.dataset.row;
        const row = state ? state.data : (source ? JSON.parse(source) : null);
        const pkLama = state ? state.pk_lama : (row ? String(row[pk] ?? '') : '');
        const editing = pkLama !== '';

        form.elements.pk_lama.value = pkLama;

        fields.forEach((element) => {
            // Kunci manual yang tidak dikirim saat edit diambil dari pk_lama.
            let value = row && element.name in row ? row[element.name] : (element.dataset.bawaan ?? '');
            if (pkManual && element.name === pk && editing) {
                value = pkLama;
            }

            if (element.type === 'checkbox') {
                element.checked = String(value ?? '') === '1';
            } else {
                element.value = value ?? '';
            }
        });

        // Kunci manual dipakai sebagai referensi di tabel lain, jadi tidak dapat diubah setelah dibuat.
        const pkField = form.querySelector('[data-pk-field]');
        if (pkField) {
            pkField.readOnly = editing;
            pkField.required = !editing;
        }

        document.getElementById('masterModalTitle').textContent = `${editing ? 'Edit' : 'Tambah'} ${form.dataset.judul.toLowerCase()}`;
        showErrors(state?.errors ?? []);
    });

    if (pendingState) {
        bootstrap.Modal.getOrCreateInstance(modal).show();
    }
})();
