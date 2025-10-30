<?php

namespace App\Models;


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
}
