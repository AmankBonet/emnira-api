<?php

// Jalankan hanya pada database pengembangan; fixture dibersihkan dalam finally.
define('BASEPATH', dirname(__DIR__));
define('ENVIRONMENT', 'development');
require dirname(__DIR__) . '/application/config/database.php';
require dirname(__DIR__) . '/vendor/autoload.php';

$config = $db[$active_group];
$connection = new mysqli(
    $config['hostname'],
    $config['username'],
    $config['password'],
    $config['database'],
    isset($config['port']) ? (int) $config['port'] : 3306
);
$username = 'web_auth_test_' . bin2hex(random_bytes(8));
$password = 'test-' . bin2hex(random_bytes(16));
$statement = $connection->prepare('INSERT INTO m_user (nama, username, password_hash, id_active) VALUES (?, ?, ?, 1)');
$statement->bind_param('sss', $username, $username, $password);
$statement->execute();
$id = $connection->insert_id;
$client = new GuzzleHttp\Client([
    'base_uri' => 'http://localhost/emnira-api/',
    'cookies' => true,
    'http_errors' => false,
    'allow_redirects' => false
]);

function check_web($condition, $label)
{
    if (!$condition) {
        throw new RuntimeException($label);
    }
    echo 'PASS: ', $label, PHP_EOL;
}

function csrf_web($client, $path)
{
    $response = $client->get($path);
    preg_match('/name="csrf_test_name" value="([^"]+)"/', (string) $response->getBody(), $matches);
    if (!isset($matches[1])) {
        throw new RuntimeException('Token CSRF tidak ditemukan.');
    }
    return $matches[1];
}

try {
    check_web($client->get('web/users')->getStatusCode() === 307, 'Halaman pengguna memerlukan login');
    $token = csrf_web($client, 'web/login');
    check_web($client->post('web/login', ['form_params' => ['username' => $username, 'password' => $password]])->getStatusCode() === 403, 'Login tanpa CSRF ditolak');
    $form = ['username' => $username, 'password' => 'salah', 'csrf_test_name' => $token];
    check_web($client->post('web/login', ['form_params' => $form])->getStatusCode() === 401, 'Password salah ditolak');
    $form['password'] = $password;
    check_web($client->post('web/login', ['form_params' => $form])->getStatusCode() === 303, 'Password akun lama diterima');
    $page = $client->get('web/users');
    check_web($page->getStatusCode() === 200 && strpos((string) $page->getBody(), 'Keluar') !== false, 'Halaman terlindungi dan tombol keluar tersedia');
    check_web($client->get('web/login/logout')->getStatusCode() === 405, 'Logout GET ditolak');
    check_web($client->post('web/login/logout')->getStatusCode() === 403, 'Logout tanpa CSRF ditolak');
    $token = csrf_web($client, 'web/users');
    check_web($client->post('web/login/logout', ['form_params' => ['csrf_test_name' => $token]])->getStatusCode() === 303, 'Logout POST berhasil');
    check_web($client->get('web/users')->getStatusCode() === 307, 'Session tidak dapat mengakses web setelah logout');

    $hash = password_hash($password, PASSWORD_DEFAULT);
    $statement = $connection->prepare('UPDATE m_user SET password_hash = ? WHERE id_user = ?');
    $statement->bind_param('si', $hash, $id);
    $statement->execute();
    $form['csrf_test_name'] = csrf_web($client, 'web/login');
    check_web($client->post('web/login', ['form_params' => $form])->getStatusCode() === 303, 'Password hash diterima');
    check_web($client->get('web/users')->getStatusCode() === 200, 'Session hash dapat mengakses pengguna');
    $connection->query('UPDATE m_user SET id_active = 0 WHERE id_user = ' . (int) $id);
    check_web($client->get('web/users')->getStatusCode() === 307, 'Akun dinonaktifkan kehilangan akses');
    $form['csrf_test_name'] = csrf_web($client, 'web/login');
    check_web($client->post('web/login', ['form_params' => $form])->getStatusCode() === 401, 'Login akun nonaktif ditolak');

    $connection->query('UPDATE m_user SET id_active = 1 WHERE id_user = ' . (int) $id);
    putenv('EMNIRA_WEB_TEST_USERNAME=' . $username);
    putenv('EMNIRA_WEB_TEST_PASSWORD=' . $password);
    passthru('powershell -NoProfile -ExecutionPolicy Bypass -File tests/web_users_smoke.ps1', $exitCode);
    check_web($exitCode === 0, 'CRUD tetap berjalan setelah login');
} finally {
    putenv('EMNIRA_WEB_TEST_USERNAME');
    putenv('EMNIRA_WEB_TEST_PASSWORD');
    $statement = $connection->prepare('DELETE FROM m_user WHERE id_user = ? AND username = ?');
    $statement->bind_param('is', $id, $username);
    $statement->execute();
    echo 'Fixture login dibersihkan.', PHP_EOL;
}
