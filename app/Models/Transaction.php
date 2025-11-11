<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;

/**
 * @OA\Schema(
 *     schema="Transaction",
 *     type="object",
 *     title="Transaction",
 *     description="Objet représentant une transaction bancaire",
 *     @OA\Property(property="id", type="string", format="uuid", example="550e8400-e29b-41d4-a716-446655440000"),
 *     @OA\Property(property="numeroTransaction", type="string", example="TXN-20251102-ABCD"),
 *     @OA\Property(property="type", type="string", enum={"depot", "retrait", "transfert", "virement"}, example="depot"),
 *     @OA\Property(property="montant", type="number", format="float", example=50000),
 *     @OA\Property(property="description", type="string", nullable=true, example="Dépôt d'espèces"),
 *     @OA\Property(property="statut", type="string", enum={"en_cours", "validee", "rejete", "annule"}, example="validee"),
 *     @OA\Property(property="dateTransaction", type="string", format="date-time", example="2025-11-02T10:30:00Z"),
 *     @OA\Property(property="compteSource", type="object",
 *         @OA\Property(property="id", type="string", format="uuid"),
 *         @OA\Property(property="numeroCompte", type="string")
 *     ),
 *     @OA\Property(property="compteDestination", type="object", nullable=true,
 *         @OA\Property(property="id", type="string", format="uuid"),
 *         @OA\Property(property="numeroCompte", type="string")
 *     ),
 *     @OA\Property(property="metadata", type="object",
 *         @OA\Property(property="derniereModification", type="string", format="date-time", example="2025-11-02T10:30:00Z"),
 *         @OA\Property(property="version", type="integer", example=1)
 *     )
 * )
 */
class Transaction extends Model
{
    use HasFactory, HasUuids;

    protected $fillable = [
        'compte_id',
        'compte_destination_id',
        'type',
        'montant',
        'description',
        'numero_transaction',
        'statut',
        'date_transaction',
    ];

    protected $casts = [
        'date_transaction' => 'datetime',
        'montant' => 'decimal:2',
    ];

    // Générer automatiquement le numéro de transaction
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($transaction) {
            if (empty($transaction->numero_transaction)) {
                $transaction->numero_transaction = 'TXN-' . now()->format('Ymd') . '-' . strtoupper(substr(md5(uniqid()), 0, 4));
            }
        });
    }

    public function compte()
    {
        return $this->belongsTo(Compte::class, 'compte_id');
    }

    public function compteDestination()
    {
        return $this->belongsTo(Compte::class, 'compte_destination_id');
    }

    // Scopes pour filtrer les transactions
    public function scopeValidees($query)
    {
        return $query->where('statut', 'validee');
    }

    public function scopeParType($query, $type)
    {
        return $query->where('type', $type);
    }

    public function scopeParPeriode($query, $debut, $fin)
    {
        return $query->whereBetween('date_transaction', [$debut, $fin]);
    }

    // Helper methods
    public function isDebit()
    {
        return in_array($this->type, ['retrait', 'transfert', 'virement']);
    }

    public function isCredit()
    {
        return $this->type === 'depot';
    }

    public function getMontantFormateAttribute()
    {
        return number_format($this->montant, 2, ',', ' ') . ' FCFA';
    }
}
