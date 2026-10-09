<?php

namespace Tests\Feature;

use App\Filament\Pages\Auth\Login;
use App\Filament\Resources\UserResource\Pages\ListUsers;
use App\Models\User;
use App\Services\LoginThrottle;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Livewire\Livewire;
use Tests\TestCase;

class LoginCooldownTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Filament::setCurrentPanel(Filament::getPanel('user'));
    }

    private function login(string $email, string $password)
    {
        return Livewire::test(Login::class)
            ->fillForm(['email' => $email, 'password' => $password])
            ->call('authenticate');
    }

    private function failTimes(User $user, int $n): void
    {
        for ($i = 0; $i < $n; $i++) {
            $this->login($user->email, 'salah');
        }
    }

    private function asAdmin(): User
    {
        $admin = User::factory()->create(['is_admin' => true]);
        $this->actingAs($admin);
        Filament::setCurrentPanel(Filament::getPanel('admin'));

        return $admin;
    }

    public function test_01_first_failure_no_cooldown(): void
    {
        $user = User::factory()->create();
        $this->login($user->email, 'salah')->assertHasFormErrors(['email']);

        $this->assertSame(1, LoginThrottle::attempts($user->email));
        $this->assertFalse(LoginThrottle::isLocked($user->email));
    }

    public function test_02_second_failure_no_cooldown(): void
    {
        $user = User::factory()->create();
        $this->failTimes($user, 2);

        $this->assertSame(2, LoginThrottle::attempts($user->email));
        $this->assertFalse(LoginThrottle::isLocked($user->email));
    }

    public function test_03_third_failure_triggers_cooldown(): void
    {
        $user = User::factory()->create();
        $this->failTimes($user, 3);

        $this->assertTrue(LoginThrottle::isLocked($user->email));
        $this->assertGreaterThan(890, LoginThrottle::availableIn($user->email));
    }

    public function test_04_success_resets_counter(): void
    {
        $user = User::factory()->create();
        $this->failTimes($user, 2);

        $this->login($user->email, 'password')->assertRedirect('/');
        $this->assertSame(0, LoginThrottle::attempts($user->email));

        // After a success, another failure starts from 1 (not straight to 3).
        auth()->logout();
        $this->login($user->email, 'salah');
        $this->assertSame(1, LoginThrottle::attempts($user->email));
        $this->assertFalse(LoginThrottle::isLocked($user->email));
    }

    public function test_05_locked_user_cannot_login_with_correct_password(): void
    {
        $user = User::factory()->create();
        $this->failTimes($user, 3);

        $this->login($user->email, 'password')->assertNotified()->assertNoRedirect();
        $this->assertGuest();
    }

    public function test_06_admin_can_reset_cooldown_and_audit_logged(): void
    {
        $user = User::factory()->create();
        $this->failTimes($user, 3);
        Log::spy();
        $admin = $this->asAdmin();

        Livewire::test(ListUsers::class)
            ->assertTableActionEnabled('reset_cooldown', $user)
            ->callTableAction('reset_cooldown', $user)
            ->assertNotified('Cooldown direset');

        $this->assertFalse(LoginThrottle::isLocked($user->email));
        $this->assertSame(0, LoginThrottle::attempts($user->email));
        Log::shouldHaveReceived('info')->withArgs(fn ($msg, $ctx) => $msg === 'login_cooldown_reset'
            && $ctx['admin_id'] === $admin->id
            && $ctx['target_user_id'] === $user->id
            && $ctx['result'] === 'success'
            && ! array_key_exists('password', $ctx))->once();
    }

    public function test_07_user_can_login_after_reset(): void
    {
        $user = User::factory()->create();
        $this->failTimes($user, 3);
        $this->asAdmin();
        Livewire::test(ListUsers::class)->callTableAction('reset_cooldown', $user);

        auth()->logout();
        Filament::setCurrentPanel(Filament::getPanel('user'));
        $this->login($user->email, 'password')->assertRedirect('/');
        $this->assertAuthenticatedAs($user);
    }

    public function test_08_non_admin_cannot_reset(): void
    {
        $victim = User::factory()->create();
        $this->failTimes($victim, 3);
        $regular = User::factory()->create(['is_admin' => false]);
        $this->actingAs($regular);

        // Admin panel rejected at HTTP level.
        $this->get('/admin/users')->assertForbidden();

        // Even when the Livewire component is called directly, the action is hidden/unauthorized and does not run.
        Filament::setCurrentPanel(Filament::getPanel('admin'));
        Livewire::test(ListUsers::class)
            ->assertTableActionHidden('reset_cooldown', $victim)
            // Forged request: call Livewire methods directly, bypassing the UI.
            ->call('mountTableAction', 'reset_cooldown', (string) $victim->getKey())
            ->call('callMountedTableAction');

        $this->assertTrue(LoginThrottle::isLocked($victim->email));
    }

    public function test_09_reset_one_user_does_not_affect_other(): void
    {
        $a = User::factory()->create();
        $b = User::factory()->create();
        $this->failTimes($a, 3);
        $this->failTimes($b, 3);
        $this->asAdmin();

        Livewire::test(ListUsers::class)->callTableAction('reset_cooldown', $a);

        $this->assertFalse(LoginThrottle::isLocked($a->email));
        $this->assertTrue(LoginThrottle::isLocked($b->email));
        $this->assertSame(3, LoginThrottle::attempts($b->email));
    }

    public function test_10_cooldown_expires(): void
    {
        $user = User::factory()->create();
        $this->failTimes($user, 3);

        $this->travel(LoginThrottle::DECAY_SECONDS + 1)->seconds();

        $this->assertFalse(LoginThrottle::isLocked($user->email));
        $this->assertSame(0, LoginThrottle::attempts($user->email));
        $this->login($user->email, 'password')->assertRedirect('/');
    }

    public function test_11_no_double_counting_and_not_shared_per_ip(): void
    {
        $user = User::factory()->create();
        $other = User::factory()->create();
        $this->login($user->email, 'salah');
        $this->assertSame(1, LoginThrottle::attempts($user->email));

        // Same IP (127.0.0.1), different user: counter separate, first attempt not locked.
        $this->login($other->email, 'password')->assertRedirect('/');

        // Wrong panel with correct password does not count.
        auth()->logout();
        Filament::setCurrentPanel(Filament::getPanel('admin'));
        $this->login($user->email, 'password')->assertHasFormErrors(['email']);
        $this->assertSame(1, LoginThrottle::attempts($user->email));
    }

    public function test_12_state_consistent_with_cache_store(): void
    {
        $user = User::factory()->create();
        $this->failTimes($user, 3);

        // State lives in the cache store with per-user keys; email case/whitespace normalized.
        $this->assertSame(3, (int) Cache::get(LoginThrottle::attemptsKey($user->email)));
        $this->assertTrue(Cache::has(LoginThrottle::lockKey($user->email)));
        $this->assertTrue(LoginThrottle::isLocked('  ' . strtoupper($user->email) . ' '));

        // Unrelated cache key is untouched by the reset (no global flush).
        Cache::put('unrelated', 'x', 60);
        LoginThrottle::clear($user->email);
        $this->assertFalse(Cache::has(LoginThrottle::attemptsKey($user->email)));
        $this->assertFalse(Cache::has(LoginThrottle::lockKey($user->email)));
        $this->assertSame('x', Cache::get('unrelated'));

        // Attempts while locked do not add to the counter.
        $this->failTimes($user, 3);
        $this->failTimes($user, 2);
        $this->assertSame(3, LoginThrottle::attempts($user->email));
    }
}
