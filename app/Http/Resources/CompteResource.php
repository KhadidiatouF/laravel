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
        return [
            'id' => $this->id,
            'numeroCompte' => $this->numCompte,
            'titulaire' => $this->client ? $this->client->nom . ' ' . $this->client->prenom : null,
            'solde' => $this->solde, // Utilise automatiquement l'accesseur getSoldeAttribute()
            'devise' => 'FCFA',
            'dateCreation' => $this->date_creation?->toISOString(),
            'statut' => $this->statut,
            'metadata' => [
                'derniereModification' => $this->updated_at?->toISOString(),
                'version' => 1,
            ],
        ];
    }

}