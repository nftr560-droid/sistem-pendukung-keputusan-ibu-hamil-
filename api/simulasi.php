<?php
/**
 * AJAX Live Simulation & Inference Endpoint
 */

header('Content-Type: application/json');
require_once __DIR__ . '/../includes/cdss_engine.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $input = [
        'umur'                  => isset($_POST['umur']) ? (int)$_POST['umur'] : 25,
        'usia_kehamilan'        => isset($_POST['usia_kehamilan']) ? (int)$_POST['usia_kehamilan'] : 20,
        'tekanan_sistolik'      => isset($_POST['tekanan_sistolik']) ? (int)$_POST['tekanan_sistolik'] : 120,
        'tekanan_diastolik'     => isset($_POST['tekanan_diastolik']) ? (int)$_POST['tekanan_diastolik'] : 80,
        'proteinuria'           => isset($_POST['proteinuria']) ? trim($_POST['proteinuria']) : 'Negatif',
        'edema'                 => isset($_POST['edema']) ? $_POST['edema'] : 'Tidak',
        'sakit_kepala'          => isset($_POST['sakit_kepala']) ? $_POST['sakit_kepala'] : 'Tidak',
        'gangguan_penglihatan'  => isset($_POST['gangguan_penglihatan']) ? $_POST['gangguan_penglihatan'] : 'Tidak',
        'nyeri_ulu_hati'        => isset($_POST['nyeri_ulu_hati']) ? $_POST['nyeri_ulu_hati'] : 'Tidak',
        'trombosit_rendah'      => isset($_POST['trombosit_rendah']) ? $_POST['trombosit_rendah'] : 'Tidak',
        'fungsi_hati'           => isset($_POST['fungsi_hati']) ? $_POST['fungsi_hati'] : 'Tidak',
        'fungsi_ginjal'         => isset($_POST['fungsi_ginjal']) ? $_POST['fungsi_ginjal'] : 'Tidak',
        'riwayat_hipertensi'    => isset($_POST['riwayat_hipertensi']) ? $_POST['riwayat_hipertensi'] : 'Tidak',
        'riwayat_preeklampsia'  => isset($_POST['riwayat_preeklampsia']) ? $_POST['riwayat_preeklampsia'] : 'Tidak',
        'kejang'                => isset($_POST['kejang']) ? $_POST['kejang'] : 'Tidak',
        'kehamilan_kembar'      => isset($_POST['kehamilan_kembar']) ? $_POST['kehamilan_kembar'] : 'Tidak'
    ];

    $result = CDSSEngine::evaluasi($input);
    echo json_encode([
        'status' => 'success',
        'result' => $result
    ]);
    exit;
}

echo json_encode(['status' => 'error', 'message' => 'Invalid request method.']);
