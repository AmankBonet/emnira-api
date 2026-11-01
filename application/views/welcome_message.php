<?php
defined('BASEPATH') OR exit('No direct script access allowed');
?><!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="description" content="Layanan API untuk integrasi sistem fasilitas kesehatan. Akses layanan memerlukan autentikasi dan izin dari administrator.">
    <title>API Layanan Faskes</title>
    <style>
        :root { color-scheme: light; }
        * { box-sizing: border-box; }
        body { margin: 0; background: #f3f7fa; color: #172c40; font-family: system-ui, -apple-system, BlinkMacSystemFont, "Segoe UI", sans-serif; line-height: 1.65; }
        main { max-width: 960px; margin: 0 auto; padding: 64px 24px 32px; }
        .brand { display: flex; align-items: center; gap: 12px; margin-bottom: 32px; font-weight: 700; }
        .mark { display: grid; place-items: center; width: 44px; height: 44px; background: #086f72; color: #fff; border-radius: 12px; font-family: monospace; font-size: 18px; }
        .card { background: #fff; border: 1px solid #dce5ec; border-radius: 24px; overflow: hidden; box-shadow: 0 16px 48px #172c4008; }
        .intro { padding: 48px; border-bottom: 1px solid #e4ebf0; }
        .label { display: inline-block; padding: 5px 12px; border-radius: 6px; background: #e7f4f3; color: #075e61; font-size: 12px; font-weight: 700; letter-spacing: .08em; }
        h1 { font-size: clamp(30px, 5vw, 46px); line-height: 1.2; letter-spacing: -.035em; margin: 20px 0; max-width: 640px; }
        .lead { max-width: 660px; margin: 0; color: #526579; font-size: 18px; }
        .details { display: grid; grid-template-columns: 1fr 1fr; gap: 32px; padding: 32px 48px; }
        h2 { font-size: 17px; margin: 0 0 8px; }
        p { margin: 0; }
        .details p { color: #526579; font-size: 15px; }
        .access { margin: 0 48px 40px; padding: 24px; border-left: 3px solid #087f83; background: #f1f8f8; border-radius: 0 12px 12px 0; }
        .access p { color: #405b66; font-size: 15px; }
        footer { padding: 24px 0; color: #5c6d7b; font-size: 13px; text-align: center; }
        @media (max-width: 600px) {
            main { padding: 28px 16px 16px; }
            .brand { margin-bottom: 24px; }
            .intro { padding: 28px 24px; }
            .lead { font-size: 16px; }
            .details { grid-template-columns: 1fr; padding: 24px; gap: 24px; }
            .access { margin: 0 24px 28px; padding: 20px; }
        }
    </style>
</head>
<body>
    <main>
        <header class="brand">
            <span class="mark" aria-hidden="true">&lt;/&gt;</span>
            <span>API Layanan Faskes</span>
        </header>
        <article class="card" aria-labelledby="page-title">
            <div class="intro">
                <span class="label">LAYANAN INTEGRASI</span>
                <h1 id="page-title">Menghubungkan sistem layanan kesehatan.</h1>
                <p class="lead">Domain ini menyediakan API untuk pertukaran data antar aplikasi fasilitas kesehatan. Penggunaan layanan dilakukan melalui aplikasi yang telah memiliki izin akses.</p>
            </div>
            <div class="details">
                <section aria-labelledby="service-title">
                    <h2 id="service-title">Tentang layanan</h2>
                    <p>Mendukung integrasi data master faskes, kepesertaan, pendaftaran, dan antrean pasien sesuai hak akses yang diberikan.</p>
                </section>
                <section aria-labelledby="auth-title">
                    <h2 id="auth-title">Akses terautentikasi</h2>
                    <p>Permintaan ke endpoint layanan memerlukan kredensial atau token autentikasi yang valid. Respons API disampaikan dalam format JSON.</p>
                </section>
            </div>
            <section class="access" aria-labelledby="access-title">
                <h2 id="access-title">Ingin menghubungkan aplikasi?</h2>
                <p>Hubungi administrator pengelola API untuk mendapatkan izin akses, dokumentasi integrasi, dan informasi endpoint yang tersedia.</p>
            </section>
        </article>
        <footer>Halaman informasi API &middot; Untuk pendaftaran atau bantuan layanan kesehatan, hubungi faskes terkait.</footer>
    </main>
</body>
</html>
