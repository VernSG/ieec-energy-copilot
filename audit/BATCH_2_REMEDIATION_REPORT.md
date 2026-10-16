# IEEC REMEDIATION REPORT — BATCH 2: DATA RELIABILITY AND ANALYTICAL CONSISTENCY

**Document Version:** 1.0  
**Date:** October 16, 2026  
**Auditor / Engineer:** Multidisciplinary IEEC Engineering Review Team  
**Scope:** Remediation of Findings F-02, F-04, F-09, and F-12 from the IEEC Comprehensive Audit Register  
**Status:** **BATCH 2 VERIFIED & FIXED**

---

## 1. Executive Summary

Following the completion and review of Batch 1 (Deterministic Calculation Integrity: F-01, F-05, F-07), **Batch 2: Data Reliability and Analytical Consistency** has been implemented, validated, and rigorously tested.

Batch 2 remediates four critical data pipeline and consistency vulnerabilities:
1. **F-02 (P0 — Critical):** Fatal SQLite foreign key constraint exception during custom CSV telemetry ingestion when encountering previously uncataloged industrial panel identifiers.
2. **F-04 (P1 — High):** Static pilot plant analytical narratives and hardcoded diagnostic text injected into the AI Copilot executive summary and offline fallback across all facility datasets.
3. **F-09 (P2 — Medium):** Hardcoded WBP (Waktu Beban Puncak) operating hours ($18 \le H < 22$) embedded across the calculation engine and IoT telemetry simulator, ignoring user-configured regulatory settings.
4. **F-12 (P2 — Medium):** Plant-wide aggregation in Z-score anomaly detection that lumped distinct panel typologies together, applied arbitrary hardcoded plant thresholds ($>95\text{ kW}$), and falsely attributed all detected anomalies to `SDP-01_LINE_A`.

All Batch 1 fixes, regression fixtures, and calculations remain 100% preserved. The automated test suite now comprises **29 comprehensive feature and regression tests with 212 assertions passing (100% success rate)**.

---

## 2. Findings Addressed & Verification Status

| Finding ID | Severity | Category | Pre-Remediation State | Post-Remediation State | Verification Status |
| :--- | :--- | :--- | :--- | :--- | :---: |
| **F-02** | **P0 — Critical** | Database Schema & CSV Ingestion | Direct bulk insert into `energy_telemetry_logs` failed with `QueryException 23000 (FOREIGN KEY constraint failed)` whenever custom panel IDs were uploaded. Missing data type, timestamp, range, and duplicate validations. | Atomic transaction discovering distinct panel IDs and auto-registering unknown panels in `sdp_panels` with proper default baselines. Strict validation of 9-column headers, parseable timestamps, numeric non-negatives, power factor bounds ($[0.0, 1.0]$), category enums, and duplicate `(timestamp, panel_id)` detection. | **VERIFIED FIXED** |
| **F-04** | **P1 — High** | AI Systems & Presentation Integrity | `AiCopilotService::generateExecutiveAuditNarrative()` hardcoded static strings asserting Line Pirolisis had $0.52\text{ kWh/batch}$ and $E_0 = 22.4\text{ kW}$ even on commercial plant ($3.9\text{M kWh}$) or custom data. Fallback chat methods hardcoded static numbers. | Fully dynamic narrative interpolation iterating `$payload['analisis_ike_per_panel']`, deriving true SEC, EnPI deviation %, OLS base load $E_0$, $\cos \varphi$, and building compliance. `deterministicKnowledgeFallback()` dynamically retrieves metrics from `$payload`. Zero hardcoded facility values. | **VERIFIED FIXED** |
| **F-09** | **P2 — Medium** | Regulatory Parameters & Grid Calibration | Calculation engine (`DeterministicEnergyEngine`) and IoT streamer (`IeecDashboardController::streamIot`) hardcoded condition `$h >= 18 && $h < 22`, completely ignoring `jam_wbp_mulai` and `jam_wbp_selesai` from `RegulatoryConfig`. | Implemented centralized `DeterministicEnergyEngine::isTimestampInWbp()` supporting configurable intra-day windows, custom hours (e.g. 17:00–21:00), and cross-midnight windows (e.g. 22:00–04:00). Unified across engine, timeseries builder, and live IoT streamer. | **VERIFIED FIXED** |
| **F-12** | **P2 — Medium** | Anomaly Detection & Statistical Rigor | Z-score was computed by averaging operational logs across mixed panel typologies. Timeseries anomaly builder checked arbitrary plant-wide sums (`$sumActual > 95.0`) and attributed all anomaly logs to hardcoded `SDP-01_LINE_A` or `SDP-02_UTILITY` with zero or distorted losses. | Precomputes panel-specific operational $\mu_p$ and $\sigma_p$. Evaluates anomalies on individual panel records: statistical spikes when $Z_p = \frac{E - \mu_p}{\sigma_p} > 2.0$, idle waste during `BREAK` above standby, and WBP peaks during configurable hours. Logs directly attributed to actual `panel_id` with non-zero financial and kWh losses. | **VERIFIED FIXED** |

---

## 3. Detailed Root Cause Analysis & Implemented Fixes

### 3.1 Finding F-02: CSV Ingestion & Unknown Panel Support

#### Root Cause
In migration `2026_10_15_000003_create_energy_telemetry_logs_table.php`, a foreign key constraint was defined:
```php
$table->string('panel_id')->index();
$table->foreign('panel_id')->references('panel_id')->on('sdp_panels')->cascadeOnDelete();
```
In `IeecDashboardController::uploadCsv()`, the controller parsed CSV rows and immediately executed:
```php
DB::table('energy_telemetry_logs')->insert($batch);
```
If a user uploaded data from a plant containing panel identifiers not in the four seeded panels (`SDP-01_LINE_A`, `SDP-02_UTILITY`, `SDP-03_GEDUNG_UTAMA`, `SDP-04_GUDANG_FEEDSTOCK`), SQLite immediately aborted the insert with a fatal foreign key constraint violation. Furthermore, the upload handler lacked data type checks (accepting negative numbers or invalid timestamps) and failed to detect duplicate records.

#### Fix Implemented
1. **Schema & Header Validation:**
   Enforced strict presence of the 9 standard columns: `['timestamp', 'panel_id', 'category', 'energy_kwh', 'power_factor', 'kvarh', 'production_qty', 'area_sqm', 'shift_state']`.
2. **Row-by-Row Sanitization & Type Validation:**
   - `timestamp`: Verified parseable via `Carbon::parse($val)`. Malformed dates return line-specific error.
   - `panel_id`: Non-empty string.
   - `category`: Must belong to valid enum `['PRODUCTION', 'BUILDING_AC', 'BUILDING_NON_AC']`.
   - `energy_kwh`, `kvarh`, `production_qty`, `area_sqm`: Validated as numeric and $\ge 0$.
   - `power_factor`: Validated as numeric between $0.0$ and $1.0$.
   - `duplicate detection`: Hash table tracking `"$timestamp|$panelId"` prevents duplicate telemetry records.
3. **Explicit Unknown Panel Registration:**
   All distinct panels in the upload are discovered. Inside a database transaction, `SdpPanel::firstOrCreate()` auto-registers unknown panels before inserting logs, preserving panel identity and setting calibrated default baselines:
   ```php
   SdpPanel::firstOrCreate(
       ['panel_id' => $pId],
       [
           'deskripsi' => ucwords(str_replace(['_', '-'], ' ', $pId)),
           'category' => $meta['category'],
           'baseline_enb' => ($meta['category'] === 'PRODUCTION') ? 0.35 : null,
           'default_area_sqm' => ($meta['area_sqm'] > 0) ? $meta['area_sqm'] : (($meta['category'] === 'PRODUCTION') ? 0.0 : 600.0),
       ]
   );
   ```
4. **Dynamic Engine Support:**
   Updated `DeterministicEnergyEngine::analyze()` to dynamically iterate `$panelGroups->keys()` rather than a static list of 4 panels, ensuring custom uploaded panels are immediately analyzed and displayed.

---

### 3.2 Finding F-04: Grounding AI Copilot Narratives in Active Telemetry

#### Root Cause
In `AiCopilotService.php:49-51`, the server-rendered executive narrative template had hardcoded diagnostic list items:
```php
$html .= '<li><strong class="text-rose-700">SDP-01 (Line Pirolisis):</strong> IKE aktual <span class="font-mono text-slate-900">0,52 kWh/batch</span> (<strong class="text-rose-700">+48,57%</strong> di atas baseline EnB 0,35). Base Load $E_0 = 22,4$ kW...</li>';
$html .= '<li><strong class="text-amber-700">SDP-02 (Utilitas):</strong> IKE aktual <span class="font-mono text-slate-900">0,39 kWh/batch</span>... K = 1,40...</li>';
```
In `deterministicKnowledgeFallback()` (lines 295–330), offline responses also hardcoded static strings (`"Base Load E0 sebesar 22,4 kW"`, `"2.450 kWh/bulan"`, `"Rp 2.537.661/bulan"`). When viewing the commercial `wajib` plant ($1,810,563\text{ kWh}$ on SDP-01 and $E_0 = 1,764.2\text{ kW}$) or custom uploaded datasets, the AI text asserted pilot plant figures, creating a glaring numerical contradiction.

#### Fix Implemented
1. **Dynamic Narrative Panel Iteration:**
   In `generateExecutiveAuditNarrative(array $payload)`:
   - Completely removed static HTML strings for SDP-01, SDP-02, SDP-03, and SDP-04.
   - Iterates dynamically through `$payload['analisis_ike_per_panel']`.
   - For production panels: displays panel ID, description, calculated `ike_aktual`, benchmark baseline comparison, % deviation, computed OLS base load $E_0$, and measured $\cos \varphi$. WBP hours and $K$ factor are interpolated from active regulatory configuration.
   - For building panels: displays normalized monthly IKE ($\text{kWh/m}^2/\text{bulan}$), total area, and SNI 6196 / Permen ESDM 3/2025 performance tier (`EFISIEN`, `CUKUP EFISIEN`, `BOROS`).
2. **Deterministic Offline Fallback Grounding:**
   In `deterministicKnowledgeFallback(string $question, array $payload)`:
   - Interpolates actual production panel $E_0$, $\cos \varphi$, idle waste kWh, financial savings, and payback period from `$payload['klasifikasi_rekomendasi_audit_esdm']` and `$payload['temuan_pemborosan_dan_finansial']`.
   - On the `wajib` commercial plant, questions regarding base load truthfully reflect the scaled $E_0$ ($1,764.2\text{ kW}$), preserving mathematical fidelity.

---

### 3.3 Finding F-09: Configurable WBP Tariff Periods

#### Root Cause
In `DeterministicEnergyEngine.php:241` and line 600, WBP hours were hardcoded:
```php
$h = (int) Carbon::parse($r->timestamp)->format('H');
return $r->panel_id === 'SDP-02_UTILITY' && ($h >= 18 && $h < 22);
```
And in `IeecDashboardController.php:241`:
```php
$isWbp = ((int) $now->format('H') >= 18 && (int) $now->format('H') < 22);
```
Changing WBP hours in the regulatory settings form (`jam_wbp_mulai` and `jam_wbp_selesai`) had zero effect on the system; the engine and IoT streamer continued filtering exclusively on hours 18 to 21.

#### Fix Implemented
1. **Centralized Helper Method:**
   Added `DeterministicEnergyEngine::isTimestampInWbp(string|Carbon $timestamp, RegulatoryConfig $config): bool` supporting:
   - Standard intra-day windows (e.g. `18:00` to `22:00` -> true for $[18:00, 22:00)$).
   - Custom regional windows (e.g. `17:00` to `21:00`).
   - Cross-midnight windows (e.g. `22:00` to `04:00` -> true if time $\ge 22:00$ or time $< 04:00$).
   - Minute-level precision and flexible format parsing (`"18:00"` or `"18"`).
2. **Unified Integration:**
   - Used in `DeterministicEnergyEngine::analyze()` for WBP shiftable energy quantification.
   - Used in `buildTimeseriesAndAnomalies()` for WBP peak markers and anomaly logging.
   - Used in `IeecDashboardController::streamIot()` for real-time live simulation.

---

### 3.4 Finding F-12: Panel-Specific Anomaly Detection & Statistical Rigor

#### Root Cause
In `buildTimeseriesAndAnomalies()`, anomalies were evaluated using plant-wide sum thresholds:
```php
if ($sumActual > 95.0 && count($anomalyLogs) < 20) {
    $anomalyLogs[] = [
        'timestamp' => $timeKey.' WIB',
        'panel_id' => 'SDP-01_LINE_A',
        'type' => 'Spike Anomaly (Z > 2.0)',
        'description' => 'Lonjakan arus start crusher biomassa berulang melebihi batas 2-sigma',
        'loss_kwh' => round(max(0.0, $sumActual - $expectedBase), 1),
...
```
This produced three critical defects:
1. Plant-wide aggregate load was compared against `95.0`, an arbitrary threshold calibrated only for `sukarela`. On `wajib` (where hourly load is $\approx 5,000\text{ kW}$), every single hour triggered a spike, flagging midnight (`00:00`) with $0.0\text{ kWh}$ loss.
2. All anomalies were hardcoded to `SDP-01_LINE_A` or `SDP-02_UTILITY`.
3. Z-scores were not calculated per panel; building panels and crusher motors were lumped into a single distribution, distorting the standard deviation.

#### Fix Implemented
1. **Precomputed Operational Statistics per Panel:**
   For every active panel in the dataset, the engine filters operational records (`shift_state === 'OPERATIONAL'`) and computes:
   $$\mu_p = \frac{1}{n} \sum E_{p,t}, \quad \sigma_p = \sqrt{\frac{1}{n-1} \sum (E_{p,t} - \mu_p)^2}$$
2. **Panel-Specific Evaluation per Record:**
   - **Idle Load Waste (Shift Break):** Evaluated during `BREAK` on production panels when energy exceeds the $2.0\text{ kW}$ machine standby threshold. Loss is computed against standby limit ($E_{p,t} - 2.0$). Attributed to `$r->panel_id`.
   - **Statistical Spike Anomaly ($Z > 2.0$):** Evaluated on operational records using panel-specific Z-score:
     $$Z_{p,t} = \frac{E_{p,t} - \mu_p}{\sigma_p}$$
     If $Z_{p,t} > 2.0$, it is flagged as a Critical anomaly (`severity => 'danger'`). Expected energy is derived from the panel's OLS baseline ($a_p \cdot P + E_{0,p}$) or $\mu_p$. Loss is calculated as $\max(0.1, E_{p,t} - E_{\text{expected}})$.
   - **WBP Peak Load:** Evaluated during configurable WBP hours (`isTimestampInWbp`) for utility and high-load production panels when $E > 1.05 \cdot \mu_p$. Attributed to `$r->panel_id`.
3. **Accurate Anomaly Log Attributes:**
   Every anomaly log contains the actual `panel_id`, specific description citing the exact panel name and calculated Z-score, non-zero loss in kWh, and financial loss in Rupiah.

---

## 4. Before and After Behavior Comparison

| Component / Feature | Before Remediation | After Remediation |
| :--- | :--- | :--- |
| **CSV Ingestion with Unknown Panel** | Uploading CSV with `SDP-99_PELLETIZER` crashed with fatal SQL foreign key integrity exception (500). | Auto-registers `SDP-99_PELLETIZER` in `sdp_panels` with calibrated baseline; logs ingested successfully; engine analyzes custom panel. |
| **CSV Input Validation** | Accepted invalid timestamps, negative energy, and power factors $> 1.0$; silently accepted duplicate records. | Rejects malformed rows with line-specific errors; validates non-negatives and $[0, 1]$ PF range; detects and rejects duplicate `(timestamp, panel_id)` records. |
| **Executive Narrative on Commercial Plant (`wajib`)** | Asserted Line Pirolisis consumed $0.52\text{ kWh/batch}$ with base load $E_0 = 22.4\text{ kW}$, contradicting the 3.9M kWh table. | Dynamically renders all panels with true metrics: SDP-01 shows $1,810,563\text{ kWh}$ and $E_0 = 1,764.2\text{ kW}$. Zero contradictory text. |
| **Copilot Offline Chat Fallback** | Hardcoded static answer citing $E_0 = 22.4\text{ kW}$ and Rp 2.5M savings across all presets. | Dynamically interpolates values from active payload: returns $1,764.2\text{ kW}$ on `wajib`, $21.8\text{ kW}$ on `sukarela`. |
| **WBP Tariff Hours** | Changing `jam_wbp_mulai` to 17:00 had no effect; engine and live streamer hardcoded 18:00–22:00. | Setting 17:00–21:00 immediately shifts WBP filter window in engine calculations and live IoT streaming simulation. Supports cross-midnight windows. |
| **Anomaly Attribution** | 100% of anomalies attributed to `SDP-01_LINE_A`; triggered bogus midnight spikes on `wajib` with $0.0\text{ kWh}$ loss. | Panel-specific Z-scores ($Z_p > 2.0$) and idle detection attribute anomalies to their true panels (`SDP-01_LINE_A`, `SDP-02_UTILITY`, etc.) with non-zero losses. |

---

## 5. Verification Commands and Actual Results

### 5.1 Test Execution

The full test suite was executed via PHPUnit:

```bash
vendor/bin/phpunit
```

**Actual Output:**
```json
{"tool":"phpunit","result":"passed","tests":29,"passed":29,"assertions":212,"duration_ms":20053}
```

### 5.2 Breakdown of New Batch 2 Regression Tests

All 5 new regression tests in `tests/Feature/EnergyEfficiencyTest.php` passed on the first run:

1. **`test_f02_csv_upload_supports_previously_unknown_panel_ids_without_fk_exception` (PASS):**
   - Uploads synthetic CSV with unknown panels `SDP-99_NEW_FURNACE` (PRODUCTION) and `SDP-88_ANNEX_OFFICE` (BUILDING_AC).
   - Verifies auto-registration in `sdp_panels` with correct default baselines and areas.
   - Verifies telemetry logs inserted under `preset_source = 'custom_upload'`.
   - Verifies `DeterministicEnergyEngine::analyze('custom_upload')` processes custom panels dynamically.
2. **`test_f02_csv_upload_validates_headers_types_timestamps_and_rejects_duplicates` (PASS):**
   - Asserts failure on missing required header.
   - Asserts failure on unparseable timestamp (`"not-a-valid-date"`).
   - Asserts failure on negative energy (`-10.0`).
   - Asserts failure on out-of-bounds power factor (`1.45`).
   - Asserts failure on invalid category enum (`"UNKNOWN_CATEGORY"`).
   - Asserts failure on duplicate `(timestamp, panel_id)` records.
3. **`test_f04_ai_executive_narrative_dynamically_reflects_active_dataset` (PASS):**
   - Generates executive narrative on `sukarela` and verifies dynamic panel metrics.
   - Generates executive narrative on `wajib` and confirms it renders $E_0 = 1,764.2\text{ kW}$ and does NOT inject static pilot plant text.
   - Tests custom payload with altered SEC ($0.88\text{ kWh/batch}$) and confirms narrative reflects `0,88 kWh/batch`.
   - Tests `/chat` offline fallback on `wajib`, confirming it reports scaled base load rather than static pilot plant $22.4\text{ kW}$.
4. **`test_f09_configurable_wbp_tariff_periods_and_simulation` (PASS):**
   - Verifies `isTimestampInWbp` across standard (`18:00`–`22:00`), custom (`17:00`–`21:00`), and midnight-crossing (`22:00`–`04:00`) windows.
   - Verifies that altering WBP hours in `RegulatoryConfig` changes WBP shiftable calculation in `analyze('sukarela')`.
5. **`test_f12_anomaly_detection_operates_per_panel_with_validated_data` (PASS):**
   - Verifies anomaly logs contain distinct panels (`SDP-01_LINE_A` and `SDP-02_UTILITY`).
   - Verifies every anomaly log has `loss_kwh > 0.0` and `loss_rp > 0.0`.
   - Verifies operational spike anomalies have `severity === 'danger'` and describe the actual panel with calculated Z-score ($Z > 2.0$).

### 5.3 Code Style & Formatting

Executed Laravel Pint according to project rules:

```bash
vendor/bin/pint --format agent
```

**Actual Output:**
```json
{"tool":"pint","result":"fixed","files":[{"path":"app/Services/AiCopilotService.php","fixers":["single_quote","unary_operator_spaces","not_operator_with_successor_space"]},{"path":"app/Services/DeterministicEnergyEngine.php","fixers":["no_unneeded_control_parentheses","unary_operator_spaces","not_operator_with_successor_space"]},{"path":"tests/Feature/EnergyEfficiencyTest.php","fixers":["unary_operator_spaces","no_unused_imports","not_operator_with_successor_space"]}]}
```

---

## 6. Changed Files Inventory

1. [`app/Http/Controllers/IeecDashboardController.php`](file:///Users/yusuf/Documents/Project/Angel-Hack/app/Http/Controllers/IeecDashboardController.php)
   - Refactored `uploadCsv()` with complete validation for headers, data types, timestamps, numeric non-negatives, power factor bounds, enums, and duplicates.
   - Implemented dynamic panel discovery and atomic `SdpPanel::firstOrCreate()` registration.
   - Refactored `streamIot()` to use `DeterministicEnergyEngine::isTimestampInWbp()`.
2. [`app/Services/DeterministicEnergyEngine.php`](file:///Users/yusuf/Documents/Project/Angel-Hack/app/Services/DeterministicEnergyEngine.php)
   - Added `isTimestampInWbp(string|Carbon $timestamp, RegulatoryConfig $config): bool` helper.
   - Refactored `analyze()` to iterate `$panelGroups->keys()` dynamically.
   - Refactored WBP shiftable calculation to use `isTimestampInWbp`.
   - Refactored `buildTimeseriesAndAnomalies()` to compute panel-specific operational $\mu_p$ and $\sigma_p$, evaluate Z-scores per panel record, and attribute anomaly logs directly to the affected panel.
3. [`app/Services/AiCopilotService.php`](file:///Users/yusuf/Documents/Project/Angel-Hack/app/Services/AiCopilotService.php)
   - Refactored `generateExecutiveAuditNarrative()` to dynamically iterate `$payload['analisis_ike_per_panel']`.
   - Refactored `deterministicKnowledgeFallback()` to interpolate metrics dynamically from `$payload`.
4. [`tests/Feature/EnergyEfficiencyTest.php`](file:///Users/yusuf/Documents/Project/Angel-Hack/tests/Feature/EnergyEfficiencyTest.php)
   - Updated existing test assertion for SDP-01 $E_0$ fallback to dynamic $21.8\text{ kW}$.
   - Added 5 new comprehensive regression test methods covering F-02, F-04, F-09, and F-12.

---

## 7. Unresolved Limitations & Boundary Constraints

1. **Air-Gapped CDN Assets (F-10):** Blade views continue to load Tailwind CSS and ApexCharts from external CDNs (`cdn.tailwindcss.com` and `cdn.jsdelivr.net`). This is scheduled for Batch 3 (Deployment & Offline Resiliency).
2. **Access Control & Auth (F-03):** Sensitive configuration and upload endpoints remain public pending the implementation of the Role-Based Access Control and authentication module in Batch 4.
3. **Audit Worksheet PDF/Excel Generation (F-08, F-13):** The ESDM audit worksheet is rendered via browser HTML print; binary PDF and Excel export generation are scheduled for Batch 5.

---

## 8. Conclusion

Batch 2 has achieved **100% of its acceptance criteria**. Data ingestion is now fault-tolerant, unknown panels are gracefully cataloged, AI Copilot audit narratives are strictly grounded in deterministic calculations without numerical hallucination or hardcoded overrides, tariff periods are derived from live configuration, and anomaly detection operates with panel-level statistical rigor.

**Findings F-02, F-04, F-09, and F-12 are VERIFIED FIXED.**
