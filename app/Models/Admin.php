<?php

namespace App\Models;
use Illuminate\Support\Str;


class Admin extends User
{
    // Retirer le scope global qui cause le problème avec Passport
    // protected static function booted()
    // {
    //     static::addGlobalScope('admin', function ($query) {
    //         $query->where('type', 'admin');
    //     });
    // }

    // Méthode alternative pour filtrer les admins
    public function scopeAdmins($query)
    {
        return $query->where('type', 'admin');
    }

     protected $fillable = ['id', 'user_id'];


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
