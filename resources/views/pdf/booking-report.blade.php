<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Laporan Peminjaman Ruangan</title>
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
            border-bottom: 3px double #1e3a8a;
            padding-bottom: 10px;
            margin-bottom: 20px;
        }
        .header table {
            width: 100%;
        }
        .header-title {
            font-size: 20px;
            font-weight: bold;
            color: #1e3a8a;
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
            background-color: #f8fafc;
            border: 1px solid #e2e8f0;
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
            color: #475569;
            width: 120px;
        }
        .table-data {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
        }
        .table-data th {
            background-color: #1e3a8a;
            color: #ffffff;
            font-weight: bold;
            text-align: left;
            padding: 8px;
            border: 1px solid #1e3a8a;
            font-size: 11px;
        }
        .table-data td {
            padding: 8px;
            border: 1px solid #e2e8f0;
            font-size: 11px;
        }
        .table-data tr:nth-child(even) {
            background-color: #f8fafc;
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
        .badge-approved {
            background-color: #dcfce7;
            color: #15803d;
        }
        .badge-pending {
            background-color: #fef9c3;
            color: #a16207;
        }
        .badge-rejected {
            background-color: #fee2e2;
            color: #b91c1c;
        }
        .badge-checked_in {
            background-color: #dbeafe;
            color: #1d4ed8;
        }
        .badge-selesai {
            background-color: #f3f4f6;
            color: #374151;
        }
        .summary-box {
            margin-top: 20px;
            page-break-inside: avoid;
        }
        .summary-title {
            font-size: 14px;
            font-weight: bold;
            color: #1e3a8a;
            margin-bottom: 10px;
            border-bottom: 1px solid #e2e8f0;
            padding-bottom: 5px;
        }
        .summary-table {
            width: 100%;
            border-collapse: collapse;
        }
        .summary-table td {
            padding: 6px 12px;
            border: 1px solid #e2e8f0;
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
                    <h1 class="header-title">Laporan Peminjaman Ruangan</h1>
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
                <td>: {{ $bookings->count() }} Transaksi</td>
            </tr>
            <tr>
                <td class="meta-label">Filter Ruangan</td>
                <td>: {{ $room_name ?? 'Semua Ruangan' }}</td>
                <td class="meta-label">Status</td>
                <td>: {{ $status_filter ?? 'Semua Status' }}</td>
            </tr>
        </table>
    </div>

    <table class="table-data">
        <thead>
            <tr>
                <th style="width: 5%;">No</th>
                <th style="width: 20%;">Peminjam</th>
                <th style="width: 15%;">Ruangan</th>
                <th style="width: 12%;">Tanggal</th>
                <th style="width: 13%;">Waktu</th>
                <th style="width: 23%;">Keperluan</th>
                <th style="width: 12%;">Status</th>
            </tr>
        </thead>
        <tbody>
            @forelse($bookings as $index => $booking)
                <tr>
                    <td style="text-align: center;">{{ $index + 1 }}</td>
                    <td>
                        <strong>{{ $booking->renter_name }}</strong><br>
                        <span style="font-size: 9px; color: #64748b;">{{ $booking->renter_phone }}</span>
                    </td>
                    <td>{{ $booking->room?->name ?? 'N/A' }}</td>
                    <td style="text-align: center;">{{ \Carbon\Carbon::parse($booking->date)->format('d-m-Y') }}</td>
                    <td style="text-align: center;">{{ substr($booking->start_time, 0, 5) }} - {{ substr($booking->end_time, 0, 5) }}</td>
                    <td>{{ $booking->purpose }}</td>
                    <td style="text-align: center;">
                        <span class="badge badge-{{ $booking->status }}">
                            {{ $booking->status }}
                        </span>
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="7" style="text-align: center; color: #64748b;">Tidak ada data peminjaman pada periode ini.</td>
                </tr>
            @endforelse
        </tbody>
    </table>

    <div class="summary-box">
        <h3 class="summary-title">Ringkasan Statistik</h3>
        <table class="summary-table">
            <tr>
                <td style="background-color: #f8fafc; font-weight: bold; width: 25%;">Total Pengajuan</td>
                <td class="summary-value" style="width: 25%;">{{ $bookings->count() }}</td>
                <td style="background-color: #f8fafc; font-weight: bold; width: 25%;">Disetujui (Approved)</td>
                <td class="summary-value" style="color: #15803d; width: 25%;">{{ $bookings->where('status', 'approved')->count() }}</td>
            </tr>
            <tr>
                <td style="background-color: #f8fafc; font-weight: bold;">Pending</td>
                <td class="summary-value" style="color: #a16207;">{{ $bookings->where('status', 'pending')->count() }}</td>
                <td style="background-color: #f8fafc; font-weight: bold;">Ditolak (Rejected)</td>
                <td class="summary-value" style="color: #b91c1c;">{{ $bookings->where('status', 'rejected')->count() }}</td>
            </tr>
            <tr>
                <td style="background-color: #f8fafc; font-weight: bold;">Checked In</td>
                <td class="summary-value" style="color: #1d4ed8;">{{ $bookings->where('status', 'checked_in')->count() }}</td>
                <td style="background-color: #f8fafc; font-weight: bold;">Selesai</td>
                <td class="summary-value" style="color: #374151;">{{ $bookings->where('status', 'selesai')->count() }}</td>
            </tr>
        </table>
    </div>

    <div class="footer">
        Laporan Peminjaman Ruangan - Halaman 1
    </div>
</body>
</html>