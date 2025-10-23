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
            $table->enum('type', ['depot', 'retrait']);
            $table->decimal('montant', 15, 2);
            $table->timestamp('date_transaction')->useCurrent();
            $table->timestamps();

            $table->foreign('compte_id')
                ->references('id')
                ->on('comptes')
                ->onDelete('cascade');

            $table->index(['compte_id', 'type']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('transactions');
    }
};
