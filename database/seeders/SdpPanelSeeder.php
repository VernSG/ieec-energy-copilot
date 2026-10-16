<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\SdpPanel;
use Illuminate\Database\Seeder;

final class SdpPanelSeeder extends Seeder
{
    public function run(): void
    {
        $panels = [
            [
                'panel_id' => 'SDP-01_LINE_A',
                'deskripsi' => 'Lini Pengolahan Biomassa & Pirolisis Biochar',
                'category' => 'PRODUCTION',
                'baseline_enb' => 0.35,
                'default_area_sqm' => 0.0,
            ],
            [
                'panel_id' => 'SDP-02_UTILITY',
                'deskripsi' => 'Utilitas Kompresor Udara & Rotary Dryer',
                'category' => 'PRODUCTION',
                'baseline_enb' => 0.35,
                'default_area_sqm' => 0.0,
            ],
            [
                'panel_id' => 'SDP-03_GEDUNG_UTAMA',
                'deskripsi' => 'Gedung Kantor & Laboratorium R&D Bioenergi Ber-AC',
                'category' => 'BUILDING_AC',
                'baseline_enb' => null,
                'default_area_sqm' => 600.0,
            ],
            [
                'panel_id' => 'SDP-04_GUDANG_FEEDSTOCK',
                'deskripsi' => 'Gudang Penyimpanan Bahan Baku Biomassa Non-AC',
                'category' => 'BUILDING_NON_AC',
                'baseline_enb' => null,
                'default_area_sqm' => 1200.0,
            ],
        ];

        foreach ($panels as $panel) {
            SdpPanel::updateOrCreate(['panel_id' => $panel['panel_id']], $panel);
        }
    }
}
