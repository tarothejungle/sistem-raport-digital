# AGENTS.md — Sistem Raport Digital

> Auto-generated via `/init` dari history percakapan + pola repo ini.
> Tujuan: bikin agent langsung paham user (vibe coder), stack, dan cara kerja yang diharapkan.

## 1. Siapa User-nya (Persona Vibe Coder)

- **Bahasa utama: Bahasa Indonesia.** Balas selalu Bahasa Indonesia kecuali diminta lain.
- **Vibe coder, bukan hardcore coder:** prompt pendek, iteratif, visual-first.
  - Contoh gaya: "rapihkan search bar...", "perbaiki bug ini...", "last but not least...", "lanjutin progressnya".
- **Ekspektasi otonomi tinggi:** kalau user bilang "lanjutin", lanjutkan tanpa banyak tanya: selesaikan validasi, cleanup, test, build.
- **Fokus hasil terlihat:** wajib buktikan dengan browser (Chrome DevTools), screenshot/bounding-box, bukan cuma klaim kode.
- **Alur kerja berantai:** satu sesi bisa lompat search-bar -> import Excel -> rapor PDF -> mobile drawer. Jaga konteks antar tugas.
- **Tidak suka sisa:** akun QA sementara, server lokal, file `.tmp-*` harus dibersihkan setelah validasi.
- **Tidak suka perubahan destruktif:** jangan ubah password/data real, jangan commit/push/PR kecuali diminta eksplisit.

## 2. Project Snapshot

- **App:** Sistem Rapor Digital — Laravel + Filament akademik sekolah.
- **Stack:**
  - PHP 8.3, Laravel 13.31, Filament v5, Livewire v4
  - OpenSpout (template/import XLSX nilai), Barryvdh DomPDF (CetakRapor PDF/ZIP)
  - TailwindCSS v4 + Vite 8, `resources/css/app.css` -> `public/build`
  - Theme kustom ganda (harus diubah berpasangan):
    - `resources/css/filament/admin/raport-theme.css`
    - `public/css/app/raport-theme.css` (served via `AdminPanelProvider::assets()`)
  - DB lokal via Laragon (Windows, `D:\laragon\www\sistem-raport-digital`), serve `php artisan serve --host=127.0.0.1 --port=8000`
  - Auth Filament panel `admin`, roles: `admin|guru|siswa`, + Cloudflare Turnstile di login
- **Menu kunci:**
  - Absen Siswa (`App\Filament\Admin\Pages\AbsenSiswa`, slug `absen-siswa`) — simpan per kelas+tahun, dipakai semua mapel + rapor.
  - Input Nilai per Mapel + Template/Import Nilai Siswa (`NilaiSpreadsheetService`)
  - Cetak Rapor Siswa (`RaportPdfService` + `resources/views/pdf/CetakRapor.blade.php`)
  - Mobile bottom nav: `resources/views/components/mobile-bottom-nav.blade.php` — pola drawer tingkat 2 (level 1: grup, level 2: menu, level 3: aksi)

## 3. How to Work Here (Wajib)

### 3.1 Bahasa & Komunikasi
- Ringkas, faktual, tanpa superlatif/emoji. Referensi kode pakai `path:line`.
- Kalau temuan kontradiksi klaim sebelumnya, nyatakan jelas dan percaya bukti (test/DOM) bukan spekulasi.
- Verifikasi via eksekusi: `php artisan test`, `npm run build`, ukur DOM, jangan mental-math.

### 3.2 Definition of Done tiap tugas UI/bug
1. `php artisan test --filter=<TestTerkait>` hijau
2. `php artisan test` penuh hijau kalau sentuh service/shared
3. `vendor\bin\pint --test <file>` lulus
4. `git diff --check` bersih
5. `npm run build` lulus kalau sentuh CSS/JS/views yang dipengaruhi Vite/theme
6. Validasi Chrome DevTools lintas viewport: `1440x900` (desktop), `768x1024` (tablet), `375x812` (mobile touch)
   - Cek: posisi (search kiri, aksi kanan desktop), tidak ada `document.scrollWidth > innerWidth`, tidak ada error console kritis (abaikan noise Turnstile/analytics), screenshot mobile bila ubah mobile nav.
7. Cleanup: hapus `.tmp-*`, hapus user QA sementara, stop `artisan serve`, pastikan `git status --short` hanya file implementasi.

### 3.3 Pola CSS yang sudah disepakati (jangan rusak)
- Search bar Absen Siswa desktop di pojok kiri tabel:
  - `.raport-absen-siswa-table .fi-ta-header-toolbar { display:grid; grid-template-columns: minmax(0,18rem) minmax(0,1fr); }`
  - search `grid-column:1`, actions `grid-column:2; justify-self:end`
  - Jangan pakai `div:last-child` — rapuh terhadap DOM Filament. Pakai `> :has(.fi-ta-search-field)`.
  - Mobile `<=768px`: grid `1fr auto`, search tetap kiri. `<=640px`: stack 1 kolom, search + actions full-width.
- Sembunyikan aksi tabel di mobile tapi tampil di desktop:
  - Tambah `'class' => 'raport-desktop-only-action'` (CSS: `display:none !important` di `max-width:767px`)
  - Contoh: `Isi Absen Siswa`, `Template/Import Nilai`, `Tambah Nilai`
- Tabel mobile:
  - `raport-mobile-full-search-table` untuk search full-width, `raport-mobile-grouped-table` untuk grouping, `raport-mobile-compact-table` untuk densitas.
  - `.fi-ta-content { overflow-x:auto }`, `.fi-ta-table { min-width:48rem }` — tabel boleh scroll horizontal, page tidak boleh overflow.
- Modal absensi: `raport-absen-siswa-modal` + `html.raport-absen-siswa-focus` sembunyikan sidebar/topbar/nav mobile (`html.raport-absen-siswa-focus .raport-mobile-nav { display:none !important }`).
- Selalu edit kedua file theme (`resources/...` + `public/...`) dengan isi identik.

### 3.4 Pola Mobile Drawer Tingkat 2 (standar)
- Referensi: `Master Data -> Mata Pelajaran` = `List + Tambah`.
- Untuk Absen Siswa: `Akademik -> Absen Siswa` harus non-`direct`, actions:
  ```php
  ['label'=>'List Absen Siswa','icon'=>'heroicon-o-list-bullet','url'=>AbsenSiswa::getUrl()],
  ['label'=>'Isi Absen Siswa','icon'=>'heroicon-o-pencil-square','url'=>AbsenSiswa::getUrl(),'trigger'=>'raport-isi-absensi-action'],
  ```
- Action tabel diberi `'id'=>'raport-isi-absensi-action'`.
- Drawer trigger via JS: `document.getElementById(trigger).click()` hanya saat di halaman Absen Siswa (jaga state Livewire tahun/kelas). Jangan pakai `?tableAction=isiAbsensi` via URL — itu reload dan hilangkan pilihan kelas (pelajaran dari bug QA).
- Dari halaman lain, `Isi Absen Siswa` arahkan ke list dulu, user pilih kelas dulu baru trigger.

### 3.5 Pola Import Nilai (pelajaran bug header)
- Header wajib `['NISN','Nama Siswa','Nilai','Saran']` di baris 9 (`HEADER_ROW=9`), tapi toleransi trailing empty cells (Excel/OpenSpout padding).
  - Implementasi: trim + `while(end==='' ) array_pop` sebelum compare. Kolom tambahan non-kosong tetap tolak dengan pesan `Header file berubah...`.
- Jangan longgarkan validasi metadata (mapel/kelas/tahun), NISN, duplikat, jumlah baris, atau keamanan ZIP (max entries/size, ratio zip-bomb).
- Regression test wajib untuk round-trip: download template -> baca via OpenSpout -> `importRows` harus lulus.

### 3.6 Pola Rapor + Absensi (pelajaran integrasi)
- `RaportPdfService::build()` harus return `absensi => ['sakit'=>int,'izin'=>int,'alpa'=>int]` via query `siswa_id+tahun_ajaran_id`, default `0`.
- `pdf/CetakRapor.blade.php` punya section `Ketidakhadiran` setelah Saran, sebelum signature: `Sakit/Izin/Alpa X hari`.
- Test harus pastikan tahun lain tidak bocor dan kosong = 0.

## 4. Commands (Windows PowerShell 5.1)

```powershell
# test cepat
php artisan test --filter=NilaiSpreadsheetServiceTest
php artisan test --filter=AbsensiSiswaServiceTest
php artisan test --filter=MobileBottomNavigationTest
php artisan test --filter=RaportPdfAttendanceTest
php artisan test --filter=BulkDownloadRaporTest

# full
php artisan test
vendor\bin\pint --test app/Services/NilaiSpreadsheetService.php tests/Feature/NilaiSpreadsheetServiceTest.php
npm run build
git diff --check
git status --short
```

- Serve hanya untuk QA browser sementara, lalu kill:
```powershell
$proc = Start-Process -FilePath "php" -ArgumentList "artisan","serve","--host=127.0.0.1","--port=8000" -PassThru; $proc.Id
# ... QA ...
Get-NetTCPConnection -LocalPort 8000 -State Listen | % { Stop-Process -Id $_.OwningProcess }
```

## 5. QA Browser (Chrome DevTools) — Standar User Ini

- User selalu minta "pastikan validitas hasilnya menggunakan chrome-devtools".
- Setup: `navigate_page` ke `http://127.0.0.1:8000/admin/...`, login QA admin sementara bila perlu, `emulate viewport`, `take_snapshot`, `evaluate_script` untuk bounding-box, `take_screenshot` mobile, `list_console_messages` (error/warn).
- Jangan pakai kredensial real. Buat user QA sementara via tinker (`qa.mobile.nav@raport.local`), lalu `delete()` + verifikasi `count()==0`.
- Jangan ubah DB prod untuk QA visual. Untuk data, pakai seeder/test atau data existing read-only.
- Ukur, bukan kira:
  - Desktop: `searchAtLeft && sameRow && actionAtRight && !bodyOverflow`
  - Mobile: `stacked && searchFullWidth && actionFullWidth && contained && !bodyOverflow`
- Kalau login gagal karena password unknown/Turnstile, fallback ke fixture DOM representatif dengan stylesheet produksi yang sama — nyatakan eksplisit di laporan.

## 6. Testing Conventions

- `RefreshDatabase`, helper `academicData()`/`templateRows()`/`xlsxRows()` di test Feature.
- Untuk UI nav: `assertSee`, `assertSeeInOrder`, `assertDontSee`, plus cek isi file (`file_get_contents`) untuk kontrak CSS/Blade/ID action.
- Untuk PDF: test `RaportPdfService::build` + `view('pdf.CetakRapor')->render()` + `BulkDownloadRaporTest` (ZIP berisi PDF).
- Selalu tambah regression test sebelum fix bila ada seam yang benar (contoh: `test_import_accepts_unchanged_header_with_trailing_empty_spreadsheet_cells`, `test_report_format_contains_attendance...`).

## 7. Do / Don't

**Do:**
- Baca file langsung sebelum edit, edit minimal, jaga style existing.
- Sinkronkan `resources/.../raport-theme.css` dan `public/.../raport-theme.css`.
- Pertahankan pola `direct` vs grouped di `mobile-bottom-nav.blade.php`.
- Redact secret/PII di log/screenshot.

**Don't:**
- Jangan tebak URL, jangan ubah `AbsenSiswa::canAccess`/otorisasi tanpa diminta.
- Jangan commit/amend/push, jangan ubah git config/hooks, jangan force-push.
- Jangan install Python/package manager via agent; di Windows pakai `python` bukan `python3` untuk skill scripts.
- Jangan biarkan `[DEBUG-...]`, prototipe throwaway, atau file `.tmp-*` tertinggal.

## 8. Konteks Percakapan Terakhir (agar nyambung)

1. Search bar Absen Siswa desktop dipindah ke pojok kiri tabel + responsif (grid + `:has(.fi-ta-search-field)`).
2. Bug `Import nilai gagal / Header file berubah` — root cause trailing empty cell, fix normalisasi + 2 regression test.
3. Hasil Absen Siswa (`sakit/izin/alpa`) masuk ke Cetak Rapor (service + Blade + test tahun-isolasi + default 0).
4. Mobile Absen Siswa: tombol pindah dari tabel ke drawer tingkat 2 (`List + Isi`), trigger Livewire via ID, modal-safe, desktop tetap ada tombol.
5. Semua di atas lolos `php artisan test` (86 test), `npm run build`, Pint, `git diff --check` pada saatnya.

---
*Kalau ragu antara kecepatan vs kebenaran: pilih kebenaran + bukti test/browser. User ini vibe coder tapi sangat menghargai validasi konkret.*
