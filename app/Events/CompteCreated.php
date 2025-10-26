<?php

namespace App\Events;

use App\Models\Compte;
use App\Models\Client;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class CompteCreated
{
    use Dispatchable, SerializesModels;

    public Compte $compte;
    public Client $client;
    public ?string $password;
    public ?string $code;

    /**
     * Create a new event instance.
     */
    public function __construct(Compte $compte, Client $client, ?string $password = null, ?string $code = null)
    {
        $this->compte = $compte;
        $this->client = $client;
        $this->password = $password;
        $this->code = $code;
    }
}