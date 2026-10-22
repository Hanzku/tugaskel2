<?php
declare(strict_types=1);

ini_set('display_errors', '0');
session_start([
    'cookie_httponly' => true,
    'cookie_samesite' => 'Lax',
    'cookie_secure' => !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off',
    'use_strict_mode' => true,
]);
require_once __DIR__ . '/validation.php';

set_exception_handler(static function (Throwable $error): void {
    error_log((string) $error);
    http_response_code(500);
    echo '<!doctype html><html lang="id"><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>Gangguan aplikasi</title><body><h1>Aplikasi belum dapat digunakan</h1><p>Periksa konfigurasi dan koneksi database. Detail kesalahan dicatat di log server.</p><a href="index.php">Kembali ke dashboard</a></body></html>';
});

function environment_value(string $key): string|false
{
    $value = getenv($key);
    if ($value !== false && $value !== '') {
        return $value;
    }
    return isset($_ENV[$key]) && $_ENV[$key] !== '' ? (string) $_ENV[$key] : false;
}

function resolve_project_path(string $path): string
{
    $trimmed = trim($path);
    if ($trimmed === '') {
        return '';
    }
    if ($trimmed[0] === '/' || $trimmed[0] === '\\' || preg_match('/^[A-Za-z]:[\\\\\/]/', $trimmed) === 1) {
        return $trimmed;
    }
    return __DIR__ . '/../' . ltrim($trimmed, './\\');
}

function ca_bundle_candidates(string $configured): array
{
    $directory = __DIR__ . '/../config';
    $candidates = [];
    if (trim($configured) !== '') {
        $candidates[] = resolve_project_path($configured);
    }
    foreach (scandir($directory) ?: [] as $entry) {
        if ($entry === '.' || $entry === '..') {
            continue;
        }
        if (preg_match('/\.(?:pem|crt)$/i', $entry) === 1) {
            $candidates[] = $directory . '/' . $entry;
        }
    }
    return array_values(array_unique($candidates));
}

function resolve_ca_bundle(string $configured): string
{
    foreach (ca_bundle_candidates($configured) as $candidate) {
        if ($candidate !== '' && is_file($candidate) && is_readable($candidate)) {
            return $candidate;
        }
    }
    return '';
}

function log_database_host_resolution(string $host): void
{
    if (!function_exists('gethostbynamel')) {
        error_log(sprintf('[database] resolusi DNS tidak tersedia untuk memeriksa host %s.', $host));
        return;
    }
    $addresses = gethostbynamel($host);
    if (is_array($addresses) && $addresses !== []) {
        error_log(sprintf('[database] host %s berhasil diresolusi (%d alamat).', $host, count($addresses)));
        return;
    }
    error_log(sprintf('[database] host %s gagal diresolusi. Periksa status layanan database dan nilai DB_HOST.', $host));
}

function log_database_failure(string $stage, Throwable $error): void
{
    $sqlState = $error instanceof PDOException ? (string) ($error->errorInfo[0] ?? '') : '';
    $driverCode = $error instanceof PDOException ? (string) ($error->errorInfo[1] ?? '') : '';
    error_log(sprintf(
        '[database] tahap=%s jenis=%s sqlstate=%s kode=%s pesan=%s',
        $stage,
        (new ReflectionClass($error))->getShortName(),
        $sqlState === '' ? '-' : $sqlState,
        $driverCode === '' ? '-' : $driverCode,
        $error->getMessage()
    ));
}

function db(): PDO
{
    static $connection;
    if ($connection instanceof PDO) {
        return $connection;
    }
    $stage = 'konfigurasi';
    try {
        $path = __DIR__ . '/../config/database.php';
        if (!is_file($path)) {
            $path = __DIR__ . '/../config/database.example.php';
        }
        $config = require $path;
        if (!is_array($config)) {
            throw new RuntimeException('Konfigurasi database tidak valid.');
        }
        foreach (['host' => 'DB_HOST', 'port' => 'DB_PORT', 'name' => 'DB_NAME', 'user' => 'DB_USER', 'password' => 'DB_PASSWORD', 'ssl_ca' => 'DB_SSL_CA'] as $key => $variable) {
            $value = environment_value($variable);
            if ($value !== false) {
                $config[$key] = $value;
            }
        }
        $sslSetting = environment_value('DB_SSL');
        if ($sslSetting !== false) {
            if (!in_array($sslSetting, ['0', '1'], true)) {
                throw new RuntimeException('DB_SSL hanya boleh bernilai 1 atau 0.');
            }
            $config['ssl'] = $sslSetting === '1';
        }
        foreach (['host', 'port', 'name', 'user'] as $key) {
            if (!isset($config[$key]) || !is_string($config[$key]) || trim($config[$key]) === '') {
                throw new RuntimeException('Konfigurasi database belum lengkap. Periksa config/database.php atau variabel lingkungan DB_*.');
            }
        }
        if (!array_key_exists('password', $config) || !is_string($config['password'])) {
            throw new RuntimeException('Kata sandi database belum diatur. Periksa config/database.php atau variabel lingkungan DB_PASSWORD.');
        }
        $stage = 'tls';
        $options = [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
        ];
        if (!empty($config['ssl'])) {
            if (!defined('PDO::MYSQL_ATTR_SSL_CA')) {
                throw new RuntimeException('Koneksi TLS MySQL memerlukan ekstensi PHP pdo_mysql.');
            }
            $caPath = resolve_ca_bundle((string) ($config['ssl_ca'] ?? ''));
            if ($caPath !== '') {
                $config['ssl_ca'] = $caPath;
                $options[constant('PDO::MYSQL_ATTR_SSL_CA')] = $caPath;
                if (defined('PDO::MYSQL_ATTR_SSL_VERIFY_SERVER_CERT')) {
                    $options[constant('PDO::MYSQL_ATTR_SSL_VERIFY_SERVER_CERT')] = true;
                }
            } else {
                error_log('[database] berkas CA TLS tidak ditemukan di config/; verifikasi memakai CA sistem.');
            }
        }
        $stage = 'koneksi';
        log_database_host_resolution((string) $config['host']);
        $connection = new PDO(
            sprintf('mysql:host=%s;port=%s;dbname=%s;charset=utf8mb4', $config['host'], $config['port'], $config['name']),
            $config['user'],
            $config['password'],
            $options
        );
        return $connection;
    } catch (Throwable $error) {
        log_database_failure($stage, $error);
        throw $error;
    }
}

function e(mixed $value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

function csrf_token(): string
{
    if (!isset($_SESSION['csrf'])) {
        $_SESSION['csrf'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf'];
}

function require_csrf(): void
{
    if (!is_string($_POST['csrf'] ?? null) || !hash_equals(csrf_token(), $_POST['csrf'])) {
        http_response_code(403);
        exit('Permintaan tidak valid. Muat ulang halaman dan coba lagi.');
    }
}

function redirect_to(string $url): never
{
    header('Location: ' . $url, true, 303);
    exit;
}

function flash(string $type, string $message): void
{
    $_SESSION['flash'] = ['type' => $type, 'message' => $message];
}

function entity_config(string $entity): array
{
    $configs = [
        'siswa' => [
            'label' => 'Siswa', 'table' => 'siswa', 'key' => 'nis',
            'select' => 'nis, nama, kelas, IF(foto IS NULL, NULL, nis) AS foto', 'order' => 'nis',
            'search' => ['nis', 'nama', 'kelas'],
            'fields' => ['nis' => 'NIS', 'nama' => 'Nama Siswa', 'kelas' => 'Kelas', 'foto' => 'Foto'],
        ],
        'pelajaran' => [
            'label' => 'Pelajaran', 'table' => 'pelajaran', 'key' => 'kd_pelajaran',
            'select' => 'kd_pelajaran, nama_pelajaran, bobot', 'order' => 'kd_pelajaran',
            'search' => ['kd_pelajaran', 'nama_pelajaran'],
            'fields' => ['kd_pelajaran' => 'Kode Pelajaran', 'nama_pelajaran' => 'Nama Pelajaran', 'bobot' => 'Bobot'],
        ],
        'mempelajari' => [
            'label' => 'Mempelajari', 'table' => 'mempelajari', 'key' => 'nomornilai',
            'select' => 'm.nomornilai, m.nis, s.nama AS nama_siswa, m.kd_pelajaran, p.nama_pelajaran, m.nilai',
            'order' => 'm.nomornilai',
            'search' => ['m.nis', 's.nama', 'm.kd_pelajaran', 'p.nama_pelajaran'],
            'fields' => ['nomornilai' => 'Nomor Nilai', 'nis' => 'NIS', 'nama_siswa' => 'Nama Siswa', 'kd_pelajaran' => 'Kode Pelajaran', 'nama_pelajaran' => 'Nama Pelajaran', 'nilai' => 'Nilai'],
        ],
    ];
    if (!isset($configs[$entity])) {
        throw new InvalidArgumentException('Modul tidak dikenal.');
    }
    return $configs[$entity];
}

function entity_query(string $entity): string
{
    $config = entity_config($entity);
    return $entity === 'mempelajari'
        ? 'SELECT ' . $config['select'] . ' FROM mempelajari m JOIN siswa s ON s.nis = m.nis JOIN pelajaran p ON p.kd_pelajaran = m.kd_pelajaran'
        : 'SELECT ' . $config['select'] . ' FROM ' . $config['table'];
}

function fetch_record(string $entity, string $id): array|false
{
    $config = entity_config($entity);
    $key = $config['key'];
    if ($entity === 'mempelajari' && !ctype_digit($id)) {
        return false;
    }
    $query = db()->prepare(entity_query($entity) . ' WHERE ' . ($entity === 'mempelajari' ? 'm.' : '') . $key . ' = ?');
    $query->execute([$id]);
    return $query->fetch();
}

function format_value(string $entity, string $key, mixed $value): string
{
    if ($entity === 'siswa' && $key === 'foto') {
        return $value ? '<img class="student-thumb" src="index.php?page=photo&amp;nis=' . rawurlencode((string) $value) . '" alt="Foto siswa">' : '<span class="muted">Tidak ada foto</span>';
    }
    if ($key === 'nilai' || $key === 'bobot') {
        return e(rtrim(rtrim(number_format((float) $value, 2, '.', ''), '0'), '.')) . ($key === 'bobot' ? '%' : '');
    }
    return e($value);
}

function upload_photo(array $file): ?string
{
    $uploadError = (int) ($file['error'] ?? UPLOAD_ERR_NO_FILE);
    if ($uploadError === UPLOAD_ERR_NO_FILE) {
        return null;
    }
    if (in_array($uploadError, [UPLOAD_ERR_INI_SIZE, UPLOAD_ERR_FORM_SIZE], true)) {
        throw new DomainException('Ukuran foto maksimal 2 MB.');
    }
    if ($uploadError !== UPLOAD_ERR_OK
        || !isset($file['tmp_name'], $file['name'], $file['size'])
        || !is_uploaded_file($file['tmp_name'])) {
        throw new DomainException('Foto gagal diunggah. Silakan pilih berkas kembali.');
    }
    $size = filesize($file['tmp_name']);
    if ($size === false || $size < 1 || $size > 2 * 1024 * 1024) {
        throw new DomainException('Ukuran foto maksimal 2 MB.');
    }
    $extension = strtolower(pathinfo((string) $file['name'], PATHINFO_EXTENSION));
    $mime = (new finfo(FILEINFO_MIME_TYPE))->file($file['tmp_name']);
    $allowed = ['jpg' => 'image/jpeg', 'jpeg' => 'image/jpeg', 'png' => 'image/png', 'webp' => 'image/webp'];
    $image = @getimagesize($file['tmp_name']);
    if (!isset($allowed[$extension]) || $allowed[$extension] !== $mime
        || !is_array($image) || $image['mime'] !== $mime
        || $image[0] < 1 || $image[1] < 1 || $image[0] * $image[1] > 40000000) {
        throw new DomainException('Format foto tidak valid. Gunakan JPG, PNG, atau WebP yang valid.');
    }
    $contents = file_get_contents($file['tmp_name']);
    if ($contents === false) {
        throw new RuntimeException('Foto tervalidasi tetapi tidak dapat dibaca untuk disimpan.');
    }
    return $contents;
}

function handle_post(): never
{
    require_csrf();
    $entity = input_text($_POST, 'entity');
    if (!in_array($entity, ['siswa', 'pelajaran', 'mempelajari'], true)) {
        http_response_code(400);
        exit('Modul tidak dikenal.');
    }
    $config = entity_config($entity);
    $action = input_text($_POST, 'action');
    if (!in_array($action, ['create', 'update', 'delete'], true)) {
        http_response_code(400);
        exit('Operasi tidak dikenal.');
    }
    $id = input_text($_POST, 'id');
    $formUrl = 'index.php?' . http_build_query(['page' => $entity, 'action' => $action === 'update' ? 'edit' : 'create', 'id' => $id]);
    if ($action !== 'create' && fetch_record($entity, $id) === false) {
        flash('error', $config['label'] . ' tidak ditemukan atau sudah dihapus.');
        redirect_to('index.php?page=' . rawurlencode($entity));
    }
    if ($action === 'delete') {
        try {
            $statement = db()->prepare('DELETE FROM ' . $config['table'] . ' WHERE ' . $config['key'] . ' = ?');
            $statement->execute([$id]);
            flash($statement->rowCount() ? 'success' : 'error', $statement->rowCount() ? $config['label'] . ' berhasil dihapus.' : 'Data sudah dihapus oleh permintaan lain.');
        } catch (PDOException $error) {
            error_log((string) $error);
            if ((int) ($error->errorInfo[1] ?? 0) === 1451) {
                flash('error', 'Data tidak dapat dihapus karena masih digunakan oleh Mempelajari. Hapus nilai terkait terlebih dahulu.');
            } else {
                flash('error', 'Data gagal dihapus. Silakan coba lagi atau periksa log server.');
            }
        }
        redirect_to('index.php?page=' . rawurlencode($entity));
    }

    [$data, $errors] = validate_record($entity, $_POST);
    if ($action === 'update' && $entity !== 'mempelajari') {
        $key = $config['key'];
        $data[$key] = $id;
    }
    if ($entity === 'mempelajari' && $action === 'update') {
        $data['nomornilai'] = $id;
    }
    if ($entity === 'mempelajari' && !$errors) {
        foreach (['nis' => 'siswa', 'kd_pelajaran' => 'pelajaran'] as $field => $master) {
            $masterConfig = entity_config($master);
            $check = db()->prepare('SELECT 1 FROM ' . $masterConfig['table'] . ' WHERE ' . $masterConfig['key'] . ' = ?');
            $check->execute([$data[$field]]);
            if (!$check->fetchColumn()) {
                $errors[$field] = $master === 'siswa' ? 'Siswa yang dipilih tidak tersedia.' : 'Pelajaran yang dipilih tidak tersedia.';
            }
        }
    }
    if ($errors) {
        $_SESSION['form'] = ['entity' => $entity, 'id' => $id, 'data' => $data, 'errors' => $errors];
        flash('error', 'Data belum disimpan. Periksa kolom yang ditandai.');
        redirect_to($formUrl);
    }

    $newPhoto = null;
    try {
        if ($entity === 'siswa' && isset($_FILES['foto'])) {
            $newPhoto = upload_photo($_FILES['foto']);
            if ($newPhoto !== null) {
                $data['foto'] = $newPhoto;
            }
        }
        $fields = array_keys($data);
        if ($entity === 'siswa' && $action === 'create' && !array_key_exists('foto', $data)) {
            $fields[] = 'foto';
            $data['foto'] = null;
        }
        if ($action === 'create') {
            $columns = implode(', ', $fields);
            $marks = implode(', ', array_fill(0, count($fields), '?'));
            $statement = db()->prepare('INSERT INTO ' . $config['table'] . ' (' . $columns . ') VALUES (' . $marks . ')');
            foreach ($fields as $index => $field) {
                $type = $field === 'foto' ? ($data[$field] === null ? PDO::PARAM_NULL : PDO::PARAM_LOB) : PDO::PARAM_STR;
                $statement->bindValue($index + 1, $data[$field], $type);
            }
            $statement->execute();
            $recordId = $entity === 'mempelajari' ? (string) db()->lastInsertId() : (string) $data[$config['key']];
        } else {
            $key = $config['key'];
            $fields = array_values(array_filter($fields, static fn(string $field): bool => $field !== $key));
            $assignments = implode(', ', array_map(static fn(string $field): string => $field . ' = ?', $fields));
            $statement = db()->prepare('UPDATE ' . $config['table'] . ' SET ' . $assignments . ' WHERE ' . $key . ' = ?');
            foreach ($fields as $index => $field) {
                $type = $field === 'foto' ? ($data[$field] === null ? PDO::PARAM_NULL : PDO::PARAM_LOB) : PDO::PARAM_STR;
                $statement->bindValue($index + 1, $data[$field], $type);
            }
            $statement->bindValue(count($fields) + 1, $id, PDO::PARAM_STR);
            $statement->execute();
            $recordId = $id;
        }
        unset($_SESSION['form']);
        flash('success', $action === 'create' ? $config['label'] . ' berhasil ditambahkan.' : 'Perubahan ' . strtolower($config['label']) . ' berhasil disimpan.');
        redirect_to('index.php?' . http_build_query(['page' => $entity, 'action' => 'detail', 'id' => $recordId]));
    } catch (DomainException $error) {
        $errors['foto'] = $error->getMessage();
    } catch (PDOException $error) {
        error_log((string) $error);
        unset($data['foto']);
        $driverCode = (int) ($error->errorInfo[1] ?? 0);
        if ($driverCode === 1062) {
            if ($entity === 'mempelajari') {
                $errors['kd_pelajaran'] = 'Siswa tersebut sudah memiliki nilai untuk pelajaran ini. Edit nilai yang sudah ada.';
            } elseif ($entity === 'siswa') {
                $errors['nis'] = 'NIS sudah digunakan. Pilih NIS yang berbeda.';
            } else {
                $errors['kd_pelajaran'] = 'Kode pelajaran sudah digunakan. Pilih kode yang berbeda.';
            }
        } elseif ($driverCode === 1452) {
            $errors['nis'] = 'Siswa atau pelajaran yang dipilih sudah tidak tersedia.';
        } else {
            $field = $entity === 'siswa' ? 'nama' : ($entity === 'pelajaran' ? 'nama_pelajaran' : 'nilai');
            $errors[$field] = 'Data gagal disimpan. Coba lagi atau periksa log server.';
        }
    } catch (Throwable $error) {
        throw $error;
    }
    $_SESSION['form'] = ['entity' => $entity, 'id' => $id, 'data' => $data, 'errors' => $errors];
    flash('error', $errors ? 'Data belum disimpan. Periksa kolom yang ditandai.' : 'Data gagal disimpan.');
    redirect_to($formUrl);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    handle_post();
}
