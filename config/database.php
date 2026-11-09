<?php
/**
 * Database Configuration & Connection
 * SPKK Hipertensi Kehamilan (CDSS)
 */

// Support environment variables from Railway, Cloud, or fallback to Laragon localhost
$envHost = getenv('DB_HOST') ?: (getenv('MYSQLHOST') ?: 'localhost');
$envPort = getenv('DB_PORT') ?: (getenv('MYSQLPORT') ?: '3306');
$envName = getenv('DB_NAME') ?: (getenv('MYSQLDATABASE') ?: 'spkk_hipertensi');
$envUser = getenv('DB_USER') ?: (getenv('MYSQLUSER') ?: 'root');
$envPass = getenv('DB_PASS') !== false ? getenv('DB_PASS') : (getenv('MYSQLPASSWORD') !== false ? getenv('MYSQLPASSWORD') : '');

define('DB_HOST', $envHost);
define('DB_PORT', $envPort);
define('DB_NAME', $envName);
define('DB_USER', $envUser);
define('DB_PASS', $envPass);

function getDBConnection() {
    static $pdo = null;
    if ($pdo === null) {
        $dsn = "mysql:host=" . DB_HOST . ";port=" . DB_PORT . ";dbname=" . DB_NAME . ";charset=utf8mb4";
        $options = [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
        ];
        try {
            $pdo = new PDO($dsn, DB_USER, DB_PASS, $options);
        } catch (PDOException $e) {
            // Attempt to connect without dbname and create it if missing (useful on localhost/Laragon)
            try {
                $rootDsn = "mysql:host=" . DB_HOST . ";port=" . DB_PORT . ";charset=utf8mb4";
                $rootPdo = new PDO($rootDsn, DB_USER, DB_PASS, $options);
                $rootPdo->exec("CREATE DATABASE IF NOT EXISTS `" . DB_NAME . "` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
                $pdo = new PDO($dsn, DB_USER, DB_PASS, $options);
            } catch (PDOException $ex) {
                die("Koneksi Database Gagal: " . $e->getMessage() . " (Host: " . DB_HOST . ", DB: " . DB_NAME . ")");
            }
        }
    }
    return $pdo;
}

/**
 * Ensure all necessary tables and extended columns exist
 */
function checkAndMigrateSchema() {
    $pdo = getDBConnection();

    // 1. Table Alternatif
    $pdo->exec("CREATE TABLE IF NOT EXISTS `alternatif` (
        `id_alternatif` INT NOT NULL AUTO_INCREMENT,
        `kode_alternatif` VARCHAR(10) NULL,
        `nama_alternatif` VARCHAR(100) NOT NULL,
        `level_risiko` VARCHAR(50) NULL,
        `rekomendasi` TEXT NULL,
        `keterangan` VARCHAR(255) DEFAULT NULL,
        PRIMARY KEY (`id_alternatif`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");

    // Add columns if table already existed without them
    $cols = $pdo->query("SHOW COLUMNS FROM `alternatif`")->fetchAll(PDO::FETCH_COLUMN);
    if (!in_array('kode_alternatif', $cols)) {
        $pdo->exec("ALTER TABLE `alternatif` ADD COLUMN `kode_alternatif` VARCHAR(10) NULL AFTER `id_alternatif`");
    }
    if (!in_array('level_risiko', $cols)) {
        $pdo->exec("ALTER TABLE `alternatif` ADD COLUMN `level_risiko` VARCHAR(50) NULL AFTER `nama_alternatif`");
    }
    if (!in_array('rekomendasi', $cols)) {
        $pdo->exec("ALTER TABLE `alternatif` ADD COLUMN `rekomendasi` TEXT NULL AFTER `level_risiko`");
    }

    // 2. Table Kriteria
    $pdo->exec("CREATE TABLE IF NOT EXISTS `kriteria` (
        `id_kriteria` INT NOT NULL AUTO_INCREMENT,
        `kode_kriteria` VARCHAR(10) NULL,
        `nama_kriteria` VARCHAR(100) NOT NULL,
        `bobot` DECIMAL(5,2) DEFAULT 1.00,
        `sub_nilai` TEXT NULL,
        `keterangan` VARCHAR(255) DEFAULT NULL,
        PRIMARY KEY (`id_kriteria`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");

    $cols = $pdo->query("SHOW COLUMNS FROM `kriteria`")->fetchAll(PDO::FETCH_COLUMN);
    if (!in_array('kode_kriteria', $cols)) {
        $pdo->exec("ALTER TABLE `kriteria` ADD COLUMN `kode_kriteria` VARCHAR(10) NULL AFTER `id_kriteria`");
    }
    if (!in_array('sub_nilai', $cols)) {
        $pdo->exec("ALTER TABLE `kriteria` ADD COLUMN `sub_nilai` TEXT NULL AFTER `bobot`");
    }
    if (!in_array('keterangan', $cols)) {
        $pdo->exec("ALTER TABLE `kriteria` ADD COLUMN `keterangan` VARCHAR(255) NULL AFTER `sub_nilai`");
    }

    // 3. Table Pasien
    $pdo->exec("CREATE TABLE IF NOT EXISTS `pasien` (
        `id_pasien` INT NOT NULL AUTO_INCREMENT,
        `no_rm` VARCHAR(30) NULL,
        `nama_pasien` VARCHAR(100) NOT NULL,
        `umur` INT DEFAULT NULL,
        `usia_kehamilan` INT DEFAULT NULL,
        `alamat` VARCHAR(200) DEFAULT NULL,
        `no_telp` VARCHAR(20) DEFAULT NULL,
        `gravida` VARCHAR(20) DEFAULT 'G1P0A0',
        `tanggal_daftar` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY (`id_pasien`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");

    $cols = $pdo->query("SHOW COLUMNS FROM `pasien`")->fetchAll(PDO::FETCH_COLUMN);
    if (!in_array('no_rm', $cols)) {
        $pdo->exec("ALTER TABLE `pasien` ADD COLUMN `no_rm` VARCHAR(30) NULL AFTER `id_pasien`");
    }
    if (!in_array('no_telp', $cols)) {
        $pdo->exec("ALTER TABLE `pasien` ADD COLUMN `no_telp` VARCHAR(20) NULL AFTER `alamat`");
    }
    if (!in_array('gravida', $cols)) {
        $pdo->exec("ALTER TABLE `pasien` ADD COLUMN `gravida` VARCHAR(20) DEFAULT 'G1P0A0' AFTER `no_telp`");
    }
    if (!in_array('tanggal_daftar', $cols)) {
        $pdo->exec("ALTER TABLE `pasien` ADD COLUMN `tanggal_daftar` TIMESTAMP DEFAULT CURRENT_TIMESTAMP AFTER `gravida`");
    }

    // 4. Table Pemeriksaan
    $pdo->exec("CREATE TABLE IF NOT EXISTS `pemeriksaan` (
        `id_pemeriksaan` INT NOT NULL AUTO_INCREMENT,
        `id_pasien` INT NOT NULL,
        `tekanan_sistolik` INT DEFAULT NULL,
        `tekanan_diastolik` INT DEFAULT NULL,
        `proteinuria` VARCHAR(20) DEFAULT 'Negatif',
        `edema` VARCHAR(20) DEFAULT 'Tidak',
        `gejala` VARCHAR(255) DEFAULT NULL,
        `sakit_kepala` VARCHAR(10) DEFAULT 'Tidak',
        `gangguan_penglihatan` VARCHAR(10) DEFAULT 'Tidak',
        `nyeri_ulu_hati` VARCHAR(10) DEFAULT 'Tidak',
        `trombosit_rendah` VARCHAR(10) DEFAULT 'Tidak',
        `fungsi_hati` VARCHAR(10) DEFAULT 'Tidak',
        `fungsi_ginjal` VARCHAR(10) DEFAULT 'Tidak',
        `riwayat_hipertensi` VARCHAR(10) DEFAULT 'Tidak',
        `riwayat_preeklampsia` VARCHAR(10) DEFAULT 'Tidak',
        `kejang` VARCHAR(10) DEFAULT 'Tidak',
        `kehamilan_kembar` VARCHAR(10) DEFAULT 'Tidak',
        `catatan_medis` TEXT DEFAULT NULL,
        `tanggal_pemeriksaan` DATE DEFAULT NULL,
        PRIMARY KEY (`id_pemeriksaan`),
        KEY `id_pasien` (`id_pasien`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");

    $cols = $pdo->query("SHOW COLUMNS FROM `pemeriksaan`")->fetchAll(PDO::FETCH_COLUMN);
    $extra_pem_cols = [
        'sakit_kepala' => "VARCHAR(10) DEFAULT 'Tidak'",
        'gangguan_penglihatan' => "VARCHAR(10) DEFAULT 'Tidak'",
        'nyeri_ulu_hati' => "VARCHAR(10) DEFAULT 'Tidak'",
        'trombosit_rendah' => "VARCHAR(10) DEFAULT 'Tidak'",
        'fungsi_hati' => "VARCHAR(10) DEFAULT 'Tidak'",
        'fungsi_ginjal' => "VARCHAR(10) DEFAULT 'Tidak'",
        'riwayat_hipertensi' => "VARCHAR(10) DEFAULT 'Tidak'",
        'riwayat_preeklampsia' => "VARCHAR(10) DEFAULT 'Tidak'",
        'kejang' => "VARCHAR(10) DEFAULT 'Tidak'",
        'kehamilan_kembar' => "VARCHAR(10) DEFAULT 'Tidak'",
        'catatan_medis' => "TEXT DEFAULT NULL"
    ];
    foreach ($extra_pem_cols as $cName => $cDef) {
        if (!in_array($cName, $cols)) {
            $pdo->exec("ALTER TABLE `pemeriksaan` ADD COLUMN `$cName` $cDef AFTER `gejala`");
        }
    }

    // 5. Table Hasil Keputusan
    $pdo->exec("CREATE TABLE IF NOT EXISTS `hasil_keputusan` (
        `id_hasil` INT NOT NULL AUTO_INCREMENT,
        `id_pasien` INT NOT NULL,
        `id_alternatif` INT NOT NULL,
        `id_pemeriksaan` INT DEFAULT NULL,
        `nilai` DECIMAL(10,2) DEFAULT NULL,
        `keputusan` VARCHAR(100) DEFAULT NULL,
        `level_risiko` VARCHAR(50) DEFAULT NULL,
        `rule_terpicu` VARCHAR(100) DEFAULT NULL,
        `rekomendasi` TEXT DEFAULT NULL,
        `trace_log` TEXT DEFAULT NULL,
        `tanggal` DATE DEFAULT NULL,
        PRIMARY KEY (`id_hasil`),
        KEY `id_pasien` (`id_pasien`),
        KEY `id_alternatif` (`id_alternatif`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");

    $cols = $pdo->query("SHOW COLUMNS FROM `hasil_keputusan`")->fetchAll(PDO::FETCH_COLUMN);
    $extra_hk_cols = [
        'id_pemeriksaan' => "INT DEFAULT NULL",
        'level_risiko' => "VARCHAR(50) DEFAULT NULL",
        'rule_terpicu' => "VARCHAR(100) DEFAULT NULL",
        'rekomendasi' => "TEXT DEFAULT NULL",
        'trace_log' => "TEXT DEFAULT NULL"
    ];
    foreach ($extra_hk_cols as $cName => $cDef) {
        if (!in_array($cName, $cols)) {
            $pdo->exec("ALTER TABLE `hasil_keputusan` ADD COLUMN `$cName` $cDef AFTER `keputusan`");
        }
    }

    // 6. Table Users (Authentication)
    $pdo->exec("CREATE TABLE IF NOT EXISTS `users` (
        `id_user` INT NOT NULL AUTO_INCREMENT,
        `username` VARCHAR(60) NOT NULL UNIQUE,
        `password` VARCHAR(255) NOT NULL,
        `nama_lengkap` VARCHAR(100) NOT NULL,
        `role` ENUM('admin','dokter','bidan') NOT NULL DEFAULT 'bidan',
        `is_active` TINYINT(1) NOT NULL DEFAULT 1,
        `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY (`id_user`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");

    // Seed default admin account if no users exist
    $userCount = (int)$pdo->query("SELECT COUNT(*) FROM `users`")->fetchColumn();
    if ($userCount === 0) {
        $defaultPassword = password_hash('admin123', PASSWORD_BCRYPT);
        $pdo->exec("INSERT INTO `users` (`username`, `password`, `nama_lengkap`, `role`) VALUES
            ('admin', '$defaultPassword', 'Administrator CDSS', 'admin'),
            ('dokter1', '" . password_hash('dokter123', PASSWORD_BCRYPT) . "', 'dr. Sp.OG PONEK', 'dokter'),
            ('bidan1', '" . password_hash('bidan123', PASSWORD_BCRYPT) . "', 'Bidan Koordinator', 'bidan')
        ");
    }
}
