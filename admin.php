<?php
$pageTitle = "Manajemen Pengguna";
require_once __DIR__ . '/config/database.php';
$pdo = getDBConnection();

// Only admin role can access this page
if (session_status() === PHP_SESSION_NONE) session_start();
if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'admin') {
    $_SESSION['flash'] = ['type' => 'error', 'title' => 'Akses Ditolak', 'message' => 'Halaman ini hanya dapat diakses oleh Administrator.'];
    header('Location: index.php');
    exit;
}

$errors = [];

// ── ADD USER ────────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'add_user') {
    $username     = trim($_POST['username'] ?? '');
    $nama         = trim($_POST['nama_lengkap'] ?? '');
    $role         = in_array($_POST['role'] ?? '', ['admin','dokter','bidan']) ? $_POST['role'] : 'bidan';
    $pass         = $_POST['password'] ?? '';
    $pass_confirm = $_POST['password_confirm'] ?? '';

    if ($username === '')      $errors[] = 'Username tidak boleh kosong.';
    if ($nama === '')          $errors[] = 'Nama lengkap tidak boleh kosong.';
    if (strlen($pass) < 6)    $errors[] = 'Password minimal 6 karakter.';
    if ($pass !== $pass_confirm) $errors[] = 'Konfirmasi password tidak cocok.';

    if (empty($errors)) {
        // Check duplicate username
        $chk = $pdo->prepare("SELECT COUNT(*) FROM `users` WHERE `username` = ?");
        $chk->execute([$username]);
        if ((int)$chk->fetchColumn() > 0) {
            $errors[] = "Username <strong>$username</strong> sudah digunakan.";
        } else {
            $hash = password_hash($pass, PASSWORD_BCRYPT);
            $stmt = $pdo->prepare("INSERT INTO `users` (`username`, `password`, `nama_lengkap`, `role`) VALUES (?, ?, ?, ?)");
            $stmt->execute([$username, $hash, $nama, $role]);
            $_SESSION['flash'] = ['type' => 'success', 'title' => 'Berhasil', 'message' => "Pengguna $nama berhasil ditambahkan."];
            header('Location: admin.php');
            exit;
        }
    }
}

// ── EDIT USER ───────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'edit_user') {
    $id   = (int)($_POST['id_user'] ?? 0);
    $nama = trim($_POST['nama_lengkap'] ?? '');
    $role = in_array($_POST['role'] ?? '', ['admin','dokter','bidan']) ? $_POST['role'] : 'bidan';
    $pass = $_POST['password'] ?? '';

    if ($nama === '') $errors[] = 'Nama lengkap tidak boleh kosong.';
    if ($pass !== '' && strlen($pass) < 6) $errors[] = 'Password baru minimal 6 karakter.';

    if (empty($errors)) {
        if ($pass !== '') {
            $hash = password_hash($pass, PASSWORD_BCRYPT);
            $stmt = $pdo->prepare("UPDATE `users` SET `nama_lengkap` = ?, `role` = ?, `password` = ? WHERE `id_user` = ?");
            $stmt->execute([$nama, $role, $hash, $id]);
        } else {
            $stmt = $pdo->prepare("UPDATE `users` SET `nama_lengkap` = ?, `role` = ? WHERE `id_user` = ?");
            $stmt->execute([$nama, $role, $id]);
        }
        $_SESSION['flash'] = ['type' => 'success', 'title' => 'Berhasil', 'message' => "Data pengguna berhasil diperbarui."];
        header('Location: admin.php');
        exit;
    }
}

// ── TOGGLE ACTIVE ────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'toggle_active') {
    $id        = (int)($_POST['id_user'] ?? 0);
    $newStatus = (int)($_POST['new_status'] ?? 0);
    // Prevent admin from deactivating themselves
    if ($id !== (int)$_SESSION['user']['id_user']) {
        $pdo->prepare("UPDATE `users` SET `is_active` = ? WHERE `id_user` = ?")->execute([$newStatus, $id]);
        $_SESSION['flash'] = ['type' => 'success', 'title' => 'Berhasil', 'message' => 'Status pengguna diperbarui.'];
    } else {
        $_SESSION['flash'] = ['type' => 'error', 'title' => 'Gagal', 'message' => 'Anda tidak dapat menonaktifkan akun Anda sendiri.'];
    }
    header('Location: admin.php');
    exit;
}

// ── DELETE USER ─────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'delete_user') {
    $id = (int)($_POST['id_user'] ?? 0);
    if ($id !== (int)$_SESSION['user']['id_user']) {
        $pdo->prepare("DELETE FROM `users` WHERE `id_user` = ?")->execute([$id]);
        $_SESSION['flash'] = ['type' => 'success', 'title' => 'Berhasil', 'message' => 'Pengguna berhasil dihapus.'];
    } else {
        $_SESSION['flash'] = ['type' => 'error', 'title' => 'Gagal', 'message' => 'Anda tidak dapat menghapus akun Anda sendiri.'];
    }
    header('Location: admin.php');
    exit;
}

// ── FETCH ALL USERS ──────────────────────────────────────────
$users = $pdo->query("SELECT * FROM `users` ORDER BY `role` ASC, `nama_lengkap` ASC")->fetchAll();

$countAdmin  = 0; $countDokter = 0; $countBidan = 0; $countAktif = 0;
foreach ($users as $u) {
    if ($u['role'] === 'admin')  $countAdmin++;
    if ($u['role'] === 'dokter') $countDokter++;
    if ($u['role'] === 'bidan')  $countBidan++;
    if ($u['is_active'])         $countAktif++;
}

require_once __DIR__ . '/includes/header.php';
?>

<!-- Page Title -->
<div class="page-title-bar">
    <div>
        <h1><i class="fa-solid fa-users-gear" style="color:var(--primary);"></i> Manajemen Pengguna</h1>
        <p>Kelola akun dokter, bidan, dan administrator yang dapat mengakses sistem CDSS.</p>
    </div>
    <div class="page-title-actions">
        <button class="btn btn-primary btn-sm" onclick="openModal('modalAddUser')">
            <i class="fa-solid fa-user-plus"></i> Tambah Pengguna
        </button>
    </div>
</div>

<?php if (!empty($errors)): ?>
    <div class="alert-emergency" style="background:#fef2f2; border-left-color:#ef4444; margin-bottom:20px;">
        <div class="alert-emergency-content">
            <i class="fa-solid fa-circle-exclamation" style="font-size:22px; color:#dc2626;"></i>
            <div>
                <div class="alert-emergency-title" style="color:#991b1b;">Terdapat Kesalahan Input</div>
                <ul style="margin:4px 0 0 16px; font-size:13px; color:#b91c1c;">
                    <?php foreach ($errors as $e): ?>
                        <li><?= $e ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
        </div>
    </div>
<?php endif; ?>

<!-- Summary Stat Tiles -->
<div class="admin-stat-row">
    <div class="admin-stat-tile">
        <div class="admin-stat-icon" style="background:#f0fdfa; color:#0f766e;">
            <i class="fa-solid fa-users"></i>
        </div>
        <div>
            <div class="admin-stat-value"><?= count($users) ?></div>
            <div class="admin-stat-label">Total Pengguna</div>
        </div>
    </div>
    <div class="admin-stat-tile">
        <div class="admin-stat-icon" style="background:#ede9fe; color:#7c3aed;">
            <i class="fa-solid fa-user-shield"></i>
        </div>
        <div>
            <div class="admin-stat-value"><?= $countAdmin ?></div>
            <div class="admin-stat-label">Administrator</div>
        </div>
    </div>
    <div class="admin-stat-tile">
        <div class="admin-stat-icon" style="background:#e0f2fe; color:#0284c7;">
            <i class="fa-solid fa-user-doctor"></i>
        </div>
        <div>
            <div class="admin-stat-value"><?= $countDokter ?></div>
            <div class="admin-stat-label">Dokter</div>
        </div>
    </div>
    <div class="admin-stat-tile">
        <div class="admin-stat-icon" style="background:#fdf2f8; color:#db2777;">
            <i class="fa-solid fa-user-nurse"></i>
        </div>
        <div>
            <div class="admin-stat-value"><?= $countBidan ?></div>
            <div class="admin-stat-label">Bidan</div>
        </div>
    </div>
    <div class="admin-stat-tile">
        <div class="admin-stat-icon" style="background:#dcfce7; color:#16a34a;">
            <i class="fa-solid fa-circle-check"></i>
        </div>
        <div>
            <div class="admin-stat-value"><?= $countAktif ?></div>
            <div class="admin-stat-label">Akun Aktif</div>
        </div>
    </div>
</div>

<!-- User Table Card -->
<div class="card">
    <div class="card-header">
        <div>
            <h3 class="card-title">
                <i class="fa-solid fa-table-list" style="color:var(--primary);"></i> Daftar Pengguna Sistem
            </h3>
            <p class="card-subtitle">Klik tombol edit untuk mengubah data atau reset password</p>
        </div>
        <div class="card-header-actions">
            <input type="text" id="userSearch" class="form-control" style="width:220px; font-size:13px; padding:7px 12px;" placeholder="Cari nama / username...">
        </div>
    </div>

    <div class="table-responsive">
        <table class="custom-table" id="userTable">
            <thead>
                <tr>
                    <th>#</th>
                    <th>Nama Lengkap</th>
                    <th>Username</th>
                    <th>Role</th>
                    <th>Status</th>
                    <th>Dibuat</th>
                    <th class="th-action">Aksi</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($users)): ?>
                    <tr>
                        <td colspan="7" style="text-align:center; color:var(--text-muted); padding:32px;">
                            Belum ada data pengguna.
                        </td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($users as $i => $u): ?>
                        <?php
                            $isSelf   = ($u['id_user'] == $_SESSION['user']['id_user']);
                            $roleData = match($u['role']) {
                                'admin'  => ['label' => 'Administrator', 'icon' => 'fa-user-shield',  'bg' => '#ede9fe', 'color' => '#7c3aed'],
                                'dokter' => ['label' => 'Dokter',         'icon' => 'fa-user-doctor',  'bg' => '#e0f2fe', 'color' => '#0284c7'],
                                default  => ['label' => 'Bidan',           'icon' => 'fa-user-nurse',   'bg' => '#fdf2f8', 'color' => '#db2777'],
                            };
                        ?>
                        <tr data-search="<?= strtolower($u['nama_lengkap'] . ' ' . $u['username']) ?>">
                            <td style="color:var(--text-muted); font-size:12px;"><?= $i + 1 ?></td>
                            <td>
                                <div class="user-row-info">
                                    <div class="user-row-avatar" style="background:<?= $roleData['bg'] ?>; color:<?= $roleData['color'] ?>;">
                                        <i class="fa-solid <?= $roleData['icon'] ?>"></i>
                                    </div>
                                    <div>
                                        <strong><?= htmlspecialchars($u['nama_lengkap']) ?></strong>
                                        <?php if ($isSelf): ?>
                                            <span class="badge" style="background:#f0fdfa; color:#0f766e; font-size:10px; margin-left:4px;">Anda</span>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </td>
                            <td>
                                <code style="background:#f1f5f9; padding:3px 8px; border-radius:5px; font-size:12.5px; color:#334155;">
                                    <?= htmlspecialchars($u['username']) ?>
                                </code>
                            </td>
                            <td>
                                <span class="badge" style="background:<?= $roleData['bg'] ?>; color:<?= $roleData['color'] ?>;">
                                    <i class="fa-solid <?= $roleData['icon'] ?>" style="font-size:10px;"></i>
                                    <?= $roleData['label'] ?>
                                </span>
                            </td>
                            <td>
                                <?php if ($u['is_active']): ?>
                                    <span class="badge badge-rendah"><i class="fa-solid fa-circle" style="font-size:8px;"></i> Aktif</span>
                                <?php else: ?>
                                    <span class="badge" style="background:#f1f5f9; color:#64748b; border-color:#e2e8f0;">
                                        <i class="fa-solid fa-circle" style="font-size:8px;"></i> Nonaktif
                                    </span>
                                <?php endif; ?>
                            </td>
                            <td style="font-size:12.5px; color:var(--text-secondary);">
                                <?= date('d M Y', strtotime($u['created_at'])) ?>
                            </td>
                            <td class="td-action">
                                <div class="action-btn-group">
                                    <!-- Edit -->
                                    <button class="btn btn-secondary btn-sm"
                                        onclick="openEditModal(<?= htmlspecialchars(json_encode([
                                            'id'   => $u['id_user'],
                                            'nama' => $u['nama_lengkap'],
                                            'role' => $u['role'],
                                        ])) ?>)"
                                        title="Edit Pengguna">
                                        <i class="fa-solid fa-pen-to-square"></i> Edit
                                    </button>

                                    <?php if (!$isSelf): ?>
                                        <!-- Toggle Active -->
                                        <form method="POST" action="admin.php" style="display:inline;">
                                            <input type="hidden" name="action" value="toggle_active">
                                            <input type="hidden" name="id_user" value="<?= $u['id_user'] ?>">
                                            <input type="hidden" name="new_status" value="<?= $u['is_active'] ? 0 : 1 ?>">
                                            <button type="submit" class="btn btn-sm <?= $u['is_active'] ? 'btn-warning-outline' : 'btn-success-outline' ?>" title="<?= $u['is_active'] ? 'Nonaktifkan' : 'Aktifkan' ?>">
                                                <i class="fa-solid <?= $u['is_active'] ? 'fa-user-slash' : 'fa-user-check' ?>"></i>
                                            </button>
                                        </form>

                                        <!-- Delete -->
                                        <form method="POST" action="admin.php" style="display:inline;" onsubmit="return confirm('Hapus pengguna <?= htmlspecialchars($u['nama_lengkap'], ENT_QUOTES) ?>? Tindakan ini tidak dapat dibatalkan.');">
                                            <input type="hidden" name="action" value="delete_user">
                                            <input type="hidden" name="id_user" value="<?= $u['id_user'] ?>">
                                            <button type="submit" class="btn btn-danger-outline btn-sm" title="Hapus Pengguna">
                                                <i class="fa-solid fa-trash"></i>
                                            </button>
                                        </form>
                                    <?php endif; ?>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- ═══════════════════════════════════════════════
     MODAL: ADD USER
════════════════════════════════════════════════ -->
<div class="modal-backdrop" id="modalAddUser">
    <div class="modal-box" style="max-width:480px;">
        <div style="display:flex; align-items:center; justify-content:space-between; margin-bottom:20px;">
            <div>
                <h3 style="font-family:var(--font-heading); font-size:18px; font-weight:800; color:var(--text-primary);">
                    <i class="fa-solid fa-user-plus" style="color:var(--primary);"></i> Tambah Pengguna Baru
                </h3>
                <p style="font-size:12.5px; color:var(--text-muted); margin-top:3px;">Isi data akun dokter, bidan, atau administrator</p>
            </div>
            <button onclick="closeModal('modalAddUser')" style="background:none; border:none; cursor:pointer; font-size:18px; color:#94a3b8; padding:4px;">
                <i class="fa-solid fa-xmark"></i>
            </button>
        </div>

        <form method="POST" action="admin.php">
            <input type="hidden" name="action" value="add_user">

            <div class="modal-inner-grid-2">
                <div class="form-group">
                    <label class="form-label">Nama Lengkap <span class="req">*</span></label>
                    <input type="text" name="nama_lengkap" class="form-control" placeholder="dr. John Doe, Sp.OG" required>
                </div>
                <div class="form-group">
                    <label class="form-label">Role <span class="req">*</span></label>
                    <select name="role" class="form-control" required>
                        <option value="bidan">Bidan</option>
                        <option value="dokter">Dokter</option>
                        <option value="admin">Administrator</option>
                    </select>
                </div>
            </div>

            <div class="form-group" style="margin-bottom:14px;">
                <label class="form-label">Username <span class="req">*</span></label>
                <input type="text" name="username" class="form-control" placeholder="contoh: dr.budi" autocomplete="off" required>
                <span class="form-help">Hanya huruf, angka, titik, underscore. Tidak boleh ada spasi.</span>
            </div>

            <div class="modal-inner-grid-2">
                <div class="form-group">
                    <label class="form-label">Password <span class="req">*</span></label>
                    <input type="password" name="password" class="form-control" placeholder="Min. 6 karakter" required>
                </div>
                <div class="form-group">
                    <label class="form-label">Konfirmasi Password <span class="req">*</span></label>
                    <input type="password" name="password_confirm" class="form-control" placeholder="Ulangi password" required>
                </div>
            </div>

            <div style="display:flex; gap:10px; justify-content:flex-end; margin-top:20px; padding-top:16px; border-top:1px solid var(--border-color);">
                <button type="button" onclick="closeModal('modalAddUser')" class="btn btn-secondary btn-sm">
                    Batal
                </button>
                <button type="submit" class="btn btn-primary btn-sm">
                    <i class="fa-solid fa-floppy-disk"></i> Simpan Pengguna
                </button>
            </div>
        </form>
    </div>
</div>

<!-- ═══════════════════════════════════════════════
     MODAL: EDIT USER
════════════════════════════════════════════════ -->
<div class="modal-backdrop" id="modalEditUser">
    <div class="modal-box" style="max-width:420px;">
        <div style="display:flex; align-items:center; justify-content:space-between; margin-bottom:20px;">
            <div>
                <h3 style="font-family:var(--font-heading); font-size:18px; font-weight:800; color:var(--text-primary);">
                    <i class="fa-solid fa-pen-to-square" style="color:var(--primary);"></i> Edit Pengguna
                </h3>
                <p style="font-size:12.5px; color:var(--text-muted); margin-top:3px;">Kosongkan password jika tidak ingin menggantinya</p>
            </div>
            <button onclick="closeModal('modalEditUser')" style="background:none; border:none; cursor:pointer; font-size:18px; color:#94a3b8; padding:4px;">
                <i class="fa-solid fa-xmark"></i>
            </button>
        </div>

        <form method="POST" action="admin.php">
            <input type="hidden" name="action" value="edit_user">
            <input type="hidden" name="id_user" id="editUserId">

            <div class="form-group" style="margin-bottom:14px;">
                <label class="form-label">Nama Lengkap <span class="req">*</span></label>
                <input type="text" name="nama_lengkap" id="editNama" class="form-control" required>
            </div>

            <div class="form-group" style="margin-bottom:14px;">
                <label class="form-label">Role <span class="req">*</span></label>
                <select name="role" id="editRole" class="form-control" required>
                    <option value="bidan">Bidan</option>
                    <option value="dokter">Dokter</option>
                    <option value="admin">Administrator</option>
                </select>
            </div>

            <div class="form-group" style="margin-bottom:6px;">
                <label class="form-label">Password Baru <span style="font-weight:400; color:var(--text-muted);">(opsional)</span></label>
                <input type="password" name="password" class="form-control" placeholder="Kosongkan jika tidak diganti">
            </div>
            <p class="form-help" style="margin-bottom:16px;">Jika diisi, password lama akan diganti secara permanen.</p>

            <div style="display:flex; gap:10px; justify-content:flex-end; padding-top:16px; border-top:1px solid var(--border-color);">
                <button type="button" onclick="closeModal('modalEditUser')" class="btn btn-secondary btn-sm">
                    Batal
                </button>
                <button type="submit" class="btn btn-primary btn-sm">
                    <i class="fa-solid fa-floppy-disk"></i> Simpan Perubahan
                </button>
            </div>
        </form>
    </div>
</div>

<!-- Inline CSS for admin-specific components -->
<style>
/* Stat summary row */
.admin-stat-row {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
    gap: 14px;
    margin-bottom: 24px;
}
.admin-stat-tile {
    background: #ffffff;
    border: 1px solid var(--border-color);
    border-radius: var(--radius-md);
    padding: 16px 18px;
    display: flex;
    align-items: center;
    gap: 14px;
    box-shadow: var(--shadow-card);
    transition: transform 0.2s, box-shadow 0.2s;
}
.admin-stat-tile:hover {
    transform: translateY(-2px);
    box-shadow: var(--shadow-md);
}
.admin-stat-icon {
    width: 44px;
    height: 44px;
    border-radius: var(--radius-sm);
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 20px;
    flex-shrink: 0;
}
.admin-stat-value {
    font-family: var(--font-heading);
    font-size: 26px;
    font-weight: 800;
    color: var(--text-primary);
    line-height: 1;
}
.admin-stat-label {
    font-size: 12px;
    font-weight: 600;
    color: var(--text-secondary);
    margin-top: 4px;
}

/* User row avatar */
.user-row-info {
    display: flex;
    align-items: center;
    gap: 10px;
}
.user-row-avatar {
    width: 36px;
    height: 36px;
    border-radius: 9px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 15px;
    flex-shrink: 0;
}

/* Action button group */
.action-btn-group {
    display: flex;
    align-items: center;
    gap: 6px;
    justify-content: flex-end;
}

/* Outline button variants */
.btn-warning-outline {
    background: transparent;
    border-color: #fcd34d;
    color: #b45309;
}
.btn-warning-outline:hover { background: #fffbeb; }
.btn-success-outline {
    background: transparent;
    border-color: #86efac;
    color: #166534;
}
.btn-success-outline:hover { background: #f0fdf4; }
</style>

<script>
// Modal helpers
function openModal(id) {
    document.getElementById(id).classList.add('open');
}
function closeModal(id) {
    document.getElementById(id).classList.remove('open');
}
// Close on backdrop click
document.querySelectorAll('.modal-backdrop').forEach(el => {
    el.addEventListener('click', function(e) {
        if (e.target === this) this.classList.remove('open');
    });
});

// Populate edit modal
function openEditModal(data) {
    document.getElementById('editUserId').value = data.id;
    document.getElementById('editNama').value   = data.nama;
    document.getElementById('editRole').value   = data.role;
    openModal('modalEditUser');
}

// Live search
document.getElementById('userSearch').addEventListener('input', function() {
    const q = this.value.toLowerCase().trim();
    document.querySelectorAll('#userTable tbody tr[data-search]').forEach(row => {
        row.style.display = row.dataset.search.includes(q) ? '' : 'none';
    });
});
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
