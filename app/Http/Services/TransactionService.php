<?php

namespace App\Http\Services;

use App\Http\Interfaces\RepositoriesInterfaces\TransactionRepositoryInterface;
use App\Http\Interfaces\RepositoriesInterfaces\CompteRepositoryInterface;
use App\Models\Transaction;
use App\Models\Compte;
use Illuminate\Support\Facades\DB;
use Illuminate\Database\Eloquent\Collection;
use App\Events\TransactionEffectuee;

class TransactionService
{
    protected $transactionRepository;
    protected $compteRepository;

    public function __construct(
        TransactionRepositoryInterface $transactionRepository,
        CompteRepositoryInterface $compteRepository
    ) {
        $this->transactionRepository = $transactionRepository;
        $this->compteRepository = $compteRepository;
    }

    // Méthode pour obtenir le repository directement (pour déboguer)
    public function getTransactionRepository()
    {
        return $this->transactionRepository;
    }

    /**
     * Créer une nouvelle transaction
     */
    public function createTransaction(array $data, $user): Transaction
    {
        // Validation des données métier
        $this->validateTransactionData($data, $user);

        // Vérification du solde si nécessaire
        if (in_array($data['type'], ['retrait', 'transfert', 'payement'])) {
            $this->checkSufficientBalance($data['compte_id'], $data['montant']);
        }

        DB::beginTransaction();
        try {
            // Créer la transaction principale
            $transaction = $this->transactionRepository->create([
                'compte_id' => $data['compte_id'],
                'compte_destination_id' => $data['compte_destination_id'] ?? null,
                'type' => $data['type'],
                'montant' => $data['montant'],
                'description' => $data['description'] ?? null,
                'statut' => 'validee', // Auto-validation
                'date_transaction' => now(),
            ]);

            // Créer la transaction miroir pour les transferts
            if (in_array($data['type'], ['transfert', 'payement'])) {
                $this->createMirrorTransaction($transaction, $data);
            }

            DB::commit();

            // Déclencher l'événement
            event(new TransactionEffectuee($transaction));

            return $transaction->load(['compte.client', 'compteDestination.client']);

        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    /**
     * Lister les transactions avec filtres
     */
    public function getTransactions(array $filters = [], $user = null)
    {
        // Ajouter le filtre utilisateur si c'est un client
        if ($user && $user->type === 'client') {
            $filters['user_id'] = $user->id;
        }

        return $this->transactionRepository->getAll($filters);
    }

    /**
     * Obtenir une transaction par ID
     */
    public function getTransactionById(string $id, $user = null): ?Transaction
    {
        $transaction = $this->transactionRepository->findById($id);

        if (!$transaction) {
            return null;
        }

        // Vérifier les permissions pour les clients
        if ($user && $user->type === 'client') {
            $this->checkTransactionAccess($transaction, $user);
        }

        return $transaction->load(['compte.client', 'compteDestination.client']);
    }

    /**
     * Mettre à jour le statut d'une transaction
     */
    public function updateTransactionStatus(Transaction $transaction, string $statut): Transaction
    {
        return $this->transactionRepository->update($transaction, ['statut' => $statut]);
    }

    /**
     * Supprimer une transaction
     */
    public function deleteTransaction(Transaction $transaction): bool
    {
        // Supprimer aussi la transaction miroir si c'est un transfert ou paiement
        if (in_array($transaction->type, ['transfert', 'payement'])) {
            $mirrorTransaction = Transaction::where('numero_transaction', $transaction->numero_transaction . '-DEST')->first();
            if ($mirrorTransaction) {
                $this->transactionRepository->delete($mirrorTransaction);
            }
        }

        return $this->transactionRepository->delete($transaction);
    }

    /**
     * Obtenir les statistiques des transactions
     */
    public function getTransactionStatistics(): array
    {
        return $this->transactionRepository->getStatistics();
    }

    /**
     * Obtenir les transactions d'un compte
     */
    public function getTransactionsByCompte(string $compteId, array $filters = []): Collection
    {
        return $this->transactionRepository->getTransactionsByCompte($compteId, $filters);
    }

    /**
     * Calculer le solde d'un compte
     */
    public function getCompteBalance(string $compteId): float
    {
        return $this->transactionRepository->getBalanceByCompte($compteId);
    }

    /**
     * Statistiques d'un compte
     */
    public function getCompteStatistics(string $compteId): array
    {
        $transactions = $this->getTransactionsByCompte($compteId);

        $totalDepots = $transactions->where('compte_id', $compteId)->where('type', 'depot')->sum('montant') +
                      $transactions->where('compte_destination_id', $compteId)->whereIn('type', ['transfert', 'virement'])->sum('montant');

        $totalRetraits = $transactions->where('compte_id', $compteId)->whereIn('type', ['retrait', 'transfert', 'payement'])->sum('montant');

        $soldeActuel = $totalDepots - $totalRetraits;

        return [
            'totalDepots' => $totalDepots,
            'totalRetraits' => $totalRetraits,
            'soldeActuel' => $soldeActuel,
            'nombreTransactions' => $transactions->count(),
            'derniereTransaction' => $transactions->sortByDesc('date_transaction')->first(),
        ];
    }

    /**
     * Dashboard client
     */
    public function getClientDashboard($user): array
    {
        $comptes = $this->compteRepository->getAll(['titulaire' => $user->id], 'created_at', 'desc', 100);

        $balanceGlobale = 0;
        $totalTransactions = 0;

        foreach ($comptes as $compte) {
            $transactions = $this->getTransactionsByCompte($compte->id);
            $debits = $transactions->where('compte_id', $compte->id)->whereIn('type', ['retrait', 'transfert', 'payement'])->sum('montant');
            $credits = $transactions->where('compte_id', $compte->id)->where('type', 'depot')->sum('montant') +
                      $transactions->where('compte_destination_id', $compte->id)->whereIn('type', ['transfert', 'virement'])->sum('montant');

            $balanceGlobale += ($credits - $debits);
            $totalTransactions += $transactions->count();
        }

        $dernieresTransactions = $this->getTransactions(['user_id' => $user->id, 'limit' => 10]);

        return [
            'nombreComptes' => $comptes->count(),
            'balanceGlobale' => $balanceGlobale,
            'totalTransactions' => $totalTransactions,
            'dernieresTransactions' => $dernieresTransactions,
            'comptes' => $comptes,
        ];
    }

    // Méthodes privées pour la validation et logique métier

    private function validateTransactionData(array $data, $user): void
    {
        $compteSource = $this->compteRepository->findById($data['compte_id']);
        if (!$compteSource) {
            throw new \Exception('Compte source non trouvé.', 400);
        }

        // Vérifier les permissions
        if ($user->type === 'client' && $compteSource->titulaire !== $user->id) {
            throw new \Exception('Accès refusé à ce compte.', 403);
        }

        // Vérifier que le compte est actif
        if ($compteSource->statut !== 'actif') {
            throw new \Exception('Le compte source doit être actif.', 400);
        }

        // Pour les transferts et paiements, vérifier le compte destination
        if (in_array($data['type'], ['transfert', 'payement'])) {
            if (empty($data['compte_destination_id'])) {
                throw new \Exception('Le compte destination est requis pour les transferts et paiements.', 400);
            }

            $compteDestination = $this->compteRepository->findById($data['compte_destination_id']);
            if (!$compteDestination) {
                throw new \Exception('Compte destination non trouvé.', 400);
            }

            if ($compteDestination->statut !== 'actif') {
                throw new \Exception('Le compte destination doit être actif.', 400);
            }
        }
    }

    private function checkSufficientBalance(string $compteId, float $montant): void
    {
        $solde = $this->getCompteBalance($compteId);
        if ($solde < $montant) {
            throw new \Exception('Solde insuffisant pour effectuer cette transaction.', 400);
        }
    }

    private function createMirrorTransaction(Transaction $transaction, array $data): void
    {
        $this->transactionRepository->create([
            'compte_id' => $data['compte_destination_id'],
            'compte_destination_id' => $data['compte_id'],
            'type' => 'depot',
            'montant' => $data['montant'],
            'description' => 'Transfert reçu - ' . ($data['description'] ?? 'Transaction'),
            'numero_transaction' => $transaction->numero_transaction . '-DEST',
            'statut' => 'validee',
            'date_transaction' => now(),
        ]);
    }

    private function checkTransactionAccess(Transaction $transaction, $user): void
    {
        $hasAccess = false;

        if ($transaction->compte && $transaction->compte->titulaire === $user->id) {
            $hasAccess = true;
        }

        if ($transaction->compteDestination && $transaction->compteDestination->titulaire === $user->id) {
            $hasAccess = true;
        }

        if (!$hasAccess) {
            throw new \Exception('Accès refusé à cette transaction.', 403);
        }
    }
}