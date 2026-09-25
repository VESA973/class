<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /** Index pour les listes les plus consultees (site public et admin). */
    public function up(): void
    {
        Schema::table('vehicles', fn (Blueprint $table) => $table->index(['is_available', 'category'], 'vehicles_available_category_index'));
        Schema::table('prestations', fn (Blueprint $table) => $table->index(['is_active', 'sort_order'], 'prestations_active_order_index'));
        Schema::table('reservations', fn (Blueprint $table) => $table->index('created_at', 'reservations_created_at_index'));
        Schema::table('quotes', fn (Blueprint $table) => $table->index('created_at', 'quotes_created_at_index'));
    }

    public function down(): void
    {
        Schema::table('vehicles', fn (Blueprint $table) => $table->dropIndex('vehicles_available_category_index'));
        Schema::table('prestations', fn (Blueprint $table) => $table->dropIndex('prestations_active_order_index'));
        Schema::table('reservations', fn (Blueprint $table) => $table->dropIndex('reservations_created_at_index'));
        Schema::table('quotes', fn (Blueprint $table) => $table->dropIndex('quotes_created_at_index'));
    }
};
