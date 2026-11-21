<?php

defined('BASEPATH') or exit('No direct script access allowed');

if (!function_exists('asset_url')) {
    /**
     * URL aset lokal dengan versi berdasarkan waktu ubah file.
     * Browser otomatis mengambil file baru setiap kali CSS/JS diperbarui,
     * sehingga HTML baru tidak tampil dengan CSS lama dari cache.
     */
    function asset_url($path)
    {
        $file = FCPATH . ltrim($path, '/');
        $url = base_url($path);

        return is_file($file) ? $url . '?v=' . filemtime($file) : $url;
    }
}
