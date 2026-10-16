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
        Schema::create('sdp_panels', function (Blueprint $table) {
            $table->string('panel_id')->primary();
            $table->string('deskripsi');
            $table->string('category');
            $table->float('baseline_enb')->nullable();
            $table->float('default_area_sqm')->default(0.0);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('sdp_panels');
    }
};
