# Sheet: Cover

Total rows: 9

| RANCANGAN BASIS PENGETAHUAN SPKK |  |
| Penanganan Hipertensi Risiko Tinggi pada Ibu Hamil |  |
| Metode: Rule-Based System dengan Forward Chaining |  |
| Sheet | Isi |
| 1. Kriteria | Daftar kriteria/gejala/faktor risiko (C1-C16) beserta sub-nilai |
| 2. Alternatif | Daftar alternatif hasil diagnosis (A1-A7) beserta level risiko dan rekomendasi |
| 3. Matriks Relasi | Matriks relasi kriteria terhadap setiap alternatif (basis penyusunan rule) |
| 4. Rule Base | Basis pengetahuan dalam bentuk kaidah produksi IF-THEN |
| 5. Tabel Keputusan | Tabel keputusan (decision table) versi ringkas gejala vs diagnosis |


# Sheet: Kriteria

Total rows: 39

| Tabel Kriteria |  |  |  |  |
| Kriteria/gejala/faktor risiko hipertensi pada ibu hamil |  |  |  |  |
| Kode | Kriteria | Sub-Nilai / Kategori | Bobot | Keterangan |
| C1 | Usia ibu hamil | <20 tahun | 1 | Risiko meningkat pada usia ekstrem |
|  |  | 20-35 tahun | 0 | Kategori risiko rendah |
|  |  | >35 tahun | 1 | Risiko meningkat pada usia ekstrem |
| C2 | Usia kehamilan | <20 minggu | 0 | Menentukan hipertensi kronis vs gestasional |
|  |  | ≥20 minggu | 1 | Menentukan hipertensi kronis vs gestasional |
| C3 | Tekanan darah sistolik | <140 mmHg | 0 | Batas normal |
|  |  | 140-159 mmHg | 1 | Hipertensi ringan-sedang |
|  |  | ≥160 mmHg | 2 | Hipertensi berat |
| C4 | Tekanan darah diastolik | <90 mmHg | 0 | Batas normal |
|  |  | 90-109 mmHg | 1 | Hipertensi ringan-sedang |
|  |  | ≥110 mmHg | 2 | Hipertensi berat |
| C5 | Proteinuria | Negatif | 0 | Tidak ada protein dalam urine |
|  |  | +1 / +2 | 1 | Indikasi preeklampsia ringan |
|  |  | +3 | 2 | Indikasi preeklampsia berat |
| C6 | Edema (bengkak) | Tidak | 0 | Bengkak tangan/kaki/wajah |
|  |  | Ya | 1 | Bengkak tangan/kaki/wajah |
| C7 | Sakit kepala hebat | Tidak | 0 | Gejala preeklampsia berat |
|  |  | Ya | 1 | Gejala preeklampsia berat |
| C8 | Gangguan penglihatan | Tidak | 0 | Pandangan kabur/berkunang |
|  |  | Ya | 1 | Pandangan kabur/berkunang |
| C9 | Nyeri ulu hati (epigastrium) | Tidak | 0 | Gejala preeklampsia berat |
|  |  | Ya | 1 | Gejala preeklampsia berat |
| C10 | Trombosit rendah (<100.000) | Tidak | 0 | Data laboratorium |
|  |  | Ya | 1 | Data laboratorium |
| C11 | Fungsi hati terganggu | Tidak | 0 | SGOT/SGPT meningkat |
|  |  | Ya | 1 | SGOT/SGPT meningkat |
| C12 | Fungsi ginjal terganggu | Tidak | 0 | Kreatinin meningkat |
|  |  | Ya | 1 | Kreatinin meningkat |
| C13 | Riwayat hipertensi sebelum hamil | Tidak | 0 | Riwayat penyakit |
|  |  | Ya | 1 | Riwayat penyakit |
| C14 | Riwayat preeklampsia kehamilan sebelumnya | Tidak | 0 | Riwayat kehamilan |
|  |  | Ya | 1 | Riwayat kehamilan |
| C15 | Kejang | Tidak | 0 | Tanda eklampsia (gawat darurat) |
|  |  | Ya | 1 | Tanda eklampsia (gawat darurat) |
| C16 | Kehamilan kembar | Tidak | 0 | Faktor risiko tambahan |
|  |  | Ya | 1 | Faktor risiko tambahan |


# Sheet: Alternatif

Total rows: 10

| Tabel Alternatif |  |  |  |
| Kemungkinan hasil klasifikasi/diagnosis SPKK |  |  |  |
| Kode | Alternatif (Diagnosis) | Level Risiko | Rekomendasi Tindak Lanjut |
| A1 | Normal / tidak hipertensi | Rendah | Pemeriksaan antenatal rutin sesuai jadwal |
| A2 | Hipertensi Gestasional | Sedang | Pantau tekanan darah berkala, kontrol lebih sering, edukasi tanda bahaya |
| A3 | Hipertensi Kronis | Sedang | Kontrol tekanan darah, evaluasi obat antihipertensi yang aman untuk kehamilan |
| A4 | Preeklampsia Ringan | Sedang-Tinggi | Rawat jalan intensif, cek proteinuria & lab berkala, edukasi tanda bahaya |
| A5 | Preeklampsia Berat | Tinggi | Rujuk segera ke fasilitas dengan fasilitas obstetri lengkap, pertimbangkan rawat inap |
| A6 | Superimposed Preeclampsia | Tinggi | Rujuk ke dokter spesialis kandungan, pemantauan ketat ibu dan janin |
| A7 | Eklampsia | Sangat Tinggi (gawat darurat) | Kegawatdaruratan - rujuk SEGERA, penanganan kejang, stabilisasi ibu dan janin |


# Sheet: Matriks Relasi

Total rows: 22

| Matriks Relasi Kriteria vs Alternatif |  |  |  |  |  |  |  |  |
| Tanda V = kriteria wajib terpenuhi (AND) | Tanda O = salah satu terpenuhi (OR, khusus grup preeklampsia berat) | - = tidak relevan |  |  |  |  |  |  |  |  |
| Kode | Kriteria | A1 | A2 | A3 | A4 | A5 | A6 | A7 |
| C2 | Usia kehamilan ≥ 20 minggu | - | V | - | V | V | V | V |
| C2 | Usia kehamilan < 20 minggu | - | - | V | - | - | - | - |
| C3 | Sistolik ≥ 140 mmHg | - | V | V | V | - | V | V |
| C3 | Sistolik ≥ 160 mmHg | - | - | - | - | V | - | - |
| C4 | Diastolik ≥ 90 mmHg | - | V | V | V | - | V | V |
| C4 | Diastolik ≥ 110 mmHg | - | - | - | - | V | - | - |
| C5 | Proteinuria ≥ +1 | - | - | - | V | V | V | V |
| C7 | Sakit kepala hebat | - | - | - | - | O | - | V |
| C8 | Gangguan penglihatan | - | - | - | - | O | - | V |
| C9 | Nyeri ulu hati | - | - | - | - | O | - | V |
| C10 | Trombosit rendah | - | - | - | - | O | - | V |
| C11 | Fungsi hati terganggu | - | - | - | - | O | - | V |
| C12 | Fungsi ginjal terganggu | - | - | - | - | O | - | V |
| C13 | Riwayat hipertensi sebelumnya | - | - | V | - | - | V | - |
| C15 | Kejang | - | - | - | - | - | - | V |
| Keterangan: |
| V | Wajib terpenuhi (relasi AND) |
| O | Salah satu terpenuhi sudah cukup (relasi OR, grup gejala preeklampsia berat) |
| - | Tidak menjadi syarat untuk alternatif tersebut |


# Sheet: Rule Base

Total rows: 9

| Basis Pengetahuan (Rule Base) |  |  |  |
| Kaidah produksi IF-THEN hasil turunan dari matriks relasi kriteria-alternatif |  |  |  |
| Kode Rule | IF (Kondisi) | THEN (Kesimpulan) | Keterangan |
| R1 | C2≥20 minggu AND C3≥140 AND C4≥90 AND C5=Negatif AND C13=Tidak | A2 - Hipertensi Gestasional | Hipertensi baru muncul setelah 20 minggu tanpa proteinuria |
| R2 | C2<20 minggu AND C3≥140 AND C4≥90 | A3 - Hipertensi Kronis | Hipertensi sudah ada sebelum usia kehamilan 20 minggu |
| R3 | C2≥20 minggu AND C3≥140 AND C4≥90 AND C5≥+1 | A4 - Preeklampsia Ringan | Hipertensi disertai proteinuria setelah 20 minggu |
| R4 | A4 terpenuhi AND (C3≥160 OR C4≥110) AND (C7 OR C8 OR C9 OR C10 OR C11 OR C12) | A5 - Preeklampsia Berat | Preeklampsia ringan berkembang dengan tekanan darah sangat tinggi dan/atau gejala berat (relasi OR pada gejala) |
| R5 | A5 terpenuhi AND C15=Ya | A7 - Eklampsia | Preeklampsia berat disertai kejang - kondisi gawat darurat |
| R6 | C13=Ya AND C2≥20 minggu AND C5≥+1 (baru muncul) | A6 - Superimposed Preeclampsia | Ibu dengan riwayat hipertensi kronis mengalami proteinuria baru |


# Sheet: Tabel Keputusan

Total rows: 10

| Tabel Keputusan (Decision Table) |  |  |  |  |  |  |  |  |  |
| *Gejala berat = sakit kepala hebat / gangguan penglihatan / nyeri ulu hati / trombosit rendah / fungsi hati-ginjal terganggu (salah satu) |  |  |  |  |  |  |  |  |  |
| No | Usia Kehamilan | Sistolik | Diastolik | Proteinuria | Gejala Berat* | Kejang | Riwayat Hipertensi | Hasil Diagnosis | Level Risiko |
| 1 | - | <140 | <90 | Negatif | Tidak | Tidak | Tidak | Normal | Rendah |
| 2 | ≥20 mgg | ≥140 | ≥90 | Negatif | Tidak | Tidak | Tidak | Hipertensi Gestasional | Sedang |
| 3 | <20 mgg | ≥140 | ≥90 | - | - | Tidak | - | Hipertensi Kronis | Sedang |
| 4 | ≥20 mgg | ≥140 | ≥90 | ≥+1 | Tidak | Tidak | - | Preeklampsia Ringan | Sedang-Tinggi |
| 5 | ≥20 mgg | ≥160 | ≥110 | ≥+1 | Ya | Tidak | - | Preeklampsia Berat | Tinggi |
| 6 | ≥20 mgg | ≥140 | ≥90 | ≥+1 (baru) | - | Tidak | Ya | Superimposed Preeclampsia | Tinggi |
| 7 | ≥20 mgg | ≥160 | ≥110 | ≥+1 | Ya | Ya | - | Eklampsia | Sangat Tinggi |


# Sheet: Simulasi Diagnosis

Total rows: 29

| Simulasi Diagnosis Otomatis |
| Rumus penentuan Hasil Diagnosis & Level Risiko diturunkan dari sheet 'Rule Base' (R1-R6) |
| Legenda: sel BIRU = input data pasien (silakan diubah/ditambah) | sel HITAM tebal = hasil rumus (jangan diedit) |
| No | Usia Kehamilan (minggu) | Sistolik (mmHg) | Diastolik (mmHg) | Proteinuria | Sakit Kepala Hebat | Gangguan Penglihatan | Nyeri Ulu Hati | Trombosit Rendah | Fungsi Hati Terganggu | Fungsi Ginjal Terganggu | Riwayat Hipertensi Sebelum Hamil | Kejang | Hasil Diagnosis (rumus) | Level Risiko (rumus) | Rekomendasi Tindak Lanjut (rumus) | Proteinuria Positif? (bantu) | Ada Gejala Berat? (bantu) |
| 1 | 25 | 120 | 75 | Negatif | Tidak | Tidak | Tidak | Tidak | Tidak | Tidak | Tidak | Tidak | Normal / tidak hipertensi | Rendah | Pemeriksaan antenatal rutin sesuai jadwal | Tidak | Tidak |
| 2 | 28 | 145 | 95 | Negatif | Tidak | Tidak | Tidak | Tidak | Tidak | Tidak | Tidak | Tidak | Hipertensi Gestasional | Sedang | Pantau tekanan darah berkala, kontrol lebih sering, edukasi tanda bahaya | Tidak | Tidak |
| 3 | 12 | 150 | 95 | Negatif | Tidak | Tidak | Tidak | Tidak | Tidak | Tidak | Tidak | Tidak | Hipertensi Kronis | Sedang | Kontrol tekanan darah, evaluasi obat antihipertensi yang aman untuk kehamilan | Tidak | Tidak |
| 4 | 30 | 148 | 92 | +1/+2 | Tidak | Tidak | Tidak | Tidak | Tidak | Tidak | Tidak | Tidak | Preeklampsia Ringan | Sedang-Tinggi | Rawat jalan intensif, cek proteinuria & lab berkala, edukasi tanda bahaya | Ya | Tidak |
| 5 | 34 | 165 | 115 | +3 | Ya | Tidak | Tidak | Tidak | Tidak | Tidak | Tidak | Tidak | Preeklampsia Berat | Tinggi | Rujuk segera ke fasilitas dengan fasilitas obstetri lengkap, pertimbangkan rawat inap | Ya | Ya |
| 6 | 26 | 150 | 95 | +1/+2 | Tidak | Tidak | Tidak | Tidak | Tidak | Tidak | Ya | Tidak | Superimposed Preeclampsia | Tinggi | Rujuk ke dokter spesialis kandungan, pemantauan ketat ibu dan janin | Ya | Tidak |
| 7 | 37 | 170 | 120 | +3 | Ya | Tidak | Tidak | Tidak | Tidak | Tidak | Tidak | Ya | Eklampsia | Sangat Tinggi (gawat darurat) | Kegawatdaruratan - rujuk SEGERA, penanganan kejang, stabilisasi ibu dan janin | Ya | Ya |
| 8 | 32 | 152 | 98 | 3 | Ya | Tidak | Tidak | Tidak | Tidak | Tidak | Tidak | Tidak | Preeklampsia Ringan | Sedang-Tinggi | Rawat jalan intensif, cek proteinuria & lab berkala, edukasi tanda bahaya | Ya | Ya |
| 9 | 20 | 123 | 100 | 4 | Tidak | Ya | Tidak | Tidak | Tidak | Tidak | Tidak | Tidak | Normal / tidak hipertensi | Rendah | Pemeriksaan antenatal rutin sesuai jadwal | Ya | Ya |
| 10 | 25 | 150 | 76 | 5 | Ya | Tidak | Ya | Ya | Tidak | Tidak | Ya | Tidak | Superimposed Preeclampsia | Tinggi | Rujuk ke dokter spesialis kandungan, pemantauan ketat ibu dan janin | Ya | Ya |
| 11 | 42 | 200 | 120 | 5 | Ya | Tidak | Ya | Tidak | Tidak | Ya | Tidak | Ya | Eklampsia | Sangat Tinggi (gawat darurat) | Kegawatdaruratan - rujuk SEGERA, penanganan kejang, stabilisasi ibu dan janin | ya | Ya |
| 12 | 32 | 190 | 130 | 6 | Ya | Tidak | Ya | Tidak | Tidak | Ya | Ya | Tidak | Preeklampsia Berat | Tinggi | Rujuk segera ke fasilitas dengan fasilitas obstetri lengkap, pertimbangkan rawat inap | Ya | Ya |
| 13 |  |  |  |  |  |  |  |  |  |  |  |  | None | - | - | None | Tidak |
| 14 |  |  |  |  |  |  |  |  |  |  |  |  | None | - | - | None | Tidak |
| 15 |  |  |  |  |  |  |  |  |  |  |  |  | None | - | - | None | Tidak |
| 16 |  |  |  |  |  |  |  |  |  |  |  |  | None | - | - | None | Tidak |
| 17 |  |  |  |  |  |  |  |  |  |  |  |  | None | - | - | None | Tidak |
| 18 |  |  |  |  |  |  |  |  |  |  |  |  | None | - | - | None | Tidak |
| 19 |  |  |  |  |  |  |  |  |  |  |  |  | None | - | - | None | Tidak |
| 20 |  |  |  |  |  |  |  |  |  |  |  |  | None | - | - | None | Tidak |
| 21 |  |  |  |  |  |  |  |  |  |  |  |  | None | - | - | None | Tidak |
| 22 |  |  |  |  |  |  |  |  |  |  |  |  | None | - | - | None | Tidak |
| 23 |  |  |  |  |  |  |  |  |  |  |  |  | None | - | - | None | Tidak |
| 24 |  |  |  |  |  |  |  |  |  |  |  |  | None | - | - | None | Tidak |
| 25 |  |  |  |  |  |  |  |  |  |  |  |  | None | - | - | None | Tidak |


