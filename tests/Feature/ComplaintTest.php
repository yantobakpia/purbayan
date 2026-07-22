<?php

namespace Tests\Feature;

use App\Models\Complaint;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ComplaintTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_submit_complaint(): void
    {
        $user = User::factory()->create();
        $room = \App\Models\Room::create([
            'name' => 'Room A',
            'capacity' => 10,
            'description' => 'Test Room',
        ]);

        $response = $this->actingAs($user)->post(route('complaint'), [
            'name' => 'John Doe',
            'email_or_phone' => '08123456789',
            'complaint_text' => 'AC is not working',
            'room_id' => $room->id,
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('complaints', [
            'name' => 'John Doe',
            'complaint_text' => 'AC is not working',
            'status' => 'pending',
            'room_id' => $room->id,
        ]);
    }

    public function test_admin_can_resolve_complaint(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);
        $complaint = Complaint::create([
            'name' => 'John Doe',
            'email_or_phone' => '08123456789',
            'complaint_text' => 'AC is not working',
            'status' => 'pending',
        ]);

        // Simulate resolving the complaint
        $complaint->update([
            'status' => 'resolved',
            'resolved_at' => now(),
            'resolved_by' => $admin->id,
            'admin_response' => 'AC has been repaired',
        ]);

        $this->assertDatabaseHas('complaints', [
            'id' => $complaint->id,
            'status' => 'resolved',
            'resolved_by' => $admin->id,
            'admin_response' => 'AC has been repaired',
        ]);
    }

    public function test_uploaded_complaint_image_is_converted_to_webp(): void
    {
        \Illuminate\Support\Facades\Storage::fake('public');

        $user = User::factory()->create();
        $file = \Illuminate\Http\UploadedFile::fake()->image('complaint.jpg');

        $response = $this->actingAs($user)->post(route('complaint'), [
            'name' => 'John Doe',
            'email_or_phone' => '08123456789',
            'complaint_text' => 'AC is not working',
            'photo' => $file,
        ]);

        $response->assertRedirect();

        $complaint = Complaint::first();
        $this->assertNotNull($complaint->photo_path);
        $this->assertStringEndsWith('.webp', $complaint->photo_path);

        \Illuminate\Support\Facades\Storage::disk('public')->assertExists($complaint->photo_path);
    }

    public function test_admin_receives_notification_on_complaint_submission(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);
        $user = User::factory()->create();

        $this->actingAs($user)->post(route('complaint'), [
            'name' => 'John Doe',
            'email_or_phone' => '08123456789',
            'complaint_text' => 'AC is not working',
        ]);

        $this->assertDatabaseCount('notifications', 1);
        $notification = \Illuminate\Support\Facades\DB::table('notifications')->first();
        $this->assertEquals($admin->id, $notification->notifiable_id);
        $this->assertStringContainsString('Keluhan Baru!', $notification->data);
    }
}
