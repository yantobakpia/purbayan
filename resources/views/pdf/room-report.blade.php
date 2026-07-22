<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Laporan Data Ruangan</title>
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
            border-bottom: 3px double #0d9488;
            padding-bottom: 10px;
            margin-bottom: 20px;
        }
        .header table {
            width: 100%;
        }
        .header-title {
            font-size: 20px;
            font-weight: bold;
            color: #0d9488;
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
            background-color: #f0fdf4;
            border: 1px solid #bbf7d0;
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
            color: #14532d;
            width: 120px;
        }
        .table-data {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
        }
        .table-data th {
            background-color: #0d9488;
            color: #ffffff;
            font-weight: bold;
            text-align: left;
            padding: 8px;
            border: 1px solid #0d9488;
            font-size: 11px;
        }
        .table-data td {
            padding: 8px;
            border: 1px solid #bbf7d0;
            font-size: 11px;
            vertical-align: top;
        }
        .table-data tr:nth-child(even) {
            background-color: #f0fdf4;
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
        .badge-occupied {
            background-color: #fee2e2;
            color: #b91c1c;
        }
        .badge-available {
            background-color: #dcfce7;
            color: #15803d;
        }
        .badge-cleaning {
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
            color: #0d9488;
            margin-bottom: 10px;
            border-bottom: 1px solid #bbf7d0;
            padding-bottom: 5px;
        }
        .summary-table {
            width: 100%;
            border-collapse: collapse;
        }
        .summary-table td {
            padding: 6px 12px;
            border: 1px solid #bbf7d0;
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
                    <h1 class="header-title">Laporan Data Ruangan</h1>
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
                <td class="meta-label">Total Ruangan</td>
                <td>: {{ $rooms->count() }} Ruangan</td>
                <td class="meta-label">Kapasitas Maksimal</td>
                <td>: {{ $rooms->max('capacity') }} Orang</td>
            </tr>
        </table>
    </div>

    <table class="table-data">
        <thead>
            <tr>
                <th style="width: 5%;">No</th>
                <th style="width: 25%;">Nama Ruangan</th>
                <th style="width: 15%;">Kapasitas</th>
                <th style="width: 40%;">Deskripsi</th>
                <th style="width: 15%;">Status Saat Ini</th>
            </tr>
        </thead>
        <tbody>
            @forelse($rooms as $index => $room)
                <tr>
                    <td style="text-align: center;">{{ $index + 1 }}</td>
                    <td><strong>{{ $room->name }}</strong></td>
                    <td style="text-align: center;">{{ $room->capacity }} Orang</td>
                    <td>{{ $room->description ?? '-' }}</td>
                    <td style="text-align: center;">
                        @if($room->is_cleaning)
                            <span class="badge badge-cleaning">Pembersihan</span>
                        @elseif($room->is_occupied)
                            <span class="badge badge-occupied">Digunakan</span>
                        @else
                            <span class="badge badge-available">Tersedia</span>
                        @endif
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="5" style="text-align: center; color: #64748b;">Tidak ada data ruangan.</td>
                </tr>
            @endforelse
        </tbody>
    </table>

    <div class="summary-box">
        <h3 class="summary-title">Ringkasan Statistik</h3>
        <table class="summary-table">
            <tr>
                <td style="background-color: #f0fdf4; font-weight: bold; width: 25%;">Total Ruangan</td>
                <td class="summary-value" style="width: 25%;">{{ $rooms->count() }}</td>
                <td style="background-color: #f0fdf4; font-weight: bold; width: 25%;">Tersedia (Available)</td>
                <td class="summary-value" style="color: #15803d; width: 25%;">{{ $rooms->where('is_occupied', false)->where('is_cleaning', false)->count() }}</td>
            </tr>
            <tr>
                <td style="background-color: #f0fdf4; font-weight: bold;">Digunakan (Occupied)</td>
                <td class="summary-value" style="color: #b91c1c;">{{ $rooms->where('is_occupied', true)->count() }}</td>
                <td style="background-color: #f0fdf4; font-weight: bold;">Pembersihan (Cleaning)</td>
                <td class="summary-value" style="color: #a16207;">{{ $rooms->where('is_cleaning', true)->count() }}</td>
            </tr>
        </table>
    </div>

    <div class="footer">
        Laporan Data Ruangan - Halaman 1
    </div>
</body>
</html>