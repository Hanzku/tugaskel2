<?php
declare(strict_types=1);
require __DIR__ . '/../includes/app.php';

function ensure(bool $condition, string $message): void
{
    if (!$condition) {
        throw new RuntimeException($message);
    }
}

function expect_database_rejection(callable $operation, string $message): void
{
    try {
        $operation();
    } catch (PDOException) {
        return;
    }
    throw new RuntimeException($message);
}

$connection = db();
$studentId = 'TST' . strtoupper(bin2hex(random_bytes(6)));
$subjectId = 'TST' . strtoupper(bin2hex(random_bytes(6)));
$secondSubjectId = 'TST' . strtoupper(bin2hex(random_bytes(6)));
$connection->beginTransaction();

try {
    $insertStudent = $connection->prepare('INSERT INTO siswa (nis, nama, kelas, foto) VALUES (?, ?, ?, NULL)');
    $insertStudent->execute([$studentId, 'Siswa Tes', 'XI TKJ']);
    $updateStudent = $connection->prepare('UPDATE siswa SET nama = ?, kelas = ? WHERE nis = ?');
    $updateStudent->execute(['Siswa Tes Diperbarui', 'XII TKJ', $studentId]);
    $searchStudent = $connection->prepare('SELECT nama, kelas FROM siswa WHERE nis LIKE ?');
    $searchStudent->execute(['%' . $studentId . '%']);
    $student = $searchStudent->fetch();
    ensure($student !== false && $student['nama'] === 'Siswa Tes Diperbarui' && $student['kelas'] === 'XII TKJ', 'Siswa create, search, read, atau update gagal.');

    $insertSubject = $connection->prepare('INSERT INTO pelajaran (kd_pelajaran, nama_pelajaran, bobot) VALUES (?, ?, ?)');
    $insertSubject->execute([$subjectId, 'Pelajaran Tes', '10.00']);
    $updateSubject = $connection->prepare('UPDATE pelajaran SET nama_pelajaran = ?, bobot = ? WHERE kd_pelajaran = ?');
    $updateSubject->execute(['Pelajaran Tes Diperbarui', '12.50', $subjectId]);
    $subjectSearch = $connection->prepare('SELECT nama_pelajaran, bobot FROM pelajaran WHERE kd_pelajaran LIKE ?');
    $subjectSearch->execute(['%' . $subjectId . '%']);
    $subject = $subjectSearch->fetch();
    ensure($subject !== false && $subject['nama_pelajaran'] === 'Pelajaran Tes Diperbarui' && (float) $subject['bobot'] === 12.5, 'Pelajaran create, search, read, atau update gagal.');

    $insertSubject->execute([$secondSubjectId, 'Pelajaran Tes Batas', '1.00']);
    $insertGrade = $connection->prepare('INSERT INTO mempelajari (nis, kd_pelajaran, nilai) VALUES (?, ?, ?)');
    $insertGrade->execute([$studentId, $subjectId, '75.50']);
    $readGrade = $connection->prepare('SELECT m.nomornilai, s.nama, p.nama_pelajaran, m.nilai FROM mempelajari m JOIN siswa s ON s.nis=m.nis JOIN pelajaran p ON p.kd_pelajaran=m.kd_pelajaran WHERE m.nis=? AND m.kd_pelajaran=?');
    $readGrade->execute([$studentId, $subjectId]);
    $grade = $readGrade->fetch();
    ensure($grade !== false && $grade['nama'] === 'Siswa Tes Diperbarui' && $grade['nama_pelajaran'] === 'Pelajaran Tes Diperbarui', 'Nilai tidak terbaca dengan relasi master.');

    expect_database_rejection(
        static fn() => $insertGrade->execute([$studentId, $subjectId, '80']),
        'Pasangan siswa-pelajaran duplikat diterima.'
    );
    expect_database_rejection(
        static fn() => $insertGrade->execute([$studentId, $secondSubjectId, '101']),
        'Nilai di luar batas 0-100 diterima.'
    );
    $missingStudent = 'TST' . strtoupper(bin2hex(random_bytes(6)));
    expect_database_rejection(
        static fn() => $insertGrade->execute([$missingStudent, $subjectId, '50']),
        'Foreign key siswa tidak menolak data yang tidak tersedia.'
    );
    expect_database_rejection(
        static fn() => $connection->prepare('DELETE FROM siswa WHERE nis=?')->execute([$studentId]),
        'Penghapusan siswa yang masih dirujuk oleh nilai tidak dicegah.'
    );
    expect_database_rejection(
        static fn() => $connection->prepare('DELETE FROM pelajaran WHERE kd_pelajaran=?')->execute([$subjectId]),
        'Penghapusan pelajaran yang masih dirujuk oleh nilai tidak dicegah.'
    );

    $updateGrade = $connection->prepare('UPDATE mempelajari SET nilai=? WHERE nomornilai=?');
    $updateGrade->execute(['88.25', $grade['nomornilai']]);
    $checkGrade = $connection->prepare('SELECT nilai FROM mempelajari WHERE nomornilai=?');
    $checkGrade->execute([$grade['nomornilai']]);
    ensure((float) $checkGrade->fetchColumn() === 88.25, 'Pembaruan nilai gagal.');
    $deleteGrade = $connection->prepare('DELETE FROM mempelajari WHERE nomornilai=?');
    $deleteGrade->execute([$grade['nomornilai']]);
    ensure($deleteGrade->rowCount() === 1, 'Penghapusan nilai gagal.');

    $connection->prepare('DELETE FROM siswa WHERE nis=?')->execute([$studentId]);
    $connection->prepare('DELETE FROM pelajaran WHERE kd_pelajaran=?')->execute([$subjectId]);
    $connection->prepare('DELETE FROM pelajaran WHERE kd_pelajaran=?')->execute([$secondSubjectId]);
    $connection->rollBack();
    echo "PASS: smoke test database Siswa, Pelajaran, Mempelajari, pencarian, validasi batas, pasangan duplikat, foreign key, dan penghapusan master.\n";
} catch (Throwable $error) {
    if ($connection->inTransaction()) {
        $connection->rollBack();
    }
    throw $error;
}
