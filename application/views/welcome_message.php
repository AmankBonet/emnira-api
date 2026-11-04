<?php
defined('BASEPATH') or exit('No direct script access allowed');

$base_url_clean = rtrim(base_url(), '/');
$rujukan_fields_path = APPPATH . 'config/rujukan_fields.json';
$rujukan_fields = [];

if (is_file($rujukan_fields_path))
{
    $decoded_fields = json_decode(file_get_contents($rujukan_fields_path), true);

    if (is_array($decoded_fields))
    {
        $rujukan_fields = $decoded_fields;
    }
}

/**
 * Menerjemahkan definisi tipe SQL menjadi label tipe dan batasan nilai yang mudah dibaca.
 */
function format_rujukan_field_rule($field, $raw_type)
{
    $required = in_array($field, ['id_kunjungan', 'id_faskes_tujuan'], true);
    $type_label = $raw_type;
    $constraint = 'Opsional (boleh bernilai null)';

    if (strpos($raw_type, 'enum(') === 0)
    {
        preg_match_all("/'([^']*)'/", $raw_type, $matches);
        $type_label = 'enum (string)';
        $options = isset($matches[1]) ? implode(', ', array_map(function ($item)
        {
            return '"' . $item . '"';
        }, $matches[1])) : '';
        $constraint = 'Pilihan nilai: ' . $options;
    }
    elseif (in_array($raw_type, ['int', 'tinyint', 'smallint', 'bigint'], true))
    {
        $type_label = 'integer (' . $raw_type . ')';

        if ($field === 'bishop_station')
        {
            $constraint = 'Rentang bilangan bulat: -128 s/d 127';
        }
        elseif ($field === 'bishop_penipisan_persen')
        {
            $constraint = 'Rentang bilangan bulat: 0 s/d 100 (%)';
        }
        elseif ($raw_type === 'tinyint')
        {
            $constraint = 'Flag boolean integer: 0 atau 1';
        }
        elseif (strpos($field, 'id_') === 0)
        {
            $constraint = 'ID referensi valid (minimal >= 1)';
        }
        elseif ($raw_type === 'smallint')
        {
            $constraint = 'Rentang bilangan bulat: 0 s/d 65535';
        }
        else
        {
            $constraint = 'Bilangan bulat >= 0';
        }
    }
    elseif (strpos($raw_type, 'decimal(') === 0)
    {
        preg_match('/decimal\((\d+),(\d+)\)/', $raw_type, $matches);
        $precision = isset($matches[1]) ? (int) $matches[1] : 5;
        $scale = isset($matches[2]) ? (int) $matches[2] : 2;
        $type_label = 'number / float (' . $raw_type . ')';
        $notes = ['Maksimal ' . $scale . ' angka desimal'];

        if (strpos($field, 'persen') !== false)
        {
            $notes[] = 'Rentang 0 s/d 100';
        }
        elseif (in_array($field, ['berat_badan_kg', 'durasi_pengamatan_urin_jam'], true))
        {
            $notes[] = 'Harus > 0';
        }
        else
        {
            $notes[] = 'Nilai >= 0 (kurang dari 10^' . ($precision - $scale) . ')';
        }

        $constraint = implode(', ', $notes);
    }
    elseif ($raw_type === 'datetime')
    {
        $type_label = 'string (datetime)';
        $constraint = 'Format "Y-m-d H:i:s" (otomatis terisi waktu server Asia/Jakarta jika kosong)';
    }
    elseif ($raw_type === 'text')
    {
        $type_label = 'string (text)';
        $constraint = 'Teks bebas maksimal 65.535 karakter';
    }
    elseif (strpos($raw_type, 'varchar(') === 0)
    {
        $max_len = (int) filter_var($raw_type, FILTER_SANITIZE_NUMBER_INT);
        $type_label = 'string (' . $raw_type . ')';
        $constraint = 'Teks maksimal ' . $max_len . ' karakter';
    }

    return [
        'required' => $required,
        'type_label' => $type_label,
        'constraint' => $constraint
    ];
}
?><!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="description" content="Dokumentasi lengkap REST API Layanan Integrasi Faskes & Rujukan PONEK (EMNIRA API).">
    <title>Dokumentasi API &mdash; EMNIRA Layanan Faskes</title>
    <style>
        :root {
            color-scheme: light;
            --bg: #f4f7f9;
            --surface: #ffffff;
            --surface-alt: #f8fafc;
            --border: #dce5ec;
            --border-strong: #c5d3de;
            --text: #15293b;
            --text-muted: #526579;
            --primary: #086f72;
            --primary-dark: #065658;
            --primary-soft: #e6f4f4;
            --code-bg: #0f1e2e;
            --code-text: #e2ecf5;
            --get-bg: #e0f2fe;
            --get-text: #0369a1;
            --post-bg: #dcfce7;
            --post-text: #15803d;
            --warn-bg: #fef3c7;
            --warn-text: #92400e;
            --err-bg: #fee2e2;
            --err-text: #b91c1c;
            --radius-lg: 18px;
            --radius-md: 12px;
            --radius-sm: 8px;
        }

        * {
            box-sizing: border-box;
        }

        html {
            scroll-behavior: smooth;
        }

        body {
            margin: 0;
            background: var(--bg);
            color: var(--text);
            font-family: system-ui, -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;
            line-height: 1.6;
            font-size: 15px;
        }

        /* Top Bar */
        .topbar {
            position: sticky;
            top: 0;
            z-index: 30;
            background: rgba(255, 255, 255, 0.92);
            backdrop-filter: blur(10px);
            border-bottom: 1px solid var(--border);
        }

        .topbar-inner {
            max-width: 1320px;
            margin: 0 auto;
            padding: 14px 24px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 16px;
            flex-wrap: wrap;
        }

        .brand {
            display: flex;
            align-items: center;
            gap: 12px;
            text-decoration: none;
            color: var(--text);
            font-weight: 700;
            font-size: 16px;
        }

        .brand-mark {
            display: grid;
            place-items: center;
            width: 38px;
            height: 38px;
            background: var(--primary);
            color: #fff;
            border-radius: 10px;
            font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, monospace;
            font-size: 15px;
        }

        .brand-subtitle {
            display: block;
            font-size: 12px;
            font-weight: 500;
            color: var(--text-muted);
        }

        .base-url-bar {
            display: flex;
            align-items: center;
            gap: 10px;
            background: var(--surface-alt);
            border: 1px solid var(--border);
            padding: 6px 10px 6px 14px;
            border-radius: var(--radius-sm);
            font-size: 13px;
        }

        .base-url-label {
            font-weight: 700;
            color: var(--primary);
            text-transform: uppercase;
            letter-spacing: 0.05em;
            font-size: 11px;
        }

        .base-url-value {
            font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, monospace;
            color: var(--text);
            word-break: break-all;
        }

        .btn-copy {
            appearance: none;
            border: 1px solid var(--border-strong);
            background: var(--surface);
            color: var(--text);
            border-radius: 6px;
            padding: 4px 10px;
            font-size: 12px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.15s ease;
        }

        .btn-copy:hover {
            background: var(--primary);
            color: #fff;
            border-color: var(--primary);
        }

        /* Layout */
        .layout {
            max-width: 1320px;
            margin: 0 auto;
            padding: 28px 24px 64px;
            display: grid;
            grid-template-columns: 270px minmax(0, 1fr);
            gap: 32px;
            align-items: start;
        }

        /* Sidebar Navigation */
        .sidebar {
            position: sticky;
            top: 84px;
            background: var(--surface);
            border: 1px solid var(--border);
            border-radius: var(--radius-lg);
            padding: 20px 16px;
            max-height: calc(100vh - 108px);
            overflow-y: auto;
        }

        .nav-group {
            margin-bottom: 20px;
        }

        .nav-group:last-child {
            margin-bottom: 0;
        }

        .nav-group-title {
            font-size: 11px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.08em;
            color: var(--text-muted);
            padding: 0 10px;
            margin: 0 0 8px;
        }

        .nav-list {
            list-style: none;
            margin: 0;
            padding: 0;
            display: flex;
            flex-direction: column;
            gap: 3px;
        }

        .nav-link {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 8px;
            padding: 7px 10px;
            border-radius: var(--radius-sm);
            color: var(--text);
            text-decoration: none;
            font-size: 13.5px;
            font-weight: 500;
            transition: background 0.15s ease, color 0.15s ease;
        }

        .nav-link:hover {
            background: var(--primary-soft);
            color: var(--primary-dark);
        }

        .method-pill {
            display: inline-block;
            padding: 2px 7px;
            border-radius: 5px;
            font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, monospace;
            font-size: 10.5px;
            font-weight: 700;
            letter-spacing: 0.04em;
            text-transform: uppercase;
            flex-shrink: 0;
        }

        .method-get {
            background: var(--get-bg);
            color: var(--get-text);
        }

        .method-post {
            background: var(--post-bg);
            color: var(--post-text);
        }

        /* Main Content */
        .content {
            display: flex;
            flex-direction: column;
            gap: 28px;
        }

        .section-card {
            background: var(--surface);
            border: 1px solid var(--border);
            border-radius: var(--radius-lg);
            padding: 32px;
            box-shadow: 0 8px 24px rgba(21, 41, 59, 0.03);
            scroll-margin-top: 90px;
        }

        .badge {
            display: inline-block;
            padding: 4px 10px;
            border-radius: 6px;
            background: var(--primary-soft);
            color: var(--primary-dark);
            font-size: 11.5px;
            font-weight: 700;
            letter-spacing: 0.06em;
            text-transform: uppercase;
        }

        h1 {
            font-size: clamp(26px, 3.5vw, 34px);
            line-height: 1.25;
            letter-spacing: -0.02em;
            margin: 12px 0 12px;
        }

        h2 {
            font-size: 20px;
            margin: 0 0 12px;
            letter-spacing: -0.01em;
        }

        h3 {
            font-size: 15px;
            margin: 22px 0 10px;
            color: var(--text);
        }

        p {
            margin: 0 0 12px;
            color: var(--text-muted);
        }

        p:last-child {
            margin-bottom: 0;
        }

        .grid-2 {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(260px, 1fr));
            gap: 16px;
            margin-top: 16px;
        }

        .info-box {
            background: var(--surface-alt);
            border: 1px solid var(--border);
            border-radius: var(--radius-md);
            padding: 16px 18px;
        }

        .info-box strong {
            display: block;
            color: var(--text);
            margin-bottom: 4px;
            font-size: 14px;
        }

        .alert {
            padding: 14px 18px;
            border-radius: var(--radius-md);
            border-left: 4px solid var(--primary);
            background: var(--primary-soft);
            color: var(--text);
            font-size: 14px;
            margin: 16px 0;
        }

        .alert-warn {
            border-left-color: #d97706;
            background: var(--warn-bg);
            color: var(--warn-text);
        }

        /* Endpoint Header */
        .endpoint-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 12px;
            flex-wrap: wrap;
            padding-bottom: 16px;
            border-bottom: 1px solid var(--border);
            margin-bottom: 16px;
        }

        .endpoint-title-wrap {
            display: flex;
            align-items: center;
            gap: 12px;
            flex-wrap: wrap;
        }

        .endpoint-title {
            margin: 0;
            font-size: 19px;
        }

        .auth-badge {
            font-size: 12px;
            font-weight: 600;
            padding: 4px 10px;
            border-radius: 999px;
            background: #f1f5f9;
            color: #475569;
            border: 1px solid var(--border);
        }

        .auth-badge.protected {
            background: #fef3c7;
            color: #92400e;
            border-color: #fde68a;
        }

        .endpoint-bar {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 12px;
            background: var(--surface-alt);
            border: 1px solid var(--border);
            border-radius: var(--radius-md);
            padding: 10px 14px;
            margin-bottom: 18px;
            flex-wrap: wrap;
        }

        .endpoint-path {
            display: flex;
            align-items: center;
            gap: 10px;
            font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, monospace;
            font-size: 13.5px;
            color: var(--text);
            word-break: break-all;
        }

        .endpoint-path .method-pill {
            font-size: 12px;
            padding: 4px 10px;
        }

        /* Code Blocks */
        .code-wrap {
            position: relative;
            margin: 10px 0 18px;
            border-radius: var(--radius-md);
            overflow: hidden;
            background: var(--code-bg);
            border: 1px solid #1e3246;
        }

        .code-head {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 8px 14px;
            background: #16293d;
            border-bottom: 1px solid #223952;
            color: #9fb3c8;
            font-size: 12px;
            font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, monospace;
        }

        .code-head .btn-copy {
            background: #223952;
            color: #e2ecf5;
            border-color: #334e68;
            padding: 3px 9px;
            font-size: 11px;
        }

        .code-head .btn-copy:hover {
            background: var(--primary);
            border-color: var(--primary);
        }

        pre {
            margin: 0;
            padding: 16px;
            overflow-x: auto;
            color: var(--code-text);
            font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, monospace;
            font-size: 13px;
            line-height: 1.55;
        }

        code {
            font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, monospace;
            font-size: 0.9em;
        }

        p code, li code, td code {
            background: #eef3f7;
            color: #0f3e52;
            padding: 2px 6px;
            border-radius: 5px;
        }

        /* Tables */
        .table-wrap {
            overflow-x: auto;
            border: 1px solid var(--border);
            border-radius: var(--radius-md);
            margin: 12px 0 18px;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            text-align: left;
            font-size: 13.5px;
        }

        th {
            background: var(--surface-alt);
            color: var(--text);
            font-weight: 700;
            padding: 10px 14px;
            border-bottom: 1px solid var(--border);
            white-space: nowrap;
        }

        td {
            padding: 10px 14px;
            border-bottom: 1px solid var(--border);
            color: var(--text-muted);
            vertical-align: top;
        }

        tr:last-child td {
            border-bottom: none;
        }

        .req-tag {
            display: inline-block;
            padding: 2px 6px;
            border-radius: 4px;
            font-size: 11px;
            font-weight: 700;
            background: var(--err-bg);
            color: var(--err-text);
        }

        .opt-tag {
            display: inline-block;
            padding: 2px 6px;
            border-radius: 4px;
            font-size: 11px;
            font-weight: 600;
            background: #f1f5f9;
            color: #64748b;
        }

        .filter-input {
            width: 100%;
            max-width: 360px;
            padding: 9px 14px;
            border: 1px solid var(--border-strong);
            border-radius: var(--radius-sm);
            font-size: 14px;
            margin-bottom: 12px;
        }

        footer {
            max-width: 1320px;
            margin: 0 auto;
            padding: 0 24px 40px;
            color: var(--text-muted);
            font-size: 13px;
            text-align: center;
        }

        @media (max-width: 960px) {
            .layout {
                grid-template-columns: 1fr;
            }
            .sidebar {
                position: static;
                max-height: none;
            }
            .section-card {
                padding: 22px;
            }
        }
    </style>
</head>
<body>

    <header class="topbar">
        <div class="topbar-inner">
            <a href="#ikhtisar" class="brand">
                <span class="brand-mark" aria-hidden="true">&lt;/&gt;</span>
                <span>
                    EMNIRA REST API
                    <span class="brand-subtitle">Dokumentasi Integrasi Layanan Faskes &amp; Rujukan PONEK</span>
                </span>
            </a>
            <div class="base-url-bar">
                <span class="base-url-label">Base URL</span>
                <span class="base-url-value" id="base-url-text"><?= htmlspecialchars($base_url_clean, ENT_QUOTES, 'UTF-8') ?></span>
                <button type="button" class="btn-copy" data-copy-target="base-url-text">Salin</button>
            </div>
        </div>
    </header>

    <div class="layout">
        <!-- Navigasi Samping -->
        <aside class="sidebar" aria-label="Daftar Isi Dokumentasi">
            <div class="nav-group">
                <p class="nav-group-title">Panduan Umum</p>
                <ul class="nav-list">
                    <li><a class="nav-link" href="#ikhtisar">Ikhtisar &amp; Format Respons</a></li>
                    <li><a class="nav-link" href="#autentikasi">Skema Autentikasi JWT</a></li>
                    <li><a class="nav-link" href="#kode-status">Kode Status HTTP</a></li>
                </ul>
            </div>

            <div class="nav-group">
                <p class="nav-group-title">1. Autentikasi (Auth)</p>
                <ul class="nav-list">
                    <li>
                        <a class="nav-link" href="#auth-login">
                            <span>Login Pengguna</span>
                            <span class="method-pill method-post">POST</span>
                        </a>
                    </li>
                    <li>
                        <a class="nav-link" href="#auth-refresh">
                            <span>Refresh Token</span>
                            <span class="method-pill method-post">POST</span>
                        </a>
                    </li>
                    <li>
                        <a class="nav-link" href="#auth-me">
                            <span>Profil Aktif (Me)</span>
                            <span class="method-pill method-get">GET</span>
                        </a>
                    </li>
                    <li>
                        <a class="nav-link" href="#auth-logout">
                            <span>Logout Sesi</span>
                            <span class="method-pill method-post">POST</span>
                        </a>
                    </li>
                    <li>
                        <a class="nav-link" href="#auth-push-token">
                            <span>Expo Push Token</span>
                            <span class="method-pill method-post">CRUD</span>
                        </a>
                    </li>
                </ul>
            </div>

            <div class="nav-group">
                <p class="nav-group-title">2. Master Data</p>
                <ul class="nav-list">
                    <li>
                        <a class="nav-link" href="#master-faskes">
                            <span>Daftar Faskes</span>
                            <span class="method-pill method-get">GET</span>
                        </a>
                    </li>
                </ul>
            </div>

            <div class="nav-group">
                <p class="nav-group-title">3. Transaksi Rujukan</p>
                <ul class="nav-list">
                    <li>
                        <a class="nav-link" href="#rujukan-create">
                            <span>Simpan Rujukan Baru</span>
                            <span class="method-pill method-post">POST</span>
                        </a>
                    </li>
                    <li>
                        <a class="nav-link" href="#rujukan-skema">
                            <span>Skema Kolom Rujukan</span>
                        </a>
                    </li>
                </ul>
            </div>

            <div class="nav-group">
                <p class="nav-group-title">4. Layanan Umum</p>
                <ul class="nav-list">
                    <li>
                        <a class="nav-link" href="#api-simpan">
                            <span>Simpan Data (Legacy)</span>
                            <span class="method-pill method-post">POST</span>
                        </a>
                    </li>
                </ul>
            </div>
        </aside>

        <!-- Konten Utama -->
        <main class="content">

            <!-- IKHTISAR -->
            <section id="ikhtisar" class="section-card">
                <span class="badge">Dokumentasi Teknis v1.0</span>
                <h1>API Layanan Integrasi Faskes &amp; Rujukan</h1>
                <p>
                    Layanan ini dibangun menggunakan <strong>CodeIgniter 3</strong> dan <strong>RestController</strong> untuk memfasilitasi autentikasi pengguna fasilitas kesehatan, pertukaran data master faskes (FKTP/FKRTL), serta pencatatan rujukan medis maternal/neonatal (PONEK).
                </p>

                <div class="grid-2">
                    <div class="info-box">
                        <strong>Format Pertukaran Data</strong>
                        <p>Seluruh request body dan response menggunakan format <code>application/json</code> dengan encoding UTF-8.</p>
                    </div>
                    <div class="info-box">
                        <strong>Zona Waktu Server</strong>
                        <p>Waktu transaksi dicatat menggunakan zona waktu <code>Asia/Jakarta</code> (WIB / UTC+7) dengan format <code>Y-m-d H:i:s</code>.</p>
                    </div>
                </div>

                <h3>Struktur Standar JSON Response</h3>
                <p>Setiap endpoint mengembalikan objek <code>metadata</code> (berisi kode HTTP dan pesan status) serta objek <code>response</code> (berisi payload data apabila tersedia).</p>
                <div class="code-wrap">
                    <div class="code-head">
                        <span>JSON &mdash; Format Umum Respons</span>
                    </div>
<pre><code>{
  "metadata": {
    "code": 200,
    "message": "Ok"
  },
  "response": { ... }
}</code></pre>
                </div>
            </section>

            <!-- AUTENTIKASI & STATUS -->
            <section id="autentikasi" class="section-card">
                <h2>Skema Autentikasi JWT (Bearer Token)</h2>
                <p>
                    Sebagian besar endpoint dilindungi oleh fungsi helper <code>validate_custom_token()</code> yang memverifikasi tanda tangan <strong>JSON Web Token (HS256)</strong> sekaligus memastikan sesi pada tabel <code>auth_sessions</code> masih aktif (belum di-revoke dan belum kedaluwarsa).
                </p>

                <div class="table-wrap">
                    <table>
                        <thead>
                            <tr>
                                <th>Header</th>
                                <th>Format Nilai</th>
                                <th>Keterangan</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td><code>Authorization</code></td>
                                <td><code>Bearer &lt;access_token&gt;</code></td>
                                <td><strong>Direkomendasikan.</strong> Gunakan <code>access_token</code> yang diperoleh dari endpoint <code>POST /auth/login</code>.Masa berlaku token adalah <strong>600 detik (10 menit)</strong>.</td>
                            </tr>
                            <tr>
                                <td><code>x-token</code></td>
                                <td><code>&lt;access_token&gt;</code></td>
                                <td>Header alternatif untuk kompatibilitas aplikasi klien versi lama.</td>
                            </tr>
                            <tr>
                                <td><code>Content-Type</code></td>
                                <td><code>application/json</code></td>
                                <td>Wajib disertakan pada setiap request dengan method <code>POST</code> yang mengirimkan JSON body.</td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <h3 id="kode-status">Kode Status HTTP</h3>
                <div class="table-wrap">
                    <table>
                        <thead>
                            <tr>
                                <th>Kode</th>
                                <th>Status</th>
                                <th>Penjelasan</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td><code>200</code></td>
                                <td>OK</td>
                                <td>Permintaan berhasil diproses.</td>
                            </tr>
                            <tr>
                                <td><code>201</code></td>
                                <td>Created</td>
                                <td>Data transaksi baru (seperti rujukan) berhasil disimpan ke database.</td>
                            </tr>
                            <tr>
                                <td><code>400</code></td>
                                <td>Bad Request</td>
                                <td>Format JSON tidak valid atau parameter wajib (seperti username/password/refresh_token) kosong.</td>
                            </tr>
                            <tr>
                                <td><code>401</code></td>
                                <td>Unauthorized</td>
                                <td>Kredensial salah, token JWT tidak valid/kedaluwarsa, atau sesi telah berakhir (logout).</td>
                            </tr>
                            <tr>
                                <td><code>403</code></td>
                                <td>Forbidden</td>
                                <td>Token valid, tetapi akun tidak memiliki hak akses (misal: faskes asal bukan kategori <code>FKTP</code> atau kunjungan bukan milik faskes pengirim).</td>
                            </tr>
                            <tr>
                                <td><code>422</code></td>
                                <td>Unprocessable Entity</td>
                                <td>Validasi aturan bisnis atau tipe data kolom gagal (detail kesalahan dikembalikan pada <code>response.errors</code>).</td>
                            </tr>
                            <tr>
                                <td><code>500 / 503</code></td>
                                <td>Server Error</td>
                                <td>Kegagalan transaksi database atau konfigurasi kepemilikan kolom belum lengkap.</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </section>

            <!-- 1. AUTH: LOGIN -->
            <section id="auth-login" class="section-card">
                <div class="endpoint-header">
                    <div class="endpoint-title-wrap">
                        <span class="method-pill method-post">POST</span>
                        <h2 class="endpoint-title">Login Pengguna</h2>
                    </div>
                    <span class="auth-badge">Publik (Tanpa Token)</span>
                </div>

                <p>Memvalidasi kredensial pengguna pada tabel <code>m_user</code> (status <code>id_active = 1</code>), mencatat sesi baru pada <code>auth_sessions</code> (berlaku 7 hari), dan menerbitkan pasangan <code>access_token</code> serta <code>refresh_token</code>.</p>

                <div class="endpoint-bar">
                    <div class="endpoint-path">
                        <span class="method-pill method-post">POST</span>
                        <span id="url-auth-login"><?= htmlspecialchars($base_url_clean . '/auth/login', ENT_QUOTES, 'UTF-8') ?></span>
                    </div>
                    <button type="button" class="btn-copy" data-copy-target="url-auth-login">Salin URL</button>
                </div>

                <h3>Request Body (JSON)</h3>
                <div class="code-wrap">
                    <div class="code-head">
                        <span>application/json</span>
                        <button type="button" class="btn-copy" data-copy-target="body-auth-login">Salin JSON</button>
                    </div>
<pre><code id="body-auth-login">{
  "username": "kiki",
  "password": "enak"
}</code></pre>
                </div>

                <h3>Contoh Respons Sukses (200 OK)</h3>
                <div class="code-wrap">
                    <div class="code-head">
                        <span>200 OK</span>
                    </div>
<pre><code>{
  "metadata": {
    "code": 200,
    "message": "Login berhasil."
  },
  "response": {
    "access_token": "eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9...",
    "token_type": "Bearer",
    "expires_in": 600,
    "refresh_token": "9f86d081884c7d659a2feaa0c55ad015a3bf4f1b2b0b822cd15d6c15b0f00a08",
    "user": {
      "id_user": "2",
      "nama": "RIZKI AMALIA",
      "username": "kiki",
      "faskes": "Puskesmas Kecamatan A",
      "role": "Bidan",
      "status": "aktif"
    }
  }
}</code></pre>
                </div>
            </section>

            <!-- 2. AUTH: REFRESH -->
            <section id="auth-refresh" class="section-card">
                <div class="endpoint-header">
                    <div class="endpoint-title-wrap">
                        <span class="method-pill method-post">POST</span>
                        <h2 class="endpoint-title">Perbarui Token (Refresh Token)</h2>
                    </div>
                    <span class="auth-badge">Publik (Memakai Refresh Token)</span>
                </div>

                <p>Memperbarui <code>access_token</code> yang telah kedaluwarsa menggunakan <code>refresh_token</code>. Setiap kali berhasil dipakai, <code>refresh_token</code> lama otomatis dirotasi (tidak bisa digunakan dua kali).</p>

                <div class="endpoint-bar">
                    <div class="endpoint-path">
                        <span class="method-pill method-post">POST</span>
                        <span id="url-auth-refresh"><?= htmlspecialchars($base_url_clean . '/auth/refresh', ENT_QUOTES, 'UTF-8') ?></span>
                    </div>
                    <button type="button" class="btn-copy" data-copy-target="url-auth-refresh">Salin URL</button>
                </div>

                <h3>Request Body (JSON)</h3>
                <div class="code-wrap">
                    <div class="code-head">
                        <span>application/json</span>
                        <button type="button" class="btn-copy" data-copy-target="body-auth-refresh">Salin JSON</button>
                    </div>
<pre><code id="body-auth-refresh">{
  "refresh_token": "9f86d081884c7d659a2feaa0c55ad015a3bf4f1b2b0b822cd15d6c15b0f00a08"
}</code></pre>
                </div>

                <h3>Contoh Respons Sukses (200 OK)</h3>
                <div class="code-wrap">
                    <div class="code-head">
                        <span>200 OK</span>
                    </div>
<pre><code>{
  "metadata": {
    "code": 200,
    "message": "Token diperbarui."
  },
  "response": {
    "access_token": "eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9...",
    "token_type": "Bearer",
    "expires_in": 600,
    "refresh_token": "4b227777d4dd1fc61c6f884f48641d02b4d121d3fd328cb08b5531fcacdabf8a",
    "user": {
      "id_user": "2",
      "nama": "RIZKI AMALIA",
      "username": "kiki",
      "faskes": "Puskesmas Kecamatan A",
      "role": "Bidan",
      "status": "aktif"
    }
  }
}</code></pre>
                </div>
            </section>

            <!-- 3. AUTH: ME -->
            <section id="auth-me" class="section-card">
                <div class="endpoint-header">
                    <div class="endpoint-title-wrap">
                        <span class="method-pill method-get">GET</span>
                        <h2 class="endpoint-title">Profil Pengguna Aktif (Me)</h2>
                    </div>
                    <span class="auth-badge protected">Wajib Bearer Token</span>
                </div>

                <p>Mengambil informasi profil pengguna yang sedang login berdasarkan klaim token JWT dan status sesi aktif di database.</p>

                <div class="endpoint-bar">
                    <div class="endpoint-path">
                        <span class="method-pill method-get">GET</span>
                        <span id="url-auth-me"><?= htmlspecialchars($base_url_clean . '/auth/me', ENT_QUOTES, 'UTF-8') ?></span>
                    </div>
                    <button type="button" class="btn-copy" data-copy-target="url-auth-me">Salin URL</button>
                </div>

                <h3>Contoh Respons Sukses (200 OK)</h3>
                <div class="code-wrap">
                    <div class="code-head">
                        <span>200 OK</span>
                    </div>
<pre><code>{
  "metadata": {
    "code": 200,
    "message": "Ok"
  },
  "response": {
    "user": {
      "id_user": "2",
      "nama": "RIZKI AMALIA",
      "username": "kiki",
      "faskes": "Puskesmas Kecamatan A",
      "role": "Bidan",
      "status": "aktif"
    }
  }
}</code></pre>
                </div>
            </section>

            <!-- 4. AUTH: LOGOUT -->
            <section id="auth-logout" class="section-card">
                <div class="endpoint-header">
                    <div class="endpoint-title-wrap">
                        <span class="method-pill method-post">POST</span>
                        <h2 class="endpoint-title">Logout Sesi</h2>
                    </div>
                    <span class="auth-badge protected">Wajib Bearer Token</span>
                </div>

                <p>Mengakhiri sesi aktif pengguna dengan mengisi kolom <code>revoked_at</code> pada tabel <code>auth_sessions</code>. Token yang terhubung dengan sesi ini tidak dapat digunakan lagi.</p>

                <div class="endpoint-bar">
                    <div class="endpoint-path">
                        <span class="method-pill method-post">POST</span>
                        <span id="url-auth-logout"><?= htmlspecialchars($base_url_clean . '/auth/logout', ENT_QUOTES, 'UTF-8') ?></span>
                    </div>
                    <button type="button" class="btn-copy" data-copy-target="url-auth-logout">Salin URL</button>
                </div>

                <h3>Contoh Respons Sukses (200 OK)</h3>
                <div class="code-wrap">
                    <div class="code-head">
                        <span>200 OK</span>
                    </div>
<pre><code>{
  "metadata": {
    "code": 200,
    "message": "Logout berhasil."
  }
}</code></pre>
                </div>
            </section>

            <!-- 4B. AUTH: EXPO PUSH TOKEN CRUD -->
            <section id="auth-push-token" class="section-card">
                <div class="endpoint-header">
                    <div class="endpoint-title-wrap">
                        <span class="method-pill method-post">POST / PUT</span>
                        <span class="method-pill method-get">GET</span>
                        <span class="method-pill" style="background:#fee2e2;color:#b91c1c;">DELETE</span>
                        <h2 class="endpoint-title">CRUD Register Token Expo Push Notification</h2>
                    </div>
                    <span class="auth-badge protected">Wajib Bearer Token</span>
                </div>

                <p>Mengelola penyimpanan, pembacaan, pembaruan, dan penghapusan kolom <code>token_expo_push</code> pada tabel <code>m_user</code> untuk akun yang sedang login.</p>

                <div class="endpoint-bar">
                    <div class="endpoint-path">
                        <span class="method-pill method-post">POST / PUT / GET / DELETE</span>
                        <span id="url-auth-push-token"><?= htmlspecialchars($base_url_clean . '/auth/push_token', ENT_QUOTES, 'UTF-8') ?></span>
                    </div>
                    <button type="button" class="btn-copy" data-copy-target="url-auth-push-token">Salin URL</button>
                </div>

                <h3>1. Register / Update Token (<code>POST</code> atau <code>PUT</code>)</h3>
                <div class="code-wrap">
                    <div class="code-head">
                        <span>Request Body &mdash; application/json</span>
                        <button type="button" class="btn-copy" data-copy-target="body-push-token">Salin JSON</button>
                    </div>
<pre><code id="body-push-token">{
  "token_expo_push": "ExponentPushToken[xxxxxxxxxxxxxxxxxxxxxx]"
}</code></pre>
                </div>

                <div class="code-wrap">
                    <div class="code-head">
                        <span>Respons Sukses (200 OK)</span>
                    </div>
<pre><code>{
  "metadata": {
    "code": 200,
    "message": "Token push notification berhasil disimpan."
  },
  "response": {
    "id_user": "2",
    "token_expo_push": "ExponentPushToken[xxxxxxxxxxxxxxxxxxxxxx]"
  }
}</code></pre>
                </div>

                <h3>2. Ambil Token Saat Ini (<code>GET</code>)</h3>
                <div class="code-wrap">
                    <div class="code-head">
                        <span>Respons Sukses (200 OK)</span>
                    </div>
<pre><code>{
  "metadata": {
    "code": 200,
    "message": "Ok"
  },
  "response": {
    "id_user": "2",
    "token_expo_push": "ExponentPushToken[xxxxxxxxxxxxxxxxxxxxxx]"
  }
}</code></pre>
                </div>

                <h3>3. Hapus Token (<code>DELETE</code>)</h3>
                <div class="code-wrap">
                    <div class="code-head">
                        <span>Respons Sukses (200 OK)</span>
                    </div>
<pre><code>{
  "metadata": {
    "code": 200,
    "message": "Token push notification berhasil dihapus."
  },
  "response": {
    "id_user": "2",
    "token_expo_push": null
  }
}</code></pre>
                </div>
            </section>

            <!-- 5. MASTER: FASKES -->
            <section id="master-faskes" class="section-card">
                <div class="endpoint-header">
                    <div class="endpoint-title-wrap">
                        <span class="method-pill method-get">GET</span>
                        <h2 class="endpoint-title">Daftar Master Faskes</h2>
                    </div>
                    <span class="auth-badge protected">Wajib Bearer Token</span>
                </div>

                <p>Mengambil seluruh daftar fasilitas kesehatan dari tabel <code>m_faskes</code> (baik kategori <code>FKTP</code> maupun rumah sakit rujukan <code>FKRTL</code>).</p>

                <div class="endpoint-bar">
                    <div class="endpoint-path">
                        <span class="method-pill method-get">GET</span>
                        <span id="url-master-faskes"><?= htmlspecialchars($base_url_clean . '/master/faskes', ENT_QUOTES, 'UTF-8') ?></span>
                    </div>
                    <button type="button" class="btn-copy" data-copy-target="url-master-faskes">Salin URL</button>
                </div>

                <h3>Contoh Respons Sukses (200 OK)</h3>
                <div class="code-wrap">
                    <div class="code-head">
                        <span>200 OK</span>
                    </div>
<pre><code>{
  "response": [
    {
      "id_faskes": "1",
      "nama_faskes": "Puskesmas Kecamatan A",
      "kategori": "FKTP"
    },
    {
      "id_faskes": "2",
      "nama_faskes": "RSUD Kabupaten B",
      "kategori": "FKRTL"
    }
  ],
  "metadata": {
    "message": "Ok",
    "code": 200
  }
}</code></pre>
                </div>
            </section>

            <!-- 6. RUJUKAN: CREATE -->
            <section id="rujukan-create" class="section-card">
                <div class="endpoint-header">
                    <div class="endpoint-title-wrap">
                        <span class="method-pill method-post">POST</span>
                        <h2 class="endpoint-title">Simpan Rujukan Baru</h2>
                    </div>
                    <span class="auth-badge protected">Wajib Bearer Token (Akun FKTP)</span>
                </div>

                <p>Mencatat transaksi rujukan rekam medis baru ke tabel <code>t_rm</code> beserta daftar kode pilihan (checkbox) ke tabel <code>t_rm_pilihan</code> dalam satu transaksi database atomik.</p>

                <div class="endpoint-bar">
                    <div class="endpoint-path">
                        <span class="method-pill method-post">POST</span>
                        <span id="url-rujukan"><?= htmlspecialchars($base_url_clean . '/rujukan', ENT_QUOTES, 'UTF-8') ?></span>
                    </div>
                    <button type="button" class="btn-copy" data-copy-target="url-rujukan">Salin URL</button>
                </div>

                <div class="alert alert-warn">
                    <strong>Aturan Bisnis &amp; Validasi Khusus Rujukan:</strong>
                    <ul style="margin: 8px 0 0; padding-left: 20px;">
                        <li>Akun pengirim harus berasal dari faskes dengan kategori <code>FKTP</code>.</li>
                        <li><code>id_kunjungan</code> harus terdaftar di <code>t_kunjungan</code> dan dimiliki oleh faskes pengirim (kolom <code>id_faskes</code>).</li>
                        <li><code>id_faskes_tujuan</code> harus terdaftar di <code>m_faskes</code> dengan kategori <code>FKRTL</code> dan berbeda dari faskes asal.</li>
                        <li>Jika <code>perdarahan_status</code> bernilai <code>"tidak_ada"</code>, maka kolom <code>perdarahan_volume</code> dan <code>perdarahan_karakteristik</code> tidak boleh diisi.</li>
                        <li>Jika array <code>pilihan</code> memuat kode <code>"riwayat_tidak_ada"</code>, maka tidak boleh digabung dengan kode <code>riwayat_*</code> lainnya.</li>
                        <li>Jika memilih kode checkbox dengan awalan <code>bahaya_*</code>, <code>riwayat_*</code>, atau <code>terapi_*</code>, maka flag penilaian terkait (<code>skrining_bahaya_sudah_dinilai</code>, <code>riwayat_medis_sudah_dinilai</code>, atau <code>terapi_sudah_dinilai</code>) wajib bernilai <code>1</code>.</li>
                        <li><strong>Push Notification Otomatis:</strong> Setelah rujukan berhasil disimpan, server otomatis mengirimkan Expo Push Notification (channel <code>emnira_custom_sound</code>, prioritas <code>high</code>) ke seluruh akun petugas aktif (<code>id_active = 1</code>) di <code>id_faskes_tujuan</code> yang memiliki <code>token_expo_push</code> terdaftar.</li>
                    </ul>
                </div>

                <h3>Contoh Request Body Lengkap (JSON)</h3>
                <div class="code-wrap">
                    <div class="code-head">
                        <span>application/json</span>
                        <button type="button" class="btn-copy" data-copy-target="body-rujukan">Salin JSON</button>
                    </div>
<pre><code id="body-rujukan">{
  "id_kunjungan": 1,
  "id_faskes_tujuan": 2,
  "id_diagnosis": 1,
  "waktu_rujukan": "2026-11-04 09:00:00",
  "skrining_bahaya_sudah_dinilai": 1,
  "perdarahan_status": "aktif",
  "perdarahan_volume": "lt_500",
  "perdarahan_karakteristik": "darah_segar",
  "tfu_cm": 32.5,
  "kepala_masuk_panggul": 1,
  "djj_per_menit": 142,
  "djj_irama": "teratur",
  "his_frekuensi": "3",
  "his_durasi": "20_40",
  "vt_pembukaan": "4_7",
  "ketuban": "utuh",
  "penurunan_kepala": "hodge_1_2",
  "bishop_dilatasi_cm": 3.5,
  "bishop_penipisan_persen": 60,
  "bishop_station": 0,
  "bishop_konsistensi": "lunak",
  "bishop_posisi": "anterior",
  "riwayat_medis_sudah_dinilai": 1,
  "alergi_obat_detail": "Alergi obat Amoxicillin",
  "riwayat_sc": "1_kali",
  "jarak_sc_tahun": 2.5,
  "operasi_ginekologi": 1,
  "operasi_ginekologi_detail": "Kistektomi ovarium kiri tahun 2022",
  "td_sistolik_mmhg": 120,
  "td_diastolik_mmhg": 80,
  "nadi_per_menit": 84,
  "rr_per_menit": 20,
  "suhu_celsius": 36.7,
  "spo2_persen": 98.0,
  "kateter_urin_terpasang": 1,
  "berat_badan_kg": 60.0,
  "produksi_urin_total_ml": 200.0,
  "durasi_pengamatan_urin_jam": 2.0,
  "urin_output_tercatat_ml_jam": 100.0,
  "proteinuria": "negatif",
  "hematuria": 0,
  "oksigenasi": "nasal_kanul",
  "oksigen_aliran_lpm": 3.0,
  "oksigen_konsentrasi_persen": 32.0,
  "akses_iv": "single_line_18_20",
  "jenis_cairan": "rl",
  "cairan_total_ml": 500.0,
  "tetesan_mode": "maintenance",
  "tetesan_per_menit": 20,
  "terapi_sudah_dinilai": 1,
  "pilihan": [
    "riwayat_tidak_ada"
  ]
}</code></pre>
                </div>

                <h3>Contoh Respons Sukses (201 Created)</h3>
                <div class="code-wrap">
                    <div class="code-head">
                        <span>201 Created</span>
                    </div>
<pre><code>{
  "metadata": {
    "code": 201,
    "message": "Rujukan berhasil disimpan."
  },
  "response": {
    "id_rm": 15
  }
}</code></pre>
                </div>

                <h3>Contoh Respons Validasi Gagal (422 Unprocessable Entity)</h3>
                <div class="code-wrap">
                    <div class="code-head">
                        <span>422 Unprocessable Entity</span>
                    </div>
<pre><code>{
  "metadata": {
    "code": 422,
    "message": "Validasi gagal."
  },
  "response": {
    "errors": {
      "id_faskes_tujuan": "Wajib diisi.",
      "perdarahan_status": "Pilihan tidak valid."
    }
  }
}</code></pre>
                </div>

                <!-- SKEMA KOLOM RUJUKAN -->
                <h3 id="rujukan-skema">Referensi Skema Kolom Input Rujukan (<?= count($rujukan_fields) + 1 ?> Parameter)</h3>
                <p>Hanya kolom yang terdaftar pada <code>application/config/rujukan_fields.json</code> (ditambah array <code>pilihan</code>) yang diterima oleh server. Mengirimkan nama properti di luar daftar ini akan ditolak dengan kode <code>422</code>.</p>

                <input type="search" id="field-search" class="filter-input" placeholder="Cari nama kolom rujukan (misal: bishop, urin, perdarahan)...">

                <div class="table-wrap">
                    <table id="rujukan-table">
                        <thead>
                            <tr>
                                <th>Nama Properti JSON</th>
                                <th>Status</th>
                                <th>Tipe Data</th>
                                <th>Aturan &amp; Batasan Nilai</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($rujukan_fields as $field_name => $raw_type): ?>
                                <?php $rule_info = format_rujukan_field_rule($field_name, $raw_type); ?>
                                <tr>
                                    <td><code><?= htmlspecialchars($field_name, ENT_QUOTES, 'UTF-8') ?></code></td>
                                    <td>
                                        <?php if ($rule_info['required']): ?>
                                            <span class="req-tag">Wajib</span>
                                        <?php else: ?>
                                            <span class="opt-tag">Opsional</span>
                                        <?php endif; ?>
                                    </td>
                                    <td><?= htmlspecialchars($rule_info['type_label'], ENT_QUOTES, 'UTF-8') ?></td>
                                    <td><?= htmlspecialchars($rule_info['constraint'], ENT_QUOTES, 'UTF-8') ?></td>
                                </tr>
                            <?php endforeach; ?>
                            <tr>
                                <td><code>pilihan</code></td>
                                <td><span class="opt-tag">Opsional</span></td>
                                <td>array of string</td>
                                <td>Daftar kode checkbox unik (maks 60 karakter per kode) yang terdaftar pada tabel <code>m_rm_pilihan</code>.</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </section>

            <!-- 7. API UMUM: SIMPAN -->
            <section id="api-simpan" class="section-card">
                <div class="endpoint-header">
                    <div class="endpoint-title-wrap">
                        <span class="method-pill method-post">POST</span>
                        <h2 class="endpoint-title">Simpan Data Klinik (Legacy Endpoint)</h2>
                    </div>
                    <span class="auth-badge protected">Wajib Header Token</span>
                </div>

                <p>Endpoint kompatibilitas pada controller <code>Api.php</code> yang memvalidasi kecocokan <code>x-username</code> dengan klaim pengguna pada JWT token.</p>

                <div class="endpoint-bar">
                    <div class="endpoint-path">
                        <span class="method-pill method-post">POST</span>
                        <span id="url-api-simpan"><?= htmlspecialchars($base_url_clean . '/api/simpan', ENT_QUOTES, 'UTF-8') ?></span>
                    </div>
                    <button type="button" class="btn-copy" data-copy-target="url-api-simpan">Salin URL</button>
                </div>

                <div class="table-wrap">
                    <table>
                        <thead>
                            <tr>
                                <th>Header</th>
                                <th>Contoh Nilai</th>
                                <th>Keterangan</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td><code>x-username</code></td>
                                <td><code>admin_fktp</code></td>
                                <td>Harus sama dengan klaim <code>username</code> di dalam token JWT.</td>
                            </tr>
                            <tr>
                                <td><code>x-token</code> / <code>Authorization</code></td>
                                <td><code>Bearer &lt;access_token&gt;</code></td>
                                <td>Token JWT aktif hasil login.</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </section>

        </main>
    </div>

    <footer>
        Dokumentasi Layanan Integrasi EMNIRA API &middot; Environment: <?= htmlspecialchars(ENVIRONMENT, ENT_QUOTES, 'UTF-8') ?> &middot; CodeIgniter <?= htmlspecialchars(CI_VERSION, ENT_QUOTES, 'UTF-8') ?>
    </footer>

    <script>
        // Fitur salin teks ke clipboard untuk URL dan potongan JSON
        document.querySelectorAll('[data-copy-target]').forEach(function (button) {
            button.addEventListener('click', function () {
                var targetId = button.getAttribute('data-copy-target');
                var targetEl = document.getElementById(targetId);

                if (!targetEl) {
                    return;
                }

                var text = targetEl.innerText || targetEl.textContent;
                var originalLabel = button.textContent;

                if (navigator.clipboard && navigator.clipboard.writeText) {
                    navigator.clipboard.writeText(text).then(function () {
                        button.textContent = 'Tersalin!';
                        setTimeout(function () {
                            button.textContent = originalLabel;
                        }, 1600);
                    });
                }
            });
        });

        // Fitur pencarian cepat pada tabel parameter rujukan
        var searchInput = document.getElementById('field-search');
        var tableRows = document.querySelectorAll('#rujukan-table tbody tr');

        if (searchInput) {
            searchInput.addEventListener('input', function () {
                var keyword = searchInput.value.toLowerCase().trim();

                tableRows.forEach(function (row) {
                    var content = row.textContent.toLowerCase();
                    row.style.display = content.indexOf(keyword) !== -1 ? '' : 'none';
                });
            });
        }
    </script>
</body>
</html>
