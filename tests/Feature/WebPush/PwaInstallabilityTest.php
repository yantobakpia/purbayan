<?php

namespace Tests\Feature\WebPush;

/**
 * Skenario: syarat agar aplikasi bisa dipasang (installable) dan menerima push.
 *
 * Ini melindungi hal-hal yang gampang rusak diam-diam: manifest salah ketik,
 * ikon terhapus, atau meta tag hilang dari layout. Kalau salah satunya rusak,
 * tombol "Install app" tidak muncul dan iOS tidak bisa Add to Home Screen —
 * tanpa error apa pun di server.
 */
class PwaInstallabilityTest extends WebPushTestCase
{
    protected function manifest(): array
    {
        $path = public_path('manifest.json');
        $this->assertFileExists($path, 'manifest.json wajib ada agar aplikasi bisa dipasang.');

        $manifest = json_decode(file_get_contents($path), true);
        $this->assertIsArray($manifest, 'manifest.json harus JSON yang valid.');

        return $manifest;
    }

    public function test_the_manifest_declares_everything_needed_to_be_installable(): void
    {
        $manifest = $this->manifest();

        foreach (['name', 'short_name', 'start_url', 'scope', 'display', 'icons'] as $field) {
            $this->assertArrayHasKey($field, $manifest, "manifest.json wajib punya '{$field}'.");
        }

        // Tanpa 'standalone', aplikasi terbuka seperti tab biasa dan
        // iOS tidak menganggapnya aplikasi terpasang.
        $this->assertSame('standalone', $manifest['display']);
    }

    public function test_the_manifest_ships_the_icon_sizes_browsers_require(): void
    {
        $icons = collect($this->manifest()['icons']);

        // Chrome mensyaratkan minimal ada 192px dan 512px untuk bisa dipasang.
        foreach (['192x192', '512x512'] as $size) {
            $this->assertTrue(
                $icons->contains(fn ($icon) => ($icon['sizes'] ?? '') === $size && ($icon['type'] ?? '') === 'image/png'),
                "manifest.json wajib punya ikon PNG {$size}."
            );
        }

        // Ikon maskable supaya tidak terpotong jelek di launcher Android.
        $this->assertTrue(
            $icons->contains(fn ($icon) => str_contains($icon['purpose'] ?? '', 'maskable')),
            'manifest.json sebaiknya punya ikon maskable.'
        );
    }

    public function test_every_icon_listed_in_the_manifest_actually_exists(): void
    {
        foreach ($this->manifest()['icons'] as $icon) {
            $path = public_path(ltrim($icon['src'], '/'));
            $this->assertFileExists($path, "Ikon terdaftar di manifest tapi filenya tidak ada: {$icon['src']}");
        }
    }

    public function test_the_apple_touch_icon_exists_for_ios_home_screen(): void
    {
        // Safari tidak memakai ikon dari manifest untuk Add to Home Screen;
        // ia butuh apple-touch-icon PNG tersendiri.
        $this->assertFileExists(public_path('apple-touch-icon.png'));
    }

    public function test_the_service_worker_handles_push_and_clicks(): void
    {
        $path = public_path('sw.js');
        $this->assertFileExists($path, 'sw.js wajib ada di root agar scope-nya seluruh situs.');

        $source = file_get_contents($path);

        $this->assertStringContainsString("addEventListener('push'", $source, 'sw.js harus menangani event push.');
        $this->assertStringContainsString("addEventListener('notificationclick'", $source, 'sw.js harus menangani klik notifikasi.');
        $this->assertStringContainsString('showNotification', $source);
    }

    public function test_the_service_worker_never_caches_private_pages(): void
    {
        $source = file_get_contents(public_path('sw.js'));

        // Halaman panel tidak boleh masuk cache: bisa basi, dan bisa bocor
        // antar akun kalau perangkat dipakai bergantian.
        foreach (['/admin', '/user', '/livewire'] as $path) {
            $this->assertStringContainsString("'{$path}'", $source, "sw.js harus mengecualikan {$path} dari cache.");
        }
    }

    public function test_the_offline_page_and_client_script_are_present(): void
    {
        $this->assertFileExists(public_path('offline.html'));
        $this->assertFileExists(public_path('js/pwa.js'));
    }

    public function test_public_pages_advertise_the_pwa_to_the_browser(): void
    {
        // When: user yang sudah login membuka halaman depan
        // (semua route kini berada di balik middleware auth)
        $response = $this->actingAs($this->givenABasicUser())->get('/');

        // Then: browser menemukan manifest, service worker, dan kunci VAPID
        $response->assertOk()
            ->assertSee('rel="manifest"', false)
            ->assertSee('/js/pwa.js', false)
            ->assertSee('apple-touch-icon', false)
            ->assertSee('name="vapid-key"', false);
    }

    public function test_the_profile_page_renders_the_notification_settings_card(): void
    {
        // When: user membuka halaman profil
        $response = $this->actingAs($this->givenABasicUser())->get('/user/profile');

        // Then: kartu pengaturan notifikasi ikut ter-render lengkap dengan
        // tombolnya. Komponen Blade yang salah nama akan hilang diam-diam,
        // jadi ini penjaga supaya kartunya tidak lenyap tanpa error.
        $response->assertOk()
            ->assertSee('Notifikasi Aplikasi')
            ->assertSee('data-push-status', false)
            ->assertSee('data-push-toggle', false)
            ->assertSee('data-push-test', false);

        // Perilakunya datang dari file JS terpisah, dimuat tepat sekali.
        $this->assertSame(1, substr_count($response->getContent(), '/js/push-notification-card.js'));
    }

    public function test_the_notification_card_can_be_used_twice_on_one_page(): void
    {
        // Given: dua instance komponen dalam satu halaman
        $html = (string) $this->blade('<x-push-notification-card /><x-push-notification-card />');

        // Then: dua kartu ter-render...
        $this->assertSame(2, substr_count($html, 'data-push-card'));

        // ...tapi skripnya hanya dimuat sekali (berkat @once), supaya
        // event listener tidak terpasang dobel.
        $this->assertSame(1, substr_count($html, 'push-notification-card.js'));
    }

    public function test_the_notification_card_keeps_its_behaviour_in_a_separate_file(): void
    {
        // Markup dan perilaku sengaja dipisah: komponennya bebas skrip inline.
        $component = file_get_contents(resource_path('views/components/push-notification-card.blade.php'));

        $this->assertStringNotContainsString('addEventListener', $component, 'Logika kartu seharusnya ada di file JS, bukan inline di Blade.');
        $this->assertStringContainsString('/js/push-notification-card.js', $component);

        $script = public_path('js/push-notification-card.js');
        $this->assertFileExists($script);

        // Skrip harus membaca kunci VAPID dari meta tag, bukan dari Blade,
        // supaya file-nya tetap statis dan bisa di-cache.
        $this->assertStringContainsString('meta[name="vapid-key"]', file_get_contents($script));
    }

    public function test_the_page_tells_the_client_that_the_visitor_is_logged_in(): void
    {
        // Klien memakai meta ini untuk memutuskan boleh menawarkan
        // aktivasi notifikasi atau tidak.
        $this->actingAs($this->givenABasicUser())
            ->get('/')
            ->assertSee('name="pwa-authenticated" content="1"', false);
    }
}
