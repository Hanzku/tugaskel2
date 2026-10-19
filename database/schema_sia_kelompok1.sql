CREATE DATABASE IF NOT EXISTS sia_malaka_kelompok1
  CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE sia_malaka_kelompok1;

CREATE TABLE IF NOT EXISTS siswa (
  nis VARCHAR(20) NOT NULL,
  nama VARCHAR(100) NOT NULL,
  kelas VARCHAR(30) NOT NULL,
  foto MEDIUMBLOB NULL,
  PRIMARY KEY (nis)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS pelajaran (
  kd_pelajaran VARCHAR(20) NOT NULL,
  nama_pelajaran VARCHAR(100) NOT NULL,
  bobot DECIMAL(5,2) NOT NULL,
  PRIMARY KEY (kd_pelajaran),
  CONSTRAINT chk_pelajaran_bobot CHECK (bobot BETWEEN 1 AND 100)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS mempelajari (
  nomornilai BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  nis VARCHAR(20) NOT NULL,
  kd_pelajaran VARCHAR(20) NOT NULL,
  nilai DECIMAL(5,2) NOT NULL,
  PRIMARY KEY (nomornilai),
  CONSTRAINT uq_mempelajari_siswa_pelajaran UNIQUE (nis, kd_pelajaran),
  CONSTRAINT chk_mempelajari_nilai CHECK (nilai BETWEEN 0 AND 100),
  CONSTRAINT fk_mempelajari_siswa FOREIGN KEY (nis)
    REFERENCES siswa (nis) ON UPDATE CASCADE ON DELETE RESTRICT,
  CONSTRAINT fk_mempelajari_pelajaran FOREIGN KEY (kd_pelajaran)
    REFERENCES pelajaran (kd_pelajaran) ON UPDATE CASCADE ON DELETE RESTRICT,
  INDEX idx_mempelajari_pelajaran (kd_pelajaran)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
