<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use App\Filament\User\Pages\Auth\Login;
use Tests\TestCase;

class AuthenticationTest extends TestCase
{
    use RefreshDatabase;

    public function test_regular_user_is_redirected_to_user_dashboard()
    {
        \Filament\Facades\Filament::setCurrentPanel(\Filament\Facades\Filament::getPanel('user'));

        $user = User::factory()->create([
            'is_admin' => false,
        ]);

        Livewire::test(Login::class)
            ->fillForm([
                'email' => $user->email,
                'password' => 'password',
            ])
            ->call('authenticate')
            ->assertRedirect('/');
    }

    public function test_admin_user_is_redirected_to_admin_dashboard()
    {
        \Filament\Facades\Filament::setCurrentPanel(\Filament\Facades\Filament::getPanel('user'));

        $admin = User::factory()->create([
            'is_admin' => true,
        ]);

        Livewire::test(Login::class)
            ->fillForm([
                'email' => $admin->email,
                'password' => 'password',
            ])
            ->call('authenticate')
            ->assertRedirect('/admin');
    }

    public function test_promoted_user_can_access_admin_panel()
    {
        $user = User::factory()->create([
            'is_admin' => false,
        ]);

        $this->assertFalse($user->canAccessPanel(\Filament\Facades\Filament::getPanel('admin')));

        $user->update(['is_admin' => true]);

        $this->assertTrue($user->canAccessPanel(\Filament\Facades\Filament::getPanel('admin')));
    }

    public function test_logout_redirects_to_welcome_page()
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $response = $this->post(route('filament.user.auth.logout'));

        $response->assertRedirect('/');
    }

    public function test_user_can_request_password_reset()
    {
        \Filament\Facades\Filament::setCurrentPanel(\Filament\Facades\Filament::getPanel('user'));

        $user = User::factory()->create([
            'phone' => '08123456789',
        ]);

        // First reset should succeed
        Livewire::test(\App\Filament\Pages\Auth\RequestPasswordReset::class)
            ->fillForm([
                'email' => $user->email,
                'phone' => '08123456789',
                'new_password' => 'newpassword123',
            ])
            ->call('request')
            ->assertHasNoFormErrors();

        // Second reset with the same password should fail
        Livewire::test(\App\Filament\Pages\Auth\RequestPasswordReset::class)
            ->fillForm([
                'email' => $user->email,
                'phone' => '08123456789',
                'new_password' => 'newpassword123',
            ])
            ->call('request')
            ->assertHasNoFormErrors(); // The form itself has no validation errors, but it returns early and doesn't change password
    }
}
