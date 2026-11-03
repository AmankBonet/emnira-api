'use strict';

(() => {
    const modal = document.getElementById('userModal');
    const form = document.getElementById('userForm');
    const errorBox = document.getElementById('userErrors');
    const stateElement = document.getElementById('userFormState');
    let pendingState = stateElement ? JSON.parse(stateElement.textContent) : null;

    modal.addEventListener('show.bs.modal', (event) => {
        form.reset();
        const state = pendingState;
        pendingState = null;
        const data = state ? state.data : (event.relatedTarget?.dataset.user ? JSON.parse(event.relatedTarget.dataset.user) : {});
        const editing = Number(data.id_user || 0) > 0;
        for (const field of ['id_user', 'nama', 'username', 'id_faskes', 'id_role', 'id_active']) {
            form.elements[field].value = data[field] ?? (field === 'id_user' ? '0' : (field === 'id_active' ? '1' : ''));
        }
        form.elements.password.value = '';
        form.elements.password.required = !editing;
        document.getElementById('userModalTitle').textContent = editing ? 'Edit pengguna' : 'Tambah pengguna';
        document.getElementById('passwordRequired').hidden = editing;
        document.getElementById('passwordHelp').textContent = editing
            ? 'Kosongkan untuk mempertahankan password lama. Maksimal 72 byte.'
            : 'Wajib untuk pengguna baru. Maksimal 72 byte.';
        errorBox.replaceChildren();
        errorBox.classList.toggle('d-none', !state?.errors?.length);
        if (state?.errors?.length) {
            const list = document.createElement('ul');
            list.className = 'mb-0';
            state.errors.forEach((message) => {
                const item = document.createElement('li');
                item.textContent = message;
                list.append(item);
            });
            errorBox.append(list);
        }
    });

    modal.addEventListener('shown.bs.modal', () => form.elements.nama.focus());
    modal.addEventListener('hidden.bs.modal', () => { form.elements.password.value = ''; });

    if (pendingState) {
        bootstrap.Modal.getOrCreateInstance(modal).show();
    }
})();
