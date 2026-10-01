<x-filament-panels::page>
    <div class="raport-page-stack raport-leger-page">
        <section class="raport-flow-panel">
            <div>
                <span class="raport-eyebrow">Leger PTS</span>
                <h2 class="raport-flow-panel__title">Rekap nilai proyek seluruh siswa dalam satu tampilan.</h2>
            </div>

            <div class="raport-flow-steps">
                <span>Pilih periode</span>
                <span>Periksa kelengkapan</span>
                <span>Lihat peringkat</span>
            </div>
        </section>

        {{ $this->form }}

        @php($leger = $this->legerData())

        @if ($leger)
            <div class="raport-leger-actions">
                <x-filament::button tag="a" :href="$this->exportUrl('xlsx')" icon="heroicon-o-table-cells" color="success" class="raport-leger-excel-button">
                    Unduh Excel
                </x-filament::button>
                <x-filament::button tag="a" :href="$this->exportUrl('pdf')" icon="heroicon-o-document-arrow-down" color="danger">
                    Unduh PDF
                </x-filament::button>
            </div>

            <section class="raport-leger-sheet">
                <header class="raport-leger-sheet__header">
                    @php($namaKelas = trim($leger['kelas']->nama_kelas))
                    <strong>LEGER {{ str_starts_with(strtoupper($namaKelas), 'KELAS ') ? strtoupper($namaKelas) : 'KELAS '.strtoupper($namaKelas) }}</strong>
                    <p>MADRASAH IBTIDAIYAH LANTABURO</p>
                    <span>TAHUN AJARAN {{ $leger['tahun_ajaran']->nama }}</span>
                </header>

                @unless ($leger['lengkap'])
                    <div class="raport-leger-warning">
                        Jadwal 11 mata pelajaran resmi belum lengkap. Jumlah dan ranking hanya tampil untuk siswa dengan seluruh nilai tersedia.
                    </div>
                @endunless

                <div class="raport-leger-table-wrap">
                    <table class="raport-leger-table">
                        <thead>
                            <tr>
                                <th>No</th>
                                <th class="raport-leger-name">Nama</th>
                                <th>NISN</th>
                                @foreach ($leger['mapel'] as $mapel)
                                    <th title="{{ $mapel['nama'] }}">{{ $mapel['label'] }}</th>
                                @endforeach
                                <th>Jumlah</th>
                                <th>Ranking</th>
                                <th class="raport-leger-name">Saran-saran</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($leger['rows'] as $row)
                                <tr>
                                    <td>{{ $row['no'] }}</td>
                                    <td class="raport-leger-name">{{ $row['nama_siswa'] }}</td>
                                    <td>{{ $row['nisn'] }}</td>
                                    @foreach (array_keys($leger['mapel']) as $kode)
                                        <td>{{ $row['mata_pelajaran'][$kode]['nilai_angka'] ?? '-' }}</td>
                                    @endforeach
                                    <td class="raport-leger-total">{{ $row['jumlah'] ?? '-' }}</td>
                                    <td class="raport-leger-rank">{{ $row['ranking'] ?? '-' }}</td>
                                    <td class="raport-leger-name">{{ $row['saran'] ?: '-' }}</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="17" class="raport-leger-empty">Belum ada siswa pada kelas ini.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </section>
        @else
            <section class="raport-leger-placeholder">
                Pilih tahun ajaran dan kelas untuk membuka leger nilai PTS.
            </section>
        @endif
    </div>
</x-filament-panels::page>
