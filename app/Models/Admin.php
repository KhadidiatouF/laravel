<?php

namespace App\Models;
use Illuminate\Support\Str;


class Admin extends User
{
  
    public function scopeAdmins($query)
    {
        return $query->where('type', 'admin');
    }

     protected $fillable = [ 'user_id'];


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
