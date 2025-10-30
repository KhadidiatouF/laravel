<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('comptes', function (Blueprint $table) {
            $table->timestamp('date_debut_bloquage')->nullable()->after('statut');
            $table->timestamp('date_fin_bloquage')->nullable()->after('date_debut_bloquage');
            $table->integer('duree_bloquage_jours')->nullable()->after('date_fin_bloquage');
        });
    }

    public function down(): void
    {
        Schema::table('comptes', function (Blueprint $table) {
            $table->dropColumn(['date_debut_bloquage', 'date_fin_bloquage', 'duree_bloquage_jours']);
        });
    }
};