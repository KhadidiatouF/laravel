<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreTransactionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $rules = [
            'telephone' => 'required|string',
            'type' => 'required|in:depot,retrait,transfert,payement',
            'montant' => 'required|numeric|min:100',
            'description' => 'nullable|string|max:255',
        ];

        // Validation conditionnelle selon le type de transaction
        if (in_array($this->input('type'), ['transfert', 'payement'])) {
            if ($this->input('type') === 'transfert') {
                $rules['numero_destinataire'] = 'required|string';
            } elseif ($this->input('type') === 'payement') {
                $rules['numero_destinataire'] = 'nullable|string';
                $rules['code_marchand'] = 'nullable|string';
            }
        }

        return $rules;
    }

    public function withValidator($validator)
    {
        $validator->after(function ($validator) {
            if ($this->input('type') === 'payement') {
                if (empty($this->input('numero_destinataire')) && empty($this->input('code_marchand'))) {
                    $validator->errors()->add('numero_destinataire', 'Le numéro du destinataire ou le code marchand est obligatoire pour les paiements.');
                    $validator->errors()->add('code_marchand', 'Le numéro du destinataire ou le code marchand est obligatoire pour les paiements.');
                }
            }
        });
    }

    public function messages(): array
    {
        return [
            'telephone.required' => 'Le numéro de téléphone est obligatoire.',
            'telephone.string' => 'Le numéro de téléphone doit être une chaîne de caractères.',
            'type.required' => 'Le type de transaction est obligatoire.',
            'type.in' => 'Le type de transaction doit être depot, retrait, transfert ou payement.',
            'montant.required' => 'Le montant est obligatoire.',
            'montant.numeric' => 'Le montant doit être un nombre.',
            'montant.min' => 'Le montant minimum est de 100 FCFA.',
            'description.string' => 'La description doit être une chaîne de caractères.',
            'description.max' => 'La description ne peut pas dépasser 255 caractères.',
            'numero_destinataire.required_if' => 'Le numéro du destinataire est obligatoire pour les transferts.',
            'numero_destinataire.required_without' => 'Le numéro du destinataire est obligatoire si aucun code marchand n\'est fourni.',
            'numero_destinataire.string' => 'Le numéro du destinataire doit être une chaîne de caractères.',
            'code_marchand.required_if' => 'Le code marchand est obligatoire pour les paiements.',
            'code_marchand.required_without' => 'Le code marchand est obligatoire si aucun numéro de destinataire n\'est fourni.',
            'code_marchand.string' => 'Le code marchand doit être une chaîne de caractères.',
        ];
    }
}