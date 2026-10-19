<?php
declare(strict_types=1);
require __DIR__ . '/../includes/validation.php';

$checks = 0;
function check(bool $condition, string $message): void
{
    global $checks;
    ++$checks;
    if (!$condition) {
        throw new RuntimeException($message);
    }
}

[$data, $errors] = validate_record('siswa', ['nis' => ' 2024001 ', 'nama' => ' Siswa Contoh ', 'kelas' => ' XI TKJ 1 ']);
check($errors === [] && $data['nis'] === '2024001' && $data['nama'] === 'Siswa Contoh', 'Valid student rejected or not normalized.');
foreach (['', 'NIS WITH SPACE', str_repeat('A', 21), "A\nB", []] as $value) {
    [, $errors] = validate_record('siswa', ['nis' => $value, 'nama' => 'Nama', 'kelas' => 'XI TKJ']);
    check(isset($errors['nis']), 'Invalid NIS accepted.');
}
foreach (['', ' ', str_repeat('x', 101), "abc\nxyz", "\xFF", []] as $value) {
    [, $errors] = validate_record('siswa', ['nis' => 'A1', 'nama' => $value, 'kelas' => 'XI TKJ']);
    check(isset($errors['nama']), 'Invalid student name accepted.');
}
[$data, $errors] = validate_record('pelajaran', ['kd_pelajaran' => ' tkj-01 ', 'nama_pelajaran' => ' Jaringan ', 'bobot' => '12.50']);
check($errors === [] && $data['kd_pelajaran'] === 'TKJ-01', 'Valid subject rejected.');
foreach (['1', '100', '1.01', '99.99'] as $value) {
    [, $errors] = validate_record('pelajaran', ['kd_pelajaran' => 'A', 'nama_pelajaran' => 'Nama', 'bobot' => $value]);
    check(!isset($errors['bobot']), 'Valid subject weight rejected.');
}
foreach (['', '0', '-1', '100.01', '1.234', '1e2', 'NaN', '12,5', 'abc'] as $value) {
    [, $errors] = validate_record('pelajaran', ['kd_pelajaran' => 'A', 'nama_pelajaran' => 'Nama', 'bobot' => $value]);
    check(isset($errors['bobot']), 'Invalid subject weight accepted.');
}
foreach (['0', '100', '1.25', '99.99'] as $value) {
    [, $errors] = validate_record('mempelajari', ['nis' => 'A1', 'kd_pelajaran' => 'B1', 'nilai' => $value]);
    check(!isset($errors['nilai']), 'Valid grade rejected.');
}
foreach (['', '-1', '100.01', '1.234', '1e2', 'NaN', 'abc'] as $value) {
    [, $errors] = validate_record('mempelajari', ['nis' => 'A1', 'kd_pelajaran' => 'B1', 'nilai' => $value]);
    check(isset($errors['nilai']), 'Invalid grade accepted.');
}
[, $errors] = validate_record('mempelajari', ['nis' => [], 'kd_pelajaran' => [], 'nilai' => []]);
check(count($errors) === 3, 'Array inputs must fail validation.');
echo "PASS: $checks validation checks\n";
