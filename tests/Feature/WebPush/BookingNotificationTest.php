<?php

namespace Tests\Feature\WebPush;

/**
 * Skenario: notifikasi push seputar siklus hidup peminjaman.
 *
 * Sebagai admin, saya ingin langsung diberi tahu saat ada pengajuan baru.
 * Sebagai peminjam, saya ingin tahu begitu pengajuan saya disetujui/ditolak.
 */
class BookingNotificationTest extends WebPushTestCase
{
    // -----------------------------------------------------------------
    // Admin diberi tahu saat user membuat peminjaman
    // -----------------------------------------------------------------

    public function test_it_notifies_the_admin_when_a_basic_user_creates_a_booking(): void
    {
        // Given: seorang admin dengan perangkat terdaftar, dan seorang user biasa
        $admin = $this->givenAnAdmin();
        $adminDevice = $this->givenADeviceFor($admin);
        $user = $this->givenABasicUser();
        $room = $this->givenARoom('Grha Argya Ballroom');

        // When: user biasa mengajukan peminjaman
        $booking = $this->whenABookingIsCreatedBy($user, $room);

        // Then: push terkirim ke perangkat admin, mengarah ke panel admin
        $this->thenPushWasSentTo([$adminDevice], [
            'title' => 'Peminjaman Baru!',
            'body'  => 'Grha Argya Ballroom',
            'url'   => '/admin/bookings',
            'tag'   => "booking-new-{$booking->id}",
        ]);
    }

    public function test_it_notifies_every_admin_that_has_a_registered_device(): void
    {
        // Given: dua admin, masing-masing satu perangkat
        $firstDevice = $this->givenADeviceFor($this->givenAnAdmin('admin@ruangan.com'));
        $secondDevice = $this->givenADeviceFor($this->givenAnAdmin('admin2@ruangan.com'));

        $user = $this->givenABasicUser();

        // When: ada pengajuan baru
        $this->whenABookingIsCreatedBy($user, $this->givenARoom());

        // Then: kedua perangkat admin masuk dalam satu job yang sama
        $this->thenPushWasSentTo([$firstDevice, $secondDevice], [
            'title' => 'Peminjaman Baru!',
        ]);
    }

    public function test_it_does_not_notify_admins_who_never_enabled_notifications(): void
    {
        // Given: admin tanpa perangkat terdaftar sama sekali
        $this->givenAnAdmin();
        $user = $this->givenABasicUser();

        // When: ada pengajuan baru
        $this->whenABookingIsCreatedBy($user, $this->givenARoom());

        // Then: tidak ada job push untuk admin
        // (user pembuat juga tidak punya perangkat, jadi total nol)
        $this->thenNoPushWasSent();
    }

    public function test_it_does_not_push_a_submission_confirmation_to_the_requesting_user(): void
    {
        // Notifikasi push saat pengajuan sengaja hanya untuk admin (per role).
        // User cukup diberi tahu saat statusnya berubah, supaya tidak
        // menerima push atas aksinya sendiri.

        // Given: admin dan user sama-sama punya perangkat
        $adminDevice = $this->givenADeviceFor($this->givenAnAdmin());
        $user = $this->givenABasicUser();
        $this->givenADeviceFor($user);

        // When: user mengajukan peminjaman
        $this->whenABookingIsCreatedBy($user, $this->givenARoom());

        // Then: hanya perangkat admin yang menerima push
        $this->thenPushWasSentTo([$adminDevice], ['title' => 'Peminjaman Baru!']);
        $this->thenPushWasSentTimes(1);
    }

    // -----------------------------------------------------------------
    // User diberi tahu saat admin menyetujui / menolak
    // -----------------------------------------------------------------

    public function test_it_notifies_the_basic_user_when_the_admin_approves_the_booking(): void
    {
        // Given: sebuah pengajuan milik user yang punya perangkat
        $user = $this->givenABasicUser();
        $userDevice = $this->givenADeviceFor($user);
        $booking = $this->whenABookingIsCreatedBy($user, $this->givenARoom('Ruang Rapat A'));

        // Abaikan notifikasi tahap pengajuan, fokus ke perubahan status
        $this->forgetRecordedPushes();

        // When: admin menyetujui
        $booking->update(['status' => 'approved']);

        // Then: user diberi tahu bahwa peminjaman DISETUJUI
        $this->thenPushWasSentTo([$userDevice], [
            'title' => 'Peminjaman Ruangan DISETUJUI',
            'body'  => 'Ruang Rapat A',
            'url'   => '/user/bookings',
            'tag'   => "booking-status-{$booking->id}",
        ]);
    }

    public function test_it_notifies_the_basic_user_when_the_admin_rejects_the_booking(): void
    {
        // Given: sebuah pengajuan milik user yang punya perangkat
        $user = $this->givenABasicUser();
        $userDevice = $this->givenADeviceFor($user);
        $booking = $this->whenABookingIsCreatedBy($user, $this->givenARoom());

        $this->forgetRecordedPushes();

        // When: admin menolak
        $booking->update(['status' => 'rejected', 'rejection_reason' => 'Bentrok jadwal']);

        // Then: user diberi tahu bahwa peminjaman DITOLAK
        $this->thenPushWasSentTo([$userDevice], [
            'title' => 'Peminjaman Ruangan DITOLAK',
            'url'   => '/user/bookings',
        ]);
    }

    public function test_it_stays_silent_when_an_unrelated_field_is_edited(): void
    {
        // Given: pengajuan milik user yang punya perangkat
        $user = $this->givenABasicUser();
        $this->givenADeviceFor($user);
        $booking = $this->whenABookingIsCreatedBy($user, $this->givenARoom());

        $this->forgetRecordedPushes();

        // When: admin hanya menyunting catatan, status tidak berubah
        $booking->update(['admin_note' => 'Sudah dicek ketersediaannya']);

        // Then: tidak ada notifikasi yang mengganggu user
        $this->thenNoPushWasSent();
    }

    public function test_it_does_not_leak_a_booking_notification_to_another_user(): void
    {
        // Given: pemilik pengajuan dan orang lain, keduanya punya perangkat
        $owner = $this->givenABasicUser();
        $ownerDevice = $this->givenADeviceFor($owner);
        $stranger = $this->givenABasicUser();
        $this->givenADeviceFor($stranger);

        $booking = $this->whenABookingIsCreatedBy($owner, $this->givenARoom());

        $this->forgetRecordedPushes();

        // When: pengajuan disetujui
        $booking->update(['status' => 'approved']);

        // Then: hanya perangkat pemilik yang dikirimi
        $this->thenPushWasSentTo([$ownerDevice], [
            'title' => 'Peminjaman Ruangan DISETUJUI',
        ]);
    }
}
