<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Laporan Siswa - {{ $student->name }}</title>
    <style>
        body { font-family: Arial, sans-serif; font-size: 12px; }
        .header { text-align: center; margin-bottom: 30px; border-bottom: 2px solid #333; padding-bottom: 20px; }
        .header h1 { margin: 0; font-size: 20px; }
        .student-info { margin-bottom: 20px; }
        .student-info table { width: 100%; }
        .student-info td { padding: 5px; }
        .student-info .label { font-weight: bold; width: 120px; }
        .stats-row { margin-bottom: 20px; }
        .stats-row table { width: auto; }
        .stats-row td { border: 1px solid #ddd; padding: 10px 20px; text-align: center; }
        .stats-row .value { font-size: 24px; font-weight: bold; }
        table.results { width: 100%; border-collapse: collapse; }
        table.results th, table.results td { border: 1px solid #ddd; padding: 8px; }
        table.results th { background: #f5f5f5; }
        .text-center { text-align: center; }
        .passed { color: #28a745; }
        .failed { color: #dc3545; }
        .footer { margin-top: 30px; text-align: right; font-size: 10px; color: #666; }
    </style>
</head>
<body>
    <div class="header">
        <h1>LAPORAN HASIL UJIAN SISWA</h1>
        <p>Sistem Ujian Online</p>
    </div>

    <div class="student-info">
        <table>
            <tr>
                <td class="label">Nama</td>
                <td>: {{ $student->name }}</td>
                <td class="label">NISN</td>
                <td>: {{ $student->nisn }}</td>
            </tr>
            <tr>
                <td class="label">Kelas</td>
                <td>: {{ $student->classroom->title ?? '-' }}</td>
                <td class="label">Jenis Kelamin</td>
                <td>: {{ $student->gender == 'L' ? 'Laki-laki' : 'Perempuan' }}</td>
            </tr>
        </table>
    </div>

    <div class="stats-row">
        <table>
            <tr>
                <td>
                    <div class="value">{{ $stats['total_exams'] }}</div>
                    <div>Total Ujian</div>
                </td>
                <td>
                    <div class="value">{{ $stats['average'] }}</div>
                    <div>Rata-rata</div>
                </td>
                <td>
                    <div class="value passed">{{ $stats['passed'] }}</div>
                    <div>Lulus</div>
                </td>
                <td>
                    <div class="value failed">{{ $stats['failed'] }}</div>
                    <div>Tidak Lulus</div>
                </td>
            </tr>
        </table>
    </div>

    <h3>Riwayat Ujian</h3>
    <table class="results">
        <thead>
            <tr>
                <th class="text-center" style="width: 40px;">No</th>
                <th>Mata Pelajaran</th>
                <th>Ujian</th>
                <th class="text-center">Nilai</th>
                <th class="text-center">Status</th>
                <th class="text-center">Tanggal</th>
            </tr>
        </thead>
        <tbody>
            @forelse($grades as $index => $grade)
            <tr>
                <td class="text-center">{{ $index + 1 }}</td>
                <td>{{ $grade->exam->lesson->title ?? '-' }}</td>
                <td>{{ $grade->exam->title }}</td>
                <td class="text-center"><strong>{{ number_format($grade->grade, 1) }}</strong></td>
                <td class="text-center {{ $grade->status == 'passed' ? 'passed' : 'failed' }}">
                    {{ $grade->status == 'passed' ? 'Lulus' : 'Tidak Lulus' }}
                </td>
                <td class="text-center">{{ $grade->end_time?->format('d/m/Y') }}</td>
            </tr>
            @empty
            <tr>
                <td colspan="6" class="text-center">Belum ada riwayat ujian</td>
            </tr>
            @endforelse
        </tbody>
    </table>

    <div class="footer">
        Dicetak pada: {{ now()->format('d/m/Y H:i') }}
    </div>
</body>
</html>
