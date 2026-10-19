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
  <title><?= e($title) ?> · SIA SMK Malaka</title>
  <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
<div class="app-shell">
  <aside class="sidebar">
    <a class="brand" href="index.php"><span class="brand-mark">M</span><span><strong>SIA MALAKA</strong><small>Sistem Informasi Akademik</small></span></a>
    <div class="nav-caption">MENU UTAMA</div>
    <nav class="main-nav" aria-label="Navigasi utama">
      <a class="nav-item <?= $active === 'dashboard' ? 'active' : '' ?>" href="index.php" <?= $active === 'dashboard' ? 'aria-current="page"' : '' ?>>
        <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M3 10.8 12 3l9 7.8v9.7a.5.5 0 0 1-.5.5h-6v-7h-5v7h-6a.5.5 0 0 1-.5-.5z"/></svg>Dashboard
      </a>
      <?php foreach ($entityNames as $key => $label): ?>
      <a class="nav-item <?= $active === $key ? 'active' : '' ?>" href="index.php?page=<?= e($key) ?>" <?= $active === $key ? 'aria-current="page"' : '' ?>>
        <?php if ($key === 'siswa'): ?><svg viewBox="0 0 24 24" aria-hidden="true"><circle cx="9" cy="8" r="3.5"/><path d="M2.5 20c.4-4 2.6-6 6.5-6s6.1 2 6.5 6zM16 5.5a3.5 3.5 0 0 1 0 6.8M17 14c2.8.6 4.2 2.6 4.5 6"/></svg>
        <?php elseif ($key === 'pelajaran'): ?><svg viewBox="0 0 24 24" aria-hidden="true"><path d="M3 5.5c3.1-.9 6.1-.4 9 1.3 2.9-1.7 5.9-2.2 9-1.3v13c-3.1-.9-6.1-.4-9 1.3-2.9-1.7-5.9-2.2-9-1.3zM12 7v12.8"/></svg>
        <?php else: ?><svg viewBox="0 0 24 24" aria-hidden="true"><path d="M4 5h16v14H4zM8 9h8M8 13h5M8 17h3"/></svg><?php endif; ?>
        <?= e($label) ?>
      </a>
      <?php endforeach; ?>
    </nav>
    <div class="sidebar-footer"><span class="status-dot"></span>Panel Operator<br><small>SMK Malaka</small></div>
  </aside>
  <main class="main-content">
    <div class="mobile-brand"><span class="brand-mark">M</span><strong>SIA MALAKA</strong></div>
    <header class="topbar"><div class="breadcrumb"><a href="index.php">Beranda</a><?php if ($active !== 'dashboard'): ?><span aria-hidden="true">/</span><a href="index.php?page=<?= e($active) ?>"><?= e($entityNames[$active] ?? '') ?></a><?php endif; ?></div><span class="operator-label">Operator Akademik</span></header>
    <?php if ($notice): ?><div class="alert <?= ($notice['type'] ?? '') === 'success' ? 'success' : 'error' ?>" role="status"><?= e($notice['message'] ?? '') ?></div><?php endif; ?>
    <div class="page-heading"><div><p class="eyebrow">SISTEM INFORMASI AKADEMIK</p><h1><?= e($title) ?></h1><p class="page-description"><?= e(match ($active) { 'siswa' => 'Kelola data identitas siswa SMK Malaka.', 'pelajaran' => 'Kelola mata pelajaran dan bobot penilaian.', 'mempelajari' => 'Kelola nilai siswa pada setiap mata pelajaran.', default => 'Ringkasan data akademik yang tersimpan di sistem.' }) ?></p></div></div>
<?php
}

function page_end(): void
{
    echo '</main></div><script src="assets/js/app.js" defer></script></body></html>';
}

function post_delete_form(string $entity, string $id, string $label): string
{
    return '<form class="inline-form delete-form" method="post" action="index.php"><input type="hidden" name="csrf" value="' . e(csrf_token()) . '"><input type="hidden" name="entity" value="' . e($entity) . '"><input type="hidden" name="action" value="delete"><input type="hidden" name="id" value="' . e($id) . '"><button class="action-link danger-link" type="submit" data-confirm="Hapus ' . e($label) . '?">Hapus</button></form>';
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
      <div><p class="eyebrow">SMK MALAKA</p><h2>Ringkasan Akademik</h2><p>Jumlah data diperbarui langsung dari basis data.</p></div>
      <a class="button button-light" href="index.php?page=mempelajari&action=create">Input Nilai <span aria-hidden="true">→</span></a>
    </section>
    <section class="stat-grid" aria-label="Jumlah data aktual">
      <?php foreach ($entityNames as $key => $label): ?>
      <a class="stat-card" href="index.php?page=<?= e($key) ?>"><span class="stat-label">Total <?= e($label) ?></span><strong><?= number_format($counts[$key]) ?></strong><span class="stat-link">Lihat data <span aria-hidden="true">→</span></span></a>
      <?php endforeach; ?>
    </section>
    <section class="panel">
      <div class="section-heading"><div><h2>Nilai terbaru</h2><p>Entri terakhir yang tersimpan pada Mempelajari.</p></div><a class="text-link" href="index.php?page=mempelajari">Lihat semua</a></div>
      <div class="table-wrap"><table><thead><tr><th>NIS</th><th>Nama Siswa</th><th>Pelajaran</th><th>Nilai</th></tr></thead><tbody>
        <?php foreach ($recent as $row): ?><tr><td class="mono"><?= e($row['nis']) ?></td><td><?= e($row['nama_siswa']) ?></td><td><?= e($row['nama_pelajaran']) ?><span class="sub-cell"><?= e($row['kd_pelajaran']) ?></span></td><td><span class="grade-pill"><?= e(rtrim(rtrim(number_format((float) $row['nilai'], 2, '.', ''), '0'), '.')) ?></span></td></tr><?php endforeach; ?>
        <?php if (!$recent): ?><tr><td colspan="4"><div class="empty-state"><span class="empty-icon" aria-hidden="true">—</span><h3>Belum ada nilai</h3><p>Data nilai akan muncul setelah dicatat pada modul Mempelajari.</p><a class="button button-primary" href="index.php?page=mempelajari&action=create">Tambah Nilai</a></div></td></tr><?php endif; ?>
      </tbody></table></div>
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
        echo '<section class="panel empty-state"><h2>Data tidak tersedia atau sudah dihapus.</h2><a class="button" href="index.php?page=' . e($page) . '">Kembali ke daftar</a></section>';
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
      <div class="section-heading"><div><h2><?= $editing ? 'Perbarui informasi' : 'Informasi ' . e($config['label']) ?></h2><p>Lengkapi kolom bertanda wajib sebelum menyimpan.</p></div></div>
      <?php if ($errors): ?><div class="form-error-summary" role="alert"><strong>Data belum tersimpan.</strong> Periksa kolom yang ditandai.</div><?php endif; ?>
      <form method="post" action="index.php" <?= $page === 'siswa' ? 'enctype="multipart/form-data"' : '' ?>>
        <input type="hidden" name="csrf" value="<?= e(csrf_token()) ?>"><input type="hidden" name="entity" value="<?= e($page) ?>"><input type="hidden" name="action" value="<?= $editing ? 'update' : 'create' ?>">
        <?php if ($editing): ?><input type="hidden" name="id" value="<?= e($id) ?>"><?php endif; ?>
        <?php if ($page === 'siswa'): ?>
          <?php foreach (['nis' => ['NIS', 'Contoh: 20241001'], 'nama' => ['Nama Lengkap', 'Nama siswa'], 'kelas' => ['Kelas', 'Contoh: XI TKJ 1']] as $key => [$label, $placeholder]): ?>
          <div class="field-row"><label for="<?= e($key) ?>"><?= e($label) ?> <span class="required">*</span></label><div class="field-control"><input id="<?= e($key) ?>" name="<?= e($key) ?>" value="<?= e($data[$key] ?? '') ?>" placeholder="<?= e($placeholder) ?>" maxlength="<?= $key === 'kelas' ? 30 : ($key === 'nis' ? 20 : 100) ?>" required <?= $editing && $key === 'nis' ? 'readonly' : '' ?> <?= isset($errors[$key]) ? 'aria-invalid="true"' : '' ?>><small class="<?= isset($errors[$key]) ? 'field-error' : '' ?>"><?= e($errors[$key] ?? ($key === 'nis' && $editing ? 'NIS adalah identitas tetap.' : '')) ?></small></div></div>
          <?php endforeach; ?>
          <div class="field-row"><label for="foto">Foto (opsional)</label><div class="field-control"><?php if (!empty($data['foto'])): ?><img class="form-photo" src="index.php?page=photo&amp;nis=<?= e(rawurlencode((string) $data['nis'])) ?>" alt="Foto siswa saat ini"><?php endif; ?><input id="foto" name="foto" type="file" accept=".jpg,.jpeg,.png,.webp,image/jpeg,image/png,image/webp" <?= isset($errors['foto']) ? 'aria-invalid="true"' : '' ?>><small class="<?= isset($errors['foto']) ? 'field-error' : '' ?>"><?= e($errors['foto'] ?? 'JPG, PNG, atau WebP; ukuran maksimal 2 MB. Kosongkan jika tidak ingin mengganti foto.') ?></small></div></div>
        <?php elseif ($page === 'pelajaran'): ?>
          <div class="field-row"><label for="kd_pelajaran">Kode Pelajaran <span class="required">*</span></label><div class="field-control"><input id="kd_pelajaran" name="kd_pelajaran" value="<?= e($data['kd_pelajaran'] ?? '') ?>" maxlength="20" placeholder="Contoh: TKJ-01" required <?= $editing ? 'readonly' : '' ?> <?= isset($errors['kd_pelajaran']) ? 'aria-invalid="true"' : '' ?>><small class="<?= isset($errors['kd_pelajaran']) ? 'field-error' : '' ?>"><?= e($errors['kd_pelajaran'] ?? ($editing ? 'Kode merupakan identitas tetap.' : 'Maksimal 20 karakter, huruf dan angka.')) ?></small></div></div>
          <div class="field-row"><label for="nama_pelajaran">Nama Pelajaran <span class="required">*</span></label><div class="field-control"><input id="nama_pelajaran" name="nama_pelajaran" value="<?= e($data['nama_pelajaran'] ?? '') ?>" maxlength="100" placeholder="Nama mata pelajaran" required <?= isset($errors['nama_pelajaran']) ? 'aria-invalid="true"' : '' ?>><small class="<?= isset($errors['nama_pelajaran']) ? 'field-error' : '' ?>"><?= e($errors['nama_pelajaran'] ?? 'Maksimal 100 karakter.') ?></small></div></div>
          <div class="field-row"><label for="bobot">Bobot (%) <span class="required">*</span></label><div class="field-control"><input id="bobot" name="bobot" type="number" min="1" max="100" step="0.01" value="<?= e($data['bobot'] ?? '') ?>" placeholder="Contoh: 10" required <?= isset($errors['bobot']) ? 'aria-invalid="true"' : '' ?>><small class="<?= isset($errors['bobot']) ? 'field-error' : '' ?>"><?= e($errors['bobot'] ?? 'Rentang 1 sampai 100, maksimal dua angka desimal.') ?></small></div></div>
        <?php else:
            $students = db()->query('SELECT nis, nama, kelas FROM siswa ORDER BY nama, nis')->fetchAll();
            $subjects = db()->query('SELECT kd_pelajaran, nama_pelajaran FROM pelajaran ORDER BY nama_pelajaran, kd_pelajaran')->fetchAll();
            ?>
          <div class="field-row"><label for="nomornilai">Nomor Nilai</label><div class="field-control"><input id="nomornilai" value="<?= $editing ? e($id) : 'Dibuat otomatis saat disimpan' ?>" readonly><small>Nomor nilai dikelola otomatis oleh sistem.</small></div></div>
          <div class="field-row"><label for="nis">Siswa <span class="required">*</span></label><div class="field-control"><select id="nis" name="nis" required <?= !$students ? 'disabled' : '' ?> <?= isset($errors['nis']) ? 'aria-invalid="true"' : '' ?>><option value="">Pilih siswa</option><?php foreach ($students as $student): ?><option value="<?= e($student['nis']) ?>" <?= ($data['nis'] ?? '') === $student['nis'] ? 'selected' : '' ?>><?= e($student['nis'] . ' — ' . $student['nama'] . ' (' . $student['kelas'] . ')') ?></option><?php endforeach; ?></select><small class="<?= isset($errors['nis']) ? 'field-error' : '' ?>"><?= e($errors['nis'] ?? (!$students ? 'Tambahkan data siswa terlebih dahulu.' : 'Pilih dari siswa yang tersimpan.')) ?></small></div></div>
          <div class="field-row"><label for="kd_pelajaran">Pelajaran <span class="required">*</span></label><div class="field-control"><select id="kd_pelajaran" name="kd_pelajaran" required <?= !$subjects ? 'disabled' : '' ?> <?= isset($errors['kd_pelajaran']) ? 'aria-invalid="true"' : '' ?>><option value="">Pilih pelajaran</option><?php foreach ($subjects as $subject): ?><option value="<?= e($subject['kd_pelajaran']) ?>" <?= ($data['kd_pelajaran'] ?? '') === $subject['kd_pelajaran'] ? 'selected' : '' ?>><?= e($subject['kd_pelajaran'] . ' — ' . $subject['nama_pelajaran']) ?></option><?php endforeach; ?></select><small class="<?= isset($errors['kd_pelajaran']) ? 'field-error' : '' ?>"><?= e($errors['kd_pelajaran'] ?? (!$subjects ? 'Tambahkan data pelajaran terlebih dahulu.' : 'Pilih dari pelajaran yang tersimpan.')) ?></small></div></div>
          <div class="field-row"><label for="nilai">Nilai <span class="required">*</span></label><div class="field-control"><input id="nilai" name="nilai" type="number" min="0" max="100" step="0.01" value="<?= e($data['nilai'] ?? '') ?>" placeholder="0–100" required <?= isset($errors['nilai']) ? 'aria-invalid="true"' : '' ?>><small class="<?= isset($errors['nilai']) ? 'field-error' : '' ?>"><?= e($errors['nilai'] ?? 'Rentang 0 sampai 100, maksimal dua angka desimal. Satu siswa hanya memiliki satu nilai per pelajaran.') ?></small></div></div>
        <?php endif; ?>
        <div class="form-actions"><button class="button button-primary" type="submit" <?= $page === 'mempelajari' && (!$students || !$subjects) ? 'disabled' : '' ?>>Simpan Data</button><a class="button button-plain" href="<?= $editing ? 'index.php?' . e(http_build_query(['page' => $page, 'action' => 'detail', 'id' => $id])) : 'index.php?page=' . e($page) ?>">Batal</a></div>
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
        echo '<section class="panel empty-state"><h2>Data tidak tersedia atau sudah dihapus.</h2><a class="button" href="index.php?page=' . e($page) . '">Kembali ke daftar</a></section>';
        page_end();
        exit;
    }
    $title = 'Detail ' . $config['label'];
    page_start($title, $page);
    ?>
    <section class="panel detail-panel">
      <div class="section-heading"><div><h2>Informasi <?= e($config['label']) ?></h2><p>Data tersimpan pada basis data akademik.</p></div><a class="button button-plain" href="index.php?page=<?= e($page) ?>">Kembali</a></div>
      <dl class="detail-list">
        <?php foreach ($config['fields'] as $key => $label): ?><div><dt><?= e($label) ?></dt><dd><?= format_value($page, $key, $record[$key] ?? '') ?></dd></div><?php endforeach; ?>
      </dl>
      <div class="form-actions"><a class="button button-primary" href="index.php?<?= e(http_build_query(['page' => $page, 'action' => 'edit', 'id' => $id])) ?>">Edit Data</a><?= post_delete_form($page, $id, $config['label']) ?></div>
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
<div class="list-toolbar"><form class="search-form" method="get" action="index.php"><input type="hidden" name="page" value="<?= e($page) ?>"><label class="sr-only" for="q">Cari <?= e(strtolower($config['label'])) ?></label><input id="q" name="q" type="search" value="<?= e($queryText) ?>" placeholder="<?= e('Cari ' . strtolower($config['label']) . '...') ?>"><button class="button button-plain" type="submit">Cari</button><?php if ($queryText !== ''): ?><a class="reset-link" href="index.php?page=<?= e($page) ?>">Reset</a><?php endif; ?></form><a class="button button-primary" href="index.php?page=<?= e($page) ?>&amp;action=create"><span aria-hidden="true">+</span> Tambah Data</a></div>
<section class="panel">
  <div class="table-wrap" tabindex="0" role="region" aria-label="Tabel <?= e(strtolower($config['label'])) ?>; geser untuk melihat seluruh kolom">
    <table>
      <thead><tr><?php foreach ($config['fields'] as $key => $label): ?><th scope="col"><?= e($label) ?></th><?php endforeach; ?><th scope="col">Operasi</th></tr></thead>
      <tbody>
      <?php foreach ($rows as $row): $recordId = (string) $row[$config['key']]; ?>
        <tr><?php foreach ($config['fields'] as $key => $label): ?><td><?= format_value($page, $key, $row[$key] ?? '') ?></td><?php endforeach; ?><td><div class="row-actions"><a class="action-link" href="index.php?<?= e(http_build_query(['page' => $page, 'action' => 'detail', 'id' => $recordId])) ?>">Lihat</a><a class="action-link" href="index.php?<?= e(http_build_query(['page' => $page, 'action' => 'edit', 'id' => $recordId])) ?>">Edit</a><?= post_delete_form($page, $recordId, $config['label']) ?></div></td></tr>
      <?php endforeach; ?>
      <?php if (!$rows): ?><tr><td colspan="<?= count($config['fields']) + 1 ?>"><div class="empty-state"><span class="empty-icon" aria-hidden="true"><?= $queryText !== '' ? '⌕' : '+' ?></span><h2><?= $queryText !== '' ? 'Tidak ada hasil pencarian' : 'Belum ada data ' . e(strtolower($config['label'])) ?></h2><p><?= $queryText !== '' ? 'Coba kata kunci yang berbeda atau tampilkan seluruh data.' : 'Tambahkan data pertama untuk mulai mengelola informasi.' ?></p><a class="button button-primary" href="<?= $queryText !== '' ? 'index.php?page=' . e($page) : 'index.php?page=' . e($page) . '&amp;action=create' ?>"><?= $queryText !== '' ? 'Tampilkan Semua' : 'Tambah Data' ?></a></div></td></tr><?php endif; ?>
      </tbody>
    </table>
  </div>
  <div class="table-footer"><span><?= $total ? 'Menampilkan ' . ($offset + 1) . '–' . min($offset + $perPage, $total) . ' dari ' . $total : '0 data' ?><?= $queryText !== '' ? ' sesuai pencarian' : '' ?></span><nav class="pagination" aria-label="Halaman daftar"><?php if ($currentPage > 1): ?><a class="button button-plain" href="index.php?<?= e(http_build_query(['page' => $page, 'q' => $queryText, 'p' => $currentPage - 1])) ?>" aria-label="Halaman sebelumnya">←</a><?php endif; ?><span>Halaman <?= $currentPage ?> / <?= $pages ?></span><?php if ($currentPage < $pages): ?><a class="button button-plain" href="index.php?<?= e(http_build_query(['page' => $page, 'q' => $queryText, 'p' => $currentPage + 1])) ?>" aria-label="Halaman berikutnya">→</a><?php endif; ?></nav></div>
</section>
<?php page_end(); ?>
