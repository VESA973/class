<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('vehicles', function (Blueprint $table): void {
            $table->id();
            $table->string('name');
            $table->string('category')->index();
            $table->unsignedInteger('horsepower')->nullable();
            $table->string('fuel_type')->default('Essence');
            $table->string('transmission')->default('Auto');
            $table->unsignedInteger('daily_price');
            $table->string('image_path')->nullable();
            $table->string('image_url')->nullable();
            $table->text('description')->nullable();
            $table->boolean('is_available')->default(true);
            $table->boolean('with_chauffeur')->default(false);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('vehicles');
    }
};
