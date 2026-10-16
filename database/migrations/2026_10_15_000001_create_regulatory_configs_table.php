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
        Schema::create('regulatory_configs', function (Blueprint $table) {
            $table->id();
            $table->string('versi_regulasi')->default('ESDM-2026-Q4-v1.2');
            $table->float('tarif_lwbp_rp_kwh')->default(1035.78);
            $table->float('faktor_k_wbp')->default(1.40);
            $table->float('tarif_wbp_rp_kwh')->default(1450.09);
            $table->string('jam_wbp_mulai')->default('18:00');
            $table->string('jam_wbp_selesai')->default('22:00');
            $table->float('tarif_kvarh_rp')->default(1114.74);
            $table->float('batas_cos_phi')->default(0.85);
            $table->float('rasio_kvarh_bebas')->default(0.62);
            $table->float('faktor_emisi_grid')->default(0.870);
            $table->float('ambang_wajib_toe')->default(4000.0);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('regulatory_configs');
    }
};
