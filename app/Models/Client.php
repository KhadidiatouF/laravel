<?php

namespace App\Models;
use Illuminate\Support\Str;

class Client extends User
{
    protected $table = 'users';


    public function comptes() {
        return $this->hasMany(Compte::class, 'titulaire'); 
    }

      protected static function booted()
    {
        static::creating(function ($model) {
            if (empty($model->id)) {
                $model->id = (string) Str::uuid();
            }
        });
    }


}
