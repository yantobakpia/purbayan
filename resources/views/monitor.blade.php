<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Monitor TV Status Ruangan</title>
    <style>
        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }

        :root {
            --bg: #0f172a;
            --card-bg: #1e293b;
            --text: #f8fafc;
            --text-muted: #94a3b8;
            --success: #22c55e;
            --danger: #ef4444;
            --border: #334155;
            --radius: 16px;
        }

        body {
            font-family: 'Segoe UI', system-ui, sans-serif;
            background-color: var(--bg);
            color: var(--text);
            min-height: 100vh;
            display: flex;
            flex-direction: column;
            padding: 2rem;
        }

        header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 2.5rem;
            border-bottom: 2px solid var(--border);
            padding-bottom: 1.5rem;
        }

        header h1 {
            font-size: 2.5rem;
            font-weight: 800;
            letter-spacing: -1px;
            display: flex;
            align-items: center;
            gap: 0.75rem;
        }

        .time-display {
            font-size: 1.8rem;
            font-weight: 700;
            color: var(--text-muted);
            background: #1e293b;
            padding: 0.5rem 1.5rem;
            border-radius: 50px;
            border: 1px solid var(--border);
        }

        .rooms-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(350px, 1fr));
            gap: 2rem;
            flex: 1;
        }

        .room-card {
            background-color: var(--card-bg);
            border-radius: var(--radius);
            border: 2px solid var(--border);
            padding: 2.5rem 2rem;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
            transition: transform 0.3s ease, border-color 0.3s ease;
            box-shadow: 0 10px 30px rgba(0,0,0,0.2);
        }

        .room-card.available {
            border-color: var(--success);
            box-shadow: 0 0 20px rgba(34, 197, 94, 0.15);
        }

        .room-card.occupied {
            border-color: var(--danger);
            box-shadow: 0 0 20px rgba(239, 68, 68, 0.15);
        }

        .room-name {
            font-size: 2.2rem;
            font-weight: 800;
            margin-bottom: 0.5rem;
        }

        .room-capacity {
            font-size: 1.1rem;
            color: var(--text-muted);
            margin-bottom: 2rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .status-badge {
            font-size: 1.5rem;
            font-weight: 900;
            text-transform: uppercase;
            padding: 1rem;
            border-radius: 12px;
            text-align: center;
            letter-spacing: 1px;
        }

        .room-card.available .status-badge {
            background-color: rgba(34, 197, 94, 0.15);
            color: var(--success);
            border: 1px solid var(--success);
        }

        .room-card.occupied .status-badge {
            background-color: rgba(239, 68, 68, 0.15);
            color: var(--danger);
            border: 1px solid var(--danger);
        }

        footer {
            margin-top: 3rem;
            text-align: center;
            color: var(--text-muted);
            font-size: 1.1rem;
        }
    </style>
</head>
<body>

<header>
    <h1>📺 Monitor Status Ruangan</h1>
    <div class="time-display" id="clock">00:00:00</div>
</header>

<div class="rooms-grid">
    @forelse($rooms as $room)
    @php
        $nowTime = now()->format('H:i:s');
        $activeBooking = $room->bookings->first(function($b) use ($nowTime) {
            return $b->start_time <= $nowTime && $b->end_time > $nowTime;
        });
        $isOccupied = $room->is_occupied || ($room->current_booking_id !== null) || ($activeBooking !== null);
    @endphp
    <div class="room-card {{ $isOccupied ? 'occupied' : 'available' }}">
        <div>
            <div class="room-name">{{ $room->name }}</div>
            <div class="room-capacity">👥 Kapasitas: {{ $room->capacity }} orang</div>
        </div>
        <div class="status-badge">
            {{ $isOccupied ? '🔴 SEDANG DIPAKAI' : '🟢 TERSEDIA' }}
        </div>
    </div>
    @empty
    <div style="grid-column: 1 / -1; text-align: center; padding: 5rem;">
        <h2>Belum ada ruangan terdaftar</h2>
    </div>
    @endforelse
</div>

<footer>
    <p>© {{ date('Y') }} Sistem Peminjaman Ruangan &bull; Halaman ini diperbarui secara otomatis</p>
</footer>

<script>
    function updateClock() {
        const now = new Date();
        const hours = String(now.getHours()).padStart(2, '0');
        const minutes = String(now.getMinutes()).padStart(2, '0');
        const seconds = String(now.getSeconds()).padStart(2, '0');
        document.getElementById('clock').textContent = `${hours}:${minutes}:${seconds}`;
    }
    setInterval(updateClock, 1000);
    updateClock();

    // Auto refresh page every 30 seconds to keep status updated
    setInterval(() => {
        window.location.reload();
    }, 30000);
</script>

</body>
</html>