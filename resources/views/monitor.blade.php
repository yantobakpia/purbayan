<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Monitor Status Ruangan - TV Display</title>
    <link rel="manifest" href="/manifest.json">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
    <link rel="apple-touch-icon" href="/icon.svg">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@500;600;700;800;900&display=swap" rel="stylesheet">
    <style>
        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }

        :root {
            --bg-main: #090d16;
            --bg-card: #131b2e;
            --bg-card-hover: #1a253e;
            --border-card: #26334d;
            --text-main: #ffffff;
            --text-muted: #a0aec0;
            --text-dim: #64748b;
            
            --available-color: #10b981;
            --available-bg: rgba(16, 185, 129, 0.15);
            --available-border: rgba(16, 185, 129, 0.5);
            --available-glow: rgba(16, 185, 129, 0.25);

            --occupied-color: #ff3355;
            --occupied-bg: rgba(255, 51, 85, 0.15);
            --occupied-border: rgba(255, 51, 85, 0.5);
            --occupied-glow: rgba(255, 51, 85, 0.25);

            --cleaning-color: #f59e0b;
            --cleaning-bg: rgba(245, 158, 11, 0.15);
            --cleaning-border: rgba(245, 158, 11, 0.5);
            --cleaning-glow: rgba(245, 158, 11, 0.25);
        }

        body {
            font-family: 'Plus Jakarta Sans', system-ui, -apple-system, sans-serif;
            background-color: var(--bg-main);
            color: var(--text-main);
            min-height: 100vh;
            display: flex;
            flex-direction: column;
            padding: 1.75rem 2.25rem;
            overflow-x: hidden;
        }

        /* Top Progress Bar for Auto-refresh */
        .refresh-progress-bar {
            position: fixed;
            top: 0;
            left: 0;
            height: 4px;
            background: linear-gradient(90deg, #3b82f6, #10b981);
            width: 100%;
            transform-origin: left;
            animation: shrink 30s linear infinite;
            z-index: 9999;
        }

        @keyframes shrink {
            from { transform: scaleX(1); }
            to { transform: scaleX(0); }
        }

        /* Header */
        header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 2rem;
            padding-bottom: 1.5rem;
            border-bottom: 2px solid var(--border-card);
            gap: 1.5rem;
            flex-wrap: nowrap;
        }

        .brand-title {
            display: flex;
            align-items: center;
            gap: 1.25rem;
            flex-shrink: 0;
        }

        .brand-icon {
            width: 56px;
            height: 56px;
            background: linear-gradient(135deg, #2563eb, #1d4ed8);
            border-radius: 16px;
            display: flex;
            align-items: center;
            justify-content: center;
            box-shadow: 0 0 25px rgba(37, 99, 235, 0.5);
        }

        .brand-icon svg {
            width: 32px;
            height: 32px;
            stroke: #ffffff;
        }

        .brand-text h1 {
            font-size: 2.2rem;
            font-weight: 900;
            letter-spacing: -0.02em;
            color: #ffffff;
            display: flex;
            align-items: center;
            gap: 1rem;
            white-space: nowrap;
        }

        .live-badge {
            font-size: 0.85rem;
            font-weight: 800;
            text-transform: uppercase;
            letter-spacing: 0.1em;
            background: rgba(255, 51, 85, 0.25);
            color: #ff4d6d;
            border: 1.5px solid rgba(255, 51, 85, 0.6);
            padding: 0.3rem 0.8rem;
            border-radius: 999px;
            display: inline-flex;
            align-items: center;
            gap: 0.45rem;
            box-shadow: 0 0 15px rgba(255, 51, 85, 0.3);
            white-space: nowrap;
        }

        .live-dot {
            width: 9px;
            height: 9px;
            background-color: #ff3355;
            border-radius: 50%;
            animation: pulse-dot 1.2s ease-in-out infinite;
        }

        @keyframes pulse-dot {
            0%, 100% { opacity: 1; transform: scale(1); box-shadow: 0 0 10px #ff3355; }
            50% { opacity: 0.3; transform: scale(1.4); }
        }

        /* Stats Bar */
        .stats-bar {
            display: flex;
            align-items: center;
            gap: 0.75rem;
            flex-wrap: nowrap;
            flex-shrink: 1;
        }

        .stat-chip {
            background: var(--bg-card);
            border: 1.5px solid var(--border-card);
            border-radius: 14px;
            padding: 0.55rem 1rem;
            display: flex;
            align-items: center;
            gap: 0.6rem;
            font-size: 0.95rem;
            font-weight: 700;
            color: var(--text-muted);
            white-space: nowrap;
        }

        .stat-chip .count {
            font-size: 1.2rem;
            font-weight: 900;
            color: var(--text-main);
        }

        .stat-chip.available { border-color: rgba(16, 185, 129, 0.4); }
        .stat-chip.available .count { color: var(--available-color); }

        .stat-chip.occupied { border-color: rgba(255, 51, 85, 0.4); }
        .stat-chip.occupied .count { color: var(--occupied-color); }

        .stat-chip.cleaning { border-color: rgba(245, 158, 11, 0.4); }
        .stat-chip.cleaning .count { color: var(--cleaning-color); }

        /* Header Right Container (Fixed Jam & Fullscreen) */
        .header-right {
            display: flex;
            align-items: center;
            gap: 1.25rem;
            flex-shrink: 0;
            margin-left: auto;
        }

        /* Time Display */
        .time-box {
            display: flex;
            flex-direction: column;
            align-items: flex-end;
            gap: 0.2rem;
            background: var(--bg-card);
            padding: 0.6rem 1.5rem;
            border-radius: 16px;
            border: 1.5px solid var(--border-card);
        }

        .time-display {
            font-size: 2.5rem;
            font-weight: 900;
            letter-spacing: 0.04em;
            font-variant-numeric: tabular-nums;
            color: #ffffff;
            line-height: 1;
            text-shadow: 0 0 20px rgba(255, 255, 255, 0.2);
        }

        .date-display {
            font-size: 0.95rem;
            font-weight: 700;
            color: var(--text-muted);
            text-transform: capitalize;
        }

        .btn-fullscreen {
            background: var(--bg-card);
            border: 1.5px solid var(--border-card);
            color: var(--text-muted);
            width: 50px;
            height: 50px;
            border-radius: 14px;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            transition: all 0.2s ease;
        }

        .btn-fullscreen:hover {
            background: var(--bg-card-hover);
            color: var(--text-main);
            border-color: #475569;
        }

        .btn-fullscreen svg {
            width: 24px;
            height: 24px;
            stroke: currentColor;
        }

        /* Rooms Grid */
        .rooms-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(380px, 1fr));
            gap: 1.75rem;
            flex: 1;
        }

        .room-card {
            background-color: var(--bg-card);
            border-radius: 24px;
            border: 2px solid var(--border-card);
            padding: 2rem;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
            transition: all 0.3s ease;
            position: relative;
            overflow: hidden;
            box-shadow: 0 15px 35px -10px rgba(0, 0, 0, 0.5);
        }

        .room-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 6px;
            background: var(--border-card);
            transition: background 0.3s ease;
        }

        /* Available State */
        .room-card.available {
            border-color: var(--available-border);
            box-shadow: 0 10px 35px -5px var(--available-glow);
        }
        .room-card.available::before {
            background: var(--available-color);
        }

        /* Occupied State */
        .room-card.occupied {
            border-color: var(--occupied-border);
            box-shadow: 0 10px 35px -5px var(--occupied-glow);
        }
        .room-card.occupied::before {
            background: var(--occupied-color);
        }

        /* Cleaning State */
        .room-card.cleaning {
            border-color: var(--cleaning-border);
            box-shadow: 0 10px 35px -5px var(--cleaning-glow);
        }
        .room-card.cleaning::before {
            background: var(--cleaning-color);
        }

        .card-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            gap: 1rem;
            margin-bottom: 1.5rem;
        }

        .room-title {
            font-size: 2.1rem;
            font-weight: 900;
            color: #ffffff;
            line-height: 1.15;
            letter-spacing: -0.01em;
        }

        .room-capacity {
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            font-size: 1rem;
            font-weight: 700;
            color: #e2e8f0;
            background: rgba(255, 255, 255, 0.08);
            padding: 0.45rem 0.9rem;
            border-radius: 999px;
            white-space: nowrap;
            border: 1px solid rgba(255, 255, 255, 0.1);
        }

        .room-capacity svg {
            width: 18px;
            height: 18px;
            stroke: #cbd5e1;
        }

        /* Status Badge */
        .status-badge {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 0.75rem;
            font-size: 1.3rem;
            font-weight: 900;
            text-transform: uppercase;
            letter-spacing: 0.06em;
            padding: 1rem 1.25rem;
            border-radius: 16px;
            width: 100%;
            text-align: center;
            margin-bottom: 1.5rem;
            box-shadow: 0 4px 15px rgba(0,0,0,0.2);
        }

        .room-card.available .status-badge {
            background-color: var(--available-bg);
            color: #34d399;
            border: 2px solid var(--available-border);
        }

        .room-card.occupied .status-badge {
            background-color: var(--occupied-bg);
            color: #ff6b81;
            border: 2px solid var(--occupied-border);
        }

        .room-card.cleaning .status-badge {
            background-color: var(--cleaning-bg);
            color: #fbbf24;
            border: 2px solid var(--cleaning-border);
        }

        .status-badge-dot {
            width: 12px;
            height: 12px;
            border-radius: 50%;
            display: inline-block;
        }

        .room-card.available .status-badge-dot { background-color: var(--available-color); box-shadow: 0 0 12px var(--available-color); }
        .room-card.occupied .status-badge-dot { background-color: var(--occupied-color); box-shadow: 0 0 12px var(--occupied-color); animation: pulse-dot 1.2s infinite; }
        .room-card.cleaning .status-badge-dot { background-color: var(--cleaning-color); box-shadow: 0 0 12px var(--cleaning-color); }

        /* Current Active Booking Details */
        .active-booking-details {
            background: rgba(255, 51, 85, 0.08);
            border: 1.5px solid rgba(255, 51, 85, 0.3);
            border-radius: 16px;
            padding: 1.25rem;
            margin-bottom: 1.25rem;
        }

        .active-label {
            font-size: 0.85rem;
            font-weight: 800;
            color: #ff6b81;
            text-transform: uppercase;
            letter-spacing: 0.06em;
            margin-bottom: 0.6rem;
            display: flex;
            align-items: center;
            gap: 0.45rem;
        }

        .active-user {
            font-size: 1.4rem;
            font-weight: 800;
            color: #ffffff;
            margin-bottom: 0.4rem;
            line-height: 1.2;
        }

        .active-purpose {
            font-size: 1.1rem;
            font-weight: 600;
            color: #cbd5e1;
            margin-bottom: 0.75rem;
            display: -webkit-box;
            -webkit-line-clamp: 2;
            -webkit-box-orient: vertical;
            overflow: hidden;
        }

        .active-time {
            display: inline-flex;
            align-items: center;
            gap: 0.45rem;
            font-size: 1.1rem;
            font-weight: 800;
            color: #ffffff;
            background: rgba(255, 255, 255, 0.12);
            padding: 0.4rem 0.85rem;
            border-radius: 10px;
            border: 1px solid rgba(255, 255, 255, 0.15);
        }

        /* Next Booking Box */
        .next-booking {
            background: rgba(255, 255, 255, 0.04);
            border: 1.5px solid rgba(255, 255, 255, 0.08);
            border-radius: 16px;
            padding: 1.1rem 1.25rem;
            border-left: 4px solid #3b82f6;
        }

        .next-booking .next-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 0.4rem;
        }

        .next-booking .next-label {
            font-size: 0.85rem;
            color: #60a5fa;
            font-weight: 800;
            text-transform: uppercase;
            letter-spacing: 0.06em;
            display: flex;
            align-items: center;
            gap: 0.45rem;
        }

        .next-booking .next-time {
            font-size: 1rem;
            font-weight: 800;
            color: #93c5fd;
            background: rgba(59, 130, 246, 0.2);
            padding: 0.2rem 0.6rem;
            border-radius: 6px;
        }

        .next-booking .next-info {
            font-size: 1.15rem;
            font-weight: 700;
            color: #ffffff;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }

        /* Footer */
        footer {
            margin-top: 3rem;
            padding-top: 1.5rem;
            border-top: 2px solid var(--border-card);
            display: flex;
            justify-content: space-between;
            align-items: center;
            color: var(--text-muted);
            font-size: 1.05rem;
            font-weight: 600;
            flex-wrap: wrap;
            gap: 1rem;
        }

        /* Optimization for Large TV Monitors (Full HD & 4K) */
        @media (min-width: 1600px) {
            body { padding: 2.5rem 3.5rem; }
            header { margin-bottom: 2.5rem; padding-bottom: 2rem; }
            .brand-text h1 { font-size: 2.6rem; }
            .brand-icon { width: 64px; height: 64px; }
            .brand-icon svg { width: 36px; height: 36px; }
            .time-display { font-size: 3rem; }
            .date-display { font-size: 1.1rem; }
            .stat-chip { font-size: 1.2rem; padding: 0.8rem 1.5rem; }
            .stat-chip .count { font-size: 1.6rem; }
            .rooms-grid { grid-template-columns: repeat(auto-fill, minmax(440px, 1fr)); gap: 2.25rem; }
            .room-card { padding: 2.25rem; border-radius: 28px; }
            .room-title { font-size: 2.5rem; }
            .room-capacity { font-size: 1.15rem; padding: 0.5rem 1.1rem; }
            .status-badge { font-size: 1.5rem; padding: 1.25rem; }
            .active-user { font-size: 1.6rem; }
            .active-purpose { font-size: 1.25rem; }
            .active-time { font-size: 1.25rem; }
            .next-booking .next-info { font-size: 1.3rem; }
        }

        @media (max-width: 768px) {
            body { padding: 1rem; }
            header { flex-direction: column; align-items: flex-start; gap: 1rem; }
            .time-box { align-items: flex-start; width: 100%; }
            .time-display { font-size: 2rem; }
            .rooms-grid { grid-template-columns: 1fr; }
        }
    </style>
</head>
<body>

<div class="refresh-progress-bar" id="refreshBar"></div>

@php
    $nowTime = now()->format('H:i:s');
    
    $totalRooms = $rooms->count();
    $occupiedCount = 0;
    $cleaningCount = 0;
    $availableCount = 0;

    foreach($rooms as $r) {
        $act = $r->bookings->first(function($b) use ($nowTime) {
            return $b->start_time <= $nowTime && $b->end_time > $nowTime && $b->status === 'approved';
        });
        $occ = $r->is_occupied || ($r->current_booking_id !== null) || ($act !== null);
        if ($r->is_cleaning) {
            $cleaningCount++;
        } elseif ($occ) {
            $occupiedCount++;
        } else {
            $availableCount++;
        }
    }
@endphp

<header>
    <div class="brand-title">
        <div class="brand-icon">
            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2.5">
                <path stroke-linecap="round" stroke-linejoin="round" d="M6 20.25h12m-12-3.75h12m-12-3.75h12m-12-3.75h12m-12-3.75h12M3 3.75h18M3 3.75v16.5M21 3.75v16.5" />
            </svg>
        </div>
        <div class="brand-text">
            <h1>
                Monitor Status Ruangan
                <!-- <span class="live-badge"><span class="live-dot"></span> LIVE</span> -->
            </h1>
        </div>
    </div>

    <!-- Quick Statistics Chips -->
    <div class="stats-bar">
        <div class="stat-chip">
            <span>Total:</span>
            <span class="count">{{ $totalRooms }}</span>
        </div>
        <div class="stat-chip available">
            <span>🟢 Tersedia:</span>
            <span class="count">{{ $availableCount }}</span>
        </div>
        <div class="stat-chip occupied">
            <span>🔴 Dipakai:</span>
            <span class="count">{{ $occupiedCount }}</span>
        </div>
        <div class="stat-chip cleaning">
            <span>🟡 Cleaning:</span>
            <span class="count">{{ $cleaningCount }}</span>
        </div>
    </div>

    <div class="header-right">
        <div class="time-box">
            <div class="time-display" id="clock">00:00:00</div>
            <div class="date-display" id="dateDisplay">--</div>
        </div>
        <button class="btn-fullscreen" onclick="toggleFullscreen()" title="Layar Penuh (TV Mode)">
            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2.5">
                <path stroke-linecap="round" stroke-linejoin="round" d="M3.75 3.75v4.5m0-4.5h4.5m-4.5 0L9 9M3.75 20.25v-4.5m0 4.5h4.5m-4.5 0L9 15M20.25 3.75h-4.5m4.5 0v4.5m0-4.5L15 9m5.25 11.25h-4.5m4.5 0v-4.5m0 4.5L15 15" />
            </svg>
        </button>
    </div>
</header>

<div class="rooms-grid">
    @forelse($rooms as $room)
    @php
        $activeBooking = $room->bookings->first(function($b) use ($nowTime) {
            return $b->start_time <= $nowTime && $b->end_time > $nowTime && $b->status === 'approved';
        });

        // Fallback to room's current booking if available
        if (!$activeBooking && $room->currentBooking) {
            $activeBooking = $room->currentBooking;
        }

        $nextBooking = $room->bookings->first(function($b) use ($nowTime) {
            return $b->start_time > $nowTime;
        });

        $isOccupied = $room->is_occupied || ($room->current_booking_id !== null) || ($activeBooking !== null);
        $isCleaning = $room->is_cleaning;
        $cardClass = $isCleaning ? 'cleaning' : ($isOccupied ? 'occupied' : 'available');
    @endphp
    
    <div class="room-card {{ $cardClass }}">
        <div>
            <div class="card-header">
                <h2 class="room-title">{{ $room->name }}</h2>
                <div class="room-capacity">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2.5">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 6a3.75 3.75 0 11-7.5 0 3.75 3.75 0 017.5 0zM4.501 20.118a7.5 7.5 0 0114.998 0A17.933 17.933 0 0112 21.75c-2.676 0-5.216-.584-7.499-1.632z" />
                    </svg>
                    <span>{{ $room->capacity }} Orang</span>
                </div>
            </div>

            <div class="status-badge">
                <span class="status-badge-dot"></span>
                @if($isCleaning)
                    SEDANG DIBERSIHKAN
                @elseif($isOccupied)
                    SEDANG DIPAKAI
                @else
                    TERSEDIA
                @endif
            </div>

            @if($isOccupied && $activeBooking)
            <div class="active-booking-details">
                <div class="active-label">
                    <svg style="width: 16px; height: 16px; stroke: currentColor;" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2.5">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 6a3.75 3.75 0 11-7.5 0 3.75 3.75 0 017.5 0zM4.501 20.118a7.5 7.5 0 0114.998 0A17.933 17.933 0 0112 21.75c-2.676 0-5.216-.584-7.499-1.632z" />
                    </svg>
                    Peminjam Saat Ini
                </div>
                <div class="active-user">{{ $activeBooking->renter_name }}</div>
                @if($activeBooking->purpose)
                <div class="active-purpose">{{ $activeBooking->purpose }}</div>
                @endif
                <div style="display: flex; gap: 0.5rem; flex-wrap: wrap; align-items: center; margin-top: 0.5rem;">
                    <div class="active-time">
                        {{ substr($activeBooking->start_time, 0, 5) }} – {{ substr($activeBooking->end_time, 0, 5) }} WIB
                    </div>
                </div>
            </div>
            @endif
        </div>

        <div>
            @if($nextBooking)
            <div class="next-booking">
                <div class="next-header">
                    <div class="next-label">
                        <svg style="width: 16px; height: 16px; stroke: currentColor;" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2.5">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6h4.5m4.5 0a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                        Peminjaman Berikutnya
                    </div>
                    <div class="next-time">{{ substr($nextBooking->start_time, 0, 5) }} - {{ substr($nextBooking->end_time, 0, 5) }}</div>
                </div>
                <div class="next-info">{{ $nextBooking->renter_name }}</div>
            </div>
            @endif
        </div>
    </div>
    @empty
    <div style="grid-column: 1 / -1; text-align: center; padding: 6rem 2rem; background: var(--bg-card); border-radius: 24px; border: 2px dashed var(--border-card);">
        <h2 style="font-size: 1.8rem; color: var(--text-muted);">Belum ada ruangan terdaftar</h2>
    </div>
    @endforelse
</div>

<footer>
    <div>© {{ date('Y') }} Sistem Peminjaman Ruangan &bull; Display Monitor Real-time</div>
    <div style="display: flex; align-items: center; gap: 0.6rem;">
        <span style="display: inline-block; width: 10px; height: 10px; background: #10b981; border-radius: 50%; box-shadow: 0 0 10px #10b981;"></span>
        <span>Auto-sync Aktif (30 Detik)</span>
    </div>
</footer>

<script>
    function updateClock() {
        const now = new Date();
        const hours = String(now.getHours()).padStart(2, '0');
        const minutes = String(now.getMinutes()).padStart(2, '0');
        const seconds = String(now.getSeconds()).padStart(2, '0');
        document.getElementById('clock').textContent = `${hours}:${minutes}:${seconds}`;

        const options = { weekday: 'long', year: 'numeric', month: 'long', day: 'numeric' };
        document.getElementById('dateDisplay').textContent = now.toLocaleDateString('id-ID', options);
    }
    setInterval(updateClock, 1000);
    updateClock();

    function toggleFullscreen() {
        if (!document.fullscreenElement) {
            document.documentElement.requestFullscreen().catch(err => {
                console.error(`Gagal masuk ke mode layar penuh: ${err.message}`);
            });
        } else {
            if (document.exitFullscreen) {
                document.exitFullscreen();
            }
        }
    }

    // Auto refresh page smoothly when there is a change
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

        setInterval(checkAutoRefresh, 5000);
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