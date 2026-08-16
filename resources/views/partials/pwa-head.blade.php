{{-- Meta & aset PWA. Disertakan di semua halaman publik dan panel Filament. --}}
<link rel="manifest" href="/manifest.json">
<meta name="theme-color" content="#2563eb">
<meta name="application-name" content="Pinjam Ruang">

{{-- iOS: wajib supaya bisa "Add to Home Screen" dan menerima Web Push (iOS 16.4+). --}}
<meta name="apple-mobile-web-app-capable" content="yes">
<meta name="mobile-web-app-capable" content="yes">
<meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
<meta name="apple-mobile-web-app-title" content="Pinjam Ruang">
<link rel="apple-touch-icon" href="/apple-touch-icon.png">
<link rel="icon" type="image/png" sizes="192x192" href="/icons/icon-192.png">
<link rel="icon" type="image/svg+xml" href="/icon.svg">

{{-- Konfigurasi untuk /js/pwa.js --}}
<meta name="vapid-key" content="{{ config('webpush.public_key') }}">
<meta name="pwa-authenticated" content="{{ auth()->check() ? '1' : '0' }}">
<meta name="pwa-csrf" content="{{ csrf_token() }}">

<script src="/js/pwa.js" defer></script>
