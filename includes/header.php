<?php
/**
 * Header Template - SPKK Hipertensi Kehamilan (CDSS)
 * Medical Dashboard Layout with Sidebar & Topbar
 */
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Auth guard — redirect to login if no valid session
if (!isset($_SESSION['user']) || empty($_SESSION['user']['id_user'])) {
    $_SESSION['redirect_after_login'] = $_SERVER['REQUEST_URI'];
    header('Location: login.php');
    exit;
}

$currentUser = $_SESSION['user'];
$currentPage = basename($_SERVER['PHP_SELF']);

// Check for emergency count in database for the topbar notification bell
$emergencyCount = 0;
if (function_exists('getDBConnection')) {
    try {
        $pConn = getDBConnection();
        $emergencyCount = (int)$pConn->query("SELECT COUNT(*) FROM `hasil_keputusan` WHERE `level_risiko` LIKE '%Sangat Tinggi%' OR `keputusan` = 'Eklampsia' OR `keputusan` = 'Preeklampsia Berat'")->fetchColumn();
    } catch(Exception $e) {
        $emergencyCount = 0;
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= isset($pageTitle) ? htmlspecialchars($pageTitle) . ' - ' : '' ?>CDSS Hipertensi Kehamilan | SPKK Maternal Dashboard</title>
    <meta name="description" content="Clinical Decision Support System (CDSS) untuk Mendukung Penanganan Hipertensi pada Ibu Hamil Berisiko Tinggi Menggunakan Metode Rule-Based System Forward Chaining.">
    
    <!-- Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@400;500;600;700;800&family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    
    <!-- FontAwesome 6 Icons CDN -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    
    <!-- Chart.js for Medical Visualizations -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>

    <!-- Custom CSS -->
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>

<div class="app-layout">
    <!-- Mobile Sidebar Backdrop Overlay -->
    <div class="sidebar-overlay" id="sidebarOverlay" onclick="toggleSidebar()"></div>

    <!-- Collapsible Medical Sidebar -->
    <aside class="app-sidebar" id="appSidebar">
        <!-- Brand Logo Header -->
        <a href="index.php" class="sidebar-brand">
            <div class="sidebar-brand-icon">
                <i class="fa-solid fa-heart-pulse"></i>
            </div>
            <div>
                <div class="sidebar-brand-title">CDSS <span>MATERNAL</span></div>
                <div class="sidebar-brand-subtitle">SPKK Hipertensi Kehamilan</div>
            </div>
        </a>

        <!-- Logged-in User Profile -->
        <div class="sidebar-user">
            <div class="user-avatar">
                <?php
                $roleIcon = match($currentUser['role'] ?? 'bidan') {
                    'admin'  => 'fa-user-shield',
                    'dokter' => 'fa-user-doctor',
                    default  => 'fa-user-nurse',
                };
                ?>
                <i class="fa-solid <?= $roleIcon ?>"></i>
                <div class="user-status-dot" title="Sistem Aktif"></div>
            </div>
            <div class="user-info">
                <span class="user-name"><?= htmlspecialchars($currentUser['nama_lengkap']) ?></span>
                <span class="user-role">
                    <?= match($currentUser['role']) {
                        'admin'  => 'Administrator',
                        'dokter' => 'Dokter Sp.OG',
                        default  => 'Bidan Jaga',
                    } ?>
                </span>
            </div>
        </div>

        <!-- Sidebar Navigation Menu -->
        <nav class="sidebar-nav">
            <div class="nav-section-title">Menu Utama</div>
            
            <a href="index.php" class="sidebar-link <?= ($currentPage === 'index.php') ? 'active' : '' ?>">
                <div class="sidebar-link-content">
                    <i class="fa-solid fa-chart-pie"></i>
                    <span>Dashboard</span>
                </div>
                <span class="sidebar-badge">Utama</span>
            </a>

            <a href="konsultasi.php" class="sidebar-link <?= ($currentPage === 'konsultasi.php') ? 'active' : '' ?>">
                <div class="sidebar-link-content">
                    <i class="fa-solid fa-stethoscope"></i>
                    <span>Skrining CDSS</span>
                </div>
                <span class="sidebar-badge" style="background: rgba(20, 184, 166, 0.25); color: #5eead4;">AI</span>
            </a>

            <div class="nav-section-title">Manajemen Data</div>

            <a href="pasien.php" class="sidebar-link <?= ($currentPage === 'pasien.php') ? 'active' : '' ?>">
                <div class="sidebar-link-content">
                    <i class="fa-solid fa-hospital-user"></i>
                    <span>Data Ibu Hamil</span>
                </div>
            </a>

            <a href="riwayat.php" class="sidebar-link <?= ($currentPage === 'riwayat.php') ? 'active' : '' ?>">
                <div class="sidebar-link-content">
                    <i class="fa-solid fa-clock-rotate-left"></i>
                    <span>Riwayat & Laporan</span>
                </div>
            </a>

            <div class="nav-section-title">Basis Pengetahuan</div>

            <a href="basis_pengetahuan.php" class="sidebar-link <?= ($currentPage === 'basis_pengetahuan.php') ? 'active' : '' ?>">
                <div class="sidebar-link-content">
                    <i class="fa-solid fa-book-medical"></i>
                    <span>Knowledge Base</span>
                </div>
                <span class="sidebar-badge">R1-R6</span>
            </a>

            <?php if (($currentUser['role'] ?? '') === 'admin'): ?>
            <div class="nav-section-title">Administrasi</div>

            <a href="admin.php" class="sidebar-link <?= ($currentPage === 'admin.php') ? 'active' : '' ?>">
                <div class="sidebar-link-content">
                    <i class="fa-solid fa-users-gear"></i>
                    <span>Manajemen Pengguna</span>
                </div>
            </a>
            <?php endif; ?>
        </nav>

        <!-- Sidebar Emergency Help Box -->
        <div class="sidebar-footer-box">
            <div class="emergency-title">
                <i class="fa-solid fa-truck-medical"></i> Rujukan PONEK 24 Jam
            </div>
            <div class="emergency-desc">
                Siagakan MgSO4 dan stabilisasi jalan napas sebelum rujukan pasien Eklampsia / PEB.
            </div>
        </div>
    </aside>

    <!-- Main Content Area -->
    <div class="app-main">
        <!-- Topbar Header -->
        <header class="app-topbar">
            <div class="topbar-left">
                <button class="menu-toggle-btn" onclick="toggleSidebar()" aria-label="Toggle Navigation">
                    <i class="fa-solid fa-bars"></i>
                </button>
                <div class="topbar-breadcrumb">
                    <span class="breadcrumb-title"><?= htmlspecialchars($pageTitle ?? 'Dashboard CDSS') ?></span>
                    <span class="breadcrumb-path">
                        <i class="fa-solid fa-house-chimney-medical" style="font-size: 11px;"></i>
                        <span>SPKK Hipertensi Kehamilan</span>
                        <span>/</span>
                        <span><?= htmlspecialchars($pageTitle ?? 'Dashboard') ?></span>
                    </span>
                </div>
            </div>

            <div class="topbar-right">
                <!-- Live Date & Clock -->
                <div class="topbar-clock no-print">
                    <i class="fa-solid fa-calendar-day" style="color: var(--primary);"></i>
                    <span><?= date('d M Y') ?></span>
                </div>

                <!-- Emergency Notification Bell -->
                <?php if ($emergencyCount > 0): ?>
                    <a href="riwayat.php?level_risiko=Tinggi" class="no-print" style="position: relative; padding: 8px 10px; color: #dc2626; background: #fee2e2; border-radius: var(--radius-sm); text-decoration: none;" title="<?= $emergencyCount ?> Pasien Memerlukan Rujukan / Penanganan Khusus">
                        <i class="fa-solid fa-bell"></i>
                        <span style="position: absolute; top: -5px; right: -5px; background: #dc2626; color: #ffffff; font-size: 10px; font-weight: 800; padding: 2px 6px; border-radius: var(--radius-full); border: 2px solid #ffffff;">
                            <?= $emergencyCount ?>
                        </span>
                    </a>
                <?php endif; ?>

                <!-- Quick Action Button -->
                <a href="konsultasi.php" class="btn btn-primary btn-sm no-print">
                    <i class="fa-solid fa-plus"></i> Skrining Baru
                </a>

                <!-- User Dropdown -->
                <div class="topbar-user-btn no-print" id="topbarUserMenu" onclick="toggleUserMenu()">
                    <div class="topbar-user-avatar">
                        <i class="fa-solid <?= match($currentUser['role'] ?? 'bidan') { 'admin' => 'fa-user-shield', 'dokter' => 'fa-user-doctor', default => 'fa-user-nurse' } ?>"></i>
                    </div>
                    <div class="topbar-user-info">
                        <span class="topbar-user-name"><?= htmlspecialchars($currentUser['nama_lengkap']) ?></span>
                        <span class="topbar-user-role"><?= ucfirst($currentUser['role']) ?></span>
                    </div>
                    <i class="fa-solid fa-chevron-down" style="font-size:11px; color:#94a3b8;"></i>
                    <div class="topbar-dropdown" id="topbarDropdown">
                        <div class="topbar-dropdown-header">
                            <strong><?= htmlspecialchars($currentUser['nama_lengkap']) ?></strong>
                            <span><?= htmlspecialchars($currentUser['username']) ?></span>
                        </div>
                        <a href="logout.php" class="topbar-dropdown-item topbar-dropdown-logout">
                            <i class="fa-solid fa-right-from-bracket"></i> Keluar
                        </a>
                    </div>
                </div>
            </div>
        </header>

        <!-- Main Inner Content Wrapper -->
        <div class="app-content">

        <!-- Flash Notifications -->
        <?php if (isset($_SESSION['flash'])): ?>
            <div class="alert-emergency" style="background: <?= $_SESSION['flash']['type'] === 'success' ? '#ecfdf5' : '#fef2f2' ?>; border-left-color: <?= $_SESSION['flash']['type'] === 'success' ? '#10b981' : '#ef4444' ?>;">
                <div class="alert-emergency-content">
                    <i class="fa-solid <?= $_SESSION['flash']['type'] === 'success' ? 'fa-circle-check' : 'fa-triangle-exclamation' ?>" style="color: <?= $_SESSION['flash']['type'] === 'success' ? '#059669' : '#dc2626' ?>; font-size: 20px;"></i>
                    <div>
                        <div class="alert-emergency-title" style="color: <?= $_SESSION['flash']['type'] === 'success' ? '#065f46' : '#991b1b' ?>;">
                            <?= htmlspecialchars($_SESSION['flash']['title'] ?? 'Pemberitahuan') ?>
                        </div>
                        <div class="alert-emergency-text" style="color: <?= $_SESSION['flash']['type'] === 'success' ? '#047857' : '#b91c1c' ?>;">
                            <?= htmlspecialchars($_SESSION['flash']['message']) ?>
                        </div>
                    </div>
                </div>
                <button onclick="this.parentElement.remove()" style="background:none; border:none; cursor:pointer; color:#64748b; font-size:16px;">
                    <i class="fa-solid fa-xmark"></i>
                </button>
            </div>
            <?php unset($_SESSION['flash']); ?>
        <?php endif; ?>
