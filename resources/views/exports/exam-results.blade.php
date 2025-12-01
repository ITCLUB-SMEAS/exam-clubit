<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Hasil Ujian - {{ $exam->title }}</title>
    <style>
        body { font-family: Arial, sans-serif; font-size: 11px; }
        .header { text-align: center; margin-bottom: 20px; }
        .header h1 { margin: 0; font-size: 18px; }
        .info { margin-bottom: 15px; }
        .info span { margin-right: 20px; }
        .stats { display: flex; margin-bottom: 15px; }
        .stat-box { border: 1px solid #ddd; padding: 10px; margin-right: 10px; text-align: center; min-width: 80px; }
        .stat-box .value { font-size: 20px; font-weight: bold; }
        table { width: 100%; border-collapse: collapse; }
        th, td { border: 1px solid #ddd; padding: 6px; text-align: left; }
        th { background: #f5f5f5; }
        .text-center { text-align: center; }
        .passed { color: #28a745; font-weight: bold; }
        .failed { color: #dc3545; font-weight: bold; }
        .footer { margin-top: 20px; font-size: 10px; color: #666; }
    </style>
</head>
<body>
    <div class="header">
        <h1>REKAP HASIL UJIAN</h1>
        <p>{{ $exam->title }}</p>
    </div>

    <div class="info">
        <span><strong>Mata Pelajaran:</strong> {{ $exam->lesson->title ?? '-' }}</span>
        <span><strong>Kelas:</strong> {{ $exam->classroom->title ?? '-' }}</span>
        <span><strong>KKM:</strong> {{ $exam->passing_grade ?? 0 }}</span>
    </div>

    <table style="width: auto; margin-bottom: 15px;">
        <tr>
            <td style="border: 1px solid #ddd; padding: 8px; text-align: center;">
                <div style="font-size: 16px; font-weight: bold;">{{ $stats['total'] }}</div>
                <div>Peserta</div>
            </td>
            <td style="border: 1px solid #ddd; padding: 8px; text-align: center;">
                <div style="font-size: 16px; font-weight: bold;">{{ $stats['average'] }}</div>
                <div>Rata-rata</div>
            </td>
            <td style="border: 1px solid #ddd; padding: 8px; text-align: center;">
                <div style="font-size: 16px; font-weight: bold; color: #28a745;">{{ $stats['passed'] }}</div>
                <div>Lulus</div>
            </td>
            <td style="border: 1px solid #ddd; padding: 8px; text-align: center;">
                <div style="font-size: 16px; font-weight: bold; color: #dc3545;">{{ $stats['failed'] }}</div>
                <div>Tidak Lulus</div>
            </td>
            <td style="border: 1px solid #ddd; padding: 8px; text-align: center;">
                <div style="font-size: 16px; font-weight: bold;">{{ $stats['highest'] }}</div>
                <div>Tertinggi</div>
            </td>
            <td style="border: 1px solid #ddd; padding: 8px; text-align: center;">
                <div style="font-size: 16px; font-weight: bold;">{{ $stats['lowest'] }}</div>
                <div>Terendah</div>
            </td>
        </tr>
    </table>

    <table>
        <thead>
            <tr>
                <th class="text-center" style="width: 40px;">No</th>
                <th>NISN</th>
                <th>Nama Siswa</th>
                <th>Kelas</th>
                <th class="text-center">Benar</th>
                <th class="text-center">Nilai</th>
                <th class="text-center">Status</th>
            </tr>
        </thead>
        <tbody>
            @foreach($grades as $index => $grade)
            <tr>
                <td class="text-center">{{ $index + 1 }}</td>
                <td>{{ $grade->student->nisn }}</td>
                <td>{{ $grade->student->name }}</td>
                <td>{{ $grade->student->classroom->title ?? '-' }}</td>
                <td class="text-center">{{ $grade->total_correct ?? 0 }}</td>
                <td class="text-center"><strong>{{ number_format($grade->grade, 1) }}</strong></td>
                <td class="text-center {{ $grade->status == 'passed' ? 'passed' : 'failed' }}">
                    {{ $grade->status == 'passed' ? 'LULUS' : 'TIDAK LULUS' }}
                </td>
            </tr>
            @endforeach
        </tbody>
    </table>

    <div class="footer">
        Dicetak pada: {{ now()->format('d/m/Y H:i') }}
    </div>
</body>
</html>
