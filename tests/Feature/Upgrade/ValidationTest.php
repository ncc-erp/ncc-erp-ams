<?php

namespace Tests\Feature\Upgrade;

/**
 * The validation of the user and the supplier has to keep rejecting invalid
 * email addresses, especially the ones that try to smuggle a header into a
 * mail (CR/LF).
 */
class ValidationTest extends UpgradeTestCase
{
    public function testAnInvalidEmailIsRejectedForAUser(): void
    {
        $user = $this->createSuperUser();

        $username = $this->unique('gate.invalid.email');

        $response = $this->postJson('/api/v1/users', [
            'first_name' => 'Gate',
            'last_name' => 'Regression',
            'username' => $username,
            'email' => 'not-a-valid-email-address',
            'password' => self::STRONG_PASSWORD,
            'password_confirmation' => self::STRONG_PASSWORD,
        ], $this->apiHeaders($user));

        // the model validation of the application answers with a 400 and the
        // errors of the model in the "messages" key
        $response->assertStatus(400);
        $this->assertArrayHasKey('email', $response->json('messages'));

        $this->assertDatabaseMissing('users', ['username' => $username]);
    }

    public function testAnEmailWithCarriageReturnAndLineFeedIsRejectedForAUser(): void
    {
        $user = $this->createSuperUser();

        $username = $this->unique('gate.crlf.email');

        $response = $this->postJson('/api/v1/users', [
            'first_name' => 'Gate',
            'last_name' => 'Regression',
            'username' => $username,
            'email' => "attacker@example.test\r\nBcc: victim@example.test",
            'password' => self::STRONG_PASSWORD,
            'password_confirmation' => self::STRONG_PASSWORD,
        ], $this->apiHeaders($user));

        // the model validation of the application answers with a 400 and the
        // errors of the model in the "messages" key
        $response->assertStatus(400);
        $this->assertArrayHasKey('email', $response->json('messages'));

        $this->assertDatabaseMissing('users', ['username' => $username]);
    }

    public function testAnInvalidEmailIsRejectedForASupplier(): void
    {
        $user = $this->createSuperUser();

        $supplier = $this->unique('Gate Regression Supplier');

        $response = $this->postJson('/api/v1/suppliers', [
            'name' => $supplier,
            'email' => 'not-a-valid-email-address',
        ], $this->apiHeaders($user));

        $response->assertStatus(400);
        $this->assertArrayHasKey('email', $response->json('messages'));

        $this->assertDatabaseMissing('suppliers', ['name' => $supplier]);
    }

    public function testAnEmailWithCarriageReturnAndLineFeedIsRejectedForASupplier(): void
    {
        $user = $this->createSuperUser();

        $supplier = $this->unique('Gate Regression Supplier');

        $response = $this->postJson('/api/v1/suppliers', [
            'name' => $supplier,
            'email' => "attacker@example.test\r\nBcc: victim@example.test",
        ], $this->apiHeaders($user));

        $response->assertStatus(400);
        $this->assertArrayHasKey('email', $response->json('messages'));

        $this->assertDatabaseMissing('suppliers', ['name' => $supplier]);
    }

    public function testAValidEmailIsAcceptedForASupplier(): void
    {
        $user = $this->createSuperUser();

        $supplier = $this->unique('Gate Regression Supplier');

        $response = $this->postJson('/api/v1/suppliers', [
            'name' => $supplier,
            'email' => 'supplier@example.test',
        ], $this->apiHeaders($user));

        $response->assertStatus(200);

        $this->assertDatabaseHas('suppliers', ['email' => 'supplier@example.test']);
    }
}
