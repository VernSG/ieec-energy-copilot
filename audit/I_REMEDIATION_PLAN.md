# DELIVERABLE I — REMEDIATION PLAN & IMPLEMENTATION ROADMAP
## Industrial Energy Efficiency Copilot (IEEC) — Prototype Alpha v1.2
**Audit Objective:** Actionable, sequenced technical remediation plan with explicit acceptance criteria.

---

### 1. Sequenced Remediation Phases

```text
+-----------------------------------------------------------------------------------+
| PHASE 1: PRE-DEMO INTEGRITY & STABILIZATION (Target: Prior to Hackathon Pitch)    |
| • F-01: Remove hardcoded calculation bypasses in DeterministicEnergyEngine.       |
| • F-02: Fix foreign key constraint crash on unknown panel upload.                 |
| • F-04: Dynamic narrative template binding in AiCopilotService.                   |
| • F-05: Dynamic simple payback period formula in recommendation matrix.           |
| • F-09: Dynamic WBP hour filter parameterization.                                 |
| • F-11: Preserve active tab on thermal energy recalculation form.                 |
+-----------------------------------------------------------------------------------+
                                          |
                                          v
+-----------------------------------------------------------------------------------+
| PHASE 2: PRE-PILOT SECURITY, EXPORT & RESILIENCE (Target: Prior to Indmira Pilot) |
| • F-03: Route authentication & API chat rate limiting.                           |
| • F-06: Native binary PDF & Excel report export generation.                       |
| • F-07: Wire OLS regression slope into timeseries baseline & CUSUM series.       |
| • F-08: Synchronize IoT streaming with ApexCharts timeseries canvas.             |
| • F-10: Local bundle of Tailwind CSS & ApexCharts via Vite (air-gap ready).       |
| • F-12: Panel-specific grouping for statistical Z-score spike calculation.        |
+-----------------------------------------------------------------------------------+
                                          |
                                          v
+-----------------------------------------------------------------------------------+
| PHASE 3: INDUSTRIAL EXPANSION & M&V (Target: Q1 2027 Commercial Scale)            |
| • Regulatory parameter version history audit logging table.                       |
| • Measurement & Verification (M&V) post-action savings tracking module.           |
| • Modbus-TCP / MQTT industrial telemetry gateway ingestion.                       |
+-----------------------------------------------------------------------------------+
```

---

### 2. Detailed Task Breakdown & Acceptance Criteria

#### Task 1.1 — Remove Hardcoded Calculation Overrides on Preset `sukarela` (Fix for F-01)
- **Target File:** [`app/Services/DeterministicEnergyEngine.php`](file:///Users/yusuf/Documents/Project/Angel-Hack/app/Services/DeterministicEnergyEngine.php#L118-L133)
- **Dependencies:** None
- **Effort:** **S** (~2 hours)
- **Implementation Action:**
  1. Remove lines 118–133 (`if ($isStandardSukarela) { ... }`).
  2. Remove lines 213–217 (`if ($isStandardSukarela) { $idleWasteKwh = ... }`).
  3. Remove lines 259–270 (`if ($isStandardSukarela) { $hematEliminasiAktifRp = ... }`).
  4. Allow the computed OLS values, idle waste, and tariff products to flow dynamically into `$payload`.
- **Explicit Acceptance Criteria:**
  - [ ] Changing `tarif_lwbp_rp_kwh` to 2,000 in *Konfigurasi Regulasi* recalculates total savings on `sukarela` from Rp 5,609,586 to ~Rp 9.5 Million.
  - [ ] $E_0$ and $R^2$ values are sourced directly from `calculateOlsBaseline()`.
  - [ ] All automated tests in `EnergyEfficiencyTest.php` pass without relying on hardcoded literals.

---

#### Task 1.2 — Resolve CSV Foreign Key Constraint Crash (Fix for F-02)
- **Target File:** [`app/Http/Controllers/IeecDashboardController.php:168-198`](file:///Users/yusuf/Documents/Project/Angel-Hack/app/Http/Controllers/IeecDashboardController.php#L168-L198)
- **Dependencies:** None
- **Effort:** **S** (~2 hours)
- **Implementation Action:**
  1. During CSV parsing in `uploadCsv()`, track all unique `panel_id` and `category` values encountered in the file.
  2. For each unique panel, execute `SdpPanel::firstOrCreate(['panel_id' => $panelId], ['category' => $cat, 'deskripsi' => $panelId])` before inserting rows into `energy_telemetry_logs`.
  3. Alternatively, update the foreign key migration to set null or soft constraint for custom uploads.
- **Explicit Acceptance Criteria:**
  - [ ] Uploading a CSV with arbitrary panel identifiers (e.g. `SDP-09_BOILER`) ingests all rows successfully without throwing SQLite constraint errors.
  - [ ] New panels appear in the SDP monitoring table with calculated consumption and IKE.

---

#### Task 1.3 — Dynamic Template Interpolation in AI Executive Summary (Fix for F-04)
- **Target File:** [`app/Services/AiCopilotService.php:49-51`](file:///Users/yusuf/Documents/Project/Angel-Hack/app/Services/AiCopilotService.php#L49-L51)
- **Dependencies:** None
- **Effort:** **S** (~2 hours)
- **Implementation Action:**
  1. Replace hardcoded strings in `generateExecutiveAuditNarrative()` with dynamic data lookups from `$payload['analisis_ike_per_panel']`.
  2. Format output dynamically: `number_format($panel['ike_aktual'], 2, ',', '.')`, `number_format($panel['base_load_e0_kwh'] ?? 0, 1, ',', '.')`, etc.
- **Explicit Acceptance Criteria:**
  - [ ] When viewing `?preset=wajib`, the Executive Summary narrative describes Line A with `1.810.563 kWh` and correct large-scale baseline metrics rather than 0.52 kWh/batch.
  - [ ] When viewing `?preset=sukarela`, values match the pilot plant scale.

---

#### Task 1.4 — Dynamic Payback Period Formula Evaluation (Fix for F-05)
- **Target File:** [`app/Services/DeterministicEnergyEngine.php:415-480`](file:///Users/yusuf/Documents/Project/Angel-Hack/app/Services/DeterministicEnergyEngine.php#L415-L480)
- **Dependencies:** Task 1.1
- **Effort:** **S** (~2 hours)
- **Implementation Action:**
  1. Define simple payback period dynamically for each action:  
     `$annualSaving = $monthlySaving * 12;`  
     `$payback = ($annualSaving > 0) ? round($capex / $annualSaving, 2) : 0.0;`
  2. Assign investment tier dynamically according to Permen ESDM 8/2025:
     - Payback = 0 $\rightarrow$ `No-Cost`
     - $0 < \text{Payback} < 2.0 \rightarrow$ `Low-Cost`
     - $2.0 \le \text{Payback} \le 4.0 \rightarrow$ `Medium-Cost`
     - $\text{Payback} > 4.0 \rightarrow$ `High-Cost`
- **Explicit Acceptance Criteria:**
  - [ ] Recommendations dynamically re-categorize if Capex or savings change.
  - [ ] On `wajib` preset, actions with 2-month payback are correctly classified as `Low-Cost` instead of mislabeled `Medium-Cost`.

---

#### Task 1.5 — Dynamic WBP Hours Parameterization (Fix for F-09)
- **Target Files:** [`app/Services/DeterministicEnergyEngine.php`](file:///Users/yusuf/Documents/Project/Angel-Hack/app/Services/DeterministicEnergyEngine.php), [`app/Http/Controllers/IeecDashboardController.php`](file:///Users/yusuf/Documents/Project/Angel-Hack/app/Http/Controllers/IeecDashboardController.php)
- **Dependencies:** None
- **Effort:** **S** (~1 hour)
- **Implementation Action:**
  1. Parse `$startH = (int) explode(':', $config->jam_wbp_mulai)[0];` and `$endH = (int) explode(':', $config->jam_wbp_selesai)[0];`.
  2. Replace hardcoded `($h >= 18 && $h < 22)` with `($h >= $startH && $h < $endH)`.
- **Explicit Acceptance Criteria:**
  - [ ] Setting WBP hours to 17:00–21:00 shifts the anomaly detection and load shifting calculations to hours 17, 18, 19, and 20.

---

#### Task 1.6 — Preserve Active Tab on Compliance Form Submit (Fix for F-11)
- **Target File:** [`resources/views/dashboard.blade.php:1168-1181`](file:///Users/yusuf/Documents/Project/Angel-Hack/resources/views/dashboard.blade.php#L1168-L1181)
- **Dependencies:** None
- **Effort:** **S** (15 minutes)
- **Implementation Action:**
  1. Add `<input type="hidden" name="tab" value="compliance">` to the recalculate form.
- **Explicit Acceptance Criteria:**
  - [ ] Submitting non-electric thermal energy recalculation keeps the user on the `#compliance` tab.

---

#### Task 2.1 — Authentication & Authorization Enforcement (Fix for F-03)
- **Target Files:** [`routes/web.php`](file:///Users/yusuf/Documents/Project/Angel-Hack/routes/web.php), `app/Http/Middleware/`
- **Dependencies:** None
- **Effort:** **M** (~4 hours)
- **Implementation Action:**
  1. Scaffold basic Laravel session auth (`breeze` or lightweight auth middleware).
  2. Protect `/config` and `/upload` routes with `auth` middleware.
  3. Apply `throttle:20,1` rate limiting on `/chat` route.
- **Explicit Acceptance Criteria:**
  - [ ] Unauthenticated POST requests to `/config` or `/upload` return HTTP 401 or redirect to login.
  - [ ] Rapid automated requests (> 20/min) to `/chat` receive HTTP 429 Too Many Requests.

---

#### Task 2.2 — Native PDF & Excel File Export (Fix for F-06)
- **Target Files:** `composer.json`, [`app/Http/Controllers/IeecDashboardController.php`](file:///Users/yusuf/Documents/Project/Angel-Hack/app/Http/Controllers/IeecDashboardController.php)
- **Dependencies:** None
- **Effort:** **M** (~4 hours)
- **Implementation Action:**
  1. Require `barryvdh/laravel-dompdf` in `composer.json`.
  2. In `exportReport()`, if `$request->query('format') === 'pdf'`, render and stream binary PDF download.
  3. If `$request->query('format') === 'excel'`, stream a formatted CSV or XLSX file.
- **Explicit Acceptance Criteria:**
  - [ ] Clicking "Download PDF" streams a valid binary `.pdf` file with proper page breaks and styling.
  - [ ] Clicking "Download Excel" streams a `.csv` / `.xlsx` file containing panel metrics and anomaly logs.

---

#### Task 2.3 — Timeseries Baseline Integration (Fix for F-07)
- **Target File:** [`app/Services/DeterministicEnergyEngine.php:486-574`](file:///Users/yusuf/Documents/Project/Angel-Hack/app/Services/DeterministicEnergyEngine.php#L486-L574)
- **Dependencies:** Task 1.1
- **Effort:** **S** (~2 hours)
- **Implementation Action:**
  1. Store the OLS slope `$a` in the panel stats array.
  2. In `buildTimeseriesAndAnomalies()`, look up each panel's actual regression slope and compute `$expectedBase = $e0Total + ($a1 * $prod1) + ($a2 * $prod2) + $buildingBase;`.
- **Explicit Acceptance Criteria:**
  - [ ] The baseline series in ApexCharts dynamically responds to changes in production efficiency coefficients.

---

#### Task 2.4 — Local Asset Bundling via Vite (Fix for F-10)
- **Target Files:** [`package.json`](file:///Users/yusuf/Documents/Project/Angel-Hack/package.json), [`resources/js/app.js`](file:///Users/yusuf/Documents/Project/Angel-Hack/resources/js/app.js), [`resources/views/dashboard.blade.php`](file:///Users/yusuf/Documents/Project/Angel-Hack/resources/views/dashboard.blade.php)
- **Dependencies:** None
- **Effort:** **S** (~2 hours)
- **Implementation Action:**
  1. Install ApexCharts via npm (`npm i apexcharts`).
  2. Import ApexCharts in `resources/js/app.js` and mount it to `window.ApexCharts`.
  3. Replace CDN script tags in Blade views with `@vite(['resources/css/app.css', 'resources/js/app.js'])`.
- **Explicit Acceptance Criteria:**
  - [ ] Application loads with complete styling and interactive charts with computer disconnected from internet.
