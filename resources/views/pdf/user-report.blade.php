<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Laporan Data Pengguna</title>
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
            border-bottom: 3px double #4f46e5;
            padding-bottom: 10px;
            margin-bottom: 20px;
        }
        .header table {
            width: 100%;
        }
        .header-title {
            font-size: 20px;
            font-weight: bold;
            color: #4f46e5;
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
            background-color: #e0e7ff;
            border: 1px solid #c7d2fe;
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
            color: #312e81;
            width: 120px;
        }
        .table-data {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
        }
        .table-data th {
            background-color: #4f46e5;
            color: #ffffff;
            font-weight: bold;
            text-align: left;
            padding: 8px;
            border: 1px solid #4f46e5;
            font-size: 11px;
        }
        .table-data td {
            padding: 8px;
            border: 1px solid #c7d2fe;
            font-size: 11px;
            vertical-align: top;
        }
        .table-data tr:nth-child(even) {
            background-color: #e0e7ff;
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
        .badge-admin {
            background-color: #fee2e2;
            color: #b91c1c;
        }
        .badge-user {
            background-color: #dcfce7;
            color: #15803d;
        }
        .summary-box {
            margin-top: 20px;
            page-break-inside: avoid;
        }
        .summary-title {
            font-size: 14px;
            font-weight: bold;
            color: #4f46e5;
            margin-bottom: 10px;
            border-bottom: 1px solid #c7d2fe;
            padding-bottom: 5px;
        }
        .summary-table {
            width: 100%;
            border-collapse: collapse;
        }
        .summary-table td {
            padding: 6px 12px;
            border: 1px solid #c7d2fe;
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
                    <h1 class="header-title">Laporan Data Pengguna</h1>
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
                <td class="meta-label">Total Pengguna</td>
                <td>: {{ $users->count() }} Orang</td>
                <td class="meta-label">Admin</td>
                <td>: {{ $users->where('is_admin', true)->count() }} Orang</td>
            </tr>
        </table>
    </div>

    <table class="table-data">
        <thead>
            <tr>
                <th style="width: 10%;">No</th>
                <th style="width: 35%;">Nama Lengkap</th>
                <th style="width: 30%;">Email</th>
                <th style="width: 15%;">Role</th>
                <th style="width: 20%;">Tanggal Terdaftar</th>
            </tr>
        </thead>
        <tbody>
            @forelse($users as $index => $user)
                <tr>
                    <td style="text-align: center;">{{ $index + 1 }}</td>
                    <td><strong>{{ $user->name }}</strong></td>
                    <td>{{ $user->email }}</td>
                    <td style="text-align: center;">
                        @if($user->is_admin)
                            <span class="badge badge-admin">Admin</span>
                        @else
                            <span class="badge badge-user">User</span>
                        @endif
                    </td>
                    <td style="text-align: center;">{{ $user->created_at->format('d-m-Y H:i') }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="5" style="text-align: center; color: #64748b;">Tidak ada data pengguna.</td>
                </tr>
            @endforelse
        </tbody>
    </table>

    <div class="summary-box">
        <h3 class="summary-title">Ringkasan Statistik</h3>
        <table class="summary-table">
            <tr>
                <td style="background-color: #e0e7ff; font-weight: bold; width: 33%;">Total Pengguna</td>
                <td style="background-color: #e0e7ff; font-weight: bold; width: 33%;">Admin</td>
                <td style="background-color: #e0e7ff; font-weight: bold; width: 34%;">Regular User</td>
            </tr>
            <tr>
                <td class="summary-value" style="text-align: center; font-size: 16px;">{{ $users->count() }}</td>
                <td class="summary-value" style="text-align: center; font-size: 16px; color: #b91c1c;">{{ $users->where('is_admin', true)->count() }}</td>
                <td class="summary-value" style="text-align: center; font-size: 16px; color: #15803d;">{{ $users->where('is_admin', false)->count() }}</td>
            </tr>
        </table>
    </div>

    <div class="footer">
        Laporan Data Pengguna - Halaman 1
    </div>
</body>
</html>