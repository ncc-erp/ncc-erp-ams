<?php

namespace Tests\Feature\Upgrade;

use Livewire\Livewire;

/**
 * The login page is rendered with a Livewire component, it has to keep working
 * after the upgrade.
 */
class LivewireLoginTest extends UpgradeTestCase
{
    public function testTheLoginPageIsRendered(): void
    {
        $response = $this->get('/login');

        $response->assertStatus(200);
        $response->assertSee('name="username"', false);
        $response->assertSee('name="password"', false);
    }

    public function testTheLivewireLoginComponentHasItsView(): void
    {
        $this->assertFileExists(resource_path('views/livewire/login-form.blade.php'));
    }

    public function testTheLivewireLoginComponentCanBeRendered(): void
    {
        Livewire::test('login-form')
            ->assertSet('username', '')
            ->assertSet('password', '');
    }

    public function testTheLivewireLoginComponentValidatesTheInput(): void
    {
        Livewire::test('login-form')
            ->set('username', '')
            ->set('password', '')
            ->call('submitForm')
            ->assertHasErrors(['username', 'password']);
    }
}
