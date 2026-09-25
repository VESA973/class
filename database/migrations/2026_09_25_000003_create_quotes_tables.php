<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Demandes & devis :
     * - reservations.request_status : suivi commercial de la demande (distinct du statut de reservation,
     *   qui continue de piloter le planning et les disponibilites) ;
     * - quotes / quote_lines : devis numerotes DEV-AAAA-NNNN ;
     * - reservation_events : historique de chaque demande.
     */
    public function up(): void
    {
        Schema::table('reservations', function (Blueprint $table): void {
            $table->string('request_status', 20)->default('new')->after('status')->index();
        });

        Schema::create('quotes', function (Blueprint $table): void {
            $table->id();
            // Un devis est un document commercial : il reste conserve si la reservation est supprimee.
            $table->foreignId('reservation_id')->nullable()->constrained()->nullOnDelete();
            $table->string('number')->unique();
            $table->unsignedSmallInteger('year');
            $table->unsignedInteger('sequence');
            $table->string('status', 20)->default('draft')->index(); // draft | sent | accepted | refused
            $table->string('customer_name');
            $table->string('customer_email')->nullable();
            $table->string('customer_phone')->nullable();
            $table->text('customer_address')->nullable();
            $table->string('subject')->nullable();
            $table->date('issued_at');
            $table->date('valid_until');
            $table->string('discount_type', 10)->default('none'); // none | percent | amount
            $table->decimal('discount_value', 12, 2)->default(0);
            $table->decimal('subtotal_ht', 12, 2)->default(0);
            $table->decimal('discount_ht', 12, 2)->default(0);
            $table->decimal('total_ht', 12, 2)->default(0);
            $table->decimal('total_vat', 12, 2)->default(0);
            $table->decimal('total_ttc', 12, 2)->default(0);
            $table->text('conditions')->nullable();
            $table->text('notes')->nullable();
            $table->string('pdf_path')->nullable();
            $table->timestamp('sent_at')->nullable();
            $table->timestamps();
            $table->unique(['year', 'sequence']);
        });

        Schema::create('quote_lines', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('quote_id')->constrained()->cascadeOnDelete();
            $table->unsignedSmallInteger('position')->default(0);
            $table->string('description', 500);
            $table->decimal('quantity', 10, 2)->default(1);
            $table->decimal('unit_price_ht', 12, 2)->default(0);
            $table->decimal('vat_rate', 5, 2)->default(20);
            $table->decimal('total_ht', 12, 2)->default(0);
            $table->timestamps();
        });

        Schema::create('reservation_events', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('reservation_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('type', 40);
            $table->string('description', 500);
            $table->json('meta')->nullable();
            $table->timestamp('created_at')->useCurrent();
            $table->index(['reservation_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('reservation_events');
        Schema::dropIfExists('quote_lines');
        Schema::dropIfExists('quotes');
        Schema::table('reservations', function (Blueprint $table): void {
            $table->dropIndex(['request_status']);
            $table->dropColumn('request_status');
        });
    }
};
