<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ClientResource extends JsonResource
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
            'nom' => $this->nom,
            'prenom' => $this->prenom,
            'email' => $this->email,
            'telephone' => $this->telephone,
            'adresse' => $this->adresse,
            'type' => $this->type,
            'dateCreation' => $this->created_at?->toISOString(),
            'comptes' => $this->whenLoaded('comptes', function () {
                return CompteResource::collection($this->comptes);
            }),
            'metadata' => [
                'derniereModification' => $this->updated_at?->toISOString(),
                'version' => 1,
            ],
        ];
    }
}