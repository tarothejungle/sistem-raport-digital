# Sistem Raport Digital

Aplikasi Laravel + Filament untuk mengelola master data sekolah, jadwal mengajar, dan nilai siswa.

## Fitur yang tersedia

- Login admin dan pembatasan akses panel admin berdasarkan role.
- CRUD tahun ajaran, kelas, mata pelajaran, guru, dan siswa.
- CRUD jadwal mengajar dengan validasi agar kombinasi guru, mapel, kelas, dan periode tidak ganda.
- Input nilai per siswa sesuai kelas pada jadwal yang dipilih.
- Nilai akhir dihitung otomatis dari rata-rata tugas, UTS, dan UAS.
- Finalisasi nilai hanya dapat dilakukan ketika tiga komponen nilai sudah terisi.
- Perlindungan data relasional: data induk yang masih digunakan tidak dapat dihapus dari panel.
- Dashboard ringkas untuk jumlah data utama.

## Prasyarat

- PHP 8.3 atau lebih baru, dengan ekstensi `pdo_mysql` atau `pdo_sqlite`, `mbstring`, `xml`, dan `dom` aktif.
- Composer 2.
- Node.js 20+ dan npm.
- MySQL 8+ atau SQLite.

## Instalasi

```bash
composer install
cp .env.example .env
php artisan key:generate
```

Atur koneksi database pada `.env`. Untuk SQLite, gunakan:

```env
DB_CONNECTION=sqlite
DB_DATABASE=/absolute/path/ke/project/database/database.sqlite
```

Lalu jalankan migrasi, seed admin, dan aset frontend:

```bash
php artisan migrate:fresh --seed
npm install
npm run build
php artisan serve
```

Buka `http://127.0.0.1:8000`. Aplikasi akan mengarahkan Anda ke panel admin.

## Akun awal

| Email | Kata sandi |
| --- | --- |
| `admin@raport.test` | `password` |

Ganti kata sandi admin setelah login pada lingkungan yang digunakan bersama.

## Alur penggunaan

1. Buat tahun ajaran dan aktifkan periode yang sedang berjalan.
2. Buat kelas serta mata pelajaran.
3. Tambahkan guru. Sistem akan membuat akun guru dengan role `guru`.
4. Tambahkan siswa ke kelas.
5. Buat jadwal mengajar.
6. Input nilai; daftar siswa otomatis dibatasi ke kelas dari jadwal tersebut.
7. Aktifkan **Finalisasi nilai** setelah nilai tugas, UTS, dan UAS lengkap.

## Pengujian

```bash
php artisan test
```

Pengujian menggunakan SQLite in-memory dan mencakup perhitungan nilai akhir, validasi kelas siswa, serta pencegahan nilai ganda.
