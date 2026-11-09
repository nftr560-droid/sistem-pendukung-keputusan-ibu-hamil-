/**
 * SPKK Hipertensi Kehamilan (CDSS)
 * Client-side Controller & Interactivity
 */

document.addEventListener('DOMContentLoaded', function () {
    // 1. Live Blood Pressure Calculator & Gauge
    const sistolikInput = document.getElementById('tekanan_sistolik');
    const diastolikInput = document.getElementById('tekanan_diastolik');
    const bpCategoryText = document.getElementById('bpCategoryText');
    const bpMapText = document.getElementById('bpMapText');
    const bpBadge = document.getElementById('bpBadge');

    function updateBloodPressureStatus() {
        if (!sistolikInput || !diastolikInput) return;

        const sys = parseInt(sistolikInput.value) || 0;
        const dia = parseInt(diastolikInput.value) || 0;

        if (sys <= 0 && dia <= 0) return;

        // Calculate MAP (Mean Arterial Pressure)
        // MAP = Diastolik + 1/3 * (Sistolik - Diastolik)
        let mapVal = dia + ((sys - dia) / 3);
        mapVal = Math.round(mapVal * 10) / 10;

        if (bpMapText) {
            bpMapText.textContent = `MAP: ${mapVal} mmHg` + (mapVal >= 105 ? ' (Risiko Tinggi MAP ≥105)' : '');
        }

        let cat = 'Normotensif (Normal)';
        let badgeClass = 'badge-rendah';

        if (sys >= 160 || dia >= 110) {
            cat = 'Hipertensi Berat / Krisis Hipertensi (≥160/110 mmHg)';
            badgeClass = 'badge-gawat';
        } else if (sys >= 140 || dia >= 90) {
            cat = 'Hipertensi Ringan-Sedang (140-159 / 90-109 mmHg)';
            badgeClass = 'badge-tinggi';
        } else if (sys >= 130 || dia >= 85) {
            cat = 'Pra-Hipertensi / Waspada (130-139 / 85-89 mmHg)';
            badgeClass = 'badge-sedang';
        }

        if (bpCategoryText) {
            bpCategoryText.textContent = cat;
        }

        if (bpBadge) {
            bpBadge.className = `badge ${badgeClass}`;
            bpBadge.textContent = cat;
        }

        // Trigger Live Inference Preview if available
        triggerLivePreview();
    }

    if (sistolikInput && diastolikInput) {
        sistolikInput.addEventListener('input', updateBloodPressureStatus);
        diastolikInput.addEventListener('input', updateBloodPressureStatus);
        updateBloodPressureStatus();
    }

    // 2. Live Clinical Inference Preview (Debounced)
    let previewTimer = null;
    function triggerLivePreview() {
        const previewBox = document.getElementById('livePreviewCard');
        if (!previewBox) return;

        clearTimeout(previewTimer);
        previewTimer = setTimeout(runLiveInference, 300);
    }

    // Attach listener to all inputs in consultation form
    const consultForm = document.getElementById('consultForm');
    if (consultForm) {
        consultForm.addEventListener('change', triggerLivePreview);
    }

    function runLiveInference() {
        const form = document.getElementById('consultForm');
        if (!form) return;

        const formData = new FormData(form);
        const params = new URLSearchParams();
        for (const [key, value] of formData.entries()) {
            params.append(key, value);
        }

        fetch('api/simulasi.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: params.toString()
        })
        .then(response => response.json())
        .then(data => {
            if (data.status === 'success') {
                const res = data.result;
                const diagTitle = document.getElementById('previewDiagnosis');
                const riskBadge = document.getElementById('previewRiskBadge');
                const ruleText  = document.getElementById('previewRule');
                const scoreText = document.getElementById('previewScore');
                const actionText = document.getElementById('previewAction');

                if (diagTitle) diagTitle.textContent = res.nama_alternatif;
                if (ruleText) ruleText.textContent = `Aturan Terpicu: ${res.rule_terpicu}`;
                if (scoreText) scoreText.textContent = `Skor Risiko: ${res.skor_risiko}`;
                if (actionText) actionText.textContent = res.rekomendasi.tindakan_segera || '-';

                if (riskBadge) {
                    riskBadge.textContent = res.level_risiko;
                    let badgeClass = 'badge-rendah';
                    if (res.level_risiko.includes('Sangat Tinggi')) badgeClass = 'badge-gawat';
                    else if (res.level_risiko.includes('Tinggi')) badgeClass = 'badge-tinggi';
                    else if (res.level_risiko.includes('Sedang-Tinggi')) badgeClass = 'badge-sedang-tinggi';
                    else if (res.level_risiko.includes('Sedang')) badgeClass = 'badge-sedang';
                    riskBadge.className = `badge ${badgeClass}`;
                }
            }
        })
        .catch(err => {
            console.log('Preview fetch note:', err);
        });
    }

    // 3. Auto-fill when existing patient is selected in consultation
    const selectPasien = document.getElementById('select_existing_pasien');
    if (selectPasien) {
        selectPasien.addEventListener('change', function () {
            const selectedOpt = this.options[this.selectedIndex];
            if (!selectedOpt || !selectedOpt.value) return;

            const umur = selectedOpt.getAttribute('data-umur');
            const uk = selectedOpt.getAttribute('data-uk');
            const nama = selectedOpt.getAttribute('data-nama');
            const alamat = selectedOpt.getAttribute('data-alamat');

            if (document.getElementById('umur')) document.getElementById('umur').value = umur || '';
            if (document.getElementById('usia_kehamilan')) document.getElementById('usia_kehamilan').value = uk || '';
            if (document.getElementById('nama_pasien')) document.getElementById('nama_pasien').value = nama || '';
            if (document.getElementById('alamat')) document.getElementById('alamat').value = alamat || '';

            triggerLivePreview();
        });
    }

    // 4. Client Search Filter for Tables
    const searchInput = document.getElementById('tableSearchInput');
    if (searchInput) {
        searchInput.addEventListener('keyup', function () {
            const query = this.value.toLowerCase();
            const tableRows = document.querySelectorAll('.custom-table tbody tr');
            tableRows.forEach(row => {
                const text = row.textContent.toLowerCase();
                row.style.display = text.includes(query) ? '' : 'none';
            });
        });
    }

    // 5. Print Helper
    window.cetakDossier = function () {
        window.print();
    };
});
