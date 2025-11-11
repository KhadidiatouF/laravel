<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CompteResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        // Calculer le solde réel à partir des transactions
        $soldeCalcule = $this->calculerSoldeReel();

        return [
            'id' => $this->id,
            'numeroCompte' => $this->numCompte,
            'titulaire' => $this->client ? $this->client->nom . ' ' . $this->client->prenom : null,
            'solde' => $soldeCalcule,
            'devise' => 'FCFA',
            'dateCreation' => $this->date_creation?->toISOString(),
            'statut' => $this->statut,
            'metadata' => [
                'derniereModification' => $this->updated_at?->toISOString(),
                'version' => 1,
            ],
        ];
    }

    /**
     * Calculer le solde réel du compte à partir des transactions
     */
    private function calculerSoldeReel(): float
    {
        $result = $this->transactions()
            ->where('statut', 'validee')
            ->selectRaw('
                SUM(CASE
                    WHEN compte_id = ? THEN
                        CASE WHEN type IN (\'depot\') THEN montant ELSE -montant END
                    WHEN compte_destination_id = ? THEN
                        CASE WHEN type IN (\'transfert\', \'payement\') THEN montant ELSE 0 END
                    ELSE 0
                END) as solde
            ', [$this->id, $this->id])
            ->value('solde');

        return (float) ($result ?? 0);
    }
}