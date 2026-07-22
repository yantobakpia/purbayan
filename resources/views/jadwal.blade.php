<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Jadwal Peminjaman Disetujui - Sistem Peminjaman Ruangan</title>
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
            display: flex; 
            flex-direction: column; 
            min-height: 100vh; 
            -webkit-font-smoothing: antialiased;
        }

        .hero {
            background: linear-gradient(135deg, #0f172a 0%, #1e1b4b 50%, #1e3a8a 100%);
            color: white;
            padding: 3.5rem 1.5rem;
            text-align: center;
        }
        .hero-title { font-size: 2.5rem; font-weight: 800; letter-spacing: -0.025em; margin-bottom: 0.5rem; }
        .hero-subtitle { font-size: 1.1rem; color: #93c5fd; max-width: 600px; margin: 0 auto; }

        nav { 
            background: var(--card); 
            border-bottom: 1px solid var(--border); 
            padding: 0.75rem 1.5rem; 
            display: flex; 
            justify-content: center; 
            gap: 0.5rem; 
            position: sticky; 
            top: 0; 
            z-index: 100; 
            box-shadow: var(--shadow-sm);
            flex-wrap: wrap;
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

        .container { max-width: 1050px; margin: 2.5rem auto; padding: 0 1.5rem; flex: 1; width: 100%; }

        .section-header { margin-bottom: 1.75rem; }
        .section-title { font-size: 1.5rem; font-weight: 800; color: var(--text); }

        .table-card {
            background: var(--card);
            border-radius: var(--radius-lg);
            border: 1px solid var(--border);
            box-shadow: var(--shadow-md);
            overflow: hidden;
            margin-bottom: 3.5rem;
        }
        .schedule-table { width: 100%; border-collapse: collapse; text-align: left; }
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

        footer { 
            background: #0f172a; 
            color: #94a3b8; 
            text-align: center; 
            padding: 2.5rem 1.5rem; 
            font-size: 0.9rem; 
            border-top: 1px solid #1e293b;
        }

        /* Modal Styles */
        .modal-overlay {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(15, 23, 42, 0.6);
            backdrop-filter: blur(4px);
            display: flex;
            align-items: center;
            justify-content: center;
            z-index: 1000;
            opacity: 0;
            pointer-events: none;
            transition: opacity 0.25s ease;
        }
        .modal-overlay.show {
            opacity: 1;
            pointer-events: auto;
        }
        .modal-content {
            background: var(--card);
            border-radius: var(--radius-lg);
            width: 90%;
            max-width: 550px;
            box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.25);
            border: 1px solid var(--border);
            overflow: hidden;
            transform: scale(0.95);
            transition: transform 0.25s ease;
        }
        .modal-overlay.show .modal-content {
            transform: scale(1);
        }
        .modal-header {
            padding: 1.25rem 1.5rem;
            border-bottom: 1px solid var(--border);
            display: flex;
            justify-content: space-between;
            align-items: center;
            background: #f8fafc;
        }
        .modal-header h3 {
            font-size: 1.2rem;
            font-weight: 800;
            color: var(--text);
            margin: 0;
        }
        .modal-close-btn {
            background: none;
            border: none;
            font-size: 1.75rem;
            font-weight: 700;
            color: var(--muted);
            cursor: pointer;
            line-height: 1;
            transition: color 0.2s;
        }
        .modal-close-btn:hover {
            color: var(--text);
        }
        .modal-body {
            padding: 1.5rem;
            max-height: 60vh;
            overflow-y: auto;
        }
        .modal-booking-item {
            padding: 1.25rem;
            border-radius: var(--radius-md);
            background: #f8fafc;
            border: 1px solid var(--border);
            margin-bottom: 1rem;
            border-left: 4px solid var(--primary);
        }
        .modal-booking-item:last-child {
            margin-bottom: 0;
        }
        .modal-booking-room {
            font-size: 1.15rem;
            font-weight: 800;
            color: var(--primary);
            margin-bottom: 0.5rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }
        .modal-booking-renter {
            font-size: 0.95rem;
            font-weight: 700;
            color: var(--text);
            margin-bottom: 0.4rem;
        }
        .modal-booking-time {
            display: inline-block;
            font-size: 0.85rem;
            font-weight: 700;
            background: var(--primary-light);
            color: var(--primary-dark);
            padding: 0.25rem 0.6rem;
            border-radius: var(--radius-sm);
            margin-bottom: 0.75rem;
        }
        .modal-booking-purpose {
            font-size: 0.9rem;
            color: var(--muted);
            line-height: 1.5;
            background: #ffffff;
            padding: 0.75rem;
            border-radius: var(--radius-sm);
            border: 1px solid var(--border);
        }
    </style>
</head>
<body>

<header class="hero">
    <h1 class="hero-title">Jadwal Peminjaman Disetujui</h1>
    <p class="hero-subtitle">Pantau seluruh jadwal peminjaman ruangan yang telah disetujui secara real-time</p>
</header>

<nav>
    <a href="/">Beranda</a>
    <a href="{{ route('jadwal.page') }}" class="active">Status & Kalender</a>
    <a href="{{ route('peminjaman.page') }}">Pesan Ruangan</a>
    <a href="{{ route('complaint.page') }}">Keluhan</a>
    @auth
        @if(auth()->user()->is_admin || auth()->user()->email === 'admin@ruangan.com')
            <a href="/admin" style="color: var(--danger-text); background: var(--danger-bg); font-weight: 700;">Dashboard Admin</a>
        @else
            <a href="/user" style="color: var(--primary); background: var(--primary-light); font-weight: 700;">Dashboard Saya</a>
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

    <section id="ruangan">
        <div class="section-header">
            <h2 class="section-title">Status Ruangan Terkini</h2>
        </div>

        @if($rooms->isEmpty())
            <p style="color:var(--muted); margin-bottom:2rem;">Belum ada ruangan yang terdaftar.</p>
        @else
        <div class="table-card">
            <div style="overflow-x: auto;">
                <table class="schedule-table">
                    <thead>
                        <tr>
                            <th>Nama Ruangan</th>
                            <th>Kapasitas</th>
                            <th>Status Terkini</th>
                            
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($rooms as $room)
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
                        <tr>
                            <td style="font-weight: 700;">{{ $room->name }}</td>
                            <td>{{ $room->capacity }} Orang</td>
                            <td>
                                @if($isCleaning)
                                    <span class="badge-status status-cleaning">Sedang Dibersihkan</span>
                                @elseif($isOccupied)
                                    <span class="badge-status status-occupied">Sedang Digunakan</span>
                                @else
                                    <span class="badge-status status-available">Tersedia</span>
                                @endif
                            </td>
                                
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
        @endif
    </section>

    <section id="kalender">
        <div class="section-header" style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 1rem; margin-bottom: 1.5rem;">
            <h2 class="section-title">Kalender & Daftar Jadwal</h2>
            <div class="calendar-filter-bar">
                <button type="button" class="cal-btn active" onclick="filterJadwal('all', this)">Semua</button>
                <button type="button" class="cal-btn" onclick="filterJadwal('today', this)">Hari Ini</button>
                <button type="button" class="cal-btn" onclick="filterJadwal('tomorrow', this)">Besok</button>
                <button type="button" class="cal-btn" onclick="filterJadwal('week', this)">Minggu Ini</button>
                <input type="date" id="datePickerFilter" onchange="filterJadwalDate(this.value)" style="padding: 0.4rem 0.75rem; border-radius: 8px; border: 1.5px solid var(--border); font-size: 0.875rem;">
            </div>
        </div>

        <!-- Kalender Visual Widget -->
        <div class="calendar-box" style="background: var(--card); border-radius: var(--radius-lg); border: 1px solid var(--border); box-shadow: var(--shadow-md); padding: 1.5rem; margin-bottom: 1.75rem;">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1rem;">
                <h3 id="calMonthYear" style="font-size: 1.1rem; font-weight: 800; color: var(--text);"></h3>
                <div style="display: flex; gap: 0.5rem;">
                    <button type="button" onclick="changeMonth(-1)" class="cal-btn" style="padding: 0.3rem 0.75rem;">&larr; Prev</button>
                    <button type="button" onclick="changeMonth(1)" class="cal-btn" style="padding: 0.3rem 0.75rem;">Next &rarr;</button>
                </div>
            </div>
            <div class="calendar-grid">
                <div class="cal-head">Min</div>
                <div class="cal-head">Sen</div>
                <div class="cal-head">Sel</div>
                <div class="cal-head">Rab</div>
                <div class="cal-head">Kam</div>
                <div class="cal-head">Jum</div>
                <div class="cal-head">Sab</div>
            </div>
            <div id="calDaysGrid" class="calendar-grid" style="margin-top: 0.5rem;"></div>
        </div>

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
                    <tbody id="scheduleTableBody">
                        @forelse($approvedBookings as $b)
                        <tr data-date="{{ $b->date->format('Y-m-d') }}">
                            <td><strong>{{ $b->room->name }}</strong></td>
                            <td>{{ $b->date->format('d M Y') }}</td>
                            <td><span class="time-pill">{{ substr($b->start_time, 0, 5) }} – {{ substr($b->end_time, 0, 5) }}</span></td>
                            <td>{{ $b->renter_name }}</td>
                            <td>{{ Str::limit($b->purpose, 45) }}</td>
                            <td>
                                <span class="badge-status status-available" style="margin-bottom:0; font-size:0.75rem;">Disetujui</span>
                            </td>
                        </tr>
                        @empty
                        <tr id="emptyRow">
                            <td colspan="6" style="text-align: center; color: var(--muted); padding: 2rem;">Belum ada jadwal peminjaman yang disetujui.</td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </section>

</div>

<footer>
    <p>© {{ date('Y') }} Sistem Peminjaman Ruangan & Keluhan • All rights reserved.</p>
</footer>

<!-- Modal Detail Peminjaman -->
<div id="bookingModal" class="modal-overlay" style="display: none;">
    <div class="modal-content">
        <div class="modal-header">
            <h3 id="modalTitle">Detail Peminjaman</h3>
            <button type="button" class="modal-close-btn" onclick="closeModal()">&times;</button>
        </div>
        <div class="modal-body" id="modalBody">
            <!-- Booking details will be inserted here -->
        </div>
    </div>
</div>

@php
    $bookingsData = [];
    foreach ($approvedBookings as $b) {
        $dateStr = $b->date->format('Y-m-d');
        $bookingsData[$dateStr][] = [
            'room_name' => $b->room->name,
            'renter_name' => $b->renter_name,
            'start_time' => substr($b->start_time, 0, 5),
            'end_time' => substr($b->end_time, 0, 5),
            'purpose' => $b->purpose,
        ];
    }
@endphp

<script>
    const bookingsByDate = @json($bookingsData);
    const bookedDates = Object.keys(bookingsByDate);
    let currentCalDate = new Date();

    function renderCalendar() {
        const year = currentCalDate.getFullYear();
        const month = currentCalDate.getMonth();
        const monthNames = ["Januari", "Februari", "Maret", "April", "Mei", "Juni", "Juli", "Agustus", "September", "Oktober", "November", "Desember"];
        const monthYearEl = document.getElementById('calMonthYear');
        if (monthYearEl) {
            monthYearEl.textContent = `${monthNames[month]} ${year}`;
        }

        const firstDay = new Date(year, month, 1).getDay();
        const daysInMonth = new Date(year, month + 1, 0).getDate();
        const todayStr = new Date().toISOString().split('T')[0];

        const daysGrid = document.getElementById('calDaysGrid');
        if (!daysGrid) return;
        daysGrid.innerHTML = '';

        for (let i = 0; i < firstDay; i++) {
            const emptyCell = document.createElement('div');
            emptyCell.className = 'cal-day-cell empty';
            daysGrid.appendChild(emptyCell);
        }

        for (let day = 1; day <= daysInMonth; day++) {
            const dateStr = `${year}-${String(month + 1).padStart(2, '0')}-${String(day).padStart(2, '0')}`;
            const cell = document.createElement('div');
            cell.className = 'cal-day-cell';
            cell.textContent = day;

            if (dateStr === todayStr) {
                cell.classList.add('is-today');
            }
            if (bookedDates.includes(dateStr)) {
                cell.classList.add('has-booking');
            }

            cell.onclick = () => {
                document.querySelectorAll('.cal-day-cell').forEach(c => c.classList.remove('is-selected'));
                cell.classList.add('is-selected');
                filterJadwalDate(dateStr);

                if (bookingsByDate[dateStr] && bookingsByDate[dateStr].length > 0) {
                    showBookingModal(dateStr, bookingsByDate[dateStr]);
                }
            };

            daysGrid.appendChild(cell);
        }
    }

    function showBookingModal(dateStr, bookings) {
        const modal = document.getElementById('bookingModal');
        const modalTitle = document.getElementById('modalTitle');
        const modalBody = document.getElementById('modalBody');
        
        const dateObj = new Date(dateStr);
        const options = { weekday: 'long', year: 'numeric', month: 'long', day: 'numeric' };
        const formattedDate = dateObj.toLocaleDateString('id-ID', options);
        
        modalTitle.textContent = `Peminjaman - ${formattedDate}`;
        
        let html = '';
        bookings.forEach(b => {
            html += `
                <div class="modal-booking-item">
                    <div class="modal-booking-room">🏛️ ${escapeHtml(b.room_name)}</div>
                    <div class="modal-booking-renter">👤 Peminjam: ${escapeHtml(b.renter_name)}</div>
                    <div class="modal-booking-time">⏰ Waktu: ${escapeHtml(b.start_time)} – ${escapeHtml(b.end_time)} WIB</div>
                    ${b.purpose ? `<div class="modal-booking-purpose"><strong>Keperluan:</strong> ${escapeHtml(b.purpose)}</div>` : ''}
                </div>
            `;
        });
        
        modalBody.innerHTML = html;
        modal.style.display = 'flex';
        setTimeout(() => {
            modal.classList.add('show');
        }, 10);
    }

    function closeModal() {
        const modal = document.getElementById('bookingModal');
        modal.classList.remove('show');
        setTimeout(() => {
            modal.style.display = 'none';
        }, 300);
    }

    function changeMonth(delta) {
        currentCalDate.setMonth(currentCalDate.getMonth() + delta);
        renderCalendar();
    }

    function filterJadwalDate(dateStr) {
        const rows = document.querySelectorAll('#scheduleTableBody tr');
        rows.forEach(row => {
            const rowDate = row.getAttribute('data-date');
            if (!dateStr || rowDate === dateStr) {
                row.style.display = '';
            } else {
                row.style.display = 'none';
            }
        });
    }

    function filterJadwal(type, btn) {
        if (btn) {
            document.querySelectorAll('.cal-btn').forEach(b => b.classList.remove('active'));
            btn.classList.add('active');
        }
        document.querySelectorAll('.cal-day-cell').forEach(c => c.classList.remove('is-selected'));

        const rows = document.querySelectorAll('#scheduleTableBody tr');
        const now = new Date();
        const todayStr = now.toISOString().split('T')[0];

        const tomorrow = new Date(now);
        tomorrow.setDate(tomorrow.getDate() + 1);
        const tomorrowStr = tomorrow.toISOString().split('T')[0];

        const weekEnd = new Date(now);
        weekEnd.setDate(weekEnd.getDate() + 7);

        rows.forEach(row => {
            const rowDateStr = row.getAttribute('data-date');
            if (type === 'all') {
                row.style.display = '';
            } else if (type === 'today') {
                row.style.display = rowDateStr === todayStr ? '' : 'none';
            } else if (type === 'tomorrow') {
                row.style.display = rowDateStr === tomorrowStr ? '' : 'none';
            } else if (type === 'week') {
                const rowDate = new Date(rowDateStr);
                row.style.display = (rowDate >= new Date(todayStr) && rowDate <= weekEnd) ? '' : 'none';
            }
        });
    }

    document.addEventListener('DOMContentLoaded', () => {
        renderCalendar();
    });

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

    function escapeHtml(text) {
        if (!text) return '';
        return text
            .replace(/&/g, "&amp;")
            .replace(/</g, "&lt;")
            .replace(/>/g, "&gt;")
            .replace(/"/g, "&quot;")
            .replace(/'/g, "&#039;");
    }

    window.onclick = function(event) {
        const modal = document.getElementById('bookingModal');
        if (event.target === modal) {
            closeModal();
        }
    }
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