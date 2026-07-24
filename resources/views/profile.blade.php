<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Profil Saya - Sistem Peminjaman Ruangan</title>
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

        .container { max-width: 800px; margin: 2rem auto; padding: 0 1.5rem; flex: 1; width: 100%; }

        .alert { 
            padding: 1rem 1.25rem; 
            border-radius: var(--radius-md); 
            margin-bottom: 1.75rem; 
            font-weight: 600;
            font-size: 0.95rem;
        }
        .alert-success { background: var(--success-bg); color: var(--success-text); border: 1px solid var(--success-border); }
        .alert-danger  { background: var(--danger-bg); color: var(--danger-text); border: 1px solid var(--danger-border); }

        .profile-header-card {
            background: linear-gradient(135deg, #1e3a8a 0%, #3b82f6 100%);
            border-radius: var(--radius-lg);
            padding: 2rem;
            color: white;
            display: flex;
            align-items: center;
            gap: 1.5rem;
            margin-bottom: 2rem;
            box-shadow: var(--shadow-md);
        }
        .profile-avatar {
            width: 70px;
            height: 70px;
            border-radius: 50%;
            background: rgba(255, 255, 255, 0.2);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 2rem;
            font-weight: 800;
            border: 3px solid rgba(255, 255, 255, 0.4);
            text-transform: uppercase;
        }
        .profile-info h2 {
            font-size: 1.5rem;
            font-weight: 800;
            margin-bottom: 0.25rem;
        }
        .profile-info p {
            font-size: 0.95rem;
            color: #bfdbfe;
        }

        .form-card { 
            background: var(--card); 
            border-radius: var(--radius-lg); 
            border: 1px solid var(--border);
            box-shadow: var(--shadow-md); 
            padding: 2.25rem; 
            margin-bottom: 2.5rem; 
        }

        .form-grid { display: grid; grid-template-columns: repeat(2, 1fr); gap: 1.5rem; }
        .form-group { display: flex; flex-direction: column; gap: 0.45rem; }
        .form-group.full-width { grid-column: span 2; }
        
        label { font-weight: 700; font-size: 0.875rem; color: #475569; }
        input { 
            border: 1.5px solid var(--border); 
            border-radius: var(--radius-md); 
            padding: 0.75rem 1rem; 
            font-size: 0.95rem; 
            font-family: inherit; 
            transition: all 0.2s; 
            background: #f8fafc; 
            color: var(--text);
        }
        input:focus { 
            outline: none; 
            border-color: var(--primary); 
            background: white;
            box-shadow: 0 0 0 4px rgba(37,99,235,0.1); 
        }
        
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

        @media (max-width: 768px) {
            .hero { padding: 2.5rem 1rem 3rem 1rem; }
            .hero-title { font-size: 1.85rem; }
            .hero-subtitle { font-size: 0.95rem; margin-bottom: 1.5rem; }
            .container { padding: 1.5rem 1rem; }
            .profile-header-card { flex-direction: column; text-align: center; padding: 1.5rem; gap: 1rem; }
            .form-card { padding: 1.25rem; }
            .form-grid { grid-template-columns: 1fr; gap: 1.25rem; }
            .form-group.full-width { grid-column: span 1; }
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
    <h1 class="hero-title">Profil Saya</h1>
    <p class="hero-subtitle">Kelola informasi profil dan ubah password Anda</p>
</header>

<nav>
    <a href="/">Beranda</a>
    <a href="{{ route('jadwal.page') }}">Status & Kalender</a>
    <a href="{{ route('peminjaman.page') }}">Pesan Ruangan</a>
    <a href="{{ route('complaint.page') }}">Keluhan</a>
    @auth
        <a href="/user/profile" class="active">Profil Saya</a>
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

    @if($errors->any())
        <div class="alert alert-danger">
            <ul style="margin-left: 1.25rem; list-style-type: disc;">
                @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <div class="profile-header-card">
        <div class="profile-avatar">
            {{ substr(auth()->user()->name, 0, 2) }}
        </div>
        <div class="profile-info">
            <h2>{{ auth()->user()->name }}</h2>
            <p>{{ auth()->user()->email }}</p>
        </div>
    </div>

    <div class="form-card">
        <form method="POST" action="{{ route('profile.update') }}">
            @csrf
            <div class="form-grid">
                <div class="form-group">
                    <label for="name">Nama Lengkap</label>
                    <input type="text" name="name" id="name" value="{{ old('name', auth()->user()->name) }}" required>
                </div>
                <div class="form-group">
                    <label for="email">Email</label>
                    <input type="email" name="email" id="email" value="{{ old('email', auth()->user()->email) }}" required>
                </div>
                <div class="form-group full-width">
                    <label for="phone">No. HP / WhatsApp</label>
                    <input type="tel" name="phone" id="phone" value="{{ old('phone', auth()->user()->phone) }}" placeholder="Contoh: 08123456789" required>
                </div>
                <div class="form-group full-width" style="margin: 0.5rem 0;">
                    <hr style="border: 0; border-top: 1px solid var(--border);">
                </div>
                <div class="form-group">
                    <label for="password">Password Baru (Kosongkan jika tidak ingin diubah)</label>
                    <input type="password" name="password" id="password" placeholder="Minimal 4 karakter">
                </div>
                <div class="form-group">
                    <label for="password_confirmation">Konfirmasi Password Baru</label>
                    <input type="password" name="password_confirmation" id="password_confirmation" placeholder="Ulangi password baru">
                </div>
            </div>
            <div style="margin-top: 2rem;">
                <button type="submit" class="btn btn-primary" style="width: 100%;">Simpan Perubahan</button>
            </div>
        </form>
    </div>
</div>

<footer>
    <p>&copy; {{ date('Y') }} {{ config('app.name') }}. All rights reserved.</p>
</footer>

</body>
</html>
