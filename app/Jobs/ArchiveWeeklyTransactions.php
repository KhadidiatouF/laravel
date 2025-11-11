<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use MongoDB\Client;

class ArchiveWeeklyTransactions implements ShouldQueue
{
    use Dispatchable, Queueable, InteractsWithQueue, SerializesModels;

    protected $weekNumber;
    protected $year;

    /**
     * Create a new job instance.
     */
    public function __construct($weekNumber = null, $year = null)
    {
        $this->weekNumber = $weekNumber ?? now()->weekOfYear;
        $this->year = $year ?? now()->year;
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        Log::info("=== DÉBUT ARCHIVAGE HEBDOMADAIRE ===");
        Log::info("Semaine: {$this->weekNumber}, Année: {$this->year}");

        try {
            // Calculer les dates de début et fin de la semaine
            $startOfWeek = now()->setISODate($this->year, $this->weekNumber)->startOfWeek();
            $endOfWeek = now()->setISODate($this->year, $this->weekNumber)->endOfWeek();

            Log::info("Période: {$startOfWeek->toDateString()} au {$endOfWeek->toDateString()}");

            // Récupérer toutes les transactions validées de la semaine
            $transactions = DB::table('transactions')
                ->where('statut', 'validee')
                ->whereBetween('date_transaction', [
                    $startOfWeek->toDateString() . ' 00:00:00',
                    $endOfWeek->toDateString() . ' 23:59:59'
                ])
                ->get();

            if ($transactions->isEmpty()) {
                Log::info("Aucune transaction à archiver pour cette semaine.");
                return;
            }

            Log::info("Nombre de transactions à archiver: " . $transactions->count());

            // Préparer les données pour MongoDB
            $archiveData = [
                'week_number' => $this->weekNumber,
                'year' => $this->year,
                'period' => [
                    'start' => $startOfWeek->toISOString(),
                    'end' => $endOfWeek->toISOString(),
                ],
                'total_transactions' => $transactions->count(),
                'summary' => [
                    'total_deposits' => $transactions->where('type', 'depot')->sum('montant'),
                    'total_withdrawals' => $transactions->whereIn('type', ['retrait', 'transfert', 'virement'])->sum('montant'),
                    'net_balance' => $transactions->where('type', 'depot')->sum('montant') -
                                   $transactions->whereIn('type', ['retrait', 'transfert', 'virement'])->sum('montant'),
                ],
                'transactions' => $transactions->map(function ($transaction) {
                    return [
                        'id' => $transaction->id,
                        'numero_transaction' => $transaction->numero_transaction,
                        'compte_id' => $transaction->compte_id,
                        'compte_destination_id' => $transaction->compte_destination_id,
                        'type' => $transaction->type,
                        'montant' => (float) $transaction->montant,
                        'description' => $transaction->description,
                        'statut' => $transaction->statut,
                        'date_transaction' => $transaction->date_transaction,
                        'created_at' => $transaction->created_at,
                        'updated_at' => $transaction->updated_at,
                    ];
                })->toArray(),
                'archived_at' => now()->toISOString(),
                'metadata' => [
                    'source' => 'laravel_api',
                    'version' => '1.0',
                    'archived_by' => 'weekly_job',
                ]
            ];

            // Connexion à MongoDB et archivage
            $this->archiveToMongoDB($archiveData);

            // Marquer les transactions comme archivées dans MySQL (soft delete logique)
            DB::table('transactions')
                ->whereIn('id', $transactions->pluck('id'))
                ->update([
                    'statut' => 'archive',
                    'updated_at' => now()
                ]);

            Log::info("✅ Archivage terminé avec succès pour la semaine {$this->weekNumber} de {$this->year}");

        } catch (\Exception $e) {
            Log::error("❌ Erreur lors de l'archivage hebdomadaire: " . $e->getMessage());
            Log::error("Trace: " . $e->getTraceAsString());

            // Relancer l'exception pour que le job soit marqué comme échoué
            throw $e;
        }
    }

    /**
     * Archiver les données dans MongoDB
     */
    private function archiveToMongoDB(array $data)
    {
        try {
            // Configuration MongoDB (à adapter selon votre configuration)
            $mongoUri = env('MONGODB_URI', 'mongodb://localhost:27017');
            $databaseName = env('MONGODB_DATABASE', 'banque_archive');

            $client = new Client($mongoUri);
            $database = $client->selectDatabase($databaseName);

            // Collection par semaine
            $collectionName = "transactions_semaine_{$this->weekNumber}_{$this->year}";
            $collection = $database->selectCollection($collectionName);

            // Insérer les données
            $result = $collection->insertOne($data);

            Log::info("✅ Données archivées dans MongoDB - Collection: {$collectionName}, ID: " . $result->getInsertedId());

        } catch (\Exception $e) {
            Log::error("❌ Erreur MongoDB: " . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Déterminer le nombre de tentatives en cas d'échec
     */
    public function tries(): int
    {
        return 3;
    }

    /**
     * Délai entre les tentatives (en secondes)
     */
    public function backoff(): array
    {
        return [60, 300, 600]; // 1min, 5min, 10min
    }
}