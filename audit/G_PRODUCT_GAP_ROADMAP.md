# DELIVERABLE G — PRODUCT GAP ANALYSIS & ROADMAP
## Industrial Energy Efficiency Copilot (IEEC) — Prototype Alpha v1.2
**Role:** Senior Product Manager Evaluation  
**Product Objective:** Transitioning IEEC from a Hackathon Prototype into an Indmira Industrial Decision Support Platform.

---

### 1. End-to-End Operational Workflow Evaluation

The diagram below traces an industrial user's journey through IEEC, from data ingestion to realized energy savings:

```text
[1. Ingest/Select Data] ---> [2. Data Quality Check] ---> [3. Establish Baseline]
        | (Pass)                     | (GAP: No summary)           | (Pass: OLS EnB)
        v                            v                             v
[4. Detect Anomalies]   ---> [5. Assess Financials]  ---> [6. AI Copilot Review]
        | (Pass: Multi-Vector)       | (Pass: PLN Tariffs)         | (Pass: DeepSeek)
        v                            v                             v
[7. Prioritize Actions] ---> [8. Export Audit Data]  ---> [9. Track M&V Savings]
        | (Pass: 4 Payback)          | (GAP: HTML Print Only)      | (GAP: No tracking)
```

#### Workflow Audit Findings by Stage:

1. **Stage 1 (Ingest/Select Data):**  
   - *Current State:* User can toggle between Sukarela and Wajib presets, or upload a CSV file.
   - *Gap:* Uploading CSV with custom panel IDs crashes with a database FK error. Overwriting custom uploads globally causes multi-user race conditions.
2. **Stage 2 (Validate & Communicate Data Quality):**  
   - *Current State:* Basic success toast showing total rows imported.
   - *Gap:* No data health indicators (missing timestamps, zero-variance spans, voltage sag warnings, or percentage completeness summaries).
3. **Stage 3 (Establish / Evaluate Energy Baseline):**  
   - *Current State:* Calculates OLS regression $E_0$ and $R^2$.
   - *Gap:* High-level cards display hardcoded sample numbers on `sukarela`. No confidence warning when $R^2 < 0.75$.
4. **Stage 4 (Identify Inefficiency & Anomalies):**  
   - *Current State:* Tabular display of recent anomalies categorized by type (Idle, Spike, WBP).
   - *Gap:* Clicking an anomaly does not jump or zoom the timeseries chart to that timestamp.
5. **Stage 5 (Assess Technical & Financial Impact):**  
   - *Current State:* Comprehensive scorecards showing active waste, load shifting savings, and kVArh penalty elimination.
   - *Gap:* Modifying electricity tariffs does not update the savings for the primary preset.
6. **Stage 6 (Review AI Recommendations):**  
   - *Current State:* Excellent structured presentation of actions grouped by payback period.
   - *Gap:* Executive summary text hardcodes pilot plant numbers regardless of active facility scale.
7. **Stage 7 (Prioritize Actions & Roadmapping):**  
   - *Current State:* Clear color-coded badges for No-Cost, Low-Cost, Medium-Cost, and High-Cost tiers.
   - *Gap:* Payback period years are static constants (e.g. 2.47 yrs) rather than dynamic formulas.
8. **Stage 8 (Export Audit Evidence):**  
   - *Current State:* HTML printable preview.
   - *Gap:* No binary `.pdf` file download; no `.xlsx` spreadsheet export.
9. **Stage 9 (Track Realized Savings & M&V):**  
   - *Current State:* Completely absent. The system is a read-only analytics dashboard.
   - *Gap:* Operators cannot log action completion dates, track task assignees, or measure post-intervention savings.

---

### 2. Prioritized Product Roadmap

#### Category 1: Must Fix Before Hackathon Demo (Immediate / S-M Effort)

| ID | Feature / Fix | Target Persona | PRD Required? | Effort | User Problem Solved & Rationale |
| :---: | :--- | :--- | :---: | :---: | :--- |
| **MF-01** | **Remove Hardcoded Analytical Bypasses on `sukarela`** | Plant Manager / Hackathon Jury | Yes (FR-02, FR-03, FR-07) | **S** | Allows live demonstration of tariff updates and recalibration without showing frozen numbers. |
| **MF-02** | **Dynamic Executive Audit Narrative in AI Service** | Energy Manager | Yes (FR-04) | **S** | Prevents contradictory diagnostic text when switching between small pilot plant and large commercial plant. |
| **MF-03** | **Fix Ingestion Foreign Key Constraint on Unknown Panels** | Utility Engineer | Yes (FR-01) | **S** | Prevents embarrassing 500 error crashes if judges or attendees upload their own factory CSV logs. |
| **MF-04** | **Preserve Active Tab on Thermal Recalculation Form** | Energy Manager | Yes (FR-06, UX) | **S** | Prevents kicking the user back to Overview when testing the $\ge 4,000\text{ TOE}$ compliance boundary. |
| **MF-05** | **Dynamic Payback Period Evaluation** | ESG Officer / Director | Yes (FR-04) | **S** | Ensures simple payback period $\frac{\text{Capex}}{\text{Annual Savings}}$ matches the reported ESDM investment tier. |

---

#### Category 2: Must Fix Before Industrial Pilot Deployment at PT Indmira (Pre-Pilot)

| ID | Feature / Fix | Target Persona | PRD Required? | Effort | User Problem Solved & Rationale |
| :---: | :--- | :--- | :---: | :---: | :--- |
| **PP-01** | **Role-Based Authentication & Session Access Control** | Plant Operations Manager | PRD Section 2 | **M** | Restricts tariff calibration, baseline updates, and raw file uploads to verified Energy Managers and plant engineers. |
| **PP-02** | **Binary PDF & Excel Spreadsheet Export** | Certified Energy Auditor | Yes (FR-08) | **M** | Provides downloadable `.pdf` and `.xlsx` artifacts required for formal ESDM audit submissions. |
| **PP-03** | **Local Bundling of Tailwind CSS & ApexCharts via Vite** | Utility Engineer / IT | NFR-04 (Air-Gap) | **S** | Guarantees the dashboard functions inside industrial control rooms without internet access. |
| **PP-04** | **Rate Limiting & Abuse Controls on AI Chat** | System Administrator | NFR-03 (Security) | **S** | Prevents API token exhaustion and denial-of-service on external LLM gateways. |
| **PP-05** | **Regulatory Parameter Version History & Audit Trail** | Compliance Officer | Yes (FR-07) | **M** | Records who modified electricity tariffs or emission factors, when, and from what prior value. |

---

#### Category 3: Recommended for Next Development Stage (Post-Hackathon Iteration)

| ID | Feature / Fix | Target Persona | PRD Required? | Effort | User Problem Solved & Rationale |
| :---: | :--- | :--- | :---: | :---: | :--- |
| **NX-01** | **Chart Synchronization on Live IoT Simulation** | Operator / Demoer | Yes (FR-05) | **S** | Streaming readings dynamically plot onto a real-time sliding strip chart. |
| **NX-02** | **Data Health & Telemetry Quality Summary** | Maintenance Engineer | Recommendation | **S** | Displays dataset health score (e.g. 99.4% completeness, zero missing timestamps, no out-of-range power factor). |
| **NX-03** | **Interactive Drill-Down from Anomaly to Time-Series Point** | Utility Engineer | Recommendation | **M** | Clicking an anomaly row centers and zooms the ApexCharts visualizer onto that specific timestamp. |
| **NX-04** | **Configurable Regional Grid Emission Presets** | Sustainability Officer | Yes (FR-07) | **S** | One-click dropdown to select Jamali (0.870), Sumatera (0.780), Kalimantan (0.890), or Sulawesi (0.740). |

---

#### Category 4: Future Enhancements (Commercial Product Roadmap)

| ID | Feature / Fix | Target Persona | PRD Required? | Effort | User Problem Solved & Rationale |
| :---: | :--- | :--- | :---: | :---: | :--- |
| **FT-01** | **Measurement & Verification (M&V) Tracking Module** | Energy Manager | Recommendation | **L** | Allows logging implementation dates for recommendations (e.g. contactor installed 15 Oct) and tracks post-intervention CUSUM divergence to verify realized savings. |
| **FT-02** | **Native Industrial IoT Gateway Protocols (Modbus-TCP / MQTT)** | Automation Engineer | Recommendation | **L** | Connects directly to Schneider / Siemens Digital Power Meters via Modbus-TCP or MQTT broker without manual CSV uploads. |
| **FT-03** | **Multi-Facility & Enterprise Multi-Tenancy** | Group ESG Director | Recommendation | **XL** | Allows PT Indmira Global Energi to manage multiple plant locations across Java, Sumatera, and Kalimantan from a single dashboard. |

---

#### Category 5: Not Recommended for Current Scope (Explicit Anti-Bloat Scope Exclusions)

| Capability | Rationale for Exclusion |
| :--- | :--- |
| **Microservices / Kubernetes Architecture** | Single-node Laravel + SQLite/PostgreSQL is entirely sufficient for high-frequency industrial plant logging (< 100,000 points/month). Microservices would add operational overhead without value. |
| **Automated Motor Actuation / Control Writeback** | Writing automated control signals back to factory PLC/SCADA systems poses industrial safety and liability hazards. IEEC must remain strictly a Decision Support System (Copilot). |
| **Automated Carbon Offset Trading Gateway** | Premature commercial feature. Focus must stay on energy conservation and mandatory ESDM compliance. |
