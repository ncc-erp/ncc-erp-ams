<?php

namespace Tests\Feature\Upgrade;

use App\Models\User;
use Illuminate\Support\Facades\Hash;

/**
 * The session based login of the web interface has to keep working.
 */
class SessionLoginTest extends UpgradeTestCase
{
    protected function createLoginUser(?string $username = null): User
    {
        $username = $username ?: $this->unique('gate.login.user');

        return User::factory()->create([
            'username' => $username,
            'email' => $username.'@example.test',
            'password' => Hash::make(self::STRONG_PASSWORD),
            'permissions' => '{"superuser":"1"}',
            'activated' => 1,
            'company_id' => $this->makeCompany()->id,
            'location_id' => $this->makeLocation()->id,
        ]);
    }

    public function testTheLoginPageIsRendered(): void
    {
        $response = $this->get('/login');

        $response->assertStatus(200);
        $response->assertSee('name="username"', false);
        $response->assertSee('name="password"', false);
    }

    public function testALoginWithTheCorrectPasswordAuthenticatesTheUser(): void
    {
        $user = $this->createLoginUser();

        $response = $this->post('/login', [
            'username' => $user->username,
            'password' => self::STRONG_PASSWORD,
        ]);

        $response->assertRedirect();
        $this->assertAuthenticatedAs($user);
    }

    public function testALoginWithAWrongPasswordIsRejected(): void
    {
        $user = $this->createLoginUser();

        $response = $this->post('/login', [
            'username' => $user->username,
            'password' => 'this-is-not-the-password',
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('error');
        $this->assertGuest();
    }

    public function testALoginWithAnUnknownUserIsRejected(): void
    {
        $response = $this->post('/login', [
            'username' => 'nobody.at.all',
            'password' => self::STRONG_PASSWORD,
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('error');
        $this->assertGuest();
    }

    public function testALoginWithoutAPasswordIsRejected(): void
    {
        $user = $this->createLoginUser();

        $response = $this->post('/login', [
            'username' => $user->username,
        ]);

        $response->assertRedirect();
        $response->assertSessionHasErrors('password');
        $this->assertGuest();
    }
}
