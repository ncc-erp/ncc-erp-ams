<?php

namespace Tests\Feature\Upgrade;

use App\Models\Setting;
use Illuminate\Support\Facades\Validator;

/**
 * The password rules of the application have to keep rejecting weak and
 * common passwords and accepting strong ones.
 */
class PasswordRuleTest extends UpgradeTestCase
{
    /**
     * Turns on the "no common passwords" rule of the settings.
     */
    protected function enforceUncommonPasswords(): void
    {
        $settings = Setting::getSettings();
        $settings->pwd_secure_uncommon = 1;
        $settings->save();
    }

    protected function validatePassword(string $password, string $action = 'store'): bool
    {
        return Validator::make(
            ['password' => $password],
            ['password' => Setting::passwordComplexityRulesSaving($action)]
        )->fails();
    }

    public function testAPasswordThatIsTooShortIsRejected(): void
    {
        $this->assertTrue($this->validatePassword('Ab1!'));
    }

    public function testAStrongPasswordIsAccepted(): void
    {
        $this->assertFalse($this->validatePassword(self::STRONG_PASSWORD));
    }

    public function testACommonPasswordIsRejectedWhenUncommonPasswordsAreEnforced(): void
    {
        $this->enforceUncommonPasswords();

        $this->assertTrue($this->validatePassword('password'));
        $this->assertTrue($this->validatePassword('12345678'));
    }

    public function testAStrongPasswordIsStillAcceptedWhenUncommonPasswordsAreEnforced(): void
    {
        $this->enforceUncommonPasswords();

        $this->assertFalse($this->validatePassword(self::STRONG_PASSWORD));
    }

    public function testTheApiRejectsAWeakPassword(): void
    {
        $user = $this->createSuperUser();

        $username = $this->unique('gate.weak.password');

        $response = $this->postJson('/api/v1/users', [
            'first_name' => 'Gate',
            'last_name' => 'Regression',
            'username' => $username,
            'email' => 'gate.weak.password@example.test',
            'password' => 'Ab1!',
            'password_confirmation' => 'Ab1!',
        ], $this->apiHeaders($user));

        $response->assertStatus(422);
        $response->assertJsonValidationErrors('password');

        $this->assertDatabaseMissing('users', ['username' => $username]);
    }

    public function testTheApiAcceptsAStrongPassword(): void
    {
        $user = $this->createSuperUser();

        $username = $this->unique('gate.strong.password');

        $response = $this->postJson('/api/v1/users', [
            'first_name' => 'Gate',
            'last_name' => 'Regression',
            'username' => $username,
            'email' => 'gate.strong.password@example.test',
            'password' => self::STRONG_PASSWORD,
            'password_confirmation' => self::STRONG_PASSWORD,
        ], $this->apiHeaders($user));

        $response->assertStatus(200);

        $this->assertDatabaseHas('users', ['username' => $username]);
    }
}
