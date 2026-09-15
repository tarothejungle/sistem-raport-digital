# Sistem Rapor Digital

Sistem Rapor Digital adalah aplikasi pengelolaan data akademik untuk madrasah dan sekolah. Aplikasi ini dibangun dengan Laravel 13 dan Filament 5, dengan tujuan sederhana: mengurus data siswa, penugasan guru, input nilai, kenaikan kelas, sampai penerbitan rapor PDF dari satu panel yang rapi.

Proyek ini dirancang untuk dipakai oleh tim sekolah dengan kemampuan teknis yang beragam. Administrator dapat mengelola data master, guru cukup fokus pada kelas dan mata pelajaran yang diampu, sedangkan siswa dapat melihat nilai ketika aksesnya dibuka.

## Fitur Utama

- **Data akademik terpusat**: tahun ajaran, kelas, mata pelajaran, guru, siswa, alumni, dan pengaturan identitas madrasah.
- **Penugasan pengajar**: guru dapat ditugaskan pada kombinasi mata pelajaran dan kelas untuk tahun ajaran tertentu.
- **Input nilai yang terstruktur**: nilai per mata pelajaran, perhitungan nilai akhir, indeks ketercapaian, dan deskripsi belajar.
- **Pengelolaan siswa**: penambahan manual, impor spreadsheet, pengaturan alumni, serta riwayat perpindahan kelas.
- **Kenaikan kelas dan kelulusan**: proses akhir periode disertai riwayat kelas agar data historis tetap jelas.
- **Penerbitan rapor**: pratinjau, unduh satu rapor, dan unduh massal dalam format PDF.
- **Kontrol akses nilai siswa**: administrator dapat mengatur kapan nilai boleh dilihat.
- **Autentikasi dan profil**: login username, reset kata sandi, avatar, ganti kata sandi, role-based access, dan Cloudflare Turnstile.
- **Antarmuka modern**: panel Filament responsif dengan dukungan light mode dan dark mode, termasuk halaman login bertema gamified.
- **Operasional harian**: dashboard ringkasan, database notification, dan halaman maintenance.

## Teknologi

- PHP 8.3 atau lebih baru
- Laravel 13
- Filament 5 dan Livewire 4
- MySQL 8+ atau SQLite
- Tailwind CSS 4, Vite 8, dan Dompdf
- Cloudflare Turnstile melalui paket lokal `packages/filament-turnstile`
- PHPUnit 12, Pint, Pail, dan PAO untuk pengembangan

## Prasyarat

Pastikan perangkat sudah memiliki:

- PHP 8.3+ dengan ekstensi umum Laravel (`pdo_mysql` atau `pdo_sqlite`, `mbstring`, `openssl`, `xml`, `dom`)
- Composer 2
- Node.js 20+ dan npm
- MySQL 8+ atau SQLite
- Git

## Instalasi

Ambil kode proyek dan pasang dependensinya:

```bash
git clone https://github.com/tarothejungle/sistem-raport-digital.git
cd sistem-raport-digital
composer install
npm install
```

Salin konfigurasi lingkungan dan buat application key:

```bash
cp .env.example .env
php artisan key:generate
```

Pada Windows, ganti perintah `cp` dengan `copy .env.example .env`.

Untuk SQLite, buat berkas `database/database.sqlite` lalu isi `.env`:

```env
DB_CONNECTION=sqlite
DB_DATABASE=/absolute/path/ke/database.sqlite
```

Untuk MySQL:

```env
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=sistem_raport_digital
DB_USERNAME=root
DB_PASSWORD=
```

Jalankan migrasi, bangun aset, lalu hidupkan server pengembangan:

```bash
php artisan migrate --seed
npm run build
php artisan serve
```

Aplikasi dapat diakses di `http://127.0.0.1:8000/admin/login`.

Jika ingin setup lebih cepat setelah dependensi terpasang, gunakan:

```bash
composer run setup
```

## Membuat Super Admin

Seeder tidak membuat akun administrator bawaan. Setelah instalasi, buat akun super admin secara interaktif:

```bash
php artisan admin:create-super
```

Perintah ini akan meminta nama lengkap, username, email, dan kata sandi. Akun yang dibuat otomatis mendapat role `admin` dan email yang sudah terverifikasi.

## Cloudflare Turnstile

Isi kredensial Turnstile pada `.env`:

```env
TURNSTILE_SITE_KEY=
TURNSTILE_SECRET_KEY=
TURNSTILE_THEME=auto
TURNSTILE_SIZE=flexible
TURNSTILE_LANGUAGE=id
```

Gunakan key asli untuk production. Key uji coba biasanya menampilkan banner peringatan di halaman login, hal ini normal pada lingkungan lokal.

## Perintah Pengembangan

Menjalankan server, queue worker, log viewer, dan Vite sekaligus:

```bash
composer run dev
```

Perintah lain yang sering dipakai:

```bash
npm run dev      # mode pengembangan Vite
npm run build    # build aset production
php artisan test # menjalankan seluruh test
```

Setelah mengubah theme atau aset Filament, publikasikan ulang asetnya:

```bash
php artisan optimize:clear
php artisan filament:assets
```

## Catatan Deployment

Untuk production, pasang dependency dari lockfile dan bangun ulang aset:

```bash
composer install --no-dev --optimize-autoloader
npm ci
npm run build
php artisan optimize:clear
php artisan filament:assets
php artisan config:cache
php artisan route:cache
php artisan view:cache
```

Jika aplikasi berada di belakang reverse proxy atau Cloudflare Tunnel, isi `TRUSTED_PROXIES` dengan IP/CIDR proxy yang dipercaya. Nilai `*` hanya cocok jika origin tidak dapat diakses langsung dari internet. Biarkan `ASSET_URL` kosong kecuali aset statis memang dilayani dari CDN terpisah.

## Struktur Penting

- `app/Filament/Admin` — resource, halaman, widget, dan autentikasi panel admin
- `app/Models` — model Eloquent untuk data akademik dan pengguna
- `app/Services` — logika impor siswa, sinkronisasi guru, dan proses kelas
- `app/Http/Controllers/RaportPdfController.php` — pratinjau dan unduh rapor PDF
- `database/migrations` — skema database beserta perubahannya
- `resources/views/filament` — tampilan kustom Filament
- `resources/css/filament/admin/raport-theme.css` — sumber theme panel
- `template-import-siswa.xlsx` — template resmi untuk impor data siswa
- `tests` — test fitur untuk layanan, keamanan, dashboard, dan rapor

## Pengujian

Jalankan test suite sebelum mengirim perubahan:

```bash
php artisan test
```

Suite ini menggunakan SQLite `:memory:` sehingga tidak menyentuh database pengembangan. Cakupannya meliputi layanan kelas dan siswa, input nilai, kenaikan kelas, command super admin, dashboard, upload file, notifikasi, keamanan production, hingga unduh rapor.

## Keamanan Data

Repository ini sengaja tidak menyertakan `.env`, database lokal, dump SQL, kredensial, upload pengguna, `vendor`, atau `node_modules`. Jangan menyimpan data pribadi guru dan siswa di Git. Gunakan `.env.example` hanya sebagai template konfigurasi, lalu simpan nilai sensitif di server masing-masing.

Berkas spreadsheet lain selain template resmi diabaikan oleh Git untuk mengurangi risiko kebocoran data sekolah.

## Lisensi

Proyek ini dirilis di bawah [GNU General Public License v3.0 atau versi lebih baru](LICENSE). Dependensi dan aset pihak ketiga tetap mengikuti lisensi masing-masing.
