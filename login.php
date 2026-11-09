<?php
/**
 * Login Page - SPKK Hipertensi Kehamilan (CDSS)
 */
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Already logged in → redirect to dashboard
if (isset($_SESSION['user']) && !empty($_SESSION['user']['id_user'])) {
    header('Location: index.php');
    exit;
}

require_once __DIR__ . '/config/database.php';
// Ensure schema (and default users) are created
checkAndMigrateSchema();

$error   = '';
$success = '';

// Show logout success message
if (isset($_GET['logout'])) {
    $success = 'Anda telah berhasil keluar dari sistem. Sampai jumpa!';
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = trim($_POST['password'] ?? '');

    if ($username === '' || $password === '') {
        $error = 'Username dan password tidak boleh kosong.';
    } else {
        $pdo  = getDBConnection();
        $stmt = $pdo->prepare("SELECT * FROM `users` WHERE `username` = ? AND `is_active` = 1 LIMIT 1");
        $stmt->execute([$username]);
        $user = $stmt->fetch();

        if ($user && password_verify($password, $user['password'])) {
            // Regenerate session ID to prevent fixation
            session_regenerate_id(true);
            $_SESSION['user'] = [
                'id_user'      => $user['id_user'],
                'username'     => $user['username'],
                'nama_lengkap' => $user['nama_lengkap'],
                'role'         => $user['role'],
            ];

            $redirect = $_SESSION['redirect_after_login'] ?? 'index.php';
            unset($_SESSION['redirect_after_login']);
            header('Location: ' . $redirect);
            exit;
        } else {
            $error = 'Username atau password salah. Silakan coba lagi.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login — CDSS Hipertensi Kehamilan | SPKK Maternal</title>
    <meta name="description" content="Masuk ke Clinical Decision Support System (CDSS) Hipertensi Kehamilan">

    <!-- Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@400;500;600;700;800&family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">

    <!-- FontAwesome 6 -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">

    <!-- Main CSS -->
    <link rel="stylesheet" href="assets/css/style.css">

    <style>
        /* =============================================
           LOGIN PAGE — Standalone Full-Screen Layout
           ============================================= */
        body {
            background: linear-gradient(135deg, #092c28 0%, #0f3d37 40%, #134e48 70%, #0e7490 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 24px;
            position: relative;
            overflow: hidden;
        }

        /* Decorative background blobs */
        body::before {
            content: '';
            position: fixed;
            top: -120px;
            right: -120px;
            width: 500px;
            height: 500px;
            border-radius: 50%;
            background: radial-gradient(circle, rgba(20,184,166,0.18) 0%, transparent 70%);
            pointer-events: none;
        }
        body::after {
            content: '';
            position: fixed;
            bottom: -100px;
            left: -100px;
            width: 420px;
            height: 420px;
            border-radius: 50%;
            background: radial-gradient(circle, rgba(6,182,212,0.12) 0%, transparent 70%);
            pointer-events: none;
        }

        .login-wrapper {
            display: flex;
            width: 100%;
            max-width: 960px;
            min-height: 560px;
            border-radius: 24px;
            overflow: hidden;
            box-shadow: 0 30px 80px rgba(0,0,0,0.45);
            position: relative;
            z-index: 1;
        }

        /* ----- Left Panel (Branding) ----- */
        .login-left {
            flex: 1;
            background: linear-gradient(160deg, #094d47 0%, #0f766e 55%, #0891b2 100%);
            padding: 52px 44px;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
            position: relative;
            overflow: hidden;
        }
        .login-left::before {
            content: '';
            position: absolute;
            top: -60px;
            right: -60px;
            width: 320px;
            height: 320px;
            border-radius: 50%;
            background: rgba(255,255,255,0.06);
            pointer-events: none;
        }
        .login-left::after {
            content: '';
            position: absolute;
            bottom: -80px;
            left: -40px;
            width: 260px;
            height: 260px;
            border-radius: 50%;
            background: rgba(255,255,255,0.04);
            pointer-events: none;
        }

        .brand-logo {
            display: flex;
            align-items: center;
            gap: 16px;
            position: relative;
            z-index: 2;
        }
        .brand-logo-icon {
            width: 56px;
            height: 56px;
            background: rgba(255,255,255,0.18);
            border: 2px solid rgba(255,255,255,0.3);
            border-radius: 16px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 26px;
            color: #ffffff;
            backdrop-filter: blur(8px);
        }
        .brand-name {
            font-family: 'Outfit', sans-serif;
            font-size: 22px;
            font-weight: 800;
            color: #ffffff;
            line-height: 1.1;
            letter-spacing: -0.3px;
        }
        .brand-name span {
            display: block;
            font-size: 12px;
            font-weight: 500;
            color: rgba(255,255,255,0.7);
            letter-spacing: 0.5px;
        }

        .login-hero-content {
            position: relative;
            z-index: 2;
        }
        .login-hero-tag {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            background: rgba(255,255,255,0.12);
            border: 1px solid rgba(255,255,255,0.2);
            padding: 5px 14px;
            border-radius: 99px;
            font-size: 11.5px;
            font-weight: 700;
            color: rgba(255,255,255,0.85);
            letter-spacing: 0.5px;
            margin-bottom: 20px;
        }
        .login-hero-title {
            font-family: 'Outfit', sans-serif;
            font-size: 32px;
            font-weight: 800;
            color: #ffffff;
            line-height: 1.2;
            margin-bottom: 16px;
            letter-spacing: -0.5px;
        }
        .login-hero-title em {
            font-style: normal;
            color: #5eead4;
        }
        .login-hero-desc {
            font-size: 14px;
            color: rgba(255,255,255,0.72);
            line-height: 1.7;
            margin-bottom: 28px;
        }
        .login-feature-list {
            list-style: none;
            display: flex;
            flex-direction: column;
            gap: 10px;
        }
        .login-feature-list li {
            display: flex;
            align-items: center;
            gap: 10px;
            font-size: 13px;
            color: rgba(255,255,255,0.78);
        }
        .login-feature-list li i {
            width: 28px;
            height: 28px;
            background: rgba(255,255,255,0.14);
            border-radius: 8px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 12px;
            color: #5eead4;
            flex-shrink: 0;
        }

        .login-left-footer {
            font-size: 11px;
            color: rgba(255,255,255,0.4);
            position: relative;
            z-index: 2;
        }

        /* ----- Right Panel (Form) ----- */
        .login-right {
            width: 420px;
            flex-shrink: 0;
            background: #ffffff;
            padding: 48px 44px;
            display: flex;
            flex-direction: column;
            justify-content: center;
        }

        .login-form-header {
            margin-bottom: 32px;
        }
        .login-form-title {
            font-family: 'Outfit', sans-serif;
            font-size: 26px;
            font-weight: 800;
            color: #0f172a;
            margin-bottom: 6px;
            letter-spacing: -0.3px;
        }
        .login-form-subtitle {
            font-size: 13.5px;
            color: #64748b;
        }

        .login-form-group {
            margin-bottom: 20px;
        }
        .login-form-label {
            display: block;
            font-size: 13px;
            font-weight: 700;
            color: #374151;
            margin-bottom: 8px;
        }
        .login-input-wrap {
            position: relative;
        }
        .login-input-icon {
            position: absolute;
            left: 14px;
            top: 50%;
            transform: translateY(-50%);
            font-size: 15px;
            color: #94a3b8;
            pointer-events: none;
        }
        .login-input {
            width: 100%;
            padding: 12px 16px 12px 42px;
            font-family: 'Plus Jakarta Sans', sans-serif;
            font-size: 14px;
            font-weight: 500;
            color: #0f172a;
            background: #f8fafc;
            border: 1.5px solid #e2e8f0;
            border-radius: 10px;
            outline: none;
            transition: border-color 0.2s, box-shadow 0.2s, background 0.2s;
        }
        .login-input:focus {
            border-color: #0d9488;
            box-shadow: 0 0 0 3px rgba(13,148,136,0.12);
            background: #ffffff;
        }
        .login-input.is-error {
            border-color: #ef4444;
            box-shadow: 0 0 0 3px rgba(239,68,68,0.1);
        }
        .toggle-password {
            position: absolute;
            right: 14px;
            top: 50%;
            transform: translateY(-50%);
            background: none;
            border: none;
            cursor: pointer;
            color: #94a3b8;
            font-size: 15px;
            padding: 0;
            line-height: 1;
            transition: color 0.2s;
        }
        .toggle-password:hover { color: #0d9488; }

        .login-alert {
            display: flex;
            align-items: flex-start;
            gap: 10px;
            padding: 12px 14px;
            border-radius: 10px;
            font-size: 13px;
            font-weight: 500;
            margin-bottom: 20px;
            animation: fadeSlideIn 0.3s ease;
        }
        .login-alert-error {
            background: #fef2f2;
            border: 1px solid #fecaca;
            color: #b91c1c;
        }
        .login-alert-success {
            background: #f0fdf4;
            border: 1px solid #bbf7d0;
            color: #166534;
        }

        .login-btn {
            width: 100%;
            padding: 13px;
            background: linear-gradient(135deg, #0f766e 0%, #0d9488 50%, #06b6d4 100%);
            color: #ffffff;
            font-family: 'Plus Jakarta Sans', sans-serif;
            font-size: 15px;
            font-weight: 700;
            border: none;
            border-radius: 10px;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            transition: all 0.2s ease;
            box-shadow: 0 4px 14px rgba(15,118,110,0.3);
            margin-bottom: 20px;
        }
        .login-btn:hover {
            transform: translateY(-1px);
            box-shadow: 0 6px 20px rgba(15,118,110,0.4);
        }
        .login-btn:active { transform: translateY(0); }

        .login-hint {
            background: #f0fdfa;
            border: 1px solid #99f6e4;
            border-radius: 10px;
            padding: 12px 14px;
            font-size: 12px;
            color: #0f766e;
        }
        .login-hint strong {
            display: block;
            font-weight: 700;
            margin-bottom: 4px;
            color: #115e59;
        }
        .login-hint code {
            background: #ccfbf1;
            padding: 1px 6px;
            border-radius: 4px;
            font-size: 11.5px;
            font-family: monospace;
        }

        @keyframes fadeSlideIn {
            from { opacity: 0; transform: translateY(-8px); }
            to   { opacity: 1; transform: translateY(0); }
        }

        /* Responsive */
        @media (max-width: 768px) {
            .login-wrapper { flex-direction: column; max-width: 440px; }
            .login-left { display: none; }
            .login-right { width: 100%; padding: 36px 28px; }
        }
    </style>
</head>
<body>

<div class="login-wrapper">
    <!-- Left Branding Panel -->
    <div class="login-left">
        <div class="brand-logo">
            <div class="brand-logo-icon">
                <i class="fa-solid fa-heart-pulse"></i>
            </div>
            <div class="brand-name">
                CDSS MATERNAL
                <span>SPKK Hipertensi Kehamilan</span>
            </div>
        </div>

        <div class="login-hero-content">
            <div class="login-hero-tag">
                <i class="fa-solid fa-shield-halved"></i> CLINICAL DECISION SUPPORT SYSTEM
            </div>
            <h1 class="login-hero-title">
                Sistem Pendukung<br>Keputusan Klinis<br><em>Hipertensi Kehamilan</em>
            </h1>
            <p class="login-hero-desc">
                Platform berbasis <strong style="color:#fff;">Rule-Based Forward Chaining</strong> untuk deteksi dini, klasifikasi, dan panduan tata laksana hipertensi pada ibu hamil berisiko tinggi.
            </p>
            <ul class="login-feature-list">
                <li>
                    <i class="fa-solid fa-brain"></i>
                    Inferensi 7 Kategori Diagnosis Klinis (A1–A7)
                </li>
                <li>
                    <i class="fa-solid fa-stethoscope"></i>
                    Evaluasi 16 Kriteria Klinis (C1–C16)
                </li>
                <li>
                    <i class="fa-solid fa-truck-medical"></i>
                    Protokol Kegawatdaruratan PONEK & MgSO4
                </li>
                <li>
                    <i class="fa-solid fa-chart-pie"></i>
                    Dashboard Analitik Real-Time
                </li>
            </ul>
        </div>

        <div class="login-left-footer">
            Standar Pedoman: POGI & WHO — Maternal Emergency Protocol
        </div>
    </div>

    <!-- Right Form Panel -->
    <div class="login-right">
        <div class="login-form-header">
            <h2 class="login-form-title">Selamat Datang</h2>
            <p class="login-form-subtitle">Masuk ke dasbor klinis CDSS untuk melanjutkan.</p>
        </div>

        <?php if ($error): ?>
            <div class="login-alert login-alert-error">
                <i class="fa-solid fa-circle-exclamation" style="font-size:16px; margin-top:1px; flex-shrink:0;"></i>
                <span><?= htmlspecialchars($error) ?></span>
            </div>
        <?php endif; ?>

        <?php if ($success): ?>
            <div class="login-alert login-alert-success">
                <i class="fa-solid fa-circle-check" style="font-size:16px; margin-top:1px; flex-shrink:0;"></i>
                <span><?= htmlspecialchars($success) ?></span>
            </div>
        <?php endif; ?>

        <form method="POST" action="login.php" autocomplete="off" novalidate>
            <div class="login-form-group">
                <label class="login-form-label" for="username">
                    Username
                </label>
                <div class="login-input-wrap">
                    <i class="fa-solid fa-user login-input-icon"></i>
                    <input
                        type="text"
                        id="username"
                        name="username"
                        class="login-input <?= $error ? 'is-error' : '' ?>"
                        placeholder="Masukkan username..."
                        value="<?= htmlspecialchars($_POST['username'] ?? '') ?>"
                        autocomplete="username"
                        required
                        autofocus
                    >
                </div>
            </div>

            <div class="login-form-group">
                <label class="login-form-label" for="password">
                    Password
                </label>
                <div class="login-input-wrap">
                    <i class="fa-solid fa-lock login-input-icon"></i>
                    <input
                        type="password"
                        id="password"
                        name="password"
                        class="login-input <?= $error ? 'is-error' : '' ?>"
                        placeholder="Masukkan password..."
                        autocomplete="current-password"
                        required
                    >
                    <button type="button" class="toggle-password" onclick="togglePwd()" aria-label="Tampilkan/sembunyikan password">
                        <i class="fa-solid fa-eye" id="pwdIcon"></i>
                    </button>
                </div>
            </div>

            <button type="submit" class="login-btn">
                <i class="fa-solid fa-right-to-bracket"></i>
                Masuk ke Sistem
            </button>
        </form>

        <div class="login-hint">
            <strong><i class="fa-solid fa-circle-info"></i> Akun Default (Development)</strong>
            Admin: <code>admin</code> / <code>admin123</code> &nbsp;|&nbsp;
            Dokter: <code>dokter1</code> / <code>dokter123</code> &nbsp;|&nbsp;
            Bidan: <code>bidan1</code> / <code>bidan123</code>
        </div>
    </div>
</div>

<script>
function togglePwd() {
    const input = document.getElementById('password');
    const icon  = document.getElementById('pwdIcon');
    if (input.type === 'password') {
        input.type = 'text';
        icon.className = 'fa-solid fa-eye-slash';
    } else {
        input.type = 'password';
        icon.className = 'fa-solid fa-eye';
    }
}
</script>
</body>
</html>
