# DELIVERABLE C — FINDINGS REGISTER
## Industrial Energy Efficiency Copilot (IEEC) — Prototype Alpha v1.2
**Classification Standard:**
- **P0 — Critical:** Serious correctness, security, or data-integrity issue that makes functionality unsafe or unusable.
- **P1 — High:** Core PRD requirement missing or incorrect, or major issue undermining the prototype's primary use case.
- **P2 — Medium:** Significant UX, reliability, maintainability, or operational limitation.
- **P3 — Low:** Polish, minor inconsistencies, or nonessential cosmetic improvements.

---

### Part 1: Prioritized Verified Defects

#### Finding F-01 (P0 — Critical)
- **Finding ID:** `F-01`
- **Severity:** **P0 — Critical**
- **Category:** Domain Calculation & Integrity / FR-07 Compliance
- **Title:** Primary Demo Preset (`sukarela`) Overrides Real Calculations with Static Constants, Breaking Dynamic Recalibration
- **Evidence:**  
  In [`DeterministicEnergyEngine.php`](file:///Users/yusuf/Documents/Project/Angel-Hack/app/Services/DeterministicEnergyEngine.php#L118-L133):
  ```php
  // Hard calibration match for preset sukarela as specified in PRD 7.1
  if ($isStandardSukarela) {
      if ($panelId === 'SDP-01_LINE_A') {
          $ikeAktual = 0.52; $baselineEnb = 0.35; $e0 = 22.4; $r2 = 0.91; $pCosPhi = 0.81;
      } elseif ($panelId === 'SDP-02_UTILITY') {
          $ikeAktual = 0.39; $baselineEnb = 0.35; $e0 = 14.1; $r2 = 0.88; $pCosPhi = 0.86;
      }
  }
  ```
  And lines 213–217 & 264–270:
  ```php
  if ($isStandardSukarela) {
      $idleWasteKwh = 2450.0;
      $spikeWasteKwh = 1670.0;
      $wbpShiftableKwh = 1200.0;
      $kvarhBillable = 758.0;
      ...
      $hematEliminasiAktifRp = 4267414.0;
      $hematGeserWbpRp = 497172.0;
      $biayaKvarhRp = 845000.0;
      $totalPotensiHematRp = 5609586.0;
      $reduksiCo2Ton = 3.58;
  }
  ```
- **Requirement Affected:** FR-02, FR-03, FR-07 (Section 2, Section 3.6, Section 7.1)
- **User/Business Impact:** Modifying electricity tariffs (e.g., from Rp 1,035.78 to Rp 2,000/kWh) in *Konfigurasi Regulasi* shows a success notification, but savings and KPI cards for `sukarela` remain permanently frozen at Rp 5,609,586. This directly contradicts the core value proposition of dynamic calibration.
- **Reproduction Steps:**
  1. Open dashboard at `/?preset=sukarela`. Note total savings: Rp 5.609.586.
  2. Navigate to *Konfigurasi Regulasi* tab (`#config-regulasi`).
  3. Change `tarif_lwbp_rp_kwh` to `2000` and submit form.
  4. Observe dashboard: Savings are still Rp 5.609.586 instead of recalculating to ~Rp 9.5 Million.
- **Recommended Fix:** Delete the `if ($isStandardSukarela)` override blocks entirely. Allow dynamic calculations to execute on `sukarela` data. Calibrate the dataset generation parameters in `generate_ieec_datasets.py` if exact rounding is desired.
- **Estimated Effort:** **S** (Small, ~2 hours)
- **Confidence:** **High** (Verified via code and tinker execution)

---

#### Finding F-02 (P0 — Critical)
- **Finding ID:** `F-02`
- **Severity:** **P0 — Critical**
- **Category:** Database Schema & Ingestion / FR-01 Compliance
- **Title:** Foreign Key Constraint Causes Fatal Database Exception When Uploading Custom Industrial Datasets
- **Evidence:**  
  Migration [`2026_10_15_000003_create_energy_telemetry_logs_table.php:20`](file:///Users/yusuf/Documents/Project/Angel-Hack/database/migrations/2026_10_15_000003_create_energy_telemetry_logs_table.php#L20):
  ```php
  $table->string('panel_id')->index();
  $table->foreign('panel_id')->references('panel_id')->on('sdp_panels')->cascadeOnDelete();
  ```
  In [`IeecDashboardController.php:168-198`](file:///Users/yusuf/Documents/Project/Angel-Hack/app/Http/Controllers/IeecDashboardController.php#L168-L198), `uploadCsv()` directly executes bulk inserts into `energy_telemetry_logs` without checking or inserting new panels into `sdp_panels`.
- **Requirement Affected:** FR-01 (Historical CSV Upload & Data Sanitizer)
- **User/Business Impact:** Any real industrial user attempting to upload CSV data from a factory containing panel identifiers other than the four hardcoded Indmira panels (`SDP-01_LINE_A`, `SDP-02_UTILITY`, `SDP-03_GEDUNG_UTAMA`, `SDP-04_GUDANG_FEEDSTOCK`) triggers an unhandled `QueryException: SQLSTATE[23000]: Integrity constraint violation: 19 FOREIGN KEY constraint failed` and receives an HTTP 500 crash.
- **Reproduction Steps:**
  1. Prepare a standard 9-column CSV with `panel_id` set to `SDP-01_BOILER`.
  2. Upload via the CSV Upload modal.
  3. Observe fatal SQLite foreign key crash.
- **Recommended Fix:** In `uploadCsv()`, extract distinct `panel_id` and `category` from the uploaded dataset, and run `SdpPanel::firstOrCreate()` to register unknown panels prior to inserting logs, or drop the foreign key constraint on prototype Alpha.
- **Estimated Effort:** **S** (Small, ~2 hours)
- **Confidence:** **High** (Verified via tinker reproduction)

---

#### Finding F-03 (P0 — Critical)
- **Finding ID:** `F-03`
- **Severity:** **P0 — Critical**
- **Category:** Security & Access Control
- **Title:** Zero Authentication and Access Control on Sensitive Configuration and Upload Routes
- **Evidence:**  
  [`routes/web.php:8-10`](file:///Users/yusuf/Documents/Project/Angel-Hack/routes/web.php#L8-L10):
  ```php
  Route::post('/config', [IeecDashboardController::class, 'updateConfig'])->name('config.update');
  Route::post('/upload', [IeecDashboardController::class, 'uploadCsv'])->name('csv.upload');
  Route::post('/chat', [IeecDashboardController::class, 'chat'])->name('copilot.chat');
  ```
  No `auth` middleware, policies, or role checks are applied to any route.
- **Requirement Affected:** PRD Section 2 (Role-Based Permissions: "Hak Akses Ubah: Ya (Manajer Energi)" vs "Terkunci (Regulasi)"), NFR-03 (Security)
- **User/Business Impact:** Any anonymous visitor on the public internet can POST to `/config` to modify plant tariffs, alter baseline thresholds, or tamper with legal compliance parameters (`ambang_wajib_toe`). Any visitor can POST to `/upload` to overwrite plant telemetry logs, or repeatedly call `/chat` to deplete third-party LLM API credits.
- **Reproduction Steps:**
  1. Open a terminal or curl client with no session cookies.
  2. Send a POST request with CSRF token to `/config` modifying `ambang_wajib_toe` to 100.
  3. Notice configuration is updated globally without authentication.
- **Recommended Fix:** Implement basic authentication or gate administrative actions behind an `auth` middleware with role checks (`Role: EnergyManager`). Apply `throttle:15,1` rate limiting on `/chat` and `/upload`.
- **Estimated Effort:** **M** (Medium, ~4 hours)
- **Confidence:** **High** (Verified via route inspection)

---

#### Finding F-04 (P1 — High)
- **Finding ID:** `F-04`
- **Severity:** **P1 — High**
- **Category:** AI Systems & Presentation Integrity / FR-04 Compliance
- **Title:** Executive Audit Narrative Template Injects Hardcoded Diagnostic Text from Small Pilot Plant Across All Datasets
- **Evidence:**  
  [`AiCopilotService.php:49-51`](file:///Users/yusuf/Documents/Project/Angel-Hack/app/Services/AiCopilotService.php#L49-L51):
  ```php
  $html .= '<li><strong class="text-rose-700">SDP-01 (Line Pirolisis):</strong> IKE aktual <span class="font-mono text-slate-900">0,52 kWh/batch</span> (<strong class="text-rose-700">+48,57%</strong> di atas baseline EnB 0,35). Base Load $E_0 = 22,4$ kW saat istirahat dan faktor daya rata-rata <span class="font-mono text-rose-700">&cos;&phi; = 0,81</span> (&lt; 0,85).</li>';
  $html .= '<li><strong class="text-amber-700">SDP-02 (Utilitas):</strong> IKE aktual <span class="font-mono text-slate-900">0,39 kWh/batch</span> (<strong class="text-amber-700">+11,43%</strong> waspada). Lonjakan daya pada jam WBP (18:00–22:00) terkena pengali faktor $K = 1,40$.</li>';
  $html .= '<li><strong class="text-emerald-700">SDP-03 &amp; SDP-04 (Gedung &amp; Gudang):</strong> Memenuhi SNI 6196 &amp; Permen ESDM 3/2025 kategori <span class="text-emerald-800 font-medium">EFISIEN</span> (10,33 kWh/m&sup2;/bln dan 2,33 kWh/m&sup2;/bln).</li>';
  ```
- **Requirement Affected:** FR-04 (AI Energy Efficiency Copilot)
- **User/Business Impact:** When the user switches to the `wajib` commercial facility preset (3,900,000 kWh/month) or uploads a custom dataset, the AI Executive Summary narrative rendered on screen still asserts that Line Pirolisis consumed `0,52 kWh/batch` with a base load of `22,4 kW`. This contradiction destroys user credibility.
- **Reproduction Steps:**
  1. Open `/?preset=wajib`.
  2. Read the "Ringkasan Eksekutif Audit Energi" panel on the right drawer or overview tab.
  3. Observe that Line Pirolisis is described with 0.52 kWh/batch and E0=22.4 kW, while the actual panel table shows 1,810,563 kWh.
- **Recommended Fix:** Refactor `generateExecutiveAuditNarrative()` to dynamically pull metrics from `$payload['analisis_ike_per_panel']`.
- **Estimated Effort:** **S** (Small, ~2 hours)
- **Confidence:** **High** (Verified via code and tinker execution)

---

#### Finding F-05 (P1 — High)
- **Finding ID:** `F-05`
- **Severity:** **P1 — High**
- **Category:** Domain Calculation & Regulatory Standards / FR-04 & FR-07 Compliance
- **Title:** Static Payback Period Constants in Prescriptive Recommendations Fail Dynamic Recalculation
- **Evidence:**  
  In [`DeterministicEnergyEngine.php:439-478`](file:///Users/yusuf/Documents/Project/Angel-Hack/app/Services/DeterministicEnergyEngine.php#L439-L478):
  ```php
  'payback_period_tahun' => 0.13, // Hardcoded for Low-Cost #1
  ...
  'payback_period_tahun' => 0.17, // Hardcoded for Low-Cost #2
  ...
  'payback_period_tahun' => 2.47, // Hardcoded for Medium-Cost
  ...
  'payback_period_tahun' => 4.92, // Hardcoded for High-Cost
  ```
- **Requirement Affected:** FR-04, Permen ESDM No. 8 Tahun 2025 Lampiran III
- **User/Business Impact:** On the `wajib` preset, Medium-Cost Capex is Rp 120,000,000 and monthly savings are Rp 68,297,890. The actual simple payback period is $\frac{120,000,000}{68,297,890 \times 12} = 0.146\text{ years}$ (under 2 months, which qualifies as Low-Cost!). However, the system reports `Payback: 2.47 Tahun` and retains it in `Medium-Cost (2-4 Tahun)`.
- **Reproduction Steps:**
  1. Query `analyze('wajib')`.
  2. Inspect the returned `klasifikasi_rekomendasi_audit_esdm` array.
  3. Compare `estimasi_biaya_implementasi_rp` against `potensi_hemat_rp_per_bulan * 12` and compare with `payback_period_tahun`.
- **Recommended Fix:** Calculate payback period dynamically:  
  `$payback = ($annualSaving > 0) ? round($capex / $annualSaving, 2) : 0.0;`  
  Dynamically assign the category based on Permen ESDM 8/2025 thresholds (0 = No-Cost, < 2 = Low-Cost, 2–4 = Medium-Cost, > 4 = High-Cost).
- **Estimated Effort:** **S** (Small, ~2 hours)
- **Confidence:** **High** (Verified via code and tinker execution)

---

#### Finding F-06 (P1 — High)
- **Finding ID:** `F-06`
- **Severity:** **P1 — High**
- **Category:** Reporting & Export / FR-08 Compliance
- **Title:** Missing Binary PDF and Excel Export Functionality; Route Returns HTML Print Page
- **Evidence:**  
  [`IeecDashboardController.php:285-298`](file:///Users/yusuf/Documents/Project/Angel-Hack/app/Http/Controllers/IeecDashboardController.php#L285-L298):
  ```php
  public function exportReport(Request $request): Response
  {
      ...
      return response()->view('reports.esdm_audit_worksheet', [...])->header('Content-Type', 'text/html');
  }
  ```
  `composer.json` contains no PDF library (such as `dompdf` or `snappy`) and no Excel library (such as `phpspreadsheet` or `laravel-excel`).
- **Requirement Affected:** FR-08 (Generator Lembar Kerja Audit Energi ESDM)
- **User/Business Impact:** PRD explicitly specifies "Supported PDF/Excel formats". Energy Managers cannot download a downloadable `.xlsx` spreadsheet for external auditor reconciliation or a formal PDF document.
- **Reproduction Steps:**
  1. Click "Export Lembar Kerja Audit" in the UI.
  2. Browser navigates to `/export-audit` and displays an HTML webpage with a print button.
- **Recommended Fix:** Install `barryvdh/laravel-dompdf` for clean PDF generation and stream binary PDF when `?format=pdf` is requested, and generate `.csv` / `.xlsx` when `?format=excel` is requested.
- **Estimated Effort:** **M** (Medium, ~4 hours)
- **Confidence:** **High** (Verified via controller and package manifests)

---

#### Finding F-07 (P1 — High)
- **Finding ID:** `F-07`
- **Severity:** **P1 — High**
- **Category:** Domain Calculation & Timeseries Modeling / ISO 50001
- **Title:** Timeseries Baseline Model Discards Real OLS Regression Slope and Uses Arbitrary Constants
- **Evidence:**  
  In [`DeterministicEnergyEngine.php:503-523`](file:///Users/yusuf/Documents/Project/Angel-Hack/app/Services/DeterministicEnergyEngine.php#L503-L523):
  ```php
  $e0Total = 0.0;
  $a1 = 0.25;
  $a2 = 0.20;
  ...
  $expectedBase = $e0Total + ($a1 * $prod1) + ($a2 * $prod2);
  // Add lighting / building baseline
  $expectedBase += 9.0;
  ```
- **Requirement Affected:** FR-02, ISO 50001:2018 (CUSUM & EnPI monitoring)
- **User/Business Impact:** The CUSUM and baseline curve in the main ApexCharts visualizer do not use the regression parameters computed by `calculateOlsBaseline()`. If an uploaded dataset has a completely different production-to-energy correlation (e.g. $a = 0.45$), the chart baseline will be completely detached from the mathematical model.
- **Reproduction Steps:**
  1. Inspect `calculateOlsBaseline()`: it computes $a$ and $E_0$.
  2. Inspect `buildTimeseriesAndAnomalies()`: it takes `$panelStats`, but `$panelStats` only contains `base_load_e0_kwh`, omitting $a$.
  3. Notice $a_1=0.25, a_2=0.20$ and $+9.0$ are hardcoded.
- **Recommended Fix:** Include the OLS slope $a$ in the `$analisisPanels` structure, and feed each panel's actual regression slope and base load into the hourly baseline generator.
- **Estimated Effort:** **S** (Small, ~2 hours)
- **Confidence:** **High** (Verified via code inspection)

---

#### Finding F-08 (P2 — Medium)
- **Finding ID:** `F-08`
- **Severity:** **P2 — Medium**
- **Category:** Telemetry & Frontend Interactivity / FR-05 Compliance
- **Title:** IoT Live Simulation Updates Banner DOM Elements but Fails to Synchronize ApexCharts Timeseries
- **Evidence:**  
  [`resources/views/dashboard.blade.php:2716-2727`](file:///Users/yusuf/Documents/Project/Angel-Hack/resources/views/dashboard.blade.php#L2716-L2727):
  ```javascript
  async function pollIotData() {
      try {
          const res = await fetch("{{ route('iot.stream', ['preset' => $preset]) }}");
          const data = await res.json();
          document.getElementById('iotTime').textContent = data.timestamp + ' WIB';
          document.getElementById('iotKw').textContent = data.total_active_kw.toLocaleString('id-ID');
          document.getElementById('iotKvar').textContent = data.total_reactive_kvar.toLocaleString('id-ID');
          document.getElementById('iotPf').textContent = data.aggregate_cos_phi;
      } catch (e) { ... }
  }
  ```
- **Requirement Affected:** FR-05 (Mockup Live IoT Telemetry Streaming)
- **User/Business Impact:** When the user turns on the "IoT Live" toggle, the banner updates every 3 seconds, but the time-series chart below remains completely static showing historical data. Operators expect the chart to update in sync with incoming telemetry.
- **Reproduction Steps:**
  1. Click "IoT Live: OFF" toggle button on the overview tab.
  2. Observe the top banner values cycling every 3 seconds.
  3. Observe that the ApexCharts graph does not plot any new streaming points.
- **Recommended Fix:** Append incoming `data.total_active_kw` to `chart.w.globals.series[0]` and shift categories to simulate a sliding real-time window.
- **Estimated Effort:** **S** (Small, ~2 hours)
- **Confidence:** **High** (Verified in browser test)

---

#### Finding F-09 (P2 — Medium)
- **Finding ID:** `F-09`
- **Severity:** **P2 — Medium**
- **Category:** Regulatory Parameters & Configuration / FR-07 Compliance
- **Title:** WBP Operating Hours Are Hardcoded to 18:00–22:00 in Code, Ignoring Configuration Values
- **Evidence:**  
  [`DeterministicEnergyEngine.php:238-242`](file:///Users/yusuf/Documents/Project/Angel-Hack/app/Services/DeterministicEnergyEngine.php#L238-L242):
  ```php
  $h = (int) Carbon::parse($r->timestamp)->format('H');
  return $r->panel_id === 'SDP-02_UTILITY' && ($h >= 18 && $h < 22);
  ```
  And line 530:
  ```php
  $isWbp = ($h >= 18 && $h < 22);
  ```
  And in [`IeecDashboardController.php:241`](file:///Users/yusuf/Documents/Project/Angel-Hack/app/Http/Controllers/IeecDashboardController.php#L241):
  ```php
  $isWbp = ((int) $now->format('H') >= 18 && (int) $now->format('H') < 22);
  ```
- **Requirement Affected:** FR-07 (Jam WBP Mulai & Selesai)
- **User/Business Impact:** Changing WBP hours in the regulatory settings form (e.g., to 17:00–21:00 or 19:00–23:00 for regional grid variations) has zero effect on the system. The calculation engine and IoT simulator continue checking hardcoded hours 18 to 21.
- **Reproduction Steps:**
  1. Change WBP hours to 17:00–21:00 in FR-07 settings.
  2. Run analysis.
  3. Notice WBP shiftable calculation still filters on $18 \le h < 22$.
- **Recommended Fix:** Parse `$config->jam_wbp_mulai` and `$config->jam_wbp_selesai` dynamically into start and end hour integers and use them in all filter conditions.
- **Estimated Effort:** **S** (Small, ~1 hour)
- **Confidence:** **High** (Verified via code inspection)

---

#### Finding F-10 (P2 — Medium)
- **Finding ID:** `F-10`
- **Severity:** **P2 — Medium**
- **Category:** Architecture & Offline Resiliency
- **Title:** Blade Views Depend on External CDNs for Tailwind CSS and ApexCharts, Causing Breakdown in Offline/Air-Gapped Environments
- **Evidence:**  
  [`resources/views/dashboard.blade.php:10, 51`](file:///Users/yusuf/Documents/Project/Angel-Hack/resources/views/dashboard.blade.php#L10):
  ```html
  <script src="https://cdn.tailwindcss.com"></script>
  <script src="https://cdn.jsdelivr.net/npm/apexcharts"></script>
  ```
  [`resources/views/reports/esdm_audit_worksheet.blade.php:8`](file:///Users/yusuf/Documents/Project/Angel-Hack/resources/views/reports/esdm_audit_worksheet.blade.php#L8):
  ```html
  <script src="https://cdn.tailwindcss.com"></script>
  ```
- **Requirement Affected:** NFR-04 (Offline & Air-Gapped Resiliency), PRD Section 1.3 Pillar 5
- **User/Business Impact:** In an industrial plant control room with air-gapped or restricted internet access, the application UI will lose all CSS styling and ApexCharts will fail to render, rendering the application unusable.
- **Reproduction Steps:**
  1. Disconnect network or block `cdn.tailwindcss.com` and `cdn.jsdelivr.net`.
  2. Reload `http://127.0.0.1:8000`.
  3. Observe unstyled HTML and blank chart area.
- **Recommended Fix:** Bundle Tailwind CSS and ApexCharts locally via Vite (`npm install apexcharts` and `@vite(['resources/css/app.css', 'resources/js/app.js'])`).
- **Estimated Effort:** **S** (Small, ~2 hours)
- **Confidence:** **High** (Verified via template inspection)

---

#### Finding F-11 (P2 — Medium)
- **Finding ID:** `F-11`
- **Severity:** **P2 — Medium**
- **Category:** User Experience & Navigation
- **Title:** Recalculating Non-Electric Thermal Energy Kicks User Out of Compliance Tab to Overview
- **Evidence:**  
  [`resources/views/dashboard.blade.php:1168-1181`](file:///Users/yusuf/Documents/Project/Angel-Hack/resources/views/dashboard.blade.php#L1168-L1181):
  ```html
  <form action="{{ route('dashboard') }}" method="GET" class="flex flex-wrap items-center gap-3 text-xs">
      <input type="hidden" name="preset" value="{{ $preset }}">
      <input type="number" step="10" name="non_electric_toe" ...>
      <button type="submit">Hitung Ulang Status Regulasi</button>
  </form>
  ```
  The form does not pass `tab=compliance`.
- **Requirement Affected:** FR-06, UX Workflow
- **User/Business Impact:** When an auditor or energy manager inputs biomass thermal energy and clicks "Hitung Ulang Status Regulasi", the page reloads and displays the Overview tab. The user must manually click back to the "Kepatuhan" tab to see the updated legal status.
- **Reproduction Steps:**
  1. Switch to *Kepatuhan & Audit* tab.
  2. Input 3,800 TOE and click "Hitung Ulang Status Regulasi".
  3. Observe dashboard redirects to `#overview`.
- **Recommended Fix:** Add `<input type="hidden" name="tab" value="compliance">` inside the form.
- **Estimated Effort:** **S** (Small, 5 minutes)
- **Confidence:** **High** (Verified in browser test)

---

#### Finding F-12 (P2 — Medium)
- **Finding ID:** `F-12`
- **Severity:** **P2 — Medium**
- **Category:** Anomaly Detection Methodology / Statistical Rigor
- **Title:** Z-Score Anomaly Calculation Lumps Different Panel Typologies Together
- **Evidence:**  
  [`DeterministicEnergyEngine.php:224-235`](file:///Users/yusuf/Documents/Project/Angel-Hack/app/Services/DeterministicEnergyEngine.php#L224-L235):
  ```php
  $opLogs = $logs->filter(fn ($r) => $r->shift_state === 'OPERATIONAL');
  $meanOp = $opLogs->avg('energy_kwh') ?: 0;
  $variance = ...;
  $stdOp = sqrt($variance);
  ```
- **Requirement Affected:** FR-03 (PRD Section 3.4: $Z_t = \frac{E_{\text{aktual}, t} - \mu_{\text{jam\_serupa}}}{\sigma_{\text{jam\_serupa}}}$)
- **User/Business Impact:** Lumping 75 kW crusher motors with 8 kW office AC panels produces a skewed $\mu$ and high $\sigma$. Consequently, real spikes on building panels never exceed $Z > 2.0$, while normal variations on heavy motor panels are misclassified.
- **Reproduction Steps:**
  1. Inspect the collection `$opLogs`: it includes all 4 panels.
  2. Notice single mean and standard deviation are computed across mixed panels.
- **Recommended Fix:** Group `$opLogs` by `panel_id` and calculate panel-specific mean and standard deviation before filtering $Z_t > 2.0$.
- **Estimated Effort:** **S** (Small, ~2 hours)
- **Confidence:** **High** (Verified via code analysis)

---

#### Finding F-13 (P3 — Low)
- **Finding ID:** `F-13`
- **Severity:** **P3 — Low**
- **Category:** Regulatory Compliance & Legal Rigor
- **Title:** Fabricated Auditor Registration Credentials in Printable Audit Worksheet
- **Evidence:**  
  [`resources/views/reports/esdm_audit_worksheet.blade.php:223-230`](file:///Users/yusuf/Documents/Project/Angel-Hack/resources/views/reports/esdm_audit_worksheet.blade.php#L223-L230):
  ```html
  <div class="font-semibold text-slate-900">( Ir. Bambang S., IPM, CEM )</div>
  <div class="text-[10px] text-slate-500">Reg. BNSP: EN-2024-0982-ID</div>
  ...
  <div class="font-semibold text-slate-900">( Dr. Eng. Haryanto, CEA )</div>
  <div class="text-[10px] text-slate-500">Reg. EBTKE: AUD-2025-1104</div>
  ```
- **Requirement Affected:** FR-08, Legal & Regulatory Integrity
- **User/Business Impact:** Presenting fabricated registration numbers on a document labeled "LEMBAR KERJA AUDIT ENERGI BERKALA SESUAI KETENTUAN PERMEN ESDM NO. 8 TAHUN 2025" poses a compliance and reputational risk if distributed outside a mock competition context.
- **Recommended Fix:** Replace fictitious names with configurable or generic placeholder fields: `( [Nama Manajer Energi Bersertifikat] ) Reg. BNSP: [Nomor Registrasi]`, accompanied by a watermark / disclaimer: *"DOKUMEN SIMULASI — BUKAN LAPORAN AUDIT RESMI"*.
- **Estimated Effort:** **S** (Small, ~30 minutes)
- **Confidence:** **High** (Verified via template inspection)
