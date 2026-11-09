<?php
/**
 * Database Seeder for SPKK Hipertensi Kehamilan
 * Populates Kriteria, Alternatif, and initial realistic clinical cases
 */

require_once __DIR__ . '/../config/database.php';

function runSeed($force = false) {
    checkAndMigrateSchema();
    $pdo = getDBConnection();

    // Check if already seeded
    $altCount = $pdo->query("SELECT COUNT(*) FROM `alternatif`")->fetchColumn();
    $kritCount = $pdo->query("SELECT COUNT(*) FROM `kriteria`")->fetchColumn();

    if ($altCount > 0 && $kritCount > 0 && !$force) {
        return ["status" => "already_seeded", "message" => "Database sudah memiliki data dasar kriteria dan alternatif."];
    }

    if ($force) {
        // Disable foreign keys temporarily for clean wipe
        $pdo->exec("SET FOREIGN_KEY_CHECKS = 0");
        $pdo->exec("TRUNCATE TABLE `hasil_keputusan`");
        $pdo->exec("TRUNCATE TABLE `pemeriksaan`");
        $pdo->exec("TRUNCATE TABLE `pasien`");
        $pdo->exec("TRUNCATE TABLE `alternatif`");
        $pdo->exec("TRUNCATE TABLE `kriteria`");
        $pdo->exec("SET FOREIGN_KEY_CHECKS = 1");
    }

    // 1. Seed Alternatif (A1 - A7)
    $alternatifData = [
        [
            'kode' => 'A1',
            'nama' => 'Normal / tidak hipertensi',
            'level' => 'Rendah',
            'rekom' => 'Pemeriksaan antenatal rutin sesuai jadwal standar (ANC terpadu), konsumsi nutrisi seimbang, hidrasi cukup, dan pertahankan pola hidup sehat.',
            'ket' => 'Kondisi kehamilan fisiologis tanpa tanda hipertensi atau proteinuria.'
        ],
        [
            'kode' => 'A2',
            'nama' => 'Hipertensi Gestasional',
            'level' => 'Sedang',
            'rekom' => 'Pantau tekanan darah berkala 2x seminggu, kontrol lebih sering (setiap 1-2 minggu), edukasi tanda bahaya preeklampsia, istirahat cukup, batasi stres, dan evaluasi ulang proteinuria.',
            'ket' => 'Hipertensi baru timbul setelah usia kehamilan ≥20 minggu tanpa proteinuria.'
        ],
        [
            'kode' => 'A3',
            'nama' => 'Hipertensi Kronis',
            'level' => 'Sedang',
            'rekom' => 'Kontrol tekanan darah ketat, evaluasi obat antihipertensi yang aman untuk kehamilan (misal: Metildopa, Labetalol, Nifedipin). Hindari ACE-inhibitor & ARB karena teratogenik. Waspadai komplikasi superimposed preeclampsia.',
            'ket' => 'Hipertensi terdeteksi sebelum kehamilan atau sebelum usia kehamilan 20 minggu.'
        ],
        [
            'kode' => 'A4',
            'nama' => 'Preeklampsia Ringan',
            'level' => 'Sedang-Tinggi',
            'rekom' => 'Rawat jalan intensif, cek proteinuria & pemeriksaan laboratorium berkala (darah lengkap, fungsi hati, ginjal), edukasi tanda bahaya (nyeri kepala, kabur pandangan, nyeri epigastrium), rencanakan persalinan aterm jika stabil.',
            'ket' => 'Hipertensi disertai proteinuria bermakna (≥ +1) setelah usia kehamilan 20 minggu tanpa gejala berat.'
        ],
        [
            'kode' => 'A5',
            'nama' => 'Preeklampsia Berat',
            'level' => 'Tinggi',
            'rekom' => 'Rujuk segera ke fasilitas kesehatan rujukan dengan fasilitas obstetri/PONEK lengkap. Pertimbangkan rawat inap, pemberian MgSO4 untuk profilaksis kejang, antihipertensi intravena/oral, dan evaluasi terminasi kehamilan sesuai usia gestasi.',
            'ket' => 'Preeklampsia dengan tekanan darah sangat tinggi (≥160/110 mmHg) dan/atau disertai keluhan impending eclampsia/gangguan organ.'
        ],
        [
            'kode' => 'A6',
            'nama' => 'Superimposed Preeclampsia',
            'level' => 'Tinggi',
            'rekom' => 'Rujuk ke dokter spesialis obstetri & ginekologi (Sp.OG). Pemantauan ketat kondisi maternal dan kesejahteraan janin (USG Doppler, NST). Evaluasi proteinuria dan fungsi organ sasaran secara berkala.',
            'ket' => 'Ibu dengan riwayat hipertensi kronis yang mengalami komplikasi proteinuria baru atau perburukan hipertensi setelah 20 minggu.'
        ],
        [
            'kode' => 'A7',
            'nama' => 'Eklampsia',
            'level' => 'Sangat Tinggi (gawat darurat)',
            'rekom' => 'KEGAWATDARURATAN OBSTETRI! Rujuk SEGERA ke RS PONEK dengan pendampingan nakes. Bebaskan jalan nafas, berikan oksigenasi, pasang IV-line, injeksi MgSO4 bolus dan rumatan untuk hentikan kejang, stabilisasi hemodinamik, terminasi kehamilan segera setelah ibu stabil.',
            'ket' => 'Kondisi preeklampsia disertai kejang fokal atau umum yang bukan akibat penyebab neurologis lain.'
        ]
    ];

    $stmtAlt = $pdo->prepare("INSERT INTO `alternatif` (`id_alternatif`, `kode_alternatif`, `nama_alternatif`, `level_risiko`, `rekomendasi`, `keterangan`) VALUES (?, ?, ?, ?, ?, ?)");
    foreach ($alternatifData as $idx => $a) {
        $stmtAlt->execute([$idx + 1, $a['kode'], $a['nama'], $a['level'], $a['rekom'], $a['ket']]);
    }

    // 2. Seed Kriteria (C1 - C16)
    $kriteriaData = [
        ['kode' => 'C1', 'nama' => 'Usia ibu hamil', 'bobot' => 1.00, 'sub' => '<20 thn (1.0), 20-35 thn (0.0), >35 thn (1.0)', 'ket' => 'Risiko meningkat pada usia ekstrem reproduksi (<20 atau >35 tahun)'],
        ['kode' => 'C2', 'nama' => 'Usia kehamilan', 'bobot' => 1.00, 'sub' => '<20 minggu (0.0), ≥20 minggu (1.0)', 'ket' => 'Menentukan klasifikasi hipertensi kronis (<20 mgg) vs gestasional/preeklampsia (≥20 mgg)'],
        ['kode' => 'C3', 'nama' => 'Tekanan darah sistolik', 'bobot' => 2.00, 'sub' => '<140 mmHg (0.0), 140-159 mmHg (1.0), ≥160 mmHg (2.0)', 'ket' => 'Parameter utama evaluasi keparahan hipertensi'],
        ['kode' => 'C4', 'nama' => 'Tekanan darah diastolik', 'bobot' => 2.00, 'sub' => '<90 mmHg (0.0), 90-109 mmHg (1.0), ≥110 mmHg (2.0)', 'ket' => 'Parameter utama evaluasi keparahan hipertensi'],
        ['kode' => 'C5', 'nama' => 'Proteinuria', 'bobot' => 2.00, 'sub' => 'Negatif (0.0), +1 / +2 (1.0), +3 / +4 (2.0)', 'ket' => 'Tanda khas kerusakan endotel ginjal pada preeklampsia'],
        ['kode' => 'C6', 'nama' => 'Edema (bengkak)', 'bobot' => 1.00, 'sub' => 'Tidak (0.0), Ya (1.0)', 'ket' => 'Bengkak patologis pada ekstremitas wajah dan tangan'],
        ['kode' => 'C7', 'nama' => 'Sakit kepala hebat', 'bobot' => 1.00, 'sub' => 'Tidak (0.0), Ya (1.0)', 'ket' => 'Tanda bahaya gangguan sirkulasi serebral / impending eclampsia'],
        ['kode' => 'C8', 'nama' => 'Gangguan penglihatan', 'bobot' => 1.00, 'sub' => 'Tidak (0.0), Ya (1.0)', 'ket' => 'Pandangan kabur, skotoma, diplopia akibat spasme arteri retina'],
        ['kode' => 'C9', 'nama' => 'Nyeri ulu hati (epigastrium)', 'bobot' => 1.00, 'sub' => 'Tidak (0.0), Ya (1.0)', 'ket' => 'Iskemia hepar atau peregangan kapsula Glisson (tanda bahaya)'],
        ['kode' => 'C10', 'nama' => 'Trombosit rendah (<100.000/µL)', 'bobot' => 1.00, 'sub' => 'Tidak (0.0), Ya (1.0)', 'ket' => 'Trombositopenia tanda HELLP syndrome atau koagulopati'],
        ['kode' => 'C11', 'nama' => 'Fungsi hati terganggu (SGOT/SGPT naik)', 'bobot' => 1.00, 'sub' => 'Tidak (0.0), Ya (1.0)', 'ket' => 'Peningkatan enzim transaminase hati >2x nilai normal'],
        ['kode' => 'C12', 'nama' => 'Fungsi ginjal terganggu (Kreatinin naik)', 'bobot' => 1.00, 'sub' => 'Tidak (0.0), Ya (1.0)', 'ket' => 'Kreatinin serum >1.1 mg/dL menandakan disfungsi ginjal'],
        ['kode' => 'C13', 'nama' => 'Riwayat hipertensi sebelum hamil', 'bobot' => 1.00, 'sub' => 'Tidak (0.0), Ya (1.0)', 'ket' => 'Faktor penentu diagnosis Hipertensi Kronis & Superimposed'],
        ['kode' => 'C14', 'nama' => 'Riwayat preeklampsia sebelumnya', 'bobot' => 1.00, 'sub' => 'Tidak (0.0), Ya (1.0)', 'ket' => 'Faktor risiko tinggi rekurensi preeklampsia hingga 7-8 kali lipat'],
        ['kode' => 'C15', 'nama' => 'Kejang', 'bobot' => 2.00, 'sub' => 'Tidak (0.0), Ya (1.0)', 'ket' => 'Tanda patognomonik Eklampsia (kegawatdaruratan mutlak)'],
        ['kode' => 'C16', 'nama' => 'Kehamilan kembar', 'bobot' => 1.00, 'sub' => 'Tidak (0.0), Ya (1.0)', 'ket' => 'Massa plasenta lebih besar meningkatkan beban vaskular dan risiko']
    ];

    $stmtKrit = $pdo->prepare("INSERT INTO `kriteria` (`id_kriteria`, `kode_kriteria`, `nama_kriteria`, `bobot`, `sub_nilai`, `keterangan`) VALUES (?, ?, ?, ?, ?, ?)");
    foreach ($kriteriaData as $idx => $k) {
        $stmtKrit->execute([$idx + 1, $k['kode'], $k['nama'], $k['bobot'], $k['sub'], $k['ket']]);
    }

    // 3. Seed Realistic Patients & Examinations from Simulation Data
    $samplePatients = [
        [
            'no_rm' => 'RM-2026-001', 'nama' => 'Ny. Siti Rahmawati', 'umur' => 26, 'usia_hamil' => 25, 'alamat' => 'Jl. Mawar No. 12, Kel. Sukajadi', 'no_telp' => '08123456701', 'gravida' => 'G1P0A0',
            'sistolik' => 120, 'diastolik' => 75, 'prot' => 'Negatif', 'edema' => 'Tidak', 'gejala' => 'Pemeriksaan rutin trimester 2, keluhan nihil',
            'c7' => 'Tidak', 'c8' => 'Tidak', 'c9' => 'Tidak', 'c10' => 'Tidak', 'c11' => 'Tidak', 'c12' => 'Tidak', 'c13' => 'Tidak', 'c14' => 'Tidak', 'c15' => 'Tidak', 'c16' => 'Tidak',
            'alt_id' => 1, 'keputusan' => 'Normal / tidak hipertensi', 'level' => 'Rendah', 'nilai' => 0.0, 'rule' => 'Normotensif'
        ],
        [
            'no_rm' => 'RM-2026-002', 'nama' => 'Ny. Dewi Sartika', 'umur' => 28, 'usia_hamil' => 28, 'alamat' => 'Jl. Melati No. 45, Kel. Cibabat', 'no_telp' => '08123456702', 'gravida' => 'G2P1A0',
            'sistolik' => 145, 'diastolik' => 95, 'prot' => 'Negatif', 'edema' => 'Tidak', 'gejala' => 'Tekanan darah meningkat pertama kali pada kontrol 28 minggu',
            'c7' => 'Tidak', 'c8' => 'Tidak', 'c9' => 'Tidak', 'c10' => 'Tidak', 'c11' => 'Tidak', 'c12' => 'Tidak', 'c13' => 'Tidak', 'c14' => 'Tidak', 'c15' => 'Tidak', 'c16' => 'Tidak',
            'alt_id' => 2, 'keputusan' => 'Hipertensi Gestasional', 'level' => 'Sedang', 'nilai' => 3.0, 'rule' => 'R1'
        ],
        [
            'no_rm' => 'RM-2026-003', 'nama' => 'Ny. Maria Ulfah', 'umur' => 36, 'usia_hamil' => 14, 'alamat' => 'Jl. Kenanga No. 8, Kel. Baros', 'no_telp' => '08123456703', 'gravida' => 'G3P2A0',
            'sistolik' => 150, 'diastolik' => 95, 'prot' => 'Negatif', 'edema' => 'Tidak', 'gejala' => 'Hipertensi sudah ada sejak sebelum hamil, kontrol trimester 1',
            'c7' => 'Tidak', 'c8' => 'Tidak', 'c9' => 'Tidak', 'c10' => 'Tidak', 'c11' => 'Tidak', 'c12' => 'Tidak', 'c13' => 'Ya', 'c14' => 'Tidak', 'c15' => 'Tidak', 'c16' => 'Tidak',
            'alt_id' => 3, 'keputusan' => 'Hipertensi Kronis', 'level' => 'Sedang', 'nilai' => 4.0, 'rule' => 'R2'
        ],
        [
            'no_rm' => 'RM-2026-004', 'nama' => 'Ny. Ratna Kusuma', 'umur' => 31, 'usia_hamil' => 30, 'alamat' => 'Jl. Anggrek No. 19, Kel. Padasuka', 'no_telp' => '08123456704', 'gravida' => 'G1P0A0',
            'sistolik' => 148, 'diastolik' => 92, 'prot' => '+1', 'edema' => 'Ya', 'gejala' => 'Bengkak pada kedua tungkai kaki, proteinuria positif 1',
            'c7' => 'Tidak', 'c8' => 'Tidak', 'c9' => 'Tidak', 'c10' => 'Tidak', 'c11' => 'Tidak', 'c12' => 'Tidak', 'c13' => 'Tidak', 'c14' => 'Tidak', 'c15' => 'Tidak', 'c16' => 'Tidak',
            'alt_id' => 4, 'keputusan' => 'Preeklampsia Ringan', 'level' => 'Sedang-Tinggi', 'nilai' => 5.0, 'rule' => 'R3'
        ],
        [
            'no_rm' => 'RM-2026-005', 'nama' => 'Ny. Indah Permata', 'umur' => 34, 'usia_hamil' => 34, 'alamat' => 'Jl. Cendana No. 27, Kel. Pasirkaliki', 'no_telp' => '08123456705', 'gravida' => 'G2P0A1',
            'sistolik' => 165, 'diastolik' => 115, 'prot' => '+3', 'edema' => 'Ya', 'gejala' => 'Sakit kepala frontal berdenyut, bengkak wajah dan tangan',
            'c7' => 'Ya', 'c8' => 'Tidak', 'c9' => 'Tidak', 'c10' => 'Tidak', 'c11' => 'Tidak', 'c12' => 'Tidak', 'c13' => 'Tidak', 'c14' => 'Tidak', 'c15' => 'Tidak', 'c16' => 'Tidak',
            'alt_id' => 5, 'keputusan' => 'Preeklampsia Berat', 'level' => 'Tinggi', 'nilai' => 8.0, 'rule' => 'R4'
        ],
        [
            'no_rm' => 'RM-2026-006', 'nama' => 'Ny. Anisa Triastuti', 'umur' => 37, 'usia_hamil' => 26, 'alamat' => 'Jl. Diponegoro No. 88, Kel. Cibeunying', 'no_telp' => '08123456706', 'gravida' => 'G3P1A1',
            'sistolik' => 155, 'diastolik' => 98, 'prot' => '+2', 'edema' => 'Ya', 'gejala' => 'Riwayat hipertensi esensial, baru ditemukan proteinuria +2 di UK 26 mgg',
            'c7' => 'Tidak', 'c8' => 'Tidak', 'c9' => 'Tidak', 'c10' => 'Tidak', 'c11' => 'Tidak', 'c12' => 'Tidak', 'c13' => 'Ya', 'c14' => 'Ya', 'c15' => 'Tidak', 'c16' => 'Tidak',
            'alt_id' => 6, 'keputusan' => 'Superimposed Preeclampsia', 'level' => 'Tinggi', 'nilai' => 7.0, 'rule' => 'R6'
        ],
        [
            'no_rm' => 'RM-2026-007', 'nama' => 'Ny. Fitri Handayani', 'umur' => 38, 'usia_hamil' => 37, 'alamat' => 'Jl. Gajah Mada No. 5, Kel. Pasteur', 'no_telp' => '08123456707', 'gravida' => 'G1P0A0',
            'sistolik' => 175, 'diastolik' => 120, 'prot' => '+3', 'edema' => 'Ya', 'gejala' => 'Kejang tonik klonik 1 kali di rumah, kesadaran somnolen pasca kejang',
            'c7' => 'Ya', 'c8' => 'Ya', 'c9' => 'Ya', 'c10' => 'Ya', 'c11' => 'Tidak', 'c12' => 'Tidak', 'c13' => 'Tidak', 'c14' => 'Tidak', 'c15' => 'Ya', 'c16' => 'Tidak',
            'alt_id' => 7, 'keputusan' => 'Eklampsia', 'level' => 'Sangat Tinggi (gawat darurat)', 'nilai' => 12.0, 'rule' => 'R5'
        ],
        [
            'no_rm' => 'RM-2026-008', 'nama' => 'Ny. Tri Wahyuni', 'umur' => 22, 'usia_hamil' => 32, 'alamat' => 'Jl. Surya Sumantri No. 101, Kel. Sukawarna', 'no_telp' => '08123456708', 'gravida' => 'G1P0A0',
            'sistolik' => 152, 'diastolik' => 98, 'prot' => '+2', 'edema' => 'Ya', 'gejala' => 'Edema pretibial, pusing ringan, proteinuria +2',
            'c7' => 'Tidak', 'c8' => 'Tidak', 'c9' => 'Tidak', 'c10' => 'Tidak', 'c11' => 'Tidak', 'c12' => 'Tidak', 'c13' => 'Tidak', 'c14' => 'Tidak', 'c15' => 'Tidak', 'c16' => 'Tidak',
            'alt_id' => 4, 'keputusan' => 'Preeklampsia Ringan', 'level' => 'Sedang-Tinggi', 'nilai' => 5.0, 'rule' => 'R3'
        ]
    ];

    $stmtPasien = $pdo->prepare("INSERT INTO `pasien` (`id_pasien`, `no_rm`, `nama_pasien`, `umur`, `usia_kehamilan`, `alamat`, `no_telp`, `gravida`, `tanggal_daftar`) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
    $stmtPem = $pdo->prepare("INSERT INTO `pemeriksaan` (
        `id_pemeriksaan`, `id_pasien`, `tekanan_sistolik`, `tekanan_diastolik`, `proteinuria`, `edema`, `gejala`,
        `sakit_kepala`, `gangguan_penglihatan`, `nyeri_ulu_hati`, `trombosit_rendah`, `fungsi_hati`, `fungsi_ginjal`,
        `riwayat_hipertensi`, `riwayat_preeklampsia`, `kejang`, `kehamilan_kembar`, `catatan_medis`, `tanggal_pemeriksaan`
    ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
    $stmtHK = $pdo->prepare("INSERT INTO `hasil_keputusan` (
        `id_hasil`, `id_pasien`, `id_alternatif`, `id_pemeriksaan`, `nilai`, `keputusan`, `level_risiko`, `rule_terpicu`, `rekomendasi`, `trace_log`, `tanggal`
    ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");

    $today = date('Y-m-d');
    foreach ($samplePatients as $idx => $p) {
        $pId = $idx + 1;
        $dateExam = date('Y-m-d', strtotime("-" . (8 - $idx) . " days"));

        $stmtPasien->execute([
            $pId, $p['no_rm'], $p['nama'], $p['umur'], $p['usia_hamil'], $p['alamat'], $p['no_telp'], $p['gravida'], $dateExam . ' 08:30:00'
        ]);

        $stmtPem->execute([
            $pId, $pId, $p['sistolik'], $p['diastolik'], $p['prot'], $p['edema'], $p['gejala'],
            $p['c7'], $p['c8'], $p['c9'], $p['c10'], $p['c11'], $p['c12'],
            $p['c13'], $p['c14'], $p['c15'], $p['c16'],
            'Pemeriksaan antenatal rutin & skrining faktor risiko CDSS', $dateExam
        ]);

        $rekomText = $alternatifData[$p['alt_id'] - 1]['rekom'];
        $traceLog = "Forward Chaining fired rule: " . $p['rule'] . ". Tekanan: " . $p['sistolik'] . "/" . $p['diastolik'] . " mmHg, Proteinuria: " . $p['prot'] . ", UK: " . $p['usia_hamil'] . " mgg.";

        $stmtHK->execute([
            $pId, $pId, $p['alt_id'], $pId, $p['nilai'], $p['keputusan'], $p['level'], $p['rule'], $rekomText, $traceLog, $dateExam
        ]);
    }

    return ["status" => "success", "message" => "Database berhasil diinisialisasi dengan data kriteria, alternatif, dan pasien sampel."];
}

// If invoked from CLI directly
if (php_sapi_name() === 'cli') {
    $res = runSeed(true);
    echo "Seed result: " . json_encode($res) . "\n";
}
