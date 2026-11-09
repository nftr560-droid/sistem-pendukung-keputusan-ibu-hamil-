<?php
$pageTitle = "Riwayat Pemeriksaan & Keputusan CDSS";
require_once __DIR__ . '/config/database.php';
$pdo = getDBConnection();

// Handle Export to CSV
if (isset($_GET['export']) && $_GET['export'] === 'csv') {
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename=Riwayat_CDSS_Hipertensi_' . date('Ymd_His') . '.csv');

    $output = fopen('php://output', 'w');
    // UTF-8 BOM for Excel compatibility
    fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF));

    fputcsv($output, [
        'ID Hasil', 'No. RM', 'Nama Pasien', 'Usia (thn)', 'Usia Kehamilan (mgg)',
        'Sistolik (mmHg)', 'Diastolik (mmHg)', 'Proteinuria', 'Edema', 'Sakit Kepala',
        'Penglihatan', 'Nyeri Epigastrium', 'Kejang', 'Diagnosis CDSS', 'Level Risiko',
        'Aturan Terpicu', 'Skor Risiko', 'Tanggal Pemeriksaan'
    ]);

    $stmtExp = $pdo->query("SELECT h.id_hasil, p.no_rm, p.nama_pasien, p.umur, p.usia_kehamilan,
        pem.tekanan_sistolik, pem.tekanan_diastolik, pem.proteinuria, pem.edema, pem.sakit_kepala,
        pem.gangguan_penglihatan, pem.nyeri_ulu_hati, pem.kejang, h.keputusan, h.level_risiko,
        h.rule_terpicu, h.nilai, h.tanggal
        FROM `hasil_keputusan` h
        JOIN `pasien` p ON h.id_pasien = p.id_pasien
        LEFT JOIN `pemeriksaan` pem ON h.id_pemeriksaan = pem.id_pemeriksaan
        ORDER BY h.id_hasil DESC");

    while ($row = $stmtExp->fetch()) {
        fputcsv($output, [
            $row['id_hasil'], $row['no_rm'], $row['nama_pasien'], $row['umur'], $row['usia_kehamilan'],
            $row['tekanan_sistolik'], $row['tekanan_diastolik'], $row['proteinuria'], $row['edema'], $row['sakit_kepala'],
            $row['gangguan_penglihatan'], $row['nyeri_ulu_hati'], $row['kejang'], $row['keputusan'], $row['level_risiko'],
            $row['rule_terpicu'], $row['nilai'], $row['tanggal']
        ]);
    }
    fclose($output);
    exit;
}

// Handle Delete History
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'delete_history') {
    $id = (int)($_POST['id_hasil'] ?? 0);
    $stmtDel = $pdo->prepare("DELETE FROM `hasil_keputusan` WHERE `id_hasil` = ?");
    $stmtDel->execute([$id]);

    $_SESSION['flash'] = ['type' => 'success', 'title' => 'Terhapus', 'message' => 'Catatan riwayat diagnosis berhasil dihapus.'];
    header("Location: riwayat.php");
    exit;
}

// Search & Filter Parameters
$search    = trim($_GET['search'] ?? '');
$diagnosis = trim($_GET['diagnosis'] ?? '');
$riskLevel = trim($_GET['level_risiko'] ?? '');
$startDate = trim($_GET['start_date'] ?? '');
$endDate   = trim($_GET['end_date'] ?? '');

$sql = "SELECT h.*, p.nama_pasien, p.no_rm, p.usia_kehamilan, p.umur,
    pem.tekanan_sistolik, pem.tekanan_diastolik, pem.proteinuria, pem.edema,
    a.kode_alternatif
    FROM `hasil_keputusan` h
    JOIN `pasien` p ON h.id_pasien = p.id_pasien
    JOIN `alternatif` a ON h.id_alternatif = a.id_alternatif
    LEFT JOIN `pemeriksaan` pem ON h.id_pemeriksaan = pem.id_pemeriksaan
    WHERE 1=1";

$params = [];

if ($search !== '') {
    $sql .= " AND (p.nama_pasien LIKE ? OR p.no_rm LIKE ?)";
    $params[] = "%$search%";
    $params[] = "%$search%";
}

if ($diagnosis !== '') {
    $sql .= " AND h.keputusan = ?";
    $params[] = $diagnosis;
}

if ($riskLevel !== '') {
    $sql .= " AND h.level_risiko = ?";
    $params[] = $riskLevel;
}

if ($startDate !== '') {
    $sql .= " AND h.tanggal >= ?";
    $params[] = $startDate;
}

if ($endDate !== '') {
    $sql .= " AND h.tanggal <= ?";
    $params[] = $endDate;
}

$sql .= " ORDER BY h.id_hasil DESC";
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$historyList = $stmt->fetchAll();

// List of all alternatives for filter dropdown
$allAlternatifs = $pdo->query("SELECT nama_alternatif FROM `alternatif` ORDER BY id_alternatif ASC")->fetchAll(PDO::FETCH_COLUMN);

require_once __DIR__ . '/includes/header.php';
?>

<div class="page-title-bar">
    <div>
        <h1><i class="fa-solid fa-clock-rotate-left" style="color: var(--primary);"></i> Riwayat Pemeriksaan &amp; Keputusan CDSS</h1>
        <p>Arsip Keputusan Klinis, Stratifikasi Risiko, dan Audit Trail Inferensi Forward Chaining</p>
    </div>
    <div class="page-title-actions">
        <a href="riwayat.php?export=csv" class="btn btn-secondary btn-sm" title="Unduh format spreadsheet">
            <i class="fa-solid fa-file-excel"></i> <span>Ekspor ke CSV / Excel</span>
        </a>
        <a href="konsultasi.php" class="btn btn-primary btn-sm">
            <i class="fa-solid fa-plus"></i> <span>Skrining Baru</span>
        </a>
    </div>
</div>


<!-- Filter Bar -->
<div class="card" style="padding: 18px 22px; margin-bottom: 20px;">
    <form method="GET" action="riwayat.php" style="display: grid; grid-template-columns: repeat(auto-fit, minmax(180px, 1fr)); gap: 12px; align-items: end;">
        <div>
            <label class="form-label" style="font-size: 12px;">Pencarian Pasien</label>
            <input type="text" name="search" class="form-control" placeholder="Nama / No RM..." value="<?= htmlspecialchars($search) ?>">
        </div>

        <div>
            <label class="form-label" style="font-size: 12px;">Hasil Diagnosis</label>
            <select name="diagnosis" class="form-control">
                <option value="">Semua Diagnosis</option>
                <?php foreach ($allAlternatifs as $altName): ?>
                    <option value="<?= htmlspecialchars($altName) ?>" <?= $diagnosis === $altName ? 'selected' : '' ?>>
                        <?= htmlspecialchars($altName) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>

        <div>
            <label class="form-label" style="font-size: 12px;">Level Risiko</label>
            <select name="level_risiko" class="form-control">
                <option value="">Semua Level</option>
                <option value="Rendah" <?= $riskLevel === 'Rendah' ? 'selected' : '' ?>>Rendah</option>
                <option value="Sedang" <?= $riskLevel === 'Sedang' ? 'selected' : '' ?>>Sedang</option>
                <option value="Sedang-Tinggi" <?= $riskLevel === 'Sedang-Tinggi' ? 'selected' : '' ?>>Sedang-Tinggi</option>
                <option value="Tinggi" <?= $riskLevel === 'Tinggi' ? 'selected' : '' ?>>Tinggi</option>
                <option value="Sangat Tinggi (gawat darurat)" <?= $riskLevel === 'Sangat Tinggi (gawat darurat)' ? 'selected' : '' ?>>Sangat Tinggi (Gawat Darurat)</option>
            </select>
        </div>

        <div>
            <label class="form-label" style="font-size: 12px;">Dari Tanggal</label>
            <input type="date" name="start_date" class="form-control" value="<?= htmlspecialchars($startDate) ?>">
        </div>

        <div>
            <label class="form-label" style="font-size: 12px;">Sampai Tanggal</label>
            <input type="date" name="end_date" class="form-control" value="<?= htmlspecialchars($endDate) ?>">
        </div>

        <div style="display: flex; gap: 8px;">
            <button type="submit" class="btn btn-secondary btn-sm" style="flex: 1;">
                <i class="fa-solid fa-filter"></i> Filter
            </button>
            <?php if ($search !== '' || $diagnosis !== '' || $riskLevel !== '' || $startDate !== '' || $endDate !== ''): ?>
                <a href="riwayat.php" class="btn btn-secondary btn-sm" style="color: #ef4444;" title="Reset Filter">
                    <i class="fa-solid fa-rotate-left"></i>
                </a>
            <?php endif; ?>
        </div>
    </form>
</div>

<!-- History Table Card -->
<div class="card">
    <div class="table-responsive">
        <table class="custom-table">
            <thead>
                <tr>
                    <th>ID / Tanggal</th>
                    <th>No. RM & Nama Pasien</th>
                    <th>Usia & Gestasi</th>
                    <th>Tekanan Darah</th>
                    <th>Proteinuria</th>
                    <th>Diagnosis Terpilih</th>
                    <th>Level Risiko</th>
                    <th>Kaidah Rule</th>
                    <th>Skor</th>
                    <th style="text-align: right;">Aksi</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($historyList)): ?>
                    <tr>
                        <td colspan="10" style="text-align: center; color: var(--text-muted); padding: 30px;">
                            Tidak ada riwayat pemeriksaan yang cocok dengan filter yang dipilih.
                        </td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($historyList as $h): ?>
                        <?php
                            $badgeClass = 'badge-rendah';
                            if (strpos($h['level_risiko'], 'Sangat Tinggi') !== false) {
                                $badgeClass = 'badge-gawat';
                            } elseif (strpos($h['level_risiko'], 'Tinggi') !== false) {
                                $badgeClass = 'badge-tinggi';
                            } elseif (strpos($h['level_risiko'], 'Sedang-Tinggi') !== false) {
                                $badgeClass = 'badge-sedang-tinggi';
                            } elseif (strpos($h['level_risiko'], 'Sedang') !== false) {
                                $badgeClass = 'badge-sedang';
                            }
                        ?>
                        <tr>
                            <td>
                                <strong>#<?= $h['id_hasil'] ?></strong>
                                <div style="font-size: 12px; color: var(--text-muted);"><?= date('d/m/Y', strtotime($h['tanggal'])) ?></div>
                            </td>
                            <td>
                                <strong><?= htmlspecialchars($h['nama_pasien']) ?></strong>
                                <div style="font-size: 12px; font-family: monospace; color: var(--text-muted);"><?= htmlspecialchars($h['no_rm'] ?? '-') ?></div>
                            </td>
                            <td>
                                <?= $h['umur'] ?> thn
                                <div style="font-size: 12px; color: var(--text-muted);"><?= $h['usia_kehamilan'] ?> minggu</div>
                            </td>
                            <td>
                                <strong style="color: <?= ($h['tekanan_sistolik'] >= 160 || $h['tekanan_diastolik'] >= 110) ? '#dc2626' : (($h['tekanan_sistolik'] >= 140 || $h['tekanan_diastolik'] >= 90) ? '#ea580c' : '#16a34a') ?>;">
                                    <?= $h['tekanan_sistolik'] ?>/<?= $h['tekanan_diastolik'] ?>
                                </strong>
                                <span style="font-size: 11px; color: var(--text-muted);">mmHg</span>
                            </td>
                            <td>
                                <span class="badge" style="background: <?= $h['proteinuria'] === 'Negatif' ? '#f1f5f9' : '#fee2e2' ?>; color: <?= $h['proteinuria'] === 'Negatif' ? '#475569' : '#991b1b' ?>;">
                                    <?= htmlspecialchars($h['proteinuria']) ?>
                                </span>
                            </td>
                            <td>
                                <strong><?= htmlspecialchars($h['keputusan']) ?></strong>
                            </td>
                            <td>
                                <span class="badge <?= $badgeClass ?>">
                                    <?= htmlspecialchars($h['level_risiko']) ?>
                                </span>
                            </td>
                            <td>
                                <span class="badge badge-rule"><?= htmlspecialchars($h['rule_terpicu'] ?? '-') ?></span>
                            </td>
                            <td>
                                <span style="font-weight: 700; color: var(--text-primary);"><?= $h['nilai'] ?></span>
                            </td>
                            <td style="text-align: right;">
                                <div style="display: inline-flex; gap: 6px;">
                                    <a href="hasil_detail.php?id=<?= $h['id_hasil'] ?>" class="btn btn-secondary btn-sm" title="Lihat Rekam Medis & Cetak">
                                        <i class="fa-solid fa-file-waveform"></i>
                                    </a>
                                    <button onclick="confirmDeleteHistory(<?= $h['id_hasil'] ?>, '<?= htmlspecialchars(addslashes($h['nama_pasien'])) ?>')" class="btn btn-danger-outline btn-sm" title="Hapus Riwayat">
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

<!-- Form Delete Hidden -->
<form id="deleteHistoryForm" method="POST" action="riwayat.php" style="display: none;">
    <input type="hidden" name="action" value="delete_history">
    <input type="hidden" name="id_hasil" id="delete_id_hasil">
</form>

<script>
function confirmDeleteHistory(id, nama) {
    if (confirm(`Hapus catatan riwayat pemeriksaan pasien "${nama}" (#${id})?`)) {
        document.getElementById('delete_id_hasil').value = id;
        document.getElementById('deleteHistoryForm').submit();
    }
}
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
