# IEEC REMEDIATION REPORT — BATCH 4: HACKATHON-SAFE GUARDRAILS, AUDIT TRAILS & REGULATORY ENHANCEMENTS

**Document Version:** 1.0  
**Date:** October 16, 2026  
**Auditor / Engineer:** Multidisciplinary IEEC Engineering Review Team  
**Scope:** Remediation of Findings F-03, F-11, and PRD Sub-Requirements FR-06 & FR-07  
**Status:** **BATCH 4 VERIFIED & FIXED**

---

## 1. Executive Summary

Following the completion and verification of **Batch 1** (Deterministic Calculation Integrity: F-01, F-05, F-07), **Batch 2** (Data Reliability and Analytical Consistency: F-02, F-04, F-09, F-12), and **Batch 3** (CDN Assets and Offline Resiliency: F-10), **Batch 4: Hackathon-Safe Guardrails, Audit Trails & Regulatory Enhancements** has been implemented, validated, and rigorously tested.

Batch 4 addresses key architectural, security, UX, and regulatory requirements:
1. **F-03 (Security & Parameter Locking without Login Walls):**  
   Tailored specifically for live hackathon jury demonstrations. In lieu of intrusive login/auth walls that add friction, F-03 enforces **server-side immutability on statutory parameters** (`ambang_wajib_toe` permanently locked to 4,000 TOE per PP 33/2023; `tarif_wbp_rp_kwh` automatically derived as $K \times \text{Tarif LWBP}$) and attaches `throttle:30,1` rate limiting to protect LLM API quotas (`/chat`) and CSV ingestion (`/upload`).
2. **F-11 (UX Navigation Preservation):**  
   Preserves the active `compliance` tab upon submitting the non-electric thermal energy recalculation form (`tab=compliance`), eliminating disruptive redirects to the Overview tab.
3. **FR-06 (TOE & SBM Regulatory Conversion):**  
   Integrates annual SBM (Setara Barel Minyak / BOE) conversion using the official ESDM Ditjen EBTKE standard coefficient of $0.613\text{ SBM/MWh}$ into `DeterministicEnergyEngine` (`kepatuhan_manajemen_energi`) and renders real-time energy mix metrics in the compliance view.
4. **FR-07 (Regional Grid Presets & Audit Trail Logging):**  
   Implements regional grid emission factor dropdown presets (Jamali: 0.870, Sumatera: 0.780, Kalimantan: 0.890, Sulawesi: 0.740 tCO2e/MWh), displays parameter locking indicators, and logs every configuration modification to `regulatory_config_logs` with timestamps, author attribution, and old/new diffs.

All previous Batch 1–3 functionality and local Vite assets remain 100% preserved. The automated test suite now comprises **33 comprehensive feature and regression tests with 252 assertions passing (100% success rate)**.

---

## 2. Findings Addressed & Verification Status

| Finding / Req ID | Severity | Category | Pre-Remediation State | Post-Remediation State | Status |
| :--- | :--- | :--- | :--- | :--- | :---: |
| **F-03** | **Critical** | Security & Governance | Routes `/config`, `/upload`, and `/chat` had zero access control, parameter protection, or rate limiting. Public requests could overwrite statutory 4,000 TOE thresholds or spam LLM API. | Enforced server-side immutability for statutory 4,000 TOE threshold. Automatic $K \times \text{LWBP}$ tariff calculation. Applied `throttle:30,1` on `/chat` and `/upload`. Zero login friction for live hackathon presentation. | **VERIFIED FIXED** |
| **F-11** | **Medium** | UX & Navigation | Submitting thermal energy recalculation form in *Kepatuhan & Audit* tab reloaded page without `tab=compliance`, kicking user back to Overview tab. | Added `<input type="hidden" name="tab" value="compliance">`. Controller and client-side tab switcher preserve active compliance context. | **VERIFIED FIXED** |
| **FR-06 (Sub)** | **Medium** | Regulatory Compliance | Only electricity TOE conversion ($0.086\text{ TOE/MWh}$) was calculated. Official ESDM Ditjen EBTKE SBM conversion ($0.613\text{ SBM/MWh}$) was missing from engine and view. | Added `sbmFactor = 0.613`, monthly SBM, and annual projected SBM in `DeterministicEnergyEngine`. Displayed in dedicated breakdown cards in compliance tab. | **VERIFIED FIXED** |
| **FR-07 (Sub)** | **Medium** | Grid Calibration & Audit Trail | No regional grid presets available (only Jamali 0.870). No version history table existed; `/config` updates overwrote DB in-place without audit logging. | Created `regulatory_config_logs` table. Controller diffs changed parameters on update and logs old/new values. Added regional grid selector (Jamali, Sumatera, Kalimantan, Sulawesi) and rendered audit trail table in UI. | **VERIFIED FIXED** |

---

## 3. Detailed Root Causes & Implemented Fixes

### 3.1 Finding F-03: Hackathon-Safe Parameter Locking & Rate Limiting

#### Root Cause
Under the original implementation, `IeecDashboardController::updateConfig()` accepted any numerical input for `ambang_wajib_toe`, allowing users to overwrite the national statutory threshold of 4,000 TOE mandated by Article 4 of Government Regulation (PP) No. 33 of 2023. Furthermore, endpoints `/chat` and `/upload` had no rate limiting, leaving the system susceptible to LLM quota exhaustion.

#### Implemented Fix
1. **Server-Side Parameter Locking:**
   In [`app/Http/Controllers/IeecDashboardController.php`](file:///Users/yusuf/Documents/Project/Angel-Hack/app/Http/Controllers/IeecDashboardController.php):
   - Validation for `ambang_wajib_toe` was changed to `nullable|numeric`.
   - The statutory threshold is hard-enforced on the server:
     `$validated['ambang_wajib_toe'] = 4000.0;`
   - Peak period tariff is locked to automatic derivation:
     `$validated['tarif_wbp_rp_kwh'] = round((float) $validated['tarif_lwbp_rp_kwh'] * (float) $validated['faktor_k_wbp'], 2);`
2. **Rate Limiting:**
   In [`routes/web.php`](file:///Users/yusuf/Documents/Project/Angel-Hack/routes/web.php):
   - Applied `throttle:30,1` middleware to `POST /upload` and `POST /chat`.
3. **UI Indicators:**
   In [`resources/views/dashboard.blade.php`](file:///Users/yusuf/Documents/Project/Angel-Hack/resources/views/dashboard.blade.php):
   - Added visual badges: *"Terkunci (Statutori PP 33/2023)"* with readonly inputs to clearly communicate regulatory immutability to judges and users.

---

### 3.2 Finding F-11: UX Compliance Tab Preservation

#### Root Cause
The thermal energy recalculation form submitted a GET request with `preset` and `non_electric_toe`, but omitted the `tab` parameter. Consequently, `IeecDashboardController::index` defaulted `$initialTab` to `'overview'`, causing an unexpected context switch.

#### Implemented Fix
1. In [`resources/views/dashboard.blade.php`](file:///Users/yusuf/Documents/Project/Angel-Hack/resources/views/dashboard.blade.php#L1131):
   - Added `<input type="hidden" name="tab" value="compliance">`.
2. In [`app/Http/Controllers/IeecDashboardController.php`](file:///Users/yusuf/Documents/Project/Angel-Hack/app/Http/Controllers/IeecDashboardController.php#L143):
   - Also updated `updateConfig()` redirect to preserve the active tab (`$tab = $request->input('tab', 'config-regulasi')`), keeping the user on the configuration screen after saving.

---

### 3.3 Sub-Requirement FR-06: Annual SBM Conversion

#### Implemented Fix
1. In [`app/Services/DeterministicEnergyEngine.php`](file:///Users/yusuf/Documents/Project/Angel-Hack/app/Services/DeterministicEnergyEngine.php):
   - Defined `$sbmFactor = 0.613;` ($1\text{ MWh} = 0.613\text{ SBM}$ / Setara Barel Minyak).
   - Computed `$sbmMonthly = $totalMwh * $sbmFactor;` and `$sbmAnnualElec = ($totalMwh / $daysCount * 365) * $sbmFactor;`.
   - Exposed `faktor_konversi_sbm_per_mwh`, `konsumsi_sbm_bulanan`, and `proyeksi_sbm_tahunan_listrik` in `$payload['kepatuhan_manajemen_energi']`.
2. In [`resources/views/dashboard.blade.php`](file:///Users/yusuf/Documents/Project/Angel-Hack/resources/views/dashboard.blade.php):
   - Rendered energy conversion breakdown cards displaying Electricity (kWh / MWh), TOE ($0.086\text{ TOE/MWh}$), SBM ($0.613\text{ SBM/MWh}$), and Thermal Energy ($TOE/\text{yr}$).

---

### 3.4 Sub-Requirement FR-07: Regional Grid Presets & Audit Trail Logging

#### Implemented Fix
1. **Database Migration & Model:**
   - Created [`database/migrations/2026_10_16_062928_create_regulatory_config_logs_table.php`](file:///Users/yusuf/Documents/Project/Angel-Hack/database/migrations/2026_10_16_062928_create_regulatory_config_logs_table.php) and [`app/Models/RegulatoryConfigLog.php`](file:///Users/yusuf/Documents/Project/Angel-Hack/app/Models/RegulatoryConfigLog.php).
   - Columns: `regulatory_config_id`, `versi_regulasi`, `parameter_name`, `old_value`, `new_value`, `changed_by`, `notes`, `timestamps`.
2. **Controller Audit Trailing:**
   - In `IeecDashboardController::updateConfig()`, iterated validated fields and logged any modified values into `RegulatoryConfigLog`.
   - In `IeecDashboardController::index()`, passed `$configLogs = RegulatoryConfigLog::latest()->take(10)->get()`.
3. **UI Enhancements:**
   - Added regional grid selector dropdown supporting:
     - Jamali (Jawa-Madura-Bali): 0.870 tCO2e/MWh
     - Sumatera: 0.780 tCO2e/MWh
     - Kalimantan: 0.890 tCO2e/MWh
     - Sulawesi: 0.740 tCO2e/MWh
   - Rendered a structured table in `config-regulasi` displaying the audit log history.

---

## 4. Automated Test Suite & Regression Verification

### 4.1 Test Execution Command & Output

Command:
```bash
vendor/bin/phpunit
```

Results:
```json
{"tool":"phpunit","result":"passed","tests":33,"passed":33,"assertions":252,"duration_ms":38450}
```

### 4.2 Batch 4 Specific Regression Tests
1. `test_f03_statutory_parameters_are_locked_and_rate_limiting_is_configured()`:
   - Verified posting `ambang_wajib_toe = 100` leaves the database locked at 4,000 TOE.
   - Verified `tarif_wbp_rp_kwh` derived automatically as $1,200 \times 1.5 = 1,800$.
   - Verified routes `copilot.chat` and `csv.upload` contain `throttle:30,1`.
2. `test_f11_thermal_energy_recalculation_form_preserves_compliance_tab()`:
   - Verified GET request with `tab=compliance` preserves the compliance context in `initialServerTab`.
   - Verified the recalculation form outputs `<input type="hidden" name="tab" value="compliance">`.
3. `test_fr06_and_fr07_sbm_conversion_and_config_audit_trail_logging()`:
   - Verified SBM conversion factor (0.613) and annual projected SBM in calculation payload.
   - Verified compliance view renders SBM cards.
   - Verified updating configuration parameters creates records in `regulatory_config_logs` and displays them in the audit trail table.

---

## 5. Files Changed in Batch 4

| File | Change Description |
| :--- | :--- |
| [`routes/web.php`](file:///Users/yusuf/Documents/Project/Angel-Hack/routes/web.php) | Applied `throttle:30,1` on `/chat` and `/upload` routes. |
| [`database/migrations/2026_10_16_062928_create_regulatory_config_logs_table.php`](file:///Users/yusuf/Documents/Project/Angel-Hack/database/migrations/2026_10_16_062928_create_regulatory_config_logs_table.php) | Migration creating `regulatory_config_logs` table. |
| [`app/Models/RegulatoryConfigLog.php`](file:///Users/yusuf/Documents/Project/Angel-Hack/app/Models/RegulatoryConfigLog.php) | Eloquent model for configuration change audit logs. |
| [`app/Models/RegulatoryConfig.php`](file:///Users/yusuf/Documents/Project/Angel-Hack/app/Models/RegulatoryConfig.php) | Added `configLogs()` hasMany relationship. |
| [`app/Services/DeterministicEnergyEngine.php`](file:///Users/yusuf/Documents/Project/Angel-Hack/app/Services/DeterministicEnergyEngine.php) | Added official SBM conversion ($0.613\text{ SBM/MWh}$) to calculations. |
| [`app/Http/Controllers/IeecDashboardController.php`](file:///Users/yusuf/Documents/Project/Angel-Hack/app/Http/Controllers/IeecDashboardController.php) | Statutory parameter locking (4000 TOE), automatic WBP tariff calculation, change diff logging, and tab preservation. |
| [`resources/views/dashboard.blade.php`](file:///Users/yusuf/Documents/Project/Angel-Hack/resources/views/dashboard.blade.php) | Added hidden compliance tab input, SBM breakdown cards, regional grid presets dropdown, parameter locking badges, and audit trail table. |
| `public/build/*` | Recompiled Vite production assets. |
| [`tests/Feature/EnergyEfficiencyTest.php`](file:///Users/yusuf/Documents/Project/Angel-Hack/tests/Feature/EnergyEfficiencyTest.php) | Added regression tests for F-03, F-11, and FR-06/FR-07 (23 new assertions). |
| [`audit/BATCH_4_REMEDIATION_REPORT.md`](file:///Users/yusuf/Documents/Project/Angel-Hack/audit/BATCH_4_REMEDIATION_REPORT.md) | Authored Batch 4 remediation documentation. |

---

## 6. What Remains for Batch 5 (Final Batch)

The remaining open items in the remediation roadmap are:
1. **F-06 (Native Binary Export):** Native binary PDF (`barryvdh/laravel-dompdf` or CSV/Excel streaming) when requesting `/export-audit?format=pdf` or `?format=excel`.
2. **F-08 (IoT Telemetry Chart Synchronization):** Synchronizing live 3-second IoT simulation stream with the ApexCharts timeseries canvas.
3. **F-13 (Auditor Disclaimers):** Replacing mock auditor registration numbers in the export sheet with formal simulation template disclaimers.
