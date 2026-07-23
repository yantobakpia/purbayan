<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Keluhan & Masukan - Sistem Peminjaman Ruangan</title>
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
            justify-content: center; 
            gap: 0.5rem;
            padding: 0.85rem 1rem; 
            position: sticky; 
            top: 0; 
            z-index: 100;
            box-shadow: 0 4px 12px rgba(0,0,0,0.03);
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
            .hero-title { font-size: 1.75rem; }
            .container { padding: 0 1rem; }
            .form-card { padding: 1.5rem; }
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
    <h1 class="hero-title">Keluhan & Masukan</h1>
    <p class="hero-subtitle">Sampaikan keluhan atau masukan Anda mengenai fasilitas ruangan</p>
</header>

<nav>
    <a href="/">Beranda</a>
    <a href="{{ route('jadwal.page') }}">Status & Kalender</a>
    <a href="{{ route('peminjaman.page') }}">Pesan Ruangan</a>
    <a href="{{ route('complaint.page') }}" class="active">Keluhan</a>
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
    @if(session('complaint_success'))
        <div class="alert alert-success">{{ session('complaint_success') }}</div>
    @endif

    @if($errors->any())
        <div class="alert alert-danger">
            <ul style="margin-left: 1.25rem; list-style-type: disc;">
                @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <div class="form-card">
        <form method="POST" action="{{ route('complaint') }}" enctype="multipart/form-data">
            @csrf
            <div class="form-grid">
                <div class="form-group full">
                    <label for="c_name">Nama</label>
                    <input type="text" name="name" id="c_name" value="{{ auth()->check() ? auth()->user()->name : '' }}" placeholder="Nama Anda" required>
                </div>
                <input type="hidden" name="email_or_phone" value="{{ auth()->check() ? (auth()->user()->phone ?? '08123456789') : '08123456789' }}">
                <div class="form-group full">
                    <label for="c_room">Ruangan (Opsional)</label>
                    <select name="room_id" id="c_room">
                        <option value="">-- Pilih Ruangan (Opsional) --</option>
                        @foreach($rooms as $room)
                            <option value="{{ $room->id }}" {{ old('room_id') == $room->id ? 'selected' : '' }}>{{ $room->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="form-group full">
                    <label for="c_text">Isi Keluhan</label>
                    <textarea name="complaint_text" id="c_text" placeholder="Tuliskan keluhan atau masukan Anda..." required></textarea>
                </div>
                <div class="form-group full">
                    <label for="c_photo">Foto Keluhan (Opsional)</label>
                    <input type="file" name="photo" id="c_photo" accept="image/*" style="width: 100%; padding: 0.5rem; border: 1px solid var(--border); border-radius: 8px;">
                </div>
            </div>
            <div style="margin-top:1.25rem">
                <button type="submit" class="btn btn-warning">Kirim Keluhan</button>
            </div>
        </form>
    </div>

    @auth
        <div style="margin-top: 3rem; margin-bottom: 2rem;">
            <h2 style="font-size: 1.6rem; font-weight: 700; margin-bottom: 1.5rem; color: var(--text); display: flex; align-items: center; gap: 0.5rem;">
                Riwayat Keluhan Saya
            </h2>
            @if($myComplaints->isEmpty())
                <p style="color: var(--muted); margin-bottom: 2rem;">Anda belum pernah mengirimkan keluhan.</p>
            @else
                <div style="display: flex; flex-direction: column; gap: 1rem;">
                    @foreach($myComplaints as $complaint)
                        <div style="background: var(--card); border-radius: var(--radius); box-shadow: var(--shadow); padding: 1.5rem; border: 1px solid var(--border); display: flex; flex-direction: column; gap: 1rem;">
                            <div style="display: flex; justify-content: space-between; align-items: center; border-bottom: 1px solid var(--border); padding-bottom: 0.75rem;">
                                <div>
                                    <span style="font-size: 0.85rem; color: var(--muted);">Dikirim pada: {{ $complaint->created_at->format('d M Y H:i') }} WIB</span>
                                    @if($complaint->room)
                                        <span style="margin-left: 0.5rem; font-size: 0.85rem; font-weight: 600; color: var(--primary);">• Ruangan: {{ $complaint->room->name }}</span>
                                    @endif
                                </div>
                                <div>
                                    @if($complaint->status === 'resolved')
                                        <span class="badge" style="background: #dcfce7; color: #15803d; border: 1px solid #86efac;">Selesai</span>
                                    @else
                                        <span class="badge" style="background: #fef3c7; color: #d97706; border: 1px solid #fcd34d;">Pending</span>
                                    @endif
                                </div>
                            </div>
                            <div>
                                <p style="font-weight: 600; margin-bottom: 0.5rem; color: var(--text);">Isi Keluhan:</p>
                                <p style="color: var(--text); white-space: pre-line;">{{ $complaint->complaint_text }}</p>
                                @if($complaint->photo_path)
                                    <div style="margin-top: 0.75rem;">
                                        <a href="{{ asset('storage/' . $complaint->photo_path) }}" target="_blank" style="display: inline-flex; align-items: center; gap: 0.25rem; font-size: 0.85rem; color: var(--primary); text-decoration: none; font-weight: 600;">
                                            🖼️ Lihat Foto Lampiran
                                        </a>
                                    </div>
                                @endif
                            </div>
                            @if($complaint->admin_response)
                                <div style="background: #f8fafc; border-left: 4px solid var(--primary); padding: 1rem; border-radius: 8px; margin-top: 0.5rem;">
                                    <p style="font-weight: 700; color: var(--primary); margin-bottom: 0.25rem; font-size: 0.9rem;">Tindak Lanjut Admin:</p>
                                    <p style="color: var(--text); font-size: 0.9rem; white-space: pre-line;">{{ $complaint->admin_response }}</p>
                                    <div style="font-size: 0.75rem; color: var(--muted); display: flex; flex-direction: column; gap: 0.15rem; margin-top: 0.5rem;">
                                        @if($complaint->resolved_at)
                                            <span>📅 Tanggal & Waktu: {{ $complaint->resolved_at->format('d M Y H:i') }} WIB</span>
                                        @endif
                                        @if($complaint->resolver)
                                            <span>👤 Ditanggapi oleh: {{ $complaint->resolver->name }}</span>
                                        @endif
                                    </div>
                                </div>
                            @endif
                        </div>
                    @endforeach
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