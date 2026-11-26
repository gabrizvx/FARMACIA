<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class AccountSettingsTest extends TestCase
{
    use RefreshDatabase;

    public function test_account_pages_require_authentication(): void
    {
        $this->get(route('profile.edit'))->assertRedirect(route('login'));
        $this->get(route('password.edit'))->assertRedirect(route('login'));
        $this->get(route('preferences.edit'))->assertRedirect(route('login'));
    }

    public function test_profile_updates_only_the_authenticated_users_allowed_fields(): void
    {
        $user = User::factory()->create([
            'name' => 'Nombre anterior',
            'phone' => null,
        ]);
        $roleId = $user->role_id;
        $dni = $user->dni;

        $this->actingAs($user)
            ->patch(route('profile.update'), [
                'name' => 'Nombre actualizado',
                'email' => 'actualizado@example.test',
                'phone' => '912345678',
                'role_id' => 999,
                'dni' => '00000000',
                'tuition' => 'CAMBIO-NO-PERMITIDO',
            ])
            ->assertRedirect(route('profile.edit'));

        $this->assertDatabaseHas('users', [
            'id' => $user->id,
            'name' => 'Nombre actualizado',
            'email' => 'actualizado@example.test',
            'phone' => '912345678',
            'role_id' => $roleId,
            'dni' => $dni,
        ]);
    }

    public function test_profile_rejects_an_email_used_by_another_user(): void
    {
        $user = User::factory()->create();
        $otherUser = User::factory()->create();

        $this->actingAs($user)
            ->from(route('profile.edit'))
            ->patch(route('profile.update'), [
                'name' => $user->name,
                'email' => $otherUser->email,
                'phone' => $user->phone,
            ])
            ->assertRedirect(route('profile.edit'))
            ->assertSessionHasErrors('email');
    }

    public function test_profile_photo_can_be_uploaded_and_removed(): void
    {
        Storage::fake('public');
        $user = User::factory()->create();
        $profileData = [
            'name' => $user->name,
            'email' => $user->email,
            'phone' => $user->phone,
        ];

        $this->actingAs($user)
            ->patch(route('profile.update'), $profileData + [
                'profile_photo' => UploadedFile::fake()->image('profile.png'),
            ])
            ->assertRedirect(route('profile.edit'));

        $user->refresh();
        $photoPath = $user->profile_photo_path;
        $this->assertNotNull($photoPath);
        Storage::disk('public')->assertExists($photoPath);

        $this->patch(route('profile.update'), $profileData + [
            'remove_photo' => '1',
        ])->assertRedirect(route('profile.edit'));

        $this->assertNull($user->fresh()->profile_photo_path);
        Storage::disk('public')->assertMissing($photoPath);
    }

    public function test_profile_photo_rejects_non_images_and_files_over_two_megabytes(): void
    {
        Storage::fake('public');
        $user = User::factory()->create();
        $profileData = [
            'name' => $user->name,
            'email' => $user->email,
            'phone' => $user->phone,
        ];

        $this->actingAs($user)
            ->from(route('profile.edit'))
            ->patch(route('profile.update'), $profileData + [
                'profile_photo' => UploadedFile::fake()->create('script.php', 10, 'application/x-php'),
            ])
            ->assertRedirect(route('profile.edit'))
            ->assertSessionHasErrors('profile_photo');

        $this->patch(route('profile.update'), $profileData + [
            'profile_photo' => UploadedFile::fake()->image('large.png')->size(2049),
        ])->assertRedirect(route('profile.edit'))
            ->assertSessionHasErrors('profile_photo');
    }

    public function test_password_change_checks_current_password_and_hashes_new_password(): void
    {
        $user = User::factory()->create([
            'password' => Hash::make('current-password'),
        ]);
        $this->actingAs($user);

        $this->from(route('password.edit'))
            ->put(route('password.update'), [
                'current_password' => 'incorrect-password',
                'password' => 'new-password-123',
                'password_confirmation' => 'new-password-123',
            ])
            ->assertRedirect(route('password.edit'))
            ->assertSessionHasErrors('current_password');

        $this->put(route('password.update'), [
            'current_password' => 'current-password',
            'password' => 'new-password-123',
            'password_confirmation' => 'new-password-123',
        ])->assertRedirect(route('password.edit'));

        $newPasswordHash = $user->fresh()->password;
        $this->assertTrue(Hash::check('new-password-123', $newPasswordHash));
        $this->assertFalse(Hash::check('current-password', $newPasswordHash));
    }

    public function test_password_change_invalidates_another_session_with_the_old_password_hash(): void
    {
        $user = User::factory()->create([
            'password' => Hash::make('current-password'),
        ]);
        $oldPasswordHash = $user->password;

        $this->actingAs($user)
            ->withSession(['password_hash_web' => $oldPasswordHash])
            ->put(route('password.update'), [
                'current_password' => 'current-password',
                'password' => 'new-password-123',
                'password_confirmation' => 'new-password-123',
            ])
            ->assertRedirect(route('password.edit'));

        $this->withSession(['password_hash_web' => $oldPasswordHash])
            ->get(route('profile.edit'))
            ->assertRedirect(route('login'));
    }

    public function test_preferences_are_saved_per_user_and_can_be_restored(): void
    {
        $user = User::factory()->create();
        $otherUser = User::factory()->create();

        $this->actingAs($user)
            ->put(route('preferences.update'), [
                'theme' => 'dark',
                'accent_color' => 'blue',
                'density' => 'compact',
                'sidebar_collapsed' => '1',
            ])
            ->assertRedirect(route('preferences.edit'));

        $this->assertDatabaseHas('user_preferences', [
            'user_id' => $user->id,
            'theme' => 'dark',
            'accent_color' => 'blue',
            'density' => 'compact',
            'sidebar_collapsed' => true,
        ]);
        $this->assertDatabaseMissing('user_preferences', [
            'user_id' => $otherUser->id,
        ]);

        $this->post(route('preferences.reset'))
            ->assertRedirect(route('preferences.edit'));

        $this->assertDatabaseHas('user_preferences', [
            'user_id' => $user->id,
            'theme' => 'system',
            'accent_color' => 'brand',
            'density' => 'comfortable',
            'sidebar_collapsed' => false,
        ]);
    }

    public function test_invalid_preference_values_are_rejected(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->from(route('preferences.edit'))
            ->put(route('preferences.update'), [
                'theme' => 'untrusted',
                'accent_color' => 'url(javascript:alert(1))',
                'density' => 'large',
                'sidebar_collapsed' => '0',
            ])
            ->assertRedirect(route('preferences.edit'))
            ->assertSessionHasErrors(['theme', 'accent_color', 'density']);
    }
}
