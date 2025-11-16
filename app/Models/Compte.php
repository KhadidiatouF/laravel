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
       
    ];

    protected $casts = [
        'date_creation' => 'date',
     
    ];

    public function client()
    {
        return $this->belongsTo(User::class, 'titulaire');
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

    public function scopeNumero(Builder $query, string $numero): Builder
    {
        return $query->where('numCompte', $numero);
    }

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
        $debits = $this->transactions()
            ->whereIn('type', ['retrait', 'transfert', 'payement'])
            ->sum('montant');

        $credits = $this->transactions()
            ->where('type', 'depot')
            ->sum('montant');

        // Ajouter les crédits provenant de comptes destination (transferts reçus)
        $creditsFromDestination = \App\Models\Transaction::where('compte_destination_id', $this->id)
            ->whereIn('type', ['transfert', 'payement'])
            ->sum('montant');

        return ($credits + $creditsFromDestination) - $debits;
    }

    /**
     * Mutateur pour définir le solde (non utilisé directement, calculé automatiquement)
     */
    public function setSoldeAttribute($value): void
    {
        
        throw new \InvalidArgumentException('Le solde ne peut pas être défini directement. Utilisez les transactions.');
    }

    public function isArchived(): bool
    {
        return $this->statut === 'fermé';
    }

    public static function getArchivedFromCloud(int $perPage = 10)
    {
      
        return static::onlyTrashed()
            ->with('client')
            ->paginate($perPage);
    }
}
