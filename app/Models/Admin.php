<?php

namespace App\Models;


class Admin extends User
{
    protected static function booted()
    {
        static::addGlobalScope('admin', function ($query) {
            $query->where('type', 'admin');
        });
    }
}
