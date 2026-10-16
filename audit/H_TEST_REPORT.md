# DELIVERABLE H — TEST REPORT & VERIFICATION EVIDENCE
## Industrial Energy Efficiency Copilot (IEEC) — Prototype Alpha v1.2
**Audit Evaluation:** Quality Assurance & Verification Engineering  
**Test Suite Executed:** PHPUnit 12.5.12 on PHP 8.4.21 / Laravel 12.17  
**Verification Date:** 16 Oktober 2026  

---

### 1. Existing Automated Test Suite Results

The existing automated test suite located at [`tests/Feature/EnergyEfficiencyTest.php`](file:///Users/yusuf/Documents/Project/Angel-Hack/tests/Feature/EnergyEfficiencyTest.php) was executed in its entirety:

```bash
php artisan test
```

#### Execution Output:
```text
   PASS  Tests\Unit\ExampleTest
  ✓ that true is true

   PASS  Tests\Feature\EnergyEfficiencyTest
  ✓ dashboard loads successfully with sukarela preset
  ✓ deterministic engine matches prd section 7 1 exactly
  ✓ wajib preset triggers mandatory energy management status
  ✓ iot live streaming endpoint
  ✓ copilot chat returns prescriptive answer
  ✓ copilot chat uses sumopod ai when configured
  ✓ copilot chat falls back gracefully when sumopod errors
  ✓ esdm audit worksheet export
  ✓ dynamic config update fr 07
  ✓ copilot dedicated page loads successfully
  ✓ copilot drawer context and topbar tanya ai present

   PASS  Tests\Feature\ExampleTest
  ✓ the application returns a successful response

  Tests:    13 passed (57 assertions)
  Duration: 10.37s
```

#### Analysis of Existing Test Suite:
- **13 of 13 tests passed (100% success rate on tested assertions).**
- **Test Quality Critique:**
  - The test `test_deterministic_engine_matches_prd_section_7_1_exactly` simply asserts that the engine returns 48,250 kWh, Rp 5,609,586, and 4,120 kWh. Because `DeterministicEnergyEngine` hardcodes these values for `sukarela`, the test is effectively asserting hardcoded literals against themselves rather than validating mathematical calculations against varying inputs!
  - No boundary condition tests, malformed dataset tests, or edge-case tests existed in the original suite.

---

### 2. Comprehensive Critical Workflow & Boundary Test Matrix

To independently audit the application beyond the developer-supplied happy path, the audit team designed and executed 14 rigorous feature, edge-case, and boundary tests:

| Test ID | Test Scenario | Expected Result | Actual Result | Status | Evidence / Log Output |
| :---: | :--- | :--- | :--- | :---: | :--- |
| **TC-01** | **Valid CSV Upload (FR-01)**<br>Upload standard 9-column CSV matching Indmira schema. | HTTP 302 redirect with flash message stating row count; records inserted into `energy_telemetry_logs`. | 2,880 rows ingested; flash message displayed. | **PASS** | `Berhasil mengunggah 2880 baris data telemetri historis (Modul FR-01).` |
| **TC-02** | **CSV Upload with Unknown Panel ID (FR-01 Edge Case)**<br>Upload CSV containing `panel_id` not seeded in `sdp_panels` (e.g. `SDP-99_BOILER`). | Ingested successfully with panel registered, or graceful validation error message. | Fatal 500 error: `SQLSTATE[23000]: Integrity constraint violation: 19 FOREIGN KEY constraint failed`. | **FAIL** | Database transaction aborted; unhandled 500 server crash. |
| **TC-03** | **Malformed CSV Row with Extra Column (FR-01 Edge Case)**<br>Row contains 10 values for 9 headers (e.g. unescaped comma). | Row skipped or reported in validation errors without application crash. | Fatal 500 error: `ValueError: array_combine(): Argument #1 ($keys) and argument #2 ($values) must have the same number of elements`. | **FAIL** | PHP 8 fatal error crashes upload request. |
| **TC-04** | **Malformed Timestamp String in CSV (FR-01 Edge Case)**<br>Row contains `2026-INVALID-DATE` in timestamp column. | Graceful error message stating line number and error. | Fatal 500 error: `Carbon\Exceptions\InvalidFormatException`. | **FAIL** | Uncaught Carbon exception terminates script. |
| **TC-05** | **Zero Production Quantity in OLS Regression (FR-02 Edge Case)**<br>All production output records $P_t = 0$. | Graceful handling without division by zero ($n \sum P^2 - (\sum P)^2 = 0$). Returns zero coefficients. | Returns `{"a": 0, "e0": 0, "r2": 0}` safely. | **PASS** | Handled by guard: `if ($denomA == 0.0) return ['a' => 0.0, 'e0' => 0.0, 'r2' => 0.0];` |
| **TC-06** | **Insufficient Baseline Observations (FR-02 Edge Case)**<br>Collection has $n < 2$ operational points. | Returns zero baseline without fatal exception. | Returns `{"a": 0, "e0": 0, "r2": 0}`. | **PASS** | Guard clause `if ($opData->count() < 2)` executes correctly. |
| **TC-07** | **Zero Variance Energy Data in OLS (FR-02 Edge Case)**<br>Energy consumption is constant across all points ($E_t = 50$). | Returns $E_0 = 50, a = 0, R^2 = 0$ without divide-by-zero on $R^2$ denominator. | Returns `{"a": 0, "e0": 50, "r2": 0}`. | **PASS** | Handled by guard: `$denomR2 > 0 ? ... : 0.0`. |
| **TC-08** | **TOE Boundary Below Threshold (FR-06 Boundary)**<br>Combined energy = 3,998.79 TOE/year (< 4,000). | Status: `DI BAWAH AMBANG (sukarela)`. | Output: `DI BAWAH AMBANG (sukarela)`. | **PASS** | Evaluated via tinker: `analyze("sukarela", 3949.0)`. |
| **TC-09** | **TOE Boundary Exactly at Threshold (FR-06 Boundary)**<br>Combined energy = 4,000.00 TOE/year ($\ge 4,000$). | Status: `WAJIB MANAJEMEN ENERGI`. | Output: `WAJIB MANAJEMEN ENERGI (>= 4.000 TOE/tahun — Mandat PP 33/2023 & Permen ESDM 8/2025)`. | **PASS** | Evaluated via tinker: `analyze("sukarela", 3950.21)`. |
| **TC-10** | **TOE Boundary Above Threshold (FR-06 Boundary)**<br>Combined energy = 4,000.79 TOE/year (> 4,000). | Status: `WAJIB MANAJEMEN ENERGI`. | Output: `WAJIB MANAJEMEN ENERGI (>= 4.000 TOE/tahun — Mandat PP 33/2023 & Permen ESDM 8/2025)`. | **PASS** | Evaluated via tinker: `analyze("sukarela", 3951.0)`. |
| **TC-11** | **Dynamic Recalibration on Preset Sukarela (FR-07 Integration)**<br>Change `tarif_lwbp_rp_kwh` from Rp 1,035.78 to Rp 2,000.00. | Total financial savings for `sukarela` should recalculate to ~Rp 9.5 Million. | Savings remain frozen at Rp 5,609,586. | **FAIL** | Bypassed by `if ($isStandardSukarela)` in `DeterministicEnergyEngine.php`. |
| **TC-12** | **WBP Operating Hour Adjustment (FR-07 Integration)**<br>Change WBP hours in configuration from 18:00–22:00 to 17:00–21:00. | Engine filters on new hours $17 \le h < 21$. | Engine continues checking hardcoded $18 \le h < 22$. | **FAIL** | Hardcoded in `DeterministicEnergyEngine.php:241` and `IeecDashboardController.php:241`. |
| **TC-13** | **IoT Streaming Simulation Start/Stop (FR-05)**<br>Toggle IoT Live button on and off repeatedly. | State toggles cleanly; polling starts and stops without duplicate intervals. | Toggle works; banner values cycle every 3s. Chart remains static. | **PARTIAL** | Front-end chart synchronization missing. |
| **TC-14** | **AI Copilot Live Context Grounding (FR-04)**<br>Ask technical question: "Berapa kWh konsumsi SDP-01 pada preset wajib?". | LLM responds with exact commercial plant figure: 1,810,563 kWh. | LLM (DeepSeek) responded: "konsumsi listrik untuk panel SDP-01_LINE_A adalah 1.810.563 kWh". | **PASS** | Injected JSON context verified live. Zero hallucination. |

---

### 3. Summary of Test Findings

- **Total Test Cases Executed:** 14 critical cases (in addition to 13 passing unit/feature tests).
- **Passed:** 8 / 14 (57.1%)
- **Partial:** 1 / 14 (7.1%)
- **Failed:** 5 / 14 (35.7%)
- **Primary Failure Modes Identified:**
  1. Ingestion fragility on non-standard panel names (SQL foreign key violation).
  2. Lack of input sanitization on malformed CSV timestamps and extra columns.
  3. Hardcoded bypasses breaking dynamic recalculation on preset `sukarela`.
  4. Hardcoded WBP hour filters in PHP code.
  5. Desynchronization between IoT streaming polling and the ApexCharts timeseries canvas.
