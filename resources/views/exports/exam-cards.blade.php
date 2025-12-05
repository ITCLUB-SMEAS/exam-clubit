<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Kartu Peserta Ujian</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        body {
            font-family: 'Helvetica', 'Arial', sans-serif;
            font-size: 11px;
            color: #333;
        }
        .page {
            width: 100%;
            padding: 10px;
        }
        .cards-container {
            display: flex;
            flex-wrap: wrap;
            justify-content: space-between;
        }
        .card {
            width: 48%;
            border: 2px solid #2563eb;
            border-radius: 8px;
            margin-bottom: 15px;
            overflow: hidden;
            page-break-inside: avoid;
        }
        .card-header {
            background: #2563eb;
            color: white;
            padding: 10px;
            text-align: center;
        }
        .card-header h1 {
            font-size: 14px;
            font-weight: bold;
            margin-bottom: 3px;
        }
        .card-header p {
            font-size: 10px;
            opacity: 0.9;
        }
        .card-body {
            padding: 12px;
        }
        .card-content {
            display: table;
            width: 100%;
        }
        .card-info {
            display: table-cell;
            vertical-align: top;
            width: 65%;
        }
        .card-photo {
            display: table-cell;
            vertical-align: top;
            width: 35%;
            text-align: center;
        }
        .photo-box {
            width: 75px;
            height: 100px;
            border: 1px solid #ddd;
            background: #f5f5f5;
            display: inline-block;
            overflow: hidden;
        }
        .photo-box img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }
        .photo-placeholder {
            width: 100%;
            height: 100%;
            display: flex;
            align-items: center;
            justify-content: center;
            color: #999;
            font-size: 9px;
            text-align: center;
        }
        .info-row {
            margin-bottom: 6px;
        }
        .info-label {
            font-size: 9px;
            color: #666;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        .info-value {
            font-size: 12px;
            font-weight: bold;
            color: #1e293b;
        }
        .info-value.name {
            font-size: 13px;
            color: #2563eb;
        }
        .card-footer {
            background: #f8fafc;
            padding: 8px 12px;
            border-top: 1px solid #e2e8f0;
        }
        .footer-content {
            display: table;
            width: 100%;
        }
        .room-box {
            display: table-cell;
            vertical-align: middle;
            background: #dbeafe;
            border: 1px solid #3b82f6;
            padding: 5px 10px;
            border-radius: 4px;
            width: 50%;
        }
        .room-label {
            font-size: 8px;
            color: #1e40af;
        }
        .room-value {
            font-size: 13px;
            font-weight: bold;
            color: #1e40af;
        }
        .exam-info {
            display: table-cell;
            vertical-align: middle;
            text-align: right;
            width: 50%;
        }
        .exam-info p {
            font-size: 9px;
            color: #64748b;
        }
        .exam-time {
            font-weight: bold;
            color: #334155;
        }
        .instructions {
            margin-top: 20px;
            padding: 15px;
            background: #f1f5f9;
            border-radius: 8px;
            page-break-inside: avoid;
        }
        .instructions h3 {
            font-size: 12px;
            margin-bottom: 8px;
            color: #1e293b;
        }
        .instructions ul {
            font-size: 10px;
            color: #475569;
            padding-left: 20px;
        }
        .instructions li {
            margin-bottom: 4px;
        }
        @page {
            margin: 15mm;
        }
    </style>
</head>
<body>
    <div class="page">
        <div class="cards-container">
            @foreach($students as $student)
            <div class="card">
                <div class="card-header">
                    <h1>KARTU PESERTA UJIAN</h1>
                    <p>{{ $exam->title }}</p>
                </div>
                <div class="card-body">
                    <div class="card-content">
                        <div class="card-info">
                            <div class="info-row">
                                <div class="info-label">Nama Lengkap</div>
                                <div class="info-value name">{{ $student['name'] }}</div>
                            </div>
                            <div class="info-row">
                                <div class="info-label">NISN</div>
                                <div class="info-value">{{ $student['nisn'] }}</div>
                            </div>
                            <div class="info-row">
                                <div class="info-label">Kelas</div>
                                <div class="info-value">{{ $student['classroom'] }}</div>
                            </div>
                        </div>
                        <div class="card-photo">
                            <div class="photo-box">
                                @if($student['photo'] && str_contains($student['photo'], '/'))
                                    <img src="{{ public_path('storage/' . $student['photo']) }}" alt="Foto">
                                @else
                                    <div class="photo-placeholder">Pas Foto<br>3x4</div>
                                @endif
                            </div>
                        </div>
                    </div>
                </div>
                <div class="card-footer">
                    <div class="footer-content">
                        <div class="room-box">
                            <div class="room-label">RUANGAN</div>
                            <div class="room-value">{{ $student['room'] ?? '-' }}</div>
                        </div>
                        <div class="exam-info">
                            <p>{{ $examSession->title }}</p>
                            <p class="exam-time">{{ \Carbon\Carbon::parse($examSession->start_time)->format('d/m/Y H:i') }}</p>
                        </div>
                    </div>
                </div>
            </div>
            @endforeach
        </div>

        @if($students->count() > 0 && $students->count() <= 4)
        <div class="instructions">
            <h3>Petunjuk Peserta Ujian:</h3>
            <ul>
                <li>Hadir 15 menit sebelum ujian dimulai</li>
                <li>Bawa kartu ini dan tunjukkan kepada pengawas</li>
                <li>Pastikan perangkat dalam kondisi baik dan baterai cukup</li>
                <li>Dilarang membawa catatan atau alat bantu apapun</li>
                <li>Kecurangan akan mengakibatkan diskualifikasi</li>
            </ul>
        </div>
        @endif
    </div>
</body>
</html>
