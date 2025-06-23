<?php

namespace App\Models\Recipients;

use Illuminate\Notifications\Notifiable;

abstract class Recipient
{
    use Notifiable;

    protected $email;

    public function getKey()
    {
        return $this->email;
    }
}
