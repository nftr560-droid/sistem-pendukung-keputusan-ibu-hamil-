<?php
$pageTitle = "Basis Pengetahuan CDSS (Knowledge Base)";
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/cdss_engine.php';

$pdo = getDBConnection();

// Fetch Kriteria
$kriteria = $pdo->query("SELECT * FROM `kriteria` ORDER BY id_kriteria ASC")->fetchAll();

// Fetch Alternatif
$alternatif = $pdo->query("SELECT * FROM `alternatif` ORDER BY id_alternatif ASC")->fetchAll();

// Structured Kaidah Produksi IF-THEN
$ruleBase = [
    [
        'kode' => 'R1',
        'nama' => 'Kaidah Hipertensi Gestasional',
        'if'   => 'C2 ≥ 20 minggu AND (C3 ≥ 140 OR C4 ≥ 90) AND C5 = Negatif AND C13 = Tidak',
        'then' => 'A2 — Hipertensi Gestasional',
        'level' => 'Sedang',
        'ket'  => 'Hipertensi baru muncul setelah usia kehamilan 20 minggu tanpa disertai proteinuria.'
    ],
    [
        'kode' => 'R2',
        'nama' => 'Kaidah Hipertensi Kronis',
        'if'   => '(C2 < 20 minggu AND (C3 ≥ 140 OR C4 ≥ 90)) OR (C13 = Ya AND C5 = Negatif)',
        'then' => 'A3 — Hipertensi Kronis',
        'level' => 'Sedang',
        'ket'  => 'Hipertensi sudah ada sebelum masa kehamilan atau didiagnosis sebelum usia gestasi 20 minggu.'
    ],
    [
        'kode' => 'R3',
        'nama' => 'Kaidah Preeklampsia Ringan',
        'if'   => 'C2 ≥ 20 minggu AND (C3 ≥ 140 OR C4 ≥ 90) AND C5 ≥ +1 (Positif)',
        'then' => 'A4 — Preeklampsia Ringan',
        'level' => 'Sedang-Tinggi',
        'ket'  => 'Hipertensi baru disertai proteinuria bermakna (≥ +1) setelah 20 minggu tanpa gejala keparahan berat.'
    ],
    [
        'kode' => 'R4',
        'nama' => 'Kaidah Preeklampsia Berat (PEB)',
        'if'   => 'Preeklampsia Terpenuhi AND ((C3 ≥ 160 OR C4 ≥ 110 OR C5 ≥ +3) OR (C7=Ya OR C8=Ya OR C9=Ya OR C10=Ya OR C11=Ya OR C12=Ya))',
        'then' => 'A5 — Preeklampsia Berat',
        'level' => 'Tinggi',
        'ket'  => 'Preeklampsia berkembang dengan tekanan darah sangat tinggi (≥160/110) atau gangguan fungsi organ sasaran (impending eclampsia).'
    ],
    [
        'kode' => 'R5',
        'nama' => 'Kaidah Eklampsia (Gawat Darurat)',
        'if'   => '(Preeklampsia / Hipertensi Terpenuhi) AND C15 = Ya (Kejang)',
        'then' => 'A7 — Eklampsia',
        'level' => 'Sangat Tinggi (gawat darurat)',
        'ket'  => 'Preeklampsia berat disertai bangkitan kejang tonik-klonik maternal — kondisi kegawatdaruratan mutlak obstetri.'
    ],
    [
        'kode' => 'R6',
        'nama' => 'Kaidah Superimposed Preeclampsia',
        'if'   => 'C13 = Ya (Riwayat Hipertensi Kronis) AND C2 ≥ 20 minggu AND C5 ≥ +1 (Proteinuria Baru)',
        'then' => 'A6 — Superimposed Preeclampsia',
        'level' => 'Tinggi',
        'ket'  => 'Ibu dengan hipertensi kronis yang mengalami kerusakan endotel ginjal baru (timbul proteinuria) setelah usia gestasi 20 minggu.'
    ]
];

// Matriks Relasi Kriteria vs Alternatif (dari Sheet Matriks Relasi Excel)
$matriksRelasi = [
    ['kode' => 'C2', 'kriteria' => 'Usia kehamilan ≥ 20 minggu', 'A1' => '-', 'A2' => 'V', 'A3' => '-', 'A4' => 'V', 'A5' => 'V', 'A6' => 'V', 'A7' => 'V'],
    ['kode' => 'C2', 'kriteria' => 'Usia kehamilan < 20 minggu', 'A1' => '-', 'A2' => '-', 'A3' => 'V', 'A4' => '-', 'A5' => '-', 'A6' => '-', 'A7' => '-'],
    ['kode' => 'C3', 'kriteria' => 'Sistolik ≥ 140 mmHg', 'A1' => '-', 'A2' => 'V', 'A3' => 'V', 'A4' => 'V', 'A5' => '-', 'A6' => 'V', 'A7' => 'V'],
    ['kode' => 'C3', 'kriteria' => 'Sistolik ≥ 160 mmHg', 'A1' => '-', 'A2' => '-', 'A3' => '-', 'A4' => '-', 'A5' => 'V', 'A6' => '-', 'A7' => '-'],
    ['kode' => 'C4', 'kriteria' => 'Diastolik ≥ 90 mmHg', 'A1' => '-', 'A2' => 'V', 'A3' => 'V', 'A4' => 'V', 'A5' => '-', 'A6' => 'V', 'A7' => 'V'],
    ['kode' => 'C4', 'kriteria' => 'Diastolik ≥ 110 mmHg', 'A1' => '-', 'A2' => '-', 'A3' => '-', 'A4' => '-', 'A5' => 'V', 'A6' => '-', 'A7' => '-'],
    ['kode' => 'C5', 'kriteria' => 'Proteinuria ≥ +1', 'A1' => '-', 'A2' => '-', 'A3' => '-', 'A4' => 'V', 'A5' => 'V', 'A6' => 'V', 'A7' => 'V'],
    ['kode' => 'C7', 'kriteria' => 'Sakit kepala hebat', 'A1' => '-', 'A2' => '-', 'A3' => '-', 'A4' => '-', 'A5' => 'O', 'A6' => '-', 'A7' => 'V'],
    ['kode' => 'C8', 'kriteria' => 'Gangguan penglihatan', 'A1' => '-', 'A2' => '-', 'A3' => '-', 'A4' => '-', 'A5' => 'O', 'A6' => '-', 'A7' => 'V'],
    ['kode' => 'C9', 'kriteria' => 'Nyeri ulu hati (epigastrium)', 'A1' => '-', 'A2' => '-', 'A3' => '-', 'A4' => '-', 'A5' => 'O', 'A6' => '-', 'A7' => 'V'],
    ['kode' => 'C10', 'kriteria' => 'Trombosit rendah (<100k)', 'A1' => '-', 'A2' => '-', 'A3' => '-', 'A4' => '-', 'A5' => 'O', 'A6' => '-', 'A7' => 'V'],
    ['kode' => 'C11', 'kriteria' => 'Fungsi hati terganggu (SGOT/PT ↑)', 'A1' => '-', 'A2' => '-', 'A3' => '-', 'A4' => '-', 'A5' => 'O', 'A6' => '-', 'A7' => 'V'],
    ['kode' => 'C12', 'kriteria' => 'Fungsi ginjal terganggu (Kreatinin ↑)', 'A1' => '-', 'A2' => '-', 'A3' => '-', 'A4' => '-', 'A5' => 'O', 'A6' => '-', 'A7' => 'V'],
    ['kode' => 'C13', 'kriteria' => 'Riwayat hipertensi sebelum hamil', 'A1' => '-', 'A2' => '-', 'A3' => 'V', 'A4' => '-', 'A5' => '-', 'A6' => 'V', 'A7' => '-'],
    ['kode' => 'C15', 'kriteria' => 'Kejang', 'A1' => '-', 'A2' => '-', 'A3' => '-', 'A4' => '-', 'A5' => '-', 'A6' => '-', 'A7' => 'V'],
];

// Tabel Keputusan / Decision Table
$decisionTable = [
    ['no' => 1, 'uk' => 'Bebas', 'sys' => '< 140', 'dia' => '< 90', 'prot' => 'Negatif', 'gejala' => 'Tidak', 'kejang' => 'Tidak', 'riw' => 'Tidak', 'diag' => 'Normal / tidak hipertensi', 'level' => 'Rendah'],
    ['no' => 2, 'uk' => '≥ 20 mgg', 'sys' => '≥ 140', 'dia' => '≥ 90', 'prot' => 'Negatif', 'gejala' => 'Tidak', 'kejang' => 'Tidak', 'riw' => 'Tidak', 'diag' => 'Hipertensi Gestasional', 'level' => 'Sedang'],
    ['no' => 3, 'uk' => '< 20 mgg', 'sys' => '≥ 140', 'dia' => '≥ 90', 'prot' => '-', 'gejala' => '-', 'kejang' => 'Tidak', 'riw' => '-', 'diag' => 'Hipertensi Kronis', 'level' => 'Sedang'],
    ['no' => 4, 'uk' => '≥ 20 mgg', 'sys' => '≥ 140', 'dia' => '≥ 90', 'prot' => '≥ +1', 'gejala' => 'Tidak', 'kejang' => 'Tidak', 'riw' => '-', 'diag' => 'Preeklampsia Ringan', 'level' => 'Sedang-Tinggi'],
    ['no' => 5, 'uk' => '≥ 20 mgg', 'sys' => '≥ 160', 'dia' => '≥ 110', 'prot' => '≥ +1', 'gejala' => 'Ya (OR)', 'kejang' => 'Tidak', 'riw' => '-', 'diag' => 'Preeklampsia Berat', 'level' => 'Tinggi'],
    ['no' => 6, 'uk' => '≥ 20 mgg', 'sys' => '≥ 140', 'dia' => '≥ 90', 'prot' => '≥ +1', 'gejala' => '-', 'kejang' => 'Tidak', 'riw' => 'Ya', 'diag' => 'Superimposed Preeclampsia', 'level' => 'Tinggi'],
    ['no' => 7, 'uk' => '≥ 20 mgg', 'sys' => '≥ 160', 'dia' => '≥ 110', 'prot' => '≥ +1', 'gejala' => 'Ya', 'kejang' => 'Ya', 'riw' => '-', 'diag' => 'Eklampsia', 'level' => 'Sangat Tinggi (gawat darurat)'],
];

require_once __DIR__ . '/includes/header.php';
?>

<div style="margin-bottom: 24px;">
    <h1 style="font-size: 24px; font-weight: 800; color: var(--text-primary);">
        <i class="fa-solid fa-book-medical" style="color: var(--primary);"></i> Basis Pengetahuan CDSS (Knowledge Base)
    </h1>
    <p style="font-size: 14px; color: var(--text-secondary);">
        Dokumentasi Formal Kriteria (C1-C16), Alternatif (A1-A7), Matriks Relasi, Kaidah Produksi IF-THEN (R1-R6), dan Simulator Interaktif
    </p>
</div>

<!-- Tabs Navigation -->
<div style="display: flex; gap: 8px; border-bottom: 2px solid var(--border-color); margin-bottom: 24px; overflow-x: auto; padding-bottom: 2px;">
    <button onclick="showTab('tab-kriteria')" class="tab-btn active" id="btn-kriteria">
        <i class="fa-solid fa-list-check"></i> Kriteria (C1-C16)
    </button>
    <button onclick="showTab('tab-alternatif')" class="tab-btn" id="btn-alternatif">
        <i class="fa-solid fa-hospital"></i> Alternatif (A1-A7)
    </button>
    <button onclick="showTab('tab-matriks')" class="tab-btn" id="btn-matriks">
        <i class="fa-solid fa-table-cells"></i> Matriks Relasi
    </button>
    <button onclick="showTab('tab-rules')" class="tab-btn" id="btn-rules">
        <i class="fa-solid fa-network-wired"></i> Kaidah Rule (R1-R6)
    </button>
    <button onclick="showTab('tab-decision')" class="tab-btn" id="btn-decision">
        <i class="fa-solid fa-table"></i> Tabel Keputusan
    </button>
    <button onclick="showTab('tab-sandbox')" class="tab-btn" id="btn-sandbox" style="background: var(--primary-50); color: var(--primary-dark); font-weight: 700;">
        <i class="fa-solid fa-flask"></i> Simulator Sandbox
    </button>
</div>

<!-- TAB 1: KRITERIA -->
<div id="tab-kriteria" class="tab-content" style="display: block;">
    <div class="card">
        <div class="card-header">
            <h3 class="card-title"><i class="fa-solid fa-list-check" style="color: var(--primary);"></i> Tabel Kriteria Klinis & Bobot (C1 - C16)</h3>
            <span class="badge" style="background: var(--primary-100); color: var(--primary-dark);">16 Kriteria Terdaftar</span>
        </div>
        <div class="table-responsive">
            <table class="custom-table">
                <thead>
                    <tr>
                        <th style="width: 70px;">Kode</th>
                        <th>Parameter Kriteria / Gejala</th>
                        <th>Sub-Nilai / Kategori</th>
                        <th style="width: 90px;">Bobot</th>
                        <th>Keterangan Klinis</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($kriteria as $k): ?>
                        <tr>
                            <td><strong style="color: var(--primary); font-size: 15px;"><?= htmlspecialchars($k['kode_kriteria']) ?></strong></td>
                            <td><strong><?= htmlspecialchars($k['nama_kriteria']) ?></strong></td>
                            <td style="color: var(--text-secondary);"><?= htmlspecialchars($k['sub_nilai'] ?? '-') ?></td>
                            <td>
                                <span class="badge" style="background: #f1f5f9; color: #0f172a; font-weight: 700;">
                                    <?= number_format($k['bobot'], 2) ?>
                                </span>
                            </td>
                            <td style="font-size: 13px; color: var(--text-secondary);"><?= htmlspecialchars($k['keterangan'] ?? '-') ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- TAB 2: ALTERNATIF -->
<div id="tab-alternatif" class="tab-content" style="display: none;">
    <div class="card">
        <div class="card-header">
            <h3 class="card-title"><i class="fa-solid fa-hospital" style="color: var(--primary);"></i> Tabel Alternatif Diagnosis CDSS (A1 - A7)</h3>
            <span class="badge" style="background: var(--primary-100); color: var(--primary-dark);">7 Diagnosis Klinis</span>
        </div>
        <div class="table-responsive">
            <table class="custom-table">
                <thead>
                    <tr>
                        <th style="width: 70px;">Kode</th>
                        <th>Alternatif (Diagnosis)</th>
                        <th>Level Risiko</th>
                        <th>Rekomendasi Tindak Lanjut Klinis</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($alternatif as $a): ?>
                        <?php
                            $bClass = 'badge-rendah';
                            if (strpos($a['level_risiko'], 'Sangat Tinggi') !== false) $bClass = 'badge-gawat';
                            elseif (strpos($a['level_risiko'], 'Tinggi') !== false) $bClass = 'badge-tinggi';
                            elseif (strpos($a['level_risiko'], 'Sedang-Tinggi') !== false) $bClass = 'badge-sedang-tinggi';
                            elseif (strpos($a['level_risiko'], 'Sedang') !== false) $bClass = 'badge-sedang';
                        ?>
                        <tr>
                            <td><strong style="color: var(--primary); font-size: 15px;"><?= htmlspecialchars($a['kode_alternatif']) ?></strong></td>
                            <td><strong><?= htmlspecialchars($a['nama_alternatif']) ?></strong></td>
                            <td>
                                <span class="badge <?= $bClass ?>">
                                    <?= htmlspecialchars($a['level_risiko']) ?>
                                </span>
                            </td>
                            <td style="font-size: 13px; color: var(--text-secondary); line-height: 1.5;"><?= htmlspecialchars($a['rekomendasi'] ?? $a['keterangan']) ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- TAB 3: MATRIKS RELASI -->
<div id="tab-matriks" class="tab-content" style="display: none;">
    <div class="card">
        <div class="card-header">
            <h3 class="card-title"><i class="fa-solid fa-table-cells" style="color: var(--primary);"></i> Matriks Relasi Kriteria vs Alternatif Diagnosis</h3>
            <div style="font-size: 12px; color: var(--text-muted);">
                <span style="color: #10b981; font-weight: 700;">V</span> = Wajib (AND) | 
                <span style="color: #f59e0b; font-weight: 700;">O</span> = Salah satu (OR) | 
                <span>-</span> = Tidak relevan
            </div>
        </div>
        <div class="table-responsive">
            <table class="custom-table" style="text-align: center;">
                <thead>
                    <tr>
                        <th style="width: 60px;">Kode</th>
                        <th style="text-align: left;">Kriteria Klinis</th>
                        <th style="text-align: center;">A1 (Normal)</th>
                        <th style="text-align: center;">A2 (Gestasional)</th>
                        <th style="text-align: center;">A3 (Kronis)</th>
                        <th style="text-align: center;">A4 (PE Ringan)</th>
                        <th style="text-align: center;">A5 (PE Berat)</th>
                        <th style="text-align: center;">A6 (Superimposed)</th>
                        <th style="text-align: center;">A7 (Eklampsia)</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($matriksRelasi as $mr): ?>
                        <tr>
                            <td><strong style="color: var(--primary);"><?= $mr['kode'] ?></strong></td>
                            <td style="text-align: left;"><?= $mr['kriteria'] ?></td>
                            <?php foreach (['A1', 'A2', 'A3', 'A4', 'A5', 'A6', 'A7'] as $col): ?>
                                <td>
                                    <?php if ($mr[$col] === 'V'): ?>
                                        <span style="display: inline-block; width: 24px; height: 24px; border-radius: 50%; background: #dcfce7; color: #166534; font-weight: 800; line-height: 24px;">V</span>
                                    <?php elseif ($mr[$col] === 'O'): ?>
                                        <span style="display: inline-block; width: 24px; height: 24px; border-radius: 50%; background: #fef9c3; color: #854d0e; font-weight: 800; line-height: 24px;">O</span>
                                    <?php else: ?>
                                        <span style="color: #cbd5e1;">-</span>
                                    <?php endif; ?>
                                </td>
                            <?php endforeach; ?>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- TAB 4: KAIDAH RULE (R1 - R6) -->
<div id="tab-rules" class="tab-content" style="display: none;">
    <div class="card">
        <div class="card-header">
            <h3 class="card-title"><i class="fa-solid fa-network-wired" style="color: var(--primary);"></i> Basis Kaidah Produksi IF-THEN (Forward Chaining)</h3>
            <span class="badge" style="background: var(--primary-100); color: var(--primary-dark);">6 Kaidah Utama</span>
        </div>

        <div style="display: flex; flex-direction: column; gap: 16px;">
            <?php foreach ($ruleBase as $rb): ?>
                <div style="border: 1px solid var(--border-color); border-radius: var(--radius-md); padding: 18px 22px; background: #ffffff;">
                    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 8px;">
                        <span style="font-family: monospace; font-size: 15px; font-weight: 800; color: var(--primary-dark); background: var(--primary-50); padding: 2px 10px; border-radius: 4px;">
                            <?= $rb['kode'] ?>: <?= $rb['nama'] ?>
                        </span>
                        <span class="badge <?= strpos($rb['level'], 'Sangat Tinggi') !== false ? 'badge-gawat' : (strpos($rb['level'], 'Tinggi') !== false ? 'badge-tinggi' : (strpos($rb['level'], 'Sedang-Tinggi') !== false ? 'badge-sedang-tinggi' : 'badge-sedang')) ?>">
                            <?= $rb['level'] ?>
                        </span>
                    </div>

                    <div style="background: #f8fafc; border-left: 3px solid var(--primary); padding: 10px 14px; border-radius: 4px; font-family: monospace; font-size: 13px; margin: 10px 0;">
                        <div style="color: #0369a1;"><strong>IF:</strong> <?= htmlspecialchars($rb['if']) ?></div>
                        <div style="color: #0f766e; margin-top: 4px;"><strong>THEN:</strong> <?= htmlspecialchars($rb['then']) ?></div>
                    </div>

                    <div style="font-size: 13px; color: var(--text-secondary);">
                        <i class="fa-solid fa-circle-info"></i> Penjelasan: <?= htmlspecialchars($rb['ket']) ?>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
</div>

<!-- TAB 5: TABEL KEPUTUSAN -->
<div id="tab-decision" class="tab-content" style="display: none;">
    <div class="card">
        <div class="card-header">
            <h3 class="card-title"><i class="fa-solid fa-table" style="color: var(--primary);"></i> Tabel Keputusan (Decision Table) Ringkas</h3>
            <span class="badge" style="background: #f1f5f9; color: #475569;">Pemetaan Gejala vs Diagnosis</span>
        </div>
        <div class="table-responsive">
            <table class="custom-table" style="font-size: 13px;">
                <thead>
                    <tr>
                        <th style="width: 40px;">No</th>
                        <th>Usia Kehamilan</th>
                        <th>Sistolik</th>
                        <th>Diastolik</th>
                        <th>Proteinuria</th>
                        <th>Gejala Berat*</th>
                        <th>Kejang</th>
                        <th>Riwayat HT</th>
                        <th>Hasil Diagnosis</th>
                        <th>Level Risiko</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($decisionTable as $dt): ?>
                        <tr>
                            <td><?= $dt['no'] ?></td>
                            <td><strong><?= $dt['uk'] ?></strong></td>
                            <td><?= $dt['sys'] ?></td>
                            <td><?= $dt['dia'] ?></td>
                            <td><strong style="color: <?= $dt['prot'] === 'Negatif' ? '#16a34a' : ($dt['prot'] === '-' ? '#94a3b8' : '#dc2626') ?>;"><?= $dt['prot'] ?></strong></td>
                            <td><?= $dt['gejala'] ?></td>
                            <td><strong style="color: <?= $dt['kejang'] === 'Ya' ? '#dc2626' : '#16a34a' ?>;"><?= $dt['kejang'] ?></strong></td>
                            <td><?= $dt['riw'] ?></td>
                            <td><strong style="color: var(--primary-dark);"><?= $dt['diag'] ?></strong></td>
                            <td>
                                <span class="badge <?= strpos($dt['level'], 'Sangat Tinggi') !== false ? 'badge-gawat' : (strpos($dt['level'], 'Tinggi') !== false ? 'badge-tinggi' : (strpos($dt['level'], 'Sedang-Tinggi') !== false ? 'badge-sedang-tinggi' : (strpos($dt['level'], 'Sedang') !== false ? 'badge-sedang' : 'badge-rendah'))) ?>">
                                    <?= $dt['level'] ?>
                                </span>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <div style="font-size: 12px; color: var(--text-muted); margin-top: 14px;">
            *Gejala berat = Salah satu dari: Sakit kepala hebat, gangguan visual, nyeri epigastrium, trombosit rendah (<100k), fungsi hati/ginjal terganggu.
        </div>
    </div>
</div>

<!-- TAB 6: SIMULATOR SANDBOX -->
<div id="tab-sandbox" class="tab-content" style="display: none;">
    <div class="card">
        <div class="card-header">
            <div>
                <h3 class="card-title"><i class="fa-solid fa-flask" style="color: var(--primary);"></i> Simulator Interaktif Kaidah CDSS (Clinical Sandbox)</h3>
                <p class="card-subtitle">Uji coba kombinasi kriteria klinis sembarang secara langsung tanpa menyimpan ke database</p>
            </div>
        </div>

        <form id="sandboxForm">
            <div style="display: grid; grid-template-columns: 2fr 1fr; gap: 24px; align-items: start;">
                <div>
                    <div class="form-grid" style="margin-bottom: 20px;">
                        <div class="form-group">
                            <label class="form-label">Usia Kehamilan (Minggu Gestasi)</label>
                            <input type="number" id="sb_uk" class="form-control" value="28" min="4" max="44">
                        </div>
                        <div class="form-group">
                            <label class="form-label">Tekanan Sistolik (mmHg)</label>
                            <input type="number" id="sb_sys" class="form-control" value="165" min="70" max="250">
                        </div>
                        <div class="form-group">
                            <label class="form-label">Tekanan Diastolik (mmHg)</label>
                            <input type="number" id="sb_dia" class="form-control" value="110" min="40" max="160">
                        </div>
                        <div class="form-group">
                            <label class="form-label">Proteinuria (Dipstick)</label>
                            <select id="sb_prot" class="form-control">
                                <option value="Negatif">Negatif</option>
                                <option value="+1">+1 (Positif 1)</option>
                                <option value="+2">+2 (Positif 2)</option>
                                <option value="+3" selected>+3 (Positif 3 - Berat)</option>
                                <option value="+4">+4 (Positif 4)</option>
                            </select>
                        </div>
                    </div>

                    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 14px; margin-bottom: 20px;">
                        <div class="form-group">
                            <label class="form-label">Sakit Kepala Hebat (C7)</label>
                            <select id="sb_c7" class="form-control"><option value="Tidak">Tidak</option><option value="Ya" selected>Ya</option></select>
                        </div>
                        <div class="form-group">
                            <label class="form-label">Gangguan Penglihatan (C8)</label>
                            <select id="sb_c8" class="form-control"><option value="Tidak" selected>Tidak</option><option value="Ya">Ya</option></select>
                        </div>
                        <div class="form-group">
                            <label class="form-label">Nyeri Epigastrium (C9)</label>
                            <select id="sb_c9" class="form-control"><option value="Tidak" selected>Tidak</option><option value="Ya">Ya</option></select>
                        </div>
                        <div class="form-group">
                            <label class="form-label">Riwayat Hipertensi (C13)</label>
                            <select id="sb_c13" class="form-control"><option value="Tidak" selected>Tidak</option><option value="Ya">Ya</option></select>
                        </div>
                        <div class="form-group">
                            <label class="form-label" style="color: #dc2626; font-weight: 700;">Kejang (C15)</label>
                            <select id="sb_c15" class="form-control"><option value="Tidak" selected>Tidak</option><option value="Ya">Ya (Eklampsia)</option></select>
                        </div>
                    </div>

                    <button type="button" onclick="runSandboxInference()" class="btn btn-primary btn-sm">
                        <i class="fa-solid fa-play"></i> Jalankan Inferensi Sandbox
                    </button>
                </div>

                <!-- Hasil Sandbox -->
                <div style="background: #f8fafc; border: 2px solid var(--border-color); border-radius: var(--radius-lg); padding: 22px;">
                    <div style="font-size: 11px; font-weight: 700; color: var(--text-muted); text-transform: uppercase;">Hasil Inferensi Realtime:</div>
                    <div id="sbResultDiag" style="font-family: var(--font-heading); font-size: 22px; font-weight: 800; color: var(--primary-dark); margin-top: 6px;">
                        Preeklampsia Berat
                    </div>
                    <div id="sbResultRule" style="font-family: monospace; font-size: 12px; color: var(--text-secondary); margin-top: 4px;">
                        Aturan Terpicu: R4 (Kaidah Preeklampsia Berat)
                    </div>
                    <div style="margin-top: 10px;">
                        <span id="sbResultBadge" class="badge badge-tinggi">Tinggi</span>
                    </div>

                    <div style="margin-top: 16px; border-top: 1px solid var(--border-color); padding-top: 14px;">
                        <div style="font-size: 12px; font-weight: 700; color: var(--text-primary); margin-bottom: 4px;">Tindakan Klinis:</div>
                        <div id="sbResultAction" style="font-size: 12.5px; color: var(--text-secondary); line-height: 1.4;">
                            Rujuk segera ke fasilitas obstetri lengkap, pasang infus MgSO4, pertimbangkan rawat inap.
                        </div>
                    </div>
                </div>
            </div>
        </form>
    </div>
</div>

<style>
.tab-btn {
    padding: 10px 18px;
    font-family: var(--font-main);
    font-size: 14px;
    font-weight: 600;
    color: var(--text-secondary);
    background: transparent;
    border: none;
    border-bottom: 3px solid transparent;
    cursor: pointer;
    transition: all 0.2s ease;
    white-space: nowrap;
    border-radius: var(--radius-md) var(--radius-md) 0 0;
}

.tab-btn:hover {
    color: var(--primary);
    background: #f1f5f9;
}

.tab-btn.active {
    color: var(--primary-dark);
    border-bottom-color: var(--primary);
    background: #ffffff;
}
</style>

<script>
function showTab(tabId) {
    document.querySelectorAll('.tab-content').forEach(el => el.style.display = 'none');
    document.querySelectorAll('.tab-btn').forEach(btn => btn.classList.remove('active'));

    const activeTab = document.getElementById(tabId);
    if (activeTab) activeTab.style.display = 'block';

    const btnId = tabId.replace('tab-', 'btn-');
    const activeBtn = document.getElementById(btnId);
    if (activeBtn) activeBtn.classList.add('active');
}

function runSandboxInference() {
    const params = new URLSearchParams();
    params.append('usia_kehamilan', document.getElementById('sb_uk').value);
    params.append('tekanan_sistolik', document.getElementById('sb_sys').value);
    params.append('tekanan_diastolik', document.getElementById('sb_dia').value);
    params.append('proteinuria', document.getElementById('sb_prot').value);
    params.append('sakit_kepala', document.getElementById('sb_c7').value);
    params.append('gangguan_penglihatan', document.getElementById('sb_c8').value);
    params.append('nyeri_ulu_hati', document.getElementById('sb_c9').value);
    params.append('riwayat_hipertensi', document.getElementById('sb_c13').value);
    params.append('kejang', document.getElementById('sb_c15').value);

    fetch('api/simulasi.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: params.toString()
    })
    .then(r => r.json())
    .then(data => {
        if (data.status === 'success') {
            const res = data.result;
            document.getElementById('sbResultDiag').textContent = res.nama_alternatif;
            document.getElementById('sbResultRule').textContent = `Aturan Terpicu: ${res.rule_terpicu}`;
            document.getElementById('sbResultAction').textContent = res.rekomendasi.tindakan_segera || '-';

            const badge = document.getElementById('sbResultBadge');
            badge.textContent = res.level_risiko;
            let bClass = 'badge-rendah';
            if (res.level_risiko.includes('Sangat Tinggi')) bClass = 'badge-gawat';
            else if (res.level_risiko.includes('Tinggi')) bClass = 'badge-tinggi';
            else if (res.level_risiko.includes('Sedang-Tinggi')) bClass = 'badge-sedang-tinggi';
            else if (res.level_risiko.includes('Sedang')) bClass = 'badge-sedang';
            badge.className = `badge ${bClass}`;
        }
    });
}

// Auto run once when tab opens or on load
document.addEventListener('DOMContentLoaded', function () {
    const sbInputs = ['sb_uk', 'sb_sys', 'sb_dia', 'sb_prot', 'sb_c7', 'sb_c8', 'sb_c9', 'sb_c13', 'sb_c15'];
    sbInputs.forEach(id => {
        const el = document.getElementById(id);
        if (el) el.addEventListener('change', runSandboxInference);
    });
});
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
