<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class AdminAuthTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_is_redirected_to_login(): void
    {
        $this->get('/admin/vehicles')->assertRedirect(route('admin.login'));
    }

    public function test_user_can_login_with_email_and_password(): void
    {
        $user = User::factory()->create(['password' => 'secret123']);

        $this->post(route('admin.login.store'), ['email' => $user->email, 'password' => 'secret123'])
            ->assertRedirect(route('admin.dashboard'));

        $this->assertAuthenticatedAs($user);
    }

    public function test_wrong_password_is_rejected(): void
    {
        $user = User::factory()->create(['password' => 'secret123']);

        $this->post(route('admin.login.store'), ['email' => $user->email, 'password' => 'mauvais'])
            ->assertSessionHasErrors('email');

        $this->assertGuest();
    }

    public function test_inactive_user_cannot_login(): void
    {
        $user = User::factory()->create(['password' => 'secret123', 'is_active' => false]);

        $this->post(route('admin.login.store'), ['email' => $user->email, 'password' => 'secret123'])
            ->assertSessionHasErrors('email');

        $this->assertGuest();
    }

    public function test_login_is_throttled_after_five_failures(): void
    {
        $user = User::factory()->create(['password' => 'secret123']);

        for ($i = 0; $i < 5; $i++) {
            $this->post(route('admin.login.store'), ['email' => $user->email, 'password' => 'mauvais']);
        }

        $this->post(route('admin.login.store'), ['email' => $user->email, 'password' => 'secret123'])
            ->assertSessionHasErrors('email');

        $this->assertGuest();
    }

    public function test_user_can_logout(): void
    {
        $this->actingAs(User::factory()->create())
            ->post(route('admin.logout'))
            ->assertRedirect(route('home'));

        $this->assertGuest();
    }

    public function test_admin_can_create_user(): void
    {
        $this->actingAs(User::factory()->create())
            ->post(route('admin.users.store'), [
                'name' => 'Jean',
                'email' => 'jean@example.com',
                'password' => 'motdepasse',
                'password_confirmation' => 'motdepasse',
                'is_active' => '1',
            ])
            ->assertRedirect(route('admin.users.index'));

        $user = User::where('email', 'jean@example.com')->firstOrFail();
        $this->assertTrue(Hash::check('motdepasse', $user->password));
        $this->assertTrue($user->is_active);
    }

    public function test_update_without_password_keeps_current_one(): void
    {
        $user = User::factory()->create(['password' => 'ancienmdp']);

        $this->actingAs(User::factory()->create())
            ->put(route('admin.users.update', $user), [
                'name' => 'Nouveau nom',
                'email' => $user->email,
                'password' => '',
                'password_confirmation' => '',
                'is_active' => '1',
            ])
            ->assertRedirect(route('admin.users.index'));

        $user->refresh();
        $this->assertSame('Nouveau nom', $user->name);
        $this->assertTrue(Hash::check('ancienmdp', $user->password));
    }

    public function test_admin_cannot_delete_or_deactivate_self(): void
    {
        $me = User::factory()->create();

        $this->actingAs($me)->delete(route('admin.users.destroy', $me))->assertSessionHasErrors('user');
        $this->actingAs($me)->put(route('admin.users.update', $me), [
            'name' => $me->name,
            'email' => $me->email,
        ])->assertSessionHasErrors('is_active');

        $this->assertTrue($me->fresh()->is_active);
    }

    public function test_admin_can_delete_other_user(): void
    {
        $other = User::factory()->create();

        $this->actingAs(User::factory()->create())
            ->delete(route('admin.users.destroy', $other))
            ->assertRedirect(route('admin.users.index'));

        $this->assertModelMissing($other);
    }

    public function test_deactivated_user_is_logged_out(): void
    {
        $user = User::factory()->create(['is_active' => false]);

        $this->actingAs($user)->get('/admin/vehicles')->assertRedirect(route('admin.login'));
        $this->assertGuest();
    }
}
