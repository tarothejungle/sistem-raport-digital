<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">

    <style>
        @page {
            margin: 7mm 8mm 8mm;
        }

        body {
            color: #111827;
            font-family: Arial, sans-serif;
            font-size: 12px;
            line-height: 1.50;
        }

        table {
            border-collapse: collapse;
            width: 100%;
        }

        .text-center {
            text-align: center;
        }

        .text-right {
            text-align: right;
        }

        .header-logo {
            width: 60px;
            vertical-align: middle;
        }

        .report-title {
            font-size: 14px;
            font-weight: bold;
            margin: 0;
        }

        .report-subtitle {
            font-size: 12px;
            margin: 1px 0;
        }

        .identity {
            margin-top: 6px;
        }

        .identity td {
            padding: 1px 3px;
            vertical-align: top;
        }

        .score-table {
            margin-top: 7px;
        }

        .score-table th,
        .score-table td {
            border: 1px solid #374151;
            padding: 3px;
            vertical-align: top;
        }

        .score-table th {
            background: #e5e7eb;
            text-align: center;
        }

        .group-row td {
            background: #d1d5db;
            font-weight: bold;
        }

        .guru {
            color: #4b5563;
            font-size: 8px;
            margin-top: 1px;
        }

        .saran {
            border: 1px solid #374151;
            min-height: 25px;
            padding: 5px;
        }

        .section-title {
            font-size: 9px;
            font-weight: bold;
            margin: 8px 0 3px;
        }

        .signature {
            margin-top: 20px;
            page-break-inside: avoid;
        }

        .signature td {
            text-align: center;
            vertical-align: top;
            width: 50%;
            font-size: 12px;
        }

        .signature-space {
            height: 34px;
        }

        .signature-image {
            height: 50px;
            max-width: 100px;
        }

        .small {
            font-size: 10px;
        }
    </style>
</head>
<body>
    <table>
        <tr>
            <td class="header-logo">
                @if ($logoDataUri)
                    <img src="{{ $logoDataUri }}" style="width: 100px; height: 100px;">
                @endif
            </td>

            <td class="text-center">
                <p class="report-title">LAPORAN HASIL PENCAPAIAN KOMPETENSI PESERTA DIDIK</p>
                <p class="report-title">PENILAIAN TENGAH SEMESTER {{ strtoupper($tahunAjaran->semester) }}</p>
                <p class="report-title">BERBASIS PROYEK PEMBELAJARAN</p>
                <p class="report-subtitle">{{ $pengaturan->nama_madrasah }}</p>
                <p class="report-subtitle">Tahun Ajaran {{ $tahunAjaran->nama }}</p>
            </td>

            <td style="width: 68px;"></td>
        </tr>
    </table>

    <table class="identity">
        <tr>
            <td style="width: 24%;">Nama Peserta Didik</td>
            <td style="width: 2%;">:</td>
            <td>{{ $siswa->nama_lengkap }}</td>
        </tr>
        <tr>
            <td>No. Induk / NISN</td>
            <td>:</td>
            <td>{{ $siswa->nisn }}</td>
        </tr>
        <tr>
            <td>Kelas</td>
            <td>:</td>
            <td>{{ $kelas?->nama_kelas ?? '-' }}</td>
        </tr>
    </table>

    <table class="score-table">
        <thead>
            <tr>
                <th style="width: 5%;">No.</th>
                <th style="width: 25%;">Mata Pelajaran</th>
                <th style="width: 8%;">KKM</th>
                <th style="width: 10%;">Nilai Angka</th>
                <th style="width: 10%;">Predikat</th>
                <th>Deskripsi</th>
            </tr>
        </thead>

        <tbody>
            @php($nomor = 1)

            @foreach (['A' => $kelompokA, 'B' => $kelompokB] as $kodeKelompok => $daftarNilai)
                <tr class="group-row">
                    <td colspan="6">Kelompok {{ $kodeKelompok }}</td>
                </tr>

                @forelse ($daftarNilai as $nilai)
                    <tr>
                        <td class="text-center">{{ $nomor++ }}</td>
                        <td>
                            <strong>{{ $nilai['mapel'] }}</strong>
                            <div class="guru">Guru: {{ $nilai['guru'] }}</div>
                        </td>
                        <td class="text-center">{{ $nilai['kkm'] }}</td>
                        <td class="text-center">{{ $nilai['nilai_angka'] ?? '-' }}</td>
                        <td class="text-center">{{ $nilai['predikat'] ?? '-' }}</td>
                        <td>{{ $nilai['deskripsi'] ?: '-' }}</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" class="text-center">Belum ada mata pelajaran pada kelompok ini.</td>
                    </tr>
                @endforelse
            @endforeach
        </tbody>
    </table>

    <p class="section-title">Saran-Saran</p>

    <div class="saran">
        {{ $saran ?: '-' }}
    </div>

    <table class="signature">
        <tr>
            <td>
                {{ $pengaturan->kota ?: '-' }}, {{ $tanggalCetak }}<br>
                Wali {{ $kelas?->nama_kelas ?? '-' }}
            </td>

            <td>
                Mengetahui,<br>
                Kepala Madrasah<br><br><br>
            </td>
        </tr>

        <tr>
            <td class="signature-space"></td>

            <td class="signature-space">
                @if ($ttdKepalaDataUri)
                    <img
                        src="{{ $ttdKepalaDataUri }}"
                        class="signature-image"
                    >
                @endif
            </td>
        </tr>

        <tr>
            <td>
                <strong>{{ $kelas?->waliKelas?->nama ?? '-' }}</strong>
            </td>

            <td>
                <strong>{{ $pengaturan->nama_kepala_madrasah ?: '-' }}</strong>

                @if (
                    filled($pengaturan->nip_kepala_madrasah)
                    && $pengaturan->nip_kepala_madrasah !== '-'
                )
                    <br>
                    <span class="small">
                        NIP. {{ $pengaturan->nip_kepala_madrasah }}
                    </span>
                @endif
            </td>
        </tr>
    </table>
</body>
</html>