<?php

namespace Tests\Feature\WebPush;

use App\Models\Complaint;

/**
 * Skenario: notifikasi push untuk keluhan.
 *
 * Sebagai admin, saya ingin segera tahu kalau ada keluhan masuk.
 */
class ComplaintNotificationTest extends WebPushTestCase
{
    public function test_it_notifies_the_admin_when_a_complaint_arrives(): void
    {
        // Given: admin dengan perangkat terdaftar
        $adminDevice = $this->givenADeviceFor($this->givenAnAdmin());

        // When: ada keluhan baru masuk
        $complaint = Complaint::create([
            'name'           => 'Budi',
            'email_or_phone' => '08123456789',
            'complaint_text' => 'AC di ruang rapat tidak dingin sejak kemarin.',
            'status'         => 'pending',
        ]);

        // Then: admin menerima push yang mengarah ke daftar keluhan
        $this->thenPushWasSentTo([$adminDevice], [
            'title' => 'Keluhan Baru!',
            'body'  => 'Budi',
            'url'   => '/admin/complaints',
            'tag'   => "complaint-new-{$complaint->id}",
        ]);
    }

    public function test_it_mentions_the_room_when_the_complaint_names_one(): void
    {
        // Given: admin dengan perangkat, dan sebuah ruangan
        $adminDevice = $this->givenADeviceFor($this->givenAnAdmin());
        $room = $this->givenARoom('Ruang Rapat B');

        // When: keluhan dikaitkan ke ruangan tersebut
        Complaint::create([
            'name'           => 'Siti',
            'email_or_phone' => '08987654321',
            'complaint_text' => 'Proyektor mati.',
            'room_id'        => $room->id,
            'status'         => 'pending',
        ]);

        // Then: nama ruangan ikut disebut, supaya admin tahu konteksnya
        $this->thenPushWasSentTo([$adminDevice], [
            'title' => 'Keluhan Baru!',
            'body'  => 'Ruang Rapat B',
        ]);
    }

    public function test_it_sends_nothing_when_no_admin_has_enabled_notifications(): void
    {
        // Given: admin tanpa perangkat
        $this->givenAnAdmin();

        // When: keluhan masuk
        Complaint::create([
            'name'           => 'Andi',
            'email_or_phone' => '08111111111',
            'complaint_text' => 'Kursi rusak.',
            'status'         => 'pending',
        ]);

        // Then: tidak ada job push yang sia-sia
        $this->thenNoPushWasSent();
    }
}
