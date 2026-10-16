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
        Schema::create('audit_reports', function (Blueprint $table) {
            $table->id();
            $table->foreignId('regulatory_config_id')->nullable()->constrained('regulatory_configs')->nullOnDelete();
            $table->string('preset_source')->index();
            $table->float('total_kwh');
            $table->float('total_toe_tahunan');
            $table->string('status_kepatuhan');
            $table->json('payload_json');
            $table->longText('ai_narrative_html')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('audit_reports');
    }
};
