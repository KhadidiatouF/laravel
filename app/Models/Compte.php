<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Str;

class Compte extends Model
{
    use HasFactory, HasUuids;

    protected $fillable = [
        'numCompte',
        'titulaire',
        'date_creation',
        'statut',
        'date_debut_bloquage',
        'date_fin_bloquage',
    ];

    protected $casts = [
        'date_creation' => 'date',
        'date_debut_bloquage' => 'datetime',
        'date_fin_bloquage' => 'datetime',
    ];

    public function client()
    {
        return $this->belongsTo(Client::class, 'titulaire');
    }

    public function transactions()
    {
        return $this->hasMany(Transaction::class);
    }

    // Scope global pour les comptes non supprimés
    protected static function booted()
    {
        static::addGlobalScope('nonSupprime', function (Builder $builder) {
            $builder->where('statut', '!=', 'fermé');
        });

        static::creating(function ($compte) {
            if (empty($compte->numCompte)) {
                $compte->numCompte = self::generateAccountNumber();
            }
        });
    }

    // Scope local pour récupérer un compte par numéro
    public function scopeNumero(Builder $query, string $numero): Builder
    {
        return $query->where('numCompte', $numero);
    }

    // Scope local pour récupérer les comptes d'un client basé sur le téléphone
    public function scopeClient(Builder $query, string $telephone): Builder
    {
        return $query->whereHas('client', function (Builder $q) use ($telephone) {
            $q->where('telephone', $telephone);
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

    // Méthode pour vérifier si le compte est archivé
    public function isArchived(): bool
    {
        return $this->statut === 'fermé';
    }

    // Méthode pour récupérer les comptes archivés depuis le cloud
    public static function getArchivedFromCloud(int $perPage = 10)
    {
        // Simulation d'appel à un service cloud
        // En production, cela ferait un appel HTTP vers un service externe
        return static::onlyTrashed()
            ->with('client')
            ->paginate($perPage);
    }
}
