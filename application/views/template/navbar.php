<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>
<nav class="navbar bg-white border-bottom px-3 px-lg-4 py-3" aria-label="Navigasi atas">
    <div class="d-flex align-items-center gap-3">
        <button class="btn btn-outline-secondary d-lg-none" type="button" data-bs-toggle="offcanvas" data-bs-target="#sidebar" aria-controls="sidebar" aria-label="Buka menu">☰</button>
        <span class="navbar-text text-dark fw-medium"><?= html_escape($page_title) ?></span>
    </div>
    <span class="badge rounded-pill text-bg-light border"><?= html_escape($web_user['nama'] ?: $web_user['username']) ?></span>
</nav>
