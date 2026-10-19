# tugaskel1 — SIA SMK Malaka, Kelompok 1: Mempelajari

Aplikasi administrasi akademik berbasis PHP 8+, MySQL/MariaDB, PDO, HTML, CSS, dan JavaScript vanilla. Modul yang tersedia adalah Dashboard, Siswa, Pelajaran, dan Mempelajari. Tidak ada modul Diampu.

## Menyiapkan MySQL daring

Aplikasi ini disiapkan untuk TiDB Cloud Starter (MySQL-compatible) melalui endpoint publik TLS. Kredensial database tidak disimpan di repository.

1. Buat instance **TiDB Cloud Starter** pada konsol TiDB Cloud. Pilih instance MySQL-compatible.
2. Dari **Connect**, pilih **Public** dan metode MySQL/PHP. Catat host, port (biasanya `4000`), nama database, serta username tepat seperti yang diberikan. Username Starter dapat memuat prefix akun; jangan menghapus prefix tersebut.
3. Buat atau ambil password database dari konsol, lalu masukkan nilai koneksi itu ke konfigurasi lokal. Jangan bagikan kata sandi dan jangan commit `config/database.php`.
4. Atur IP Access List TiDB agar mengizinkan alamat IP keluar komputer/server yang menjalankan PHP. Gunakan alamat yang sesempit mungkin, bukan membuka akses untuk semua IP.
5. Jalankan `database/schema_sia_kelompok1.sql` melalui SQL Editor/SQL Shell TiDB. Jika database sudah dipilih atau penyedia tidak mengizinkan `CREATE DATABASE`, lewati `CREATE DATABASE` dan `USE`, lalu jalankan pernyataan `CREATE TABLE`.
6. Salin `config/database.example.php` menjadi `config/database.php`, lalu isi `host`, `name`, `user`, dan `password`. Port 4000, TLS, serta CA ISRG Root X1 sudah disiapkan sebagai default; gunakan berkas CA hanya untuk validasi sertifikat, jangan menonaktifkan TLS.
7. Alternatif environment variables: `DB_HOST`, `DB_PORT`, `DB_NAME`, `DB_USER`, `DB_PASSWORD`, `DB_SSL=1`, dan `DB_SSL_CA`. CA publik ISRG Root X1 tersedia di `config/isrgrootx1.pem`.

Detail koneksi dan IP Access List dapat berubah; ikuti connection dialog pada konsol serta dokumentasi resmi [koneksi TiDB Cloud Starter/Essential](https://docs.pingcap.com/tidbcloud/connect-to-tidb-cluster-serverless/) dan [TLS TiDB Cloud](https://docs.pingcap.com/tidbcloud/secure-connections-to-serverless-clusters/).

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

Jalankan pemeriksaan validasi mandiri dengan `php tests/validation_test.php`. Setelah TiDB Cloud dikonfigurasi, jalankan `php tests/database_smoke.php` untuk menguji create/read/update/delete, pencarian, duplikasi pasangan nilai, rentang nilai, foreign key, dan pencegahan penghapusan master. Tes database membungkus data uji dalam transaksi lalu melakukan rollback. Pemeriksaan sintaks semua berkas dapat dilakukan dengan `php -l` pada tiap berkas PHP. Tes smoke basis data bukan pengganti uji alur melalui browser dan CSRF.
