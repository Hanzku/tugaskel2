<?php
declare(strict_types=1);
require __DIR__ . '/includes/app.php';

$entityNames = ['siswa' => 'Siswa', 'pelajaran' => 'Pelajaran', 'mempelajari' => 'Mempelajari'];
$page = input_text($_GET, 'page');
$action = input_text($_GET, 'action');
if (!in_array($page, ['dashboard', 'photo', ...array_keys($entityNames)], true)) {
    $page = 'dashboard';
}
if ($page !== 'dashboard' && !in_array($action, ['', 'create', 'edit', 'detail'], true)) {
    $action = '';
}
$id = input_text($_GET, 'id');
$photoNis = input_text($_GET, 'nis');
$isPhotoRequest = $page === 'photo';
$notice = $_SESSION['flash'] ?? null;
unset($_SESSION['flash']);
$formState = $_SESSION['form'] ?? null;
if (($formState['entity'] ?? null) === $page && ($formState['id'] ?? '') === $id) {
    unset($_SESSION['form']);
} else {
    unset($_SESSION['form']);
    $formState = null;
}
if ($isPhotoRequest) {
    if (!preg_match('/\A[A-Z0-9_-]{1,20}\z/', strtoupper($photoNis))) {
        http_response_code(404);
        exit('Foto tidak ditemukan.');
    }
    $photoQuery = db()->prepare('SELECT foto FROM siswa WHERE nis = ? AND foto IS NOT NULL');
    $photoQuery->execute([strtoupper($photoNis)]);
    $photo = $photoQuery->fetchColumn();
    if (!is_string($photo) || $photo === '') {
        http_response_code(404);
        exit('Foto tidak ditemukan.');
    }
    $photoInfo = getimagesizefromstring($photo);
    $photoTypes = [IMAGETYPE_JPEG => 'image/jpeg', IMAGETYPE_PNG => 'image/png', IMAGETYPE_WEBP => 'image/webp'];
    if (!is_array($photoInfo) || !isset($photoTypes[$photoInfo[2]])) {
        throw new RuntimeException('Data foto pada database bukan gambar yang didukung.');
    }
    header('Content-Type: ' . $photoTypes[$photoInfo[2]]);
    header('Content-Length: ' . strlen($photo));
    header('X-Content-Type-Options: nosniff');
    header('Cache-Control: private, max-age=300');
    echo $photo;
    exit;
}

function page_start(string $title, string $active = 'dashboard'): void
{
    global $notice, $entityNames;
    ?>
<!doctype html>
<html lang="id">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <meta name="color-scheme" content="light">
  <meta name="description" content="SIA Malaka - Sistem Informasi Akademik SMK Malaka">
  <title><?= e($title) ?> · SIA SMK Malaka</title>
  <link rel="icon" type="image/svg+xml" href="assets/img/favicon.svg">
  <link rel="alternate icon" type="image/png" href="assets/img/favicon.png">
  <link rel="apple-touch-icon" href="assets/img/favicon.png">
  <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
<div class="sidebar-backdrop" id="sidebarBackdrop" aria-hidden="true"></div>
<div class="app-shell">
  <aside class="sidebar" id="appSidebar" aria-label="Navigasi aplikasi">
    <div class="sidebar-header">
      <a class="brand" href="index.php" title="Ke Dashboard Operator">
        <span class="brand-mark">
          <img class="brand-icon" src="assets/img/logo.svg" alt="Logo SIA Malaka" width="38" height="38">
        </span>
        <span class="brand-text">
          <strong>SIA MALAKA</strong>
          <small>Sistem Informasi Akademik</small>
        </span>
      </a>
      <button type="button" class="sidebar-close" id="sidebarClose" aria-label="Tutup menu navigasi">
        <svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M18 6 6 18M6 6l12 12"/></svg>
      </button>
    </div>
    <div class="nav-caption">MENU UTAMA</div>
    <nav class="main-nav" aria-label="Navigasi utama">
      <a class="nav-item <?= $active === 'dashboard' ? 'active' : '' ?>" href="index.php" <?= $active === 'dashboard' ? 'aria-current="page"' : '' ?>>
        <svg viewBox="0 0 24 24" width="19" height="19" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M3 10.5 12 3l9 7.5v9a1.5 1.5 0 0 1-1.5 1.5h-5a1 1 0 0 1-1-1v-5h-3v5a1 1 0 0 1-1 1h-5A1.5 1.5 0 0 1 3 19.5z"/></svg>
        <span>Dashboard</span>
      </a>
      <?php foreach ($entityNames as $key => $label): ?>
      <a class="nav-item <?= $active === $key ? 'active' : '' ?>" href="index.php?page=<?= e($key) ?>" <?= $active === $key ? 'aria-current="page"' : '' ?>>
        <?php if ($key === 'siswa'): ?>
          <svg viewBox="0 0 24 24" width="19" height="19" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M22 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/></svg>
        <?php elseif ($key === 'pelajaran'): ?>
          <svg viewBox="0 0 24 24" width="19" height="19" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M2 3h6a4 4 0 0 1 4 4v14a3 3 0 0 0-3-3H2z"/><path d="M22 3h-6a4 4 0 0 0-4 4v14a3 3 0 0 1 3-3h7z"/></svg>
        <?php else: ?>
          <svg viewBox="0 0 24 24" width="19" height="19" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><rect x="3" y="3" width="18" height="18" rx="2"/><path d="M7 8h10M7 12h10M7 16h6"/></svg>
        <?php endif; ?>
        <span><?= e($label) ?></span>
      </a>
      <?php endforeach; ?>
    </nav>
    <div class="sidebar-footer">
      <div class="operator-status">
        <span class="status-dot" aria-hidden="true"></span>
        <div class="operator-meta">
          <span class="operator-role">Panel Operator</span>
          <span class="operator-org">SMK Malaka</span>
        </div>
      </div>
    </div>
  </aside>
  <main class="main-content">
    <header class="topbar">
      <div class="topbar-left">
        <button type="button" class="sidebar-toggle" id="sidebarToggle" aria-label="Buka menu navigasi" aria-expanded="false" aria-controls="appSidebar">
          <svg viewBox="0 0 24 24" width="20" height="20" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M4 6h16M4 12h16M4 18h16"/></svg>
        </button>
        <a class="mobile-brand" href="index.php" title="Ke Dashboard Operator">
          <span class="brand-mark brand-mark-sm">
            <img class="brand-icon" src="assets/img/logo.svg" alt="Logo SIA Malaka" width="30" height="30">
          </span>
          <strong>SIA MALAKA</strong>
        </a>
        <nav class="breadcrumb" aria-label="Navigasi rekam jejak">
          <a href="index.php">Beranda</a>
          <?php if ($active !== 'dashboard'): ?>
            <span class="breadcrumb-separator" aria-hidden="true">/</span>
            <span class="breadcrumb-current"><?= e($entityNames[$active] ?? '') ?></span>
          <?php else: ?>
            <span class="breadcrumb-separator" aria-hidden="true">/</span>
            <span class="breadcrumb-current">Dashboard</span>
          <?php endif; ?>
        </nav>
      </div>
      <div class="topbar-right">
        <div class="operator-badge">
          <svg viewBox="0 0 24 24" width="15" height="15" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M19 21v-2a4 4 0 0 0-4-4H9a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>
          <span class="operator-label">Operator Akademik</span>
        </div>
      </div>
    </header>
    <?php if ($notice): ?>
      <div class="alert <?= ($notice['type'] ?? '') === 'success' ? 'alert-success' : 'alert-error' ?>" role="status">
        <span class="alert-icon" aria-hidden="true">
          <?php if (($notice['type'] ?? '') === 'success'): ?>
            <svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M20 6 9 17l-5-5"/></svg>
          <?php else: ?>
            <svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><path d="M12 8v4M12 16h.01"/></svg>
          <?php endif; ?>
        </span>
        <div class="alert-text"><?= e($notice['message'] ?? '') ?></div>
      </div>
    <?php endif; ?>
    <div class="page-heading">
      <div>
        <p class="eyebrow">SISTEM INFORMASI AKADEMIK</p>
        <h1><?= e($title) ?></h1>
        <p class="page-description"><?= e(match ($active) {
          'siswa' => 'Kelola data identitas siswa SMK Malaka.',
          'pelajaran' => 'Kelola mata pelajaran dan bobot penilaian.',
          'mempelajari' => 'Kelola nilai siswa pada setiap mata pelajaran.',
          default => 'Ringkasan data akademik yang tersimpan di sistem.'
        }) ?></p>
      </div>
    </div>
<?php
}

function page_end(): void
{
    echo '</main></div><script src="assets/js/app.js" defer></script></body></html>';
}

function post_delete_form(string $entity, string $id, string $label): string
{
    return '<form class="inline-form delete-form" method="post" action="index.php">'
        . '<input type="hidden" name="csrf" value="' . e(csrf_token()) . '">'
        . '<input type="hidden" name="entity" value="' . e($entity) . '">'
        . '<input type="hidden" name="action" value="delete">'
        . '<input type="hidden" name="id" value="' . e($id) . '">'
        . '<button class="action-btn action-delete action-link danger-link" type="submit" data-confirm="Hapus ' . e($label) . ' ini? Data yang terhapus tidak dapat dikembalikan.">Hapus</button>'
        . '</form>';
}

if ($page === 'dashboard') {
    $counts = [];
    foreach ($entityNames as $key => $label) {
        $counts[$key] = (int) db()->query('SELECT COUNT(*) FROM ' . entity_config($key)['table'])->fetchColumn();
    }
    $recent = db()->query('SELECT m.nomornilai, m.nis, s.nama AS nama_siswa, m.kd_pelajaran, p.nama_pelajaran, m.nilai FROM mempelajari m JOIN siswa s ON s.nis=m.nis JOIN pelajaran p ON p.kd_pelajaran=m.kd_pelajaran ORDER BY m.nomornilai DESC LIMIT 5')->fetchAll();
    page_start('Dashboard Operator');
    ?>
    <section class="welcome-banner">
      <div class="banner-body">
        <p class="banner-eyebrow">SMK MALAKA</p>
        <h2>Ringkasan Akademik</h2>
        <p class="banner-desc">Jumlah data diperbarui langsung dari basis data.</p>
      </div>
      <div class="banner-actions">
        <a class="button button-banner" href="index.php?page=mempelajari&amp;action=create">Input Nilai <span class="arrow-icon" aria-hidden="true">→</span></a>
      </div>
    </section>
    <section class="stat-grid" aria-label="Jumlah data aktual">
      <?php foreach ($entityNames as $key => $label): ?>
      <a class="stat-card" href="index.php?page=<?= e($key) ?>">
        <span class="stat-label">Total <?= e($label) ?></span>
        <strong class="stat-number"><?= number_format($counts[$key]) ?></strong>
        <span class="stat-link">Lihat data <span class="arrow-icon" aria-hidden="true">→</span></span>
      </a>
      <?php endforeach; ?>
    </section>
    <section class="panel">
      <div class="section-heading">
        <div>
          <h2>Nilai terbaru</h2>
          <p>Entri terakhir yang tersimpan pada Mempelajari.</p>
        </div>
        <a class="text-link" href="index.php?page=mempelajari">Lihat semua</a>
      </div>
      <div class="table-wrap">
        <table>
          <thead>
            <tr>
              <th scope="col">NIS</th>
              <th scope="col">Nama Siswa</th>
              <th scope="col">Pelajaran</th>
              <th scope="col">Nilai</th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($recent as $row): ?>
            <tr>
              <td class="mono"><?= e($row['nis']) ?></td>
              <td class="cell-strong"><?= e($row['nama_siswa']) ?></td>
              <td>
                <span class="cell-main-text"><?= e($row['nama_pelajaran']) ?></span>
                <span class="sub-cell"><?= e($row['kd_pelajaran']) ?></span>
              </td>
              <td><span class="grade-pill"><?= e(rtrim(rtrim(number_format((float) $row['nilai'], 2, '.', ''), '0'), '.')) ?></span></td>
            </tr>
            <?php endforeach; ?>
            <?php if (!$recent): ?>
            <tr>
              <td colspan="4">
                <div class="empty-state">
                  <span class="empty-icon" aria-hidden="true">
                    <svg viewBox="0 0 24 24" width="22" height="22" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M4 5h16v14H4zM8 9h8M8 13h5M8 17h3"/></svg>
                  </span>
                  <h3>Belum ada nilai</h3>
                  <p>Data nilai akan muncul setelah dicatat pada modul Mempelajari.</p>
                  <a class="button button-primary" href="index.php?page=mempelajari&amp;action=create">Tambah Nilai</a>
                </div>
              </td>
            </tr>
            <?php endif; ?>
          </tbody>
        </table>
      </div>
    </section>
    <?php
    page_end();
    exit;
}

$config = entity_config($page);
$title = $config['label'];
if ($action === 'create' || $action === 'edit') {
    $editing = $action === 'edit';
    $record = $editing ? fetch_record($page, $id) : false;
    if ($editing && $record === false) {
        http_response_code(404);
        $title = $config['label'] . ' tidak ditemukan';
        page_start($title, $page);
        echo '<section class="panel empty-state"><h2>Data tidak tersedia atau sudah dihapus.</h2><a class="button button-plain" href="index.php?page=' . e($page) . '">Kembali ke daftar</a></section>';
        page_end();
        exit;
    }
    $title = ($editing ? 'Edit ' : 'Tambah ') . $config['label'];
    $data = $record ?: [];
    $errors = [];
    if ($formState !== null) {
        $data = array_merge($data, $formState['data']);
        $errors = $formState['errors'];
    }
    page_start($title, $page);
    ?>
    <section class="panel form-panel">
      <div class="section-heading">
        <div>
          <h2><?= $editing ? 'Perbarui informasi' : 'Informasi ' . e($config['label']) ?></h2>
          <p>Lengkapi kolom bertanda wajib sebelum menyimpan.</p>
        </div>
      </div>
      <?php if ($errors): ?>
        <div class="form-error-summary" role="alert">
          <strong>Data belum tersimpan.</strong> Periksa kolom yang ditandai.
        </div>
      <?php endif; ?>
      <form class="entity-form" method="post" action="index.php" <?= $page === 'siswa' ? 'enctype="multipart/form-data"' : '' ?>>
        <input type="hidden" name="csrf" value="<?= e(csrf_token()) ?>">
        <input type="hidden" name="entity" value="<?= e($page) ?>">
        <input type="hidden" name="action" value="<?= $editing ? 'update' : 'create' ?>">
        <?php if ($editing): ?><input type="hidden" name="id" value="<?= e($id) ?>"><?php endif; ?>
        <?php if ($page === 'siswa'): ?>
          <?php foreach (['nis' => ['NIS', 'Contoh: 20241001'], 'nama' => ['Nama Lengkap', 'Nama siswa'], 'kelas' => ['Kelas', 'Contoh: XI TKJ 1']] as $key => [$label, $placeholder]): ?>
          <div class="field-row">
            <label for="<?= e($key) ?>"><?= e($label) ?> <span class="required" title="Wajib diisi">*</span></label>
            <div class="field-control">
              <input id="<?= e($key) ?>" name="<?= e($key) ?>" value="<?= e($data[$key] ?? '') ?>" placeholder="<?= e($placeholder) ?>" maxlength="<?= $key === 'kelas' ? 30 : ($key === 'nis' ? 20 : 100) ?>" required <?= $editing && $key === 'nis' ? 'readonly' : '' ?> <?= isset($errors[$key]) ? 'aria-invalid="true"' : '' ?>>
              <small class="<?= isset($errors[$key]) ? 'field-error' : 'field-help' ?>"><?= e($errors[$key] ?? ($key === 'nis' && $editing ? 'NIS adalah identitas tetap.' : '')) ?></small>
            </div>
          </div>
          <?php endforeach; ?>
          <div class="field-row">
            <label for="foto">Foto <span class="optional-label">(opsional)</span></label>
            <div class="field-control">
              <?php if (!empty($data['foto'])): ?>
                <div class="current-photo-wrap">
                  <img class="form-photo" src="index.php?page=photo&amp;nis=<?= e(rawurlencode((string) $data['nis'])) ?>" alt="Foto siswa saat ini">
                  <span class="photo-caption">Foto tersimpan saat ini</span>
                </div>
              <?php endif; ?>
              <input id="foto" name="foto" type="file" accept=".jpg,.jpeg,.png,.webp,image/jpeg,image/png,image/webp" <?= isset($errors['foto']) ? 'aria-invalid="true"' : '' ?>>
              <small class="<?= isset($errors['foto']) ? 'field-error' : 'field-help' ?>"><?= e($errors['foto'] ?? 'JPG, PNG, atau WebP; ukuran maksimal 2 MB. Kosongkan jika tidak ingin mengganti foto.') ?></small>
            </div>
          </div>
        <?php elseif ($page === 'pelajaran'): ?>
          <div class="field-row">
            <label for="kd_pelajaran">Kode Pelajaran <span class="required" title="Wajib diisi">*</span></label>
            <div class="field-control">
              <input id="kd_pelajaran" name="kd_pelajaran" value="<?= e($data['kd_pelajaran'] ?? '') ?>" maxlength="20" placeholder="Contoh: TKJ-01" required <?= $editing ? 'readonly' : '' ?> <?= isset($errors['kd_pelajaran']) ? 'aria-invalid="true"' : '' ?>>
              <small class="<?= isset($errors['kd_pelajaran']) ? 'field-error' : 'field-help' ?>"><?= e($errors['kd_pelajaran'] ?? ($editing ? 'Kode merupakan identitas tetap.' : 'Maksimal 20 karakter, huruf dan angka.')) ?></small>
            </div>
          </div>
          <div class="field-row">
            <label for="nama_pelajaran">Nama Pelajaran <span class="required" title="Wajib diisi">*</span></label>
            <div class="field-control">
              <input id="nama_pelajaran" name="nama_pelajaran" value="<?= e($data['nama_pelajaran'] ?? '') ?>" maxlength="100" placeholder="Nama mata pelajaran" required <?= isset($errors['nama_pelajaran']) ? 'aria-invalid="true"' : '' ?>>
              <small class="<?= isset($errors['nama_pelajaran']) ? 'field-error' : 'field-help' ?>"><?= e($errors['nama_pelajaran'] ?? 'Maksimal 100 karakter.') ?></small>
            </div>
          </div>
          <div class="field-row">
            <label for="bobot">Bobot (%) <span class="required" title="Wajib diisi">*</span></label>
            <div class="field-control">
              <input id="bobot" name="bobot" type="number" min="1" max="100" step="0.01" value="<?= e($data['bobot'] ?? '') ?>" placeholder="Contoh: 10" required <?= isset($errors['bobot']) ? 'aria-invalid="true"' : '' ?>>
              <small class="<?= isset($errors['bobot']) ? 'field-error' : 'field-help' ?>"><?= e($errors['bobot'] ?? 'Rentang 1 sampai 100, maksimal dua angka desimal.') ?></small>
            </div>
          </div>
        <?php else:
            $students = db()->query('SELECT nis, nama, kelas FROM siswa ORDER BY nama, nis')->fetchAll();
            $subjects = db()->query('SELECT kd_pelajaran, nama_pelajaran FROM pelajaran ORDER BY nama_pelajaran, kd_pelajaran')->fetchAll();
            ?>
          <div class="field-row">
            <label for="nomornilai">Nomor Nilai</label>
            <div class="field-control">
              <input id="nomornilai" value="<?= $editing ? e($id) : 'Dibuat otomatis saat disimpan' ?>" readonly>
              <small class="field-help">Nomor nilai dikelola otomatis oleh sistem.</small>
            </div>
          </div>
          <div class="field-row">
            <label for="nis">Siswa <span class="required" title="Wajib diisi">*</span></label>
            <div class="field-control">
              <select id="nis" name="nis" required <?= !$students ? 'disabled' : '' ?> <?= isset($errors['nis']) ? 'aria-invalid="true"' : '' ?>>
                <option value="">Pilih siswa</option>
                <?php foreach ($students as $student): ?>
                  <option value="<?= e($student['nis']) ?>" <?= ($data['nis'] ?? '') === $student['nis'] ? 'selected' : '' ?>><?= e($student['nis'] . ' — ' . $student['nama'] . ' (' . $student['kelas'] . ')') ?></option>
                <?php endforeach; ?>
              </select>
              <small class="<?= isset($errors['nis']) ? 'field-error' : 'field-help' ?>"><?= e($errors['nis'] ?? (!$students ? 'Tambahkan data siswa terlebih dahulu.' : 'Pilih dari siswa yang tersimpan.')) ?></small>
            </div>
          </div>
          <div class="field-row">
            <label for="kd_pelajaran">Pelajaran <span class="required" title="Wajib diisi">*</span></label>
            <div class="field-control">
              <select id="kd_pelajaran" name="kd_pelajaran" required <?= !$subjects ? 'disabled' : '' ?> <?= isset($errors['kd_pelajaran']) ? 'aria-invalid="true"' : '' ?>>
                <option value="">Pilih pelajaran</option>
                <?php foreach ($subjects as $subject): ?>
                  <option value="<?= e($subject['kd_pelajaran']) ?>" <?= ($data['kd_pelajaran'] ?? '') === $subject['kd_pelajaran'] ? 'selected' : '' ?>><?= e($subject['kd_pelajaran'] . ' — ' . $subject['nama_pelajaran']) ?></option>
                <?php endforeach; ?>
              </select>
              <small class="<?= isset($errors['kd_pelajaran']) ? 'field-error' : 'field-help' ?>"><?= e($errors['kd_pelajaran'] ?? (!$subjects ? 'Tambahkan data pelajaran terlebih dahulu.' : 'Pilih dari pelajaran yang tersimpan.')) ?></small>
            </div>
          </div>
          <div class="field-row">
            <label for="nilai">Nilai <span class="required" title="Wajib diisi">*</span></label>
            <div class="field-control">
              <input id="nilai" name="nilai" type="number" min="0" max="100" step="0.01" value="<?= e($data['nilai'] ?? '') ?>" placeholder="0–100" required <?= isset($errors['nilai']) ? 'aria-invalid="true"' : '' ?>>
              <small class="<?= isset($errors['nilai']) ? 'field-error' : 'field-help' ?>"><?= e($errors['nilai'] ?? 'Rentang 0 sampai 100, maksimal dua angka desimal. Satu siswa hanya memiliki satu nilai per pelajaran.') ?></small>
            </div>
          </div>
        <?php endif; ?>
        <div class="form-actions">
          <button class="button button-primary" type="submit" <?= $page === 'mempelajari' && (!$students || !$subjects) ? 'disabled' : '' ?>>Simpan Data</button>
          <a class="button button-plain" href="<?= $editing ? 'index.php?' . e(http_build_query(['page' => $page, 'action' => 'detail', 'id' => $id])) : 'index.php?page=' . e($page) ?>">Batal</a>
        </div>
      </form>
    </section>
    <?php
    page_end();
    exit;
}

if ($action === 'detail') {
    $record = fetch_record($page, $id);
    if ($record === false) {
        http_response_code(404);
        $title = $config['label'] . ' tidak ditemukan';
        page_start($title, $page);
        echo '<section class="panel empty-state"><h2>Data tidak tersedia atau sudah dihapus.</h2><a class="button button-plain" href="index.php?page=' . e($page) . '">Kembali ke daftar</a></section>';
        page_end();
        exit;
    }
    $title = 'Detail ' . $config['label'];
    page_start($title, $page);
    ?>
    <section class="panel detail-panel">
      <div class="section-heading">
        <div>
          <h2>Informasi <?= e($config['label']) ?></h2>
          <p>Data tersimpan pada basis data akademik.</p>
        </div>
        <a class="button button-plain" href="index.php?page=<?= e($page) ?>">Kembali</a>
      </div>
      <dl class="detail-list">
        <?php foreach ($config['fields'] as $key => $label): ?>
          <div>
            <dt><?= e($label) ?></dt>
            <dd><?= format_value($page, $key, $record[$key] ?? '') ?></dd>
          </div>
        <?php endforeach; ?>
      </dl>
      <div class="form-actions">
        <a class="button button-primary" href="index.php?<?= e(http_build_query(['page' => $page, 'action' => 'edit', 'id' => $id])) ?>">Edit Data</a>
        <?= post_delete_form($page, $id, $config['label']) ?>
      </div>
    </section>
    <?php
    page_end();
    exit;
}

$queryText = substr(input_text($_GET, 'q'), 0, 200);
$currentPage = max(1, min(1000000, (int) input_text($_GET, 'p')));
$perPage = 10;
$baseQuery = entity_query($page);
$params = [];
if ($queryText !== '') {
    $conditions = [];
    $escaped = '%' . strtr($queryText, ['!' => '!!', '%' => '!%', '_' => '!_']) . '%';
    foreach ($config['search'] as $field) {
        $conditions[] = $field . " LIKE ? ESCAPE '!'";
        $params[] = $escaped;
    }
    $where = ' WHERE ' . implode(' OR ', $conditions);
} else {
    $where = '';
}
$countTable = $page === 'mempelajari' ? 'mempelajari m JOIN siswa s ON s.nis=m.nis JOIN pelajaran p ON p.kd_pelajaran=m.kd_pelajaran' : $config['table'];
$countStatement = db()->prepare('SELECT COUNT(*) FROM ' . $countTable . $where);
$countStatement->execute($params);
$total = (int) $countStatement->fetchColumn();
$pages = max(1, (int) ceil($total / $perPage));
$currentPage = min($currentPage, $pages);
$offset = ($currentPage - 1) * $perPage;
$statement = db()->prepare($baseQuery . $where . ' ORDER BY ' . $config['order'] . ' DESC LIMIT ' . $perPage . ' OFFSET ' . $offset);
$statement->execute($params);
$rows = $statement->fetchAll();

page_start('Data ' . $config['label'], $page);
?>
<div class="list-toolbar">
  <form class="search-form" method="get" action="index.php">
    <input type="hidden" name="page" value="<?= e($page) ?>">
    <label class="sr-only" for="q">Cari <?= e(strtolower($config['label'])) ?></label>
    <div class="search-input-wrap">
      <svg class="search-icon" viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="11" cy="11" r="7"/><path d="m21 21-4.35-4.35"/></svg>
      <input id="q" name="q" type="search" value="<?= e($queryText) ?>" placeholder="<?= e('Cari ' . strtolower($config['label']) . '...') ?>" autocomplete="off">
    </div>
    <button class="button button-plain" type="submit">Cari</button>
    <?php if ($queryText !== ''): ?>
      <a class="reset-link" href="index.php?page=<?= e($page) ?>">Reset</a>
    <?php endif; ?>
  </form>
  <a class="button button-primary" href="index.php?page=<?= e($page) ?>&amp;action=create">
    <span aria-hidden="true">+</span> Tambah Data
  </a>
</div>
<section class="panel">
  <div class="table-wrap" tabindex="0" role="region" aria-label="Tabel <?= e(strtolower($config['label'])) ?>; geser untuk melihat seluruh kolom">
    <table>
      <thead>
        <tr>
          <?php foreach ($config['fields'] as $key => $label): ?>
            <th scope="col"><?= e($label) ?></th>
          <?php endforeach; ?>
          <th scope="col" class="th-actions">Operasi</th>
        </tr>
      </thead>
      <tbody>
      <?php foreach ($rows as $row): $recordId = (string) $row[$config['key']]; ?>
        <tr>
          <?php foreach ($config['fields'] as $key => $label): ?>
            <td class="<?= $key === 'nis' || $key === 'kd_pelajaran' || $key === 'nomornilai' ? 'mono' : '' ?>"><?= format_value($page, $key, $row[$key] ?? '') ?></td>
          <?php endforeach; ?>
          <td>
            <div class="row-actions">
              <a class="action-btn action-view action-link" href="index.php?<?= e(http_build_query(['page' => $page, 'action' => 'detail', 'id' => $recordId])) ?>">Lihat</a>
              <a class="action-btn action-edit action-link" href="index.php?<?= e(http_build_query(['page' => $page, 'action' => 'edit', 'id' => $recordId])) ?>">Edit</a>
              <?= post_delete_form($page, $recordId, $config['label']) ?>
            </div>
          </td>
        </tr>
      <?php endforeach; ?>
      <?php if (!$rows): ?>
        <tr>
          <td colspan="<?= count($config['fields']) + 1 ?>">
            <div class="empty-state">
              <span class="empty-icon" aria-hidden="true">
                <?php if ($queryText !== ''): ?>
                  <svg viewBox="0 0 24 24" width="22" height="22" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="11" cy="11" r="7"/><path d="m21 21-4.35-4.35"/></svg>
                <?php else: ?>
                  <svg viewBox="0 0 24 24" width="22" height="22" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M12 5v14M5 12h14"/></svg>
                <?php endif; ?>
              </span>
              <h2><?= e(
                $queryText !== ''
                  ? match ($page) {
                      'siswa' => 'Siswa tidak ditemukan',
                      'pelajaran' => 'Pelajaran tidak ditemukan',
                      'mempelajari' => 'Data nilai tidak ditemukan',
                      default => 'Tidak ada hasil pencarian'
                    }
                  : match ($page) {
                      'siswa' => 'Belum ada data siswa',
                      'pelajaran' => 'Belum ada data pelajaran',
                      'mempelajari' => 'Belum ada data mempelajari',
                      default => 'Belum ada data ' . strtolower($config['label'])
                    }
              ) ?></h2>
              <p><?= e($queryText !== '' ? 'Coba kata kunci yang berbeda atau tampilkan seluruh data.' : 'Tambahkan data pertama untuk mulai mengelola informasi.') ?></p>
              <a class="button button-primary" href="<?= $queryText !== '' ? 'index.php?page=' . e($page) : 'index.php?page=' . e($page) . '&amp;action=create' ?>">
                <?= $queryText !== '' ? 'Tampilkan Semua' : 'Tambah Data' ?>
              </a>
            </div>
          </td>
        </tr>
      <?php endif; ?>
      </tbody>
    </table>
  </div>
  <div class="table-footer">
    <span><?= $total ? 'Menampilkan ' . ($offset + 1) . '–' . min($offset + $perPage, $total) . ' dari ' . $total . ' data' : '0 data' ?><?= $queryText !== '' ? ' sesuai pencarian' : '' ?></span>
    <nav class="pagination" aria-label="Halaman daftar">
      <?php if ($currentPage > 1): ?>
        <a class="button button-plain" href="index.php?<?= e(http_build_query(['page' => $page, 'q' => $queryText, 'p' => $currentPage - 1])) ?>" aria-label="Halaman sebelumnya">←</a>
      <?php endif; ?>
      <span class="pagination-indicator">Halaman <?= $currentPage ?> / <?= $pages ?></span>
      <?php if ($currentPage < $pages): ?>
        <a class="button button-plain" href="index.php?<?= e(http_build_query(['page' => $page, 'q' => $queryText, 'p' => $currentPage + 1])) ?>" aria-label="Halaman berikutnya">→</a>
      <?php endif; ?>
    </nav>
  </div>
</section>
<?php page_end(); ?>
