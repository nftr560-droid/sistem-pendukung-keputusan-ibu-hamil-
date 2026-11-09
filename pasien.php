<?php
$pageTitle = "Data Pasien Ibu Hamil";
require_once __DIR__ . '/config/database.php';
$pdo = getDBConnection();

// Handle Add Patient
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    if ($_POST['action'] === 'add_patient') {
        $nama    = trim($_POST['nama_pasien'] ?? '');
        $umur    = (int)($_POST['umur'] ?? 25);
        $uk      = (int)($_POST['usia_kehamilan'] ?? 20);
        $alamat  = trim($_POST['alamat'] ?? '');
        $telp    = trim($_POST['no_telp'] ?? '');
        $gravida = trim($_POST['gravida'] ?? 'G1P0A0');

        $countP = $pdo->query("SELECT COUNT(*) FROM `pasien`")->fetchColumn();
        $noRM = 'RM-' . date('Y') . '-' . str_pad($countP + 1, 3, '0', STR_PAD_LEFT);

        $stmt = $pdo->prepare("INSERT INTO `pasien` (`no_rm`, `nama_pasien`, `umur`, `usia_kehamilan`, `alamat`, `no_telp`, `gravida`) VALUES (?, ?, ?, ?, ?, ?, ?)");
        $stmt->execute([$noRM, $nama, $umur, $uk, $alamat, $telp, $gravida]);

        $_SESSION['flash'] = ['type' => 'success', 'title' => 'Sukses', 'message' => "Pasien $nama berhasil didaftarkan."];
        header("Location: pasien.php");
        exit;
    }

    if ($_POST['action'] === 'edit_patient') {
        $id      = (int)($_POST['id_pasien'] ?? 0);
        $nama    = trim($_POST['nama_pasien'] ?? '');
        $umur    = (int)($_POST['umur'] ?? 25);
        $uk      = (int)($_POST['usia_kehamilan'] ?? 20);
        $alamat  = trim($_POST['alamat'] ?? '');
        $telp    = trim($_POST['no_telp'] ?? '');
        $gravida = trim($_POST['gravida'] ?? 'G1P0A0');

        $stmt = $pdo->prepare("UPDATE `pasien` SET `nama_pasien` = ?, `umur` = ?, `usia_kehamilan` = ?, `alamat` = ?, `no_telp` = ?, `gravida` = ? WHERE `id_pasien` = ?");
        $stmt->execute([$nama, $umur, $uk, $alamat, $telp, $gravida, $id]);

        $_SESSION['flash'] = ['type' => 'success', 'title' => 'Sukses', 'message' => "Data pasien $nama berhasil diperbarui."];
        header("Location: pasien.php");
        exit;
    }

    if ($_POST['action'] === 'delete_patient') {
        $id = (int)($_POST['id_pasien'] ?? 0);
        try {
            $pdo->beginTransaction();
            $pdo->prepare("DELETE FROM `hasil_keputusan` WHERE `id_pasien` = ?")->execute([$id]);
            $pdo->prepare("DELETE FROM `pemeriksaan` WHERE `id_pasien` = ?")->execute([$id]);
            $pdo->prepare("DELETE FROM `pasien` WHERE `id_pasien` = ?")->execute([$id]);
            $pdo->commit();
            $_SESSION['flash'] = ['type' => 'success', 'title' => 'Dihapus', 'message' => "Data pasien dan seluruh riwayat pemeriksaannya berhasil dihapus."];
        } catch (Exception $e) {
            $pdo->rollBack();
            $_SESSION['flash'] = ['type' => 'error', 'title' => 'Gagal Menghapus', 'message' => $e->getMessage()];
        }
        header("Location: pasien.php");
        exit;
    }
}

// Search and Filter
$search = trim($_GET['search'] ?? '');
$trimester = trim($_GET['trimester'] ?? '');

$sql = "SELECT p.*, COUNT(h.id_hasil) as total_skrining,
    (SELECT h2.keputusan FROM `hasil_keputusan` h2 WHERE h2.id_pasien = p.id_pasien ORDER BY h2.id_hasil DESC LIMIT 1) as diagnosa_terakhir,
    (SELECT h2.level_risiko FROM `hasil_keputusan` h2 WHERE h2.id_pasien = p.id_pasien ORDER BY h2.id_hasil DESC LIMIT 1) as risiko_terakhir,
    (SELECT h2.id_hasil FROM `hasil_keputusan` h2 WHERE h2.id_pasien = p.id_pasien ORDER BY h2.id_hasil DESC LIMIT 1) as id_hasil_terakhir
    FROM `pasien` p
    LEFT JOIN `hasil_keputusan` h ON p.id_pasien = h.id_pasien
    WHERE 1=1";

$params = [];
if ($search !== '') {
    $sql .= " AND (p.nama_pasien LIKE ? OR p.no_rm LIKE ? OR p.alamat LIKE ?)";
    $params[] = "%$search%";
    $params[] = "%$search%";
    $params[] = "%$search%";
}

if ($trimester === '1') {
    $sql .= " AND p.usia_kehamilan < 14";
} elseif ($trimester === '2') {
    $sql .= " AND p.usia_kehamilan >= 14 AND p.usia_kehamilan <= 27";
} elseif ($trimester === '3') {
    $sql .= " AND p.usia_kehamilan >= 28";
}

$sql .= " GROUP BY p.id_pasien ORDER BY p.id_pasien DESC";
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$patients = $stmt->fetchAll();

require_once __DIR__ . '/includes/header.php';
?>

<div class="page-title-bar">
    <div>
        <h1><i class="fa-solid fa-hospital-user" style="color: var(--primary);"></i> Data Pasien Ibu Hamil</h1>
        <p>Master Data Ibu Hamil, Riwayat Kunjungan Antenatal &amp; Rekam Medis CDSS</p>
    </div>
    <div class="page-title-actions">
        <button onclick="openModal('addPatientModal')" class="btn btn-primary btn-sm">
            <i class="fa-solid fa-user-plus"></i> Tambah Pasien Baru
        </button>
    </div>
</div>


<!-- Search & Filter Card -->
<div class="card" style="padding: 16px 20px; margin-bottom: 20px;">
    <form method="GET" action="pasien.php" style="display: flex; gap: 12px; flex-wrap: wrap; align-items: center;">
        <div style="flex: 1; min-width: 240px; position: relative;">
            <input type="text" name="search" class="form-control" placeholder="Cari berdasarkan Nama Pasien, No RM, atau Alamat..." value="<?= htmlspecialchars($search) ?>">
        </div>
        <div style="width: 200px;">
            <select name="trimester" class="form-control">
                <option value="">Semua Trimester</option>
                <option value="1" <?= $trimester === '1' ? 'selected' : '' ?>>Trimester 1 (<14 mgg)</option>
                <option value="2" <?= $trimester === '2' ? 'selected' : '' ?>>Trimester 2 (14-27 mgg)</option>
                <option value="3" <?= $trimester === '3' ? 'selected' : '' ?>>Trimester 3 (≥28 mgg)</option>
            </select>
        </div>
        <button type="submit" class="btn btn-secondary btn-sm">
            <i class="fa-solid fa-filter"></i> Filter
        </button>
        <?php if ($search !== '' || $trimester !== ''): ?>
            <a href="pasien.php" class="btn btn-secondary btn-sm" style="color: #ef4444;">
                <i class="fa-solid fa-rotate-left"></i> Reset
            </a>
        <?php endif; ?>
    </form>
</div>

<!-- Patients Table Card -->
<div class="card">
    <div class="table-responsive">
        <table class="custom-table">
            <thead>
                <tr>
                    <th>No. RM</th>
                    <th>Nama Pasien</th>
                    <th>Usia</th>
                    <th>Usia Kehamilan</th>
                    <th>Gravida</th>
                    <th>Alamat & Kontak</th>
                    <th>Skrining Terakhir</th>
                    <th>Status Risiko</th>
                    <th style="text-align: right;">Aksi</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($patients)): ?>
                    <tr>
                        <td colspan="9" style="text-align: center; color: var(--text-muted); padding: 30px;">
                            Tidak ditemukan data pasien sesuai kriteria pencarian.
                        </td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($patients as $p): ?>
                        <?php
                            $badgeClass = 'badge-rendah';
                            if ($p['risiko_terakhir']) {
                                if (strpos($p['risiko_terakhir'], 'Sangat Tinggi') !== false) $badgeClass = 'badge-gawat';
                                elseif (strpos($p['risiko_terakhir'], 'Tinggi') !== false) $badgeClass = 'badge-tinggi';
                                elseif (strpos($p['risiko_terakhir'], 'Sedang-Tinggi') !== false) $badgeClass = 'badge-sedang-tinggi';
                                elseif (strpos($p['risiko_terakhir'], 'Sedang') !== false) $badgeClass = 'badge-sedang';
                            }
                        ?>
                        <tr>
                            <td>
                                <strong style="font-family: monospace; color: var(--primary-dark);"><?= htmlspecialchars($p['no_rm'] ?? '-') ?></strong>
                            </td>
                            <td>
                                <strong><?= htmlspecialchars($p['nama_pasien']) ?></strong>
                            </td>
                            <td><?= $p['umur'] ?> thn</td>
                            <td>
                                <strong><?= $p['usia_kehamilan'] ?> minggu</strong>
                                <div style="font-size: 11px; color: var(--text-muted);">
                                    <?= $p['usia_kehamilan'] < 14 ? 'Trimester 1' : ($p['usia_kehamilan'] <= 27 ? 'Trimester 2' : 'Trimester 3') ?>
                                </div>
                            </td>
                            <td><?= htmlspecialchars($p['gravida'] ?? 'G1P0A0') ?></td>
                            <td>
                                <div><?= htmlspecialchars($p['alamat'] ?? '-') ?></div>
                                <div style="font-size: 12px; color: var(--text-muted);"><?= htmlspecialchars($p['no_telp'] ?? '-') ?></div>
                            </td>
                            <td>
                                <?php if ($p['diagnosa_terakhir']): ?>
                                    <a href="hasil_detail.php?id=<?= $p['id_hasil_terakhir'] ?>" style="font-weight: 600;">
                                        <?= htmlspecialchars($p['diagnosa_terakhir']) ?>
                                    </a>
                                    <div style="font-size: 11px; color: var(--text-muted);">Total: <?= $p['total_skrining'] ?> kali periksa</div>
                                <?php else: ?>
                                    <span style="color: var(--text-muted); font-size: 12px;">Belum diperiksa</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php if ($p['risiko_terakhir']): ?>
                                    <span class="badge <?= $badgeClass ?>">
                                        <?= htmlspecialchars($p['risiko_terakhir']) ?>
                                    </span>
                                <?php else: ?>
                                    <span class="badge" style="background:#f1f5f9; color:#94a3b8;">-</span>
                                <?php endif; ?>
                            </td>
                            <td style="text-align: right;">
                                <div style="display: inline-flex; gap: 6px;">
                                    <a href="konsultasi.php" class="btn btn-primary btn-sm" title="Mulai Skrining CDSS Pasien Ini">
                                        <i class="fa-solid fa-stethoscope"></i> Skrining
                                    </a>
                                    <button onclick='editPatient(<?= json_encode($p) ?>)' class="btn btn-secondary btn-sm" title="Edit Data">
                                        <i class="fa-solid fa-pen-to-square"></i>
                                    </button>
                                    <button onclick="confirmDeletePatient(<?= $p['id_pasien'] ?>, '<?= htmlspecialchars(addslashes($p['nama_pasien'])) ?>')" class="btn btn-danger-outline btn-sm" title="Hapus">
                                        <i class="fa-solid fa-trash"></i>
                                    </button>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Modal Tambah Pasien -->
<div id="addPatientModal" class="modal-backdrop" onclick="if(event.target===this)closeModal('addPatientModal')">
    <div class="modal-box">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
            <h3 style="font-size: 18px; font-weight: 700; color: var(--text-primary);">
                <i class="fa-solid fa-user-plus" style="color: var(--primary);"></i> Pendaftaran Ibu Hamil Baru
            </h3>
            <button onclick="closeModal('addPatientModal')" style="background:none; border:none; cursor:pointer; font-size:18px; color:#64748b;">
                <i class="fa-solid fa-xmark"></i>
            </button>
        </div>

        <form method="POST" action="pasien.php">
            <input type="hidden" name="action" value="add_patient">
            <div class="form-group" style="margin-bottom: 14px;">
                <label class="form-label">Nama Lengkap Pasien <span class="req">*</span></label>
                <input type="text" name="nama_pasien" class="form-control" required placeholder="Contoh: Ny. Ratna Sari">
            </div>

            <div class="modal-inner-grid-2">
                <div class="form-group">
                    <label class="form-label">Usia Ibu (Tahun) <span class="req">*</span></label>
                    <input type="number" name="umur" class="form-control" required min="12" max="60" value="26">
                </div>
                <div class="form-group">
                    <label class="form-label">Usia Kehamilan (Minggu) <span class="req">*</span></label>
                    <input type="number" name="usia_kehamilan" class="form-control" required min="4" max="44" value="24">
                </div>
            </div>

            <div class="modal-inner-grid-2">
                <div class="form-group">
                    <label class="form-label">Paritas (Gravida)</label>
                    <input type="text" name="gravida" class="form-control" value="G1P0A0">
                </div>
                <div class="form-group">
                    <label class="form-label">No. Telepon / WhatsApp</label>
                    <input type="text" name="no_telp" class="form-control" placeholder="08xxxxxxxxxx">
                </div>
            </div>

            <div class="form-group" style="margin-bottom: 20px;">
                <label class="form-label">Alamat Lengkap</label>
                <textarea name="alamat" class="form-control" placeholder="Jl. ..."></textarea>
            </div>

            <div style="display: flex; justify-content: flex-end; gap: 10px;">
                <button type="button" onclick="closeModal('addPatientModal')" class="btn btn-secondary btn-sm">Batal</button>
                <button type="submit" class="btn btn-primary btn-sm"><i class="fa-solid fa-save"></i> Simpan Pasien</button>
            </div>
        </form>
    </div>
</div>

<!-- Modal Edit Pasien -->
<div id="editPatientModal" class="modal-backdrop" onclick="if(event.target===this)closeModal('editPatientModal')">
    <div class="modal-box">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
            <h3 style="font-size: 18px; font-weight: 700; color: var(--text-primary);">
                <i class="fa-solid fa-user-pen" style="color: var(--primary);"></i> Edit Data Ibu Hamil
            </h3>
            <button onclick="closeModal('editPatientModal')" style="background:none; border:none; cursor:pointer; font-size:18px; color:#64748b;">
                <i class="fa-solid fa-xmark"></i>
            </button>
        </div>

        <form method="POST" action="pasien.php">
            <input type="hidden" name="action" value="edit_patient">
            <input type="hidden" name="id_pasien" id="edit_id_pasien">

            <div class="form-group" style="margin-bottom: 14px;">
                <label class="form-label">Nama Lengkap Pasien <span class="req">*</span></label>
                <input type="text" name="nama_pasien" id="edit_nama_pasien" class="form-control" required>
            </div>

            <div class="modal-inner-grid-2">
                <div class="form-group">
                    <label class="form-label">Usia Ibu (Tahun) <span class="req">*</span></label>
                    <input type="number" name="umur" id="edit_umur" class="form-control" required min="12" max="60">
                </div>
                <div class="form-group">
                    <label class="form-label">Usia Kehamilan (Minggu) <span class="req">*</span></label>
                    <input type="number" name="usia_kehamilan" id="edit_uk" class="form-control" required min="4" max="44">
                </div>
            </div>

            <div class="modal-inner-grid-2">
                <div class="form-group">
                    <label class="form-label">Paritas (Gravida)</label>
                    <input type="text" name="gravida" id="edit_gravida" class="form-control">
                </div>
                <div class="form-group">
                    <label class="form-label">No. Telepon / WhatsApp</label>
                    <input type="text" name="no_telp" id="edit_telp" class="form-control">
                </div>
            </div>

            <div class="form-group" style="margin-bottom: 20px;">
                <label class="form-label">Alamat Lengkap</label>
                <textarea name="alamat" id="edit_alamat" class="form-control"></textarea>
            </div>

            <div style="display: flex; justify-content: flex-end; gap: 10px;">
                <button type="button" onclick="closeModal('editPatientModal')" class="btn btn-secondary btn-sm">Batal</button>
                <button type="submit" class="btn btn-primary btn-sm"><i class="fa-solid fa-save"></i> Perbarui Data</button>
            </div>
        </form>
    </div>
</div>

<!-- Form Hapus Hidden -->
<form id="deletePatientForm" method="POST" action="pasien.php" style="display: none;">
    <input type="hidden" name="action" value="delete_patient">
    <input type="hidden" name="id_pasien" id="delete_id_pasien">
</form>

<script>
function openModal(id) {
    const el = document.getElementById(id);
    if (el) el.classList.add('open');
}

function closeModal(id) {
    const el = document.getElementById(id);
    if (el) el.classList.remove('open');
}

function editPatient(p) {
    document.getElementById('edit_id_pasien').value = p.id_pasien;
    document.getElementById('edit_nama_pasien').value = p.nama_pasien;
    document.getElementById('edit_umur').value = p.umur;
    document.getElementById('edit_uk').value = p.usia_kehamilan;
    document.getElementById('edit_gravida').value = p.gravida || 'G1P0A0';
    document.getElementById('edit_telp').value = p.no_telp || '';
    document.getElementById('edit_alamat').value = p.alamat || '';
    openModal('editPatientModal');
}

function confirmDeletePatient(id, nama) {
    if (confirm(`Apakah Anda yakin ingin menghapus data pasien "${nama}" beserta seluruh riwayat skriningnya?`)) {
        document.getElementById('delete_id_pasien').value = id;
        document.getElementById('deletePatientForm').submit();
    }
}
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
