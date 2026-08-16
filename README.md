# Sistem Rapor Digital

Sistem Rapor Digital merupakan aplikasi pengelolaan data akademik sekolah berbasis Laravel 13 dan Filament 5. Aplikasi menyediakan panel terpadu untuk administrator, guru, dan siswa, mulai dari pengelolaan master data hingga input nilai, kenaikan kelas, dan penerbitan rapor PDF.

## Fitur Utama

- Autentikasi menggunakan **username** dan kata sandi.
- Role `admin`, `guru`, dan `siswa` dengan pembatasan akses panel.
- Perlindungan login dengan rate limiting, regenerasi session, dan Cloudflare Turnstile.
- Dashboard operasional sesuai konteks pengguna.
- Pengelolaan tahun ajaran, kelas, mata pelajaran, guru, siswa, dan alumni.
- Penugasan guru pada mata pelajaran dan kelas setiap tahun ajaran.
- Input nilai per mata pelajaran dengan validasi data dan penyimpanan batch.
- Perhitungan nilai akhir dan deskripsi ketercapaian belajar.
- Pengaturan akses siswa untuk melihat nilai.
- Kenaikan kelas dan kelulusan dengan riwayat kelas siswa.
- Impor data siswa dari berkas spreadsheet.
- Pratinjau, unduh, dan unduh massal rapor dalam format PDF.
- Profil pengguna, avatar, dan penggantian kata sandi.
- Database notification dan halaman maintenance.
- Tampilan responsif dengan light mode dan dark mode.

## Teknologi

- PHP 8.3+
- Laravel 13 (`laravel/framework ^13.8`)
- Filament 5 (`filament/filament ~5.0`)
- Livewire 4 (`livewire/livewire ~4.0`)
- MySQL 8+ atau SQLite
- Tailwind CSS 4 (`@tailwindcss/vite ^4.0.0`) dan Vite 8
- Dompdf (`barryvdh/laravel-dompdf ^3.1`)
- Cloudflare Turnstile (`muazzambuilds/filament-turnstile 1.1.0`, paket lokal `packages/filament-turnstile`)
- SweetAlert2 (`sweetalert2 ^11.26`)
- PHPUnit 12
- Pint, Pail, PAO (dev tooling)

## Prasyarat

- PHP 8.3 atau lebih baru.
- Composer 2.
- Node.js 20+ dan npm.
- MySQL 8+ atau SQLite.
- Ekstensi PHP yang dibutuhkan Laravel, termasuk `pdo_mysql` atau `pdo_sqlite`, `mbstring`, `openssl`, `xml`, dan `dom`.
- Git.

## Instalasi

```bash
git clone https://github.com/tarothejungle/sistem-raport-digital.git
cd sistem-raport-digital
composer install
cp .env.example .env
php artisan key:generate
npm install
```

Atur koneksi database pada `.env`. Konfigurasi SQLite:

```env
DB_CONNECTION=sqlite
DB_DATABASE=/absolute/path/ke/project/database/database.sqlite
```

Konfigurasi MySQL:

```env
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=sistem_raport_digital
DB_USERNAME=root
DB_PASSWORD=
```

Jalankan migrasi, buat akun demo, dan bangun aset:

```bash
php artisan migrate --seed
npm run build
php artisan serve
```

Buka `http://127.0.0.1:8000/admin/login`.

## Akun Demo & Super Admin

`DatabaseSeeder` membuat akun administrator demo berikut secara idempotent:

| Username | Kata sandi | Role |
| --- | --- | --- |
| `demo.admin` | `DemoRaport2026` | `admin` |

Kredensial ini hanya untuk lingkungan local, demo, dan pengujian. Ubah kata sandi atau hapus akun demo sebelum aplikasi digunakan pada lingkungan production.

Login sekarang menggunakan **username**, bukan alamat email. Seeder dapat dijalankan kembali tanpa membuat akun demo ganda:

```bash
php artisan db:seed
```

Alternatif production — buat super admin interaktif tanpa seeder:

```bash
php artisan admin:create-super
```

Perintah akan meminta `Nama lengkap`, `Username` (huruf kecil `a-z0-9._-`, unik), `Email` (unik), `Kata sandi` (min. 8 karakter, konfirmasi), lalu membuat `role = admin` dengan `email_verified_at` terisi.

## Cloudflare Turnstile

Isi key pada `.env`:

```env
TURNSTILE_SITE_KEY=
TURNSTILE_SECRET_KEY=
TURNSTILE_THEME=auto
TURNSTILE_SIZE=flexible
TURNSTILE_LANGUAGE=id
```

Gunakan key Cloudflare asli pada production. Banner merah Turnstile merupakan perilaku normal ketika test key digunakan pada lingkungan local.

## Alur Penggunaan

1. Login menggunakan username dan kata sandi.
2. Atur profil madrasah dan tahun ajaran aktif.
3. Buat kelas dan mata pelajaran.
4. Tambahkan guru dan siswa.
5. Tetapkan pengajar untuk kelas dan mata pelajaran.
6. Input nilai siswa berdasarkan penugasan pengajar.
7. Atur akses siswa untuk melihat nilai.
8. Proses kenaikan kelas atau kelulusan pada akhir periode.
9. Pratinjau dan unduh rapor siswa.

## Perintah Pengembangan

Menjalankan server, queue worker, log viewer, dan Vite secara bersamaan:

```bash
composer run dev
```

Setup satu perintah (install + env + migrate/seed + build):

```bash
composer run setup
```

Membangun aset production:

```bash
npm run build
```

Mode dev Vite:

```bash
npm run dev
```

Mempublikasikan ulang aset Filament setelah theme berubah:

```bash
php artisan filament:assets
```

Proxy untuk deployment di balik reverse proxy / load balancer:

`bootstrap/app.php` sudah mengaktifkan `trustProxies(at: '*')`.

## Tampilan Login

Halaman login (`/admin/login`) memakai desain dua panel Road to Knowledge: panel ilustrasi `public/logo/undraw_educato_*.svg` dan panel form navy dengan theme switcher di sudut kanan atas, heading `Sign In`, serta form Filament (username, kata sandi, ingat saya, Turnstile, tombol `Login ke Sistem`). Token warna login terpusat di `resources/css/filament/admin/raport-theme.css` dan sinkron ke `public/css/app/raport-theme.css` via `AdminPanelProvider` (`filemtime` versioning), dark mode mengikuti token yang sama.

## Struktur Proyek (ringkas)

- `app/Filament/Admin/Pages/Auth/Login.php` — halaman login kustom
- `app/Console/Commands/CreateSuperAdmin.php` — `admin:create-super`
- `resources/views/filament/admin/pages/auth/login.blade.php` — shell login dua panel
- `resources/css/filament/admin/raport-theme.css` — sumber theme
- `public/css/app/raport-theme.css` — hasil sinkron theme (jangan edit manual)
- `public/logo/` — aset ilustrasi & logo login
- `logo/` — varian logo sumber (tidak ikut build)

## Pengujian

```bash
php artisan test
# atau
composer run test
```

Test suite menggunakan SQLite `:memory:` terisolasi (`phpunit.xml`) dan mencakup layanan kelas, siswa, kenaikan kelas, command `admin:create-super` (`tests/Feature/Console/CreateSuperAdminCommandTest.php`), serta aturan penting data akademik.

## Keamanan dan Data Sensitif

Repository tidak menyertakan `.env`, database lokal, dump SQL, private key, upload pengguna, `vendor`, atau `node_modules`. Jangan commit kredensial production, Turnstile secret key, database sekolah, berkas rapor, maupun data pribadi guru dan siswa. File `.env.example` adalah template — jangan isi key production di sana sebelum push.

## Lisensi

Sistem Rapor Digital didistribusikan di bawah [GNU General Public License v3.0 atau versi lebih baru](LICENSE). Dependensi dan aset pihak ketiga tetap tunduk pada lisensi masing-masing.
