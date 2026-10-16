# DELIVERABLE D — CALCULATION VALIDATION REPORT
## Industrial Energy Efficiency Copilot (IEEC) — Prototype Alpha v1.2
**Audit Focus:** Independent Mathematical & Engineering Validation of the Deterministic Calculation Pipeline.

---

### 1. Calculation Pipeline Architecture

The IEEC analytical pipeline processes hourly electrical telemetry and production variables through 6 primary computational stages:
1. **Raw Telemetry Aggregation:** Summation of active energy ($E_{\text{kWh}}$) and reactive energy ($Q_{\text{kVArh}}$) over $D$ days.
2. **Plant Power Quality Assessment:** Root-Sum-Square determination of aggregate plant displacement power factor ($\cos \varphi_{\text{agregat}}$).
3. **Regulatory Energy Equivalence:** Conversion of electrical MWh to Tonne of Oil Equivalent (TOE) and Barrel of Oil Equivalent (SBM/BOE), combined with thermal inputs.
4. **Zone-Specific Performance Indicators:**
   - Building facilities: Floor-area normalized Energy Consumption Intensity (IKE) against Permen ESDM 3/2025.
   - Production lines: Specific Energy Consumption (SEC) and Ordinary Least Squares (OLS) linear baseline modeling against ISO 50001:2018.
5. **Multi-Vector Anomaly Quantification:** Idle waste, statistical Z-score spikes, and peak-hour (WBP) load shifting potential.
6. **Financial Reconciliation & Emissions Accounting:** PLN tariff structure application (Permen ESDM 7/2024), kVArh billing penalties, and Gatrik ESDM grid emission factors.

---

### 2. Mathematical Validation & Hand-Calculation Audit

#### Formula Audit 1: Building Energy Intensity (IKE Gedung)
- **PRD Formula (Section 3.2):**
  $$\text{IKE}_{\text{normalisasi\_bulanan}} = \left( \frac{\sum_{t=1}^{N} \text{energy\_kwh}_{\text{gedung}, t}}{\text{area\_sqm} \times D} \right) \times 30 \quad (\text{kWh/m}^2\text{/bulan})$$
- **Code Implementation:** [`DeterministicEnergyEngine.php:163-165`](file:///Users/yusuf/Documents/Project/Angel-Hack/app/Services/DeterministicEnergyEngine.php#L163-L165)
  ```php
  $ikeAktual = ($area > 0 && $daysCount > 0)
      ? round(($pKwh / ($area * $daysCount)) * 30, 2)
      : 0.0;
  ```
- **Validation Hand-Calculation (SDP-03 Gedung Utama & Lab):**
  - Input: $\sum E = 6,200\text{ kWh}$, $\text{Area} = 600\text{ m}^2$, $D = 30\text{ days}$.
  - Expected: $\frac{6,200}{600 \times 30} \times 30 = \frac{6,200}{600} = 10.3333 \approx \mathbf{10.33\text{ kWh/m}^2\text{/bulan}}$.
  - Code Output: `10.33`.
  - Regulatory Threshold (Permen ESDM 3/2025 Lampiran I, Office $\le 5,000\text{ m}^2$): $8.50 \le \text{IKE} < 14.00 \rightarrow$ **EFISIEN**.
  - Code Classification: `EFISIEN`.
- **Validation Hand-Calculation (SDP-04 Gudang Feedstock Non-AC):**
  - Input: $\sum E = 2,800\text{ kWh}$, $\text{Area} = 1,200\text{ m}^2$, $D = 30\text{ days}$.
  - Expected: $\frac{2,800}{1,200 \times 30} \times 30 = \frac{2,800}{1,200} = 2.3333 \approx \mathbf{2.33\text{ kWh/m}^2\text{/bulan}}$.
  - Code Output: `2.33`.
  - Regulatory Threshold (SNI 6196 & Permen ESDM 3/2025, Non-AC): $2.00 \le \text{IKE} \le 3.40 \rightarrow$ **EFISIEN**.
  - Code Classification: `EFISIEN`.
- **Finding / Verdict:** **PASS (Mathematical & Classification Correct)**.  
  *Limitation:* The code only checks the $\le 5,000\text{ m}^2$ classification rules. Facilities with conditioned area $> 5,000\text{ m}^2$ are not evaluated against the tighter thresholds ($7.50 \le \text{IKE} < 12.00$).

---

#### Formula Audit 2: OLS Linear Regression Baseline (ISO 50001:2018)
- **PRD Formula (Section 3.3):**
  $$E = a \cdot P_t + E_0$$
  $$a = \frac{n \sum (P_t \cdot E_t) - \sum P_t \sum E_t}{n \sum (P_t^2) - (\sum P_t)^2}, \quad E_0 = \bar{E} - a \cdot \bar{P}$$
  $$R^2 = \frac{\left[ n \sum (P_t \cdot E_t) - \sum P_t \sum E_t \right]^2}{\left[ n \sum (P_t^2) - (\sum P_t)^2 \right] \left[ n \sum (E_t^2) - (\sum E_t)^2 \right]}$$
- **Code Implementation:** [`DeterministicEnergyEngine.php:350-393`](file:///Users/yusuf/Documents/Project/Angel-Hack/app/Services/DeterministicEnergyEngine.php#L350-L393)
- **Validation Execution Tracing (SDP-01 Line Pirolisis on Raw Dataset):**
  - Total operational points ($P_t > 0$): $n = 360$ hours.
  - Sums: $\sum P = 43,077\text{ batches}$, $\sum E = 22,400.01\text{ kWh}$.
  - Actual OLS Output:
    - Marginal slope ($a$): **0.2436 kWh/batch**
    - Base load ($E_0$): **21.83 kW**
    - Coefficient of Determination ($R^2$): **0.9370**
  - PRD Table 7.1 Stated Values: $E_0 = 22.4\text{ kW}, R^2 = 0.91$.
- **Finding / Discrepancy:**
  - In [`DeterministicEnergyEngine.php:123-124`](file:///Users/yusuf/Documents/Project/Angel-Hack/app/Services/DeterministicEnergyEngine.php#L123-L124), the code forcibly discards the actual computed results ($E_0=21.83, R^2=0.937$) and hardcodes `$e0 = 22.4; $r2 = 0.91;`.
  - **Verdict:** **FAIL (Artificial Override)**. The real OLS calculation actually works and achieves an excellent $R^2 = 0.937$ ($> 0.75$, ISO 50001 valid). The hardcoded override was unnecessary and undermines dynamic calculation credibility.

---

#### Formula Audit 3: Plant Displacement Power Factor & Billable kVArh
- **PRD Formula (Section 3.5):**
  $$\cos \varphi_{\text{agregat}} = \frac{\sum \text{kWh}}{\sqrt{(\sum \text{kWh})^2 + (\sum \text{kVArh})^2}}$$
  $$\text{Batas Bebas Biaya kVArh} = 0.62 \times \sum \text{kWh}$$
  $$\text{Jika } \cos \varphi < 0.85 \rightarrow \text{kVArh}_{\text{kena\_biaya}} = \max \left(0, \sum \text{kVArh} - \text{Batas Bebas} \right)$$
  $$\text{Biaya Penalti} = \text{kVArh}_{\text{kena\_biaya}} \times \text{Rp } 1.114,74$$
- **Code Implementation:** [`DeterministicEnergyEngine.php:60-63, 246-249, 258`](file:///Users/yusuf/Documents/Project/Angel-Hack/app/Services/DeterministicEnergyEngine.php#L60-L63)
- **Validation Hand-Calculation (`preset_pilot_plant_sukarela.csv`):**
  - $\sum \text{kWh} = 48,250.09\text{ kWh}$
  - $\sum \text{kVArh} = 30,673.00\text{ kVArh}$
  - $\cos \varphi_{\text{agregat}} = \frac{48,250.09}{\sqrt{48,250.09^2 + 30,673^2}} = \frac{48,250.09}{\sqrt{2,328,071,190 + 940,832,929}} = \frac{48,250.09}{57,174.33} = \mathbf{0.8439} \approx \mathbf{0.84}$.
  - Since $0.8439 < 0.85$, penalty applies:
  - $\text{Batas Bebas} = 0.62 \times 48,250.09 = 29,915.056\text{ kVArh}$.
  - $\text{kVArh}_{\text{kena\_biaya}} = 30,673.00 - 29,915.06 = \mathbf{757.94\text{ kVArh}} \approx \mathbf{758\text{ kVArh}}$.
  - $\text{Biaya Penalti} = 757.944 \times \text{Rp } 1.114,74 = \mathbf{\text{Rp } 844.910}$ (using float) or $758 \times 1.114,74 = \mathbf{\text{Rp } 844.973}$ (using integer).
- **Finding / Discrepancy:**
  - The actual mathematical calculation yields Rp 844,973.
  - In code line 260 & 268: `$biayaKvarhRp = 845000.0;` forces a flat Rp 845,000 for `sukarela`.
  - **Verdict:** **PARTIAL (Formula sound, unnecessary hardcoded round-off)**.

---

#### Formula Audit 4: Electricity & Thermal Energy Conversion to TOE/SBM (PP 33/2023)
- **PRD Formula (Section 3.1):**
  $$\text{Total MWh} = \frac{\sum \text{kWh}}{1,000}$$
  $$\text{TOE}_{\text{listrik\_tahunan}} = \left( \frac{\text{Total MWh}}{D} \times 365 \right) \times 0.086$$
  $$\text{SBM}_{\text{listrik\_tahunan}} = \left( \frac{\text{Total MWh}}{D} \times 365 \right) \times 0.613$$
  $$\text{TOE}_{\text{total\_tahunan}} = \text{TOE}_{\text{listrik\_tahunan}} + \text{TOE}_{\text{non\_listrik\_input}}$$
- **Code Implementation:** [`DeterministicEnergyEngine.php:58, 65-74`](file:///Users/yusuf/Documents/Project/Angel-Hack/app/Services/DeterministicEnergyEngine.php#L58)
- **Validation Hand-Calculation (`sukarela`):**
  - $\sum \text{kWh} = 48,250.09 \rightarrow \text{MWh} = 48.25009$. $D = 30$.
  - $\text{Annualized MWh} = \frac{48.25009}{30} \times 365 = 587.0428\text{ MWh/yr}$.
  - $\text{TOE}_{\text{listrik}} = 587.0428 \times 0.086 = \mathbf{50.4856\text{ TOE/yr}}$ (using exact 365 days) or $(48.25 \times 12) \times 0.086 = \mathbf{49.794\text{ TOE/yr}}$ (using 12 calendar months).
  - Code Output: `49.79 TOE/yr`.
  - Hand-calculation SBM: $587.0428 \times 0.613 = \mathbf{359.85\text{ SBM/yr}}$.
- **Finding / Discrepancy:**
  - SBM calculation ($0.613\text{ SBM/MWh}$) is entirely missing in code.
  - **Verdict:** **PARTIAL (TOE verified, SBM missing)**.

---

#### Formula Audit 5: Financial Savings & Decarbonization Reconciliation
- **PRD Formula (Section 3.6):**
  $$\text{Hemat}_{\text{aktif}} = (E_{\text{idle}} + E_{\text{spike}}) \times \text{Tarif}_{\text{LWBP}}$$
  $$\text{Hemat}_{\text{geser\_WBP}} = E_{\text{WBP\_shift}} \times (\text{Tarif}_{\text{WBP}} - \text{Tarif}_{\text{LWBP}})$$
  $$\text{Total Hemat Rp} = \text{Hemat}_{\text{aktif}} + \text{Hemat}_{\text{geser\_WBP}} + \text{Denda}_{\text{kVArh}}$$
  $$\text{Reduksi CO}_2\text{e} = \left( \frac{E_{\text{idle}} + E_{\text{spike}}}{1,000} \right) \times \text{Faktor}_{\text{grid}}$$
- **Hand-Calculation Matrix on Pilot Plant Parameters:**

| Parameter | Unit | Value in PRD 3.6 | Hand-Calculated Value | Code Output (`sukarela`) | Discrepancy |
| :--- | :---: | :---: | :---: | :---: | :---: |
| Idle Waste ($E_{\text{idle}}$) | kWh | 2,450 | 2,450.0 | 2,450 | 0 |
| Spike Waste ($E_{\text{spike}}$) | kWh | 1,670 | 1,670.0 | 1,670 | 0 |
| Total Active Waste | kWh | 4,120 | 4,120.0 | 4,120 | 0 |
| LWBP Tariff | Rp/kWh | 1,035.78 | 1,035.78 | 1,035.78 | 0 |
| Active Elimination Savings | Rp | 4,267,414 | $4,120 \times 1,035.78 = \mathbf{4,267,413.60}$ | 4,267,414 | +Rp 0.40 (rounding) |
| WBP Shiftable Energy | kWh | 1,200 | 1,200.0 | 1,200 | 0 |
| WBP Tariff Diff ($1.4 \times - 1 \times$) | Rp/kWh | 414.31 | $1,450.09 - 1,035.78 = \mathbf{414.31}$ | 414.31 | 0 |
| WBP Shift Savings | Rp | 497,172 | $1,200 \times 414.31 = \mathbf{497,172.00}$ | 497,172 | 0 |
| kVArh Eliminated Penalty | Rp | 845,000 | $758 \times 1,114.74 = \mathbf{844,972.92}$ | 845,000 | +Rp 27.08 (rounding) |
| **Total Monthly Savings** | **Rp** | **5,609,586** | $4,267,414 + 497,172 + 844,973 = \mathbf{5,609,559}$ | **5,609,586** | +Rp 27.00 |
| Grid Emission Factor (Jamali) | $\text{tCO}_2\text{e/MWh}$ | 0.870 | 0.870 | 0.870 | 0 |
| **Total GHG Reduction** | **tCO2e** | **3.58** | $\frac{4,120}{1,000} \times 0.870 = \mathbf{3.5844}$ | **3.58** | 0 |

- **Physical Conservation Integrity Rule:**
  - PRD Section 3.6 A states: *"Load shifting WBP to LWBP does NOT reduce physical kWh or $\text{CO}_2\text{e}$ emissions; it only leverages the financial tariff delta ($K=1.40$)".*
  - Code check: Line 252 defines `$totalPotensiHematKwh = $idleWasteKwh + $spikeWasteKwh;` (excluding WBP shiftable kWh). Line 272 computes $\text{CO}_2\text{e}$ based purely on `$totalPotensiHematKwh`.
  - **Verdict:** **PASS (Physically and financially consistent)**.

---

### 3. Summary of Discrepancies & Unresolved Domain Assumptions

| Item | Expected Mathematical Behavior | Actual Implementation Behavior | Severity | Root Cause |
| :--- | :--- | :--- | :---: | :--- |
| **Recalculation on Preset Sukarela** | Updating tariff or emission factor in FR-07 should recalculate all savings. | If preset is `sukarela`, lines 264–270 overwrite calculations with static PRD sample numbers. | **Critical** | Hardcoded bypass to match PRD text exactly. |
| **Timeseries Baseline Formula** | Hourly baseline curve in chart should use OLS parameters $E_0$ and $a$. | Lines 503–523 hardcode arbitrary slopes $a_1=0.25, a_2=0.20$ and base $+9.0$. | **High** | Incomplete data plumbing from regression module to chart generator. |
| **Dynamic Payback Period** | Payback period should equal $\frac{\text{Capex}}{\text{Monthly Savings} \times 12}$. | Payback values in `generateRecommendations` are hardcoded constants (0.13, 0.17, 2.47, 4.92). | **High** | Static array definition without dynamic formula evaluation. |
| **Idle Waste Filtering** | Scan hours where production is zero during break AND off-shift periods. | Lines 220–221 only check `shift_state === 'BREAK'`, ignoring `OFF_SHIFT`. | **Medium** | Incomplete filter predicate in collection pipeline. |
| **WBP Shifting Factor** | Shiftable load should be derived from continuous thermal/utility equipment profile. | Line 243 multiplies SDP-02 WBP energy by an uncalibrated constant `0.45`. | **Medium** | Lack of equipment schedule metadata in CSV schema. |
| **SBM Conversion** | PRD Section 3.1 mandates SBM reporting ($0.613\text{ SBM/MWh}$). | SBM is omitted from the JSON payload and dashboard display. | **Low** | Omission in data contract. |
