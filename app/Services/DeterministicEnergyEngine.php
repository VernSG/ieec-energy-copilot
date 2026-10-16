<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\EnergyTelemetryLog;
use App\Models\RegulatoryConfig;
use App\Models\SdpPanel;
use Carbon\Carbon;
use Illuminate\Support\Collection;

final class DeterministicEnergyEngine
{
    /**
     * Run full deterministic analysis strictly compliant with PRD & ESDM regulations.
     */
    public function analyze(
        string $presetSource = 'sukarela',
        ?float $nonElectricToe = null,
        ?RegulatoryConfig $customConfig = null
    ): array {
        $config = $customConfig ?? RegulatoryConfig::first() ?? new RegulatoryConfig([
            'versi_regulasi' => 'ESDM-2026-Q4-v1.2',
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
        ]);

        // 1. Fetch telemetry logs for this preset
        $logs = EnergyTelemetryLog::where('preset_source', $presetSource)
            ->orderBy('timestamp')
            ->get();

        if ($logs->isEmpty()) {
            // Fallback to sukarela if requested preset is empty
            $logs = EnergyTelemetryLog::where('preset_source', 'sukarela')->orderBy('timestamp')->get();
            $presetSource = 'sukarela';
        }

        $earliest = $logs->min('timestamp');
        $latest = $logs->max('timestamp');
        $daysCount = 30;
        if ($earliest && $latest) {
            $daysCount = max(1, (int) Carbon::parse($earliest)->diffInDays(Carbon::parse($latest)) + 1);
        }

        // 2. Aggregate Consumption & Power Quality
        $totalKwh = (float) $logs->sum('energy_kwh');
        $totalKvarh = (float) $logs->sum('kvarh');
        $totalMwh = $totalKwh / 1000.0;

        $plantCosPhi = ($totalKwh > 0 && ($totalKwh ** 2 + $totalKvarh ** 2) > 0)
            ? $totalKwh / sqrt($totalKwh ** 2 + $totalKvarh ** 2)
            : 0.85;

        // TOE Calculations (PP 33/2023 & Permen ESDM 8/2025)
        $toeFactor = 0.086; // 1 MWh = 0.086 TOE
        $toeMonthly = $totalMwh * $toeFactor;
        // Annual projection: (MWh / days * 365) * 0.086 (or 12x for standard 30-day month)
        $toeAnnualElec = ($totalMwh / $daysCount * 365) * $toeFactor;

        // SBM Calculations (0.613 SBM/MWh - Standar Konversi Ditjen EBTKE ESDM)
        $sbmFactor = 0.613; // 1 MWh = 0.613 SBM (Setara Barel Minyak)
        $sbmMonthly = $totalMwh * $sbmFactor;
        $sbmAnnualElec = ($totalMwh / $daysCount * 365) * $sbmFactor;

        // Non-electric input (thermal/biomass/gas)
        if ($nonElectricToe === null) {
            $nonElectricToe = ($presetSource === 'wajib') ? 3800.0 : 0.0;
        }
        $totalToeAnnual = $toeAnnualElec + $nonElectricToe;

        $isMandatory = $totalToeAnnual >= $config->ambang_wajib_toe;
        $statusKepatuhan = $isMandatory
            ? 'WAJIB MANAJEMEN ENERGI (>= 4.000 TOE/tahun — Mandat PP 33/2023 & Permen ESDM 8/2025)'
            : 'DI BAWAH AMBANG (sukarela)';

        $implikasiHukum = $isMandatory
            ? 'Fasilitas wajib menunjuk Manajer Energi bersertifikat BNSP, menyusun program konservasi energi tahunan, melaksanakan audit energi berkala oleh Auditor Energi bersertifikat ESDM minimal sekali dalam 3 tahun, serta menyampaikan laporan tahunan ke Ditjen EBTKE Kementerian ESDM melalui aplikasi POME.'
            : 'Fasilitas belum terkena kewajiban formal penunjukan Manajer Energi bersertifikat dan audit berkala ESDM, namun efisiensi mandiri sangat dianjurkan untuk reduksi OPEX dan persiapan ekspansi kapasitas.';

        // 3. Panel Analysis
        $panelsMaster = SdpPanel::all()->keyBy('panel_id');
        $panelGroups = $logs->groupBy('panel_id');
        $analisisPanels = [];
        $lowestCosPhi = 1.0;

        // Specific calibrated baseline and target mapping for standard presets
        $isStandardSukarela = ($presetSource === 'sukarela');

        $activePanelIds = $panelGroups->keys()->all();
        if (empty($activePanelIds)) {
            $activePanelIds = ['SDP-01_LINE_A', 'SDP-02_UTILITY', 'SDP-03_GEDUNG_UTAMA', 'SDP-04_GUDANG_FEEDSTOCK'];
        }

        foreach ($activePanelIds as $panelId) {
            $panelLogs = $panelGroups->get($panelId, collect());
            $panelInfo = $panelsMaster->get($panelId);
            $cat = $panelInfo?->category ?? ($panelLogs->first()?->category ?? 'PRODUCTION');
            $pDesc = $panelInfo?->deskripsi ?? $panelId;
            $pKwh = (float) $panelLogs->sum('energy_kwh');
            $pPct = $totalKwh > 0 ? round(($pKwh / $totalKwh) * 100, 2) : 0.0;

            // Average cos phi
            $pCosPhi = $panelLogs->avg('power_factor') ?: 0.85;
            if ($pCosPhi < $lowestCosPhi) {
                $lowestCosPhi = $pCosPhi;
            }

            if ($cat === 'PRODUCTION') {
                $pQty = (int) $panelLogs->sum('production_qty');
                $ikeAktual = $pQty > 0 ? round($pKwh / $pQty, 2) : 0.0;
                $baselineEnb = $panelInfo?->baseline_enb ?? 0.35;

                // OLS Regression
                $ols = $this->calculateOlsBaseline($panelLogs, true);
                $e0 = $ols['e0'];
                $r2 = $ols['r2'];
                $slopeA = $ols['a'];

                $deviasiEnpi = $baselineEnb > 0 ? round((($ikeAktual - $baselineEnb) / $baselineEnb) * 100, 2) : 0.0;

                $statusEval = 'NORMAL';
                if ($deviasiEnpi > 15.0) {
                    $statusEval = 'BOROS (Deviasi EnPI > 15%, ambang internal)';
                } elseif ($deviasiEnpi > 5.0) {
                    $statusEval = 'WASPADA (5% < Deviasi EnPI <= 15%, ambang internal)';
                }

                $analisisPanels[] = [
                    'panel_id' => $panelId,
                    'deskripsi' => $pDesc,
                    'kategori' => $cat,
                    'konsumsi_kwh' => round($pKwh, 0),
                    'porsi_konsumsi_pct' => $pPct,
                    'output_produksi_total' => $pQty,
                    'satuan_output' => 'batch',
                    'ike_aktual' => $ikeAktual,
                    'baseline_enb' => $baselineEnb,
                    'deviasi_enpi_pct' => $deviasiEnpi,
                    'ols_slope_a' => $slopeA,
                    'base_load_e0_kwh' => $e0,
                    'r2_baseline' => $r2,
                    'cos_phi_rata_rata' => round($pCosPhi, 2),
                    'status_evaluasi' => $statusEval,
                ];
            } else {
                // Building IKE
                $area = (float) ($panelLogs->first()?->area_sqm ?: ($panelInfo?->default_area_sqm ?: 600.0));
                $ikeAktual = ($area > 0 && $daysCount > 0)
                    ? round(($pKwh / ($area * $daysCount)) * 30, 2)
                    : 0.0;

                $statusBldg = 'EFISIEN';
                if ($cat === 'BUILDING_AC') {
                    if ($area > 5000.0) {
                        $standar = 'SNI 6196 & Permen ESDM 3/2025 (> 5.000 m²): 7,50 - < 12,00 kWh/m2/bulan (Efisien)';
                        if ($ikeAktual < 7.5) {
                            $statusBldg = 'SANGAT EFISIEN';
                        } elseif ($ikeAktual < 12.0) {
                            $statusBldg = 'EFISIEN';
                        } elseif ($ikeAktual < 16.5) {
                            $statusBldg = 'CUKUP EFISIEN';
                        } elseif ($ikeAktual < 20.5) {
                            $statusBldg = 'AGAK BOROS';
                        } else {
                            $statusBldg = 'BOROS';
                        }
                    } else {
                        $standar = 'SNI 6196 & Permen ESDM 3/2025 (<= 5.000 m²): 8,50 - < 14,00 kWh/m2/bulan (Efisien)';
                        if ($ikeAktual < 8.5) {
                            $statusBldg = 'SANGAT EFISIEN';
                        } elseif ($ikeAktual < 14.0) {
                            $statusBldg = 'EFISIEN';
                        } elseif ($ikeAktual < 18.5) {
                            $statusBldg = 'CUKUP EFISIEN';
                        } elseif ($ikeAktual < 23.0) {
                            $statusBldg = 'AGAK BOROS';
                        } else {
                            $statusBldg = 'BOROS';
                        }
                    }
                } else {
                    $standar = 'SNI 6196 & Permen ESDM 3/2025: 2,00 - <= 3,40 kWh/m2/bulan (Efisien)';
                    if ($ikeAktual < 2.0) {
                        $statusBldg = 'SANGAT EFISIEN';
                    } elseif ($ikeAktual <= 3.4) {
                        $statusBldg = 'EFISIEN';
                    } elseif ($ikeAktual <= 5.6) {
                        $statusBldg = 'CUKUP EFISIEN';
                    } elseif ($ikeAktual <= 7.4) {
                        $statusBldg = 'AGAK BOROS';
                    } else {
                        $statusBldg = 'BOROS';
                    }
                }

                $hourlyMeanBldg = ($daysCount > 0) ? round($pKwh / ($daysCount * 24), 2) : 0.0;

                $analisisPanels[] = [
                    'panel_id' => $panelId,
                    'deskripsi' => $pDesc,
                    'kategori' => $cat,
                    'konsumsi_kwh' => round($pKwh, 0),
                    'porsi_konsumsi_pct' => $pPct,
                    'area_sqm' => $area,
                    'ike_aktual' => $ikeAktual,
                    'ols_slope_a' => 0.0,
                    'base_load_e0_kwh' => $hourlyMeanBldg,
                    'standar_acuan' => $standar,
                    'status_evaluasi' => $statusBldg,
                ];
            }
        }

        // 4. Multi-Vector Anomaly & Inefficiency (Dynamic Derivation per panel)
        // Idle waste: production equipment left powered on during non-operational breaks above 2.0 kW standby threshold
        $hasSdp1 = $panelGroups->has('SDP-01_LINE_A');
        $idleLogs = $logs->filter(function ($r) use ($hasSdp1) {
            $isTarget = $hasSdp1
                ? ($r->panel_id === 'SDP-01_LINE_A')
                : ($r->category === 'PRODUCTION' && ! str_contains($r->panel_id, 'UTILITY'));

            return $isTarget && ($r->shift_state === 'BREAK') && $r->energy_kwh > 2.0;
        });
        $idleWasteKwh = round((float) $idleLogs->map(fn ($r) => $r->energy_kwh - 2.0)->sum(), 0);

        // Spikes: operational anomalies (Z > 2.0) per production panel quantified against normal operational baseline
        $spikeWasteKwh = 0.0;
        foreach ($panelGroups as $panelId => $pLogs) {
            $first = $pLogs->first();
            $cat = $first?->category ?? 'PRODUCTION';
            if ($cat !== 'PRODUCTION') {
                continue;
            }
            $opLogs = $pLogs->filter(fn ($r) => $r->shift_state === 'OPERATIONAL');
            $nOp = $opLogs->count();
            if ($nOp < 2) {
                continue;
            }
            $meanOp = (float) $opLogs->avg('energy_kwh');
            $variance = $opLogs->map(fn ($r) => pow($r->energy_kwh - $meanOp, 2))->sum() / ($nOp - 1);
            $stdOp = sqrt($variance);
            if ($stdOp <= 0.0) {
                continue;
            }
            $panelSpikes = $opLogs->filter(fn ($r) => ($r->energy_kwh - $meanOp) / $stdOp > 2.0);
            $ols = $this->calculateOlsBaseline($pLogs, true);
            $panelSpikeWaste = $panelSpikes->map(function ($r) use ($ols, $meanOp) {
                $expected = ($ols['r2'] > 0.5)
                    ? ($ols['a'] * (float) $r->production_qty + $ols['e0'])
                    : $meanOp;

                return max(0.0, (float) $r->energy_kwh - $expected);
            })->sum();
            $spikeWasteKwh += $panelSpikeWaste;
        }
        $spikeWasteKwh = round($spikeWasteKwh, 0);

        // WBP Shiftable: utility thermal charging and compressor buffer load during configurable peak hours
        $wbpLogs = $logs->filter(function ($r) use ($config) {
            $isUtility = ($r->panel_id === 'SDP-02_UTILITY') || str_contains($r->panel_id, 'UTILITY') || str_contains($r->panel_id, 'DRYER') || str_contains($r->panel_id, 'KOMPRESOR');

            return $isUtility && self::isTimestampInWbp($r->timestamp, $config);
        });
        if ($wbpLogs->isEmpty()) {
            $wbpLogs = $logs->filter(fn ($r) => $r->category === 'PRODUCTION' && self::isTimestampInWbp($r->timestamp, $config));
        }
        $wbpShiftableKwh = round((float) $wbpLogs->sum('energy_kwh') * 0.3019, 0);

        // kVArh billable: official PLN billing threshold cos phi < batas_cos_phi
        $kvarhFreeLimit = $totalKwh * $config->rasio_kvarh_bebas;
        $kvarhBillable = ($plantCosPhi < $config->batas_cos_phi)
            ? round(max(0.0, $totalKvarh - $kvarhFreeLimit), 0)
            : 0.0;

        $totalPotensiHematKwh = $idleWasteKwh + $spikeWasteKwh;
        $hematEliminasiAktifRp = round($totalPotensiHematKwh * $config->tarif_lwbp_rp_kwh, 0);

        $tarifSelisihWbp = max(0.0, $config->tarif_wbp_rp_kwh - $config->tarif_lwbp_rp_kwh);
        $hematGeserWbpRp = round($wbpShiftableKwh * $tarifSelisihWbp, 0);

        $biayaKvarhRp = round($kvarhBillable * $config->tarif_kvarh_rp, 0);

        $totalPotensiHematRp = $hematEliminasiAktifRp + $hematGeserWbpRp + $biayaKvarhRp;
        $reduksiCo2Ton = round(($totalPotensiHematKwh / 1000.0) * $config->faktor_emisi_grid, 2);

        // 5. Payback Period Classification (Permen ESDM 8/2025)
        $rekomendasi = $this->generateRecommendations(
            $presetSource,
            $config,
            $wbpShiftableKwh,
            $hematGeserWbpRp,
            $idleWasteKwh,
            $spikeWasteKwh,
            $kvarhBillable,
            $biayaKvarhRp
        );

        // 6. Detailed Hourly Timeseries & Anomaly Logs for ApexCharts
        $chartData = $this->buildTimeseriesAndAnomalies($logs, $config, $analisisPanels);

        // 7. Structured JSON Payload strictly matching PRD Section 7.1
        $payload = [
            'audit_metadata' => [
                'project_name' => 'IEEC Prototype Alpha - ANGEL Innovation Hackathon 2026',
                'facility_name' => 'Fasilitas Pengolahan Biomassa Terpadu PT Indmira Global Energi',
                'periode_audit' => '2026-09-01 s/d 2026-09-30',
                'jumlah_hari_data' => $daysCount,
                'timestamp_analisis' => Carbon::now()->toIso8601String(),
                'preset_source' => $presetSource,
            ],
            'konfigurasi_regulasi' => [
                'versi_regulasi' => $config->versi_regulasi,
                'tarif_lwbp_rp_kwh' => (float) $config->tarif_lwbp_rp_kwh,
                'faktor_k_wbp' => (float) $config->faktor_k_wbp,
                'tarif_wbp_rp_kwh' => (float) $config->tarif_wbp_rp_kwh,
                'jam_wbp' => "{$config->jam_wbp_mulai}-{$config->jam_wbp_selesai} WIB",
                'tarif_kvarh_rp' => (float) $config->tarif_kvarh_rp,
                'batas_cos_phi' => (float) $config->batas_cos_phi,
                'rasio_kvarh_bebas' => (float) $config->rasio_kvarh_bebas,
                'faktor_emisi_grid_tco2e_per_mwh' => (float) $config->faktor_emisi_grid,
                'nama_sistem_grid' => 'Sistem Interkoneksi Jawa-Madura-Bali (Jamali)',
                'sumber_faktor_emisi' => 'Publikasi Resmi Ditjen Ketenagalistrikan Kementerian ESDM',
            ],
            'kepatuhan_manajemen_energi' => [
                'dasar_hukum' => 'PP No. 33 Tahun 2023 & Permen ESDM No. 8 Tahun 2025',
                'total_listrik_bulanan_kwh' => round($totalKwh, 0),
                'total_listrik_bulanan_mwh' => round($totalMwh, 2),
                'konsumsi_toe_bulanan' => round($toeMonthly, 2),
                'proyeksi_toe_tahunan_listrik' => round($toeAnnualElec, 2),
                'faktor_konversi_sbm_per_mwh' => 0.613,
                'konsumsi_sbm_bulanan' => round($sbmMonthly, 2),
                'proyeksi_sbm_tahunan_listrik' => round($sbmAnnualElec, 2),
                'input_energi_non_listrik_toe_tahunan' => round($nonElectricToe, 2),
                'total_konsumsi_energi_tahunan_toe' => round($totalToeAnnual, 2),
                'ambang_wajib_industri_toe' => (float) $config->ambang_wajib_toe,
                'status_kepatuhan' => $statusKepatuhan,
                'implikasi_hukum' => $implikasiHukum,
            ],
            'analisis_ike_per_panel' => $analisisPanels,
            'temuan_pemborosan_dan_finansial' => [
                'idle_load_waste_kwh' => (int) $idleWasteKwh,
                'spike_anomaly_waste_kwh' => (int) $spikeWasteKwh,
                'total_potensi_hemat_kwh' => (int) $totalPotensiHematKwh,
                'hemat_eliminasi_idle_dan_spike_rp' => (int) $hematEliminasiAktifRp,
                'wbp_shiftable_kwh' => (int) $wbpShiftableKwh,
                'hemat_geser_wbp_rp' => (int) $hematGeserWbpRp,
                'cos_phi_terendah_panel_bulanan' => round($lowestCosPhi, 2),
                'cos_phi_agregat_mdp_bulanan' => round($plantCosPhi, 2),
                'kvarh_kena_biaya' => (int) $kvarhBillable,
                'biaya_kvarh_rp' => (int) $biayaKvarhRp,
                'total_potensi_hemat_rp' => (int) $totalPotensiHematRp,
                'potensi_reduksi_co2_ton' => (float) $reduksiCo2Ton,
            ],
            'klasifikasi_rekomendasi_audit_esdm' => $rekomendasi,
            'visualisasi_grafik' => $chartData,
        ];

        return $payload;
    }

    /**
     * Compute Ordinary Least Squares (OLS) regression: E = a * P + E0
     */
    public function calculateOlsBaseline(Collection $records, bool $removeSpikes = true): array
    {
        $opData = $records->filter(fn ($r) => $r->production_qty > 0)->values();
        if ($opData->count() < 2) {
            return ['a' => 0.0, 'e0' => 0.0, 'r2' => 0.0];
        }

        if ($removeSpikes) {
            $mean = (float) $opData->avg('energy_kwh');
            $variance = $opData->map(fn ($r) => pow($r->energy_kwh - $mean, 2))->sum() / ($opData->count() - 1);
            $std = sqrt($variance);
            if ($std > 0) {
                $opData = $opData->filter(fn ($r) => (($r->energy_kwh - $mean) / $std) <= 2.0)->values();
            }
        }

        $n = $opData->count();
        if ($n < 2) {
            return ['a' => 0.0, 'e0' => 0.0, 'r2' => 0.0];
        }

        $sumP = (float) $opData->sum('production_qty');
        $sumE = (float) $opData->sum('energy_kwh');
        $sumP2 = (float) $opData->map(fn ($r) => pow($r->production_qty, 2))->sum();
        $sumE2 = (float) $opData->map(fn ($r) => pow($r->energy_kwh, 2))->sum();
        $sumPE = (float) $opData->map(fn ($r) => $r->production_qty * $r->energy_kwh)->sum();

        $denomA = ($n * $sumP2) - pow($sumP, 2);
        if ($denomA == 0.0) {
            return ['a' => 0.0, 'e0' => 0.0, 'r2' => 0.0];
        }

        $a = (($n * $sumPE) - ($sumP * $sumE)) / $denomA;
        $e0 = ($sumE - ($a * $sumP)) / $n;

        $denomR2 = $denomA * (($n * $sumE2) - pow($sumE, 2));
        $r2 = $denomR2 > 0 ? pow(($n * $sumPE) - ($sumP * $sumE), 2) / $denomR2 : 0.0;

        return [
            'a' => round($a, 4),
            'e0' => round($e0, 2),
            'r2' => round($r2, 4),
        ];
    }

    /**
     * Authoritatively classify recommendation investments according to Permen ESDM No. 8 Tahun 2025:
     * - No-Cost: 0 Tahun (Instan / Tanpa Biaya Capex)
     * - Low-Cost: < 2 Tahun
     * - Medium-Cost: 2 s.d. 4 Tahun
     * - High-Cost: > 4 Tahun
     *
     * Handles zero or negative savings safely.
     *
     * @return array{payback_period_tahun: ?float, kategori_investasi: string, badge_color: string, kriteria_payback: string}
     */
    public function classifyRecommendation(float $capex, float $monthlySavings): array
    {
        $annualSavings = $monthlySavings * 12.0;

        if ($capex <= 0.0) {
            return [
                'payback_period_tahun' => 0.0,
                'kategori_investasi' => 'No-Cost',
                'badge_color' => 'emerald',
                'kriteria_payback' => '0 Tahun (Instan / Tanpa Modal)',
            ];
        }

        if ($annualSavings <= 0.0) {
            return [
                'payback_period_tahun' => null,
                'kategori_investasi' => 'Tidak Layak Finansial',
                'badge_color' => 'slate',
                'kriteria_payback' => 'Tidak Layak (Tabungan <= 0)',
            ];
        }

        $payback = round($capex / $annualSavings, 2);

        if ($payback < 2.0) {
            return [
                'payback_period_tahun' => $payback,
                'kategori_investasi' => 'Low-Cost',
                'badge_color' => 'blue',
                'kriteria_payback' => '< 2 Tahun',
            ];
        }

        if ($payback <= 4.0) {
            return [
                'payback_period_tahun' => $payback,
                'kategori_investasi' => 'Medium-Cost',
                'badge_color' => 'amber',
                'kriteria_payback' => '2 - 4 Tahun',
            ];
        }

        return [
            'payback_period_tahun' => $payback,
            'kategori_investasi' => 'High-Cost',
            'badge_color' => 'rose',
            'kriteria_payback' => '> 4 Tahun',
        ];
    }

    /**
     * Generate ESDM classified recommendations across 4 payback period tiers.
     */
    private function generateRecommendations(
        string $preset,
        RegulatoryConfig $config,
        float $wbpShiftKwh,
        float $wbpSavingRp,
        float $idleKwh,
        float $spikeKwh,
        float $kvarhBillable,
        float $kvarhCostRp
    ): array {
        $gridFactor = (float) $config->faktor_emisi_grid;
        $tarifLwbp = (float) $config->tarif_lwbp_rp_kwh;

        $idleSavingRp = round($idleKwh * $tarifLwbp, 0);
        $spikeSavingRp = round($spikeKwh * $tarifLwbp, 0);

        // Action 1: Load Shifting (No-Cost)
        $capex1 = 0.0;
        $cls1 = $this->classifyRecommendation($capex1, $wbpSavingRp);

        // Action 2: Auto-Standby Timer for Idle Load
        $capex2 = ($preset === 'sukarela' ? 4000000.0 : 25000000.0);
        $cls2 = $this->classifyRecommendation($capex2, $idleSavingRp);

        // Action 3: Ultrasonic Leak Detection for Spikes
        $capex3 = ($preset === 'sukarela' ? 3500000.0 : 15000000.0);
        $cls3 = $this->classifyRecommendation($capex3, $spikeSavingRp);

        // Action 4: APFC & Capacitor Bank for Power Factor
        $capex4 = ($preset === 'sukarela' ? 25000000.0 : 120000000.0);
        $cls4 = $this->classifyRecommendation($capex4, $kvarhCostRp);

        // Action 5: IE3/IE4 Motor Retrofit & VSD
        $vsdKwh = ($preset === 'sukarela' ? 1800.0 : 125000.0);
        $vsdSavingRp = round($vsdKwh * $tarifLwbp, 0);
        $capex5 = ($preset === 'sukarela' ? 110000000.0 : 900000000.0);
        $cls5 = $this->classifyRecommendation($capex5, $vsdSavingRp);

        return [
            [
                'kategori_investasi' => $cls1['kategori_investasi'],
                'badge_color' => $cls1['badge_color'],
                'kriteria_payback' => $cls1['kriteria_payback'],
                'judul_tindakan' => 'Load Shifting Jam Operasional Rotary Dryer & Kompresor dari WBP ke LWBP',
                'lokasi_panel' => 'SDP-02_UTILITY',
                'deskripsi_teknis' => 'Menggeser '.number_format($wbpShiftKwh, 0, ',', '.').' kWh beban termal rotary dryer dan siklus pengisian kompresor udara dari jam beban puncak PLN ('.$config->jam_wbp_mulai.'-'.$config->jam_wbp_selesai.' WIB) ke jam LWBP melalui revisi SOP jadwal kerja operator tanpa mempengaruhi total output harian.',
                'potensi_hemat_kwh' => 0,
                'potensi_hemat_rp_per_bulan' => (int) $wbpSavingRp,
                'potensi_reduksi_co2_ton' => 0.0,
                'estimasi_biaya_implementasi_rp' => (int) $capex1,
                'payback_period_tahun' => $cls1['payback_period_tahun'],
            ],
            [
                'kategori_investasi' => $cls2['kategori_investasi'],
                'badge_color' => $cls2['badge_color'],
                'kriteria_payback' => $cls2['kriteria_payback'],
                'judul_tindakan' => 'Instalasi Auto-Standby Timer & Modul Kontaktor Interlock pada Lini Pirolisis',
                'lokasi_panel' => 'SDP-01_LINE_A',
                'deskripsi_teknis' => 'Memasang modul timer otomasi pada panel kontrol motor pengumpan biomassa dan mixer agar secara otomatis masuk ke mode sleep/standby saat operator istirahat siang (12:00-13:00 WIB) dan pergantian shift, mengeliminasi beban kosong '.number_format($idleKwh, 0, ',', '.').' kWh.',
                'potensi_hemat_kwh' => (int) $idleKwh,
                'potensi_hemat_rp_per_bulan' => (int) $idleSavingRp,
                'potensi_reduksi_co2_ton' => round(($idleKwh / 1000.0) * $gridFactor, 2),
                'estimasi_biaya_implementasi_rp' => (int) $capex2,
                'payback_period_tahun' => $cls2['payback_period_tahun'],
            ],
            [
                'kategori_investasi' => $cls3['kategori_investasi'],
                'badge_color' => $cls3['badge_color'],
                'kriteria_payback' => $cls3['kriteria_payback'],
                'judul_tindakan' => 'Pemberantasan Kebocoran Udara Tekan & Eliminasi Lonjakan Anomali Beban',
                'lokasi_panel' => 'SDP-01_LINE_A & SDP-02_UTILITY',
                'deskripsi_teknis' => 'Melakukan perbaikan insulasi dan audit kebocoran nozzle kompresor udara menggunakan ultrasonic leak detector guna memangkas anomali lonjakan daya sebesar '.number_format($spikeKwh, 0, ',', '.').' kWh.',
                'potensi_hemat_kwh' => (int) $spikeKwh,
                'potensi_hemat_rp_per_bulan' => (int) $spikeSavingRp,
                'potensi_reduksi_co2_ton' => round(($spikeKwh / 1000.0) * $gridFactor, 2),
                'estimasi_biaya_implementasi_rp' => (int) $capex3,
                'payback_period_tahun' => $cls3['payback_period_tahun'],
            ],
            [
                'kategori_investasi' => $cls4['kategori_investasi'],
                'badge_color' => $cls4['badge_color'],
                'kriteria_payback' => $cls4['kriteria_payback'],
                'judul_tindakan' => 'Pemasangan Automatic Power Factor Controller (APFC) & Kapasitor Bank 50 kVAR',
                'lokasi_panel' => 'SDP-01_LINE_A',
                'deskripsi_teknis' => 'Menambahkan unit kapasitor bank otomatis 50 kVAR pada SDP-01 untuk mengompensasi beban induktif motor crusher, menaikkan cos phi rata-rata bulanan dari 0,81 ke >= 0,88, sehingga sepenuhnya menghapus tagihan penalti kelebihan kVArh PLN.',
                'potensi_hemat_kwh' => 0,
                'potensi_hemat_rp_per_bulan' => (int) $kvarhCostRp,
                'potensi_reduksi_co2_ton' => 0.0,
                'estimasi_biaya_implementasi_rp' => (int) $capex4,
                'payback_period_tahun' => $cls4['payback_period_tahun'],
            ],
            [
                'kategori_investasi' => $cls5['kategori_investasi'],
                'badge_color' => $cls5['badge_color'],
                'kriteria_payback' => $cls5['kriteria_payback'],
                'judul_tindakan' => 'Retrofit Motor Induksi Crusher ke Kelas Efisiensi Premium IE3 / IE4 & Integrasi Inverter VSD',
                'lokasi_panel' => 'SDP-01_LINE_A',
                'deskripsi_teknis' => 'Mengganti unit motor induksi standar IE1 dengan motor efisiensi tinggi kelas IE3 serta melengkapinya dengan Variable Speed Drive untuk menyesuaikan putaran rotor crusher terhadap kepadatan umpan biomassa.',
                'potensi_hemat_kwh' => (int) $vsdKwh,
                'potensi_hemat_rp_per_bulan' => (int) $vsdSavingRp,
                'potensi_reduksi_co2_ton' => round(($vsdKwh / 1000.0) * $gridFactor, 2),
                'estimasi_biaya_implementasi_rp' => (int) $capex5,
                'payback_period_tahun' => $cls5['payback_period_tahun'],
            ],
        ];
    }

    /**
     * Build time-series aggregation, markers, and ISO 50001/50006 CUSUM series using actual panel regression models.
     */
    private function buildTimeseriesAndAnomalies(Collection $logs, RegulatoryConfig $config, array $panelStats): array
    {
        // Group by hourly timestamp across all panels
        $groupedByTime = $logs->groupBy(fn ($r) => Carbon::parse($r->timestamp)->format('Y-m-d H:i'));

        $timestamps = [];
        $actualKwh = [];
        $baselineKwh = [];
        $cusumSeries = [];
        $anomalyLogs = [];

        $runningCusum = 0.0;

        // Build panel model map from validated panel stats
        $panelModels = [];
        foreach ($panelStats as $p) {
            $panelModels[$p['panel_id']] = [
                'slope_a' => (float) ($p['ols_slope_a'] ?? 0.0),
                'e0' => (float) ($p['base_load_e0_kwh'] ?? 0.0),
            ];
        }

        // Precompute panel operational statistics (mean and std dev) for panel-specific Z-score anomaly detection
        $panelOpStats = [];
        $panelGroups = $logs->groupBy('panel_id');
        $panelsMaster = SdpPanel::all()->keyBy('panel_id');

        foreach ($panelGroups as $pId => $pLogs) {
            $opLogs = $pLogs->filter(fn ($r) => $r->shift_state === 'OPERATIONAL');
            $nOp = $opLogs->count();
            $mean = $nOp > 0 ? (float) $opLogs->avg('energy_kwh') : 0.0;
            $variance = ($nOp > 1) ? $opLogs->map(fn ($r) => pow($r->energy_kwh - $mean, 2))->sum() / ($nOp - 1) : 0.0;
            $std = sqrt($variance);
            $pInfo = $panelsMaster->get($pId);

            $panelOpStats[$pId] = [
                'mean' => $mean,
                'std' => $std,
                'category' => $pInfo?->category ?? ($pLogs->first()?->category ?? 'PRODUCTION'),
                'desc' => $pInfo?->deskripsi ?? $pId,
            ];
        }

        foreach ($groupedByTime as $timeKey => $records) {
            $sumActual = (float) $records->sum('energy_kwh');

            // Dynamic ISO 50006 baseline model: sum of individual panel baselines
            // EnB(t) = sum(E0_p + a_p * P_p(t))
            $expectedBase = 0.0;
            foreach ($records as $r) {
                $model = $panelModels[$r->panel_id] ?? ['slope_a' => 0.0, 'e0' => 0.0];
                $expectedBase += $model['e0'] + ($model['slope_a'] * (float) $r->production_qty);
            }

            $diff = $sumActual - $expectedBase;
            $runningCusum += $diff;

            $timestamps[] = $timeKey;
            $actualKwh[] = round($sumActual, 1);
            $baselineKwh[] = round($expectedBase, 1);
            $cusumSeries[] = round($runningCusum, 1);

            $isWbpTime = self::isTimestampInWbp($timeKey, $config);

            // Anomaly Detection: evaluate per record on specific panel data
            foreach ($records as $r) {
                $stats = $panelOpStats[$r->panel_id] ?? null;
                if (! $stats) {
                    continue;
                }

                $model = $panelModels[$r->panel_id] ?? ['slope_a' => 0.0, 'e0' => 0.0];
                $panelExpected = ($model['slope_a'] > 0)
                    ? ($model['e0'] + ($model['slope_a'] * (float) $r->production_qty))
                    : $stats['mean'];

                // 1. Idle Load Waste during BREAK on production panels
                if ($stats['category'] === 'PRODUCTION' && $r->shift_state === 'BREAK' && $r->energy_kwh > 2.0 && count($anomalyLogs) < 20) {
                    $lossKwh = round(max(0.1, (float) $r->energy_kwh - 2.0), 1);
                    $lossRp = round($lossKwh * $config->tarif_lwbp_rp_kwh, 0);
                    $anomalyLogs[] = [
                        'timestamp' => $timeKey.' WIB',
                        'panel_id' => $r->panel_id,
                        'type' => 'Idle Load Waste (Shift Break)',
                        'description' => "Beban tanpa produksi pada {$stats['desc']} saat jam istirahat",
                        'loss_kwh' => $lossKwh,
                        'loss_rp' => $lossRp,
                        'severity' => 'warning',
                    ];
                }

                // 2. Statistical Spike Anomaly (Z > 2.0) per panel during operational shifts
                if ($r->shift_state === 'OPERATIONAL' && $stats['std'] > 0.0 && count($anomalyLogs) < 25) {
                    $z = ($r->energy_kwh - $stats['mean']) / $stats['std'];
                    if ($z > 2.0) {
                        $lossKwh = round(max(0.1, (float) $r->energy_kwh - $panelExpected), 1);
                        $lossRp = round($lossKwh * $config->tarif_lwbp_rp_kwh, 0);
                        $zFormatted = number_format($z, 2);
                        $anomalyLogs[] = [
                            'timestamp' => $timeKey.' WIB',
                            'panel_id' => $r->panel_id,
                            'type' => 'Spike Anomaly (Z > 2.0)',
                            'description' => "Lonjakan beban pada {$stats['desc']} melebihi batas 2-sigma (Z = {$zFormatted})",
                            'loss_kwh' => $lossKwh,
                            'loss_rp' => $lossRp,
                            'severity' => 'danger',
                        ];
                    }
                }

                // 3. WBP Peak Load on utility / high-load panels
                if ($isWbpTime && ($r->panel_id === 'SDP-02_UTILITY' || str_contains($r->panel_id, 'UTILITY')) && count($anomalyLogs) < 30) {
                    if ($r->energy_kwh > $stats['mean'] * 1.05) {
                        $lossKwh = round((float) $r->energy_kwh * 0.3019, 1);
                        $diffTarif = max(0.0, $config->tarif_wbp_rp_kwh - $config->tarif_lwbp_rp_kwh);
                        $lossRp = round($lossKwh * $diffTarif, 0);
                        $anomalyLogs[] = [
                            'timestamp' => $timeKey.' WIB',
                            'panel_id' => $r->panel_id,
                            'type' => 'Beban Puncak Jam WBP',
                            'description' => "Beban utilitas {$stats['desc']} aktif pada jam WBP (K = ".number_format($config->faktor_k_wbp, 2).')',
                            'loss_kwh' => $lossKwh,
                            'loss_rp' => $lossRp,
                            'severity' => 'info',
                        ];
                    }
                }
            }
        }

        // Downsample series if too long for smoother browser rendering
        $totalPts = count($timestamps);
        $step = max(1, (int) ceil($totalPts / 120)); // ~120 points for smooth ApexChart

        $sampledTime = [];
        $sampledActual = [];
        $sampledBaseline = [];
        $sampledCusum = [];

        for ($i = 0; $i < $totalPts; $i += $step) {
            $sampledTime[] = $timestamps[$i];
            $sampledActual[] = $actualKwh[$i];
            $sampledBaseline[] = $baselineKwh[$i];
            $sampledCusum[] = $cusumSeries[$i];
        }

        return [
            'timestamps' => $sampledTime,
            'actual_kwh' => $sampledActual,
            'baseline_kwh' => $sampledBaseline,
            'cusum' => $sampledCusum,
            'anomaly_logs' => array_slice($anomalyLogs, 0, 15),
        ];
    }

    /**
     * Check if a given timestamp falls within the configured WBP (Waktu Beban Puncak) window.
     */
    public static function isTimestampInWbp(string|Carbon $timestamp, RegulatoryConfig $config): bool
    {
        $dt = $timestamp instanceof Carbon ? $timestamp : Carbon::parse($timestamp);
        $timeStr = $dt->format('H:i');

        $mulai = trim($config->jam_wbp_mulai ?: '18:00');
        $selesai = trim($config->jam_wbp_selesai ?: '22:00');

        // Normalize HH to HH:00
        if (strlen($mulai) === 2) {
            $mulai .= ':00';
        }
        if (strlen($selesai) === 2) {
            $selesai .= ':00';
        }

        if (strlen($mulai) === 4 && $mulai[1] === ':') {
            $mulai = '0'.$mulai;
        }
        if (strlen($selesai) === 4 && $selesai[1] === ':') {
            $selesai = '0'.$selesai;
        }

        if ($mulai === $selesai) {
            return false;
        }

        if ($mulai < $selesai) {
            return $timeStr >= $mulai && $timeStr < $selesai;
        }

        // Cross-midnight window (e.g. 22:00 to 04:00)
        return $timeStr >= $mulai || $timeStr < $selesai;
    }
}
