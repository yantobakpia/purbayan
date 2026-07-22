<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Laporan Keluhan Pengguna</title>
    <style>
        body {
            font-family: 'Helvetica Neue', Helvetica, Arial, sans-serif;
            color: #333;
            font-size: 12px;
            line-height: 1.4;
            margin: 0;
            padding: 0;
        }
        .header {
            border-bottom: 3px double #b91c1c;
            padding-bottom: 10px;
            margin-bottom: 20px;
        }
        .header table {
            width: 100%;
        }
        .header-title {
            font-size: 20px;
            font-weight: bold;
            color: #b91c1c;
            text-transform: uppercase;
            margin: 0;
        }
        .header-subtitle {
            font-size: 12px;
            color: #666;
            margin: 5px 0 0 0;
        }
        .meta-info {
            margin-bottom: 20px;
            background-color: #fdf2f2;
            border: 1px solid #fecaca;
            padding: 10px;
            border-radius: 4px;
        }
        .meta-info table {
            width: 100%;
        }
        .meta-info td {
            padding: 3px 0;
        }
        .meta-label {
            font-weight: bold;
            color: #7f1d1d;
            width: 120px;
        }
        .table-data {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
        }
        .table-data th {
            background-color: #b91c1c;
            color: #ffffff;
            font-weight: bold;
            text-align: left;
            padding: 8px;
            border: 1px solid #b91c1c;
            font-size: 11px;
        }
        .table-data td {
            padding: 8px;
            border: 1px solid #fecaca;
            font-size: 11px;
            vertical-align: top;
        }
        .table-data tr:nth-child(even) {
            background-color: #fdf2f2;
        }
        .badge {
            display: inline-block;
            padding: 3px 6px;
            font-size: 9px;
            font-weight: bold;
            border-radius: 3px;
            text-transform: uppercase;
            text-align: center;
        }
        .badge-resolved {
            background-color: #dcfce7;
            color: #15803d;
        }
        .badge-pending {
            background-color: #fef9c3;
            color: #a16207;
        }
        .summary-box {
            margin-top: 20px;
            page-break-inside: avoid;
        }
        .summary-title {
            font-size: 14px;
            font-weight: bold;
            color: #b91c1c;
            margin-bottom: 10px;
            border-bottom: 1px solid #fecaca;
            padding-bottom: 5px;
        }
        .summary-table {
            width: 100%;
            border-collapse: collapse;
        }
        .summary-table td {
            padding: 6px 12px;
            border: 1px solid #fecaca;
        }
        .summary-value {
            font-weight: bold;
            text-align: right;
        }
        .footer {
            position: fixed;
            bottom: -10px;
            left: 0;
            right: 0;
            height: 20px;
            text-align: center;
            font-size: 9px;
            color: #94a3b8;
            border-top: 1px solid #e2e8f0;
            padding-top: 5px;
        }
    </style>
</head>
<body>
    <div class="header">
        <table>
            <tr>
                <td>
                    <h1 class="header-title">Laporan Keluhan Pengguna</h1>
                    <p class="header-subtitle">Sistem Informasi Peminjaman Ruangan & Keluhan</p>
                </td>
                <td style="text-align: right; vertical-align: bottom; color: #666; font-size: 10px;">
                    Dicetak pada: {{ now()->format('d-m-Y H:i') }}
                </td>
            </tr>
        </table>
    </div>

    <div class="meta-info">
        <table>
            <tr>
                <td class="meta-label">Periode Laporan</td>
                <td>: {{ $start_date ? \Carbon\Carbon::parse($start_date)->format('d-m-Y') : 'Semua' }} s/d {{ $end_date ? \Carbon\Carbon::parse($end_date)->format('d-m-Y') : 'Semua' }}</td>
                <td class="meta-label">Total Data</td>
                <td>: {{ $complaints->count() }} Keluhan</td>
            </tr>
            <tr>
                <td class="meta-label">Status Keluhan</td>
                <td>: {{ $status_filter ?? 'Semua Status' }}</td>
                <td class="meta-label">Filter Ruangan</td>
                <td>: {{ $room_filter ?? 'Semua Ruangan' }}</td>
            </tr>
        </table>
    </div>

    <table class="table-data">
        <thead>
            <tr>
                <th style="width: 4%; text-align: center;">No</th>
                <th style="width: 15%;">Nama</th>
                <th style="width: 14%;">Kontak</th>
                <th style="width: 12%; text-align: center;">Foto</th>
                <th style="width: 23%;">Keluhan</th>
                <th style="width: 10%; text-align: center;">Status</th>
                <th style="width: 22%;">Penyelesaian Admin</th>
            </tr>
        </thead>
        <tbody>
            @forelse($complaints as $index => $complaint)
                @php
                    $photoBase64 = null;
                    if ($complaint->photo_path && \Illuminate\Support\Facades\Storage::disk('public')->exists($complaint->photo_path)) {
                        $imageContent = \Illuminate\Support\Facades\Storage::disk('public')->get($complaint->photo_path);
                        $mimeType = \Illuminate\Support\Facades\Storage::disk('public')->mimeType($complaint->photo_path);
                        $photoBase64 = 'data:' . $mimeType . ';base64,' . base64_encode($imageContent);
                    }
                @endphp
                <tr>
                    <td style="text-align: center;">{{ $index + 1 }}</td>
                    <td><strong>{{ $complaint->name }}</strong></td>
                    <td><span style="font-size: 10px; color: #475569;">{{ $complaint->email_or_phone }}</span></td>
                    <td style="text-align: center;">
                        @if($photoBase64)
                            <img src="{{ $photoBase64 }}" style="max-width: 50px; max-height: 50px; border-radius: 4px; border: 1px solid #fecaca;" alt="Foto">
                        @else
                            <span style="color: #94a3b8; font-style: italic; font-size: 9px;">Tidak Ada Foto</span>
                        @endif
                    </td>
                    <td>
                        {{ $complaint->complaint_text }}
                        @if($complaint->room)
                            <br><span style="font-size: 9px; color: #b91c1c; font-weight: bold;">Ruangan: {{ $complaint->room->name }}</span>
                        @endif
                        <br><span style="font-size: 9px; color: #64748b;">Tanggal: {{ $complaint->created_at->format('d-m-Y H:i') }}</span>
                    </td>
                    <td style="text-align: center;">
                        <span class="badge badge-{{ $complaint->status }}">
                            {{ $complaint->status === 'resolved' ? 'Selesai' : 'Pending' }}
                        </span>
                    </td>
                    <td>
                        @if($complaint->admin_response)
                            {{ $complaint->admin_response }}
                            @if($complaint->resolved_at)
                                <br><span style="font-size: 9px; color: #64748b;">Diselesaikan: {{ $complaint->resolved_at->format('d-m-Y H:i') }}</span>
                            @endif
                            @if($complaint->resolver)
                                <br><span style="font-size: 9px; color: #7f1d1d; font-weight: bold;">Admin: {{ $complaint->resolver->name }}</span>
                            @endif
                        @else
                            <span style="color: #94a3b8; font-style: italic;">Belum ada tindak lanjut</span>
                        @endif
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="7" style="text-align: center; color: #64748b;">Tidak ada data keluhan pada periode ini.</td>
                </tr>
            @endforelse
        </tbody>
    </table>

    <div class="summary-box">
        <h3 class="summary-title">Ringkasan Statistik</h3>
        <table class="summary-table">
            <tr>
                <td style="background-color: #fdf2f2; font-weight: bold; width: 33%;">Total Keluhan</td>
                <td style="background-color: #fdf2f2; font-weight: bold; width: 33%;">Pending</td>
                <td style="background-color: #fdf2f2; font-weight: bold; width: 34%;">Selesai (Resolved)</td>
            </tr>
            <tr>
                <td class="summary-value" style="text-align: center; font-size: 16px;">{{ $complaints->count() }}</td>
                <td class="summary-value" style="text-align: center; font-size: 16px; color: #a16207;">{{ $complaints->where('status', 'pending')->count() }}</td>
                <td class="summary-value" style="text-align: center; font-size: 16px; color: #15803d;">{{ $complaints->where('status', 'resolved')->count() }}</td>
            </tr>
        </table>
    </div>

    <div class="footer">
        Laporan Keluhan Pengguna - Halaman 1
    </div>
</body>
</html>