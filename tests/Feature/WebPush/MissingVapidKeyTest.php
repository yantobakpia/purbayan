<?php

namespace Tests\Feature\WebPush;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Skenario: deploy tapi lupa menjalankan `php artisan webpush:keys`.
 *
 * Harapannya: aplikasi tetap jalan normal. Fitur push saja yang mati,
 * dan matinya harus tercatat di log supaya mudah ditemukan.
 */
class MissingVapidKeyTest extends WebPushTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        // Kunci VAPID kosong, persis seperti server yang belum disiapkan.
        $this->givenPushIsConfigured(false);
    }

    public function test_bookings_can_still_be_created(): void
    {
        $user = $this->givenABasicUser();
        $this->givenADeviceFor($this->givenAnAdmin());

        $booking = $this->whenABookingIsCreatedBy($user, $this->givenARoom());

        // Tidak ada exception, data tetap tersimpan
        $this->assertDatabaseHas('bookings', ['id' => $booking->id, 'status' => 'pending']);
    }

    public function test_bookings_can_still_be_approved(): void
    {
        $user = $this->givenABasicUser();
        $this->givenADeviceFor($user);
        $booking = $this->whenABookingIsCreatedBy($user, $this->givenARoom());

        $booking->update(['status' => 'approved']);

        $this->assertSame('approved', $booking->fresh()->status);
    }

    public function test_the_filament_bell_notification_still_works(): void
    {
        $admin = $this->givenAnAdmin();
        $user = $this->givenABasicUser();

        $this->whenABookingIsCreatedBy($user, $this->givenARoom());

        // Notifikasi database (lonceng Filament) tidak terpengaruh sama sekali
        $this->assertSame(1, DB::table('notifications')->where('notifiable_id', $admin->id)->count());
    }

    public function test_no_push_job_is_dispatched_at_all(): void
    {
        $this->givenADeviceFor($this->givenAnAdmin());
        $user = $this->givenABasicUser();

        $this->whenABookingIsCreatedBy($user, $this->givenARoom());

        $this->thenNoPushWasSent();
    }

    public function test_it_writes_a_warning_so_the_cause_is_findable(): void
    {
        $this->givenADeviceFor($this->givenAnAdmin());
        $user = $this->givenABasicUser();

        Log::spy();

        $this->whenABookingIsCreatedBy($user, $this->givenARoom());

        Log::shouldHaveReceived('warning');
    }

    public function test_the_api_reports_push_as_unavailable(): void
    {
        $this->getJson('/push/public-key')
            ->assertOk()
            ->assertJson(['available' => false]);
    }

    public function test_pages_still_render_with_an_empty_vapid_meta_tag(): void
    {
        // Halaman tetap 200, hanya meta vapid-key-nya kosong sehingga
        // klien tahu harus menampilkan pesan "belum dikonfigurasi".
        $this->actingAs($this->givenABasicUser())
            ->get('/')
            ->assertOk()
            ->assertSee('name="vapid-key" content=""', false);
    }

    public function test_the_profile_page_still_loads(): void
    {
        $this->actingAs($this->givenABasicUser())
            ->get('/user/profile')
            ->assertOk()
            ->assertSee('Notifikasi Aplikasi');
    }
}
