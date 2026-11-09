<?php
$pageTitle = "Skrining & Konsultasi CDSS";
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/cdss_engine.php';

$pdo = getDBConnection();

// Fetch existing patients for selection dropdown
$stmtPasien = $pdo->query("SELECT * FROM `pasien` ORDER BY `nama_pasien` ASC");
$existingPatients = $stmtPasien->fetchAll();

// Handle Form Submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $patientMode = $_POST['patient_mode'] ?? 'new';
    $idPasien = 0;

    if ($patientMode === 'existing' && !empty($_POST['id_pasien_existing'])) {
        $idPasien = (int)$_POST['id_pasien_existing'];
        // Update gestational age if provided
        if (!empty($_POST['usia_kehamilan'])) {
            $stmtUpd = $pdo->prepare("UPDATE `pasien` SET `usia_kehamilan` = ? WHERE `id_pasien` = ?");
            $stmtUpd->execute([(int)$_POST['usia_kehamilan'], $idPasien]);
        }
    } else {
        // Register new patient
        $namaPasien    = trim($_POST['nama_pasien'] ?? '');
        $umur          = (int)($_POST['umur'] ?? 25);
        $usiaKehamilan = (int)($_POST['usia_kehamilan'] ?? 20);
        $alamat        = trim($_POST['alamat'] ?? '');
        $noTelp        = trim($_POST['no_telp'] ?? '');
        $gravida       = trim($_POST['gravida'] ?? 'G1P0A0');

        // Generate No RM
        $countP = $pdo->query("SELECT COUNT(*) FROM `pasien`")->fetchColumn();
        $noRM = 'RM-' . date('Y') . '-' . str_pad($countP + 1, 3, '0', STR_PAD_LEFT);

        $stmtInsPasien = $pdo->prepare("INSERT INTO `pasien` (`no_rm`, `nama_pasien`, `umur`, `usia_kehamilan`, `alamat`, `no_telp`, `gravida`) VALUES (?, ?, ?, ?, ?, ?, ?)");
        $stmtInsPasien->execute([$noRM, $namaPasien, $umur, $usiaKehamilan, $alamat, $noTelp, $gravida]);
        $idPasien = (int)$pdo->lastInsertId();
    }

    // Examination data
    $sistolik        = (int)($_POST['tekanan_sistolik'] ?? 120);
    $diastolik       = (int)($_POST['tekanan_diastolik'] ?? 80);
    $proteinuria     = trim($_POST['proteinuria'] ?? 'Negatif');
    $edema           = $_POST['edema'] ?? 'Tidak';
    $sakitKepala     = $_POST['sakit_kepala'] ?? 'Tidak';
    $penglihatan     = $_POST['gangguan_penglihatan'] ?? 'Tidak';
    $nyeriUluHati    = $_POST['nyeri_ulu_hati'] ?? 'Tidak';
    $trombositRendah = $_POST['trombosit_rendah'] ?? 'Tidak';
    $fungsiHati      = $_POST['fungsi_hati'] ?? 'Tidak';
    $fungsiGinjal    = $_POST['fungsi_ginjal'] ?? 'Tidak';
    $riwayatHT       = $_POST['riwayat_hipertensi'] ?? 'Tidak';
    $riwayatPE       = $_POST['riwayat_preeklampsia'] ?? 'Tidak';
    $kejang          = $_POST['kejang'] ?? 'Tidak';
    $kembar          = $_POST['kehamilan_kembar'] ?? 'Tidak';
    $catatanMedis    = trim($_POST['catatan_medis'] ?? '');
    $tglPeriksa      = date('Y-m-d');

    // Run CDSS Inference Engine
    $inputCDSS = [
        'umur'                  => (int)($_POST['umur'] ?? 25),
        'usia_kehamilan'        => (int)($_POST['usia_kehamilan'] ?? 20),
        'tekanan_sistolik'      => $sistolik,
        'tekanan_diastolik'     => $diastolik,
        'proteinuria'           => $proteinuria,
        'edema'                 => $edema,
        'sakit_kepala'          => $sakitKepala,
        'gangguan_penglihatan'  => $penglihatan,
        'nyeri_ulu_hati'        => $nyeriUluHati,
        'trombosit_rendah'      => $trombositRendah,
        'fungsi_hati'           => $fungsiHati,
        'fungsi_ginjal'         => $fungsiGinjal,
        'riwayat_hipertensi'    => $riwayatHT,
        'riwayat_preeklampsia'  => $riwayatPE,
        'kejang'                => $kejang,
        'kehamilan_kembar'      => $kembar
    ];
    $hasilCDSS = CDSSEngine::evaluasi($inputCDSS);

    // Save to pemeriksaan
    $gejalaSummary = [];
    if ($sakitKepala === 'Ya') $gejalaSummary[] = 'Sakit Kepala';
    if ($penglihatan === 'Ya') $gejalaSummary[] = 'Penglihatan Kabur';
    if ($nyeriUluHati === 'Ya') $gejalaSummary[] = 'Nyeri Epigastrium';
    if ($kejang === 'Ya') $gejalaSummary[] = 'Kejang';
    $gejalaText = empty($gejalaSummary) ? 'Tidak ada keluhan subjektif' : implode(', ', $gejalaSummary);

    $stmtInsPem = $pdo->prepare("INSERT INTO `pemeriksaan` (
        `id_pasien`, `tekanan_sistolik`, `tekanan_diastolik`, `proteinuria`, `edema`, `gejala`,
        `sakit_kepala`, `gangguan_penglihatan`, `nyeri_ulu_hati`, `trombosit_rendah`, `fungsi_hati`, `fungsi_ginjal`,
        `riwayat_hipertensi`, `riwayat_preeklampsia`, `kejang`, `kehamilan_kembar`, `catatan_medis`, `tanggal_pemeriksaan`
    ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
    $stmtInsPem->execute([
        $idPasien, $sistolik, $diastolik, $proteinuria, $edema, $gejalaText,
        $sakitKepala, $penglihatan, $nyeriUluHati, $trombositRendah, $fungsiHati, $fungsiGinjal,
        $riwayatHT, $riwayatPE, $kejang, $kembar, $catatanMedis, $tglPeriksa
    ]);
    $idPemeriksaan = (int)$pdo->lastInsertId();

    // Save to hasil_keputusan
    $traceLogJSON = json_encode($hasilCDSS['trace']);
    $rekomJSON = json_encode($hasilCDSS['rekomendasi']);

    $stmtInsHK = $pdo->prepare("INSERT INTO `hasil_keputusan` (
        `id_pasien`, `id_alternatif`, `id_pemeriksaan`, `nilai`, `keputusan`, `level_risiko`, `rule_terpicu`, `rekomendasi`, `trace_log`, `tanggal`
    ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
    $stmtInsHK->execute([
        $idPasien,
        $hasilCDSS['id_alternatif'],
        $idPemeriksaan,
        $hasilCDSS['skor_risiko'],
        $hasilCDSS['nama_alternatif'],
        $hasilCDSS['level_risiko'],
        $hasilCDSS['rule_terpicu'],
        $hasilCDSS['rekomendasi']['tindakan_segera'],
        $traceLogJSON,
        $tglPeriksa
    ]);
    $idHasil = (int)$pdo->lastInsertId();

    // Redirect to result dossier
    $_SESSION['flash'] = [
        'type' => 'success',
        'title' => 'Skrining CDSS Selesai',
        'message' => 'Hasil inferensi diagnosis berhasil dirumuskan dan disimpan ke dalam rekam medis.'
    ];
    header("Location: hasil_detail.php?id=" . $idHasil);
    exit;
}

require_once __DIR__ . '/includes/header.php';
?>

<div style="margin-bottom: 24px;">
    <div style="display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 12px;">
        <div>
            <h1 style="font-size: 24px; font-weight: 800; color: var(--text-primary);">
                <i class="fa-solid fa-stethoscope" style="color: var(--primary);"></i> Skrining & Konsultasi CDSS
            </h1>
            <p style="font-size: 14px; color: var(--text-secondary);">
                Pemeriksaan Klinis Ibu Hamil Berisiko Tinggi Hipertensi & Evaluasi Forward Chaining
            </p>
        </div>
        <a href="basis_pengetahuan.php" class="btn btn-secondary btn-sm">
            <i class="fa-solid fa-book-medical"></i> Lihat Basis Kaidah Rule R1-R6
        </a>
    </div>
</div>

<form id="consultForm" method="POST" action="konsultasi.php">
    <div class="consult-layout">
        <!-- Left Column: Form Fields -->
        <div>
            <!-- Section 1: Identitas Pasien -->
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title"><i class="fa-solid fa-user-pregnant" style="color: var(--primary);"></i> 1. Identitas & Data Demografi Pasien</h3>
                    <span class="badge" style="background: #f1f5f9; color: #475569;">Faktor C1 & C2</span>
                </div>

                <div style="margin-bottom: 18px;">
                    <label class="form-label">Mode Pasien</label>
                    <div class="choice-tiles">
                        <div class="choice-tile">
                            <input type="radio" name="patient_mode" id="pm_new" value="new" checked onchange="togglePatientMode('new')">
                            <label for="pm_new"><i class="fa-solid fa-user-plus"></i> Pasien Baru</label>
                        </div>
                        <div class="choice-tile">
                            <input type="radio" name="patient_mode" id="pm_existing" value="existing" onchange="togglePatientMode('existing')">
                            <label for="pm_existing"><i class="fa-solid fa-folder-open"></i> Pasien Terdaftar</label>
                        </div>
                    </div>
                </div>

                <!-- Existing Patient Selector -->
                <div id="existingPatientSection" style="display: none; margin-bottom: 18px;">
                    <div class="form-group">
                        <label class="form-label" for="select_existing_pasien">Pilih Pasien Terdaftar</label>
                        <select name="id_pasien_existing" id="select_existing_pasien" class="form-control">
                            <option value="">-- Pilih dari Rekam Medis --</option>
                            <?php foreach ($existingPatients as $ep): ?>
                                <option value="<?= $ep['id_pasien'] ?>"
                                    data-nama="<?= htmlspecialchars($ep['nama_pasien']) ?>"
                                    data-umur="<?= $ep['umur'] ?>"
                                    data-uk="<?= $ep['usia_kehamilan'] ?>"
                                    data-alamat="<?= htmlspecialchars($ep['alamat'] ?? '') ?>">
                                    <?= htmlspecialchars($ep['no_rm'] ?? '') ?> - <?= htmlspecialchars($ep['nama_pasien']) ?> (<?= $ep['umur'] ?> thn, UK: <?= $ep['usia_kehamilan'] ?> mgg)
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>

                <div class="form-grid">
                    <div class="form-group">
                        <label class="form-label" for="nama_pasien">Nama Lengkap Pasien <span class="req">*</span></label>
                        <input type="text" name="nama_pasien" id="nama_pasien" class="form-control" required placeholder="Contoh: Ny. Ratna Sari">
                    </div>

                    <div class="form-group">
                        <label class="form-label" for="umur">Usia Ibu (Tahun) <span class="req">*</span></label>
                        <input type="number" name="umur" id="umur" class="form-control" required min="12" max="60" value="28" placeholder="Contoh: 28">
                        <span class="form-help">Kriteria C1: Risiko tinggi jika <20 atau >35 tahun</span>
                    </div>

                    <div class="form-group">
                        <label class="form-label" for="usia_kehamilan">Usia Kehamilan (Minggu Gestasi) <span class="req">*</span></label>
                        <input type="number" name="usia_kehamilan" id="usia_kehamilan" class="form-control" required min="4" max="44" value="26" placeholder="Contoh: 26">
                        <span class="form-help">Kriteria C2: Batas penentu kronis (<20 mgg) vs gestasional/PE (≥20 mgg)</span>
                    </div>

                    <div class="form-group">
                        <label class="form-label" for="gravida">Paritas / Gravida</label>
                        <input type="text" name="gravida" id="gravida" class="form-control" value="G1P0A0" placeholder="G1P0A0">
                    </div>

                    <div class="form-group">
                        <label class="form-label" for="no_telp">No. Telepon / WhatsApp</label>
                        <input type="text" name="no_telp" id="no_telp" class="form-control" placeholder="08xxxxxxxxxx">
                    </div>

                    <div class="form-group">
                        <label class="form-label" for="alamat">Alamat Domisili</label>
                        <input type="text" name="alamat" id="alamat" class="form-control" placeholder="Kota / Kelurahan">
                    </div>
                </div>
            </div>

            <!-- Section 2: Tanda-Tanda Vital & Hemodinamik -->
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title"><i class="fa-solid fa-heart-circle-bolt" style="color: #ef4444;"></i> 2. Tanda Vital & Tekanan Darah</h3>
                    <span class="badge" style="background: #fee2e2; color: #991b1b;">Faktor Kunci C3 & C4</span>
                </div>

                <div class="form-grid">
                    <div class="form-group">
                        <label class="form-label" for="tekanan_sistolik">Tekanan Darah Sistolik (mmHg) <span class="req">*</span></label>
                        <input type="number" name="tekanan_sistolik" id="tekanan_sistolik" class="form-control" required min="70" max="260" value="145" style="font-size: 18px; font-weight: 700;">
                        <span class="form-help">Kriteria C3: Normal <140, Hipertensi 140-159, Berat ≥160</span>
                    </div>

                    <div class="form-group">
                        <label class="form-label" for="tekanan_diastolik">Tekanan Darah Diastolik (mmHg) <span class="req">*</span></label>
                        <input type="number" name="tekanan_diastolik" id="tekanan_diastolik" class="form-control" required min="40" max="180" value="95" style="font-size: 18px; font-weight: 700;">
                        <span class="form-help">Kriteria C4: Normal <90, Hipertensi 90-109, Berat ≥110</span>
                    </div>
                </div>

                <!-- Blood Pressure Gauge Visualizer -->
                <div class="bp-gauge-box">
                    <div class="bp-indicator">
                        <div>
                            <span>Status Hemodinamik:</span>
                            <span id="bpCategoryText" style="font-weight: 700; color: #b45309; margin-left: 6px;">Evaluasi...</span>
                        </div>
                        <div>
                            <span id="bpMapText" style="font-weight: 700; color: #0284c7;">MAP: - mmHg</span>
                        </div>
                    </div>
                    <div class="bp-meter-bar">
                        <div class="bp-seg-normal" title="Normal: Sistolik <140, Diastolik <90"></div>
                        <div class="bp-seg-stage1" title="Hipertensi Ringan: Sistolik 140-159, Diastolik 90-109"></div>
                        <div class="bp-seg-stage2" title="Hipertensi Berat: Sistolik ≥160, Diastolik ≥110"></div>
                    </div>
                    <div style="display: flex; justify-content: space-between; font-size: 11px; color: var(--text-muted);">
                        <span>< 140/90 mmHg (Normal)</span>
                        <span>140-159 / 90-109 (Derajat 1)</span>
                        <span>≥ 160/110 mmHg (Derajat 2 / Krisis)</span>
                    </div>
                </div>
            </div>

            <!-- Section 3: Urinalisis & Pemeriksaan Fisik -->
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title"><i class="fa-solid fa-vial-circle-check" style="color: #0284c7;"></i> 3. Pemeriksaan Urin & Fisik</h3>
                    <span class="badge" style="background: #e0f2fe; color: #0369a1;">Faktor C5 & C6</span>
                </div>

                <div class="form-group" style="margin-bottom: 20px;">
                    <label class="form-label">Proteinuria (Pemeriksaan Dipstick Urin) <span class="req">*</span></label>
                    <div class="choice-tiles">
                        <div class="choice-tile">
                            <input type="radio" name="proteinuria" id="prot_neg" value="Negatif" checked>
                            <label for="prot_neg">
                                <span style="font-weight: 800; font-size: 15px;">Negatif</span>
                                <span style="font-size: 11px; color: var(--text-muted);">0 mg/dL</span>
                            </label>
                        </div>
                        <div class="choice-tile">
                            <input type="radio" name="proteinuria" id="prot_p1" value="+1">
                            <label for="prot_p1">
                                <span style="font-weight: 800; font-size: 15px; color: #ea580c;">+1 (Positif 1)</span>
                                <span style="font-size: 11px; color: var(--text-muted);">~30 mg/dL</span>
                            </label>
                        </div>
                        <div class="choice-tile">
                            <input type="radio" name="proteinuria" id="prot_p2" value="+2">
                            <label for="prot_p2">
                                <span style="font-weight: 800; font-size: 15px; color: #ea580c;">+2 (Positif 2)</span>
                                <span style="font-size: 11px; color: var(--text-muted);">~100 mg/dL</span>
                            </label>
                        </div>
                        <div class="choice-tile danger">
                            <input type="radio" name="proteinuria" id="prot_p3" value="+3">
                            <label for="prot_p3">
                                <span style="font-weight: 800; font-size: 15px; color: #dc2626;">+3 (Positif 3)</span>
                                <span style="font-size: 11px; color: var(--text-muted);">~300 mg/dL (Berat)</span>
                            </label>
                        </div>
                        <div class="choice-tile danger">
                            <input type="radio" name="proteinuria" id="prot_p4" value="+4">
                            <label for="prot_p4">
                                <span style="font-weight: 800; font-size: 15px; color: #dc2626;">+4 (Positif 4)</span>
                                <span style="font-size: 11px; color: var(--text-muted);">≥1000 mg/dL</span>
                            </label>
                        </div>
                    </div>
                    <span class="form-help">Kriteria C5: Indikator utama kerusakan endotel glomerulus ginjal pada preeklampsia</span>
                </div>

                <div class="form-group">
                    <label class="form-label">Edema Patologis (Bengkak Tangan/Wajah/Kaki Berlebih)</label>
                    <div class="choice-tiles">
                        <div class="choice-tile">
                            <input type="radio" name="edema" id="edema_no" value="Tidak" checked>
                            <label for="edema_no">Tidak Ada Edema</label>
                        </div>
                        <div class="choice-tile">
                            <input type="radio" name="edema" id="edema_yes" value="Ya">
                            <label for="edema_yes">Ya, Ada Edema Patologis</label>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Section 4: Gejala Subjektif & Laboratorium (Tanda Bahaya) -->
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title"><i class="fa-solid fa-triangle-exclamation" style="color: #b45309;"></i> 4. Gejala Berat & Laboratorium (Impending Eclampsia)</h3>
                    <span class="badge" style="background: #fef3c7; color: #b45309;">Kriteria C7 - C12</span>
                </div>

                <p style="font-size: 13px; color: var(--text-secondary); margin-bottom: 16px;">
                    Pilih "Ya" jika pasien mengalami salah satu tanda kegawatan organ sasaran di bawah ini (syarat kaidah Rule R4 Preeklampsia Berat):
                </p>

                <div class="form-grid">
                    <div class="form-group">
                        <label class="form-label">C7: Sakit Kepala Hebat</label>
                        <div class="choice-tiles">
                            <div class="choice-tile"><input type="radio" name="sakit_kepala" id="c7_n" value="Tidak" checked><label for="c7_n">Tidak</label></div>
                            <div class="choice-tile danger"><input type="radio" name="sakit_kepala" id="c7_y" value="Ya"><label for="c7_y">Ya (Hebat/Berdenyut)</label></div>
                        </div>
                    </div>

                    <div class="form-group">
                        <label class="form-label">C8: Gangguan Penglihatan</label>
                        <div class="choice-tiles">
                            <div class="choice-tile"><input type="radio" name="gangguan_penglihatan" id="c8_n" value="Tidak" checked><label for="c8_n">Tidak</label></div>
                            <div class="choice-tile danger"><input type="radio" name="gangguan_penglihatan" id="c8_y" value="Ya"><label for="c8_y">Ya (Kabur / Skotoma)</label></div>
                        </div>
                    </div>

                    <div class="form-group">
                        <label class="form-label">C9: Nyeri Ulu Hati (Epigastrium)</label>
                        <div class="choice-tiles">
                            <div class="choice-tile"><input type="radio" name="nyeri_ulu_hati" id="c9_n" value="Tidak" checked><label for="c9_n">Tidak</label></div>
                            <div class="choice-tile danger"><input type="radio" name="nyeri_ulu_hati" id="c9_y" value="Ya"><label for="c9_y">Ya (Nyeri Menetap)</label></div>
                        </div>
                    </div>

                    <div class="form-group">
                        <label class="form-label">C10: Trombosit Rendah (<100.000/µL)</label>
                        <div class="choice-tiles">
                            <div class="choice-tile"><input type="radio" name="trombosit_rendah" id="c10_n" value="Tidak" checked><label for="c10_n">Tidak</label></div>
                            <div class="choice-tile danger"><input type="radio" name="trombosit_rendah" id="c10_y" value="Ya"><label for="c10_y">Ya (< 100.000)</label></div>
                        </div>
                    </div>

                    <div class="form-group">
                        <label class="form-label">C11: Fungsi Hati Terganggu (SGOT/SGPT ↑)</label>
                        <div class="choice-tiles">
                            <div class="choice-tile"><input type="radio" name="fungsi_hati" id="c11_n" value="Tidak" checked><label for="c11_n">Tidak</label></div>
                            <div class="choice-tile danger"><input type="radio" name="fungsi_hati" id="c11_y" value="Ya"><label for="c11_y">Ya (Meningkat >2x)</label></div>
                        </div>
                    </div>

                    <div class="form-group">
                        <label class="form-label">C12: Fungsi Ginjal Terganggu (Kreatinin ↑)</label>
                        <div class="choice-tiles">
                            <div class="choice-tile"><input type="radio" name="fungsi_ginjal" id="c12_n" value="Tidak" checked><label for="c12_n">Tidak</label></div>
                            <div class="choice-tile danger"><input type="radio" name="fungsi_ginjal" id="c12_y" value="Ya"><label for="c12_y">Ya (> 1.1 mg/dL)</label></div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Section 5: Riwayat Penyakit & Kegawatdaruratan Mutlak -->
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title"><i class="fa-solid fa-notes-medical" style="color: var(--primary);"></i> 5. Riwayat Penyakit & Kegawatdaruratan Kejang</h3>
                    <span class="badge" style="background: #f1f5f9; color: #475569;">C13, C14, C15, C16</span>
                </div>

                <div class="form-grid">
                    <div class="form-group">
                        <label class="form-label">C13: Riwayat Hipertensi Sebelum Hamil</label>
                        <div class="choice-tiles">
                            <div class="choice-tile"><input type="radio" name="riwayat_hipertensi" id="c13_n" value="Tidak" checked><label for="c13_n">Tidak</label></div>
                            <div class="choice-tile"><input type="radio" name="riwayat_hipertensi" id="c13_y" value="Ya"><label for="c13_y">Ya (Ada Riwayat)</label></div>
                        </div>
                        <span class="form-help">Kaidah R2 (Hipertensi Kronis) & R6 (Superimposed)</span>
                    </div>

                    <div class="form-group">
                        <label class="form-label">C14: Riwayat Preeklampsia Kehamilan Lalu</label>
                        <div class="choice-tiles">
                            <div class="choice-tile"><input type="radio" name="riwayat_preeklampsia" id="c14_n" value="Tidak" checked><label for="c14_n">Tidak</label></div>
                            <div class="choice-tile"><input type="radio" name="riwayat_preeklampsia" id="c14_y" value="Ya"><label for="c14_y">Ya (Ada Riwayat)</label></div>
                        </div>
                    </div>

                    <div class="form-group">
                        <label class="form-label" style="color: #b91c1c; font-weight: 700;">
                            <i class="fa-solid fa-bolt"></i> C15: Pasien Mengalami KEJANG?
                        </label>
                        <div class="choice-tiles">
                            <div class="choice-tile"><input type="radio" name="kejang" id="c15_n" value="Tidak" checked><label for="c15_n">Tidak Kejang</label></div>
                            <div class="choice-tile danger"><input type="radio" name="kejang" id="c15_y" value="Ya"><label for="c15_y"><i class="fa-solid fa-triangle-exclamation"></i> Ya, Kejang (Eklampsia)</label></div>
                        </div>
                        <span class="form-help" style="color: #b91c1c;">Memicu Rule R5 (Eklampsia) - Kegawatdaruratan obstetri!</span>
                    </div>

                    <div class="form-group">
                        <label class="form-label">C16: Kehamilan Kembar (Gemelli)</label>
                        <div class="choice-tiles">
                            <div class="choice-tile"><input type="radio" name="kehamilan_kembar" id="c16_n" value="Tidak" checked><label for="c16_n">Tunggal</label></div>
                            <div class="choice-tile"><input type="radio" name="kehamilan_kembar" id="c16_y" value="Ya"><label for="c16_y">Kembar (Gemelli)</label></div>
                        </div>
                    </div>
                </div>

                <div class="form-group" style="margin-top: 18px;">
                    <label class="form-label" for="catatan_medis">Catatan Klinis Dokter / Bidan Pemeriksa</label>
                    <textarea name="catatan_medis" id="catatan_medis" class="form-control" placeholder="Tuliskan catatan keluhan subjektif, hasil DJJ, denyut jantung janin, atau riwayat alergi obat..."></textarea>
                </div>
            </div>

            <!-- Submit Button -->
            <div style="margin-top: 24px;">
                <button type="submit" class="btn btn-primary btn-lg" style="width: 100%;">
                    <i class="fa-solid fa-brain"></i> PROSES INFERENSI CDSS & RUMUSKAN KEPUTUSAN
                </button>
            </div>
        </div>

        <!-- Right Column: Live Sticky Inference Preview -->
        <div class="consult-sticky-preview" style="position: sticky; top: 90px;">
            <div class="card" id="livePreviewCard" style="border: 2px solid var(--primary-light); background: #ffffff;">
                <div class="card-header" style="background: var(--primary-50); margin: -24px -24px 18px -24px; padding: 16px 20px; border-radius: var(--radius-lg) var(--radius-lg) 0 0;">
                    <div style="display: flex; align-items: center; justify-content: space-between; width: 100%;">
                        <span style="font-size: 13px; font-weight: 700; color: var(--primary-dark); text-transform: uppercase;">
                            <i class="fa-solid fa-bolt-lightning"></i> Evaluasi Kaidah Realtime
                        </span>
                        <span id="previewRiskBadge" class="badge badge-tinggi">Sedang</span>
                    </div>
                </div>

                <div style="text-align: center; padding: 12px 0 20px 0; border-bottom: 1px solid var(--border-color);">
                    <div style="font-size: 12px; font-weight: 600; color: var(--text-muted); text-transform: uppercase;">Prediksi Diagnosis CDSS</div>
                    <div id="previewDiagnosis" style="font-family: var(--font-heading); font-size: 22px; font-weight: 800; color: var(--primary-dark); margin-top: 4px;">
                        Hipertensi Gestasional
                    </div>
                    <div id="previewRule" style="font-size: 12px; color: var(--text-secondary); margin-top: 4px; font-family: monospace;">
                        Aturan Terpicu: R1 (Kaidah Hipertensi Gestasional)
                    </div>
                </div>

                <div style="padding-top: 16px;">
                    <div style="display: flex; justify-content: space-between; margin-bottom: 10px; font-size: 13px;">
                        <span style="color: var(--text-secondary);"><i class="fa-solid fa-calculator"></i> Total Bobot Risiko:</span>
                        <strong id="previewScore" style="color: var(--text-primary);">Skor: 3.0</strong>
                    </div>

                    <div style="background: #f8fafc; border-radius: var(--radius-md); padding: 12px; border: 1px solid var(--border-color); margin-top: 14px;">
                        <div style="font-size: 11px; font-weight: 700; color: var(--text-muted); text-transform: uppercase; margin-bottom: 4px;">
                            <i class="fa-solid fa-bell"></i> Protokol Rekomendasi Utama:
                        </div>
                        <div id="previewAction" style="font-size: 12px; color: var(--text-secondary); line-height: 1.5;">
                            Pantau tekanan darah berkala, kontrol lebih sering, edukasi tanda bahaya.
                        </div>
                    </div>

                    <div style="margin-top: 16px; font-size: 11px; color: var(--text-muted); text-align: center; line-height: 1.4;">
                        <i class="fa-solid fa-circle-info"></i> Preview diperbarui otomatis saat Anda memilih atau mengetik kriteria klinis pasien.
                    </div>
                </div>
            </div>
        </div>
    </div>
</form>

<script>
function togglePatientMode(mode) {
    const existingSec = document.getElementById('existingPatientSection');
    const namaInput = document.getElementById('nama_pasien');
    if (mode === 'existing') {
        existingSec.style.display = 'block';
        namaInput.readOnly = true;
    } else {
        existingSec.style.display = 'none';
        namaInput.readOnly = false;
        namaInput.value = '';
    }
}
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
