<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Support\Str;

class Compte extends Model
{
    use HasFactory, HasUuids;

    protected $fillable = [
        'numCompte',
        'titulaire',
        'type',
        'date_creation',
        'statut',
    ];

   
    public function client()
    {
        return $this->belongsTo(Client::class, 'titulaire');
    }

    public function transactions()
    {
        return $this->hasMany(Transaction::class);
    }

 
    protected static function booted()
    {
        static::creating(function ($compte) {
            if (empty($compte->numCompte)) {
                $compte->numCompte = self::generateAccountNumber();
            }
        });
    }

    private static function generateAccountNumber(): string
    {
        $prefix = 'C-';
        $date = now()->format('Ymd');
        $random = strtoupper(Str::random(4));

        return $prefix . $date . '-' . $random;
    }

 
    public function getSoldeAttribute(): float
    {
        $depot = $this->transactions()->where('type', 'depot')->sum('montant');
        $retrait = $this->transactions()->where('type', 'retrait')->sum('montant');
        return $depot - $retrait;
    }
}
