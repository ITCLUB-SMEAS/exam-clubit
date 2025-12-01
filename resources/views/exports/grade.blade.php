<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Hasil Ujian</title>
    <style>
        body { font-family: Arial, sans-serif; font-size: 12px; }
        .header { text-align: center; margin-bottom: 30px; border-bottom: 2px solid #333; padding-bottom: 20px; }
        .header h1 { margin: 0; font-size: 24px; }
        .header p { margin: 5px 0; color: #666; }
        .info-table { width: 100%; margin-bottom: 20px; }
        .info-table td { padding: 8px; vertical-align: top; }
        .info-table .label { font-weight: bold; width: 150px; }
        .result-box { text-align: center; padding: 30px; margin: 30px 0; border: 2px solid #333; }
        .result-box .grade { font-size: 72px; font-weight: bold; }
        .result-box .status { font-size: 24px; margin-top: 10px; }
        .status-passed { color: #28a745; }
        .status-failed { color: #dc3545; }
        .footer { margin-top: 50px; text-align: right; }
        .signature { margin-top: 60px; }
    </style>
</head>
<body>
    <div class="header">
        <h1>HASIL UJIAN</h1>
        <p>Sistem Ujian Online</p>
    </div>

    <table class="info-table">
        <tr>
            <td class="label">Nama Siswa</td>
            <td>: {{ $grade->student->name }}</td>
            <td class="label">NISN</td>
            <td>: {{ $grade->student->nisn }}</td>
        </tr>
        <tr>
            <td class="label">Kelas</td>
            <td>: {{ $grade->student->classroom->title ?? '-' }}</td>
            <td class="label">Jenis Kelamin</td>
            <td>: {{ $grade->student->gender == 'L' ? 'Laki-laki' : 'Perempuan' }}</td>
        </tr>
        <tr>
            <td class="label">Mata Pelajaran</td>
            <td>: {{ $grade->exam->lesson->title ?? '-' }}</td>
            <td class="label">Ujian</td>
            <td>: {{ $grade->exam->title }}</td>
        </tr>
        <tr>
            <td class="label">Tanggal Ujian</td>
            <td>: {{ $grade->start_time?->format('d/m/Y H:i') }}</td>
            <td class="label">Selesai</td>
            <td>: {{ $grade->end_time?->format('d/m/Y H:i') }}</td>
        </tr>
    </table>

    <div class="result-box">
        <div class="grade">{{ number_format($grade->grade, 1) }}</div>
        <div class="status {{ $grade->status == 'passed' ? 'status-passed' : 'status-failed' }}">
            {{ $grade->status == 'passed' ? 'LULUS' : 'TIDAK LULUS' }}
        </div>
        <p style="margin-top: 15px; color: #666;">
            KKM: {{ $grade->exam->passing_grade ?? 0 }} | 
            Jawaban Benar: {{ $grade->total_correct ?? 0 }}
        </p>
    </div>

    <div class="footer">
        <p>Dicetak pada: {{ now()->format('d/m/Y H:i') }}</p>
        <div class="signature">
            <p>Mengetahui,</p>
            <br><br><br>
            <p>_______________________</p>
            <p>Administrator</p>
        </div>
    </div>
</body>
</html>
