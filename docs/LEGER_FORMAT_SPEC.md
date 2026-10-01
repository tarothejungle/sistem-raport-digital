# Spesifikasi Format Leger Nilai PTS

Dokumen ini menjadi acuan format Leger Nilai PTS MIS Lantaburo.

## 1. Sumber Acuan

| Prioritas | Sumber | Fungsi |
| ---: | --- | --- |
| 1 | `docs/Leger Nilai PTS.pdf` | Format tabel, identitas dokumen, urutan kolom, dan urutan mata pelajaran terbaru |
| 2 | `docs/Leger Nilai PTS.md` | Hasil konversi MarkItDown untuk struktur dan contoh data |
| 3 | `docs/Raport PTS.pdf` | Konteks nilai PTS dan data mata pelajaran |

Format lama pada `docs/leger nilai PTS MI lantaburo 2022-2023.xlsx` tidak lagi
menjadi rujukan keluaran Leger.

## 2. Identitas Dokumen

```text
LEGER KELAS {kelas}
MADRASAH IBTIDAIYAH LANTABURO
TAHUN AJARAN {tahun_ajaran}
```

Prefix `KELAS` hanya boleh muncul satu kali.

## 3. Struktur Tabel

Leger terbaru memiliki 17 kolom:

| No. Kolom | Kolom |
| ---: | --- |
| 1 | No |
| 2 | Nama |
| 3 | NISN |
| 4 | AH |
| 5 | AA |
| 6 | Fiqih |
| 7 | PPKN |
| 8 | BIn |
| 9 | BA |
| 10 | SB |
| 11 | MTK |
| 12 | PJOK |
| 13 | Tahfidz |
| 14 | BIng |
| 15 | Jumlah |
| 16 | Ranking |
| 17 | Saran-saran |

Urutan kode internal:

```text
QH, AA, FQ, PPKn, BIN, BAR, SBK, MTK, PJOK, TAHFIDZ, BING
```

Label header Leger tidak mengubah nama lengkap mata pelajaran pada database:

```text
AH, AA, Fiqih, PPKN, BIn, BA, SB, MTK, PJOK, Tahfidz, BIng
```

Perubahan dari format sebelumnya:

- NISN ditampilkan setelah nama siswa.
- Saran-saran ditampilkan setelah ranking.
- Seni Budaya tampil sebelum Matematika.
- Kolom jenis kelamin, rata-rata, perilaku, dan absensi tidak ditampilkan.
- Baris KKM tidak ditampilkan.
- Absensi tetap disimpan untuk fitur lain, tetapi bukan bagian Leger terbaru.

## 4. Sumber Data

| Kolom | Sumber |
| --- | --- |
| No | Urutan nama siswa A-Z dalam kelas dan tahun ajaran terpilih |
| Nama | `siswa.nama_lengkap` |
| NISN | `siswa.nisn` |
| Nilai mapel | `nilai.nilai_angka` final untuk jadwal kelas dan tahun ajaran |
| Jumlah | Total 11 nilai mapel |
| Ranking | Peringkat berdasarkan jumlah |
| Saran-saran | `catatan_rapor.saran` untuk siswa dan tahun ajaran terpilih |

## 5. Aturan Kalkulasi

1. `Jumlah` dihitung jika seluruh 11 nilai mapel tersedia.
2. Jika satu nilai belum tersedia, `Jumlah` dan `Ranking` ditampilkan kosong atau `-`.
3. Ranking diurutkan menurun berdasarkan `Jumlah`.
4. Nilai jumlah sama memakai competition ranking, contoh `1, 2, 2, 4`.
5. Saran tidak memengaruhi jumlah atau ranking.

## 6. Kontrak Data Baris

```text
LegerRow:
  no: integer >= 1
  siswa_id: integer|string
  nama_siswa: string
  nisn: string
  mata_pelajaran:
    QH|AA|FQ|PPKn|BIN|BAR|SBK|MTK|PJOK|TAHFIDZ|BING:
      kkm: number|null
      nilai_angka: number|null
      predikat: "A"|"B"|"C"|"D"|null
      deskripsi: string|null
      guru: string|null
  jumlah: number|null
  ranking: integer|null
  saran: string|null
```

## 7. Keluaran

Format sama wajib diterapkan pada:

- Tampilan web Leger Nilai PTS.
- Unduh Excel `.xlsx`.
- Unduh PDF.

Styling boleh berbeda sesuai kemampuan format, tetapi identitas, urutan kolom,
urutan mata pelajaran, dan isi data harus sama.
