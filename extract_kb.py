import json

with open('excel_dump.json', 'r', encoding='utf-8') as f:
    d = json.load(f)

# Extract structured Kriteria
kriteria_list = []
curr_kode = ''
curr_nama = ''
for row in d['Kriteria'][3:]:
    if not row or not any(row): continue
    kode = row[0] if len(row) > 0 and row[0] else curr_kode
    nama = row[1] if len(row) > 1 and row[1] else curr_nama
    sub = row[2] if len(row) > 2 else ''
    bobot = row[3] if len(row) > 3 else '0'
    ket = row[4] if len(row) > 4 else ''
    curr_kode = kode
    curr_nama = nama
    kriteria_list.append({
        'kode': kode,
        'nama': nama,
        'sub': sub,
        'bobot': float(bobot) if bobot else 0,
        'ket': ket
    })

# Extract structured Alternatif
alternatif_list = []
for row in d['Alternatif'][3:]:
    if not row or not any(row): continue
    kode = row[0] if len(row) > 0 else ''
    nama = row[1] if len(row) > 1 else ''
    level = row[2] if len(row) > 2 else ''
    rekom = row[3] if len(row) > 3 else ''
    alternatif_list.append({
        'kode': kode,
        'nama': nama,
        'level_risiko': level,
        'rekomendasi': rekom
    })

# Extract Rule Base
rule_list = []
for row in d['Rule Base'][3:]:
    if not row or not any(row): continue
    kode = row[0] if len(row) > 0 else ''
    if_cond = row[1] if len(row) > 1 else ''
    then_conc = row[2] if len(row) > 2 else ''
    ket = row[3] if len(row) > 3 else ''
    rule_list.append({
        'kode': kode,
        'if': if_cond,
        'then': then_conc,
        'ket': ket
    })

# Extract Simulation cases
simulasi_list = []
for row in d['Simulasi Diagnosis'][4:]:
    if not row or not any(row): continue
    no = row[0] if len(row) > 0 else ''
    if not no or not str(no).isdigit(): continue
    simulasi_list.append({
        'no': int(no),
        'usia_kehamilan': row[1] if len(row) > 1 else '',
        'sistolik': row[2] if len(row) > 2 else '',
        'diastolik': row[3] if len(row) > 3 else '',
        'proteinuria': row[4] if len(row) > 4 else '',
        'sakit_kepala': row[5] if len(row) > 5 else '',
        'gangguan_penglihatan': row[6] if len(row) > 6 else '',
        'nyeri_ulu_hati': row[7] if len(row) > 7 else '',
        'trombosit_rendah': row[8] if len(row) > 8 else '',
        'fungsi_hati': row[9] if len(row) > 9 else '',
        'fungsi_ginjal': row[10] if len(row) > 10 else '',
        'riwayat_hipertensi': row[11] if len(row) > 11 else '',
        'kejang': row[12] if len(row) > 12 else '',
        'hasil_diagnosis': row[13] if len(row) > 13 else '',
        'level_risiko': row[14] if len(row) > 14 else '',
        'rekomendasi': row[15] if len(row) > 15 else ''
    })

structured_kb = {
    'kriteria': kriteria_list,
    'alternatif': alternatif_list,
    'rules': rule_list,
    'simulasi': simulasi_list
}

with open('structured_kb.json', 'w', encoding='utf-8') as out:
    json.dump(structured_kb, out, ensure_ascii=False, indent=2)

print('Success')
