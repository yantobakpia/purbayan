<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sistem Peminjaman Ruangan & Keluhan</title>
    <link rel="manifest" href="/manifest.json">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
    <link rel="apple-touch-icon" href="/icon.svg">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }

        :root {
            --primary: #2563eb;
            --primary-dark: #1d4ed8;
            --primary-light: #eff6ff;
            --bg: #f8fafc;
            --card: #ffffff;
            --text: #0f172a;
            --muted: #64748b;
            --border: #e2e8f0;
            --success-bg: #f0fdf4;
            --success-text: #166534;
            --success-border: #bbf7d0;
            --danger-bg: #fef2f2;
            --danger-text: #991b1b;
            --danger-border: #fecaca;
            --warning-bg: #fffbeb;
            --warning-text: #92400e;
            --warning-border: #fde68a;
            --radius-lg: 16px;
            --radius-md: 10px;
            --radius-sm: 6px;
            --shadow-sm: 0 1px 3px rgba(0,0,0,0.05);
            --shadow-md: 0 4px 16px -2px rgba(15, 23, 42, 0.08);
            --shadow-hover: 0 20px 25px -5px rgba(15, 23, 42, 0.12), 0 8px 10px -6px rgba(15, 23, 42, 0.04);
        }

        body { 
            font-family: 'Plus Jakarta Sans', system-ui, -apple-system, sans-serif; 
            background: var(--bg); 
            color: var(--text); 
            line-height: 1.6; 
            -webkit-font-smoothing: antialiased;
        }

        .hero {
            background: linear-gradient(135deg, #0f172a 0%, #1e1b4b 50%, #1e3a8a 100%);
            color: white; 
            padding: 4rem 1.5rem 5rem 1.5rem; 
            text-align: center;
            position: relative;
            overflow: hidden;
        }
        .hero-title { 
            font-size: 2.75rem; 
            font-weight: 800; 
            letter-spacing: -0.025em; 
            line-height: 1.2;
            background: linear-gradient(to right, #ffffff, #93c5fd);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }
        .hero-subtitle { 
            margin: 1rem auto 2rem auto; 
            max-width: 600px;
            font-size: 1.15rem; 
            color: #cbd5e1; 
            font-weight: 400;
        }
        .hero-cta {
            display: flex;
            justify-content: center;
            gap: 1rem;
            flex-wrap: wrap;
        }

        nav {
            background: rgba(255, 255, 255, 0.9); 
            backdrop-filter: blur(12px);
            -webkit-backdrop-filter: blur(12px);
            border-bottom: 1px solid var(--border);
            display: flex; 
            justify-content: flex-start; 
            gap: 0.35rem;
            padding: 0.75rem 1rem; 
            position: sticky; 
            top: 0; 
            z-index: 100;
            box-shadow: 0 4px 12px rgba(0,0,0,0.03);
            overflow-x: auto;
            white-space: nowrap;
            -webkit-overflow-scrolling: touch;
        }
        nav::-webkit-scrollbar {
            display: none;
        }
        nav {
            -ms-overflow-style: none;
            scrollbar-width: none;
        }
        nav a, nav button, nav form {
            flex-shrink: 0;
        }
        @media (min-width: 769px) {
            nav {
                justify-content: center;
                gap: 0.5rem;
            }
        }
        nav a, nav button { 
            color: #475569; 
            text-decoration: none; 
            font-weight: 600; 
            font-size: 0.925rem;
            padding: 0.5rem 1.15rem; 
            border-radius: var(--radius-sm); 
            transition: all 0.2s ease;
            display: inline-flex;
            align-items: center;
            gap: 0.4rem;
        }
        nav a:hover, nav button:hover { 
            color: var(--primary); 
            background: var(--primary-light); 
        }
        nav a.active {
            color: var(--primary);
            background: var(--primary-light);
            font-weight: 700;
        }

        .container { max-width: 1180px; margin: 0 auto; padding: 2.5rem 1.5rem; }

        .section-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 1.75rem;
        }
        .section-title {
            font-size: 1.5rem; 
            font-weight: 800; 
            color: var(--text);
            letter-spacing: -0.02em;
            display: flex; 
            align-items: center; 
            gap: 0.6rem;
        }

        .alert { 
            padding: 1rem 1.25rem; 
            border-radius: var(--radius-md); 
            margin-bottom: 2rem; 
            font-weight: 600;
            font-size: 0.95rem;
            display: flex;
            align-items: center;
            gap: 0.75rem;
            box-shadow: var(--shadow-sm);
        }
        .alert-success { background: var(--success-bg); color: var(--success-text); border: 1px solid var(--success-border); }
        .alert-danger  { background: var(--danger-bg); color: var(--danger-text); border: 1px solid var(--danger-border); }

        .rooms-grid { 
            display: grid; 
            grid-template-columns: repeat(auto-fill, minmax(310px, 1fr)); 
            gap: 1.75rem; 
            margin-bottom: 3.5rem; 
        }
        .room-card { 
            background: var(--card); 
            border-radius: var(--radius-lg); 
            border: 1px solid var(--border);
            box-shadow: var(--shadow-md); 
            overflow: hidden; 
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            display: flex;
            flex-direction: column;
            cursor: pointer;
        }
        .room-card:hover { 
            transform: translateY(-6px); 
            box-shadow: var(--shadow-hover);
            border-color: #cbd5e1;
        }
        .room-card-image-wrap {
            position: relative;
            width: 100%;
            height: 200px;
            overflow: hidden;
            background: #f1f5f9;
        }
        .room-card img { 
            width: 100%; 
            height: 100%; 
            object-fit: cover;
            transition: transform 0.4s ease;
        }
        .room-card:hover img {
            transform: scale(1.05);
        }
        .room-card-placeholder { 
            width: 100%; 
            height: 100%; 
            background: linear-gradient(135deg, #e0e7ff 0%, #c7d2fe 100%); 
            display: flex; 
            align-items: center; 
            justify-content: center; 
            font-size: 3.5rem;
            color: #4338ca;
        }

        .room-card-body { 
            padding: 1.5rem; 
            display: flex;
            flex-direction: column;
            flex: 1;
        }
        .room-card-body h3 { 
            font-size: 1.25rem; 
            font-weight: 700; 
            margin-bottom: 0.5rem;
            letter-spacing: -0.01em;
        }
        .room-card-body h3 a {
            color: var(--text);
            text-decoration: none;
            transition: color 0.2s;
        }
        .room-card-body h3 a:hover {
            color: var(--primary);
        }

        .meta-tags {
            display: flex;
            flex-wrap: wrap;
            gap: 0.5rem;
            margin-bottom: 0.85rem;
        }
        .tag { 
            display: inline-flex; 
            align-items: center;
            gap: 0.35rem;
            font-size: 0.8rem; 
            font-weight: 600; 
            padding: 0.3rem 0.75rem; 
            border-radius: 30px; 
        }
        .tag-capacity { background: var(--primary-light); color: var(--primary-dark); border: 1px solid #bfdbfe; }
        .tag-quota-ok { background: var(--warning-bg); color: var(--warning-text); border: 1px solid var(--warning-border); }
        .tag-quota-full { background: var(--danger-bg); color: var(--danger-text); border: 1px solid var(--danger-border); }

        .badge-status { 
            display: inline-flex; 
            align-items: center;
            gap: 0.35rem;
            padding: 0.35rem 0.85rem; 
            border-radius: 30px; 
            font-size: 0.825rem; 
            font-weight: 700; 
            margin-bottom: 1rem;
            width: fit-content;
        }
        .status-available { background: var(--success-bg); color: var(--success-text); border: 1px solid var(--success-border); }
        .status-occupied { background: var(--danger-bg); color: var(--danger-text); border: 1px solid var(--danger-border); }
        .status-cleaning { background: var(--warning-bg); color: var(--warning-text); border: 1px solid var(--warning-border); }

        .room-desc { 
            color: var(--muted); 
            font-size: 0.9rem; 
            margin-bottom: 1.25rem;
            flex: 1;
            display: -webkit-box;
            -webkit-line-clamp: 3;
            -webkit-box-orient: vertical;
            overflow: hidden;
        }

        .btn-action {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 0.5rem;
            width: 100%;
            padding: 0.7rem 1.25rem;
            border-radius: var(--radius-md);
            font-weight: 700;
            font-size: 0.925rem;
            text-decoration: none;
            transition: all 0.2s;
        }
        .btn-book {
            background: var(--primary);
            color: white;
        }
        .btn-book:hover {
            background: var(--primary-dark);
            box-shadow: 0 4px 12px rgba(37, 99, 235, 0.25);
        }

        .table-card {
            background: var(--card);
            border-radius: var(--radius-lg);
            border: 1px solid var(--border);
            box-shadow: var(--shadow-md);
            overflow: hidden;
            margin-bottom: 3.5rem;
        }

        .calendar-filter-bar {
            display: flex;
            gap: 0.5rem;
            align-items: center;
            flex-wrap: wrap;
        }
        .cal-btn {
            padding: 0.4rem 0.85rem;
            border-radius: var(--radius-md);
            border: 1px solid var(--border);
            background: #ffffff;
            color: #475569;
            font-size: 0.85rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.2s;
        }
        .cal-btn:hover, .cal-btn.active {
            background: var(--primary);
            color: white;
            border-color: var(--primary);
        }
        .calendar-grid {
            display: grid;
            grid-template-columns: repeat(7, 1fr);
            gap: 0.4rem;
            text-align: center;
        }
        .cal-head {
            font-size: 0.75rem;
            font-weight: 700;
            color: var(--muted);
            text-transform: uppercase;
            padding: 0.4rem 0;
        }
        .cal-day-cell {
            padding: 0.6rem 0.2rem;
            border-radius: var(--radius-sm);
            border: 1px solid #f1f5f9;
            background: #f8fafc;
            font-size: 0.875rem;
            font-weight: 600;
            color: var(--text);
            cursor: pointer;
            transition: all 0.15s;
            position: relative;
        }
        .cal-day-cell:hover {
            background: var(--primary-light);
            color: var(--primary);
            border-color: #bfdbfe;
        }
        .cal-day-cell.is-today {
            background: #eff6ff;
            color: var(--primary);
            border-color: #93c5fd;
            font-weight: 800;
        }
        .cal-day-cell.is-selected {
            background: var(--primary) !important;
            color: white !important;
            border-color: var(--primary) !important;
        }
        .cal-day-cell.has-booking::after {
            content: '';
            display: block;
            width: 6px;
            height: 6px;
            background: #2563eb;
            border-radius: 50%;
            margin: 3px auto 0;
        }
        .cal-day-cell.is-selected.has-booking::after {
            background: white;
        }
        .cal-day-cell.empty {
            background: transparent;
            border-color: transparent;
            cursor: default;
        }
        .schedule-table { 
            width: 100%; 
            border-collapse: collapse; 
            text-align: left;
        }
        .schedule-table th { 
            background: #f8fafc; 
            color: #475569; 
            padding: 1rem 1.25rem; 
            font-size: 0.85rem; 
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.05em;
            border-bottom: 1px solid var(--border);
        }
        .schedule-table td { 
            padding: 1rem 1.25rem; 
            border-bottom: 1px solid var(--border); 
            font-size: 0.925rem; 
            color: var(--text);
        }
        .schedule-table tr:last-child td { border-bottom: none; }
        .schedule-table tr:hover td { background: #f1f5f9; }

        .time-pill {
            background: #f1f5f9;
            color: #334155;
            padding: 0.25rem 0.6rem;
            border-radius: var(--radius-sm);
            font-weight: 600;
            font-size: 0.85rem;
            font-family: monospace;
        }

        .btn-hero {
            padding: 0.85rem 1.75rem;
            border-radius: var(--radius-md);
            font-weight: 700;
            font-size: 1rem;
            text-decoration: none;
            transition: all 0.2s;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
        }
        .btn-hero-primary {
            background: var(--primary);
            color: white;
        }
        .btn-hero-primary:hover {
            background: var(--primary-dark);
            transform: translateY(-2px);
        }
        .btn-hero-secondary {
            background: rgba(255, 255, 255, 0.1);
            color: white;
            border: 1px solid rgba(255, 255, 255, 0.2);
            backdrop-filter: blur(8px);
        }
        .btn-hero-secondary:hover {
            background: rgba(255, 255, 255, 0.2);
            transform: translateY(-2px);
        }

        footer { 
            background: #0f172a; 
            color: #94a3b8; 
            text-align: center; 
            padding: 2.5rem 1.5rem; 
            font-size: 0.9rem; 
            border-top: 1px solid #1e293b;
        }

        @media (max-width: 768px) {
            .hero { padding: 2.5rem 1rem 3rem 1rem; }
            .hero-title { font-size: 1.85rem; }
            .hero-subtitle { font-size: 0.95rem; margin-bottom: 1.5rem; }
            .container { padding: 1.5rem 1rem; }
            .rooms-grid { grid-template-columns: 1fr; gap: 1.25rem; }
            .table-card { overflow-x: auto; -webkit-overflow-scrolling: touch; }
            .calendar-grid { gap: 0.2rem; }
            .cal-day-cell { padding: 0.4rem 0.1rem; font-size: 0.75rem; }
            .cal-head { font-size: 0.65rem; }
            .section-header { flex-direction: column; align-items: flex-start; gap: 0.75rem; }
            .calendar-filter-bar { width: 100%; justify-content: flex-start; }
        }
    </style>
</head>
<body>

<header class="hero">
    <h1 class="hero-title">Sistem Peminjaman Ruangan</h1>
    <p class="hero-subtitle">Platform peminjaman ruangan yang transparan, mudah, dan terintegrasi secara real-time</p>
    <div class="hero-cta">
        <a href="{{ route('peminjaman.page') }}" class="btn-hero btn-hero-primary">Pesan Ruangan</a>
        <a href="{{ route('complaint.page') }}" class="btn-hero btn-hero-secondary">Ajukan Keluhan</a>
    </div>
</header>

<nav>
    <a href="/" class="active">Beranda</a>
    <a href="{{ route('jadwal.page') }}">Status & Kalender</a>
    <a href="{{ route('peminjaman.page') }}">Pesan Ruangan</a>
    <a href="{{ route('complaint.page') }}">Keluhan</a>
    @auth
        <a href="/user/profile">Profil Saya</a>
        @if(auth()->user()->is_admin || auth()->user()->email === 'admin@ruangan.com')
            <a href="/admin" style="color: var(--danger-text); background: var(--danger-bg); font-weight: 700;">Dashboard Admin</a>
        @endif
        <form action="{{ route('filament.user.auth.logout') }}" method="POST" style="display: inline;">
            @csrf
            <button type="submit" style="background: none; border: none; font-family: inherit; font-size: inherit; cursor: pointer;">Logout</button>
        </form>
    @else
        <a href="/user/login">Login</a>
    @endauth
</nav>

<div class="container">

    @if(session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif
    @if($errors->has('conflict'))
        <div class="alert alert-danger">{{ $errors->first('conflict') }}</div>
    @endif
    @if($errors->has('booking_error'))
        <div class="alert alert-danger">{{ $errors->first('booking_error') }}</div>
    @endif

    <section id="ruangan">
        <div class="section-header">
            <h2 class="section-title">Daftar Ruangan</h2>
        </div>

        @if($rooms->isEmpty())
            <p style="color:var(--muted); margin-bottom:2rem;">Belum ada ruangan yang terdaftar.</p>
        @else
        <div class="rooms-grid">
            @foreach($rooms as $room)
            <div class="room-card" onclick="window.location.href='{{ route('peminjaman.page', ['room_id' => $room->id]) }}'">
                <div class="room-card-image-wrap">
                    @if($room->image_path)
                        <img src="{{ asset('storage/' . $room->image_path) }}" alt="{{ $room->name }}">
                    @else
                        <div class="room-card-placeholder">
                            <svg width="48" height="48" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path stroke-linecap="round" stroke-linejoin="round" d="M3.75 21h16.5M4.5 3h15M5.25 3v18m13.5-18v18M9 6.75h1.5m-1.5 3h1.5m-1.5 3h1.5m-1.5 3h1.5m4.5-12h1.5m-1.5 3h1.5m-1.5 3h1.5m-1.5 3h1.5"></path></svg>
                        </div>
                    @endif
                </div>

                <div class="room-card-body">
                    <h3>
                        {{ $room->name }}
                    </h3>

                    @php
                        $nowTime = now()->format('H:i:s');
                        $hasActiveBooking = $room->current_booking_id || $room->bookings()
                            ->where('date', today())
                            ->where('status', 'approved')
                            ->where('start_time', '<=', $nowTime)
                            ->where('end_time', '>', $nowTime)
                            ->exists();
                        $isOccupied = $room->is_occupied || $hasActiveBooking;
                        $isCleaning = $room->is_cleaning;
                    @endphp

                    <div class="meta-tags">
                        <span class="tag tag-capacity">Kapasitas: {{ $room->capacity }} Orang</span>
                    </div>

                    @if($isCleaning)
                        <div class="badge-status status-cleaning">Sedang Dibersihkan</div>
                    @elseif($isOccupied)
                        <div class="badge-status status-occupied">Sedang Digunakan</div>
                    @else
                        <div class="badge-status status-available">Tersedia</div>
                    @endif

                    @if($room->description)
                        <p class="room-desc">{{ $room->description }}</p>
                    @endif

                   
                </div>
            </div>
            @endforeach
        </div>
        @endif
    </section>

    <section id="jadwal">
        <div class="section-header">
            <h2 class="section-title">Jadwal Peminjaman Disetujui</h2>
        </div>

        @if($approvedBookings->isEmpty())
            <p style="color:var(--muted); margin-bottom:2rem;">Belum ada jadwal peminjaman yang disetujui.</p>
        @else
        <div class="table-card">
            <div style="overflow-x: auto;">
                <table class="schedule-table">
                    <thead>
                        <tr>
                            <th>Ruangan</th>
                            <th>Tanggal</th>
                            <th>Jam</th>
                            <th>Peminjam</th>
                            <th>Keperluan</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($approvedBookings as $b)
                        <tr>
                            <td><strong>{{ $b->room->name }}</strong></td>
                            <td>{{ $b->date->format('d M Y') }}</td>
                            <td><span class="time-pill">{{ substr($b->start_time, 0, 5) }} – {{ substr($b->end_time, 0, 5) }}</span></td>
                            <td>{{ $b->renter_name }}</td>
                            <td>{{ Str::limit($b->purpose, 45) }}</td>
                            <td>
                                <span class="badge-status status-available" style="margin-bottom:0; font-size:0.75rem;">Disetujui</span>
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
        @endif
    </section>

</div>

<footer>
    <p>© {{ date('Y') }} Sistem Peminjaman Ruangan & Keluhan • All rights reserved.</p>
</footer>

<script>
    (function() {
        let currentHash = null;

        async function checkAutoRefresh() {
            try {
                const response = await fetch('{{ route("check-status") }}');
                if (response.ok) {
                    const data = await response.json();
                    if (currentHash === null) {
                        currentHash = data.hash;
                    } else if (currentHash !== data.hash) {
                        window.location.reload();
                    }
                }
            } catch (error) {
                console.error('Auto refresh check error:', error);
            }
        }

        setInterval(checkAutoRefresh, 10000);
        checkAutoRefresh();
    })();
</script>
<script>
    if ('serviceWorker' in navigator) {
        window.addEventListener('load', () => {
            navigator.serviceWorker.register('/sw.js')
                .then(reg => console.log('Service Worker registered successfully.', reg))
                .catch(err => console.log('Service Worker registration failed.', err));
        });
    }
</script>

</body>
</html>

