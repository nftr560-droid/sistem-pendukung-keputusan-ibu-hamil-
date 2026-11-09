<?php
$pageTitle = "Dashboard Klinis CDSS";
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/database/seed.php';

// Auto migrate and seed if empty
checkAndMigrateSchema();
$pdo = getDBConnection();
$patientCount = $pdo->query("SELECT COUNT(*) FROM `pasien`")->fetchColumn();
if ($patientCount == 0) {
    runSeed(false);
}

// 1. Metric Queries
$totalPasien = (int)$pdo->query("SELECT COUNT(*) FROM `pasien`")->fetchColumn();
$totalPemeriksaan = (int)$pdo->query("SELECT COUNT(*) FROM `pemeriksaan`")->fetchColumn();

// Specific Diagnosis Counts
$countNormal = (int)$pdo->query("SELECT COUNT(*) FROM `hasil_keputusan` WHERE `keputusan` LIKE '%Normal%'")->fetchColumn();
$countGestasional = (int)$pdo->query("SELECT COUNT(*) FROM `hasil_keputusan` WHERE `keputusan` = 'Hipertensi Gestasional'")->fetchColumn();
$countKronis = (int)$pdo->query("SELECT COUNT(*) FROM `hasil_keputusan` WHERE `keputusan` = 'Hipertensi Kronis'")->fetchColumn();
$countPERingan = (int)$pdo->query("SELECT COUNT(*) FROM `hasil_keputusan` WHERE `keputusan` = 'Preeklampsia Ringan'")->fetchColumn();
$countPEBerat = (int)$pdo->query("SELECT COUNT(*) FROM `hasil_keputusan` WHERE `keputusan` = 'Preeklampsia Berat'")->fetchColumn();
$countSuperimposed = (int)$pdo->query("SELECT COUNT(*) FROM `hasil_keputusan` WHERE `keputusan` = 'Superimposed Preeclampsia'")->fetchColumn();
$countEklampsia = (int)$pdo->query("SELECT COUNT(*) FROM `hasil_keputusan` WHERE `keputusan` = 'Eklampsia'")->fetchColumn();

$totalPreeklampsiaAll = $countPERingan + $countPEBerat + $countSuperimposed;
$totalEmergency = $countEklampsia + $countPEBerat;

// 2. Fetch Active Emergency Cases (Eclampsia or PEB in the last consultations)
$stmtEmergencyList = $pdo->query("SELECT h.*, p.nama_pasien, p.no_rm, p.usia_kehamilan, pem.tekanan_sistolik, pem.tekanan_diastolik, pem.proteinuria
    FROM `hasil_keputusan` h
    JOIN `pasien` p ON h.id_pasien = p.id_pasien
    LEFT JOIN `pemeriksaan` pem ON h.id_pemeriksaan = pem.id_pemeriksaan
    WHERE h.level_risiko LIKE '%Sangat Tinggi%' OR h.keputusan = 'Eklampsia' OR h.keputusan = 'Preeklampsia Berat'
    ORDER BY h.id_hasil DESC LIMIT 2");
$emergencyCases = $stmtEmergencyList->fetchAll();

// 3. Fetch Diagnosis Distribution for Doughnut Chart
$stmtDiagDist = $pdo->query("SELECT a.nama_alternatif, a.kode_alternatif, COUNT(h.id_hasil) as jumlah, a.level_risiko
    FROM `alternatif` a
    LEFT JOIN `hasil_keputusan` h ON a.id_alternatif = h.id_alternatif
    GROUP BY a.id_alternatif, a.nama_alternatif, a.kode_alternatif, a.level_risiko
    ORDER BY a.id_alternatif ASC");
$diagDist = $stmtDiagDist->fetchAll();

$chartLabels = [];
$chartCounts = [];
foreach ($diagDist as $d) {
    $chartLabels[] = $d['kode_alternatif'] . ' - ' . $d['nama_alternatif'];
    $chartCounts[] = (int)$d['jumlah'];
}

// 4. Fetch Risk Level Distribution
$stmtRiskDist = $pdo->query("SELECT level_risiko, COUNT(*) as jumlah FROM `hasil_keputusan` GROUP BY level_risiko ORDER BY jumlah DESC");
$riskDist = $stmtRiskDist->fetchAll();

// 5. Fetch Rules Triggered Distribution (R1 - R6)
$stmtRules = $pdo->query("SELECT rule_terpicu, COUNT(*) as total FROM `hasil_keputusan` WHERE rule_terpicu IS NOT NULL GROUP BY rule_terpicu ORDER BY total DESC");
$ruleStats = $stmtRules->fetchAll();

// 6. Fetch Recent Consultations
$stmtRecent = $pdo->query("SELECT h.*, p.nama_pasien, p.no_rm, p.usia_kehamilan, p.umur,
    pem.tekanan_sistolik, pem.tekanan_diastolik, pem.proteinuria, pem.edema, a.kode_alternatif
    FROM `hasil_keputusan` h
    JOIN `pasien` p ON h.id_pasien = p.id_pasien
    JOIN `alternatif` a ON h.id_alternatif = a.id_alternatif
    LEFT JOIN `pemeriksaan` pem ON h.id_pemeriksaan = pem.id_pemeriksaan
    ORDER BY h.id_hasil DESC LIMIT 8");
$recentConsultations = $stmtRecent->fetchAll();

require_once __DIR__ . '/includes/header.php';
?>

<!-- Dashboard Executive Hero Card -->
<div class="dashboard-hero">
    <!-- Decorative dot grid -->
    <div class="dashboard-hero-dots" aria-hidden="true">
        <?php for ($i = 0; $i < 25; $i++): ?><span></span><?php endfor; ?>
    </div>

    <div style="position: relative; z-index: 2;">
        <div class="hero-pill">
            <i class="fa-solid fa-hospital-user"></i> CLINICAL DECISION SUPPORT SYSTEM (CDSS)
        </div>
        <h1 class="hero-title">Dashboard Penanganan Hipertensi Ibu Hamil</h1>
        <p class="hero-desc">
            Sistem inferensi klinis berbasis <strong>Rule-Based System (Forward Chaining)</strong> untuk deteksi dini risiko maternal, klasifikasi 7 sindrom hipertensi kehamilan, dan panduan protokol kegawatdaruratan obstetri sesuai pedoman POGI & WHO.
        </p>
        <!-- Quick stats row -->
        <div class="hero-stats-row">
            <div class="hero-stat-chip">
                <i class="fa-solid fa-users"></i>
                <strong><?= number_format($totalPasien) ?></strong> Pasien
            </div>
            <div class="hero-stat-chip">
                <i class="fa-solid fa-stethoscope"></i>
                <strong><?= number_format($totalPemeriksaan) ?></strong> Skrining
            </div>
            <?php if ($totalEmergency > 0): ?>
            <div class="hero-stat-chip" style="background: rgba(220,38,38,0.25); border-color: rgba(220,38,38,0.4);">
                <i class="fa-solid fa-triangle-exclamation"></i>
                <strong><?= $totalEmergency ?></strong> Kasus Gawat
            </div>
            <?php endif; ?>
            <div class="hero-stat-chip">
                <i class="fa-solid fa-user-tie"></i>
                <?= htmlspecialchars($_SESSION['user']['nama_lengkap'] ?? 'Pengguna') ?>
            </div>
        </div>
    </div>
</div>

<!-- Emergency Critical Alert Triage Strip -->
<?php if (!empty($emergencyCases)): ?>
    <?php foreach ($emergencyCases as $emg): ?>
        <div class="alert-emergency">
            <div class="alert-emergency-content">
                <i class="fa-solid fa-triangle-exclamation alert-emergency-icon"></i>
                <div>
                    <div class="alert-emergency-title">
                        TRIAGE GAWAT DARURAT: <?= htmlspecialchars($emg['nama_pasien']) ?> (<?= htmlspecialchars($emg['no_rm'] ?? '-') ?>) — <u><?= htmlspecialchars($emg['keputusan']) ?></u>
                    </div>
                    <div class="alert-emergency-text">
                        Tekanan Darah: <strong><?= $emg['tekanan_sistolik'] ?>/<?= $emg['tekanan_diastolik'] ?> mmHg</strong> | Proteinuria: <strong><?= htmlspecialchars($emg['proteinuria']) ?></strong> | Usia Kehamilan: <strong><?= $emg['usia_kehamilan'] ?> mgg</strong>.
                        Tindakan: <em><?= htmlspecialchars(substr($emg['rekomendasi'], 0, 130)) ?>...</em>
                    </div>
                </div>
            </div>
            <a href="hasil_detail.php?id=<?= $emg['id_hasil'] ?>" class="btn btn-danger-outline btn-sm" style="flex-shrink: 0;">
                <i class="fa-solid fa-file-medical"></i> Protokol Lengkap
            </a>
        </div>
    <?php endforeach; ?>
<?php endif; ?>

<!-- 6 High-Impact Metric Cards -->
<div class="stats-grid-6">
    <!-- Card 1: Total Pasien -->
    <div class="stat-tile">
        <div class="stat-tile-top">
            <div class="stat-tile-icon primary">
                <i class="fa-solid fa-users"></i>
            </div>
            <span class="badge badge-rendah">Aktif</span>
        </div>
        <div>
            <div class="stat-tile-value"><?= number_format($totalPasien) ?></div>
            <div class="stat-tile-label">Total Ibu Hamil</div>
            <div class="stat-tile-sub">Rekam medis terdaftar</div>
        </div>
    </div>

    <!-- Card 2: Total Skrining -->
    <div class="stat-tile">
        <div class="stat-tile-top">
            <div class="stat-tile-icon info">
                <i class="fa-solid fa-stethoscope"></i>
            </div>
            <span class="badge" style="background:#e0f2fe; color:#0369a1;">Sesi</span>
        </div>
        <div>
            <div class="stat-tile-value"><?= number_format($totalPemeriksaan) ?></div>
            <div class="stat-tile-label">Total Skrining CDSS</div>
            <div class="stat-tile-sub">Evaluasi klinis lengkap</div>
        </div>
    </div>

    <!-- Card 3: Normal / Fisiologis -->
    <div class="stat-tile">
        <div class="stat-tile-top">
            <div class="stat-tile-icon success">
                <i class="fa-solid fa-circle-check"></i>
            </div>
            <span class="badge badge-rendah">Rendah</span>
        </div>
        <div>
            <div class="stat-tile-value"><?= number_format($countNormal) ?></div>
            <div class="stat-tile-label">Kasus Normotensif</div>
            <div class="stat-tile-sub">Kondisi kehamilan fisiologis</div>
        </div>
    </div>

    <!-- Card 4: Hipertensi Gestasional & Kronis -->
    <div class="stat-tile">
        <div class="stat-tile-top">
            <div class="stat-tile-icon warning">
                <i class="fa-solid fa-heart-pulse"></i>
            </div>
            <span class="badge badge-sedang">Sedang</span>
        </div>
        <div>
            <div class="stat-tile-value"><?= number_format($countGestasional + $countKronis) ?></div>
            <div class="stat-tile-label">Gestasional & Kronis</div>
            <div class="stat-tile-sub">Tanpa proteinuria</div>
        </div>
    </div>

    <!-- Card 5: Preeklampsia (Ringan, Berat, Superimposed) -->
    <div class="stat-tile">
        <div class="stat-tile-top">
            <div class="stat-tile-icon orange">
                <i class="fa-solid fa-shield-halved"></i>
            </div>
            <span class="badge badge-sedang-tinggi">Tinggi</span>
        </div>
        <div>
            <div class="stat-tile-value"><?= number_format($totalPreeklampsiaAll) ?></div>
            <div class="stat-tile-label">Kasus Preeklampsia</div>
            <div class="stat-tile-sub">Ringan, Berat & Superimposed</div>
        </div>
    </div>

    <!-- Card 6: Eklampsia / Gawat Darurat -->
    <div class="stat-tile">
        <div class="stat-tile-top">
            <div class="stat-tile-icon danger">
                <i class="fa-solid fa-truck-medical"></i>
            </div>
            <span class="badge badge-gawat">Gawat</span>
        </div>
        <div>
            <div class="stat-tile-value"><?= number_format($countEklampsia) ?></div>
            <div class="stat-tile-label">Eklampsia (Kejang)</div>
            <div class="stat-tile-sub">Rujukan segera RS PONEK</div>
        </div>
    </div>
</div>

<!-- Visualizations & Charts Grid -->
<div class="charts-grid">
    <!-- Chart 1: Distribusi 7 Diagnosis CDSS -->
    <div class="card" style="margin-bottom: 0;">
        <div class="card-header">
            <div>
                <h3 class="card-title">
                    <i class="fa-solid fa-chart-pie" style="color: var(--primary);"></i> Distribusi 7 Kategori Diagnosis CDSS
                </h3>
                <p class="card-subtitle">Proporsi alternatif hasil inferensi kaidah Forward Chaining</p>
            </div>
        </div>
        <div style="height: 290px; position: relative; display: flex; align-items: center; justify-content: center;">
            <canvas id="chartDiagnosis"></canvas>
        </div>
    </div>

    <!-- Chart 2: Stratifikasi Tingkat Risiko -->
    <div class="card" style="margin-bottom: 0;">
        <div class="card-header">
            <div>
                <h3 class="card-title">
                    <i class="fa-solid fa-chart-column" style="color: var(--secondary);"></i> Stratifikasi Level Risiko Maternal
                </h3>
                <p class="card-subtitle">Klasifikasi tingkat keparahan risiko komplikasi ibu dan janin</p>
            </div>
        </div>
        <div style="height: 290px; position: relative;">
            <canvas id="chartRisiko"></canvas>
        </div>
    </div>
</div>

<!-- Dashboard Mini Screener & Quick Clinical Triage Widget -->
<div class="card" style="border: 2px solid var(--primary-light); background: linear-gradient(180deg, #ffffff 0%, #f0fdfa 100%);">
    <div class="card-header">
        <div>
            <h3 class="card-title">
                <i class="fa-solid fa-bolt-lightning" style="color: var(--primary);"></i> Triage Klinis Cepat
            </h3>
            <p class="card-subtitle">Evaluasi instan langsung dari dashboard tanpa menyimpan data</p>
        </div>
        <a href="konsultasi.php" class="btn btn-primary btn-sm">
            <i class="fa-solid fa-stethoscope"></i> Skrining Lengkap
        </a>
    </div>

    <div class="screener-grid">
        <div class="form-group">
            <label class="form-label" style="font-size: 11.5px;">Usia Kehamilan</label>
            <input type="number" id="dash_uk" class="form-control" value="28" min="4" max="44" placeholder="Minggu">
        </div>

        <div class="form-group">
            <label class="form-label" style="font-size: 11.5px;">Sistolik (mmHg)</label>
            <input type="number" id="dash_sys" class="form-control" value="155" min="70" max="250" placeholder="Sistolik">
        </div>

        <div class="form-group">
            <label class="form-label" style="font-size: 11.5px;">Diastolik (mmHg)</label>
            <input type="number" id="dash_dia" class="form-control" value="100" min="40" max="160" placeholder="Diastolik">
        </div>

        <div class="form-group">
            <label class="form-label" style="font-size: 11.5px;">Proteinuria</label>
            <select id="dash_prot" class="form-control">
                <option value="Negatif">Negatif</option>
                <option value="+1">+1 (Positif 1)</option>
                <option value="+2" selected>+2 (Positif 2)</option>
                <option value="+3">+3 (Positif 3 - Berat)</option>
                <option value="+4">+4 (Positif 4)</option>
            </select>
        </div>

        <div class="form-group">
            <label class="form-label" style="font-size: 11.5px; color:#dc2626; font-weight:700;">Kejang?</label>
            <select id="dash_kejang" class="form-control">
                <option value="Tidak" selected>Tidak</option>
                <option value="Ya">Ya (Eklampsia)</option>
            </select>
        </div>

        <!-- Quick Output Card -->
        <div class="screener-result-box" style="background: #ffffff; border: 1px solid var(--primary-light); border-radius: var(--radius-md); padding: 14px; box-shadow: var(--shadow-sm);">
            <div style="font-size: 11px; font-weight: 700; color: var(--text-muted); text-transform: uppercase;">Hasil Inferensi Realtime:</div>
            <div id="dashResultTitle" style="font-family: var(--font-heading); font-size: 18px; font-weight: 800; color: var(--primary-dark); margin-top: 2px;">
                Preeklampsia Ringan
            </div>
            <div style="display: flex; align-items: center; justify-content: space-between; margin-top: 6px;">
                <span id="dashResultRule" style="font-family: monospace; font-size: 11px; color: var(--text-secondary);">Rule: R3</span>
                <span id="dashResultBadge" class="badge badge-sedang-tinggi">Sedang-Tinggi</span>
            </div>
        </div>
    </div>
</div>

<!-- Recent Consultations Table -->
<div class="card">
    <div class="card-header">
        <div>
            <h3 class="card-title">
                <i class="fa-solid fa-notes-medical" style="color: var(--primary);"></i> Rekam Skrining Klinis Terbaru
            </h3>
            <p class="card-subtitle">Daftar rekam medis pemeriksaan antenatal dan keputusan CDSS terkini</p>
        </div>
        <div class="card-header-actions">
            <a href="riwayat.php" class="btn btn-secondary btn-sm">
                <i class="fa-solid fa-list"></i> Semua Riwayat
            </a>
            <a href="riwayat.php?export=csv" class="btn btn-secondary btn-sm" title="Ekspor Data ke CSV/Excel">
                <i class="fa-solid fa-file-excel"></i> Ekspor
            </a>
        </div>
    </div>

    <div class="table-responsive">
        <table class="custom-table">
            <thead>
                <tr>
                    <th>No. RM / Pasien</th>
                    <th>Usia & Gestasi</th>
                    <th>Tekanan Darah</th>
                    <th>Proteinuria</th>
                    <th>Hasil Diagnosis CDSS</th>
                    <th>Level Risiko</th>
                    <th>Kaidah Rule</th>
                    <th>Tanggal</th>
                    <th class="th-action">Aksi</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($recentConsultations)): ?>
                    <tr>
                        <td colspan="9" style="text-align: center; color: var(--text-muted); padding: 30px;">
                            Belum ada riwayat pemeriksaan. Silakan mulai skrining baru.
                        </td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($recentConsultations as $row): ?>
                        <?php
                            $badgeClass = 'badge-rendah';
                            if (strpos($row['level_risiko'], 'Sangat Tinggi') !== false) {
                                $badgeClass = 'badge-gawat';
                            } elseif (strpos($row['level_risiko'], 'Tinggi') !== false) {
                                $badgeClass = 'badge-tinggi';
                            } elseif (strpos($row['level_risiko'], 'Sedang-Tinggi') !== false) {
                                $badgeClass = 'badge-sedang-tinggi';
                            } elseif (strpos($row['level_risiko'], 'Sedang') !== false) {
                                $badgeClass = 'badge-sedang';
                            }
                        ?>
                        <tr>
                            <td>
                                <strong><?= htmlspecialchars($row['nama_pasien']) ?></strong>
                                <div style="font-size: 11.5px; font-family: monospace; color: var(--text-muted);"><?= htmlspecialchars($row['no_rm'] ?? '-') ?></div>
                            </td>
                            <td>
                                <?= $row['umur'] ?> thn
                                <div style="font-size: 11.5px; color: var(--text-muted);"><?= $row['usia_kehamilan'] ?> minggu</div>
                            </td>
                            <td>
                                <strong style="color: <?= ($row['tekanan_sistolik'] >= 160 || $row['tekanan_diastolik'] >= 110) ? '#dc2626' : (($row['tekanan_sistolik'] >= 140 || $row['tekanan_diastolik'] >= 90) ? '#ea580c' : '#16a34a') ?>;">
                                    <?= $row['tekanan_sistolik'] ?>/<?= $row['tekanan_diastolik'] ?>
                                </strong>
                                <span style="font-size: 11px; color: var(--text-muted);">mmHg</span>
                            </td>
                            <td>
                                <span class="badge" style="background: <?= $row['proteinuria'] === 'Negatif' ? '#f1f5f9' : '#fee2e2' ?>; color: <?= $row['proteinuria'] === 'Negatif' ? '#475569' : '#991b1b' ?>;">
                                    <?= htmlspecialchars($row['proteinuria']) ?>
                                </span>
                            </td>
                            <td>
                                <strong style="color: var(--text-primary);"><?= htmlspecialchars($row['keputusan']) ?></strong>
                            </td>
                            <td>
                                <span class="badge <?= $badgeClass ?>">
                                    <?= htmlspecialchars($row['level_risiko']) ?>
                                </span>
                            </td>
                            <td>
                                <span class="badge badge-rule"><?= htmlspecialchars($row['rule_terpicu'] ?? '-') ?></span>
                            </td>
                            <td style="font-size: 12.5px; color: var(--text-secondary);">
                                <?= date('d/m/Y', strtotime($row['tanggal'])) ?>
                            </td>
                            <td class="td-action">
                                <a href="hasil_detail.php?id=<?= $row['id_hasil'] ?>" class="btn btn-secondary btn-sm" title="Lihat Rekam Medis & Cetak">
                                    <i class="fa-solid fa-file-waveform"></i> Detail
                                </a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Chart Initialization and Dashboard Live Triage Script -->
<script>
document.addEventListener('DOMContentLoaded', function () {
    // 1. Chart Diagnosis Distribution
    const ctxDiag = document.getElementById('chartDiagnosis');
    if (ctxDiag) {
        new Chart(ctxDiag, {
            type: 'doughnut',
            data: {
                labels: <?= json_encode($chartLabels) ?>,
                datasets: [{
                    data: <?= json_encode($chartCounts) ?>,
                    backgroundColor: [
                        '#10b981', // A1 Normal
                        '#f59e0b', // A2 Gestasional
                        '#0284c7', // A3 Kronis
                        '#f97316', // A4 PE Ringan
                        '#ef4444', // A5 PE Berat
                        '#8b5cf6', // A6 Superimposed
                        '#991b1b'  // A7 Eklampsia
                    ],
                    borderWidth: 2,
                    borderColor: '#ffffff'
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        position: 'right',
                        labels: {
                            boxWidth: 12,
                            font: { family: 'Plus Jakarta Sans', size: 11.5 }
                        }
                    }
                },
                cutout: '62%'
            }
        });
    }

    // 2. Chart Risk Levels
    const ctxRisk = document.getElementById('chartRisiko');
    if (ctxRisk) {
        const riskData = <?= json_encode($riskDist) ?>;
        const rLabels = riskData.map(d => d.level_risiko);
        const rCounts = riskData.map(d => parseInt(d.jumlah));

        new Chart(ctxRisk, {
            type: 'bar',
            data: {
                labels: rLabels,
                datasets: [{
                    label: 'Jumlah Kasus',
                    data: rCounts,
                    backgroundColor: [
                        '#86efac',
                        '#fde047',
                        '#fdba74',
                        '#fca5a5',
                        '#f87171'
                    ],
                    borderRadius: 6,
                    borderWidth: 1
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { display: false }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: { stepSize: 1 }
                    }
                }
            }
        });
    }

    // 3. Quick Triage Screener Widget Listener
    const dashInputs = ['dash_uk', 'dash_sys', 'dash_dia', 'dash_prot', 'dash_kejang'];
    dashInputs.forEach(id => {
        const el = document.getElementById(id);
        if (el) {
            el.addEventListener('input', runDashboardTriage);
            el.addEventListener('change', runDashboardTriage);
        }
    });

    function runDashboardTriage() {
        const params = new URLSearchParams();
        params.append('usia_kehamilan', document.getElementById('dash_uk').value);
        params.append('tekanan_sistolik', document.getElementById('dash_sys').value);
        params.append('tekanan_diastolik', document.getElementById('dash_dia').value);
        params.append('proteinuria', document.getElementById('dash_prot').value);
        params.append('kejang', document.getElementById('dash_kejang').value);

        fetch('api/simulasi.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: params.toString()
        })
        .then(r => r.json())
        .then(data => {
            if (data.status === 'success') {
                const res = data.result;
                document.getElementById('dashResultTitle').textContent = res.nama_alternatif;
                document.getElementById('dashResultRule').textContent = `Rule: ${res.rule_terpicu}`;
                const badge = document.getElementById('dashResultBadge');
                badge.textContent = res.level_risiko;

                let bClass = 'badge-rendah';
                if (res.level_risiko.includes('Sangat Tinggi')) bClass = 'badge-gawat';
                else if (res.level_risiko.includes('Tinggi')) bClass = 'badge-tinggi';
                else if (res.level_risiko.includes('Sedang-Tinggi')) bClass = 'badge-sedang-tinggi';
                else if (res.level_risiko.includes('Sedang')) bClass = 'badge-sedang';
                badge.className = `badge ${bClass}`;
            }
        })
        .catch(err => console.log('Triage note:', err));
    }
});
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
