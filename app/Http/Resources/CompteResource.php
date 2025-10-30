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
            'motifBlocage' => $this->statut === 'bloqué' ? 'Inactivité de 30+ jours' : null,
            'informationsBlocage' => $this->statut === 'bloqué' ? [
                'dateDebutBlocage' => $this->date_debut_bloquage?->toISOString(),
                'dateFinBlocage' => $this->date_fin_bloquage?->toISOString(),
                'dureeBlocageJours' => $this->duree_bloquage_jours,
            ] : null,
            'metadata' => [
                'derniereModification' => $this->updated_at?->toISOString(),
                'version' => 1,
            ],
        ];
    }
}