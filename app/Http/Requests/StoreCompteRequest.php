<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use App\Rules\SenegalesePhoneRule;
use App\Rules\SenegaleseNCIRule;

class StoreCompteRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'type' => ['required', 'in:cheque,courant,epargne,bloqué'],
            'soldeInitial' => ['required', 'numeric', 'min:10000'],
            'devise' => ['required', 'in:FCFA,XOF,EUR,USD'],
            'solde' => ['nullable', 'numeric'],
            'client' => ['required', 'array'],
            'client.id' => ['nullable', 'uuid', 'exists:users,id'],
            'client.titulaire' => ['required_without:client.id', 'string', 'max:255'],
            'client.email' => ['required', 'email', 'unique:users,email'],
            'client.telephone' => ['required', 'string', 'unique:users,telephone', new SenegalesePhoneRule()],
            'client.adresse' => ['required', 'string', 'max:255'],
            'client.nci' => ['nullable', 'string', new SenegaleseNCIRule()],
        ];
    }

    public function messages(): array
    {
        return [
            'type.required' => 'Le type de compte est obligatoire.',
            'type.in' => 'Le type doit être "cheque", "courant", "epargne" ou "bloqué".',
            'soldeInitial.required' => 'Le solde initial est obligatoire.',
            'soldeInitial.numeric' => 'Le solde initial doit être un nombre.',
            'soldeInitial.min' => 'Le solde initial doit être d\'au moins 10 000 FCFA.',
            'devise.required' => 'La devise est obligatoire.',
            'devise.in' => 'La devise doit être FCFA, XOF, EUR ou USD.',
            'client.required' => 'Les informations du client sont obligatoires.',
            'client.titulaire.required_without' => 'Le nom du titulaire est requis si l\'ID client n\'est pas fourni.',
            'client.email.required' => 'L\'email est obligatoire.',
            'client.email.email' => 'L\'email doit être valide.',
            'client.email.unique' => 'Cet email est déjà utilisé.',
            'client.telephone.required' => 'Le numéro de téléphone est obligatoire.',
            'client.telephone.unique' => 'Ce numéro de téléphone est déjà utilisé.',
            'client.telephone.regex' => 'Le numéro de téléphone doit être un numéro sénégalais valide.',
            'client.adresse.required' => 'L\'adresse est obligatoire.',
            'client.nci.regex' => 'Le NCI doit être au format valide (13 chiffres suivis d\'une lettre majuscule).',
        ];
    }
}
