<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Detail Keluhan #{{ $complaint->id }}</title>
    <style>
        body {
            font-family: 'Helvetica Neue', Helvetica, Arial, sans-serif;
            color: #333;
            font-size: 12px;
            line-height: 1.5;
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
        .info-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
        }
        .info-table td {
            padding: 10px 12px;
            border: 1px solid #fecaca;
            vertical-align: top;
        }
        .info-label {
            font-weight: bold;
            color: #7f1d1d;
            width: 25%;
            background-color: #fdf2f2;
        }
        .info-value {
            width: 75%;
        }
        .badge {
            display: inline-block;
            padding: 3px 8px;
            font-size: 10px;
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
            <tbody>
                <tr>
                    <td>
                        <h1 class="header-title">Laporan Detail Keluhan</h1>
                        <p class="header-subtitle">Sistem Informasi Peminjaman Ruangan & Keluhan</p>
                    </td>
                    <td style="text-align: right; vertical-align: bottom; color: #666; font-size: 10px;">
                        ID Keluhan: #{{ $complaint->id }}<br>
                        Dicetak pada: {{ now()->format('d-m-Y H:i') }}
                    </td>
                </tr>
            </tbody>
        </table>
    </div>

    <table class="info-table">
        <tbody>
            <tr>
                <td class="info-label">Nama</td>
                <td class="info-value"><strong>{{ $complaint->name }}</strong></td>
            </tr>
            <tr>
                <td class="info-label">Kontak</td>
                <td class="info-value">{{ $complaint->email_or_phone }}</td>
            </tr>
            <tr>
                <td class="info-label">Foto</td>
                <td class="info-value">
                    @if($photoBase64)
                        <img src="{{ $photoBase64 }}" style="max-width: 250px; max-height: 250px; border-radius: 4px; border: 1px solid #fecaca;" alt="Foto Keluhan">
                    @else
                        <span style="color: #94a3b8; font-style: italic;">Tidak Ada Foto</span>
                    @endif
                </td>
            </tr>
            <tr>
                <td class="info-label">Keluhan</td>
                <td class="info-value">
                    <span>{{ $complaint->complaint_text }}</span>
                    <br><span style="font-size: 10px; color: #64748b;">Waktu Laporan: {{ $complaint->created_at->format('d-m-Y H:i') }}</span>
                </td>
            </tr>
            <tr>
                <td class="info-label">Status</td>
                <td class="info-value">
                    <span class="badge badge-{{ $complaint->status }}">
                        {{ $complaint->status === 'resolved' ? 'Selesai' : 'Pending' }}
                    </span>
                </td>
            </tr>
            <tr>
                <td class="info-label">Penyelesaian Admin</td>
                <td class="info-value">
                    @if($complaint->admin_response)
                        <span>{{ $complaint->admin_response }}</span>
                        @if($complaint->resolved_at)
                            <br><span style="font-size: 10px; color: #64748b;">Waktu Penyelesaian: {{ $complaint->resolved_at->format('d-m-Y H:i') }}</span>
                        @endif
                        @if($complaint->resolver)
                            <br><span style="font-size: 10px; color: #7f1d1d; font-weight: bold;">Admin Penanggung Jawab: {{ $complaint->resolver->name }}</span>
                        @endif
                    @else
                        <span style="color: #94a3b8; font-style: italic;">Belum ada tindak lanjut dari admin</span>
                    @endif
                </td>
            </tr>
        </tbody>
    </table>

    <div class="footer">
        Laporan Detail Keluhan Pengguna #{{ $complaint->id }} - Halaman 1
    </div>
</body>
</html>