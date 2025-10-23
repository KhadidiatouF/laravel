<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;

class Transaction extends Model
{
    use HasFactory, HasUuids;

    protected $fillable = [
        'compte_id',
        'type',
        'montant',
        'date_transaction',
    ];

   
    public function compte()
    {
        return $this->belongsTo(Compte::class);
    }
}
