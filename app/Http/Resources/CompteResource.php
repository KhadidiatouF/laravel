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
            'type' => $this->type,
            'solde' => $this->solde,
            'devise' => 'FCFA',
            'dateCreation' => $this->date_creation?->toISOString(),
            'statut' => $this->statut,
            'motifBlocage' => $this->statut === 'bloque' ? 'Inactivité de 30+ jours' : null,
            'metadata' => [
                'derniereModification' => $this->updated_at?->toISOString(),
                'version' => 1,
            ],
        ];
    }
}