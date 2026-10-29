<?php

namespace Tests\Feature\UpgradeExtra;

use Illuminate\Support\Facades\DB;
use Tests\Feature\Upgrade\UpgradeTestCase;

class LegacyTokenTest extends UpgradeTestCase
{
    public function testPassport12AcceptsATokenStoredInThePassport10Schema(): void
    {
        $user = $this->createSuperUser();
        $legacyClientId = DB::table('oauth_clients')->insertGetId([
            'user_id' => null,
            'name' => 'Passport 10 legacy client',
            'secret' => 'legacy-client-secret',
            'redirect' => 'http://localhost',
            'personal_access_client' => true,
            'password_client' => false,
            'revoked' => false,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('oauth_personal_access_clients')->insert([
            'client_id' => $legacyClientId,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $result = $user->createToken('legacy-passport-10-token', ['*']);

        DB::table('oauth_access_tokens')
            ->where('id', $result->token->id)
            ->update([
                'name' => 'legacy-passport-10-token',
                'scopes' => '["*"]',
                'revoked' => false,
                'expires_at' => now()->addYear(),
            ]);

        $response = $this->getJson('/api/v1/users', [
            'Authorization' => 'Bearer '.$result->accessToken,
            'Accept' => 'application/json',
        ]);

        $response->assertOk();
        $this->assertDatabaseHas('oauth_access_tokens', [
            'id' => $result->token->id,
            'name' => 'legacy-passport-10-token',
            'revoked' => 0,
        ]);
    }
}
