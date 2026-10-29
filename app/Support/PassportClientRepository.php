<?php

namespace App\Support;

use Laravel\Passport\Client;

class PassportClientRepository extends \Laravel\Passport\ClientRepository
{
    public function personalAccessClient(?string $provider = null): Client
    {
        return parent::personalAccessClient($provider ?? config('auth.guards.api.provider'));
    }

    public function createPersonalAccessClient($userId, string $name, string $redirect): Client
    {
        return $this->createPersonalAccessGrantClient($name);
    }
}
