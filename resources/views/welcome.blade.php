<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Peminjaman Ruangan</title>
    <style>
        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }

        :root {
            --primary: #2563eb;
            --primary-dark: #1d4ed8;
            --accent: #f59e0b;
            --bg: #f8fafc;
            --card: #ffffff;
            --text: #1e293b;
            --muted: #64748b;
            --border: #e2e8f0;
            --success: #16a34a;
            --danger: #dc2626;
            --radius: 12px;
            --shadow: 0 4px 24px rgba(0,0,0,0.08);
        }

        body { font-family: 'Segoe UI', system-ui, sans-serif; background: var(--bg); color: var(--text); line-height: 1.6; }

        header {
            background: linear-gradient(135deg, #1e3a8a 0%, #2563eb 60%, #3b82f6 100%);
            color: white; padding: 2.5rem 1rem; text-align: center;
        }
        header h1 { font-size: 2.2rem; font-weight: 800; letter-spacing: -0.5px; }
        header p { margin-top: 0.5rem; opacity: 0.85; font-size: 1.05rem; }

        nav {
            background: white; border-bottom: 1px solid var(--border);
            display: flex; justify-content: center; gap: 0.5rem;
            padding: 0.75rem 1rem; position: sticky; top: 0; z-index: 100;
            box-shadow: 0 2px 8px rgba(0,0,0,0.06);
        }
        nav a { color: var(--primary); text-decoration: none; font-weight: 600; padding: 0.4rem 1rem; border-radius: 6px; transition: background 0.2s; }
        nav a:hover { background: #eff6ff; }

        .container { max-width: 1100px; margin: 0 auto; padding: 2rem 1rem; }

        .section-title {
            font-size: 1.6rem; font-weight: 700; margin-bottom: 1.5rem; color: var(--text);
            display: flex; align-items: center; gap: 0.5rem;
        }
        .section-title::after { content: ''; flex: 1; height: 2px; background: var(--border); margin-left: 0.5rem; }

        .alert { padding: 1rem 1.25rem; border-radius: var(--radius); margin-bottom: 1.5rem; font-weight: 500; }
        .alert-success { background: #dcfce7; color: #15803d; border-left: 4px solid #16a34a; }
        .alert-danger  { background: #fee2e2; color: #b91c1c; border-left: 4px solid #dc2626; }

        .rooms-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(300px, 1fr)); gap: 1.5rem; margin-bottom: 3rem; }
        .room-card { background: var(--card); border-radius: var(--radius); box-shadow: var(--shadow); overflow: hidden; transition: transform 0.2s, box-shadow 0.2s; }
        .room-card:hover { transform: translateY(-4px); box-shadow: 0 8px 32px rgba(0,0,0,0.12); }
        .room-card img { width: 100%; height: 200px; object-fit: cover; }
        .room-card-placeholder { width: 100%; height: 200px; background: linear-gradient(135deg, #dbeafe, #bfdbfe); display: flex; align-items: center; justify-content: center; font-size: 3rem; }
        .room-card-body { padding: 1.25rem; }
        .room-card-body h3 { font-size: 1.2rem; font-weight: 700; margin-bottom: 0.25rem; }
        .room-card-body .capacity { display: inline-block; background: #eff6ff; color: var(--primary); font-size: 0.8rem; font-weight: 600; padding: 0.2rem 0.6rem; border-radius: 20px; margin-bottom: 0.5rem; }
        .room-card-body p { color: var(--muted); font-size: 0.9rem; }

        .form-card { background: var(--card); border-radius: var(--radius); box-shadow: var(--shadow); padding: 2rem; margin-bottom: 3rem; }
        .form-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 1rem; }
        @media (max-width: 640px) { .form-grid { grid-template-columns: 1fr; } }
        .form-group { display: flex; flex-direction: column; gap: 0.4rem; }
        .form-group.full { grid-column: 1 / -1; }
        label { font-weight: 600; font-size: 0.9rem; color: var(--text); }
        input, select, textarea { border: 1.5px solid var(--border); border-radius: 8px; padding: 0.6rem 0.9rem; font-size: 0.95rem; font-family: inherit; transition: border-color 0.2s; background: white; }
        input:focus, select:focus, textarea:focus { outline: none; border-color: var(--primary); box-shadow: 0 0 0 3px rgba(37,99,235,0.1); }
        textarea { resize: vertical; min-height: 90px; }
        .btn { display: inline-block; padding: 0.75rem 2rem; border-radius: 8px; font-weight: 700; font-size: 1rem; cursor: pointer; border: none; transition: background 0.2s, transform 0.1s; }
        .btn:active { transform: scale(0.98); }
        .btn-primary { background: var(--primary); color: white; }
        .btn-primary:hover { background: var(--primary-dark); }
        .btn-warning { background: var(--accent); color: white; }
        .btn-warning:hover { background: #d97706; }

        .schedule-table { width: 100%; border-collapse: collapse; background: var(--card); border-radius: var(--radius); overflow: hidden; box-shadow: var(--shadow); margin-bottom: 3rem; }
        .schedule-table th { background: var(--primary); color: white; padding: 0.85rem 1rem; text-align: left; font-size: 0.9rem; }
        .schedule-table td { padding: 0.75rem 1rem; border-bottom: 1px solid var(--border); font-size: 0.9rem; }
        .schedule-table tr:last-child td { border-bottom: none; }
        .schedule-table tr:hover td { background: #f8fafc; }
        .badge { display: inline-block; padding: 0.2rem 0.6rem; border-radius: 20px; font-size: 0.78rem; font-weight: 700; }
        .badge-approved { background: #dcfce7; color: #15803d; }

        footer { background: #1e293b; color: #94a3b8; text-align: center; padding: 2rem 1rem; font-size: 0.9rem; }
        footer a { color: #60a5fa; text-decoration: none; }
    </style>
</head>
<body>

<header>
    <h1>🏛️ Sistem Peminjaman Ruangan</h1>
    <p>Pesan ruangan dengan mudah, cepat, dan transparan</p>
</header>

<nav>
    <a href="#ruangan">Ruangan</a>
    <a href="#pesan">Pesan Ruangan</a>
    <a href="#jadwal">Jadwal</a>
    <a href="#keluhan">Keluhan</a>
    @auth
        <a href="/user">Dashboard Saya</a>
        <form action="{{ route('filament.user.auth.logout') }}" method="POST" style="display: inline;">
            @csrf
            <button type="submit" style="background: none; border: none; color: var(--primary); font-weight: 600; padding: 0.4rem 1rem; border-radius: 6px; cursor: pointer; font-family: inherit; font-size: inherit;">Logout</button>
        </form>
    @else
        <a href="/user/login">Login</a>
        <a href="/user/register">Daftar</a>
    @endauth
</nav>

<div class="container">

    @if(session('success'))
        <div class="alert alert-success">✅ {{ session('success') }}</div>
    @endif
    @if($errors->has('conflict'))
        <div class="alert alert-danger">⚠️ {{ $errors->first('conflict') }}</div>
    @endif

    <section id="ruangan">
        <h2 class="section-title">🏠 Daftar Ruangan</h2>
        @if($rooms->isEmpty())
            <p style="color:var(--muted);margin-bottom:2rem">Belum ada ruangan yang terdaftar.</p>
        @else
        <div class="rooms-grid">
            @foreach($rooms as $room)
            <div class="room-card">
                @if($room->image_path)
                    <img src="{{ asset('storage/' . $room->image_path) }}" alt="{{ $room->name }}">
                @else
                    <div class="room-card-placeholder">🏛️</div>
                @endif
                <div class="room-card-body">
                    <h3>{{ $room->name }}</h3>
                    <span class="capacity">👥 Kapasitas: {{ $room->capacity }} orang</span>
                    @if($room->booking_quota !== null)
                        <span class="capacity" style="background: #fef3c7; color: #d97706;">📋 Kuota Harian: {{ $room->booking_quota }}</span>
                    @else
                        <span class="capacity" style="background: #f3f4f6; color: #4b5563;">📋 Kuota Harian: Bebas</span>
                    @endif
                    @php
                        $nowTime = now()->format('H:i:s');
                        $hasActiveBooking = $room->current_booking_id || $room->bookings()
                            ->where('date', today())
                            ->where('status', 'approved')
                            ->where('start_time', '<=', $nowTime)
                            ->where('end_time', '>', $nowTime)
                            ->exists();
                        $isOccupied = $room->is_occupied || $hasActiveBooking;
                    @endphp
                    @if($isOccupied)
                        <span class="badge" style="background: #fee2e2; color: #b91c1c; border: 1px solid #fca5a5; margin-bottom: 0.5rem;">🔴 Sedang Digunakan</span>
                    @else
                        <span class="badge" style="background: #dcfce7; color: #15803d; border: 1px solid #86efac; margin-bottom: 0.5rem;">🟢 Tersedia</span>
                    @endif
                    @if($room->description)
                        <p>{{ $room->description }}</p>
                    @endif
                </div>
            </div>
            @endforeach
        </div>
        @endif
    </section>

    <section id="pesan">
        <h2 class="section-title">📋 Form Peminjaman</h2>
        <div class="form-card">
            @auth
                @if($errors->any() && !$errors->has('conflict'))
                    <div class="alert alert-danger">
                        <ul style="margin-left:1rem">
                            @foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach
                        </ul>
                    </div>
                @endif
                <form method="POST" action="{{ route('book') }}">
                    @csrf
                    <div class="form-grid">
                        <div class="form-group">
                            <label for="room_id">Ruangan</label>
                            <select name="room_id" id="room_id" required>
                                <option value="">-- Pilih Ruangan --</option>
                                @foreach($rooms as $room)
                                    <option value="{{ $room->id }}" {{ old('room_id') == $room->id ? 'selected' : '' }}>
                                        {{ $room->name }} ({{ $room->capacity }} orang)
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <div class="form-group">
                            <label for="date">Tanggal</label>
                            <input type="date" name="date" id="date" value="{{ old('date') }}" min="{{ date('Y-m-d', strtotime('+1 day')) }}" required>
                        </div>
                        <div id="quota-info" class="form-group full" style="display: none; background: #eff6ff; border: 1px solid #bfdbfe; padding: 0.75rem 1rem; border-radius: 8px; font-size: 0.9rem; color: #1e40af; font-weight: 600;">
                            <!-- Quota info will be loaded here -->
                        </div>
                        <div class="form-group">
                            <label for="start_time">Jam Mulai</label>
                            <input type="time" name="start_time" id="start_time" value="{{ old('start_time') }}" required>
                        </div>
                        <div class="form-group">
                            <label for="end_time">Jam Selesai</label>
                            <input type="time" name="end_time" id="end_time" value="{{ old('end_time') }}" required>
                        </div>
                        <div class="form-group">
                            <label for="renter_name">Nama Peminjam</label>
                            <input type="text" name="renter_name" id="renter_name" value="{{ old('renter_name', auth()->user()->name) }}" placeholder="Nama lengkap" required>
                        </div>
                        <div class="form-group">
                            <label for="renter_email">Email</label>
                            <input type="email" name="renter_email" id="renter_email" value="{{ old('renter_email', auth()->user()->email) }}" placeholder="email@contoh.com" required>
                        </div>
                        <div class="form-group">
                            <label for="renter_phone">No. WhatsApp / Telepon</label>
                            <input type="text" name="renter_phone" id="renter_phone" value="{{ old('renter_phone') }}" placeholder="08xxxxxxxxxx" required>
                        </div>
                        <div class="form-group full">
                            <label for="purpose">Keperluan / Tujuan</label>
                            <textarea name="purpose" id="purpose" placeholder="Jelaskan keperluan peminjaman ruangan..." required>{{ old('purpose') }}</textarea>
                        </div>
                    </div>
                    <div style="margin-top:1.25rem">
                        <button type="submit" class="btn btn-primary">🚀 Kirim Permohonan</button>
                    </div>
                </form>
            @else
                <div style="text-align: center; padding: 2rem 0;">
                    <p style="font-size: 1.1rem; color: var(--muted); margin-bottom: 1.5rem;">Anda harus masuk (login) ke akun Anda terlebih dahulu untuk dapat melakukan peminjaman ruangan.</p>
                    <a href="/user/login" class="btn btn-primary" style="text-decoration: none;">Masuk / Login</a>
                    <a href="/user/register" class="btn btn-warning" style="text-decoration: none; margin-left: 0.5rem;">Daftar Akun</a>
                </div>
            @endauth
        </div>
    </section>

    <section id="jadwal">
        <h2 class="section-title">📅 Jadwal Peminjaman Disetujui</h2>
        @if($approvedBookings->isEmpty())
            <p style="color:var(--muted);margin-bottom:2rem">Belum ada jadwal peminjaman yang disetujui.</p>
        @else
        <table class="schedule-table">
            <thead>
                <tr>
                    <th>Ruangan</th><th>Tanggal</th><th>Jam</th><th>Peminjam</th><th>Keperluan</th><th>Status</th>
                </tr>
            </thead>
            <tbody>
                @foreach($approvedBookings as $b)
                <tr>
                    <td><strong>{{ $b->room->name }}</strong></td>
                    <td>{{ $b->date->format('d M Y') }}</td>
                    <td>{{ substr($b->start_time, 0, 5) }} – {{ substr($b->end_time, 0, 5) }}</td>
                    <td>{{ $b->renter_name }}</td>
                    <td>{{ Str::limit($b->purpose, 40) }}</td>
                    <td><span class="badge badge-approved">✓ Disetujui</span></td>
                </tr>
                @endforeach
            </tbody>
        </table>
        @endif
    </section>

    <section id="keluhan">
        <h2 class="section-title">📣 Kirim Keluhan</h2>
        <div class="form-card">
            @if(session('complaint_success'))
                <div class="alert alert-success">✅ {{ session('complaint_success') }}</div>
            @endif
            <form method="POST" action="{{ route('complaint') }}">
                @csrf
                <div class="form-grid">
                    <div class="form-group">
                        <label for="c_name">Nama</label>
                        <input type="text" name="name" id="c_name" placeholder="Nama Anda" required>
                    </div>
                    <div class="form-group">
                        <label for="c_contact">Email / No. HP</label>
                        <input type="text" name="email_or_phone" id="c_contact" placeholder="Kontak Anda" required>
                    </div>
                    <div class="form-group full">
                        <label for="c_text">Isi Keluhan</label>
                        <textarea name="complaint_text" id="c_text" placeholder="Tuliskan keluhan atau masukan Anda..." required></textarea>
                    </div>
                </div>
                <div style="margin-top:1.25rem">
                    <button type="submit" class="btn btn-warning">📤 Kirim Keluhan</button>
                </div>
            </form>
        </div>
    </section>

</div>

<footer>
    <p>© {{ date('Y') }} Sistem Peminjaman Ruangan</p>
</footer>

<script>
    document.addEventListener('DOMContentLoaded', function () {
        const roomIdSelect = document.getElementById('room_id');
        const dateInput = document.getElementById('date');
        const quotaInfoDiv = document.getElementById('quota-info');

        function updateQuotaInfo() {
            const roomId = roomIdSelect.value;
            const date = dateInput.value;

            if (!roomId || !date) {
                quotaInfoDiv.style.display = 'none';
                quotaInfoDiv.innerHTML = '';
                return;
            }

            fetch(`/check-quota?room_id=${roomId}&date=${date}`)
                .then(response => response.json())
                .then(data => {
                    if (data.quota !== null) {
                        quotaInfoDiv.style.display = 'block';
                        quotaInfoDiv.innerHTML = `ℹ️ Kuota Harian: <strong>${data.quota}</strong> | Sisa Kuota: <strong>${data.remaining}</strong>`;
                        if (data.remaining <= 0) {
                            quotaInfoDiv.style.background = '#fee2e2';
                            quotaInfoDiv.style.borderColor = '#fca5a5';
                            quotaInfoDiv.style.color = '#991b1b';
                            quotaInfoDiv.innerHTML += ` <span style="color: #dc2626;">(Penuh!)</span>`;
                        } else {
                            quotaInfoDiv.style.background = '#eff6ff';
                            quotaInfoDiv.style.borderColor = '#bfdbfe';
                            quotaInfoDiv.style.color = '#1e40af';
                        }
                    } else {
                        quotaInfoDiv.style.display = 'none';
                        quotaInfoDiv.innerHTML = '';
                    }
                })
                .catch(error => {
                    console.error('Error fetching quota:', error);
                });
        }

        if (roomIdSelect && dateInput) {
            roomIdSelect.addEventListener('change', updateQuotaInfo);
            dateInput.addEventListener('change', updateQuotaInfo);
            // Trigger on load if values are pre-filled
            if (roomIdSelect.value && dateInput.value) {
                updateQuotaInfo();
            }
        }
    });
</script>

</body>
</html>

