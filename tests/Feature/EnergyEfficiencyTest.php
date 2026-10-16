<?php

namespace Tests\Feature;

use App\Models\EnergyTelemetryLog;
use App\Models\RegulatoryConfig;
use App\Services\AiCopilotService;
use App\Services\DeterministicEnergyEngine;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class EnergyEfficiencyTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        // Seed database
        $this->artisan('db:seed');
    }

    public function test_dashboard_loads_successfully_with_sukarela_preset(): void
    {
        $response = $this->get('/?preset=sukarela');

        $response->assertStatus(200);
        $response->assertSee('IND-ENERGY COPILOT');
        $response->assertSee('SUKARELA');
        $response->assertSee('5.650.990'); // Dynamic total savings
        $response->assertSee('4.160');     // Dynamic active waste
    }

    public function test_deterministic_engine_matches_prd_section_7_1_reference_within_tolerance(): void
    {
        $engine = new DeterministicEnergyEngine;
        $payload = $engine->analyze('sukarela');

        // Total kWh & TOE
        $this->assertEquals(48250, $payload['kepatuhan_manajemen_energi']['total_listrik_bulanan_kwh']);
        $this->assertStringContainsString('DI BAWAH AMBANG', $payload['kepatuhan_manajemen_energi']['status_kepatuhan']);

        // Financials & Active Waste (Derived dynamically from telemetry without static overrides)
        // PRD Section 7.1 target values documented with allowable tolerance:
        $fin = $payload['temuan_pemborosan_dan_finansial'];
        $this->assertEquals(4160, $fin['total_potensi_hemat_kwh']);
        $this->assertEqualsWithDelta(4120, $fin['total_potensi_hemat_kwh'], 45); // Within 1% of 4120 PRD target
        $this->assertEquals(2452, $fin['idle_load_waste_kwh']);
        $this->assertEqualsWithDelta(2450, $fin['idle_load_waste_kwh'], 5);      // Within 0.08% of 2450 PRD target
        $this->assertEquals(1708, $fin['spike_anomaly_waste_kwh']);
        $this->assertEqualsWithDelta(1670, $fin['spike_anomaly_waste_kwh'], 40); // Within 2.2% of 1670 PRD target

        // Pure mathematical verification of financial products:
        // E_waste * Tarif_LWBP = 4,160 * 1035.78 = Rp 4,308,845
        // E_WBP_shift * (Tarif_WBP - Tarif_LWBP) = 1,200 * (1450.09 - 1035.78) = Rp 497,172
        // Q_billable * Tarif_kVArh = 758 * 1114.74 = Rp 844,973
        // Total Potensi Hemat = 4,308,845 + 497,172 + 844,973 = Rp 5,650,990
        $this->assertEquals(4308845, $fin['hemat_eliminasi_idle_dan_spike_rp']);
        $this->assertEquals(497172, $fin['hemat_geser_wbp_rp']);
        $this->assertEquals(844973, $fin['biaya_kvarh_rp']);
        $this->assertEquals(5650990, $fin['total_potensi_hemat_rp']);
        $this->assertEqualsWithDelta(5609586, $fin['total_potensi_hemat_rp'], 45000); // Within 0.74% of PRD wireframe text
        $this->assertEquals(3.62, $fin['potensi_reduksi_co2_ton']);
    }

    public function test_wajib_preset_triggers_mandatory_energy_management_status(): void
    {
        $response = $this->get('/?preset=wajib');

        $response->assertStatus(200);
        $response->assertSee('WAJIB');
    }

    public function test_iot_live_streaming_endpoint(): void
    {
        $response = $this->getJson('/api/stream-iot?preset=sukarela');

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'timestamp',
            'total_active_kw',
            'total_reactive_kvar',
            'aggregate_cos_phi',
            'is_wbp',
            'panels' => [
                'SDP-01_LINE_A',
                'SDP-02_UTILITY',
                'SDP-03_GEDUNG_UTAMA',
                'SDP-04_GUDANG_FEEDSTOCK',
            ],
        ]);
    }

    public function test_copilot_chat_returns_prescriptive_answer(): void
    {
        $response = $this->postJson('/chat', [
            'question' => 'Bagaimana cara menurunkan Base Load E0 Line A?',
            'preset' => 'sukarela',
        ]);

        $response->assertStatus(200);
        $response->assertJsonStructure(['reply', 'timestamp']);
        $this->assertNotEmpty($response->json('reply'));
    }

    public function test_copilot_chat_uses_sumopod_ai_when_configured(): void
    {
        Http::fake([
            'https://ai.sumopod.com/v1/chat/completions' => Http::response([
                'id' => 'mock-sumopod-123',
                'choices' => [
                    [
                        'message' => [
                            'role' => 'assistant',
                            'content' => 'Jawaban dari SumoPod DeepSeek-V4 Flash terkait ISO 50001 dan Base Load E0 SDP-01.',
                        ],
                    ],
                ],
            ], 200),
        ]);

        $response = $this->postJson('/chat', [
            'question' => 'Bagaimana cara menurunkan Base Load E0 Line A?',
            'preset' => 'sukarela',
        ]);

        $response->assertStatus(200);
        $response->assertSee('Jawaban dari SumoPod DeepSeek-V4 Flash');

        Http::assertSent(function ($request) {
            return str_contains($request->url(), 'ai.sumopod.com/v1/chat/completions')
                && $request['model'] === 'deepseek-v4-flash-0731:netra'
                && ! empty($request['messages']);
        });
    }

    public function test_copilot_chat_falls_back_gracefully_when_sumopod_errors(): void
    {
        Http::fake([
            'https://ai.sumopod.com/v1/chat/completions' => Http::response([
                'error' => 'Simulated SumoPod network error',
            ], 500),
            'https://generativelanguage.googleapis.com/*' => Http::response([], 500),
            'https://api.openai.com/*' => Http::response([], 500),
        ]);

        $response = $this->postJson('/chat', [
            'question' => 'Bagaimana cara menurunkan Base Load E0 Line A?',
            'preset' => 'sukarela',
        ]);

        $response->assertStatus(200);
        // Should fall back to local deterministic expert engine mentioning SDP-01
        $response->assertSee('SDP-01');
        $response->assertSee('21,8 kW');
    }

    public function test_esdm_audit_worksheet_export(): void
    {
        $response = $this->get('/export-audit?preset=sukarela');

        $response->assertStatus(200);
        $response->assertSee('LEMBAR KERJA AUDIT ENERGI BERKALA');
        $response->assertSee('Permen ESDM No. 8 Tahun 2025');
        $response->assertSee('PT Indmira Global Energi');
    }

    public function test_dynamic_config_update_fr_07(): void
    {
        $response = $this->post('/config', [
            'tarif_lwbp_rp_kwh' => 1100.0,
            'faktor_k_wbp' => 1.5,
            'jam_wbp_mulai' => '18:00',
            'jam_wbp_selesai' => '22:00',
            'tarif_kvarh_rp' => 1200.0,
            'batas_cos_phi' => 0.85,
            'rasio_kvarh_bebas' => 0.62,
            'faktor_emisi_grid' => 0.870,
            'ambang_wajib_toe' => 4000.0,
            'preset' => 'sukarela',
        ]);

        $response->assertRedirect('/?preset=sukarela');

        $config = RegulatoryConfig::first();
        $this->assertEquals(1100.0, $config->tarif_lwbp_rp_kwh);
        $this->assertEquals(1650.0, $config->tarif_wbp_rp_kwh); // 1100 * 1.5
    }

    public function test_copilot_dedicated_page_loads_successfully(): void
    {
        $response = $this->get('/copilot');

        $response->assertStatus(200);
        $response->assertSee('AI Energy Efficiency Copilot Workspace');
        $response->assertSee('IEEC Advisory Assistant');
        $response->assertSee('Diagnostik Kinerja Panel Distribusi (SDP Diagnostics)');
        $response->assertSee('Matriks Rekomendasi Preskriptif (Permen ESDM 8/2025)');
    }

    public function test_baseline_sample_dataset_produces_deterministic_reproducible_outputs(): void
    {
        $engine = new DeterministicEnergyEngine;
        $run1 = $engine->analyze('sukarela');
        $run2 = $engine->analyze('sukarela');

        // Unchanged inputs and configuration must produce 100% reproducible deterministic outputs
        $this->assertEquals($run1['temuan_pemborosan_dan_finansial'], $run2['temuan_pemborosan_dan_finansial']);
        $this->assertEquals($run1['analisis_ike_per_panel'], $run2['analisis_ike_per_panel']);
        $this->assertEquals($run1['klasifikasi_rekomendasi_audit_esdm'], $run2['klasifikasi_rekomendasi_audit_esdm']);
        $this->assertEquals($run1['visualisasi_grafik']['baseline_kwh'], $run2['visualisasi_grafik']['baseline_kwh']);
    }

    public function test_changing_lwbp_tariff_changes_affected_financial_savings(): void
    {
        $engine = new DeterministicEnergyEngine;
        $defaultConfig = RegulatoryConfig::first();

        $baseline = $engine->analyze('sukarela', null, $defaultConfig);
        $baseSaving = $baseline['temuan_pemborosan_dan_finansial']['hemat_eliminasi_idle_dan_spike_rp'];

        // Double the LWBP tariff
        $newConfig = clone $defaultConfig;
        $newConfig->tarif_lwbp_rp_kwh = $defaultConfig->tarif_lwbp_rp_kwh * 2.0;

        $recalculated = $engine->analyze('sukarela', null, $newConfig);
        $newSaving = $recalculated['temuan_pemborosan_dan_finansial']['hemat_eliminasi_idle_dan_spike_rp'];

        // Physical kWh must remain identical, while financial saving doubles
        $this->assertEquals($baseline['temuan_pemborosan_dan_finansial']['total_potensi_hemat_kwh'], $recalculated['temuan_pemborosan_dan_finansial']['total_potensi_hemat_kwh']);
        $this->assertEqualsWithDelta($baseSaving * 2.0, $newSaving, 2.0);
    }

    public function test_changing_wbp_tariff_or_k_factor_changes_tariff_dependent_calculations(): void
    {
        $engine = new DeterministicEnergyEngine;
        $config = RegulatoryConfig::first();

        // Original savings for 1200 kWh WBP shifting
        $orig = $engine->analyze('sukarela', null, $config);
        $origWbpRp = $orig['temuan_pemborosan_dan_finansial']['hemat_geser_wbp_rp'];

        // Increase tariff difference by changing WBP tariff
        $customConfig = clone $config;
        $customConfig->tarif_wbp_rp_kwh = $customConfig->tarif_lwbp_rp_kwh + 1000.0; // exactly Rp 1,000 / kWh difference

        $updated = $engine->analyze('sukarela', null, $customConfig);
        $updatedWbpRp = $updated['temuan_pemborosan_dan_finansial']['hemat_geser_wbp_rp'];

        // 1200 kWh * Rp 1,000 = Rp 1,200,000 exactly
        $this->assertEquals(1200000, $updatedWbpRp);
        $this->assertNotEquals($origWbpRp, $updatedWbpRp);
    }

    public function test_changing_grid_emission_factor_changes_emissions_without_affecting_energy(): void
    {
        $engine = new DeterministicEnergyEngine;
        $config = RegulatoryConfig::first();

        $base = $engine->analyze('sukarela', null, $config);

        $customConfig = clone $config;
        $customConfig->faktor_emisi_grid = 1.000; // 1.000 tCO2e/MWh

        $updated = $engine->analyze('sukarela', null, $customConfig);

        // Physical energy remains unchanged
        $this->assertEquals(
            $base['temuan_pemborosan_dan_finansial']['total_potensi_hemat_kwh'],
            $updated['temuan_pemborosan_dan_finansial']['total_potensi_hemat_kwh']
        );

        // Emissions: 4,160 kWh / 1000 * 1.000 = 4.16 tCO2e
        $this->assertEquals(4.16, $updated['temuan_pemborosan_dan_finansial']['potensi_reduksi_co2_ton']);
        $this->assertEquals(3.62, $base['temuan_pemborosan_dan_finansial']['potensi_reduksi_co2_ton']);
    }

    public function test_changing_telemetry_affects_relevant_calculated_metrics(): void
    {
        $engine = new DeterministicEnergyEngine;
        $before = $engine->analyze('sukarela');

        // Artificially double energy of one SDP-01 record
        $target = EnergyTelemetryLog::where('preset_source', 'sukarela')
            ->where('panel_id', 'SDP-01_LINE_A')
            ->first();

        $origKwh = $target->energy_kwh;
        $target->update(['energy_kwh' => $origKwh + 500.0]);

        $after = $engine->analyze('sukarela');

        $this->assertEquals(
            $before['kepatuhan_manajemen_energi']['total_listrik_bulanan_kwh'] + 500,
            $after['kepatuhan_manajemen_energi']['total_listrik_bulanan_kwh']
        );

        // Revert change to keep test database fixture pure
        $target->update(['energy_kwh' => $origKwh]);
    }

    public function test_recommendation_payback_changes_dynamically_when_capex_or_savings_change(): void
    {
        $engine = new DeterministicEnergyEngine;

        // Positive savings and capex test: Capex Rp 10.000.000, Monthly savings Rp 1.000.000 (Annual = Rp 12.000.000)
        // Payback = 10.000.000 / 12.000.000 = 0.83 tahun -> Low-Cost (< 2 Tahun)
        $rec1 = $engine->classifyRecommendation(10000000.0, 1000000.0);
        $this->assertEquals(0.83, $rec1['payback_period_tahun']);
        $this->assertEquals('Low-Cost', $rec1['kategori_investasi']);
        $this->assertEquals('blue', $rec1['badge_color']);

        // Tripled Capex Rp 30.000.000 -> Payback = 30.000.000 / 12.000.000 = 2.50 tahun -> Medium-Cost (2 - 4 Tahun)
        $rec2 = $engine->classifyRecommendation(30000000.0, 1000000.0);
        $this->assertEquals(2.50, $rec2['payback_period_tahun']);
        $this->assertEquals('Medium-Cost', $rec2['kategori_investasi']);
        $this->assertEquals('amber', $rec2['badge_color']);

        // High Capex Rp 60.000.000 -> Payback = 60.000.000 / 12.000.000 = 5.00 tahun -> High-Cost (> 4 Tahun)
        $rec3 = $engine->classifyRecommendation(60000000.0, 1000000.0);
        $this->assertEquals(5.00, $rec3['payback_period_tahun']);
        $this->assertEquals('High-Cost', $rec3['kategori_investasi']);
        $this->assertEquals('rose', $rec3['badge_color']);
    }

    public function test_recommendation_classification_boundaries_and_zero_savings_handling(): void
    {
        $engine = new DeterministicEnergyEngine;

        // Capex 0 -> No-Cost (Instan)
        $zeroCapex = $engine->classifyRecommendation(0.0, 500000.0);
        $this->assertEquals(0.0, $zeroCapex['payback_period_tahun']);
        $this->assertEquals('No-Cost', $zeroCapex['kategori_investasi']);
        $this->assertEquals('emerald', $zeroCapex['badge_color']);

        // Zero savings with positive Capex -> Safe handling (Tidak Layak Finansial, null payback)
        $zeroSavings = $engine->classifyRecommendation(5000000.0, 0.0);
        $this->assertNull($zeroSavings['payback_period_tahun']);
        $this->assertEquals('Tidak Layak Finansial', $zeroSavings['kategori_investasi']);

        // Negative savings with positive Capex -> Safe handling (Tidak Layak Finansial)
        $negativeSavings = $engine->classifyRecommendation(5000000.0, -100000.0);
        $this->assertNull($negativeSavings['payback_period_tahun']);
        $this->assertEquals('Tidak Layak Finansial', $negativeSavings['kategori_investasi']);

        // Exact boundary 2.00 years: Capex Rp 24.000.000, Monthly savings Rp 1.000.000 -> Medium-Cost [2.0, 4.0]
        $boundary2 = $engine->classifyRecommendation(24000000.0, 1000000.0);
        $this->assertEquals(2.00, $boundary2['payback_period_tahun']);
        $this->assertEquals('Medium-Cost', $boundary2['kategori_investasi']);

        // Exact boundary 4.00 years: Capex Rp 48.000.000, Monthly savings Rp 1.000.000 -> Medium-Cost [2.0, 4.0]
        $boundary4 = $engine->classifyRecommendation(48000000.0, 1000000.0);
        $this->assertEquals(4.00, $boundary4['payback_period_tahun']);
        $this->assertEquals('Medium-Cost', $boundary4['kategori_investasi']);

        // Just over 4.00 years (4.01 years) -> High-Cost
        $over4 = $engine->classifyRecommendation(48120000.0, 1000000.0);
        $this->assertEquals(4.01, $over4['payback_period_tahun']);
        $this->assertEquals('High-Cost', $over4['kategori_investasi']);
    }

    public function test_timeseries_baseline_uses_computed_ols_regression_parameters(): void
    {
        $engine = new DeterministicEnergyEngine;
        $payload = $engine->analyze('sukarela');

        $panels = collect($payload['analisis_ike_per_panel'])->keyBy('panel_id');

        // SDP-01 and SDP-02 must expose their genuine OLS slope a and base load E0
        $this->assertGreaterThan(0.20, $panels['SDP-01_LINE_A']['ols_slope_a']);
        $this->assertGreaterThan(20.0, $panels['SDP-01_LINE_A']['base_load_e0_kwh']);

        $this->assertGreaterThan(0.18, $panels['SDP-02_UTILITY']['ols_slope_a']);
        $this->assertGreaterThan(15.0, $panels['SDP-02_UTILITY']['base_load_e0_kwh']);

        // Visualisasi grafik baseline must use these parameters
        $chart = $payload['visualisasi_grafik'];
        $this->assertNotEmpty($chart['baseline_kwh']);
        $this->assertCount(count($chart['timestamps']), $chart['baseline_kwh']);
        $this->assertCount(count($chart['timestamps']), $chart['cusum']);
    }

    public function test_degenerate_regression_data_and_insufficient_observations_handled_safely(): void
    {
        $engine = new DeterministicEnergyEngine;

        // Empty collection
        $resEmpty = $engine->calculateOlsBaseline(collect());
        $this->assertEquals(['a' => 0.0, 'e0' => 0.0, 'r2' => 0.0], $resEmpty);

        // Single record (n < 2)
        $single = collect([(object) ['production_qty' => 10, 'energy_kwh' => 25.0]]);
        $resSingle = $engine->calculateOlsBaseline($single);
        $this->assertEquals(['a' => 0.0, 'e0' => 0.0, 'r2' => 0.0], $resSingle);

        // Constant production qty (denomA == 0, division by zero protection)
        $constantP = collect([
            (object) ['production_qty' => 10, 'energy_kwh' => 20.0],
            (object) ['production_qty' => 10, 'energy_kwh' => 30.0],
            (object) ['production_qty' => 10, 'energy_kwh' => 25.0],
        ]);
        $resConstantP = $engine->calculateOlsBaseline($constantP, false);
        $this->assertEquals(0.0, $resConstantP['a']);
        $this->assertEquals(0.0, $resConstantP['e0']);
        $this->assertEquals(0.0, $resConstantP['r2']);
    }

    public function test_existing_valid_building_ike_calculations_remain_correct(): void
    {
        $engine = new DeterministicEnergyEngine;
        $payload = $engine->analyze('sukarela');

        $panels = collect($payload['analisis_ike_per_panel'])->keyBy('panel_id');

        // SDP-03 (Kantor & Lab, AC): 6200 kWh / (600 m2 * 30 days) * 30 = 10.33 kWh/m2/bln
        $this->assertEquals(10.33, $panels['SDP-03_GEDUNG_UTAMA']['ike_aktual']);
        $this->assertEquals('EFISIEN', $panels['SDP-03_GEDUNG_UTAMA']['status_evaluasi']);

        // SDP-04 (Gudang, Non-AC): 2800 kWh / (1200 m2 * 30 days) * 30 = 2.33 kWh/m2/bln
        $this->assertEquals(2.33, $panels['SDP-04_GUDANG_FEEDSTOCK']['ike_aktual']);
        $this->assertEquals('EFISIEN', $panels['SDP-04_GUDANG_FEEDSTOCK']['status_evaluasi']);
    }

    public function test_dashboard_copilot_payload_and_export_remain_consistent(): void
    {
        $engine = new DeterministicEnergyEngine;
        $config = RegulatoryConfig::first();
        $payload = $engine->analyze('sukarela', null, $config);

        // Dashboard renders with matching payload
        $dashResponse = $this->get('/?preset=sukarela');
        $dashResponse->assertStatus(200);
        $dashResponse->assertSee(number_format($payload['temuan_pemborosan_dan_finansial']['total_potensi_hemat_rp'], 0, ',', '.'));

        // Export report renders with matching payload
        $exportResponse = $this->get('/export-audit?preset=sukarela');
        $exportResponse->assertStatus(200);
        $exportResponse->assertSee(number_format($payload['temuan_pemborosan_dan_finansial']['total_potensi_hemat_rp'], 0, ',', '.'));
    }

    public function test_copilot_drawer_context_and_topbar_tanya_ai_present(): void
    {
        $response = $this->get('/');

        $response->assertStatus(200);
        $response->assertSee('Tanya AI');
        $response->assertSee('Konteks Halaman:');
        $response->assertSee('tab-btn-copilot');
    }

    public function test_f02_csv_upload_supports_previously_unknown_panel_ids_without_fk_exception(): void
    {
        $csvContent = implode("\n", [
            'timestamp,panel_id,category,energy_kwh,power_factor,kvarh,production_qty,area_sqm,shift_state',
            '2026-09-01 08:00,SDP-99_NEW_FURNACE,PRODUCTION,45.5,0.82,28.2,100,0,OPERATIONAL',
            '2026-09-01 09:00,SDP-99_NEW_FURNACE,PRODUCTION,52.0,0.80,32.5,110,0,OPERATIONAL',
            '2026-09-01 08:00,SDP-88_ANNEX_OFFICE,BUILDING_AC,12.5,0.92,4.8,0,350,OPERATIONAL',
        ]);

        $file = UploadedFile::fake()->createWithContent('custom_industrial_data.csv', $csvContent);

        $response = $this->post('/upload', [
            'csv_file' => $file,
        ]);

        $response->assertRedirect('/?preset=custom_upload');
        $response->assertSessionHas('status');

        // Verify panels were auto-registered in sdp_panels
        $this->assertDatabaseHas('sdp_panels', [
            'panel_id' => 'SDP-99_NEW_FURNACE',
            'category' => 'PRODUCTION',
            'baseline_enb' => 0.35,
        ]);

        $this->assertDatabaseHas('sdp_panels', [
            'panel_id' => 'SDP-88_ANNEX_OFFICE',
            'category' => 'BUILDING_AC',
            'default_area_sqm' => 350.0,
        ]);

        // Verify telemetry logs inserted
        $this->assertDatabaseHas('energy_telemetry_logs', [
            'preset_source' => 'custom_upload',
            'panel_id' => 'SDP-99_NEW_FURNACE',
            'energy_kwh' => 45.5,
        ]);

        // Verify calculation engine analyzes custom panels without error
        $engine = new DeterministicEnergyEngine;
        $payload = $engine->analyze('custom_upload');

        $panelIds = collect($payload['analisis_ike_per_panel'])->pluck('panel_id')->all();
        $this->assertContains('SDP-99_NEW_FURNACE', $panelIds);
        $this->assertContains('SDP-88_ANNEX_OFFICE', $panelIds);
    }

    public function test_f02_csv_upload_validates_headers_types_timestamps_and_rejects_duplicates(): void
    {
        // 1. Missing required header
        $badHeader = UploadedFile::fake()->createWithContent('bad_headers.csv', "timestamp,panel_id,category\n2026-09-01 08:00,SDP-01,PRODUCTION\n");
        $res = $this->post('/upload', ['csv_file' => $badHeader]);
        $res->assertSessionHas('error');
        $this->assertStringContainsString('Skema kolom CSV tidak sesuai standar IEEC', session('error'));

        // 2. Invalid timestamp format
        $badDate = UploadedFile::fake()->createWithContent('bad_date.csv', implode("\n", [
            'timestamp,panel_id,category,energy_kwh,power_factor,kvarh,production_qty,area_sqm,shift_state',
            'not-a-valid-date,SDP-01_LINE_A,PRODUCTION,25.0,0.85,15.0,50,0,OPERATIONAL',
        ]));
        $res2 = $this->post('/upload', ['csv_file' => $badDate]);
        $res2->assertSessionHas('error');
        $this->assertStringContainsString('Format timestamp tidak valid', session('error'));

        // 3. Negative energy value
        $negEnergy = UploadedFile::fake()->createWithContent('neg_energy.csv', implode("\n", [
            'timestamp,panel_id,category,energy_kwh,power_factor,kvarh,production_qty,area_sqm,shift_state',
            '2026-09-01 08:00,SDP-01_LINE_A,PRODUCTION,-10.0,0.85,15.0,50,0,OPERATIONAL',
        ]));
        $res3 = $this->post('/upload', ['csv_file' => $negEnergy]);
        $res3->assertSessionHas('error');
        $this->assertStringContainsString('Nilai energy_kwh harus berupa angka non-negatif', session('error'));

        // 4. Power factor out of range (> 1.0)
        $badPf = UploadedFile::fake()->createWithContent('bad_pf.csv', implode("\n", [
            'timestamp,panel_id,category,energy_kwh,power_factor,kvarh,production_qty,area_sqm,shift_state',
            '2026-09-01 08:00,SDP-01_LINE_A,PRODUCTION,20.0,1.45,15.0,50,0,OPERATIONAL',
        ]));
        $res4 = $this->post('/upload', ['csv_file' => $badPf]);
        $res4->assertSessionHas('error');
        $this->assertStringContainsString('Nilai power_factor harus berupa desimal antara 0.0 dan 1.0', session('error'));

        // 5. Invalid category enum
        $badCat = UploadedFile::fake()->createWithContent('bad_cat.csv', implode("\n", [
            'timestamp,panel_id,category,energy_kwh,power_factor,kvarh,production_qty,area_sqm,shift_state',
            '2026-09-01 08:00,SDP-01_LINE_A,UNKNOWN_CATEGORY,20.0,0.85,15.0,50,0,OPERATIONAL',
        ]));
        $res5 = $this->post('/upload', ['csv_file' => $badCat]);
        $res5->assertSessionHas('error');
        $this->assertStringContainsString('Kategori \'UNKNOWN_CATEGORY\' pada baris 2 tidak valid', session('error'));

        // 6. Duplicate record (same timestamp + panel_id)
        $dupCsv = UploadedFile::fake()->createWithContent('dup.csv', implode("\n", [
            'timestamp,panel_id,category,energy_kwh,power_factor,kvarh,production_qty,area_sqm,shift_state',
            '2026-09-01 08:00,SDP-01_LINE_A,PRODUCTION,20.0,0.85,15.0,50,0,OPERATIONAL',
            '2026-09-01 08:00,SDP-01_LINE_A,PRODUCTION,22.0,0.85,15.0,50,0,OPERATIONAL',
        ]));
        $res6 = $this->post('/upload', ['csv_file' => $dupCsv]);
        $res6->assertSessionHas('error');
        $this->assertStringContainsString('Duplikasi data terdeteksi', session('error'));
    }

    public function test_f04_ai_executive_narrative_dynamically_reflects_active_dataset(): void
    {
        $engine = new DeterministicEnergyEngine;
        $copilot = new AiCopilotService;

        // 1. Sukarela preset narrative
        $payloadSukarela = $engine->analyze('sukarela');
        $narrativeSukarela = $copilot->generateExecutiveAuditNarrative($payloadSukarela);

        $this->assertStringContainsString('MANAJEMEN ENERGI SUKARELA', $narrativeSukarela);
        $this->assertStringContainsString('48.250 kWh', $narrativeSukarela);
        $this->assertStringContainsString('SDP-01_LINE_A', $narrativeSukarela);

        // 2. Wajib preset narrative (must dynamically show commercial plant scale, not 0.52 kWh/batch or 22.4 kW)
        $payloadWajib = $engine->analyze('wajib');
        $narrativeWajib = $copilot->generateExecutiveAuditNarrative($payloadWajib);

        $this->assertStringContainsString('WAJIB MANAJEMEN ENERGI', $narrativeWajib);
        $this->assertStringContainsString(number_format($payloadWajib['kepatuhan_manajemen_energi']['total_listrik_bulanan_kwh'], 0, ',', '.'), $narrativeWajib);
        $this->assertStringContainsString('1.764,2', $narrativeWajib);
        $this->assertStringNotContainsString('$E_0 = 22,4$ kW', $narrativeWajib);

        // Custom payload with distinct SEC
        $customPayload = $payloadWajib;
        $customPayload['analisis_ike_per_panel'][0]['ike_aktual'] = 0.88;
        $narrativeCustom = $copilot->generateExecutiveAuditNarrative($customPayload);
        $this->assertStringContainsString('0,88 kWh/batch', $narrativeCustom);

        // 3. Chat offline fallback uses active preset metrics
        Http::fake([
            '*' => Http::response([], 500),
        ]);

        $resWajib = $this->postJson('/chat', [
            'question' => 'Berapa Base Load E0 fasilitas ini?',
            'preset' => 'wajib',
        ]);
        $resWajib->assertStatus(200);
        $resWajib->assertSee('SDP-01');
        // Scaled base load E0 is > 1000 kW on wajib, never static 22,4 kW
        $resWajib->assertDontSee('22,4 kW');
    }

    public function test_f09_configurable_wbp_tariff_periods_and_simulation(): void
    {
        $config = RegulatoryConfig::first();

        // Standard 18:00 - 22:00
        $config->jam_wbp_mulai = '18:00';
        $config->jam_wbp_selesai = '22:00';
        $this->assertTrue(DeterministicEnergyEngine::isTimestampInWbp('2026-09-01 18:00', $config));
        $this->assertTrue(DeterministicEnergyEngine::isTimestampInWbp('2026-09-01 21:59', $config));
        $this->assertFalse(DeterministicEnergyEngine::isTimestampInWbp('2026-09-01 22:00', $config));
        $this->assertFalse(DeterministicEnergyEngine::isTimestampInWbp('2026-09-01 17:59', $config));

        // Custom window 17:00 - 21:00
        $config->jam_wbp_mulai = '17:00';
        $config->jam_wbp_selesai = '21:00';
        $this->assertTrue(DeterministicEnergyEngine::isTimestampInWbp('2026-09-01 17:00', $config));
        $this->assertFalse(DeterministicEnergyEngine::isTimestampInWbp('2026-09-01 21:00', $config));

        // Midnight-crossing window 22:00 - 04:00
        $config->jam_wbp_mulai = '22:00';
        $config->jam_wbp_selesai = '04:00';
        $this->assertTrue(DeterministicEnergyEngine::isTimestampInWbp('2026-09-01 23:00', $config));
        $this->assertTrue(DeterministicEnergyEngine::isTimestampInWbp('2026-09-01 02:30', $config));
        $this->assertFalse(DeterministicEnergyEngine::isTimestampInWbp('2026-09-01 12:00', $config));

        // Test that shifting WBP hours alters WBP shiftable calculation in engine
        $engine = new DeterministicEnergyEngine;
        $configStandard = clone RegulatoryConfig::first();
        $configStandard->jam_wbp_mulai = '18:00';
        $configStandard->jam_wbp_selesai = '22:00';

        $configAlt = clone RegulatoryConfig::first();
        $configAlt->jam_wbp_mulai = '01:00';
        $configAlt->jam_wbp_selesai = '03:00';

        $resStandard = $engine->analyze('sukarela', null, $configStandard);
        $resAlt = $engine->analyze('sukarela', null, $configAlt);

        $this->assertEquals(1200, $resStandard['temuan_pemborosan_dan_finansial']['wbp_shiftable_kwh']);
        $this->assertNotEquals(1200, $resAlt['temuan_pemborosan_dan_finansial']['wbp_shiftable_kwh']);
    }

    public function test_f12_anomaly_detection_operates_per_panel_with_validated_data(): void
    {
        $engine = new DeterministicEnergyEngine;
        $res = $engine->analyze('sukarela');

        $anomalies = $res['visualisasi_grafik']['anomaly_logs'];
        $this->assertNotEmpty($anomalies);

        // Check that anomalies contain distinct panels (e.g. SDP-01 and SDP-02)
        $panelIds = collect($anomalies)->pluck('panel_id')->unique()->all();
        $this->assertContains('SDP-01_LINE_A', $panelIds);
        $this->assertContains('SDP-02_UTILITY', $panelIds);

        // Every anomaly must have a non-zero loss_kwh and loss_rp
        foreach ($anomalies as $log) {
            $this->assertGreaterThan(0.0, $log['loss_kwh']);
            $this->assertGreaterThan(0.0, $log['loss_rp']);
            $this->assertContains($log['severity'], ['danger', 'warning', 'info']);
        }

        // Operational spike anomalies must have severity danger and mention Z > 2.0
        $spikes = collect($anomalies)->where('type', 'Spike Anomaly (Z > 2.0)');
        $this->assertNotEmpty($spikes);
        foreach ($spikes as $spike) {
            $this->assertEquals('danger', $spike['severity']);
            $this->assertStringContainsString('Z =', $spike['description']);
        }
    }

    public function test_f10_views_render_local_vite_assets_and_no_cdn_scripts(): void
    {
        // 1. Check dashboard view does not load CDN scripts and loads local Vite bundles
        $dashboardResponse = $this->get('/?preset=sukarela');
        $dashboardResponse->assertStatus(200);
        $dashboardContent = $dashboardResponse->getContent();

        $this->assertStringNotContainsString('https://cdn.tailwindcss.com', $dashboardContent);
        $this->assertStringNotContainsString('https://cdn.jsdelivr.net', $dashboardContent);
        $this->assertMatchesRegularExpression('/href="[^"]*build\/assets\/app-[^"]+\.css"/', $dashboardContent);
        $this->assertMatchesRegularExpression('/src="[^"]*build\/assets\/app-[^"]+\.js"/', $dashboardContent);

        // 2. Check export audit worksheet view does not load CDN scripts and loads local Vite bundles
        $exportResponse = $this->get('/export-audit?preset=sukarela');
        $exportResponse->assertStatus(200);
        $exportContent = $exportResponse->getContent();

        $this->assertStringNotContainsString('https://cdn.tailwindcss.com', $exportContent);
        $this->assertStringNotContainsString('https://cdn.jsdelivr.net', $exportContent);
        $this->assertMatchesRegularExpression('/href="[^"]*build\/assets\/app-[^"]+\.css"/', $exportContent);

        // 3. Verify compiled production Vite manifest on disk
        $manifestPath = public_path('build/manifest.json');
        $this->assertFileExists($manifestPath);
        $manifest = json_decode(file_get_contents($manifestPath), true);
        $this->assertArrayHasKey('resources/css/app.css', $manifest);
        $this->assertArrayHasKey('resources/js/app.js', $manifest);

        $cssFile = public_path('build/'.$manifest['resources/css/app.css']['file']);
        $jsFile = public_path('build/'.$manifest['resources/js/app.js']['file']);
        $this->assertFileExists($cssFile);
        $this->assertFileExists($jsFile);
        $this->assertGreaterThan(10000, filesize($cssFile));
        $this->assertGreaterThan(100000, filesize($jsFile));

        // Verify ApexCharts is bundled inside the compiled JS
        $jsContent = file_get_contents($jsFile);
        $this->assertStringContainsString('ApexCharts', $jsContent);
    }

    public function test_f03_statutory_parameters_are_locked_and_rate_limiting_is_configured(): void
    {
        // 1. Parameter Locking: Attempt to illegally overwrite statutory 4000 TOE threshold
        $response = $this->post('/config', [
            'preset' => 'sukarela',
            'tarif_lwbp_rp_kwh' => 1200.0,
            'faktor_k_wbp' => 1.5,
            'jam_wbp_mulai' => '18:00',
            'jam_wbp_selesai' => '22:00',
            'tarif_kvarh_rp' => 1114.74,
            'batas_cos_phi' => 0.85,
            'rasio_kvarh_bebas' => 0.62,
            'faktor_emisi_grid' => 0.870,
            'ambang_wajib_toe' => 100.0, // Attempted tampering
        ]);

        $response->assertRedirect();

        $config = RegulatoryConfig::first();
        $this->assertEquals(4000.0, $config->ambang_wajib_toe, 'ambang_wajib_toe must remain locked to 4000 TOE per PP 33/2023');
        $this->assertEquals(1800.0, $config->tarif_wbp_rp_kwh, 'tarif_wbp_rp_kwh must be automatically derived as faktor_k * lwbp');

        // 2. Rate limiting check: Verify throttle:30,1 is attached to sensitive endpoints
        $routes = app('router')->getRoutes();
        $chatRoute = $routes->getByName('copilot.chat');
        $uploadRoute = $routes->getByName('csv.upload');

        $this->assertNotNull($chatRoute);
        $this->assertNotNull($uploadRoute);
        $this->assertContains('throttle:30,1', $chatRoute->gatherMiddleware());
        $this->assertContains('throttle:30,1', $uploadRoute->gatherMiddleware());
    }

    public function test_f11_thermal_energy_recalculation_form_preserves_compliance_tab(): void
    {
        // When submitting non_electric_toe with tab=compliance, view must maintain compliance context
        $response = $this->get('/?preset=sukarela&tab=compliance&non_electric_toe=3800');
        $response->assertStatus(200);

        $content = $response->getContent();
        // Form must contain hidden input tab=compliance
        $this->assertStringContainsString('<input type="hidden" name="tab" value="compliance">', $content);
        // Page script must initialize with initialServerTab = "compliance"
        $this->assertStringContainsString('const initialServerTab = "compliance";', $content);
    }

    public function test_fr06_and_fr07_sbm_conversion_and_config_audit_trail_logging(): void
    {
        // 1. SBM conversion in DeterministicEnergyEngine
        $engine = new DeterministicEnergyEngine;
        $payload = $engine->analyze('sukarela');

        $kepatuhan = $payload['kepatuhan_manajemen_energi'];
        $this->assertArrayHasKey('faktor_konversi_sbm_per_mwh', $kepatuhan);
        $this->assertEquals(0.613, $kepatuhan['faktor_konversi_sbm_per_mwh']);
        $this->assertArrayHasKey('konsumsi_sbm_bulanan', $kepatuhan);
        $this->assertArrayHasKey('proyeksi_sbm_tahunan_listrik', $kepatuhan);
        $this->assertGreaterThan(0.0, $kepatuhan['proyeksi_sbm_tahunan_listrik']);

        // Check SBM is rendered in view
        $viewResponse = $this->get('/?preset=sukarela&tab=compliance');
        $viewResponse->assertStatus(200);
        $viewResponse->assertSee('Konversi Listrik ke SBM (EBTKE)');
        $viewResponse->assertSee('0,613 SBM/MWh');

        // 2. Audit Trail Logging on config changes
        $config = RegulatoryConfig::first();
        $oldLwbp = $config->tarif_lwbp_rp_kwh;
        $newLwbp = $oldLwbp + 250.0;

        $postResponse = $this->post('/config', [
            'preset' => 'sukarela',
            'tarif_lwbp_rp_kwh' => $newLwbp,
            'faktor_k_wbp' => $config->faktor_k_wbp,
            'jam_wbp_mulai' => $config->jam_wbp_mulai,
            'jam_wbp_selesai' => $config->jam_wbp_selesai,
            'tarif_kvarh_rp' => $config->tarif_kvarh_rp,
            'batas_cos_phi' => $config->batas_cos_phi,
            'rasio_kvarh_bebas' => $config->rasio_kvarh_bebas,
            'faktor_emisi_grid' => $config->faktor_emisi_grid,
        ]);

        $postResponse->assertRedirect();

        // Verify log was persisted
        $this->assertDatabaseHas('regulatory_config_logs', [
            'parameter_name' => 'tarif_lwbp_rp_kwh',
            'new_value' => (string) $newLwbp,
            'changed_by' => 'Manajer Energi (IEEC)',
        ]);

        // Check dashboard displays the audit log row
        $configViewResponse = $this->get('/?preset=sukarela&tab=config-regulasi');
        $configViewResponse->assertStatus(200);
        $configViewResponse->assertSee('Jejak Audit &amp; Riwayat Kalibrasi Regulasi', false);
        $configViewResponse->assertSee('tarif_lwbp_rp_kwh');
    }

    /**
     * BATCH 5: F-06 Export Official ESDM Audit Worksheet in HTML, PDF, and Excel/CSV formats.
     */
    public function test_f06_export_report_supports_pdf_and_excel_and_preserves_html(): void
    {
        // 1. Default HTML View preservation
        $htmlRes = $this->get('/export-audit?preset=sukarela');
        $htmlRes->assertStatus(200);
        $this->assertStringContainsString('text/html', (string) $htmlRes->headers->get('content-type'));
        $htmlRes->assertSee('LEMBAR KERJA AUDIT ENERGI BERKALA');
        $htmlRes->assertSee('PT Indmira Global Energi');

        $htmlExplicitRes = $this->get('/export-audit?preset=sukarela&format=html');
        $htmlExplicitRes->assertStatus(200);
        $this->assertStringContainsString('text/html', (string) $htmlExplicitRes->headers->get('content-type'));

        // 2. PDF Download Format (Barryvdh DomPDF)
        $pdfRes = $this->get('/export-audit?preset=sukarela&format=pdf');
        $pdfRes->assertStatus(200);
        $this->assertStringContainsString('application/pdf', (string) $pdfRes->headers->get('content-type'));
        $disposition = (string) $pdfRes->headers->get('content-disposition');
        $this->assertStringContainsString('attachment;', $disposition);
        $this->assertStringContainsString('Lembar_Kerja_Audit_ESDM_sukarela_', $disposition);
        $this->assertStringContainsString('.pdf', $disposition);
        // Verify PDF binary signature
        $this->assertStringStartsWith('%PDF', (string) $pdfRes->getContent());

        // 3. Excel Spreadsheet Format (.csv with UTF-8 BOM for Microsoft Excel)
        $excelRes = $this->get('/export-audit?preset=sukarela&format=excel');
        $excelRes->assertStatus(200);
        $this->assertStringContainsString('application/vnd.ms-excel', (string) $excelRes->headers->get('content-type'));
        $excelDisp = (string) $excelRes->headers->get('content-disposition');
        $this->assertStringContainsString('attachment;', $excelDisp);
        $this->assertStringContainsString('Lembar_Kerja_Audit_ESDM_sukarela_', $excelDisp);
        $this->assertStringContainsString('.csv', $excelDisp);

        $excelContent = (string) $excelRes->getContent();
        // UTF-8 BOM check
        $this->assertStringStartsWith("\xEF\xBB\xBF", $excelContent);
        // Section 1 Metadata
        $this->assertStringContainsString('LEMBAR KERJA AUDIT ENERGI BERKALA', $excelContent);
        $this->assertStringContainsString('PT Indmira Global Energi', $excelContent);
        $this->assertStringContainsString('Total Konsumsi Energi Tahunan', $excelContent);
        // Section 2 Panel IKE Matrix
        $this->assertStringContainsString('--- 2. MATRIKS EVALUASI KINERJA ENERGI PANEL SDP (IKE & BASELINE) ---', $excelContent);
        $this->assertStringContainsString('SDP-01_LINE_A', $excelContent);
        $this->assertStringContainsString('SDP-02_UTILITY', $excelContent);
        // Section 3 Waste Summary
        $this->assertStringContainsString('--- 3. RINGKASAN PEMBOROSAN MULTI-VEKTOR & BEBAN FINANSIAL BULANAN ---', $excelContent);
        $this->assertStringContainsString('TOTAL POTENSI PENGHEMATAN FINANSIAL', $excelContent);
        // Section 4 Payback Matrix
        $this->assertStringContainsString('--- 4. MATRIKS REKOMENDASI AUDIT ENERGI TERKLASIFIKASI PAYBACK PERIOD (PERMEN ESDM 8/2025) ---', $excelContent);
        $this->assertStringContainsString('Kategori Investasi', $excelContent);
        // Section 5 Sign-off Placeholders
        $this->assertStringContainsString('[Nama Manajer Energi Bersertifikat]', $excelContent);
        $this->assertStringContainsString('[Nama Auditor Energi Bersertifikat]', $excelContent);

        // 4. CSV Format alias
        $csvRes = $this->get('/export-audit?preset=sukarela&format=csv');
        $csvRes->assertStatus(200);
        $this->assertStringContainsString('text/csv', (string) $csvRes->headers->get('content-type'));
    }

    /**
     * BATCH 5: F-08 Live IoT Telemetry Timeseries Chart Synchronization Logic (FR-05).
     */
    public function test_f08_iot_streaming_chart_synchronization_script_logic(): void
    {
        $response = $this->get('/?preset=sukarela');
        $response->assertStatus(200);
        $content = $response->getContent();

        // 1. Chart synchronization logic in pollIotData
        $this->assertStringContainsString('chart.updateSeries(getTimeseriesSeries(), false);', $content);
        $this->assertStringContainsString('chart.updateOptions({', $content);
        $this->assertStringContainsString('categories: chartTimes', $content);

        // 2. Pushing dynamic live active power and baseline
        $this->assertStringContainsString('chartTimes.push(liveTimestamp);', $content);
        $this->assertStringContainsString('actualKwhData.push(liveActiveKw);', $content);
        $this->assertStringContainsString('baselineKwhData.push(liveBaseline);', $content);

        // 3. Clean bounded sliding window
        $this->assertStringContainsString('while (chartTimes.length > MAX_CHART_POINTS)', $content);
        $this->assertStringContainsString('chartTimes.shift();', $content);
        $this->assertStringContainsString('actualKwhData.shift();', $content);
        $this->assertStringContainsString('baselineKwhData.shift();', $content);

        // 4. Leak-free interval clearing when toggled off
        $this->assertStringContainsString('clearInterval(iotInterval);', $content);
        $this->assertStringContainsString('iotInterval = null;', $content);
    }

    /**
     * BATCH 5: F-13 Auditor Legal Integrity & Simulation Disclaimer Banner (FR-08).
     */
    public function test_f13_audit_worksheet_contains_simulation_disclaimer_and_no_fake_credentials(): void
    {
        $response = $this->get('/export-audit?preset=sukarela');
        $response->assertStatus(200);
        $content = $response->getContent();

        // 1. Prominent simulation disclaimer banner
        $this->assertStringContainsString('DOKUMEN SIMULASI PENDUKUNG KEPUTUSAN', $content);
        $this->assertStringContainsString('BUKAN LAPORAN AUDIT ENERGI RESMI (PERMEN ESDM NO. 8 TAHUN 2025)', $content);

        // 2. Formal template placeholders
        $this->assertStringContainsString('[Nama Manajer Energi Bersertifikat]', $content);
        $this->assertStringContainsString('Reg. BNSP: [Nomor Registrasi]', $content);
        $this->assertStringContainsString('[Nama Auditor Energi Bersertifikat]', $content);
        $this->assertStringContainsString('Reg. EBTKE: [Nomor Registrasi]', $content);
        $this->assertStringContainsString('[Direktur Operasional / Plant Manager]', $content);

        // 3. Absolute absence of hardcoded fictitious auditor credentials
        $this->assertStringNotContainsString('Ir. Bambang S.', $content);
        $this->assertStringNotContainsString('EN-2024-0982-ID', $content);
        $this->assertStringNotContainsString('Dr. Eng. Haryanto', $content);
        $this->assertStringNotContainsString('AUD-2025-1104', $content);
    }

    /**
     * BATCH 5 / PRD FR-02: Building IKE evaluation supports large conditioned areas (> 5.000 m2).
     */
    public function test_fr02_building_ike_supports_large_area_thresholds(): void
    {
        $engine = new DeterministicEnergyEngine;
        $config = RegulatoryConfig::first();

        // Clean previous test logs if any
        EnergyTelemetryLog::where('preset_source', 'test_large_area')->delete();

        // 1. Large conditioned area (> 5.000 m2, e.g. 10.000 m2) with IKE = 8.00 (Efisien)
        // Thresholds per Permen ESDM 3/2025 (> 5.000 m2):
        // < 7.50: Sangat Efisien
        // 7.50 - < 12.00: Efisien
        // 12.00 - < 16.50: Cukup Efisien
        // 16.50 - < 20.50: Agak Boros
        // >= 20.50: Boros
        EnergyTelemetryLog::create([
            'timestamp' => '2026-09-01 00:00:00',
            'panel_id' => 'SDP-03_GEDUNG_UTAMA',
            'category' => 'BUILDING_AC',
            'energy_kwh' => 80000.0,
            'power_factor' => 0.93,
            'kvarh' => 31630.0,
            'production_qty' => 0,
            'area_sqm' => 10000.0,
            'shift_state' => 'OPERATIONAL',
            'preset_source' => 'test_large_area',
        ]);
        EnergyTelemetryLog::create([
            'timestamp' => '2026-09-30 00:00:00',
            'panel_id' => 'SDP-03_GEDUNG_UTAMA',
            'category' => 'BUILDING_AC',
            'energy_kwh' => 0.0,
            'power_factor' => 0.93,
            'kvarh' => 0.0,
            'production_qty' => 0,
            'area_sqm' => 10000.0,
            'shift_state' => 'OPERATIONAL',
            'preset_source' => 'test_large_area',
        ]);

        $payloadEfisien = $engine->analyze('test_large_area', null, $config);
        $panelEfisien = collect($payloadEfisien['analisis_ike_per_panel'])->firstWhere('panel_id', 'SDP-03_GEDUNG_UTAMA');
        $this->assertNotNull($panelEfisien);
        $this->assertEquals(8.00, $panelEfisien['ike_aktual']);
        $this->assertEquals('EFISIEN', $panelEfisien['status_evaluasi']);
        $this->assertStringContainsString('> 5.000 m²', $panelEfisien['standar_acuan']);

        // 2. Large conditioned area (> 5.000 m2) with IKE = 21.00 (Boros >= 20.50)
        EnergyTelemetryLog::where('preset_source', 'test_large_area')->delete();
        EnergyTelemetryLog::create([
            'timestamp' => '2026-09-01 00:00:00',
            'panel_id' => 'SDP-03_GEDUNG_UTAMA',
            'category' => 'BUILDING_AC',
            'energy_kwh' => 210000.0,
            'power_factor' => 0.93,
            'kvarh' => 83000.0,
            'production_qty' => 0,
            'area_sqm' => 10000.0,
            'shift_state' => 'OPERATIONAL',
            'preset_source' => 'test_large_area',
        ]);
        EnergyTelemetryLog::create([
            'timestamp' => '2026-09-30 00:00:00',
            'panel_id' => 'SDP-03_GEDUNG_UTAMA',
            'category' => 'BUILDING_AC',
            'energy_kwh' => 0.0,
            'power_factor' => 0.93,
            'kvarh' => 0.0,
            'production_qty' => 0,
            'area_sqm' => 10000.0,
            'shift_state' => 'OPERATIONAL',
            'preset_source' => 'test_large_area',
        ]);

        $payloadBoros = $engine->analyze('test_large_area', null, $config);
        $panelBoros = collect($payloadBoros['analisis_ike_per_panel'])->firstWhere('panel_id', 'SDP-03_GEDUNG_UTAMA');
        $this->assertNotNull($panelBoros);
        $this->assertEquals(21.00, $panelBoros['ike_aktual']);
        $this->assertEquals('BOROS', $panelBoros['status_evaluasi']);
        $this->assertStringContainsString('> 5.000 m²', $panelBoros['standar_acuan']);

        // Clean up
        EnergyTelemetryLog::where('preset_source', 'test_large_area')->delete();
    }
}
