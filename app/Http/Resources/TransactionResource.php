<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class TransactionResource extends JsonResource
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
            'numeroTransaction' => $this->numero_transaction,
            'type' => $this->type,
            'montant' => (float) $this->montant,
            'description' => $this->description,
            'statut' => $this->statut,
            'dateTransaction' => $this->date_transaction?->toISOString(),
            'compteSource' => $this->whenLoaded('compte', function () {
                return [
                    'id' => $this->compte->id,
                    'numeroCompte' => $this->compte->numCompte,
                    'telephone' => $this->whenLoaded('compte.client', function () {
                        return $this->compte->client->telephone;
                    }),
                ];
            }),
            'compteDestination' => $this->when(
                $this->compte_destination_id && $this->relationLoaded('compteDestination'),
                function () {
                    return [
                        'id' => $this->compteDestination->id,
                        'numeroCompte' => $this->compteDestination->numCompte,
                        'telephone' => $this->whenLoaded('compteDestination.client', function () {
                            return $this->compteDestination->client->telephone;
                        }),
                    ];
                }
            ),
            'metadata' => [
                'derniereModification' => $this->updated_at?->toISOString(),
                'version' => 1,
            ],
        ];
    }
}