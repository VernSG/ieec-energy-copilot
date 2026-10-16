<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\RegulatoryConfig;
use Illuminate\Database\Seeder;

final class RegulatoryConfigSeeder extends Seeder
{
    public function run(): void
    {
        RegulatoryConfig::updateOrCreate(
            ['versi_regulasi' => 'ESDM-2026-Q4-v1.2'],
            [
                'tarif_lwbp_rp_kwh' => 1035.78,
                'faktor_k_wbp' => 1.40,
                'tarif_wbp_rp_kwh' => 1450.09,
                'jam_wbp_mulai' => '18:00',
                'jam_wbp_selesai' => '22:00',
                'tarif_kvarh_rp' => 1114.74,
                'batas_cos_phi' => 0.85,
                'rasio_kvarh_bebas' => 0.62,
                'faktor_emisi_grid' => 0.870,
                'ambang_wajib_toe' => 4000.0,
            ]
        );
    }
}
