<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Marchand extends Model
{
    use HasFactory;

    protected $fillable = [
        'telephone',
        'code_marchand',
    ];

    public function user(){
        return $this->belongsTo(User::class);
    }

}
