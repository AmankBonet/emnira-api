<?php

defined('BASEPATH') or exit('No direct script access allowed');

/**
 * Hak akses rekam medis rujukan berdasarkan hubungan faskes user dengan kunjungan.
 *
 * - Faskes perujuk: mengisi selama draf, mengirim, dan menambah observasi selama pasien belum tiba.
 * - Faskes tujuan: melihat setelah rujukan dikirim, menerima, dan mencatat kedatangan.
 * - Administrator (role ADMINISTRATOR) dapat mengakses semua kunjungan.
 * - User non-admin tanpa faskes tidak dapat mengakses kunjungan apa pun.
 * - Faskes lain tidak dapat melihat kunjungan sama sekali.
 */
class Akses_rujukan
{
    // ID pada tabel m_role (lihat database/admin.sql untuk role administrator).
    const ROLE_FKTP = 1;
    const ROLE_FKRTL = 2;
    const ROLE_ADMIN = 3;

    private $user;

    public function __construct($user = [])
    {
        $this->user = is_array($user) ? $user : [];
    }

    /**
     * Administrator ditentukan dari role, bukan dari faskes kosong, agar user yang
     * lupa diisi faskesnya tidak otomatis mendapat akses penuh.
     */
    public function is_admin()
    {
        return isset($this->user['id_role']) && (int) $this->user['id_role'] === self::ROLE_ADMIN;
    }

    /**
     * Menu FKTP: entri rekam medis dan pengiriman rujukan.
     */
    public function is_fktp()
    {
        return $this->is_admin() || (int) $this->user['id_role'] === self::ROLE_FKTP;
    }

    /**
     * Menu FKRTL / rumah sakit: rujukan masuk, terima, dan catat kedatangan.
     */
    public function is_fkrtl()
    {
        return $this->is_admin() || (int) $this->user['id_role'] === self::ROLE_FKRTL;
    }

    /**
     * Halaman pertama setelah login sesuai peran.
     */
    public function beranda()
    {
        if ($this->is_admin()) {
            return 'web/master';
        }

        if ((int) $this->user['id_role'] === self::ROLE_FKTP) {
            return 'web/rm';
        }

        return (int) $this->user['id_role'] === self::ROLE_FKRTL ? 'web/rujukan' : 'web/users';
    }

    /**
     * ID faskes user; null untuk administrator (tanpa filter faskes).
     */
    public function faskes_id()
    {
        return $this->is_admin() ? null : (int) $this->user['id_faskes'];
    }

    /**
     * Peran user terhadap kunjungan: admin, perujuk, tujuan, atau null bila tidak berhak.
     */
    public function peran(array $kunjungan)
    {
        if ($this->is_admin()) {
            return 'admin';
        }

        $faskes = $this->faskes_id();

        // Tanpa faskes, (int) NULL = 0 akan cocok dengan kunjungan yang faskesnya kosong.
        if ($faskes === 0) {
            return null;
        }

        if ((int) $kunjungan['id_faskes_perujuk'] === $faskes) {
            return 'perujuk';
        }

        // Faskes tujuan baru melihat data setelah perujuk selesai menyiapkan dan mengirim.
        if ((int) $kunjungan['id_faskes_tujuan'] === $faskes && $kunjungan['status_rujukan'] !== 'draf') {
            return 'tujuan';
        }

        return null;
    }

    public function can_view(array $kunjungan)
    {
        return $this->peran($kunjungan) !== null;
    }

    public function can_edit(array $kunjungan)
    {
        return $this->is_pengisi($kunjungan) && $kunjungan['status_rujukan'] === 'draf';
    }

    /**
     * Observasi tetap boleh ditambah perujuk selama perjalanan sampai pasien tiba.
     */
    public function can_add_observasi(array $kunjungan)
    {
        return $this->is_pengisi($kunjungan)
            && in_array($kunjungan['status_rujukan'], ['draf', 'dikirim', 'diterima'], true);
    }

    public function can_send(array $kunjungan)
    {
        return $this->is_pengisi($kunjungan) && $kunjungan['status_rujukan'] === 'draf';
    }

    /**
     * Pengiriman hanya dapat dibatalkan sebelum faskes tujuan menerima.
     */
    public function can_cancel_send(array $kunjungan)
    {
        return $this->is_pengisi($kunjungan) && $kunjungan['status_rujukan'] === 'dikirim';
    }

    public function can_receive(array $kunjungan)
    {
        return $this->is_penerima($kunjungan) && $kunjungan['status_rujukan'] === 'dikirim';
    }

    public function can_record_arrival(array $kunjungan)
    {
        return $this->is_penerima($kunjungan) && $kunjungan['status_rujukan'] === 'diterima';
    }

    private function is_pengisi(array $kunjungan)
    {
        return in_array($this->peran($kunjungan), ['admin', 'perujuk'], true);
    }

    private function is_penerima(array $kunjungan)
    {
        return in_array($this->peran($kunjungan), ['admin', 'tujuan'], true);
    }
}
