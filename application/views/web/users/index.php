<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>
<div class="d-flex flex-wrap align-items-center justify-content-between gap-3 mb-4">
    <div>
        <div class="text-secondary small mb-2">Master data / Pengguna</div>
        <h1 class="h3 fw-bold mb-2">Pengguna</h1>
        <p class="text-secondary mb-0">Kelola akun, faskes, dan status pengguna EMNIRA.</p>
    </div>
    <button type="button" class="btn btn-primary px-4 py-2" data-bs-toggle="modal" data-bs-target="#userModal">+ Tambah pengguna</button>
</div>
<section class="card shadow-sm" aria-label="Daftar pengguna">
    <div class="card-body d-flex flex-wrap gap-3 align-items-center justify-content-between p-4">
        <div class="fw-semibold">Daftar pengguna <span class="badge text-bg-light border ms-2"><?= $total ?></span></div>
        <form method="get" action="<?= html_escape(site_url('web/users')) ?>" class="input-group search-input" role="search">
            <input type="search" class="form-control" name="q" value="<?= html_escape($search) ?>" placeholder="Cari nama atau username…" aria-label="Cari nama atau username" maxlength="255">
            <button class="btn btn-outline-secondary" type="submit">Cari</button>
            <?php if ($search !== ''): ?><a class="btn btn-outline-secondary" href="<?= html_escape(site_url('web/users')) ?>">Reset</a><?php endif; ?>
        </form>
    </div>
    <div class="table-responsive">
        <table class="table align-middle mb-0">
            <thead><tr><th scope="col">Pengguna</th><th scope="col">Username</th><th scope="col">Faskes</th><th scope="col">Role</th><th scope="col">Status</th><th scope="col" class="text-end">Tindakan</th></tr></thead>
            <tbody>
            <?php foreach ($users as $user): ?>
                <tr>
                    <td><div class="d-flex gap-3 align-items-center"><span class="user-avatar" aria-hidden="true"><?= html_escape(mb_strtoupper(mb_substr((string) $user['nama'], 0, 1))) ?></span><div class="user-name"><div class="fw-semibold"><?= html_escape($user['nama'] ?? '—') ?></div><small class="text-secondary">#<?= (int) $user['id_user'] ?></small></div></div></td>
                    <td class="user-name"><?= html_escape($user['username'] ?? '—') ?></td>
                    <td><?= html_escape(getFaskes($user['id_faskes'] ?? '—', 'nama_faskes')) ?></td>
                    <td><?= html_escape(getRole($user['id_role'] ?? '—', 'nama_role')) ?></td>
                    <td><span class="badge rounded-pill <?= (int) $user['id_active'] === 1 ? 'text-bg-success' : 'text-bg-secondary' ?>"><?= (int) $user['id_active'] === 1 ? 'Aktif' : 'Nonaktif' ?></span></td>
                    <td class="text-end text-nowrap">
                        <button class="btn btn-sm btn-outline-secondary" type="button" data-bs-toggle="modal" data-bs-target="#userModal" data-user="<?= html_escape(json_encode($user, JSON_INVALID_UTF8_SUBSTITUTE)) ?>" aria-label="Edit <?= html_escape($user['nama'] ?? '') ?>">Edit</button>
                        <?= form_open('web/users/delete/' . $user['id_user'], [
                            'class' => 'd-inline',
                            'data-confirm-title' => 'Hapus pengguna?',
                            'data-confirm' => html_escape('Pengguna ' . ($user['nama'] ?? '') . ' akan dihapus secara permanen. Tindakan ini tidak dapat dibatalkan.')
                        ]) ?>
                            <button class="btn btn-sm btn-outline-danger ms-1" type="submit" aria-label="Hapus <?= html_escape($user['nama'] ?? '') ?>">Hapus</button>
                        <?= form_close() ?>
                    </td>
                </tr>
            <?php endforeach; ?>
            <?php if (!$users): ?>
                <tr><td colspan="6" class="text-center py-5"><h2 class="h5">Belum ada pengguna<?= $search !== '' ? ' yang sesuai' : '' ?></h2><p class="text-secondary mb-0"><?= $search !== '' ? 'Coba kata pencarian lain.' : 'Klik Tambah pengguna untuk membuat akun pertama.' ?></p></td></tr>
            <?php endif; ?>
            </tbody>
        </table>
    </div>
    <div class="card-body d-flex flex-wrap justify-content-between align-items-center gap-3 px-4">
        <small class="text-secondary"><?= $total ? (($page - 1) * 15 + 1) : 0 ?>–<?= min($page * 15, $total) ?> dari <?= $total ?> pengguna</small>
        <nav class="d-flex align-items-center gap-2" aria-label="Halaman pengguna">
            <?php if ($page > 1): ?><a class="btn btn-sm btn-outline-secondary" href="<?= html_escape(site_url('web/users') . '?' . http_build_query(['q' => $search, 'page' => $page - 1])) ?>">Sebelumnya</a><?php endif; ?>
            <span class="small text-secondary">Halaman <?= $page ?> / <?= $pages ?></span>
            <?php if ($page < $pages): ?><a class="btn btn-sm btn-outline-secondary" href="<?= html_escape(site_url('web/users') . '?' . http_build_query(['q' => $search, 'page' => $page + 1])) ?>">Berikutnya</a><?php endif; ?>
        </nav>
    </div>
</section>

<div class="modal fade" id="userModal" tabindex="-1" aria-labelledby="userModalTitle" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable">
        <?= form_open('web/users/save', ['class' => 'modal-content', 'id' => 'userForm']) ?>
            <div class="modal-header"><h2 class="modal-title fs-5" id="userModalTitle">Tambah pengguna</h2><button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Tutup"></button></div>
            <div class="modal-body p-4">
                <div class="alert alert-danger d-none" id="userErrors" role="alert"></div>
                <input type="hidden" name="id_user" value="0">
                <div class="row g-3">
                    <div class="col-md-6"><label for="nama" class="form-label">Nama <span class="text-danger">*</span></label><input class="form-control" id="nama" name="nama" maxlength="255" required autocomplete="name"></div>
                    <div class="col-md-6"><label for="username" class="form-label">Username <span class="text-danger">*</span></label><input class="form-control" id="username" name="username" maxlength="255" required autocomplete="off"></div>
                    <div class="col-12"><label for="password" class="form-label">Password <span id="passwordRequired" class="text-danger">*</span></label><input type="password" class="form-control" id="password" name="password" maxlength="72" autocomplete="new-password" aria-describedby="passwordHelp" required><div class="form-text" id="passwordHelp">Wajib untuk pengguna baru. Maksimal 72 byte.</div></div>
                    <div class="col-md-6">
                        <label for="id_faskes" class="form-label">Fasilitas kesehatan</label>
                        <select class="form-select" id="id_faskes" name="id_faskes" aria-describedby="faskesHelp">
                            <option value="">Tanpa faskes</option>
                            <?php foreach ($faskes_options as $faskes): ?>
                                <option value="<?= (int) $faskes['id_faskes'] ?>"><?= html_escape(($faskes['nama_faskes'] ?: 'Faskes #' . $faskes['id_faskes']) . ($faskes['kategori'] ? ' (' . $faskes['kategori'] . ')' : '')) ?></option>
                            <?php endforeach; ?>
                        </select>
                        <div class="form-text" id="faskesHelp"><?= $faskes_options ? 'Opsional. Pilih fasilitas kesehatan pengguna.' : 'Belum ada fasilitas kesehatan pada data master.' ?></div>
                    </div>
                    <div class="col-md-6">
                        <label for="id_role" class="form-label">Role</label>
                        <select class="form-select" id="id_role" name="id_role" aria-describedby="roleHelp">
                            <option value="">Tanpa role</option>
                            <?php foreach ($roles as $role): ?>
                                <option value="<?= (int) $role['id_role'] ?>"><?= html_escape($role['nama_role'] ?: 'Role #' . $role['id_role']) ?></option>
                            <?php endforeach; ?>
                        </select>
                        <div class="form-text" id="roleHelp"><?= $roles ? 'Opsional. Pilih peran pengguna.' : 'Belum ada role pada data master.' ?></div>
                    </div>
                    <div class="col-md-6"><label for="id_active" class="form-label">Status <span class="text-danger">*</span></label><select class="form-select" id="id_active" name="id_active" required><option value="1">Aktif</option><option value="0">Nonaktif</option></select></div>
                </div>
            </div>
            <div class="modal-footer"><button type="button" class="btn btn-light border" data-bs-dismiss="modal">Batal</button><button type="submit" class="btn btn-primary">Simpan pengguna</button></div>
        <?= form_close() ?>
    </div>
</div>

<?php if ($form_state): ?>
    <script type="application/json" id="userFormState"><?= json_encode($form_state, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_INVALID_UTF8_SUBSTITUTE) ?></script>
<?php endif; ?>
