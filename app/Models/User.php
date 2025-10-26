<?php



namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable, HasUuids;

 
    protected $fillable = [
        'nom',
        'prenom',
        'email',
        'telephone',
        'adresse',
        'password',
        'type',
        'code_verification'
    ];

  
    protected $hidden = [
        'password',
        'remember_token',
    ];

  
    protected $casts = [
        'email_verified_at' => 'datetime',
        'password' => 'hashed',
    ];

     public function isAdmin(): bool {
        return $this->type === 'admin';
    }

    public function isClient(): bool {
        return $this->type === 'client';
    }

    public function comptes() {
        return $this->hasMany(Compte::class, 'titulaire'); // ← Corrigé de 'client_id' à 'titulaire'
    }

    
}
