<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreClientRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'nom' => ['required', 'string', 'max:100'],
            'prenom' => ['required', 'string', 'max:100'],
            'telephone' => ['required', 'regex:/^(77|78|76|70)[0-9]{7}$/', 'unique:clients,telephone'],
            'email' => ['nullable', 'email', 'unique:clients,email'],
            'adresse' => ['nullable', 'string', 'max:255'],
        ];
    }

    public function messages(): array
    {
        return [
            'nom.required' => 'Le nom est obligatoire.',
            'prenom.required' => 'Le prénom est obligatoire.',
            'telephone.required' => 'Le numéro de téléphone est obligatoire.',
            'telephone.regex' => 'Le numéro doit comporter 9 chiffres et commencer par 77, 78, 76 ou 70.',
            'telephone.unique' => 'Ce numéro de téléphone est déjà utilisé.',
            'email.email' => 'Le format de l’email est invalide.',
            'email.unique' => 'Cet email est déjà enregistré.',
        ];
    }
}
