# Sistem Rapor Digital

Sistem Rapor Digital adalah aplikasi pengelolaan data akademik untuk madrasah dan sekolah. Aplikasi ini dibangun dengan Laravel 13 dan Filament 5, dengan tujuan sederhana: mengurus data siswa, penugasan guru, input nilai, kenaikan kelas, sampai penerbitan rapor PDF dari satu panel yang rapi.

Proyek ini dirancang untuk dipakai oleh tim sekolah dengan kemampuan teknis yang beragam. Administrator dapat mengelola data master, guru cukup fokus pada kelas dan mata pelajaran yang diampu, sedangkan siswa dapat melihat nilai ketika aksesnya dibuka. Intinya satu: memindahkan pekerjaan rapor yang biasanya tersebar di banyak file Excel ke satu alur yang nyambung, dari menyiapkan data sampai rapor siap dibagikan.

## Fitur Utama

- **Data akademik terpusat**: tahun ajaran, kelas, mata pelajaran, guru, siswa, alumni, dan pengaturan identitas madrasah.
- **Penugasan pengajar**: guru dapat ditugaskan pada kombinasi mata pelajaran dan kelas untuk tahun ajaran tertentu.
- **Input nilai yang terstruktur**: nilai per mata pelajaran, perhitungan nilai akhir, indeks ketercapaian, dan deskripsi belajar.
- **Template dan impor nilai**: unduh template Excel per kelas, isi di luar aplikasi, lalu impor kembali dengan validasi header dan NISN agar data tidak tertukar.
- **Absensi siswa**: pencatatan sakit, izin, dan alpa per kelas dan tahun ajaran yang otomatis ikut ke seluruh mata pelajaran dan muncul di rapor.
- **Leger Nilai PTS**: rekap nilai tengah semester per kelas yang dapat diekspor ke Excel maupun PDF.
- **Pengelolaan siswa**: penambahan manual, impor spreadsheet, pengaturan alumni, serta riwayat perpindahan kelas.
- **Kenaikan kelas dan kelulusan**: proses akhir periode disertai riwayat kelas agar data historis tetap jelas.
- **Penerbitan rapor**: pratinjau, unduh satu rapor, dan unduh massal dalam format PDF atau ZIP, lengkap dengan rekap ketidakhadiran.
- **Kontrol akses nilai siswa**: administrator dapat mengatur kapan nilai boleh dilihat siswa.
- **Autentikasi dan profil**: login username, reset kata sandi, avatar, ganti kata sandi, role-based access, dan Cloudflare Turnstile.
- **Mode pemeliharaan dan pengumuman**: halaman maintenance beserta jadwal, pintu masuk administrator khusus saat maintenance, dan pengumuman situs yang dapat diatur dari panel.
- **Antarmuka modern**: panel Filament responsif bergaya neumorphism dengan dukungan light mode dan dark mode, navigasi bawah untuk mobile, dan halaman login yang senada.
- **Operasional harian**: dashboard ringkasan kesiapan rapor, database notification, dan navigasi yang disesuaikan per peran.

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

## Docker Development

Docker Compose dapat menggantikan PHP, Composer, Node.js, dan MySQL lokal. Pada workstation ini Docker Engine berjalan di distro WSL2 `FedoraLinux-44`; helper PowerShell meneruskan command ke distro tersebut melalui `sudo`.

Stack development terdiri dari:

- `app` — Laravel pada `http://localhost:8005`
- `vite` — Vite HMR pada port `5173`
- `queue` — listener queue database yang membaca perubahan source untuk setiap job
- `mysql` — MySQL 8.4 dengan data persisten

Jalankan setup otomatis dari PowerShell:

```powershell
.\scripts\docker-up.ps1
```

Jika sesi sudo Fedora sudah kedaluwarsa, autentikasi sekali lalu ulangi helper:

```powershell
wsl.exe -d FedoraLinux-44 -- sudo -v
.\scripts\docker-up.ps1
```

Script membuat `.env.docker` dari `.env.docker.example`, menghasilkan `APP_KEY`, membangun image, lalu menjalankan seluruh service. Alternatif manual:

```powershell
Copy-Item .env.docker.example .env.docker
wsl.exe -d FedoraLinux-44 -- sudo docker compose --project-directory /mnt/d/laragon/www/sistem-raport-digital --env-file /mnt/d/laragon/www/sistem-raport-digital/.env.docker up -d --build --remove-orphans
```

Jika memakai cara manual, isi `APP_KEY` pada `.env.docker` sebelum startup. Aplikasi tersedia di:

```text
http://localhost:8005
http://localhost:8005/admin/login
```

Perintah operasional:

```bash
wsl.exe -d FedoraLinux-44 -- sudo docker compose --project-directory /mnt/d/laragon/www/sistem-raport-digital --env-file /mnt/d/laragon/www/sistem-raport-digital/.env.docker ps
wsl.exe -d FedoraLinux-44 -- sudo docker compose --project-directory /mnt/d/laragon/www/sistem-raport-digital --env-file /mnt/d/laragon/www/sistem-raport-digital/.env.docker logs -f app queue vite
wsl.exe -d FedoraLinux-44 -- sudo docker compose --project-directory /mnt/d/laragon/www/sistem-raport-digital --env-file /mnt/d/laragon/www/sistem-raport-digital/.env.docker run --rm test
wsl.exe -d FedoraLinux-44 -- sudo docker compose --project-directory /mnt/d/laragon/www/sistem-raport-digital --env-file /mnt/d/laragon/www/sistem-raport-digital/.env.docker down
```

Source proyek di-bind mount ke container. Perubahan PHP dan Blade langsung aktif; perubahan CSS landing diproses Vite HMR. Dependency disimpan dalam named volume Linux, bukan `node_modules` atau `vendor` host. Startup menyinkronkan dependency jika `composer.lock` atau `package-lock.json` berubah.

Saat service Vite berhenti normal, entrypoint menghapus `public/hot` agar Laravel di luar Docker tidak menunjuk ke dev server yang sudah mati. Jika komputer atau Docker mati paksa dan landing mencoba membuka port `5173`, hapus file tersebut manual:

```powershell
Remove-Item public\hot -ErrorAction SilentlyContinue
```

Setelah mengambil update proyek, jalankan kembali:

```powershell
.\scripts\docker-up.ps1
```

Script memakai `--build --force-recreate`, sehingga perubahan Dockerfile, image, konfigurasi service, dependency, migration, dan source ikut diterapkan. Startup menjalankan `php artisan migrate --force`; tidak ada `migrate:fresh` atau seed ulang otomatis.

Jika package Filament atau aset vendornya berubah, publikasikan ulang secara eksplisit lalu commit hasil yang memang berubah:

```bash
wsl.exe -d FedoraLinux-44 -- sudo docker compose --project-directory /mnt/d/laragon/www/sistem-raport-digital --env-file /mnt/d/laragon/www/sistem-raport-digital/.env.docker exec app php artisan filament:assets
```

Data MySQL tersimpan dalam volume `mysql_data`. `docker compose down` tidak menghapusnya. Perintah berikut menghapus database dan dependency Docker secara permanen, jadi jangan gunakan untuk operasi normal:

```bash
wsl.exe -d FedoraLinux-44 -- sudo docker compose --project-directory /mnt/d/laragon/www/sistem-raport-digital --env-file /mnt/d/laragon/www/sistem-raport-digital/.env.docker down -v
```

### Memindahkan Database Laragon

Buat dump database lama lebih dahulu. Contoh jika `mysqldump` tersedia pada PATH:

```powershell
mysqldump -u root --default-character-set=utf8mb4 sistem-rapor-digital > storage\app\backup-laragon.sql
```

Hidupkan MySQL Docker tanpa `app`, lalu impor sekali:

```powershell
Copy-Item .env.docker.example .env.docker
wsl.exe -d FedoraLinux-44 -- sudo docker compose --project-directory /mnt/d/laragon/www/sistem-raport-digital --env-file /mnt/d/laragon/www/sistem-raport-digital/.env.docker up -d mysql
.\scripts\import-docker-database.ps1 -DumpPath storage\app\backup-laragon.sql
.\scripts\docker-up.ps1
```

Script import menolak database Docker yang sudah memiliki tabel. Guard ini mencegah dump lama menimpa database yang sudah digunakan. Simpan backup sampai jumlah akun, siswa, guru, nilai, dan rapor sudah diverifikasi.

### Node dan Vite

`node_modules` hanya dibutuhkan saat build atau Vite development server berjalan. Folder ini tidak dibutuhkan oleh runtime PHP dan tidak boleh dikirim ke production. Landing page `/` tetap memakai Tailwind melalui Vite, sehingga `package.json`, `package-lock.json`, `vite.config.js`, dan `resources/css/app.css` tetap menjadi source yang diperlukan.

Pada production non-Docker, jalankan `npm ci && npm run build`, lalu deploy `public/build`. Setelah build selesai, `node_modules` boleh dihapus. Panel Filament memakai pipeline terpisah melalui `php artisan filament:assets`.

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
- `app/Services` — logika impor siswa, spreadsheet nilai, absensi, leger PTS, sinkronisasi guru, dan proses kelas
- `app/Http/Controllers/RaportPdfController.php` — pratinjau dan unduh rapor PDF
- `database/migrations` — skema database beserta perubahannya
- `resources/views/filament` — tampilan kustom Filament
- `resources/views/components/mobile-bottom-nav.blade.php` — navigasi bawah untuk tampilan mobile
- `resources/css/filament/admin/raport-theme.css` — sumber theme panel (disinkronkan ke `public/css/app/raport-theme.css`)
- `public/logo` — logo resmi (`logo-baru-*.png`) beserta master SVG dan varian satu warna
- `template-import-siswa.xlsx` — template resmi untuk impor data siswa
- `tests` — test fitur untuk layanan, keamanan, dashboard, dan rapor

## Pengujian

Jalankan test suite sebelum mengirim perubahan:

```bash
php artisan test
```

Suite ini menggunakan SQLite `:memory:` sehingga tidak menyentuh database pengembangan. Cakupannya meliputi layanan kelas dan siswa, input nilai, kenaikan kelas, command super admin, dashboard, upload file, notifikasi, keamanan production, hingga unduh rapor.

## Keamanan Data

Repository ini sengaja tidak menyertakan `.env`, database lokal, dump SQL, kredensial, upload pengguna, `vendor`, atau `node_modules`. Dokumen yang memuat data pribadi siswa (misalnya hasil ekspor EMIS, leger nilai asli, dan rapor PDF nyata), laporan pengujian keamanan internal, serta arsip proyek juga diabaikan oleh Git dan tidak boleh dipublikasikan. Jangan menyimpan data pribadi guru dan siswa di Git. Gunakan `.env.example` hanya sebagai template konfigurasi, lalu simpan nilai sensitif di server masing-masing.

Berkas spreadsheet lain selain template resmi diabaikan oleh Git untuk mengurangi risiko kebocoran data sekolah.

## Lisensi

Proyek ini dirilis di bawah [GNU General Public License v3.0 atau versi lebih baru](LICENSE). Dependensi dan aset pihak ketiga tetap mengikuti lisensi masing-masing.
