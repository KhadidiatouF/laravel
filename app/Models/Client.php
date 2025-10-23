<?php

namespace App\Models;

class Client extends User
{
    protected $table = 'users';

    protected static function booted()
    {
        static::addGlobalScope('client', function ($query) {
            $query->where('type', 'client');
        });
    }

    public function comptes() {
        return $this->hasMany(Compte::class, 'titulaire'); // la FK vers users.id
    }
}
