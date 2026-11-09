# Sistem Pendukung Keputusan Klinis (CDSS) Penanganan Hipertensi pada Ibu Hamil Berisiko Tinggi

Sistem web **Clinical Decision Support System (CDSS) / Sistem Pendukung Keputusan Klinis (SPKK)** untuk mendiagnosis, menstratifikasi risiko, dan merumuskan protokol penanganan medis hipertensi pada kehamilan (Preeklampsia, Eklampsia, Hipertensi Gestasional, Hipertensi Kronis, dan Superimposed Preeclampsia) menggunakan metode **Rule-Based System dengan Forward Chaining**.

Aplikasi ini dikembangkan berdasarkan rancangan basis pengetahuan medis pada file [SPKK_Hipertensi_Kehamilan_Kriteria_Alternatif (1).xlsx](file:///d:/SPKK/SPKK_Hipertensi_Kehamilan_Kriteria_Alternatif%20(1).xlsx) dan basis data [spkk_hipertensi.sql](file:///d:/SPKK/spkk_hipertensi.sql).

---

## 🏥 Fitur Utama Sistem

1. **Dashboard & Clinical Triage Analytics**:
   - Kartu metrik: Total Pasien, Total Pemeriksaan, Kasus Preeklampsia, dan Gawat Darurat (Eklampsia).
   - Banner Peringatan Dini (Emergency Alert) otomatis jika terdeteksi kasus gawat darurat (Eklampsia / PEB).
   - Grafik interaktif: Distribusi 7 Diagnosis CDSS (Donut Chart) dan Stratifikasi Level Risiko (Bar Chart).
   - Daftar riwayat konsultasi terbaru dengan tombol cepat menuju detail rekam medis.

2. **Skrining Klinis & Inferensi CDSS (Forward Chaining)**:
   - Pilihan: Pasien Baru atau Pasien Terdaftar (Auto-fill data).
   - Form klinis terstruktur (Demografi C1-C2, Tanda Vital C3-C4, Urinalisis C5-C6, Gejala Berat C7-C12, Riwayat & Tanda Kritis C13-C16).
   - Indikator tensi interaktif dengan kalkulasi otomatis **MAP (Mean Arterial Pressure)**.
   - **Evaluasi Kaidah Realtime**: Panel samping menampilkan prediksi diagnosis, rule terpicu, dan tindakan segera secara langsung saat nakes mengisi form.

3. **Dossier Rekam Medis & Lembar Rujukan Resmi**:
   - Diagnosis Utama & Badge Level Risiko (Rendah, Sedang, Sedang-Tinggi, Tinggi, Sangat Tinggi).
   - **Rantai Inferensi Transparan (Forward Chaining Trace)**: Menjelaskan langkah demi langkah kaidah produksi mana yang terpicu (FIRE) beserta kondisi pendukungnya.
   - Matriks 16 kriteria klinis pasien beserta bobot risikonya.
   - Protokol Penanganan Klinis Lengkap: Tindakan segera, jalur rujukan, posisi/oksigenasi, protokol MgSO4 (bolus & rumatan), antihipertensi aman, kontraindikasi medis, dan terminasi kehamilan.
   - Format cetak resmi (**Print View / Surat Rujukan**) lengkap dengan kop fasilitas kesehatan dan kolom tanda tangan Dokter Penanggung Jawab (Sp.OG) & Bidan.

4. **Manajemen Master Data Pasien**:
   - Pencarian pasien (Nama, No. RM, Alamat) dan filter berdasarkan trimester gestasi.
   - Modal tambah pasien dan edit data pasien.
   - Tombol langsung untuk memulai skrining pasien terpilih.

5. **Riwayat Pemeriksaan & Ekspor Data**:
   - Filter multi-kriteria: Pencarian pasien, diagnosis, level risiko, dan rentang tanggal pemeriksaan.
   - Fitur **Ekspor ke CSV / Excel** untuk pelaporan epidemiologis dan audit maternal.

6. **Eksplorasi Basis Pengetahuan (Knowledge Base) & Simulator Sandbox**:
   - Tab 1: Kriteria Klinis & Bobot (C1 - C16).
   - Tab 2: Alternatif Diagnosis & Level Risiko (A1 - A7).
   - Tab 3: Matriks Relasi Kriteria vs Alternatif (AND / OR matrix).
   - Tab 4: Basis Kaidah Produksi IF-THEN (R1 - R6).
   - Tab 5: Tabel Keputusan Ringkas (Decision Table).
   - Tab 6: **Simulator Sandbox Interaktif**: Uji coba skenario kombinasi gejala klinis secara instan di browser tanpa memengaruhi database.

---

## 🧬 Pemetaan Basis Pengetahuan

### 1. Kriteria Klinis (C1 - C16)
- **C1**: Usia ibu hamil (`<20 tahun` [1], `20-35 tahun` [0], `>35 tahun` [1])
- **C2**: Usia kehamilan (`<20 minggu` [0], `≥20 minggu` [1])
- **C3**: Tekanan darah sistolik (`<140 mmHg` [0], `140-159 mmHg` [1], `≥160 mmHg` [2])
- **C4**: Tekanan darah diastolik (`<90 mmHg` [0], `90-109 mmHg` [1], `≥110 mmHg` [2])
- **C5**: Proteinuria (`Negatif` [0], `+1 / +2` [1], `+3 / +4` [2])
- **C6**: Edema patologis (`Tidak` [0], `Ya` [1])
- **C7**: Sakit kepala hebat frontal/oksipital (`Tidak` [0], `Ya` [1])
- **C8**: Gangguan penglihatan / kabur / skotoma (`Tidak` [0], `Ya` [1])
- **C9**: Nyeri ulu hati / epigastrium (`Tidak` [0], `Ya` [1])
- **C10**: Trombosit rendah < 100.000/µL (`Tidak` [0], `Ya` [1])
- **C11**: Fungsi hati terganggu / SGOT-SGPT naik (`Tidak` [0], `Ya` [1])
- **C12**: Fungsi ginjal terganggu / Kreatinin naik (`Tidak` [0], `Ya` [1])
- **C13**: Riwayat hipertensi sebelum hamil (`Tidak` [0], `Ya` [1])
- **C14**: Riwayat preeklampsia kehamilan sebelumnya (`Tidak` [0], `Ya` [1])
- **C15**: Kejang maternal (`Tidak` [0], `Ya` [2])
- **C16**: Kehamilan kembar / gemelli (`Tidak` [0], `Ya` [1])

### 2. Alternatif Diagnosis (A1 - A7)
- **A1**: Normal / tidak hipertensi (Risiko: Rendah)
- **A2**: Hipertensi Gestasional (Risiko: Sedang)
- **A3**: Hipertensi Kronis (Risiko: Sedang)
- **A4**: Preeklampsia Ringan (Risiko: Sedang-Tinggi)
- **A5**: Preeklampsia Berat (Risiko: Tinggi)
- **A6**: Superimposed Preeclampsia (Risiko: Tinggi)
- **A7**: Eklampsia (Risiko: Sangat Tinggi / Gawat Darurat)

### 3. Kaidah Produksi (Rules R1 - R6)
- **R1 (Hipertensi Gestasional)**: `IF C2 ≥ 20 mgg AND (C3 ≥ 140 OR C4 ≥ 90) AND C5 = Negatif AND C13 = Tidak THEN A2`
- **R2 (Hipertensi Kronis)**: `IF (C2 < 20 mgg AND (C3 ≥ 140 OR C4 ≥ 90)) OR (C13 = Ya AND C5 = Negatif) THEN A3`
- **R3 (Preeklampsia Ringan)**: `IF C2 ≥ 20 mgg AND (C3 ≥ 140 OR C4 ≥ 90) AND C5 ≥ +1 THEN A4`
- **R4 (Preeklampsia Berat)**: `IF Preeklampsia Terpenuhi AND ((C3 ≥ 160 OR C4 ≥ 110 OR C5 ≥ +3) OR (C7 OR C8 OR C9 OR C10 OR C11 OR C12)) THEN A5`
- **R5 (Eklampsia)**: `IF (Preeklampsia / Hipertensi Terpenuhi) AND C15 = Ya (Kejang) THEN A7`
- **R6 (Superimposed Preeclampsia)**: `IF C13 = Ya AND C2 ≥ 20 mgg AND C5 ≥ +1 THEN A6`

---

## 🚀 Cara Menjalankan Aplikasi

Aplikasi dapat diakses langsung melalui dua cara:

### Cara 1: Menggunakan Web Server Laragon / Apache (Rekomendasi)
Karena direktori `d:\SPKK` telah terhubung langsung ke Laragon `C:\laragon\www\spkk`, pastikan Laragon aktif (Apache & MySQL running):
- Buka browser dan buka:
  ```
  http://localhost/spkk/
  ```

### Cara 2: Menggunakan PHP Built-in Server
Jika ingin menjalankan secara standalone di port lain:
```bash
php -S localhost:8000 -t d:\SPKK
```
Kemudian buka browser di: `http://localhost:8000/`

---

## 📂 Struktur Berkas Proyek

```
d:\SPKK\
├── config\
│   └── database.php           # Konfigurasi PDO MySQL & migrasi otomatis skema
├── database\
│   └── seed.php               # Seeder kriteria, alternatif, dan pasien sampel
├── includes\
│   ├── cdss_engine.php        # Mesin inferensi Forward Chaining & protokol klinis
│   ├── header.php             # Template header & navigasi navbar
│   └── footer.php             # Template footer aplikasi
├── assets\
│   ├── css\
│   │   └── style.css          # Desain sistem medis modern & styling cetak rekam medis
│   └── js\
│       └── app.js             # Kontroler UI, gauge tensi real-time & live inference
├── api\
│   └── simulasi.php           # Endpoint AJAX live inference & sandbox
├── index.php                  # Dashboard CDSS & visualisasi analitik klinis
├── konsultasi.php             # Antarmuka skrining klinis & pengisian form
├── hasil_detail.php           # Detail rekam medis, jejak inferensi & surat rujukan
├── pasien.php                 # Manajemen master data pasien ibu hamil
├── riwayat.php                # Arsip riwayat diagnosis & ekspor CSV
├── basis_pengetahuan.php      # Eksplorasi knowledge base & simulator sandbox
├── spkk_hipertensi.sql        # Skema basis data SQL
├── SPKK_Hipertensi_Kehamilan_Kriteria_Alternatif (1).xlsx # Berkas acuan Excel
└── README.md                  # Dokumentasi teknis proyek
```
