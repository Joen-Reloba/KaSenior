<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class SuperadminSettingsTest extends TestCase
{
    use RefreshDatabase;

    public function test_superadmin_can_rotate_the_pin_from_settings(): void
    {
        $oldHash = Hash::make('012345678');
        config(['kasenior.superadmin_pin_hash' => $oldHash]);
        $change = [
            'current_password' => '012345678',
            'password' => '987654321',
            'password_confirmation' => '987654321',
        ];

        $this->get(route('admins.settings'))->assertRedirect(route('admins.pin'));
        $this->put(route('admins.settings.pin'), $change)->assertForbidden();
        $this->post(route('admins.unlock'), ['password' => '012345678'])->assertRedirect(route('admins.index'));
        $this->get(route('admins.index'))->assertOk()->assertSee(route('admins.settings'));
        $this->get(route('admins.settings'))->assertOk()->assertSee('Change PIN');

        $this->put(route('admins.settings.pin'), array_replace($change, ['current_password' => '012345679']))
            ->assertSessionHasErrors('current_password');
        $this->put(route('admins.settings.pin'), $change)->assertRedirect(route('admins.settings'));
        $this->get(route('admins.settings'))->assertOk();

        $this->withSession(['superadmin_unlock' => hash('sha256', $oldHash)])
            ->get(route('admins.settings'))->assertRedirect(route('admins.pin'));
        $this->post(route('admins.unlock'), ['password' => '012345678'])->assertSessionHasErrors('pin');
        $this->post(route('admins.unlock'), ['password' => '987654321'])->assertRedirect(route('admins.index'));
    }
}
