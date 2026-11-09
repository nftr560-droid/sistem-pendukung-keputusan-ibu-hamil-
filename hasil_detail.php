<?php
$pageTitle = "Detail Hasil Rekam Medis CDSS";
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/cdss_engine.php';

$pdo = getDBConnection();
$idHasil = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($idHasil <= 0) {
    header("Location: riwayat.php");
    exit;
}

// Fetch Full Dossier
$stmt = $pdo->prepare("SELECT h.*, 
    p.nama_pasien, p.no_rm, p.umur, p.usia_kehamilan as gestasi_pasien, p.alamat, p.no_telp, p.gravida,
    pem.tekanan_sistolik, pem.tekanan_diastolik, pem.proteinuria, pem.edema, pem.gejala,
    pem.sakit_kepala, pem.gangguan_penglihatan, pem.nyeri_ulu_hati, pem.trombosit_rendah,
    pem.fungsi_hati, pem.fungsi_ginjal, pem.riwayat_hipertensi, pem.riwayat_preeklampsia,
    pem.kejang, pem.kehamilan_kembar, pem.catatan_medis, pem.tanggal_pemeriksaan,
    a.kode_alternatif, a.nama_alternatif, a.level_risiko as alt_level, a.keterangan as alt_ket
    FROM `hasil_keputusan` h
    JOIN `pasien` p ON h.id_pasien = p.id_pasien
    JOIN `alternatif` a ON h.id_alternatif = a.id_alternatif
    LEFT JOIN `pemeriksaan` pem ON h.id_pemeriksaan = pem.id_pemeriksaan
    WHERE h.id_hasil = ?");
$stmt->execute([$idHasil]);
$dossier = $stmt->fetch();

if (!$dossier) {
    die("Data hasil pemeriksaan tidak ditemukan.");
}

// Re-evaluate facts for complete structured presentation
$inputCDSS = [
    'umur'                  => $dossier['umur'],
    'usia_kehamilan'        => $dossier['gestasi_pasien'],
    'tekanan_sistolik'      => $dossier['tekanan_sistolik'],
    'tekanan_diastolik'     => $dossier['tekanan_diastolik'],
    'proteinuria'           => $dossier['proteinuria'],
    'edema'                 => $dossier['edema'],
    'sakit_kepala'          => $dossier['sakit_kepala'],
    'gangguan_penglihatan'  => $dossier['gangguan_penglihatan'],
    'nyeri_ulu_hati'        => $dossier['nyeri_ulu_hati'],
    'trombosit_rendah'      => $dossier['trombosit_rendah'],
    'fungsi_hati'           => $dossier['fungsi_hati'],
    'fungsi_ginjal'         => $dossier['fungsi_ginjal'],
    'riwayat_hipertensi'    => $dossier['riwayat_hipertensi'],
    'riwayat_preeklampsia'  => $dossier['riwayat_preeklampsia'],
    'kejang'                => $dossier['kejang'],
    'kehamilan_kembar'      => $dossier['kehamilan_kembar']
];
$evalDetail = CDSSEngine::evaluasi($inputCDSS);

// Decode trace log if stored
$traceSteps = !empty($dossier['trace_log']) ? json_decode($dossier['trace_log'], true) : $evalDetail['trace'];
$rekomendasi = $evalDetail['rekomendasi'];

// Risk Badge Styling
$level = $dossier['level_risiko'];
$badgeClass = 'badge-rendah';
$headerBg = 'linear-gradient(135deg, #059669 0%, #10b981 100%)';

if (strpos($level, 'Sangat Tinggi') !== false) {
    $badgeClass = 'badge-gawat';
    $headerBg = 'linear-gradient(135deg, #7f1d1d 0%, #dc2626 100%)';
} elseif (strpos($level, 'Tinggi') !== false) {
    $badgeClass = 'badge-tinggi';
    $headerBg = 'linear-gradient(135deg, #991b1b 0%, #ef4444 100%)';
} elseif (strpos($level, 'Sedang-Tinggi') !== false) {
    $badgeClass = 'badge-sedang-tinggi';
    $headerBg = 'linear-gradient(135deg, #c2410c 0%, #f97316 100%)';
} elseif (strpos($level, 'Sedang') !== false) {
    $badgeClass = 'badge-sedang';
    $headerBg = 'linear-gradient(135deg, #b45309 0%, #f59e0b 100%)';
}

require_once __DIR__ . '/includes/header.php';
?>

<!-- Action Bar (Hidden on Print) -->
<div class="no-print" style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 24px; flex-wrap: wrap; gap: 12px;">
    <div>
        <a href="riwayat.php" class="btn btn-secondary btn-sm">
            <i class="fa-solid fa-arrow-left"></i> Kembali ke Riwayat
        </a>
    </div>
    <div style="display: flex; gap: 10px;">
        <button onclick="cetakDossier()" class="btn btn-primary btn-sm">
            <i class="fa-solid fa-print"></i> Cetak Hasil & Rekam Medis
        </button>
        <a href="konsultasi.php" class="btn btn-secondary btn-sm">
            <i class="fa-solid fa-plus"></i> Skrining Baru
        </a>
    </div>
</div>

<div class="dossier-card">
    <!-- Official Hospital Letterhead -->
    <div class="hospital-header">
        <div style="display: flex; align-items: center; justify-content: center; gap: 16px; margin-bottom: 8px;">
            <i class="fa-solid fa-hospital-user" style="font-size: 32px; color: var(--primary);"></i>
            <div>
                <h2>PUSAT PELAYANAN KESEHATAN MATERNAL & PERINATAL</h2>
                <p>INSTALASI KEBIDANAN DAN KANDUNGAN — UNIT RUJUKAN RISIKO TINGGI (PONEK)</p>
            </div>
        </div>
        <p style="font-size: 12px; color: var(--text-muted);">
            LEMBAR REKAM MEDIS CLINICAL DECISION SUPPORT SYSTEM (CDSS) PENANGANAN HIPERTENSI DALAM KEHAMILAN
        </p>
    </div>

    <!-- Patient Demographics Section -->
    <div style="background: #f8fafc; border: 1px solid var(--border-color); border-radius: var(--radius-md); padding: 18px 24px; margin-bottom: 28px;">
        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); gap: 16px; font-size: 14px;">
            <div>
                <span style="color: var(--text-muted); font-size: 12px; font-weight: 600; text-transform: uppercase;">No. Rekam Medis:</span>
                <div style="font-weight: 700; color: var(--text-primary); font-size: 16px;"><?= htmlspecialchars($dossier['no_rm'] ?? '-') ?></div>
            </div>
            <div>
                <span style="color: var(--text-muted); font-size: 12px; font-weight: 600; text-transform: uppercase;">Nama Pasien:</span>
                <div style="font-weight: 700; color: var(--text-primary); font-size: 16px;"><?= htmlspecialchars($dossier['nama_pasien']) ?></div>
            </div>
            <div>
                <span style="color: var(--text-muted); font-size: 12px; font-weight: 600; text-transform: uppercase;">Usia / Gestasi:</span>
                <div style="font-weight: 700; color: var(--text-primary);"><?= $dossier['umur'] ?> Tahun / <?= $dossier['gestasi_pasien'] ?> Minggu</div>
            </div>
            <div>
                <span style="color: var(--text-muted); font-size: 12px; font-weight: 600; text-transform: uppercase;">Gravida / Paritas:</span>
                <div style="font-weight: 700; color: var(--text-primary);"><?= htmlspecialchars($dossier['gravida'] ?? 'G1P0A0') ?></div>
            </div>
            <div>
                <span style="color: var(--text-muted); font-size: 12px; font-weight: 600; text-transform: uppercase;">Tanggal Pemeriksaan:</span>
                <div style="font-weight: 700; color: var(--text-primary);"><?= date('d F Y', strtotime($dossier['tanggal'])) ?></div>
            </div>
            <div>
                <span style="color: var(--text-muted); font-size: 12px; font-weight: 600; text-transform: uppercase;">Alamat / Kontak:</span>
                <div style="font-size: 13px; color: var(--text-secondary);"><?= htmlspecialchars($dossier['alamat'] ?? '-') ?> (<?= htmlspecialchars($dossier['no_telp'] ?? '-') ?>)</div>
            </div>
        </div>
    </div>

    <!-- Prominent Diagnosis Banner -->
    <div style="background: <?= $headerBg ?>; color: #ffffff; border-radius: var(--radius-lg); padding: 24px 28px; margin-bottom: 28px; box-shadow: var(--shadow-md);">
        <div style="display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 16px;">
            <div>
                <div style="font-size: 12px; font-weight: 700; letter-spacing: 1px; text-transform: uppercase; color: rgba(255,255,255,0.85);">
                    HASIL INFERENSI RULE-BASED SYSTEM (FORWARD CHAINING):
                </div>
                <h1 style="font-size: 30px; font-weight: 800; color: #ffffff; margin-top: 4px; letter-spacing: -0.5px;">
                    <?= htmlspecialchars($dossier['kode_alternatif']) ?> — <?= strtoupper(htmlspecialchars($dossier['keputusan'])) ?>
                </h1>
                <div style="font-size: 13px; color: rgba(255,255,255,0.9); margin-top: 6px;">
                    <i class="fa-solid fa-code-fork"></i> Kaidah Produksi: <strong><?= htmlspecialchars($dossier['rule_terpicu'] ?? '-') ?></strong>
                </div>
            </div>
            <div style="text-align: right;">
                <span class="badge" style="background: #ffffff; color: #0f172a; font-size: 14px; padding: 8px 16px; font-weight: 800; box-shadow: 0 4px 12px rgba(0,0,0,0.15);">
                    <i class="fa-solid fa-shield-virus"></i> Level Risiko: <?= htmlspecialchars($dossier['level_risiko']) ?>
                </span>
                <div style="font-size: 12px; color: rgba(255,255,255,0.85); margin-top: 8px;">
                    Skor Akumulasi Risiko: <strong><?= $dossier['nilai'] ?> poin</strong>
                </div>
            </div>
        </div>
    </div>

    <!-- Vital Signs Summary Bar -->
    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(170px, 1fr)); gap: 14px; margin-bottom: 28px;">
        <div style="background: #ffffff; border: 1px solid var(--border-color); border-radius: var(--radius-md); padding: 14px 18px; text-align: center;">
            <div style="font-size: 11px; font-weight: 700; color: var(--text-muted); text-transform: uppercase;">Tekanan Sistolik</div>
            <div style="font-family: var(--font-heading); font-size: 24px; font-weight: 800; color: <?= $dossier['tekanan_sistolik'] >= 160 ? '#dc2626' : ($dossier['tekanan_sistolik'] >= 140 ? '#ea580c' : '#16a34a') ?>;">
                <?= $dossier['tekanan_sistolik'] ?> <span style="font-size: 12px; font-weight: 500; color: var(--text-muted);">mmHg</span>
            </div>
        </div>

        <div style="background: #ffffff; border: 1px solid var(--border-color); border-radius: var(--radius-md); padding: 14px 18px; text-align: center;">
            <div style="font-size: 11px; font-weight: 700; color: var(--text-muted); text-transform: uppercase;">Tekanan Diastolik</div>
            <div style="font-family: var(--font-heading); font-size: 24px; font-weight: 800; color: <?= $dossier['tekanan_diastolik'] >= 110 ? '#dc2626' : ($dossier['tekanan_diastolik'] >= 90 ? '#ea580c' : '#16a34a') ?>;">
                <?= $dossier['tekanan_diastolik'] ?> <span style="font-size: 12px; font-weight: 500; color: var(--text-muted);">mmHg</span>
            </div>
        </div>

        <div style="background: #ffffff; border: 1px solid var(--border-color); border-radius: var(--radius-md); padding: 14px 18px; text-align: center;">
            <div style="font-size: 11px; font-weight: 700; color: var(--text-muted); text-transform: uppercase;">Proteinuria (Dipstick)</div>
            <div style="font-family: var(--font-heading); font-size: 22px; font-weight: 800; color: <?= $dossier['proteinuria'] === 'Negatif' ? '#16a34a' : '#dc2626' ?>;">
                <?= htmlspecialchars($dossier['proteinuria']) ?>
            </div>
        </div>

        <div style="background: #ffffff; border: 1px solid var(--border-color); border-radius: var(--radius-md); padding: 14px 18px; text-align: center;">
            <div style="font-size: 11px; font-weight: 700; color: var(--text-muted); text-transform: uppercase;">Edema Patologis</div>
            <div style="font-family: var(--font-heading); font-size: 22px; font-weight: 800; color: <?= $dossier['edema'] === 'Ya' ? '#ea580c' : '#16a34a' ?>;">
                <?= htmlspecialchars($dossier['edema']) ?>
            </div>
        </div>

        <div style="background: #ffffff; border: 1px solid var(--border-color); border-radius: var(--radius-md); padding: 14px 18px; text-align: center;">
            <div style="font-size: 11px; font-weight: 700; color: var(--text-muted); text-transform: uppercase;">Status Kejang (C15)</div>
            <div style="font-family: var(--font-heading); font-size: 22px; font-weight: 800; color: <?= $dossier['kejang'] === 'Ya' ? '#dc2626' : '#16a34a' ?>;">
                <?= htmlspecialchars($dossier['kejang']) ?>
            </div>
        </div>
    </div>

    <!-- Clinical Warning Bar if Danger Signs are present -->
    <?php if (!empty($evalDetail['tanda_bahaya'])): ?>
        <div class="alert-emergency" style="margin-bottom: 28px;">
            <div class="alert-emergency-content">
                <i class="fa-solid fa-triangle-exclamation alert-emergency-icon"></i>
                <div>
                    <div class="alert-emergency-title">TANDA BAHAYA OBSTETRI TERDETEKSI (IMPENDING ECLAMPSIA):</div>
                    <ul style="margin-left: 20px; font-size: 13px; color: #b91c1c; margin-top: 4px;">
                        <?php foreach ($evalDetail['tanda_bahaya'] as $tb): ?>
                            <li><?= htmlspecialchars($tb) ?></li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            </div>
        </div>
    <?php endif; ?>

    <!-- Two-Column Section: Clinical Protocol & Inference Trace -->
    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(360px, 1fr)); gap: 24px; margin-bottom: 28px;">
        
        <!-- Left: Rekomendasi Klinis & Farmakoterapi -->
        <div style="background: #ffffff; border: 1px solid var(--border-color); border-radius: var(--radius-lg); padding: 22px;">
            <h3 style="font-size: 17px; font-weight: 700; margin-bottom: 16px; color: var(--primary-dark); display: flex; align-items: center; gap: 8px;">
                <i class="fa-solid fa-clipboard-check"></i> Protokol Penanganan Klinis Terstruktur
            </h3>

            <div style="margin-bottom: 18px;">
                <div style="font-size: 12px; font-weight: 700; color: var(--text-muted); text-transform: uppercase;">
                    1. Tindakan Segera & Jalur Rujukan:
                </div>
                <div style="font-weight: 700; color: #dc2626; font-size: 14px; margin-top: 4px;">
                    <?= htmlspecialchars($rekomendasi['tindakan_segera'] ?? '-') ?>
                </div>
                <div style="font-size: 13px; color: var(--text-secondary); margin-top: 4px;">
                    Fasilitas Rujukan: <strong><?= htmlspecialchars($rekomendasi['fasilitas_rujukan'] ?? '-') ?></strong>
                </div>
            </div>

            <div style="margin-bottom: 18px;">
                <div style="font-size: 12px; font-weight: 700; color: var(--text-muted); text-transform: uppercase;">
                    2. Posisi & Tindakan Suportif:
                </div>
                <div style="font-size: 13px; color: var(--text-secondary); margin-top: 4px; line-height: 1.5;">
                    <?= htmlspecialchars($rekomendasi['protokol_jalan_nafas'] ?? '-') ?>
                </div>
            </div>

            <div style="margin-bottom: 18px;">
                <div style="font-size: 12px; font-weight: 700; color: var(--text-muted); text-transform: uppercase;">
                    3. Farmakoterapi / Medikamentosa:
                </div>
                <?php if (isset($rekomendasi['medikamentosa']) && is_array($rekomendasi['medikamentosa'])): ?>
                    <div style="display: flex; flex-direction: column; gap: 8px; margin-top: 6px;">
                        <?php foreach ($rekomendasi['medikamentosa'] as $kMed => $vMed): ?>
                            <div style="background: #f8fafc; border-left: 3px solid var(--primary); padding: 8px 12px; border-radius: 4px; font-size: 12.5px;">
                                <strong><?= htmlspecialchars($kMed) ?></strong><br>
                                <span style="color: var(--text-secondary);"><?= htmlspecialchars($vMed) ?></span>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>

            <div style="margin-bottom: 18px; background: #fef2f2; border-left: 3px solid #dc2626; padding: 10px 14px; border-radius: 4px;">
                <div style="font-size: 12px; font-weight: 700; color: #991b1b; text-transform: uppercase;">
                    <i class="fa-solid fa-ban"></i> Kontraindikasi Medis:
                </div>
                <div style="font-size: 13px; color: #b91c1c; margin-top: 4px;">
                    <?= htmlspecialchars($rekomendasi['kontraindikasi'] ?? '-') ?>
                </div>
            </div>

            <div>
                <div style="font-size: 12px; font-weight: 700; color: var(--text-muted); text-transform: uppercase;">
                    4. Rencana Terminasi Kehamilan:
                </div>
                <div style="font-size: 13px; color: var(--text-secondary); margin-top: 4px;">
                    <?= htmlspecialchars($rekomendasi['terminasi_kehamilan'] ?? '-') ?>
                </div>
            </div>
        </div>

        <!-- Right: Forward Chaining Trace & Reasoning -->
        <div style="background: #ffffff; border: 1px solid var(--border-color); border-radius: var(--radius-lg); padding: 22px;">
            <h3 style="font-size: 17px; font-weight: 700; margin-bottom: 16px; color: var(--secondary); display: flex; align-items: center; gap: 8px;">
                <i class="fa-solid fa-network-wired"></i> Rantai Inferensi (Forward Chaining Trace)
            </h3>
            <p style="font-size: 12px; color: var(--text-muted); margin-bottom: 14px;">
                Rekam jejak transparan evaluasi kaidah produksi IF-THEN dari working memory fakta klinis:
            </p>

            <div class="trace-timeline">
                <?php if (!empty($traceSteps)): ?>
                    <?php foreach ($traceSteps as $ts): ?>
                        <?php
                            $isFire = (isset($ts['status']) && $ts['status'] === 'FIRE');
                            $stepClass = $isFire ? 'trace-step fire' : 'trace-step';
                            if ($isFire && ($dossier['kode_alternatif'] === 'A7' || $dossier['kode_alternatif'] === 'A5')) {
                                $stepClass .= ' emergency';
                            }
                        ?>
                        <div class="<?= $stepClass ?>">
                            <div class="trace-dot"></div>
                            <div class="trace-title">
                                <?= htmlspecialchars($ts['title']) ?>
                                <?php if (isset($ts['status'])): ?>
                                    <span class="badge <?= $isFire ? 'badge-rendah' : 'badge-rule' ?>" style="font-size: 10px; margin-left: 6px;">
                                        <?= htmlspecialchars($ts['status']) ?>
                                    </span>
                                <?php endif; ?>
                            </div>
                            <?php if (!empty($ts['rule'])): ?>
                                <div style="font-family: monospace; font-size: 11px; color: #475569; background: #f8fafc; padding: 4px 8px; border-radius: 4px; margin-top: 4px;">
                                    <?= htmlspecialchars($ts['rule']) ?>
                                </div>
                            <?php endif; ?>
                            <div class="trace-desc">
                                <?= htmlspecialchars($ts['detail']) ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Complete Criteria Fulfillment Matrix (C1 - C16) -->
    <div style="background: #ffffff; border: 1px solid var(--border-color); border-radius: var(--radius-lg); padding: 22px; margin-bottom: 28px;">
        <h3 style="font-size: 17px; font-weight: 700; margin-bottom: 14px; color: var(--text-primary); display: flex; align-items: center; gap: 8px;">
            <i class="fa-solid fa-list-check"></i> Matriks 16 Kriteria Klinis (C1 - C16) Pasien
        </h3>
        <div class="table-responsive">
            <table class="custom-table" style="font-size: 13px;">
                <thead>
                    <tr>
                        <th style="width: 60px;">Kode</th>
                        <th>Parameter Kriteria</th>
                        <th>Nilai Pasien</th>
                        <th>Kategori / Interpretasi</th>
                        <th>Bobot</th>
                        <th style="text-align: center;">Status Risiko</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($evalDetail['facts'] as $code => $fact): ?>
                        <tr>
                            <td><strong style="color: var(--primary);"><?= $code ?></strong></td>
                            <td><?= htmlspecialchars($fact['nama']) ?></td>
                            <td><strong><?= htmlspecialchars($fact['nilai']) ?></strong></td>
                            <td style="color: var(--text-secondary);"><?= htmlspecialchars($fact['kategori'] ?? $fact['nilai']) ?></td>
                            <td><?= number_format($fact['bobot'], 1) ?></td>
                            <td style="text-align: center;">
                                <?php if ($fact['is_risiko']): ?>
                                    <span class="badge badge-tinggi" style="font-size: 11px;">Faktor Risiko (+<?= $fact['bobot'] ?>)</span>
                                <?php else: ?>
                                    <span class="badge badge-rendah" style="font-size: 11px;">Normal (0)</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Catatan Tambahan Pemeriksa -->
    <?php if (!empty($dossier['catatan_medis'])): ?>
        <div style="background: #f8fafc; border: 1px solid var(--border-color); border-radius: var(--radius-md); padding: 16px 20px; margin-bottom: 28px;">
            <div style="font-size: 12px; font-weight: 700; color: var(--text-muted); text-transform: uppercase; margin-bottom: 4px;">
                <i class="fa-solid fa-comment-medical"></i> Catatan Klinis Tambahan:
            </div>
            <p style="font-size: 14px; color: var(--text-secondary); line-height: 1.5;">
                <?= nl2br(htmlspecialchars($dossier['catatan_medis'])) ?>
            </p>
        </div>
    <?php endif; ?>

    <!-- Signature Block for Official Medical Record -->
    <div class="signature-block">
        <div class="sign-box">
            <div style="font-size: 13px; color: var(--text-muted);">Mengetahui / Verifikator:</div>
            <div style="font-size: 13px; font-weight: 600; margin-top: 4px;">Bidan Koordinator PONEK</div>
            <div class="sign-line">( ..................................................... )</div>
            <div style="font-size: 11px; color: var(--text-muted); margin-top: 4px;">NIP / SIP Bidan</div>
        </div>

        <div class="sign-box">
            <div style="font-size: 13px; color: var(--text-muted);"><?= date('d F Y') ?></div>
            <div style="font-size: 13px; font-weight: 600; margin-top: 4px;">Dokter Penanggung Jawab Pelayanan (DPJP)</div>
            <div class="sign-line">( dr. .............................................., Sp.OG )</div>
            <div style="font-size: 11px; color: var(--text-muted); margin-top: 4px;">SIP Dokter Spesialis Obstetri & Ginekologi</div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
