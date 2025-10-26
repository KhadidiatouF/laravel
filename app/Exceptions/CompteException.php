<?php

namespace App\Exceptions;

use Exception;

class CompteException extends Exception
{
    protected $errors;
    protected $statusCode;

    public function __construct(string $message = 'Erreur liée au compte', int $statusCode = 400, array $errors = [])
    {
        parent::__construct($message);
        $this->statusCode = $statusCode;
        $this->errors = $errors;
    }

    public function getStatusCode(): int
    {
        return $this->statusCode;
    }

    public function getErrors(): array
    {
        return $this->errors;
    }

    public static function compteNotFound(string $numero = null): self
    {
        $message = $numero ? "Compte avec numéro {$numero} non trouvé" : "Compte non trouvé";
        return new self($message, 404);
    }

    public static function accessDenied(string $reason = 'Accès refusé'): self
    {
        return new self($reason, 403);
    }

    public static function invalidFilter(string $filter): self
    {
        return new self("Filtre invalide: {$filter}", 400, ['filter' => $filter]);
    }

    public static function archivedAccountAccessDenied(): self
    {
        return new self('Accès aux comptes archivés réservé aux administrateurs', 403);
    }
}