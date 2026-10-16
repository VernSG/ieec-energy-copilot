<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('regulatory_config_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('regulatory_config_id')->nullable()->constrained('regulatory_configs')->cascadeOnDelete();
            $table->string('versi_regulasi')->default('ESDM-2026-Q4-v1.2');
            $table->string('parameter_name');
            $table->text('old_value')->nullable();
            $table->text('new_value')->nullable();
            $table->string('changed_by')->default('Manajer Energi (IEEC)');
            $table->text('notes')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('regulatory_config_logs');
    }
};
