{{--
    Kartu pengaturan notifikasi push.

    Pemakaian:
        <x-push-notification-card />
        <x-push-notification-card heading="Notifikasi" class="mt-4" />

    Perilakunya ada di /js/push-notification-card.js, yang mencari setiap
    elemen [data-push-card]. Skripnya butuh /js/pwa.js dan meta tag
    `vapid-key` — keduanya sudah dimuat oleh partial `pwa-head`.

    Gaya visualnya memakai kelas halaman publik (.form-card, .btn, .btn-primary)
    dan variabel CSS --muted / --border.
--}}

@props([
    'heading' => '🔔 Notifikasi Aplikasi',
])

<div data-push-card {{ $attributes->merge(['class' => 'form-card', 'style' => 'margin-top: 1.5rem;']) }}>
    <h3 style="font-size: 1.05rem; font-weight: 700; margin-bottom: 0.35rem;">{{ $heading }}</h3>

    <p data-push-status style="color: var(--muted); font-size: 0.875rem; line-height: 1.6;">
        Memeriksa dukungan notifikasi...
    </p>

    <div data-push-actions style="display: none; gap: 0.75rem; margin-top: 1.25rem; flex-wrap: wrap;">
        <button type="button" data-push-toggle class="btn btn-primary">Aktifkan Notifikasi</button>
        <button type="button" data-push-test class="btn" style="display: none; border: 1px solid var(--border);">Kirim Tes</button>
    </div>
</div>

{{-- Dimuat sekali saja walau komponennya dipakai beberapa kali. --}}
@once
    <script src="/js/push-notification-card.js" defer></script>
@endonce
