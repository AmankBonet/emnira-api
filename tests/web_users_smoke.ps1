param(
    [string]$BaseUrl = 'http://localhost/emnira-api'
)

$ErrorActionPreference = 'Stop'
$base = $BaseUrl.TrimEnd('/')
$session = New-Object Microsoft.PowerShell.Commands.WebRequestSession
$username = 'crud_test_' + [guid]::NewGuid().ToString('N')
$createdId = $null

function Assert-True($Condition, $Message) {
    if (-not $Condition) {
        throw $Message
    }
    Write-Output "PASS: $Message"
}

function Get-Page {
    return Invoke-WebRequest -Uri "$base/web/users?q=$username" -WebSession $session -UseBasicParsing
}

function Get-Token($Page) {
    $match = [regex]::Match($Page.Content, 'name="csrf_test_name" value="([^"]+)"')
    if (-not $match.Success) {
        throw 'Token CSRF tidak ditemukan.'
    }
    return $match.Groups[1].Value
}

function Get-TestUser($Page) {
    foreach ($match in [regex]::Matches($Page.Content, 'data-user="([^"]+)"')) {
        $user = [System.Net.WebUtility]::HtmlDecode($match.Groups[1].Value) | ConvertFrom-Json
        if ($user.username -eq $username) {
            return $user
        }
    }
    return $null
}

function Get-FirstOption($Page, $Field) {
    $select = [regex]::Match($Page.Content, '(?s)<select[^>]+id="' + $Field + '"[^>]*>(.*?)</select>')
    Assert-True $select.Success "Dropdown $Field tersedia"
    $option = [regex]::Match($select.Groups[1].Value, '<option value="([1-9][0-9]*)"')
    if (-not $option.Success) {
        throw "Data master $Field diperlukan untuk pengujian relasi."
    }
    return $option.Groups[1].Value
}

function Get-Status($Uri, $Method = 'GET', $Body = $null) {
    try {
        $response = Invoke-WebRequest -Uri $Uri -Method $Method -Body $Body -WebSession $session -UseBasicParsing
        return [int]$response.StatusCode
    } catch {
        if ($_.Exception.Response) {
            return [int]$_.Exception.Response.StatusCode
        }
        throw
    }
}

try {
    if (-not $env:EMNIRA_WEB_TEST_USERNAME -or -not $env:EMNIRA_WEB_TEST_PASSWORD) {
        throw 'Isi EMNIRA_WEB_TEST_USERNAME dan EMNIRA_WEB_TEST_PASSWORD dengan akun pengujian aktif.'
    }
    $loginPage = Invoke-WebRequest -Uri "$base/web/login" -WebSession $session -UseBasicParsing
    $null = Invoke-WebRequest -Uri "$base/web/login" -Method POST -WebSession $session -UseBasicParsing -Body @{
        csrf_test_name = Get-Token $loginPage
        username = $env:EMNIRA_WEB_TEST_USERNAME
        password = $env:EMNIRA_WEB_TEST_PASSWORD
    }
    $page = Get-Page
    Assert-True ($page.StatusCode -eq 200 -and $page.Content.Contains('id="userModal"')) 'Halaman dan modal tersedia'
    Assert-True (-not $page.Content.Contains('password_hash')) 'Hash tidak dikirim ke browser'
    Assert-True ((Get-Status "$base/web/users/save") -eq 405) 'GET tidak dapat menyimpan data'
    Assert-True ((Get-Status "$base/web/users/save" 'POST' @{id_user='0'}) -eq 403) 'POST tanpa CSRF ditolak'

    $roleId = @(Get-FirstOption $page 'id_role')[-1]
    $faskesId = @(Get-FirstOption $page 'id_faskes')[-1]
    $body = @{
        csrf_test_name = Get-Token $page
        id_user = '0'
        nama = 'Pengguna pengujian'
        username = $username
        password = 'Test-only-password-123!'
        id_faskes = $faskesId
        id_role = $roleId
        id_active = '1'
    }
    $page = Invoke-WebRequest -Uri "$base/web/users/save" -Method POST -Body $body -WebSession $session -UseBasicParsing
    $page = Get-Page
    $user = Get-TestUser $page
    Assert-True ($null -ne $user) 'Tambah pengguna berhasil'
    $createdId = $user.id_user
    Assert-True ($user.id_role -eq $roleId -and $user.id_faskes -eq $faskesId) 'Pilihan role dan faskes tersimpan'

    $body.csrf_test_name = Get-Token $page
    $page = Invoke-WebRequest -Uri "$base/web/users/save" -Method POST -Body $body -WebSession $session -UseBasicParsing
    Assert-True ($page.Content.Contains('Username sudah digunakan')) 'Username duplikat ditolak dan form dibuka kembali'
    Assert-True (-not $page.Content.Contains('Test-only-password-123!')) 'Password tidak diisi ulang pada error'

    $body.csrf_test_name = Get-Token $page
    $body.id_user = [string]$createdId
    $body.nama = '<script>alert("xss")</script>'
    $body.password = ''
    $body.id_active = '0'
    $page = Invoke-WebRequest -Uri "$base/web/users/save" -Method POST -Body $body -WebSession $session -UseBasicParsing
    $page = Get-Page
    $user = Get-TestUser $page
    Assert-True ($user.nama -eq $body.nama -and [int]$user.id_active -eq 0) 'Edit dengan password kosong berhasil'
    Assert-True (-not $page.Content.Contains($body.nama)) 'Konten HTML pengguna di-escape'

    $body.csrf_test_name = Get-Token $page
    $body.id_role = '2147483647'
    $body.id_faskes = '2147483647'
    $page = Invoke-WebRequest -Uri "$base/web/users/save" -Method POST -Body $body -WebSession $session -UseBasicParsing
    Assert-True ($page.Content.Contains('Role yang dipilih tidak tersedia') -and $page.Content.Contains('Faskes yang dipilih tidak tersedia')) 'Referensi master tidak valid ditolak'
    $page = Get-Page
    $user = Get-TestUser $page
    Assert-True ($user.id_role -eq $roleId -and $user.id_faskes -eq $faskesId) 'Referensi lama tetap utuh setelah validasi gagal'

    $body.csrf_test_name = Get-Token $page
    $body.id_role = ''
    $body.id_faskes = ''
    $page = Invoke-WebRequest -Uri "$base/web/users/save" -Method POST -Body $body -WebSession $session -UseBasicParsing
    $page = Get-Page
    $user = Get-TestUser $page
    Assert-True ($null -eq $user.id_role -and $null -eq $user.id_faskes) 'Pilihan kosong disimpan sebagai NULL'

    Assert-True ((Get-Status "$base/web/users/delete/$createdId") -eq 405) 'GET tidak dapat menghapus data'
    $body.csrf_test_name = Get-Token $page
    $body.id_active = '2'
    $page = Invoke-WebRequest -Uri "$base/web/users/save" -Method POST -Body $body -WebSession $session -UseBasicParsing
    Assert-True ($page.Content.Contains('id="userFormState"')) 'Status tidak valid ditolak'
} finally {
    # Hanya hapus fixture milik pengujian ini, tanpa menyentuh akun lain.
    $page = Get-Page
    $user = Get-TestUser $page
    if ($null -ne $user) {
        $null = Invoke-WebRequest -Uri "$base/web/users/delete/$($user.id_user)" -Method POST -Body @{csrf_test_name=(Get-Token $page)} -WebSession $session -UseBasicParsing
        $page = Get-Page
        Assert-True ($null -eq (Get-TestUser $page)) 'Hapus pengguna berhasil; fixture dibersihkan'
    }
}
