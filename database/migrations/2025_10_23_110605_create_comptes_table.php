<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('comptes', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('numCompte')->unique()->index();
            $table->uuid('titulaire');
            $table->enum('type', ['courant', 'epargne', 'bloqué', 'cheque']);
            $table->date('date_creation')->useCurrent();
            $table->enum('statut', ['actif', 'inactif', 'fermé', 'bloqué'])->default('actif');
            $table->timestamps();

            $table->foreign('titulaire')->references('id')
                ->on('users')
                ->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('comptes');
    }
};
