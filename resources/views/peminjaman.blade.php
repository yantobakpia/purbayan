<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pesan Ruangan - Sistem Peminjaman Ruangan</title>
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
            padding: 3rem 1.5rem 3.5rem 1.5rem; 
            text-align: center;
        }
        .hero-title { 
            font-size: 2.25rem; 
            font-weight: 800; 
            letter-spacing: -0.025em; 
            background: linear-gradient(to right, #ffffff, #93c5fd);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }
        .hero-subtitle { margin-top: 0.5rem; color: #cbd5e1; font-size: 1.05rem; }

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

        .container { max-width: 1050px; margin: 2rem auto; padding: 0 1.5rem; flex: 1; width: 100%; }

        .alert { 
            padding: 1rem 1.25rem; 
            border-radius: var(--radius-md); 
            margin-bottom: 1.75rem; 
            font-weight: 600;
            font-size: 0.95rem;
        }
        .alert-success { background: var(--success-bg); color: var(--success-text); border: 1px solid var(--success-border); }
        .alert-danger  { background: var(--danger-bg); color: var(--danger-text); border: 1px solid var(--danger-border); }

        .form-card { 
            background: var(--card); 
            border-radius: var(--radius-lg); 
            border: 1px solid var(--border);
            box-shadow: var(--shadow-md); 
            padding: 2.25rem; 
            margin-bottom: 2.5rem; 
        }
        .form-title {
            font-size: 1.35rem;
            font-weight: 800;
            margin-bottom: 1.5rem;
            color: var(--text);
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .form-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 1.25rem; }
        @media (max-width: 640px) { .form-grid { grid-template-columns: 1fr; } }
        .form-group { display: flex; flex-direction: column; gap: 0.45rem; }
        .form-group.full { grid-column: 1 / -1; }
        
        label { font-weight: 700; font-size: 0.875rem; color: #334155; }
        input, select, textarea { 
            border: 1.5px solid var(--border); 
            border-radius: var(--radius-md); 
            padding: 0.7rem 1rem; 
            font-size: 0.95rem; 
            font-family: inherit; 
            transition: all 0.2s; 
            background: white; 
            color: var(--text);
        }
        input:focus, select:focus, textarea:focus { 
            outline: none; 
            border-color: var(--primary); 
            box-shadow: 0 0 0 4px rgba(37,99,235,0.1); 
        }
        textarea { resize: vertical; min-height: 100px; }
        
        .btn { 
            display: inline-flex; 
            align-items: center;
            justify-content: center;
            gap: 0.5rem;
            padding: 0.85rem 2rem; 
            border-radius: var(--radius-md); 
            font-weight: 700; 
            font-size: 1rem; 
            cursor: pointer; 
            border: none; 
            transition: all 0.2s; 
            text-decoration: none; 
            text-align: center; 
        }
        .btn:active { transform: scale(0.98); }
        .btn-primary { background: var(--primary); color: white; }
        .btn-primary:hover { background: var(--primary-dark); box-shadow: 0 4px 12px rgba(37, 99, 235, 0.25); }
        .btn-warning { background: #f59e0b; color: white; }
        .btn-warning:hover { background: #d97706; }

        .table-card {
            background: var(--card);
            border-radius: var(--radius-lg);
            border: 1px solid var(--border);
            box-shadow: var(--shadow-md);
            overflow: hidden;
            margin-bottom: 3rem;
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
        .schedule-table td { padding: 1rem 1.25rem; border-bottom: 1px solid var(--border); font-size: 0.925rem; }
        .schedule-table tr:last-child td { border-bottom: none; }
        .schedule-table tr:hover td { background: #f1f5f9; }

        .badge-status { 
            display: inline-flex; 
            align-items: center;
            gap: 0.35rem;
            padding: 0.3rem 0.75rem; 
            border-radius: 30px; 
            font-size: 0.8rem; 
            font-weight: 700; 
        }

        .back-link { display: block; text-align: center; margin-top: 1.5rem; color: var(--primary); text-decoration: none; font-weight: 600; }
        .back-link:hover { text-decoration: underline; }

        @media (max-width: 768px) {
            .hero { padding: 2.5rem 1rem 3rem 1rem; }
            .hero-title { font-size: 1.85rem; }
            .hero-subtitle { font-size: 0.95rem; margin-bottom: 1.5rem; }
            .container { padding: 1.5rem 1rem; }
            .form-card { padding: 1.25rem; }
            .table-card { overflow-x: auto; -webkit-overflow-scrolling: touch; }
            .section-header { flex-direction: column; align-items: flex-start; gap: 0.75rem; }
        }

        footer { 
            background: #0f172a; 
            color: #94a3b8; 
            text-align: center; 
            padding: 2rem 1rem; 
            font-size: 0.9rem; 
            margin-top: auto; 
            border-top: 1px solid #1e293b;
        }
    </style>
</head>
<body>

<header class="hero">
    <h1 class="hero-title">Form Peminjaman Ruangan</h1>
    <p class="hero-subtitle">Silakan isi formulir di bawah ini untuk mengajukan peminjaman ruangan</p>
</header>

<nav>
    <a href="/">Beranda</a>
    <a href="{{ route('jadwal.page') }}">Status & Kalender</a>
    <a href="{{ route('peminjaman.page') }}" class="active">Pesan Ruangan</a>
    <a href="{{ route('complaint.page') }}">Keluhan</a>
    @auth
        <a href="/user/profile">Profil Saya</a>
        @if(auth()->user()->is_admin || auth()->user()->email === 'admin@ruangan.com')
            <a href="/admin" style="color: var(--danger-text); background: var(--danger-bg); font-weight: 700;">Dashboard Admin</a>
        @endif
        <form action="{{ route('filament.user.auth.logout') }}" method="POST" style="display: inline;">
            @csrf
            <button type="submit" style="background: none; border: none; color: var(--primary); font-weight: 600; padding: 0.4rem 1rem; border-radius: 6px; cursor: pointer; font-family: inherit; font-size: inherit;">Logout</button>
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

    <div class="form-card">
        @auth
            @if($errors->any() && !$errors->has('conflict'))
                <div class="alert alert-danger">
                    <ul style="margin-left:1rem">
                        @foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach
                    </ul>
                </div>
            @endif
            <form method="POST" action="{{ route('book') }}" enctype="multipart/form-data">
                @csrf
                <div class="form-grid">
                    <div class="form-group">
                        <label for="room_id">Ruangan</label>
                        <select name="room_id" id="room_id" required>
                            <option value="">-- Pilih Ruangan --</option>
                            @foreach($rooms as $room)
                                <option value="{{ $room->id }}" {{ (old('room_id', request('room_id')) == $room->id) ? 'selected' : '' }}>
                                    {{ $room->name }} ({{ $room->capacity }} orang)
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="date">Tanggal Mulai / Tanggal Peminjaman</label>
                        <input type="date" name="date" id="date" value="{{ old('date') }}" min="{{ date('Y-m-d', strtotime('+1 day')) }}" required>
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
                    <input type="hidden" name="renter_phone" value="{{ auth()->user()->phone ?? '08123456789' }}">
                    <div class="form-group full">
                        <label for="permit_letter">Surat Permohonan (Opsional, PDF, Maksimal 5 MB)</label>
                        <input type="file" name="permit_letter" id="permit_letter" accept="application/pdf" style="width: 100%; padding: 0.5rem; border: 1px solid var(--border); border-radius: 8px;">
                    </div>
                    <div class="form-group full" style="display: flex; flex-direction: row; align-items: center; gap: 0.5rem; margin-top: 0.5rem;">
                        <input type="checkbox" name="is_recurring" id="is_recurring" value="1" {{ old('is_recurring') ? 'checked' : '' }} style="width: auto; cursor: pointer;">
                        <label for="is_recurring" style="cursor: pointer; margin-bottom: 0; font-weight: 700;">Peminjaman Berkala (Ulangi Setiap Minggu)</label>
                    </div>
                    <div id="recurring_options" class="form-group full" style="display: {{ old('is_recurring') ? 'block' : 'none' }}; border: 1.5px solid var(--primary); padding: 1.25rem; border-radius: var(--radius-md); background: var(--primary-light); margin-top: 0.5rem;">
                        <input type="hidden" name="recurring_day" id="recurring_day" value="{{ old('recurring_day') }}">
                        <div style="display: flex; flex-direction: column; gap: 0.75rem;">
                            <div class="form-group">
                                <label for="end_date" style="color: var(--primary-dark);">Ulangi Setiap Hari <span id="recurring_day_name" style="text-decoration: underline; font-weight: 800;">-</span> Hingga Tanggal:</label>
                                <input type="date" name="end_date" id="end_date" value="{{ old('end_date') }}" min="{{ date('Y-m-d', strtotime('+2 days')) }}" style="border-color: #93c5fd;">
                            </div>
                            <p style="font-size: 0.85rem; color: #1e40af; margin: 0; font-weight: 500;">
                                * Sistem akan otomatis membuat jadwal peminjaman berulang pada hari tersebut dari tanggal mulai hingga tanggal selesai yang Anda tentukan.
                            </p>
                        </div>
                    </div>
                    <div class="form-group full">
                        <label for="purpose">Keperluan / Tujuan</label>
                        <textarea name="purpose" id="purpose" placeholder="Jelaskan keperluan peminjaman ruangan..." required>{{ old('purpose') }}</textarea>
                    </div>
                </div>
                <div style="margin-top:1.25rem">
                    <button type="submit" class="btn btn-primary">Kirim Permohonan</button>
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

    @auth
        <div style="margin-top: 3rem; margin-bottom: 2rem;">
            <h2 style="font-size: 1.6rem; font-weight: 700; margin-bottom: 1.5rem; color: var(--text); display: flex; align-items: center; gap: 0.5rem;">
                Riwayat Peminjaman Saya
            </h2>
            @if($myBookings->isEmpty())
                <p style="color: var(--muted); margin-bottom: 2rem;">Anda belum memiliki riwayat peminjaman.</p>
            @else
                <div style="overflow-x: auto; border: 1px solid var(--border); border-radius: 8px;">
                    <table class="schedule-table" style="margin-bottom: 0;">
                        <thead>
                            <tr>
                                <th>Ruangan</th>
                                <th>Tanggal</th>
                                <th>Jam</th>
                                <th>Surat</th>
                                <th>Catatan Admin</th>
                                <th>Status</th>
                                <th>Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($myBookings as $b)
                                <tr>
                                    <td><strong>{{ $b->room->name }}</strong></td>
                                    <td>{{ $b->date->format('d M Y') }}</td>
                                    <td>{{ substr($b->start_time, 0, 5) }} - {{ substr($b->end_time, 0, 5) }}</td>
                                    <td>
                                        @if($b->permit_letter_path)
                                            <a href="{{ asset('storage/' . $b->permit_letter_path) }}" target="_blank" style="color: var(--primary); font-weight: 600; text-decoration: none;">📄 PDF</a>
                                        @else
                                            -
                                        @endif
                                    </td>
                                    <td>{{ $b->admin_note ?? '-' }}</td>
                                    <td>
                                        @if($b->status === 'pending')
                                            <span class="badge" style="background: #fef3c7; color: #d97706; border: 1px solid #fcd34d;">Pending</span>
                                        @elseif($b->status === 'approved')
                                            <span class="badge" style="background: #dcfce7; color: #15803d; border: 1px solid #86efac;">Disetujui</span>
                                        @elseif($b->status === 'rejected')
                                            <span class="badge" style="background: #fee2e2; color: #b91c1c; border: 1px solid #fca5a5;" title="Alasan: {{ $b->rejection_reason }}">Ditolak</span>
                                        @elseif($b->status === 'selesai')
                                            <span class="badge" style="background: #f3f4f6; color: #4b5563; border: 1px solid #d1d5db;">Selesai</span>
                                        @endif
                                    </td>
                                    <td>
                                        @if($b->status === 'pending')
                                            <form action="{{ route('bookings.cancel', $b->id) }}" method="POST" onsubmit="return confirm('Apakah Anda yakin ingin membatalkan peminjaman ini?');" style="display: inline;">
                                                @csrf
                                                <button type="submit" style="background: #fee2e2; color: #b91c1c; border: 1px solid #fca5a5; padding: 0.25rem 0.5rem; border-radius: 4px; font-size: 0.8rem; font-weight: 600; cursor: pointer;">Batalkan</button>
                                            </form>
                                        @else
                                            -
                                        @endif
                                    </td>
                                </tr>
                                @if($b->status === 'rejected' && $b->rejection_reason)
                                    <tr style="background: #fffbeb;">
                                        <td colspan="6" style="padding: 0.5rem 1rem; font-size: 0.85rem; color: #b45309;">
                                            <strong>Alasan Penolakan:</strong> {{ $b->rejection_reason }}
                                        </td>
                                    </tr>
                                @endif
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </div>
    @endauth

    <a href="/" class="back-link">← Kembali ke Beranda</a>
</div>

<footer>
    <p>© {{ date('Y') }} Sistem Peminjaman Ruangan</p>
</footer>

<script>
    document.addEventListener('DOMContentLoaded', function () {
        const startTimeInput = document.getElementById('start_time');
        const endTimeInput = document.getElementById('end_time');

        // Validasi Jam Mulai & Jam Selesai
        const form = document.querySelector('form');

        if (form && startTimeInput && endTimeInput) {
            form.addEventListener('submit', function (e) {
                const startTime = startTimeInput.value;
                const endTime = endTimeInput.value;

                if (startTime && endTime) {
                    if (endTime <= startTime) {
                        e.preventDefault();
                        alert('Jam selesai harus lebih lambat dari jam mulai.');
                        return;
                    }
                }
            });
        }

        // Peminjaman Berkala Logic
        const isRecurringCheckbox = document.getElementById('is_recurring');
        const recurringOptions = document.getElementById('recurring_options');
        const dateInput = document.getElementById('date');
        const endDateInput = document.getElementById('end_date');
        const recurringDayInput = document.getElementById('recurring_day');
        const recurringDayName = document.getElementById('recurring_day_name');

        const daysInIndonesian = {
            'Sunday': 'Minggu',
            'Monday': 'Senin',
            'Tuesday': 'Selasa',
            'Wednesday': 'Rabu',
            'Thursday': 'Kamis',
            'Friday': 'Jumat',
            'Saturday': 'Sabtu'
        };

        function updateRecurringDay() {
            if (dateInput.value) {
                const dateObj = new Date(dateInput.value);
                const dayNameEnglish = dateObj.toLocaleDateString('en-US', { weekday: 'long' });
                const dayNameIndo = daysInIndonesian[dayNameEnglish] || dayNameEnglish;
                recurringDayName.textContent = dayNameIndo;
                recurringDayInput.value = dateObj.getDay(); // 0 = Sunday, 1 = Monday, etc.
                
                // Set min date for end_date to be the day after dateInput
                const nextDay = new Date(dateObj);
                nextDay.setDate(nextDay.getDate() + 1);
                endDateInput.min = nextDay.toISOString().split('T')[0];
            } else {
                recurringDayName.textContent = '-';
                recurringDayInput.value = '';
            }
        }

        if (isRecurringCheckbox && recurringOptions && dateInput) {
            isRecurringCheckbox.addEventListener('change', function () {
                if (this.checked) {
                    if (!dateInput.value) {
                        alert('Silakan pilih Tanggal Mulai terlebih dahulu.');
                        this.checked = false;
                        return;
                    }
                    recurringOptions.style.display = 'block';
                    endDateInput.required = true;
                    updateRecurringDay();
                } else {
                    recurringOptions.style.display = 'none';
                    endDateInput.required = false;
                    endDateInput.value = '';
                    recurringDayInput.value = '';
                }
            });

            dateInput.addEventListener('change', function () {
                if (isRecurringCheckbox.checked) {
                    updateRecurringDay();
                }
            });

            // Initial check on page load (e.g. if old input exists)
            if (isRecurringCheckbox.checked) {
                updateRecurringDay();
            }
        }
    });
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