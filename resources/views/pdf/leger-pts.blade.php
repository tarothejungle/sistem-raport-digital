<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Leger Nilai PTS</title>
    <style>
        @page { margin: 16px; }
        body { font-family: DejaVu Sans, sans-serif; color: #111827; font-size: 7px; }
        .heading { margin-bottom: 10px; text-align: center; line-height: 1.35; }
        .heading p { margin: 0; font-weight: bold; }
        table { width: 100%; border-collapse: collapse; table-layout: fixed; }
        th, td { border: 1px solid #334155; padding: 3px 2px; text-align: center; }
        th { background: #dbeafe; font-weight: bold; }
        .name { width: 105px; text-align: left; }
        .suggestion { width: 180px; text-align: left; }
    </style>
</head>
<body>
    <div class="heading">
        @php($namaKelas = trim($leger['kelas']->nama_kelas))
        <p>LEGER {{ str_starts_with(strtoupper($namaKelas), 'KELAS ') ? strtoupper($namaKelas) : 'KELAS '.strtoupper($namaKelas) }}</p>
        <p>MADRASAH IBTIDAIYAH LANTABURO</p>
        <p>TAHUN AJARAN {{ $leger['tahun_ajaran']->nama }}</p>
    </div>

    <table>
        <thead>
            <tr>
                <th>No</th>
                <th class="name">Nama</th>
                <th>NISN</th>
                @foreach ($leger['mapel'] as $mapel)
                    <th>{{ $mapel['label'] }}</th>
                @endforeach
                <th>Jumlah</th>
                <th>Ranking</th>
                <th class="suggestion">Saran-saran</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($leger['rows'] as $row)
                <tr>
                    <td>{{ $row['no'] }}</td>
                    <td class="name">{{ $row['nama_siswa'] }}</td>
                    <td>{{ $row['nisn'] }}</td>
                    @foreach ($row['mata_pelajaran'] as $mapel)
                        <td>{{ $mapel['nilai_angka'] ?? '-' }}</td>
                    @endforeach
                    <td>{{ $row['jumlah'] ?? '-' }}</td>
                    <td>{{ $row['ranking'] ?? '-' }}</td>
                    <td class="suggestion">{{ $row['saran'] ?: '-' }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>
</body>
</html>
