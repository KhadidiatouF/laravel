<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreCompteRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'titulaire' => ['required', 'uuid', 'exists:clients,id'],
            'type' => ['required', 'in:courant,epargne,bloqué'],
            'statut' => ['nullable', 'in:actif,inactif,fermé'],
            'date_creation' => ['nullable', 'date'],
        ];
    }

    public function messages(): array
    {
        return [
            'titulaire.required' => 'Le titulaire du compte est obligatoire.',
            'titulaire.exists' => 'Le client sélectionné est invalide.',
            'type.in' => 'Le type doit être "courant", "epargne" ou "bloqué".',
        ];
    }
}
