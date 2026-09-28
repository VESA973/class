<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /** Base clients (particuliers et professionnels), reliee aux demandes et aux devis. Uniquement des ajouts. */
    public function up(): void
    {
        Schema::create('customers', function (Blueprint $table) {
            $table->id();
            $table->string('type', 20)->default('particulier'); // particulier | professionnel
            $table->string('first_name', 100)->nullable();
            $table->string('last_name', 100)->nullable();
            $table->string('company_name', 150)->nullable();
            $table->string('siret', 30)->nullable();
            $table->string('email', 160)->nullable()->index();
            $table->string('phone_mobile', 40)->nullable();
            $table->string('phone_landline', 40)->nullable();
            // Numeros normalises (format international, chiffres seuls) : recherche des doublons.
            $table->string('phone_mobile_key', 20)->nullable()->index();
            $table->string('phone_landline_key', 20)->nullable()->index();
            $table->string('address', 255)->nullable();
            $table->string('postal_code', 20)->nullable();
            $table->string('city', 120)->nullable();
            $table->string('country', 80)->nullable();
            $table->date('birth_date')->nullable();
            $table->string('license_number', 50)->nullable();
            $table->date('license_issued_at')->nullable();
            $table->text('notes')->nullable();
            $table->string('source', 20)->default('admin'); // site | admin | devis | import
            $table->foreignId('merged_into_id')->nullable()->constrained('customers')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();
            $table->index(['last_name', 'first_name']);
            $table->index('company_name');
        });

        Schema::table('reservations', function (Blueprint $table) {
            $table->foreignId('customer_id')->nullable()->after('vehicle_id')->constrained()->nullOnDelete();
        });

        Schema::table('quotes', function (Blueprint $table) {
            $table->foreignId('customer_id')->nullable()->after('reservation_id')->constrained()->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('quotes', function (Blueprint $table) {
            $table->dropConstrainedForeignId('customer_id');
        });

        Schema::table('reservations', function (Blueprint $table) {
            $table->dropConstrainedForeignId('customer_id');
        });

        Schema::dropIfExists('customers');
    }
};
