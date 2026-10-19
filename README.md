# tugaskel1 — SIA SMK Malaka, Kelompok 1: Mempelajari

Aplikasi administrasi akademik berbasis PHP 8+, MySQL/MariaDB, PDO, HTML, CSS, dan JavaScript vanilla. Modul yang tersedia adalah Dashboard, Siswa, Pelajaran, dan Mempelajari. Tidak ada modul Diampu.

## Menyiapkan MySQL daring

Aplikasi menggunakan MySQL 8 di Aiven dengan TLS dan CA sertifikat khusus layanan. Kredensial database tidak disimpan di repository.

1. Buat layanan **MySQL** pada konsol Aiven. Paket gratis berkapasitas terbatas dan dapat dimatikan otomatis saat tidak aktif; tinjau paket dan ketentuan terbaru sebelum memilih layanan berbayar.
2. Tunggu hingga layanan berstatus **Running**, lalu buka **Connection information**. Salin host, port, user, password, dan CA certificate. Gunakan database `defaultdb` untuk menjalankan SQL awal bila perlu.
3. Jalankan `database/schema_sia_kelompok1.sql` melalui MySQL CLI/SQL client Aiven. Skrip membuat database terpisah `sia_malaka_kelompok1` dan tabel aplikasinya.
4. Atur endpoint publik serta IP allowlist sesuai alamat keluar server. Jangan membuka akses ke seluruh internet kecuali memang dibutuhkan dan dipahami risikonya; wajibkan TLS.
5. Untuk XAMPP, salin `config/database.example.php` menjadi `config/database.php`; isi host, port, nama database `sia_malaka_kelompok1`, user, password, dan lokasi CA yang diunduh sebagai `config/aiven-ca.pem`. Jangan commit `config/database.php`.
6. Di Vercel, atur `DB_HOST`, `DB_PORT`, `DB_NAME`, `DB_USER`, `DB_PASSWORD`, `DB_SSL=1`, dan `DB_SSL_CA=config/aiven-ca.pem` untuk environment Production. Tandai password sebagai secret.

## Menjalankan aplikasi dengan XAMPP

1. Salin folder proyek ini ke `C:\xampp\htdocs\sia-smk-malaka-kelompok1`.
2. Jalankan **Apache** dari XAMPP Control Panel. MySQL lokal tidak perlu dijalankan bila aplikasi menggunakan database daring.
3. Siapkan koneksi daring seperti bagian di atas. Untuk menguji XAMPP, atur environment PHP/Apache agar dapat membaca variabel koneksi, atau isi konfigurasi lokal `config/database.php`.
4. Foto disimpan sebagai BLOB pada database sehingga tidak hilang ketika deployment Vercel mengganti instance.
5. Buka `http://localhost/sia-smk-malaka-kelompok1/`.

Ekstensi PHP `pdo_mysql` dan `fileinfo` diperlukan. Pastikan `upload_max_filesize` di `php.ini` sedikitnya `2M` dan `post_max_size` lebih besar dari batas tersebut. Unggahan foto dibatasi 2 MB dan hanya menerima JPG/JPEG, PNG, dan WebP.

## Struktur data

- `siswa`: NIS, nama, kelas, dan foto opsional sebagai BLOB.
- `pelajaran`: kode, nama, dan bobot.
- `mempelajari`: nomor nilai otomatis, NIS, kode pelajaran, dan nilai 0–100.
- Satu pasangan siswa-pelajaran hanya dapat memiliki satu nilai. Foreign key membatasi penghapusan data master yang masih dipakai.

Skema tidak mengisi data contoh otomatis; statistik dashboard dan daftar akan mencerminkan data sebenarnya.

## Pengujian

Jalankan pemeriksaan validasi mandiri dengan `php tests/validation_test.php`. Setelah Aiven dikonfigurasi, jalankan `php tests/database_smoke.php` untuk menguji create/read/update/delete, pencarian, duplikasi pasangan nilai, rentang nilai, foreign key, dan pencegahan penghapusan master. Tes database membungkus data uji dalam transaksi lalu melakukan rollback. Pemeriksaan sintaks semua berkas dapat dilakukan dengan `php -l` pada tiap berkas PHP. Tes smoke basis data bukan pengganti uji alur melalui browser dan CSRF.
