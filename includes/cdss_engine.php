<?php
/**
 * CDSS Rule-Based Forward Chaining Inference Engine
 * Hipertensi pada Ibu Hamil Berisiko Tinggi
 * Sesuai Pedoman Klinis POGI, WHO, dan Basis Pengetahuan Excel
 */

class CDSSEngine {

    /**
     * Jalankan inferensi Forward Chaining berdasarkan fakta klinis
     */
    public static function evaluasi($input) {
        $usiaIbu         = isset($input['umur']) ? (int)$input['umur'] : 25;
        $usiaKehamilan   = isset($input['usia_kehamilan']) ? (int)$input['usia_kehamilan'] : 20;
        $sistolik        = isset($input['tekanan_sistolik']) ? (int)$input['tekanan_sistolik'] : 120;
        $diastolik       = isset($input['tekanan_diastolik']) ? (int)$input['tekanan_diastolik'] : 80;
        $proteinuria     = isset($input['proteinuria']) ? trim($input['proteinuria']) : 'Negatif';
        $edema           = isset($input['edema']) ? $input['edema'] : 'Tidak';
        $sakitKepala     = isset($input['sakit_kepala']) ? $input['sakit_kepala'] : 'Tidak';
        $penglihatan     = isset($input['gangguan_penglihatan']) ? $input['gangguan_penglihatan'] : 'Tidak';
        $nyeriUluHati    = isset($input['nyeri_ulu_hati']) ? $input['nyeri_ulu_hati'] : 'Tidak';
        $trombositRendah = isset($input['trombosit_rendah']) ? $input['trombosit_rendah'] : 'Tidak';
        $fungsiHati      = isset($input['fungsi_hati']) ? $input['fungsi_hati'] : 'Tidak';
        $fungsiGinjal    = isset($input['fungsi_ginjal']) ? $input['fungsi_ginjal'] : 'Tidak';
        $riwayatHT       = isset($input['riwayat_hipertensi']) ? $input['riwayat_hipertensi'] : 'Tidak';
        $riwayatPE       = isset($input['riwayat_preeklampsia']) ? $input['riwayat_preeklampsia'] : 'Tidak';
        $kejang          = isset($input['kejang']) ? $input['kejang'] : 'Tidak';
        $kembar          = isset($input['kehamilan_kembar']) ? $input['kehamilan_kembar'] : 'Tidak';

        // 1. KATEGORISASI FAKTA KLINIS (WORKING MEMORY)
        $facts = [];

        // C1: Usia Ibu
        $c1Val = ($usiaIbu < 20 || $usiaIbu > 35) ? 1.0 : 0.0;
        $facts['C1'] = [
            'nama' => 'Usia ibu hamil',
            'nilai' => $usiaIbu . ' tahun',
            'kategori' => ($usiaIbu < 20 ? '<20 tahun (Usia Muda Ekstrem)' : ($usiaIbu > 35 ? '>35 tahun (Usia Lanjut)' : '20-35 tahun (Usia Reproduksi Sehat)')),
            'bobot' => $c1Val,
            'is_risiko' => ($c1Val > 0)
        ];

        // C2: Usia Kehamilan
        $c2Val = ($usiaKehamilan >= 20) ? 1.0 : 0.0;
        $facts['C2'] = [
            'nama' => 'Usia kehamilan',
            'nilai' => $usiaKehamilan . ' minggu',
            'kategori' => ($usiaKehamilan >= 20 ? '≥20 minggu' : '<20 minggu'),
            'bobot' => $c2Val,
            'is_risiko' => ($c2Val > 0)
        ];

        // C3: Sistolik
        $c3Val = ($sistolik >= 160) ? 2.0 : (($sistolik >= 140) ? 1.0 : 0.0);
        $facts['C3'] = [
            'nama' => 'Tekanan darah sistolik',
            'nilai' => $sistolik . ' mmHg',
            'kategori' => ($sistolik >= 160 ? '≥160 mmHg (Hipertensi Berat)' : ($sistolik >= 140 ? '140-159 mmHg (Hipertensi Ringan-Sedang)' : '<140 mmHg (Normal)')),
            'bobot' => $c3Val,
            'is_risiko' => ($c3Val > 0)
        ];

        // C4: Diastolik
        $c4Val = ($diastolik >= 110) ? 2.0 : (($diastolik >= 90) ? 1.0 : 0.0);
        $facts['C4'] = [
            'nama' => 'Tekanan darah diastolik',
            'nilai' => $diastolik . ' mmHg',
            'kategori' => ($diastolik >= 110 ? '≥110 mmHg (Hipertensi Berat)' : ($diastolik >= 90 ? '90-109 mmHg (Hipertensi Ringan-Sedang)' : '<90 mmHg (Normal)')),
            'bobot' => $c4Val,
            'is_risiko' => ($c4Val > 0)
        ];

        // C5: Proteinuria
        $hasProteinuria = !in_array($proteinuria, ['Negatif', '-', '0', 'negatif', '']);
        $isProtBerat    = in_array($proteinuria, ['+3', '+4', '3', '4']);
        $c5Val = $isProtBerat ? 2.0 : ($hasProteinuria ? 1.0 : 0.0);
        $facts['C5'] = [
            'nama' => 'Proteinuria',
            'nilai' => $proteinuria,
            'kategori' => ($isProtBerat ? '+3 / +4 (Proteinuria Berat)' : ($hasProteinuria ? '+1 / +2 (Proteinuria Bermakna)' : 'Negatif')),
            'bobot' => $c5Val,
            'is_risiko' => ($c5Val > 0)
        ];

        // C6: Edema
        $c6Val = ($edema === 'Ya') ? 1.0 : 0.0;
        $facts['C6'] = ['nama' => 'Edema (bengkak patologis)', 'nilai' => $edema, 'bobot' => $c6Val, 'is_risiko' => ($c6Val > 0)];

        // C7: Sakit Kepala
        $c7Val = ($sakitKepala === 'Ya') ? 1.0 : 0.0;
        $facts['C7'] = ['nama' => 'Sakit kepala hebat (frontal/oksipital)', 'nilai' => $sakitKepala, 'bobot' => $c7Val, 'is_risiko' => ($c7Val > 0)];

        // C8: Gangguan Penglihatan
        $c8Val = ($penglihatan === 'Ya') ? 1.0 : 0.0;
        $facts['C8'] = ['nama' => 'Gangguan penglihatan (kabur/skotoma)', 'nilai' => $penglihatan, 'bobot' => $c8Val, 'is_risiko' => ($c8Val > 0)];

        // C9: Nyeri Epigastrium
        $c9Val = ($nyeriUluHati === 'Ya') ? 1.0 : 0.0;
        $facts['C9'] = ['nama' => 'Nyeri ulu hati (epigastrium)', 'nilai' => $nyeriUluHati, 'bobot' => $c9Val, 'is_risiko' => ($c9Val > 0)];

        // C10: Trombosit Rendah
        $c10Val = ($trombositRendah === 'Ya') ? 1.0 : 0.0;
        $facts['C10'] = ['nama' => 'Trombosit rendah (<100.000/µL)', 'nilai' => $trombositRendah, 'bobot' => $c10Val, 'is_risiko' => ($c10Val > 0)];

        // C11: Fungsi Hati
        $c11Val = ($fungsiHati === 'Ya') ? 1.0 : 0.0;
        $facts['C11'] = ['nama' => 'Fungsi hati terganggu (SGOT/SGPT naik)', 'nilai' => $fungsiHati, 'bobot' => $c11Val, 'is_risiko' => ($c11Val > 0)];

        // C12: Fungsi Ginjal
        $c12Val = ($fungsiGinjal === 'Ya') ? 1.0 : 0.0;
        $facts['C12'] = ['nama' => 'Fungsi ginjal terganggu (Kreatinin naik)', 'nilai' => $fungsiGinjal, 'bobot' => $c12Val, 'is_risiko' => ($c12Val > 0)];

        // C13: Riwayat Hipertensi
        $c13Val = ($riwayatHT === 'Ya') ? 1.0 : 0.0;
        $facts['C13'] = ['nama' => 'Riwayat hipertensi sebelum hamil', 'nilai' => $riwayatHT, 'bobot' => $c13Val, 'is_risiko' => ($c13Val > 0)];

        // C14: Riwayat Preeklampsia
        $c14Val = ($riwayatPE === 'Ya') ? 1.0 : 0.0;
        $facts['C14'] = ['nama' => 'Riwayat preeklampsia sebelumnya', 'nilai' => $riwayatPE, 'bobot' => $c14Val, 'is_risiko' => ($c14Val > 0)];

        // C15: Kejang
        $c15Val = ($kejang === 'Ya') ? 2.0 : 0.0;
        $facts['C15'] = ['nama' => 'Kejang (eklampsia)', 'nilai' => $kejang, 'bobot' => $c15Val, 'is_risiko' => ($c15Val > 0)];

        // C16: Kehamilan Kembar
        $c16Val = ($kembar === 'Ya') ? 1.0 : 0.0;
        $facts['C16'] = ['nama' => 'Kehamilan kembar (gemelli)', 'nilai' => $kembar, 'bobot' => $c16Val, 'is_risiko' => ($c16Val > 0)];

        // Total skor bobot risiko
        $totalSkorRisiko = 0;
        foreach ($facts as $f) {
            $totalSkorRisiko += $f['bobot'];
        }

        // 2. INTERMEDIATE PREDICATES (EVALUASI LOGIKA KLINIS)
        $isHipertensi       = ($sistolik >= 140 || $diastolik >= 90);
        $isHipertensiBerat  = ($sistolik >= 160 || $diastolik >= 110);
        $ge20Minggu         = ($usiaKehamilan >= 20);
        $lt20Minggu         = ($usiaKehamilan < 20);
        $hasSevereSymptoms  = ($sakitKepala === 'Ya' || $penglihatan === 'Ya' || $nyeriUluHati === 'Ya' || $trombositRendah === 'Ya' || $fungsiHati === 'Ya' || $fungsiGinjal === 'Ya');
        $hasKejang          = ($kejang === 'Ya');
        $hasRiwayatHT       = ($riwayatHT === 'Ya');

        // 3. FORWARD CHAINING INFERENCE TRACE
        $trace = [];
        $trace[] = [
            'step' => 1,
            'title' => 'Ekstraksi Fakta Pasien',
            'detail' => sprintf("Tekanan: %d/%d mmHg (%s), Usia Kehamilan: %d minggu (%s), Proteinuria: %s, Riwayat HT: %s, Kejang: %s, Gejala Berat: %s.",
                $sistolik, $diastolik, ($isHipertensiBerat ? 'Hipertensi Berat' : ($isHipertensi ? 'Hipertensi' : 'Normotensif')),
                $usiaKehamilan, ($ge20Minggu ? '≥20 mgg' : '<20 mgg'),
                $proteinuria, $riwayatHT, $kejang, ($hasSevereSymptoms ? 'Ada' : 'Tidak')
            )
        ];

        $diagnosisKode = 'A1';
        $diagnosisNama = 'Normal / tidak hipertensi';
        $levelRisiko   = 'Rendah';
        $ruleTerpicu   = 'Normotensif';
        $alasanRule    = '';

        // EVALUASI KAIDAH PRODUKSI (FORWARD CHAINING) DENGAN PRIORITAS GAWAT DARURAT

        // CEK RULE R5 (Eklampsia)
        $r5Match = false;
        if ($hasKejang) {
            // Pasien hamil dengan kejang adalah Eklampsia (kegawatdaruratan)
            $r5Match = true;
            $diagnosisKode = 'A7';
            $diagnosisNama = 'Eklampsia';
            $levelRisiko   = 'Sangat Tinggi (gawat darurat)';
            $ruleTerpicu   = 'R5 (Kaidah Eklampsia)';
            $alasanRule    = 'Pasien mengalami kejang (C15=Ya) pada kondisi kehamilan dengan tekanan darah tinggi atau proteinuria, merupakan kegawatdaruratan mutlak obstetri.';
            $trace[] = [
                'step' => 2,
                'title' => 'Evaluasi Rule R5 (Eklampsia)',
                'status' => 'FIRE',
                'rule' => 'IF (Preeklampsia/Hipertensi) AND C15=Ya THEN A7 - Eklampsia',
                'detail' => 'Aturan R5 TERPENUHI karena ditemukan kejang pada masa gestasi lanjut.'
            ];
        } else {
            $trace[] = [
                'step' => 2,
                'title' => 'Evaluasi Rule R5 (Eklampsia)',
                'status' => 'PASS',
                'rule' => 'IF C15=Ya THEN A7 - Eklampsia',
                'detail' => 'Kondisi kejang nihil (C15=Tidak), lanjut ke aturan berikutnya.'
            ];

            // CEK RULE R4 (Preeklampsia Berat)
            // Syarat: C2>=20 mgg AND Hipertensi AND Proteinuria POSITIF AND (Tekanan Berat OR Gejala Berat OR Prot >= +3)
            $r4Match = ($ge20Minggu && $isHipertensi && $hasProteinuria && ($isHipertensiBerat || $hasSevereSymptoms || $isProtBerat));
            if ($r4Match) {
                $diagnosisKode = 'A5';
                $diagnosisNama = 'Preeklampsia Berat';
                $levelRisiko   = 'Tinggi';
                $ruleTerpicu   = 'R4 (Kaidah Preeklampsia Berat)';
                $alasanRule    = sprintf(
                    'Usia kehamilan ≥20 minggu (%d mgg), tensi %d/%d mmHg dengan proteinuria %s, disertai %s.',
                    $usiaKehamilan, $sistolik, $diastolik, $proteinuria,
                    ($isHipertensiBerat ? 'tekanan darah berat (≥160/110)' : 'gejala komplikasi organ sasaran / impending eclampsia')
                );
                $trace[] = [
                    'step' => 3,
                    'title' => 'Evaluasi Rule R4 (Preeklampsia Berat)',
                    'status' => 'FIRE',
                    'rule' => 'IF C2≥20 AND C3≥140/C4≥90 AND C5≥+1 AND (C3≥160/C4≥110 OR Gejala_Berat) THEN A5',
                    'detail' => 'Aturan R4 TERPENUHI: Terjadi preeklampsia dengan kriteria berat (tekanan tinggi atau keluhan organ).'
                ];
            } else {
                $trace[] = [
                    'step' => 3,
                    'title' => 'Evaluasi Rule R4 (Preeklampsia Berat)',
                    'status' => 'PASS',
                    'rule' => 'IF Preeklampsia Ringan AND (Tensi Berat OR Gejala Berat) THEN A5',
                    'detail' => 'Tidak memenuhi kriteria berat, lanjut ke evaluasi Superimposed & Preeklampsia Ringan.'
                ];

                // CEK RULE R6 (Superimposed Preeclampsia)
                // Syarat: Riwayat HT sebelumnya (C13=Ya) AND Usia Kehamilan >= 20 mgg AND Proteinuria Positif
                $r6Match = ($hasRiwayatHT && $ge20Minggu && $hasProteinuria);
                if ($r6Match) {
                    $diagnosisKode = 'A6';
                    $diagnosisNama = 'Superimposed Preeclampsia';
                    $levelRisiko   = 'Tinggi';
                    $ruleTerpicu   = 'R6 (Kaidah Superimposed Preeclampsia)';
                    $alasanRule    = 'Pasien dengan riwayat hipertensi sebelum hamil mengalami komplikasi proteinuria baru pada usia kehamilan ≥20 minggu.';
                    $trace[] = [
                        'step' => 4,
                        'title' => 'Evaluasi Rule R6 (Superimposed Preeclampsia)',
                        'status' => 'FIRE',
                        'rule' => 'IF C13=Ya AND C2≥20 AND C5≥+1 THEN A6 - Superimposed Preeclampsia',
                        'detail' => 'Aturan R6 TERPENUHI: Superimposed preeclampsia pada ibu dengan hipertensi kronis.'
                    ];
                } else {
                    $trace[] = [
                        'step' => 4,
                        'title' => 'Evaluasi Rule R6 (Superimposed Preeclampsia)',
                        'status' => 'PASS',
                        'rule' => 'IF C13=Ya AND C2≥20 AND C5≥+1 THEN A6',
                        'detail' => 'Bukan superimposed (riwayat HT atau proteinuria tidak berpasangan).'
                    ];

                    // CEK RULE R3 (Preeklampsia Ringan)
                    // Syarat: Usia Kehamilan >= 20 mgg AND Hipertensi AND Proteinuria Positif
                    $r3Match = ($ge20Minggu && $isHipertensi && $hasProteinuria);
                    if ($r3Match) {
                        $diagnosisKode = 'A4';
                        $diagnosisNama = 'Preeklampsia Ringan';
                        $levelRisiko   = 'Sedang-Tinggi';
                        $ruleTerpicu   = 'R3 (Kaidah Preeklampsia Ringan)';
                        $alasanRule    = sprintf('Hipertensi (%d/%d mmHg) baru timbul setelah 20 minggu gestasi disertai proteinuria positif (%s) tanpa kriteria keparahan berat.', $sistolik, $diastolik, $proteinuria);
                        $trace[] = [
                            'step' => 5,
                            'title' => 'Evaluasi Rule R3 (Preeklampsia Ringan)',
                            'status' => 'FIRE',
                            'rule' => 'IF C2≥20 AND C3≥140/C4≥90 AND C5≥+1 THEN A4 - Preeklampsia Ringan',
                            'detail' => 'Aturan R3 TERPENUHI: Gejala klinis memenuhi Preeklampsia Ringan.'
                        ];
                    } else {
                        $trace[] = [
                            'step' => 5,
                            'title' => 'Evaluasi Rule R3 (Preeklampsia Ringan)',
                            'status' => 'PASS',
                            'rule' => 'IF C2≥20 AND C3≥140/C4≥90 AND C5≥+1 THEN A4',
                            'detail' => 'Proteinuria negatif atau usia gestasi <20 minggu.'
                        ];

                        // CEK RULE R2 (Hipertensi Kronis)
                        // Syarat: Hipertensi terdeteksi sebelum usia kehamilan 20 minggu (C2 < 20 mgg) ATAU riwayat hipertensi tanpa proteinuria
                        $r2Match = (($lt20Minggu && $isHipertensi) || ($hasRiwayatHT && !$hasProteinuria));
                        if ($r2Match) {
                            $diagnosisKode = 'A3';
                            $diagnosisNama = 'Hipertensi Kronis';
                            $levelRisiko   = 'Sedang';
                            $ruleTerpicu   = 'R2 (Kaidah Hipertensi Kronis)';
                            $alasanRule    = sprintf('Hipertensi (%d/%d mmHg) telah ada sebelum kehamilan atau terdeteksi sebelum usia gestasi 20 minggu (%d minggu).', $sistolik, $diastolik, $usiaKehamilan);
                            $trace[] = [
                                'step' => 6,
                                'title' => 'Evaluasi Rule R2 (Hipertensi Kronis)',
                                'status' => 'FIRE',
                                'rule' => 'IF (C2<20 AND C3≥140/C4≥90) OR C13=Ya THEN A3 - Hipertensi Kronis',
                                'detail' => 'Aturan R2 TERPENUHI: Hipertensi telah berlangsung sejak sebelum atau trimester awal kehamilan.'
                            ];
                        } else {
                            $trace[] = [
                                'step' => 6,
                                'title' => 'Evaluasi Rule R2 (Hipertensi Kronis)',
                                'status' => 'PASS',
                                'rule' => 'IF C2<20 AND C3≥140/C4≥90 THEN A3',
                                'detail' => 'Bukan hipertensi kronis.'
                            ];

                            // CEK RULE R1 (Hipertensi Gestasional)
                            // Syarat: Usia Kehamilan >= 20 mgg AND Hipertensi AND Proteinuria Negatif AND Riwayat HT = Tidak
                            $r1Match = ($ge20Minggu && $isHipertensi && !$hasProteinuria && !$hasRiwayatHT);
                            if ($r1Match) {
                                $diagnosisKode = 'A2';
                                $diagnosisNama = 'Hipertensi Gestasional';
                                $levelRisiko   = 'Sedang';
                                $ruleTerpicu   = 'R1 (Kaidah Hipertensi Gestasional)';
                                $alasanRule    = sprintf('Hipertensi (%d/%d mmHg) baru pertama kali timbul setelah usia kehamilan 20 minggu (%d mgg) tanpa adanya proteinuria.', $sistolik, $diastolik, $usiaKehamilan);
                                $trace[] = [
                                    'step' => 7,
                                    'title' => 'Evaluasi Rule R1 (Hipertensi Gestasional)',
                                    'status' => 'FIRE',
                                    'rule' => 'IF C2≥20 AND C3≥140/C4≥90 AND C5=Negatif AND C13=Tidak THEN A2',
                                    'detail' => 'Aturan R1 TERPENUHI: Hipertensi gestasional murni.'
                                ];
                            } else {
                                // Default: Normal / Tidak Hipertensi
                                $diagnosisKode = 'A1';
                                $diagnosisNama = 'Normal / tidak hipertensi';
                                $levelRisiko   = 'Rendah';
                                $ruleTerpicu   = 'Normotensif (Tanpa Hipertensi)';
                                $alasanRule    = sprintf('Tekanan darah dalam batas normal (%d/%d mmHg), proteinuria negatif, dan tidak ada tanda klinis preeklampsia.', $sistolik, $diastolik);
                                $trace[] = [
                                    'step' => 7,
                                    'title' => 'Evaluasi Akhir (Status Normotensif)',
                                    'status' => 'FIRE',
                                    'rule' => 'IF C3<140 AND C4<90 AND C5=Negatif THEN A1 - Normal',
                                    'detail' => 'Parameter hemodinamik dan laboratoris pasien dalam rentang fisiologis normal.'
                                ];
                            }
                        }
                    }
                }
            }
        }

        // 4. PROTOKOL PENANGANAN KLINIS LENGKAP & FARMAKOTERAPI
        $rekomendasi = self::getRekomendasiKlinis($diagnosisKode);

        // 5. IDENTIFIKASI TANDA BAHAYA & KONTROL RISIKO
        $tandaBahaya = [];
        if ($sistolik >= 160 || $diastolik >= 110) $tandaBahaya[] = 'Krisis Tekanan Darah Berat (≥160/110 mmHg) berisiko stroke maternal & solusio plasenta.';
        if ($sakitKepala === 'Ya') $tandaBahaya[] = 'Sakit kepala berdenyut hebat / refrakter analgesik (tanda edema serebral).';
        if ($penglihatan === 'Ya') $tandaBahaya[] = 'Gangguan visual / skotoma (tanda iskemia arteriol retina).';
        if ($nyeriUluHati === 'Ya') $tandaBahaya[] = 'Nyeri ulu hati persisten (tanda hematoma subkapsular hepar / sindrom HELLP).';
        if ($hasKejang) $tandaBahaya[] = 'KEJANG OBSTETRI: Bahaya aspirasi, hipoksia janin, dan henti nafas!';
        if ($trombositRendah === 'Ya') $tandaBahaya[] = 'Trombositopenia (<100.000/µL) berisiko perdarahan mayor.';

        // Kembalikan hasil komprehensif
        return [
            'id_alternatif'    => self::getIdAlternatif($diagnosisKode),
            'kode_alternatif'  => $diagnosisKode,
            'nama_alternatif'  => $diagnosisNama,
            'level_risiko'     => $levelRisiko,
            'skor_risiko'      => $totalSkorRisiko,
            'rule_terpicu'     => $ruleTerpicu,
            'alasan_rule'      => $alasanRule,
            'facts'            => $facts,
            'trace'            => $trace,
            'rekomendasi'      => $rekomendasi,
            'tanda_bahaya'     => $tandaBahaya,
            'is_emergency'     => in_array($diagnosisKode, ['A5', 'A7']),
            'waktu_evaluasi'   => date('Y-m-d H:i:s')
        ];
    }

    private static function getIdAlternatif($kode) {
        $map = ['A1' => 1, 'A2' => 2, 'A3' => 3, 'A4' => 4, 'A5' => 5, 'A6' => 6, 'A7' => 7];
        return isset($map[$kode]) ? $map[$kode] : 1;
    }

    private static function getRekomendasiKlinis($kode) {
        switch ($kode) {
            case 'A7': // Eklampsia
                return [
                    'tindakan_segera' => 'SEGERA RUJUK KE RS PONEK DENGAN PENDAMPINGAN TIM MEDIS TERLATIH. Kegawatdaruratan mutlak obstetric.',
                    'protokol_jalan_nafas' => 'Bebaskan jalan nafas, posisi miring kiri (left lateral position), pasang spatel lidah / OPA jika kejang berlangsung, bersihkan lendir/sekret, berikan O2 kanul nasal 4-6 L/menit atau masker 10 L/menit.',
                    'medikamentosa' => [
                        'Anti-konvulsan Utama (MgSO4):' => 'Dosis Awal: 4 gram MgSO4 40% (10 mL) dilarutkan dalam 10 mL aquabidest IV bolus perlahan selama 15-20 menit. Dosis Rumatan: Infus 6 gram MgSO4 dalam 500 mL RL drip 28 tetes/menit (1 gram/jam) selama 24 jam pasca salin atau pasca kejang terakhir.',
                        'Syarat Pemberian MgSO4:' => 'Refleks patella (+), frekuensi nafas ≥16 x/menit, produksi urin minimal 30 mL/jam dalam 4 jam terakhir. Selalu sediakan antidotum Kalsium Glukonat 10% (1 gram IV dalam 10 menit jika terjadi depresi nafas).',
                        'Antihipertensi Intravena:' => 'Nikardipin IV drip atau Labetalol IV jika tensi ≥160/110 mmHg. Target: Sistolik 140-150 mmHg, Diastolik 90-100 mmHg.'
                    ],
                    'kontraindikasi' => 'DILARANG memberikan Diazepam rutin jika MgSO4 tersedia. HINDARI ACE-Inhibitor, ARB, dan Diuretik (kecuali ada edema paru akut).',
                    'terminasi_kehamilan' => 'Terminasi kehamilan harus dilakukan segera setelah kondisi ibu stabil (biasanya dalam 4-12 jam pasca kejang teratasi) terlepas dari usia kehamilan.',
                    'fasilitas_rujukan' => 'Rumah Sakit Rujukan Kelas B/A dengan Fasilitas ICU/ICCU dan Perinatologi/NICU level 3.'
                ];

            case 'A5': // Preeklampsia Berat (PEB)
                return [
                    'tindakan_segera' => 'Rujuk segera ke RS PONEK / fasilitas rawat inap spesialis obstetri. Waspadai progresi menjadi Eklampsia.',
                    'protokol_jalan_nafas' => 'Tirah baring miring ke kiri untuk memaksimalkan perfusi uteroplasenta. Pasang kateter urine menetap (folley catheter) untuk evaluasi diuresis berkala.',
                    'medikamentosa' => [
                        'Pencegahan Kejang (Profilaksis MgSO4):' => 'Loading dose 4 gram MgSO4 40% IV lambat, dilanjutkan rumatan 1 gram/jam infus selama 24 jam.',
                        'Antihipertensi Akut:' => 'Nifedipin oral 10 mg (dapat diulang tiap 30 menit, maks 30 mg) atau Nikardipin intravena jika tensi ≥160/110 mmHg.',
                        'Kortikosteroid Pematangan Paru:' => 'Jika usia kehamilan <34 minggu: Deksametason 6 mg IM tiap 12 jam (4 dosis) atau Betametason 12 mg IM tiap 24 jam (2 dosis).'
                    ],
                    'kontraindikasi' => 'Hindari penurunan tensi terlalu agresif (sistolik <130 atau diastolik <80) karena dapat memicu gawat janin akibat hipoperfusi plasenta.',
                    'terminasi_kehamilan' => 'Evaluasi persalinan: Jika usia kehamilan ≥34 minggu, terminasi segera dianjurkan. Jika <34 minggu, rawat konservatif hanya di RS rujukan tingkat lanjut dengan monitoring ketat.',
                    'fasilitas_rujukan' => 'RSUD / Rumah Sakit tipe C/B yang memiliki fasilitas OK emergensi, dokter Sp.OG, Sp.A, dan fasilitas transfusi darah.'
                ];

            case 'A6': // Superimposed Preeclampsia
                return [
                    'tindakan_segera' => 'Rujuk ke Dokter Spesialis Obstetri & Ginekologi (Sp.OG) untuk tata laksana bersama dokter spesialis penyakit dalam (Sp.PD).',
                    'protokol_jalan_nafas' => 'Tirah baring teratur, hindari aktivitas fisik berat. Edukasi pemantauan gerakan janin (kick chart).',
                    'medikamentosa' => [
                        'Pengaturan Antihipertensi:' => 'Evaluasi dan eskalasi obat: Metildopa 250-500 mg PO 2-3 kali sehari, atau Labetalol 100-200 mg PO 2-3 kali sehari, atau Nifedipin extended-release 30-60 mg PO sekali sehari.',
                        'Pemeriksaan Laboratorium Berkala:' => 'Cek darah lengkap, trombosit, SGOT/SGPT, LDH, kreatinin serum, dan proteinuria kuantitatif 24 jam setiap minggu.'
                    ],
                    'kontraindikasi' => 'STOP segera obat golongan ACE-inhibitor (Captopril, Enalapril) dan ARB (Valsartan, Candesartan) jika pasien masih mengonsumsi!',
                    'terminasi_kehamilan' => 'Pertimbangkan terminasi elektif pada usia gestasi 37 minggu atau lebih awal jika terdapat komplikasi maternal-fetal.',
                    'fasilitas_rujukan' => 'Poli Spesialis Kebidanan & Kandungan RS Rujukan.'
                ];

            case 'A4': // Preeklampsia Ringan
                return [
                    'tindakan_segera' => 'Rawat jalan intensif dengan kontrol berkala 2 kali seminggu, atau pertimbangkan rawat singkat untuk evaluasi awal.',
                    'protokol_jalan_nafas' => 'Istirahat cukup (tirah baring tidak mutlak namun kurangi beban kerja), diet gizi seimbang dengan asupan protein cukup.',
                    'medikamentosa' => [
                        'Terapi Farmakologis:' => 'Obat antihipertensi belum diindikasikan jika tensi <150/100 mmHg kecuali jika timbul perburukan hemodinamik.',
                        'Suplementasi:' => 'Kalsium 1-1.5 gram/hari dan Asam Folat jika belum tercukupi.',
                        'Pemantauan Rutin:' => 'Pemeriksaan protein urine dipstick setiap kontrol, evaluasi kenaikan berat badan berlebih, dan USG biometri janin berkala.'
                    ],
                    'kontraindikasi' => 'Jangan gunakan obat penurun tensi sembarangan tanpa indikasi medis jelas pada preeklampsia ringan karena dapat menutupi perburukan gejala.',
                    'terminasi_kehamilan' => 'Direncanakan persalinan pervaginam spontan pada usia kehamilan 37 minggu penuh jika kondisi ibu dan janin tetap stabil.',
                    'fasilitas_rujukan' => 'Puskesmas PONED / Klinik Pratama dengan konsultasi dokter Sp.OG.'
                ];

            case 'A3': // Hipertensi Kronis
                return [
                    'tindakan_segera' => 'Pemeriksaan antenatal lebih sering (tiap 2 minggu s.d usia 28 mgg, kemudian tiap 1 minggu). Skrining berkala adanya superimposed preeclampsia.',
                    'protokol_jalan_nafas' => 'Hindari stres psikologis, olahraga ringan yang diizinkan dokter (senam hamil), batasi konsumsi garam berlebih.',
                    'medikamentosa' => [
                        'Obat Pilihan Pertama:' => 'Metildopa (250-500 mg oral 2-3x/hari) merupakan obat paling teruji keamanannya untuk janin.',
                        'Pilihan Alternatif:' => 'Nifedipin lepas lambat atau Labetalol oral.',
                        'Profilaksis Preeklampsia:' => 'Aspirin dosis rendah (Low-Dose Aspirin 75-150 mg/hari) mulai usia gestasi 12-16 minggu s.d 36 minggu untuk cegah preeklampsia.'
                    ],
                    'kontraindikasi' => 'KONTRAINDIKASI MUTLAK: ACE-Inhibitor & ARB menyebabkan agenesis ginjal janin, oligohidramnion, dan kematian janin intrauterin.',
                    'terminasi_kehamilan' => 'Jika tekanan darah terkontrol baik tanpa komplikasi, persalinan dapat ditunggu hingga usia 38-39 minggu.',
                    'fasilitas_rujukan' => 'Dokter Umum Puskesmas atau Dokter Spesialis Obstetri.'
                ];

            case 'A2': // Hipertensi Gestasional
                return [
                    'tindakan_segera' => 'Pemantauan tekanan darah berkala 2-3 kali seminggu di rumah atau faskes terdekat. Kontrol ulang tiap 1 minggu.',
                    'protokol_jalan_nafas' => 'Edukasi pasien dan keluarga mengenai tanda-tanda bahaya preeklampsia (sakit kepala hebat, pandangan kabur, mual muntah, bengkak mendadak).',
                    'medikamentosa' => [
                        'Terapi Farmakologis:' => 'Jika tensi mencapai ≥150/100 mmHg, pertimbangkan pemberian antihipertensi oral aman seperti Metildopa atau Nifedipin.',
                        'Pemantauan Lab:' => 'Cek ulang urin reduksi dan protein urin setiap minggu untuk deteksi dini konversi menjadi preeklampsia.'
                    ],
                    'kontraindikasi' => 'Hindari restriksi cairan berlebih. Jangan berikan obat penenang tanpa indikasi dokter.',
                    'terminasi_kehamilan' => 'Persalinan direncanakan saat cukup bulan (aterm, 37-39 minggu). Tensi biasanya normal kembali dalam 12 minggu pasca salin.',
                    'fasilitas_rujukan' => 'Fasilitas Kesehatan Tingkat Pertama (Puskesmas/Klinik).'
                ];

            case 'A1': // Normal
            default:
                return [
                    'tindakan_segera' => 'Pertahankan pemeriksaan antenatal terpadu (ANC) rutin sesuai standar Kemenkes RI (minimal 6 kali selama kehamilan).',
                    'protokol_jalan_nafas' => 'Pola hidup sehat, makanan bergizi seimbang tinggi protein dan serat, hidrasi cukup (minum 2-3 liter air per hari).',
                    'medikamentosa' => [
                        'Suplemen Standar:' => 'Tablet Tambah Darah (Fe) 1 tablet per hari, Asam Folat 400 mcg, Kalsium 500 mg.',
                        'Imunisasi:' => 'Lengkapi status imunisasi Tetanus Toxoid (TT).'
                    ],
                    'kontraindikasi' => 'Hindari merokok, paparan asap rokok (perokok pasif), konsumsi jamu-jamuan yang belum teruji klinis, dan konsumsi obat bebas tanpa resep.',
                    'terminasi_kehamilan' => 'Persalinan normal terencana pada usia gestasi 38-41 minggu.',
                    'fasilitas_rujukan' => 'Bidan Praktik Mandiri, Puskesmas, atau Klinik Pratama.'
                ];
        }
    }
}
