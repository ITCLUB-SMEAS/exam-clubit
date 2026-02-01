<x-mail::message>
# Peringatan: Siswa Berisiko Tinggi

Sistem telah mendeteksi **{{ $highCount }} siswa** yang berisiko tinggi untuk ujian berikut:

**Ujian:** {{ $exam->title }}
@if($exam->lesson)
**Mata Pelajaran:** {{ $exam->lesson->name }}
@endif

---

## Ringkasan

| Kategori | Jumlah |
|----------|--------|
| Risiko Kritis | {{ $criticalCount }} siswa |
| Risiko Tinggi | {{ $highCount - $criticalCount }} siswa |
| **Total** | **{{ $highCount }} siswa** |

---

## Daftar Siswa Berisiko

<x-mail::table>
| Nama Siswa | Skor Risiko | Level | Faktor Utama |
|:-----------|:-----------:|:-----:|:-------------|
@foreach($predictions->take(10) as $prediction)
| {{ $prediction->student->name ?? 'N/A' }} | {{ number_format($prediction->risk_score, 1) }}% | {{ $prediction->getRiskLabel() }} | {{ implode(', ', array_slice($prediction->getPrimaryRiskFactors(), 0, 2)) ?: '-' }} |
@endforeach
</x-mail::table>

@if($highCount > 10)
*... dan {{ $highCount - 10 }} siswa lainnya*
@endif

---

## Rekomendasi Tindakan

Berdasarkan analisis sistem, berikut rekomendasi untuk siswa berisiko tinggi:

1. **Review materi** - Berikan latihan tambahan untuk topik yang lemah
2. **Komunikasi** - Hubungi siswa untuk memberikan motivasi
3. **Monitoring** - Pantau progres siswa lebih intensif

<x-mail::button :url="$dashboardUrl">
Lihat Detail di Dashboard
</x-mail::button>

---

*Email ini dikirim secara otomatis oleh sistem Predictive Analytics.*
*Prediksi dibuat berdasarkan data historis dan tidak menjamin hasil akhir.*

Salam,<br>
{{ config('app.name') }}
</x-mail::message>
