# Login Redesign Plan: Road to Knowledge

> Lokasi utama: `resources/views/filament/admin/pages/auth/login.blade.php`, `app/Filament/Admin/Pages/Auth/Login.php`, dan `resources/css/filament/admin/raport-theme.css`
> Stack: Laravel 13, Filament 5, Livewire 4, Cloudflare Turnstile
> Referensi: desain dua panel dengan ilustrasi akademik putih dan panel form navy
> Keputusan teks: mengikuti gambar (`Sign In`, `Username`, `Password`, dan `Login Ke Dalam Sistem`)
> Asset: gunakan resource lokal dari `public/logo`

## 1. Tujuan

Bangun ulang halaman login agar mengikuti komposisi referensi tanpa menyalin CSS absolut hasil export desain. Implementasi harus responsif, tetap memakai komponen form Filament, dan tidak mengubah alur autentikasi.

Karakter visual utama:

- Background halaman periwinkle lembut.
- Panel ilustrasi putih di kiri.
- Panel form navy di kanan.
- Panel putih berada di depan dan sedikit overlap dengan panel navy.
- Radius besar sekitar `28-30px`.
- Ilustrasi akademik menjadi elemen visual utama.
- Logo simbol tampil tepat di atas heading `Sign In`.
- Theme switcher berada di sudut kanan atas panel navy.
- Form ringkas, terpusat, dan memiliki kontras tinggi.

## 2. Analisis Referensi

Referensi memiliki rasio kanvas sekitar `1.79:1` dan komposisi berikut:

```text
┌──────────────────────────────────────────────────────────────┐
│ ┌────────────────────────┐ ┌───────────────────────────────┐ │
│ │                        │ │                Theme switcher │ │
│ │                        │ │                               │ │
│ │                        │ │          Symbol logo          │ │
│ │  Road to Knowledge     │ │            Sign In            │ │
│ │  illustration          │ │                               │ │
│ │                        │ │  Username                     │ │
│ │                        │ │  [ icon  input              ] │ │
│ │                        │ │                               │ │
│ │                        │ │  Password                     │ │
│ │                        │ │  [ icon  input       eye    ] │ │
│ │                        │ │                               │ │
│ │                        │ │  □ Ingat Saya   Lupa Sandi?   │ │
│ │                        │ │  [ Cloudflare Turnstile     ] │ │
│ │                        │ │  [ Login Ke Dalam Sistem    ] │ │
│ └────────────────────────┘ └───────────────────────────────┘ │
└──────────────────────────────────────────────────────────────┘
```

Proporsi target desktop:

| Bagian | Proporsi/ukuran target |
|---|---:|
| Shell | Maksimum `1120px` |
| Tinggi shell | `580-620px` |
| Panel ilustrasi | Sekitar `45%` shell |
| Panel form | Mulai sekitar `36%` dari kiri |
| Overlap | Sekitar `9%` shell |
| Form | Maksimum `396px` |
| Radius | `28-30px` |

CSS absolut dari export tidak dipakai karena bergantung pada koordinat kanvas `960px`, tidak responsif, dan tidak sesuai DOM Filament 5.

## 3. Kondisi Source Saat Ini

Halaman login sekarang sudah memiliki wrapper stabil:

- `.srd-login-shell`
- `.srd-login-panel--left`
- `.srd-login-panel--right`
- `.srd-login-form`
- `.srd-login-field--username`
- `.srd-login-field--password`
- `.srd-login-options`
- `.srd-login-turnstile`
- `.srd-login-submit`

DOM form aktual Filament 5 memakai `.fi-sc-form > .fi-sc.fi-grid`, bukan `.fi-fo-component-ctn` sebagai pemilik utama layout.

CSS login saat ini tersebar dalam dua lapisan:

- Blok besar mulai marker `Final Revision Plan - Login Page` sekitar baris `4718`.
- Blok koreksi `Login layout fix` sekitar baris `5486`.

Strategi implementasi:

- Hapus kedua blok login lama.
- Ganti dengan satu blok final yang terisolasi.
- Jangan menambahkan override ketiga di akhir file.
- Batasi selector komponen Filament di bawah `.srd-login-form`.

## 4. Batas Perubahan

Perubahan hanya menyentuh presentasi dan label UI.

Tetap dipertahankan tanpa perubahan perilaku:

- `LoginIdentifierResolver::resolve()`.
- `User::canAccessPanel()`.
- Rate limit lima percobaan.
- Reset Turnstile setelah autentikasi gagal.
- Password hashing check.
- Remember me.
- Session regeneration.
- Login response Filament.
- Password reset URL.
- Validasi dan error message Filament.

Tidak ada perubahan pada:

- Database.
- Migration.
- Route.
- Authorization policy.
- Kontrak autentikasi.
- Konfigurasi secret Turnstile.

## 5. Asset

Gunakan asset lokal berikut:

```text
public/logo/undraw_road-to-knowledge.svg
public/logo/logo-tanpa-teks-light.svg
public/logo/logo-tanpa-teks-dark.svg
```

Aturan asset:

- Jangan menyalin SVG panjang dari export ke Blade.
- Tambahkan cache-busting memakai `filemtime()`.
- Ilustrasi memakai `object-fit: contain`.
- Lebar ilustrasi desktop sekitar `78-84%` panel kiri.
- Logo simbol sekitar `52-58px` lebar.
- Ilustrasi bersifat dekoratif dan memakai `alt=""`.
- Logo utama memiliki label aksesibel `Sistem Rapor Digital`.
- Jangan mengubah SVG sumber kecuali screenshot membuktikan kontras tidak sesuai.

## 6. Sistem Warna

Tambahkan token khusus login agar dashboard Filament tidak ikut berubah:

```css
:root {
    --srd-login-page: #93a7f2;
    --srd-login-page-dark: #07113d;
    --srd-login-art-surface: #ffffff;
    --srd-login-art-surface-dark: #111a3a;
    --srd-login-panel: #001467;
    --srd-login-panel-dark: #000d4f;
    --srd-login-control: #ffffff;
    --srd-login-control-border: #0f172a;
    --srd-login-control-text: #0f172a;
    --srd-login-label: #ffffff;
    --srd-login-link: rgba(255, 255, 255, 0.88);
    --srd-login-focus: #60a5fa;
    --srd-login-shadow: 0 24px 60px rgba(0, 20, 103, 0.18);
}
```

Dark mode:

- Background halaman berubah ke navy sangat gelap.
- Panel ilustrasi berubah ke surface biru gelap.
- Panel form tetap navy sebagai identitas utama.
- Control tetap terang agar komposisi dan keterbacaan konsisten.
- Logo simbol berganti ke varian yang memiliki kontras benar.

## 7. Tipografi

Referensi menggunakan `Plus Jakarta Sans` bobot `400` dan `700`.

Rencana:

- Gunakan `Plus Jakarta Sans` hanya pada halaman login.
- Sediakan WOFF2 lokal bobot `400` dan `700`.
- Ambil dari font data yang disertakan pengguna.
- Simpan sebagai asset lokal, bukan request runtime ke Google Fonts.
- Gunakan `font-display: swap`, bukan `block`.
- Jangan mengganti font global `.fi-body`.
- Fallback ke `Inter Variable`, `Inter`, lalu `sans-serif`.

Scope font:

```css
.fi-simple-page:has(.srd-login-shell) {
    font-family: "Plus Jakarta Sans", "Inter Variable", Inter, sans-serif;
}
```

Skala tipografi:

| Elemen | Ukuran | Weight | Warna |
|---|---:|---:|---|
| Heading `Sign In` | `18-20px` | `700` | Putih |
| Label | `13-14px` | `700` | Putih |
| Input | `14px` | `400` | Slate gelap |
| Options/link | `11-12px` | `400-600` | Putih |
| Tombol | `12-14px` | `700` | Navy |

## 8. Struktur Blade

File: `resources/views/filament/admin/pages/auth/login.blade.php`

Struktur target:

```text
srd-login-shell
├── srd-login-panel srd-login-panel--art
│   └── srd-login-illustration
│       └── undraw_road-to-knowledge.svg
└── srd-login-panel srd-login-panel--form
    ├── srd-login-theme
    │   └── Filament theme switcher
    └── srd-login-form
        ├── srd-login-form-brand
        │   ├── logo-tanpa-teks-light.svg
        │   └── logo-tanpa-teks-dark.svg
        ├── srd-login-form-heading
        │   └── Sign In
        └── srd-login-fields
            └── Filament form content
```

Perubahan:

- Hapus brand logo penuh dari panel kiri.
- Hapus eyebrow, headline, deskripsi, dan role notice lama.
- Tambahkan ilustrasi `undraw_road-to-knowledge.svg` pada panel kiri.
- Pindahkan theme switcher ke sudut kanan atas panel navy.
- Tambahkan logo simbol light/dark di atas heading.
- Heading `Sign In` berada di tengah.
- Pertahankan `{{ $this->content }}` agar schema Filament tetap menjadi sumber form.
- Pertahankan script label eye button dan loading state.
- Sederhanakan script hanya jika ditemukan binding ganda saat verifikasi Livewire.

Semantik:

- Panel ilustrasi memakai `aria-hidden="true"` bila tidak membawa informasi.
- Panel form memakai `aria-label="Form masuk"`.
- Logo light memiliki alt `Sistem Rapor Digital`.
- Logo dark dekoratif untuk screen reader agar label tidak dibaca dua kali.

## 9. Schema Form

File: `app/Filament/Admin/Pages/Auth/Login.php`

Perubahan label:

| Saat ini | Target |
|---|---|
| `Nama pengguna` | `Username` |
| `Kata sandi` | `Password` |
| Heading `Masuk ke Sistem` | `Sign In` |
| Tombol `Masuk ke Sistem` | `Login Ke Dalam Sistem` |
| `Ingat saya` | `Ingat Saya` |
| `Lupa kata sandi?` | `Lupa Kata Sandi?` |

Aturan:

- `getHeading()` mengembalikan `Sign In`.
- `getSubheading()` mengembalikan `null` karena referensi tidak memiliki subheading.
- `getTitle()` tetap deskriptif untuk judul browser.
- Prefix icon username tetap dipakai.
- Prefix icon password tetap dipakai.
- Password reveal tetap dipakai.
- `autocomplete`, `autofocus`, `required`, dan `statePath` tetap dipakai.
- Wrapper class username/password tetap dipakai.
- `authenticate()` dan `throttleKey()` tidak diubah.

File `resources/views/filament/admin/components/login-reset-link.blade.php` diperbarui hanya untuk kapitalisasi teks.

## 10. Desktop Layout

Breakpoint desktop: `> 768px`.

Aturan shell:

- `position: relative`.
- Lebar maksimum `1120px`.
- Tinggi target `580-620px`.
- Background shell navy.
- `overflow: hidden`.
- Radius luar `30px`.
- Shadow lembut, tidak memakai glow biru.

Aturan panel ilustrasi:

- Lebar sekitar `45%`.
- Tinggi penuh.
- Surface putih.
- Radius penuh `30px`.
- `z-index: 2` agar tampak berada di depan.
- Ilustrasi terpusat horizontal dan vertikal.

Aturan panel form:

- Berada dari sekitar `36%` hingga sisi kanan shell.
- `z-index: 1`.
- Background `#001467`.
- Padding kiri memperhitungkan overlap panel ilustrasi.
- Form tetap terlihat terpusat pada area navy yang nyata.
- Theme switcher berjarak `24px` dari atas dan kanan.

Konsep layout:

```text
Shell: background navy, overflow hidden
Left art: absolute/relative width 45%, z-index 2
Right form: absolute inset 0 0 0 36%, z-index 1
Right inner: padding-left cukup agar form tidak tertutup left panel
```

Tidak boleh:

- Memakai koordinat absolut hasil export seperti `left: 516px`.
- Mengunci layout ke kanvas `960px`.
- Menggunakan `transform: scale()` untuk memperkecil komponen interaktif.
- Memakai `justify-between` atau `mt-auto` untuk mendorong tombol ke bawah.

## 11. Form Styling

Form:

- Lebar `100%`.
- Maksimum `396px`.
- Terpusat pada panel navy.
- Heading dan logo terpusat.
- Field dan options rata kiri.

Input:

| Properti | Target |
|---|---|
| Tinggi | Minimum `44px` |
| Radius | `12-15px` |
| Background | `#ffffff` |
| Border | `1px solid #0f172a` |
| Text | `#0f172a` |
| Icon | `#334155` |
| Focus | Border dan ring biru terlihat |
| Width | `100%` |

Catatan:

- Referensi memakai tinggi sekitar `37px`, tetapi implementasi memakai minimum `44px` untuk aksesibilitas.
- Prefix dan suffix tanpa separator vertikal.
- Eye button minimum `40px`.
- Autofill tetap putih dengan teks gelap.
- Placeholder harus terbaca tetapi lebih redup dari nilai input.
- Error validation tampil tepat di bawah field dan terbaca pada background navy.

## 12. Vertical Rhythm

Satu selector menjadi pemilik setiap jarak. Jangan mencampur `gap` parent dengan margin child untuk jarak yang sama.

Target desktop:

| Area | Jarak |
|---|---:|
| Logo ke heading | `8px` |
| Heading ke username | `20-24px` |
| Label ke input | `6px` |
| Username ke password | `12-14px` |
| Password ke options | `10-12px` |
| Options ke Turnstile | `12-14px` |
| Turnstile ke tombol | `12-14px` |

Gunakan wrapper aktual:

- `.srd-login-field--username`
- `.srd-login-field--password`
- `.srd-login-options`
- `.srd-login-turnstile`
- `.fi-sc-form > .fi-sc.fi-grid:nth-child(2)` untuk action row bila struktur render tetap sama.

## 13. Options Row

Aturan:

- Checkbox `16-18px`.
- Label `Ingat Saya`.
- Link `Lupa Kata Sandi?`.
- Gunakan `grid-template-columns: minmax(0, 1fr) auto`.
- Satu baseline visual.
- Link putih dengan hover underline.
- Keyboard focus ring terlihat.
- Di bawah `360px`, row boleh wrap.
- Seluruh label checkbox tetap dapat diklik.

## 14. Cloudflare Turnstile

Pertahankan komponen asli:

```php
$this->getTurnstileFormComponent('login')
    ->size('flexible')
    ->theme('auto')
    ->language('id');
```

Aturan:

- Jangan menyembunyikan iframe, test banner, error, atau branding Cloudflare.
- Jangan menggunakan `transform: scale()`.
- Wrapper boleh memakai radius `12-15px`.
- Tinggi mengikuti iframe Cloudflare, bukan placeholder `37px` pada mockup.
- Turnstile harus `width: 100%` dan tidak terpotong.
- Local test banner tetap diperbolehkan.
- Production wajib memakai production site key dan secret key.

## 15. Submit Button

Aturan:

- Teks `Login Ke Dalam Sistem`.
- Tinggi minimum `44px`.
- Radius `12-15px`.
- Background putih.
- Teks navy.
- Border gelap tipis seperti input.
- Hover memakai putih kebiruan ringan.
- Focus ring terlihat di atas navy.
- Loading tetap `Memproses...`.
- Disabled state tetap jelas.
- Hapus shadow biru CTA lama.
- Tidak memakai active translation.

## 16. Theme Switcher

Tetap gunakan:

```blade
<x-filament-panels::theme-switcher />
```

Aturan:

- Tempatkan di sudut kanan atas panel navy.
- Jangan menampilkan string literal `$tema_switcher`.
- Background transparan.
- Icon putih atau biru muda.
- Active state memakai surface putih transparan.
- Hit area minimum `40px`.
- Tooltip dan `aria-label` bawaan Filament tetap berfungsi.
- Light/dark/system tetap tersedia.

## 17. Mobile Layout

Breakpoint: `<= 768px`.

Aturan:

- Sembunyikan panel ilustrasi.
- Hapus overlap.
- Panel navy menjadi satu kartu.
- Lebar `calc(100vw - 24px)`.
- Padding horizontal `20px`.
- Radius `22-24px`.
- Theme switcher tetap di kanan atas.
- Logo simbol dan heading tetap center.
- Form full width.
- Scroll vertikal natural.
- Turnstile tidak terpotong.
- Tinggi panel tidak dikunci ke `100vh`.
- Background periwinkle tetap terlihat sebagai frame.
- Dark mode memakai background navy sangat gelap.

Target viewport:

```text
375x667
390x844
430x932
```

Pada viewport sangat pendek:

- Kartu boleh scroll bersama halaman.
- Form tidak boleh dipaksa center vertikal bila menyebabkan bagian atas/bawah terpotong.
- Padding vertikal boleh dikurangi, tetapi hit area control tidak boleh di bawah `40px`.

## 18. CSS Cleanup

File: `resources/css/filament/admin/raport-theme.css`

Tindakan:

1. Hapus blok login lama mulai marker `Final Revision Plan - Login Page`.
2. Hapus blok `Login layout fix`.
3. Tambahkan satu blok final `Login Redesign - Road to Knowledge`.
4. Pertahankan namespace `.srd-login-*`.
5. Hindari selector global seperti `.fi-btn` tanpa scope login.
6. Gunakan DOM aktual `.fi-sc-form > .fi-sc.fi-grid`.
7. Gunakan satu pemilik spacing untuk setiap jarak.
8. Pertahankan reduced motion dan focus visibility.
9. Jangan menambah patch CSS lain setelah blok final.

## 19. Distribusi CSS

File sumber:

```text
resources/css/filament/admin/raport-theme.css
```

File publik:

```text
public/css/app/raport-theme.css
```

Aturan:

- Sinkronkan source ke public setelah perubahan.
- Pertahankan `filemtime` versioning di `AdminPanelProvider`.
- Pastikan hanya satu link theme muncul pada HTML.
- Jangan mengembalikan inline `STYLES_AFTER`.
- Verifikasi SHA256 source dan public sama.

## 20. Urutan Implementasi

1. Tambahkan asset font lokal `Plus Jakarta Sans` bobot `400` dan `700`.
2. Ubah label form tanpa menyentuh autentikasi.
3. Ubah Blade menjadi panel ilustrasi dan panel form.
4. Ganti seluruh blok CSS login lama dengan satu blok final.
5. Sinkronkan CSS source ke public.
6. Clear dan rebuild cache Laravel.
7. Verifikasi route, PHP syntax, Blade render, dan asset URL.
8. Ambil screenshot desktop light/dark.
9. Ambil screenshot mobile light/dark.
10. Koreksi proporsi, spacing, dan overflow berdasarkan screenshot.
11. Uji keyboard, focus, validation, loading, autofill, dan Turnstile.
12. Jalankan impact analysis dan re-index MCP.

## 21. Verifikasi Teknis

Pemeriksaan source:

```powershell
php -l app\Filament\Admin\Pages\Auth\Login.php
php -l app\Providers\Filament\AdminPanelProvider.php
php artisan route:list --path=admin/login
php artisan view:clear
php artisan config:clear
php artisan view:cache
git diff --check
```

Pemeriksaan render HTML:

- Hanya satu link `raport-theme.css?v=[filemtime]`.
- Tidak ada inline copy theme.
- Class username/password muncul.
- URL ilustrasi dan logo mengandung version query.
- Tidak ada SVG panjang hasil export yang tertanam di HTML.

Pemeriksaan CSS:

- Source dan public memiliki SHA256 sama.
- Marker blok login lama sudah hilang.
- Hanya satu marker login final.
- Tidak ada `transform: scale()` pada Turnstile atau theme switcher.
- Tidak ada selector login yang tidak ter-scope dan mengubah dashboard.

## 22. Verifikasi Visual

Desktop:

```text
1366x768 light
1440x900 light
1920x1080 light
1366x768 dark
1440x900 dark
1920x1080 dark
```

Mobile:

```text
375x667 light/dark
390x844 light/dark
430x932 light/dark
```

Checklist visual:

- Panel putih dan navy memiliki proporsi seperti referensi.
- Overlap terlihat natural, bukan seperti dua kartu terpisah.
- Radius panel konsisten.
- Ilustrasi tidak terpotong atau terlalu kecil.
- Logo simbol center di atas heading.
- Theme switcher tidak bertabrakan dengan form.
- Form tidak terlalu lebar atau terlalu rendah.
- Label, options, dan link terbaca di atas navy.
- Turnstile tidak keluar dari form.
- Tidak ada horizontal overflow.
- Dark mode tetap mempertahankan hierarki panel.

## 23. Verifikasi Perilaku dan Aksesibilitas

- Username autofocus.
- Password reveal memiliki label `Tampilkan kata sandi` dan `Sembunyikan kata sandi`.
- Remember me berfungsi.
- Reset password link dapat dibuka.
- Invalid credential message tampil dekat field.
- Rate-limit message tampil.
- Unauthorized panel message tampil.
- Turnstile reset setelah autentikasi gagal.
- Submit loading menjadi `Memproses...`.
- Submit memakai `aria-busy` saat loading.
- Theme switcher bertahan setelah refresh.
- Urutan tab logis.
- Focus indicator terlihat.
- Autofill Chrome tetap terbaca.
- Zoom browser `200%` tidak memotong form.
- `prefers-reduced-motion` dihormati.
- Kontras teks memenuhi kebutuhan form autentikasi.

## 24. Kolaborasi MCP

Sebelum edit:

- Gunakan `cbm-laragon_search_graph` untuk menemukan method login dan wrapper terkait.
- Gunakan `cbm-laragon_trace_path` pada `Login::authenticate()`.
- Gunakan `cbm-laragon_get_architecture` untuk memastikan scope panel admin.

Sesudah edit:

- Jalankan `cbm-laragon_detect_changes` untuk impact analysis.
- Re-index repository mode `fast`.
- Pastikan status index `ready`.
- Cari ulang `.srd-login-*` dan method login.
- Pastikan perubahan tetap presentation-only di luar label UI.
- Catat node/edge index akhir pada hasil implementasi.

Hasil analisis MCP awal:

- `Login::authenticate()` terhubung ke `LoginIdentifierResolver::resolve()`.
- `Login::authenticate()` terhubung ke `User::canAccessPanel()`.
- `Login::authenticate()` memanggil `dispatchTurnstileReset()` pada kegagalan.
- Perubahan desain tidak membutuhkan perubahan pada dependency tersebut.

## 25. Risiko dan Mitigasi

| Risiko | Mitigasi |
|---|---|
| CSS Filament mengalahkan style login | Scope selector ke `.srd-login-form` dan letakkan satu blok final |
| Layout overlap gagal pada viewport kecil | Hilangkan overlap pada breakpoint `768px` |
| Turnstile lebih tinggi dari mockup | Biarkan tinggi asli dan pusatkan komposisi berdasarkan render nyata |
| Font menambah request eksternal | Gunakan WOFF2 lokal |
| Logo dark/light salah kontras | Uji kedua mode melalui screenshot |
| CSS browser stale | Pertahankan versioning `filemtime` |
| Loading handler terikat ganda | Uji Livewire navigation dan pertahankan dataset guard |
| Error validation mengubah tinggi shell | Gunakan tinggi minimum, bukan tinggi tetap |
| Global dashboard ikut berubah | Jangan ubah font atau selector komponen secara global |

## 26. Definition of Done

- [ ] Desktop mengikuti komposisi referensi: white art panel, navy form panel, overlap, dan radius besar.
- [ ] `undraw_road-to-knowledge.svg` tampil proporsional.
- [ ] Logo simbol tampil di atas `Sign In`.
- [ ] Theme switcher berada di sudut kanan atas.
- [ ] Semua teks mengikuti pilihan gambar.
- [ ] Plus Jakarta Sans aktif hanya pada login.
- [ ] Form tetap memakai komponen Filament.
- [ ] Turnstile asli tetap terlihat dan operasional.
- [ ] Login, rate limit, session regeneration, dan authorization tidak berubah.
- [ ] CSS login hanya memiliki satu blok final.
- [ ] Light dan dark mode lolos pemeriksaan.
- [ ] Desktop dan mobile tidak overflow.
- [ ] Keyboard, focus, loading, autofill, dan validation berfungsi.
- [ ] CSS source/public sinkron.
- [ ] Route dan PHP syntax valid.
- [ ] `git diff --check` bersih.
- [ ] MCP impact analysis selesai.
- [ ] MCP index berstatus `ready`.
