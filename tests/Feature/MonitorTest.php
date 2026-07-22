<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MonitorTest extends TestCase
{
    use RefreshDatabase;

    public function test_non_admin_cannot_access_monitor_page(): void
    {
        $user = User::factory()->create(['is_admin' => false]);
        $response = $this->actingAs($user)->get(route('monitor'));
        $response->assertStatus(403);
    }

    public function test_admin_can_access_monitor_page(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);
        $response = $this->actingAs($admin)->get(route('monitor'));
        $response->assertStatus(200);
    }

    public function test_guest_cannot_access_unread_notifications(): void
    {
        $response = $this->getJson(route('admin.unread-notifications'));
        $response->assertStatus(401);
    }

    public function test_authenticated_user_can_access_unread_notifications(): void
    {
        $user = User::factory()->create();
        $response = $this->actingAs($user)->getJson(route('admin.unread-notifications'));
        $response->assertStatus(200);
        $response->assertJson([]);
    }
}
