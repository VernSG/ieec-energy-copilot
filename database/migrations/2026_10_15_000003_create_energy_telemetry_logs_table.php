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
        Schema::create('energy_telemetry_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('regulatory_config_id')->nullable()->constrained('regulatory_configs')->nullOnDelete();
            $table->string('preset_source')->index(); // 'sukarela' | 'wajib' | 'upload' | 'iot_live'
            $table->timestamp('timestamp')->index();
            $table->string('panel_id')->index();
            $table->foreign('panel_id')->references('panel_id')->on('sdp_panels')->cascadeOnDelete();
            $table->string('category');
            $table->float('energy_kwh');
            $table->float('power_factor');
            $table->float('kvarh');
            $table->integer('production_qty')->default(0);
            $table->float('area_sqm')->default(0.0);
            $table->string('shift_state')->default('OPERATIONAL');
            $table->timestamps();

            $table->index(['preset_source', 'panel_id', 'timestamp']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('energy_telemetry_logs');
    }
};
