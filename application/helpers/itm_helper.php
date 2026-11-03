<?php

function getFaskes($kode, $strcol)
{
    $ci =   get_instance();
    $result = $ci->db->get_where('m_faskes', ['id_faskes' => $kode])->row_array();
    if ($result) {
        $response =  $result[$strcol];
    } else {
        $response =  "-";
    }
    return $response;
}

function getRole($kode, $strcol)
{
    $ci =   get_instance();
    $result = $ci->db->get_where('m_role', ['id_role' => $kode])->row_array();
    if ($result) {
        $response =  $result[$strcol];
    } else {
        $response =  "-";
    }
    return $response;
}

function get_client_ip()
{
    $ipaddress = '';
    if (isset($_SERVER['HTTP_CLIENT_IP'])) {
        $ipaddress = $_SERVER['HTTP_CLIENT_IP'];
    } else if (isset($_SERVER['HTTP_X_FORWARDED_FOR'])) {
        $ipaddress = $_SERVER['HTTP_X_FORWARDED_FOR'];
    } else if (isset($_SERVER['HTTP_X_FORWARDED'])) {
        $ipaddress = $_SERVER['HTTP_X_FORWARDED'];
    } else if (isset($_SERVER['HTTP_FORWARDED_FOR'])) {
        $ipaddress = $_SERVER['HTTP_FORWARDED_FOR'];
    } else if (isset($_SERVER['HTTP_FORWARDED'])) {
        $ipaddress = $_SERVER['HTTP_FORWARDED'];
    } else if (isset($_SERVER['REMOTE_ADDR'])) {
        $ipaddress = $_SERVER['REMOTE_ADDR'];
    } else {
        $ipaddress = 'UNKNOWN';
    }

    return $ipaddress;
}

function datediff($d1, $d2)
{
    $diff     = abs(strtotime($d2) - strtotime($d1));
    $a    = array();
    $a[]     = floor($diff / (365 * 60 * 60 * 24));
    $a[] = floor(($diff - $a[0] * 365 * 60 * 60 * 24) / (30 * 60 * 60 * 24));
    $a[]     = floor(($diff - $a[0] * 365 * 60 * 60 * 24 - $a[1] * 30 * 60 * 60 * 24) / (60 * 60 * 24));
    return $a;
}

function p($kode)
{
        $response =  print_r('<pre>'.json_encode($kode, JSON_PRETTY_PRINT).'<pre>').die();
        return $response;
}

function getHari($hariInggris) {
    switch ($hariInggris) {
      case 'Sun':
        return '7';
      case 'Mon':
        return '1';
      case 'Tue':
        return '2';
      case 'Wed':
        return '3';
      case 'Thu':
        return '4';
      case 'Fri':
        return '5';
      case 'Sat':
        return '6';
      default:
        return 'hari tidak valid';
    }
  }

function getTanggal($tanggal)
{
    $bulan = array(
        1 =>   'Januari',
        'Februari',
        'Maret',
        'April',
        'Mei',
        'Juni',
        'Juli',
        'Agustus',
        'September',
        'Oktober',
        'November',
        'Desember'
    );
    $split = explode('-', $tanggal);

    if($tanggal <> "0000-00-00"){
        $response =  $split[2] . ' ' . $bulan[(int)$split[1]] . ' ' . $split[0];
    }else if ($tanggal == "0000-00-00") {
        $response =  "-";
    } else {
        $response =  "-";
    }
    return $response;
}
