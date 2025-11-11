<?php

namespace App\Models;
use Illuminate\Support\Str;

class Client extends User
{
    protected $table = 'users';

    // protected static function booted()
    // {
    //     static::addGlobalScope('client', function ($query) {
    //         $query->where('type', 'client');
    //     });
    // }

    public function comptes() {
        return $this->hasMany(Compte::class, 'titulaire'); // la FK vers users.id
    }

      protected static function booted()
    {
        static::creating(function ($model) {
            if (empty($model->id)) {
                $model->id = (string) Str::uuid();
            }
        });
    }

    public function user(){
        return $this->belongsTo(User::class);
    }

}
