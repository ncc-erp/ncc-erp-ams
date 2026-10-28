<?php

namespace Tests\Feature\Upgrade;

/**
 * The Passport based API has to keep working: personal access tokens, the
 * read endpoints of the main resources and the rejection of invalid tokens.
 */
class PassportApiTest extends UpgradeTestCase
{
    public function testAPersonalAccessTokenCanBeCreated(): void
    {
        $user = $this->createSuperUser();

        $token = $this->apiTokenFor($user);

        $this->assertNotEmpty($token);
        $this->assertDatabaseCount('oauth_access_tokens', 1);
    }

    /**
     * @dataProvider readOnlyEndpoints
     */
    public function testTheReadEndpointsAreReachableWithAToken(string $endpoint): void
    {
        $user = $this->createSuperUser();

        $response = $this->getJson('/api/v1/'.$endpoint, $this->apiHeaders($user));

        $response->assertStatus(200);
        $this->assertArrayHasKey('rows', $response->json());
    }

    /**
     * @return array<string, array<int, string>>
     */
    public function readOnlyEndpoints(): array
    {
        return [
            'hardware' => ['hardware'],
            'users' => ['users'],
            'locations' => ['locations'],
            'categories' => ['categories'],
            'models' => ['models'],
        ];
    }

    public function testAnInvalidTokenIsRejectedWith401(): void
    {
        $response = $this->getJson('/api/v1/hardware', [
            'Authorization' => 'Bearer this-is-not-a-valid-token',
            'Accept' => 'application/json',
        ]);

        $response->assertStatus(401);
    }

    public function testAMissingTokenIsRejectedWith401(): void
    {
        $response = $this->getJson('/api/v1/hardware', ['Accept' => 'application/json']);

        $response->assertStatus(401);
    }
}
