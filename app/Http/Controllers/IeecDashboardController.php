<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\AuditReport;
use App\Models\EnergyTelemetryLog;
use App\Models\RegulatoryConfig;
use App\Models\RegulatoryConfigLog;
use App\Models\SdpPanel;
use App\Services\AiCopilotService;
use App\Services\DeterministicEnergyEngine;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

final class IeecDashboardController extends Controller
{
    public function __construct(
        private readonly DeterministicEnergyEngine $engine,
        private readonly AiCopilotService $copilot
    ) {}

    /**
     * Main Dashboard View.
     */
    public function index(Request $request): View
    {
        $preset = $request->query('preset', 'sukarela');
        if (! in_array($preset, ['sukarela', 'wajib', 'custom_upload'], true)) {
            $preset = 'sukarela';
        }

        $nonElectricToe = $request->filled('non_electric_toe')
            ? (float) $request->query('non_electric_toe')
            : null;

        $config = RegulatoryConfig::first() ?? RegulatoryConfig::create([
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

        // Run Deterministic Analytics
        $payload = $this->engine->analyze($preset, $nonElectricToe, $config);

        // Generate AI Narrative
        $narrativeHtml = $this->copilot->generateExecutiveAuditNarrative($payload);

        // Persist or update audit report log
        AuditReport::updateOrCreate(
            ['preset_source' => $preset, 'regulatory_config_id' => $config->id],
            [
                'total_kwh' => $payload['kepatuhan_manajemen_energi']['total_listrik_bulanan_kwh'],
                'total_toe_tahunan' => $payload['kepatuhan_manajemen_energi']['total_konsumsi_energi_tahunan_toe'],
                'status_kepatuhan' => $payload['kepatuhan_manajemen_energi']['status_kepatuhan'],
                'payload_json' => $payload,
                'ai_narrative_html' => $narrativeHtml,
            ]
        );

        $allPanels = SdpPanel::all();
        $initialTab = ($request->routeIs('copilot') || $request->path() === 'copilot')
            ? 'copilot'
            : ($request->query('tab') ?? 'overview');

        return view('dashboard', [
            'payload' => $payload,
            'narrativeHtml' => $narrativeHtml,
            'preset' => $preset,
            'config' => $config,
            'allPanels' => $allPanels,
            'nonElectricToe' => $nonElectricToe ?? ($preset === 'wajib' ? 3800.0 : 0.0),
            'initialTab' => $initialTab,
            'configLogs' => RegulatoryConfigLog::latest()->take(10)->get(),
        ]);
    }

    /**
     * FR-07: Update Regulatory Configurations Dynamically.
     */
    public function updateConfig(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'tarif_lwbp_rp_kwh' => 'required|numeric|min:1',
            'faktor_k_wbp' => 'required|numeric|min:1|max:3',
            'jam_wbp_mulai' => 'required|string',
            'jam_wbp_selesai' => 'required|string',
            'tarif_kvarh_rp' => 'required|numeric|min:1',
            'batas_cos_phi' => 'required|numeric|min:0.5|max:1.0',
            'rasio_kvarh_bebas' => 'required|numeric|min:0.1|max:1.0',
            'faktor_emisi_grid' => 'required|numeric|min:0.1|max:2.0',
            'ambang_wajib_toe' => 'nullable|numeric',
        ]);

        // Statutory Parameter Locking per PRD Section 2:
        // 1. ambang_wajib_toe is locked to 4000 TOE under PP 33/2023 ("Terkunci (Regulasi)").
        $validated['ambang_wajib_toe'] = 4000.0;
        // 2. tarif_wbp_rp_kwh is automatically computed ("Terkunci (Otomatis)").
        $validated['tarif_wbp_rp_kwh'] = round((float) $validated['tarif_lwbp_rp_kwh'] * (float) $validated['faktor_k_wbp'], 2);

        $config = RegulatoryConfig::first();
        if ($config) {
            // Track configuration parameter changes into audit log
            $changes = [];
            foreach ($validated as $key => $newValue) {
                $oldValue = $config->{$key};
                $isDifferent = is_numeric($newValue)
                    ? abs((float) $oldValue - (float) $newValue) > 0.0001
                    : (string) $oldValue !== (string) $newValue;

                if ($isDifferent) {
                    $changes[] = [
                        'regulatory_config_id' => $config->id,
                        'versi_regulasi' => $config->versi_regulasi ?? 'ESDM-2026-Q4-v1.2',
                        'parameter_name' => $key,
                        'old_value' => (string) $oldValue,
                        'new_value' => (string) $newValue,
                        'changed_by' => 'Manajer Energi (IEEC)',
                        'notes' => 'Kalibrasi via form Konfigurasi Regulasi FR-07',
                        'created_at' => now(),
                        'updated_at' => now(),
                    ];
                }
            }

            if (! empty($changes)) {
                RegulatoryConfigLog::insert($changes);
            }

            $config->update($validated);
        } else {
            RegulatoryConfig::create($validated);
        }

        $preset = $request->input('preset', 'sukarela');
        $params = ['preset' => $preset];
        if ($request->filled('tab')) {
            $params['tab'] = $request->input('tab');
        }

        return redirect()->route('dashboard', $params)
            ->with('status', 'Konfigurasi Regulasi FR-07 berhasil diperbarui! Seluruh kartu KPI dan analitik telah dikalibrasi ulang.');
    }

    /**
     * FR-01: Upload and Ingest Historical Energy CSV.
     */
    public function uploadCsv(Request $request): RedirectResponse
    {
        $request->validate([
            'csv_file' => 'required|file|mimes:csv,txt|max:10240',
        ]);

        $file = $request->file('csv_file');
        if (! $file) {
            return back()->with('error', 'Berkas CSV tidak ditemukan.');
        }

        $handle = fopen($file->getRealPath(), 'r');
        if (! $handle) {
            return back()->with('error', 'Gagal membuka berkas CSV.');
        }

        $headers = fgetcsv($handle);
        if (! $headers) {
            fclose($handle);

            return back()->with('error', 'Berkas CSV kosong.');
        }

        $headers = array_map(fn ($h) => trim(str_replace("\xEF\xBB\xBF", '', $h)), $headers);
        $expected = ['timestamp', 'panel_id', 'category', 'energy_kwh', 'power_factor', 'kvarh', 'production_qty', 'area_sqm', 'shift_state'];

        $diff = array_diff($expected, $headers);
        if (! empty($diff)) {
            fclose($handle);

            return back()->with('error', 'Skema kolom CSV tidak sesuai standar IEEC. Kolom wajib: '.implode(', ', $expected));
        }

        $config = RegulatoryConfig::first();
        $source = 'custom_upload';
        $now = Carbon::now();

        $rowsToInsert = [];
        $discoveredPanels = [];
        $seenRecords = [];
        $lineNum = 1;

        while (($row = fgetcsv($handle)) !== false) {
            $lineNum++;

            // Skip empty rows
            if (empty(array_filter($row, fn ($val) => trim((string) $val) !== ''))) {
                continue;
            }

            if (count($row) !== count($headers)) {
                fclose($handle);

                return back()->with('error', "Jumlah kolom tidak cocok pada baris {$lineNum}. Ditemukan ".count($row).' kolom, diharapkan '.count($headers).'.');
            }

            $data = array_combine($headers, $row);

            // 1. Timestamp validation
            try {
                $dt = Carbon::parse($data['timestamp']);
                $formattedTs = $dt->format('Y-m-d H:i:s');
            } catch (\Throwable) {
                fclose($handle);

                return back()->with('error', "Format timestamp tidak valid pada baris {$lineNum}: '{$data['timestamp']}'. Gunakan format ISO/standar YYYY-MM-DD HH:MM.");
            }

            // 2. Panel ID validation
            $panelId = trim((string) ($data['panel_id'] ?? ''));
            if ($panelId === '') {
                fclose($handle);

                return back()->with('error', "Kolom panel_id tidak boleh kosong pada baris {$lineNum}.");
            }

            // 3. Category enum validation
            $category = trim((string) ($data['category'] ?? ''));
            if (! in_array($category, ['PRODUCTION', 'BUILDING_AC', 'BUILDING_NON_AC'], true)) {
                fclose($handle);

                return back()->with('error', "Kategori '{$category}' pada baris {$lineNum} tidak valid. Pilihan valid: PRODUCTION, BUILDING_AC, BUILDING_NON_AC.");
            }

            // 4. Numeric fields validation
            if (! is_numeric($data['energy_kwh']) || (float) $data['energy_kwh'] < 0.0) {
                fclose($handle);

                return back()->with('error', "Nilai energy_kwh harus berupa angka non-negatif pada baris {$lineNum}.");
            }

            if (! is_numeric($data['power_factor']) || (float) $data['power_factor'] < 0.0 || (float) $data['power_factor'] > 1.0) {
                fclose($handle);

                return back()->with('error', "Nilai power_factor harus berupa desimal antara 0.0 dan 1.0 pada baris {$lineNum}.");
            }

            if (! is_numeric($data['kvarh']) || (float) $data['kvarh'] < 0.0) {
                fclose($handle);

                return back()->with('error', "Nilai kvarh harus berupa angka non-negatif pada baris {$lineNum}.");
            }

            if (! is_numeric($data['production_qty']) || (float) $data['production_qty'] < 0.0) {
                fclose($handle);

                return back()->with('error', "Nilai production_qty harus berupa angka non-negatif pada baris {$lineNum}.");
            }

            if (! is_numeric($data['area_sqm']) || (float) $data['area_sqm'] < 0.0) {
                fclose($handle);

                return back()->with('error', "Nilai area_sqm harus berupa angka non-negatif pada baris {$lineNum}.");
            }

            // 5. Shift state
            $shiftState = trim((string) ($data['shift_state'] ?? '')) ?: 'OPERATIONAL';

            // 6. Duplicate detection (timestamp + panel_id)
            $dupKey = $formattedTs.'|'.$panelId;
            if (isset($seenRecords[$dupKey])) {
                fclose($handle);

                return back()->with('error', "Duplikasi data terdeteksi pada baris {$lineNum}: data untuk panel '{$panelId}' pada timestamp '{$formattedTs}' sudah ada.");
            }
            $seenRecords[$dupKey] = true;

            // Track panel for auto-registration
            if (! isset($discoveredPanels[$panelId])) {
                $discoveredPanels[$panelId] = [
                    'category' => $category,
                    'area_sqm' => (float) $data['area_sqm'],
                ];
            }

            $rowsToInsert[] = [
                'regulatory_config_id' => $config?->id,
                'preset_source' => $source,
                'timestamp' => $formattedTs,
                'panel_id' => $panelId,
                'category' => $category,
                'energy_kwh' => (float) $data['energy_kwh'],
                'power_factor' => (float) $data['power_factor'],
                'kvarh' => (float) $data['kvarh'],
                'production_qty' => (int) $data['production_qty'],
                'area_sqm' => (float) $data['area_sqm'],
                'shift_state' => $shiftState,
                'created_at' => $now,
                'updated_at' => $now,
            ];
        }

        fclose($handle);

        if (empty($rowsToInsert)) {
            return back()->with('error', 'Berkas CSV tidak memuat data yang dapat diimpor.');
        }

        // Atomic transaction: auto-register unknown panels and replace previous custom upload
        DB::transaction(function () use ($discoveredPanels, $rowsToInsert, $source) {
            foreach ($discoveredPanels as $pId => $meta) {
                SdpPanel::firstOrCreate(
                    ['panel_id' => $pId],
                    [
                        'deskripsi' => ucwords(str_replace(['_', '-'], ' ', $pId)),
                        'category' => $meta['category'],
                        'baseline_enb' => ($meta['category'] === 'PRODUCTION') ? 0.35 : null,
                        'default_area_sqm' => ($meta['area_sqm'] > 0) ? $meta['area_sqm'] : (($meta['category'] === 'PRODUCTION') ? 0.0 : 600.0),
                    ]
                );
            }

            // Clear previous custom upload
            EnergyTelemetryLog::where('preset_source', $source)->delete();

            // Insert in batches of 500
            foreach (array_chunk($rowsToInsert, 500) as $chunk) {
                DB::table('energy_telemetry_logs')->insert($chunk);
            }
        });

        $insertedCount = count($rowsToInsert);
        $panelCount = count($discoveredPanels);

        return redirect()->route('dashboard', ['preset' => $source])
            ->with('status', "Berhasil memvalidasi dan mengunggah {$insertedCount} baris telemetri ({$panelCount} panel terdaftar).");
    }

    /**
     * FR-04: Copilot Interactive Chat Endpoint.
     */
    public function chat(Request $request): JsonResponse
    {
        $question = (string) $request->input('question', '');
        $preset = (string) $request->input('preset', 'sukarela');
        $history = (array) $request->input('history', []);

        if (trim($question) === '') {
            return response()->json(['reply' => 'Silakan masukkan pertanyaan teknis atau regulasi.'], 400);
        }

        $config = RegulatoryConfig::first();
        $payload = $this->engine->analyze($preset, null, $config);

        $reply = $this->copilot->chat($question, $payload, $history);

        return response()->json([
            'reply' => $reply,
            'timestamp' => Carbon::now()->format('H:i:s WIB'),
        ]);
    }

    /**
     * FR-05: Real-time IoT Telemetry Streaming Simulation (3s interval).
     */
    public function streamIot(Request $request): JsonResponse
    {
        $preset = $request->query('preset', 'sukarela');
        $multiplier = ($preset === 'wajib') ? 80.0 : 1.0;

        $config = RegulatoryConfig::first();
        $now = Carbon::now();
        $isWbp = $config
            ? DeterministicEnergyEngine::isTimestampInWbp($now, $config)
            : ((int) $now->format('H') >= 18 && (int) $now->format('H') < 22);

        // Simulated readings for 4 panels
        $sdp1Kwh = round((rand(30, 50) + (rand(0, 99) / 100)) * $multiplier, 2);
        $sdp1Pf = round(0.79 + (rand(0, 50) / 1000), 4); // Low cos phi
        $sdp1Kvarh = round($sdp1Kwh * tan(acos(min(1.0, $sdp1Pf))), 2);

        $sdp2Kwh = round((rand(20, 38) + (rand(0, 99) / 100)) * $multiplier, 2);
        if ($isWbp) {
            $sdp2Kwh = round($sdp2Kwh * 1.35, 2); // WBP spike
        }
        $sdp2Pf = round(0.85 + (rand(0, 40) / 1000), 4);
        $sdp2Kvarh = round($sdp2Kwh * tan(acos(min(1.0, $sdp2Pf))), 2);

        $sdp3Kwh = round((8.5 + (rand(0, 50) / 10)) * ($multiplier > 1 ? 50 : 1), 2);
        $sdp3Pf = 0.93;
        $sdp3Kvarh = round($sdp3Kwh * tan(acos(0.93)), 2);

        $sdp4Kwh = round((3.8 + (rand(0, 30) / 10)) * ($multiplier > 1 ? 50 : 1), 2);
        $sdp4Pf = 0.89;
        $sdp4Kvarh = round($sdp4Kwh * tan(acos(0.89)), 2);

        $totalKw = $sdp1Kwh + $sdp2Kwh + $sdp3Kwh + $sdp4Kwh;
        $totalKvar = $sdp1Kvarh + $sdp2Kvarh + $sdp3Kvarh + $sdp4Kvarh;
        $aggregateCosPhi = round($totalKw / sqrt(pow($totalKw, 2) + pow($totalKvar, 2)), 4);

        return response()->json([
            'timestamp' => $now->format('H:i:s'),
            'total_active_kw' => round($totalKw, 1),
            'total_reactive_kvar' => round($totalKvar, 1),
            'aggregate_cos_phi' => $aggregateCosPhi,
            'is_wbp' => $isWbp,
            'panels' => [
                'SDP-01_LINE_A' => ['kwh' => $sdp1Kwh, 'pf' => $sdp1Pf, 'kvarh' => $sdp1Kvarh, 'status' => ($sdp1Pf < 0.85 ? 'ALERT_PF' : 'NORMAL')],
                'SDP-02_UTILITY' => ['kwh' => $sdp2Kwh, 'pf' => $sdp2Pf, 'kvarh' => $sdp2Kvarh, 'status' => ($isWbp ? 'PEAK_WBP' : 'NORMAL')],
                'SDP-03_GEDUNG_UTAMA' => ['kwh' => $sdp3Kwh, 'pf' => $sdp3Pf, 'kvarh' => $sdp3Kvarh, 'status' => 'NORMAL'],
                'SDP-04_GUDANG_FEEDSTOCK' => ['kwh' => $sdp4Kwh, 'pf' => $sdp4Pf, 'kvarh' => $sdp4Kvarh, 'status' => 'NORMAL'],
            ],
        ]);
    }

    /**
     * FR-08: Export Official ESDM Energy Audit Worksheet (HTML, PDF, Excel/CSV).
     */
    public function exportReport(Request $request): Response
    {
        $preset = (string) $request->query('preset', 'sukarela');
        $nonElectricToe = $request->filled('non_electric_toe') ? (float) $request->query('non_electric_toe') : null;
        $format = strtolower((string) $request->query('format', 'html'));
        $config = RegulatoryConfig::first();

        $payload = $this->engine->analyze($preset, $nonElectricToe, $config);
        $exportTime = Carbon::now()->isoFormat('D MMMM Y, HH:mm [WIB]');
        $cleanTimestamp = Carbon::now()->format('Ymd_His');

        // 1. PDF Format (Barryvdh DomPDF)
        if ($format === 'pdf') {
            $logoPath = public_path('images/indmira-logo.jpeg');
            $logoBase64 = file_exists($logoPath) ? base64_encode((string) file_get_contents($logoPath)) : null;

            $pdf = Pdf::loadView('reports.esdm_audit_worksheet_pdf', [
                'payload' => $payload,
                'config' => $config,
                'exportTime' => $exportTime,
                'logoBase64' => $logoBase64,
            ]);

            $pdf->setPaper('a4', 'portrait');

            return $pdf->download("Lembar_Kerja_Audit_ESDM_{$preset}_{$cleanTimestamp}.pdf");
        }

        // 2. Excel / CSV Spreadsheet Format
        if (in_array($format, ['excel', 'csv'], true)) {
            $filename = "Lembar_Kerja_Audit_ESDM_{$preset}_{$cleanTimestamp}.csv";
            $handle = fopen('php://temp', 'r+');

            // UTF-8 BOM for Microsoft Excel compatibility
            fwrite($handle, "\xEF\xBB\xBF");

            $writeCsvRow = function (array $fields) use ($handle): void {
                fputcsv($handle, $fields, ',', '"', '\\');
            };

            // Document Header & Legal Disclaimer
            $writeCsvRow(['LEMBAR KERJA AUDIT ENERGI BERKALA (PERMEN ESDM NO. 8 TAHUN 2025 & PP NO. 33 TAHUN 2023)']);
            $writeCsvRow(['DOKUMEN SIMULASI PENDUKUNG KEPUTUSAN — BUKAN LAPORAN AUDIT ENERGI RESMI (PERMEN ESDM NO. 8 TAHUN 2025)']);
            $writeCsvRow(['Waktu Ekspor', $exportTime]);
            $writeCsvRow(['Versi Regulasi', $config->versi_regulasi ?? 'Permen ESDM No. 8/2025']);
            $writeCsvRow([]);

            // 1. Facility & Compliance Balance
            $writeCsvRow(['--- 1. DATA FASILITAS & EVALUASI KEPATUHAN REGULASI ---']);
            $writeCsvRow(['Parameter Fasilitas', 'Nilai Terhitung', 'Satuan / Keterangan']);
            $writeCsvRow(['Nama Fasilitas', $payload['audit_metadata']['facility_name'] ?? 'Pabrik Pengolahan Biomassa & Arang Terpadu', '-']);
            $writeCsvRow(['Industry Problem Owner', 'PT Indmira Global Energi', '-']);
            $writeCsvRow(['Periode Audit', $payload['audit_metadata']['periode_audit'] ?? '-', "({$payload['audit_metadata']['jumlah_hari_data']} Hari Data)"]);
            $writeCsvRow(['Konsumsi Listrik Bulanan', (string) ($payload['kepatuhan_manajemen_energi']['total_listrik_bulanan_kwh'] ?? 0), 'kWh / bln']);
            $writeCsvRow(['Konsumsi Listrik Bulanan (MWh)', (string) ($payload['kepatuhan_manajemen_energi']['total_listrik_bulanan_mwh'] ?? 0), 'MWh / bln']);
            $writeCsvRow(['Konsumsi TOE Primer Bulanan', (string) ($payload['kepatuhan_manajemen_energi']['konsumsi_toe_bulanan'] ?? 0), 'TOE / bln']);
            $writeCsvRow(['Proyeksi Energi Listrik Tahunan', (string) ($payload['kepatuhan_manajemen_energi']['proyeksi_toe_tahunan_listrik'] ?? 0), 'TOE / th']);
            $writeCsvRow(['Input Energi Termal Non-Listrik', (string) ($payload['kepatuhan_manajemen_energi']['input_energi_non_listrik_toe_tahunan'] ?? 0), 'TOE / th']);
            $writeCsvRow(['Total Konsumsi Energi Tahunan', (string) ($payload['kepatuhan_manajemen_energi']['total_konsumsi_energi_tahunan_toe'] ?? 0), 'TOE / th']);
            $writeCsvRow(['Ambang Batas Wajib Manajemen Energi', (string) ($payload['kepatuhan_manajemen_energi']['ambang_batas_toe'] ?? 4000), 'TOE / th']);
            $writeCsvRow(['Status Kepatuhan PP 33/2023', $payload['kepatuhan_manajemen_energi']['status_kepatuhan'] ?? '-', '-']);
            $writeCsvRow(['Implikasi Regulasi', $payload['kepatuhan_manajemen_energi']['implikasi_hukum'] ?? '-', '-']);
            $writeCsvRow([]);

            // 2. SDP Panel IKE & Baseline Matrix
            $writeCsvRow(['--- 2. MATRIKS EVALUASI KINERJA ENERGI PANEL SDP (IKE & BASELINE) ---']);
            $writeCsvRow(['Panel ID', 'Deskripsi Beban', 'Kategori', 'Konsumsi Listrik (kWh)', 'Beban Dasar E0 (kW)', 'R2 EnB Baseline', 'Cos Phi Rata-rata', 'Status Evaluasi IKE']);
            foreach ($payload['analisis_ike_per_panel'] ?? [] as $panel) {
                $writeCsvRow([
                    $panel['panel_id'],
                    $panel['deskripsi'],
                    $panel['kategori'],
                    (string) ($panel['konsumsi_kwh'] ?? 0),
                    isset($panel['base_load_e0_kwh']) && $panel['base_load_e0_kwh'] > 0 ? (string) $panel['base_load_e0_kwh'] : '-',
                    isset($panel['r2_baseline']) ? (string) $panel['r2_baseline'] : '-',
                    isset($panel['cos_phi_rata_rata']) ? (string) $panel['cos_phi_rata_rata'] : '-',
                    $panel['status_evaluasi'] ?? '-',
                ]);
            }
            $writeCsvRow([]);

            // 3. Multi-Vector Waste & Financial Summary
            $writeCsvRow(['--- 3. RINGKASAN PEMBOROSAN MULTI-VEKTOR & BEBAN FINANSIAL BULANAN ---']);
            $writeCsvRow(['Vektor Pemborosan Energi', 'Parameter Fisik Terukur', 'Standar Acuan Regulasi', 'Beban Finansial (Rp/bln)']);
            $writeCsvRow([
                'Denda Penalti Daya Reaktif (Cos Phi Rendah)',
                ($payload['temuan_pemborosan_dan_finansial']['kvarh_kena_biaya'] ?? 0).' kVARh',
                'Permen ESDM No. 8/2025 & SPLN (Standar Cos Phi >= 0.85)',
                (string) ($payload['temuan_pemborosan_dan_finansial']['biaya_kvarh_rp'] ?? 0),
            ]);
            $writeCsvRow([
                'Beban Puncak WBP (18:00 - 22:00 WIB)',
                ($payload['temuan_pemborosan_dan_finansial']['wbp_shiftable_kwh'] ?? 0).' kWh teridentifikasi',
                'Tarif Tenaga Listrik PLN Gol. '.($config->golongan_tarif ?? 'I-3/TM').' (K = '.($config->wbp_multiplier ?? 1.5).'x)',
                (string) ($payload['temuan_pemborosan_dan_finansial']['hemat_geser_wbp_rp'] ?? 0),
            ]);
            $writeCsvRow([
                'Idle Load Waste (Break & Jam Kosong)',
                ($payload['temuan_pemborosan_dan_finansial']['idle_load_waste_kwh'] ?? 0).' kWh',
                'Metode Baseline Beban Dasar ISO 50006:2014',
                (string) round(($payload['temuan_pemborosan_dan_finansial']['idle_load_waste_kwh'] ?? 0) * ($config->tarif_lwbp_rp_kwh ?? 1115)),
            ]);
            $writeCsvRow([
                'Spike Anomaly Waste (Z-Score > 2.0)',
                ($payload['temuan_pemborosan_dan_finansial']['spike_anomaly_waste_kwh'] ?? 0).' kWh',
                'Analisis Z-Score ISO 50001',
                (string) round(($payload['temuan_pemborosan_dan_finansial']['spike_anomaly_waste_kwh'] ?? 0) * ($config->tarif_lwbp_rp_kwh ?? 1115)),
            ]);
            $writeCsvRow([
                'TOTAL POTENSI PENGHEMATAN FINANSIAL',
                '-',
                '-',
                (string) ($payload['temuan_pemborosan_dan_finansial']['total_potensi_hemat_rp'] ?? 0),
            ]);
            $writeCsvRow([]);

            // 4. Payback Period Classified Audit Recommendations (Permen ESDM 8/2025)
            $writeCsvRow(['--- 4. MATRIKS REKOMENDASI AUDIT ENERGI TERKLASIFIKASI PAYBACK PERIOD (PERMEN ESDM 8/2025) ---']);
            $writeCsvRow(['Kategori Investasi', 'Kriteria Payback', 'Tindakan Rekomendasi', 'Lokasi Panel', 'Potensi Hemat (Rp/bln)', 'Capex Implementasi (Rp)', 'Payback Period (Tahun)', 'Reduksi CO2e (Ton/bln)', 'Deskripsi Teknis']);
            foreach ($payload['klasifikasi_rekomendasi_audit_esdm'] ?? [] as $rek) {
                $writeCsvRow([
                    $rek['kategori_investasi'],
                    $rek['kriteria_payback'],
                    $rek['judul_tindakan'],
                    $rek['lokasi_panel'],
                    (string) $rek['potensi_hemat_rp_per_bulan'],
                    (string) $rek['estimasi_biaya_implementasi_rp'],
                    (string) $rek['payback_period_tahun'],
                    (string) $rek['potensi_reduksi_co2_ton'],
                    $rek['deskripsi_teknis'],
                ]);
            }
            $writeCsvRow([]);

            // 5. Formal Legal Sign-off Placeholders
            $writeCsvRow(['--- 5. LEMBAR PENGESAHAN AUDIT ENERGI BERKALA ---']);
            $writeCsvRow(['Peran Pengesahan', 'Nama Lengkap', 'Nomor Registrasi / Unit Organisasi']);
            $writeCsvRow(['Penyusun & Verifikator', '[Nama Manajer Energi Bersertifikat]', 'Reg. BNSP: [Nomor Registrasi]']);
            $writeCsvRow(['Peninjau & Auditor Resmi', '[Nama Auditor Energi Bersertifikat]', 'Reg. EBTKE: [Nomor Registrasi]']);
            $writeCsvRow(['Persetujuan Manajemen Fasilitas', '[Direktur Operasional / Plant Manager]', 'Plant Operation & ESG Division']);

            rewind($handle);
            $csvContent = (string) stream_get_contents($handle);
            fclose($handle);

            $contentType = ($format === 'excel') ? 'application/vnd.ms-excel; charset=UTF-8' : 'text/csv; charset=UTF-8';

            return response($csvContent, 200, [
                'Content-Type' => $contentType,
                'Content-Disposition' => 'attachment; filename="'.$filename.'"',
            ]);
        }

        // 3. Default HTML Printable Document
        return response()->view('reports.esdm_audit_worksheet', [
            'payload' => $payload,
            'config' => $config,
            'exportTime' => $exportTime,
        ])->header('Content-Type', 'text/html');
    }
}
