<?php
declare(strict_types=1);

function input_text(array $input, string $key): string
{
    return is_string($input[$key] ?? null) ? trim($input[$key]) : '';
}

function valid_human_name(string $value, int $limit): bool
{
    $length = preg_match_all('/./us', $value);
    return $length !== false
        && $length >= 1
        && $length <= $limit
        && preg_match('/\A\s*\z/u', $value) !== 1
        && preg_match('/[\x00-\x1F\x7F]/', $value) !== 1;
}

function validate_record(string $entity, array $input): array
{
    $errors = [];
    if ($entity === 'siswa') {
        $data = [
            'nis' => strtoupper(input_text($input, 'nis')),
            'nama' => input_text($input, 'nama'),
            'kelas' => input_text($input, 'kelas'),
        ];
        if (!preg_match('/\A[A-Z0-9_-]{1,20}\z/', $data['nis'])) {
            $errors['nis'] = 'NIS wajib diisi, maksimal 20 karakter: huruf, angka, tanda hubung, atau garis bawah.';
        }
        if (!valid_human_name($data['nama'], 100)) {
            $errors['nama'] = 'Nama wajib diisi, maksimal 100 karakter, tanpa karakter kontrol.';
        }
        if (!valid_human_name($data['kelas'], 30)) {
            $errors['kelas'] = 'Kelas wajib diisi, maksimal 30 karakter, tanpa karakter kontrol.';
        }
        return [$data, $errors];
    }
    if ($entity === 'pelajaran') {
        $data = [
            'kd_pelajaran' => strtoupper(input_text($input, 'kd_pelajaran')),
            'nama_pelajaran' => input_text($input, 'nama_pelajaran'),
            'bobot' => input_text($input, 'bobot'),
        ];
        if (!preg_match('/\A[A-Z0-9_-]{1,20}\z/', $data['kd_pelajaran'])) {
            $errors['kd_pelajaran'] = 'Kode wajib diisi, maksimal 20 karakter: huruf, angka, tanda hubung, atau garis bawah.';
        }
        if (!valid_human_name($data['nama_pelajaran'], 100)) {
            $errors['nama_pelajaran'] = 'Nama pelajaran wajib diisi, maksimal 100 karakter, tanpa karakter kontrol.';
        }
        if (!preg_match('/\A[0-9]+(?:\.[0-9]{1,2})?\z/', $data['bobot'])
            || (float) $data['bobot'] < 1
            || (float) $data['bobot'] > 100) {
            $errors['bobot'] = 'Bobot harus berupa angka 1 sampai 100, maksimal dua angka desimal.';
        }
        return [$data, $errors];
    }
    if ($entity === 'mempelajari') {
        $data = [
            'nis' => strtoupper(input_text($input, 'nis')),
            'kd_pelajaran' => strtoupper(input_text($input, 'kd_pelajaran')),
            'nilai' => input_text($input, 'nilai'),
        ];
        if (!preg_match('/\A[A-Z0-9_-]{1,20}\z/', $data['nis'])) {
            $errors['nis'] = 'Pilih siswa yang valid.';
        }
        if (!preg_match('/\A[A-Z0-9_-]{1,20}\z/', $data['kd_pelajaran'])) {
            $errors['kd_pelajaran'] = 'Pilih pelajaran yang valid.';
        }
        if (!preg_match('/\A[0-9]{1,3}(?:\.[0-9]{1,2})?\z/', $data['nilai'])
            || (float) $data['nilai'] < 0
            || (float) $data['nilai'] > 100) {
            $errors['nilai'] = 'Nilai harus berupa angka 0 sampai 100, maksimal dua angka desimal.';
        }
        return [$data, $errors];
    }
    throw new InvalidArgumentException('Modul tidak dikenal.');
}
