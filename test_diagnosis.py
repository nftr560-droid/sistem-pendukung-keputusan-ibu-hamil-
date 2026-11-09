import json

with open('structured_kb.json', 'r', encoding='utf-8') as f:
    d = json.load(f)

def diagnose(row):
    ga = int(row['usia_kehamilan']) if str(row['usia_kehamilan']).isdigit() else 0
    sys = int(row['sistolik']) if str(row['sistolik']).isdigit() else 0
    dia = int(row['diastolik']) if str(row['diastolik']).isdigit() else 0
    prot = str(row['proteinuria']).strip()
    has_prot = prot not in ['Negatif', '-', '0', '']
    severe_sym = any(row.get(k) == 'Ya' for k in ['sakit_kepala', 'gangguan_penglihatan', 'nyeri_ulu_hati', 'trombosit_rendah', 'fungsi_hati', 'fungsi_ginjal'])
    prior_ht = (row['riwayat_hipertensi'] == 'Ya')
    seizure = (row['kejang'] == 'Ya')
    
    is_ht = (sys >= 140 or dia >= 90)
    is_severe_bp = (sys >= 160 or dia >= 110)
    
    # Exact reproduction of Excel formula:
    # IF(AND(M="Ya",Q="Ya",OR(C>=160,D>=110)),"Eklampsia",
    # IF(AND(B>=20,C>=140,D>=90,Q="Ya",OR(C>=160,D>=110),R="Ya"),"Preeklampsia Berat",
    # IF(AND(L="Ya",B>=20,Q="Ya"),"Superimposed Preeclampsia",
    # IF(AND(B>=20,C>=140,D>=90,Q="Ya"),"Preeklampsia Ringan",
    # IF(AND(B<20,C>=140,D>=90),"Hipertensi Kronis",
    # IF(AND(B>=20,C>=140,D>=90,Q="Tidak",L="Tidak"),"Hipertensi Gestasional","Normal / tidak hipertensi"))))))
    if seizure and has_prot and is_severe_bp:
        return 'Eklampsia'
    elif ga >= 20 and is_ht and has_prot and is_severe_bp and severe_sym:
        return 'Preeklampsia Berat'
    elif prior_ht and ga >= 20 and has_prot:
        return 'Superimposed Preeclampsia'
    elif ga >= 20 and is_ht and has_prot:
        return 'Preeklampsia Ringan'
    elif ga < 20 and is_ht:
        return 'Hipertensi Kronis'
    elif ga >= 20 and is_ht and not has_prot and not prior_ht:
        return 'Hipertensi Gestasional'
    else:
        return 'Normal / tidak hipertensi'

for sim in d['simulasi']:
    expected = sim['hasil_diagnosis']
    actual = diagnose(sim)
    print(f"Case {sim['no']}: Expected='{expected}' | Actual='{actual}'")
