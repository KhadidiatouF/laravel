<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('transactions', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('compte_id');
            $table->enum('type', ['depot', 'retrait', 'transfert', 'virement']);
            $table->decimal('montant', 15, 2);
            $table->string('description')->nullable();
            $table->uuid('compte_destination_id')->nullable(); // Pour les transferts
            $table->string('numero_transaction')->unique(); // Numéro unique de transaction
            $table->enum('statut', ['en_cours', 'validee', 'rejete', 'annule', 'archive'])->default('en_cours');
            $table->timestamp('date_transaction')->useCurrent();
            $table->timestamps();

            $table->foreign('compte_id')
                ->references('id')
                ->on('comptes')
                ->onDelete('cascade');

            $table->foreign('compte_destination_id')
                ->references('id')
                ->on('comptes')
                ->onDelete('set null');

            $table->index(['compte_id', 'type']);
            $table->index(['numero_transaction']);
            $table->index(['date_transaction']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('transactions');
    }
};
