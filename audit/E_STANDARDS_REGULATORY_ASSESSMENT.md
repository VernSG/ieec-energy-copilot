# DELIVERABLE E — STANDARDS & REGULATORY ASSESSMENT
## Industrial Energy Efficiency Copilot (IEEC) — Prototype Alpha v1.2
**Audit Scope:** Mapping System Behavior Against Indonesian Energy Regulations and International Standards.

---

### 1. Regulatory & Standards Compliance Mapping Matrix

| Standard / Regulation | Juridical Status (per Oct 2026) | Assessed System Requirement | PRD Requirement | Implementation Support in Code | Available Source Code Evidence | Compliance Distinction (Software Support vs Formal Certification) |
| :--- | :--- | :--- | :---: | :---: | :--- | :--- |
| **PP No. 33 Tahun 2023**<br>(Konservasi Energi) | Active National Law | • Mandatory threshold evaluation ($\ge 4,000\text{ TOE/yr}$ for industry).<br>• Annual energy accounting across all sources (electric + thermal).<br>• Legal compliance notification triggering Energy Manager appointment. | Explicit (Section 1.1, 2, 3.1, FR-06) | **SUPPORTED**<br>(with minor omissions) | • [`DeterministicEnergyEngine.php:64-84`](file:///Users/yusuf/Documents/Project/Angel-Hack/app/Services/DeterministicEnergyEngine.php#L64-L84)<br>• [`dashboard.blade.php:1120-1189`](file:///Users/yusuf/Documents/Project/Angel-Hack/resources/views/dashboard.blade.php#L1120-L1189) | **Software Support Only.** The software correctly categorizes facilities into Mandatory or Voluntary regimes based on TOE thresholds. It does not constitute official POME filing or exempt the plant from formal ESDM inspection. |
| **Permen ESDM No. 8 Tahun 2025**<br>(Manajemen Energi) | Active Ministerial Regulation *(mencabut Permen ESDM 14/2012)* | • Payback period investment categorization (*No-Cost*, *Low-Cost*, *Medium-Cost*, *High-Cost*).<br>• Energy Manager facilitation checklist.<br>• Periodic audit worksheet generation. | Explicit (Section 1.1, 2, 3.6 C, 5.3, FR-04, FR-08) | **PARTIALLY SUPPORTED** | • Categories defined in [`DeterministicEnergyEngine.php:415-480`](file:///Users/yusuf/Documents/Project/Angel-Hack/app/Services/DeterministicEnergyEngine.php#L415-L480)<br>• Payback periods in code are static constants, not dynamic formulas.<br>• Printable HTML worksheet in [`esdm_audit_worksheet.blade.php`](file:///Users/yusuf/Documents/Project/Angel-Hack/resources/views/reports/esdm_audit_worksheet.blade.php). | **Software Support Only.** The recommendation matrix follows ESDM tiers, but does not substitute for a formal energy audit report certified by a BNSP/ESDM-accredited Energy Auditor (CEA). |
| **Permen ESDM No. 3 Tahun 2025**<br>(Konservasi Gedung Pemda/Pusat) & **SNI 6196:2011** | Active Ministerial Regulation & National Standard | • Standard Building IKE classification (Sangat Efisien, Efisien, Cukup Efisien, Agak Boros, Boros).<br>• Split evaluation for Air-Conditioned vs Non-AC facilities. | Explicit (Section 1.1, 2, 3.2, FR-02) | **SUPPORTED**<br>(for facilities $\le 5,000\text{ m}^2$) | • Threshold logic in [`DeterministicEnergyEngine.php:167-196`](file:///Users/yusuf/Documents/Project/Angel-Hack/app/Services/DeterministicEnergyEngine.php#L167-L196)<br>• Evaluates $8.50 \le \text{IKE} < 14.00$ (AC) and $2.00 \le \text{IKE} \le 3.40$ (Non-AC). | **Software Support Only.** Correctly calculates normalized monthly $\text{kWh/m}^2/\text{month}$. Missing thresholds for buildings $> 5,000\text{ m}^2$. |
| **SNI ISO 50001:2018**<br>(Sistem Manajemen Energi) | National / International Standard | • OLS Linear Regression for Energy Baseline (EnB).<br>• Baseline fit quality evaluation ($R^2 \ge 0.75$).<br>• Energy Performance Indicators (EnPI) tracking.<br>• Cumulative Sum (CUSUM) deviation monitoring. | Explicit (Section 1.1, 2, 3.3, FR-02) | **PARTIALLY SUPPORTED** | • Regression module in [`DeterministicEnergyEngine.php:350-393`](file:///Users/yusuf/Documents/Project/Angel-Hack/app/Services/DeterministicEnergyEngine.php#L350-L393)<br>• CUSUM array in [`DeterministicEnergyEngine.php:524-536`](file:///Users/yusuf/Documents/Project/Angel-Hack/app/Services/DeterministicEnergyEngine.php#L524-L536)<br>• CUSUM series in chart uses hardcoded slope constants rather than OLS output. | **Software Support Only.** Implements core ISO 50001 quantitative algorithms. The software itself is NOT ISO 50001 certified, and using it does not automatically confer ISO 50001 certification upon the facility. |
| **Permen ESDM No. 7 Tahun 2024**<br>(Tarif Tenaga Listrik PLN) | Active Ministerial Regulation | • Tariff structure for Industrial Customer I-3/TM (> 200 kVA).<br>• WBP surcharge via $K$ factor ($1.40 \le K \le 2.00$).<br>• Excess reactive energy penalty billing if monthly aggregate $\cos \varphi < 0.85$.<br>• Free reactive energy allocation ($0.62 \times \text{kWh}$). | Explicit (Section 1.1, 2, 3.5, FR-03, FR-07) | **SUPPORTED**<br>(with hardcoded WBP hours) | • Reactive penalty formula in [`DeterministicEnergyEngine.php:60-63, 246-249`](file:///Users/yusuf/Documents/Project/Angel-Hack/app/Services/DeterministicEnergyEngine.php#L60-L63)<br>• Formula is exact against PLN billing guidelines.<br>• Hardcodes 18:00–22:00 in filter predicates. | **Software Support Only.** Billing reconciliation accurately models PLN billing rules. Formal PLN utility bills remain the authoritative fiscal legal instrument. |
| **Ditjen Gatrik ESDM**<br>(Grid Emission Factors) | Official Publication | • Grid carbon intensity conversion factors ($\text{tCO}_2\text{e/MWh}$).<br>• Regional grid support (Jamali 0.870, Sumatera 0.780, Kalimantan 0.890, Sulawesi 0.740). | Explicit (Section 2, 3.6 A, FR-07) | **PARTIALLY SUPPORTED** | • Parameter in `regulatory_configs`<br>• Default Jamali (0.870) applied in [`DeterministicEnergyEngine.php:272`](file:///Users/yusuf/Documents/Project/Angel-Hack/app/Services/DeterministicEnergyEngine.php#L272)<br>• No UI dropdown for regional grid selection; grid name is hardcoded to "Jamali". | **Software Support Only.** Accurately converts active kWh savings to carbon reductions. Does not generate carbon credits or certified offsets. |

---

### 2. Legal Integrity Check on Revoked Regulations

The PRD establishes a strict regulatory compliance principle:
> *"Seluruh rujukan hukum lama yang telah dicabut—khususnya Permen ESDM No. 14 Tahun 2012 (Manajemen Energi) dan Permen ESDM No. 13 Tahun 2012 (Penghematan Tenaga Listrik; dicabut via Permen ESDM 9/2018)—dilarang keras dijadikan referensi legalitas dalam mesin analitik, pelaporan, maupun instruksi system prompt LLM."*

#### Code Audit Verification:
1. **PHP Codebase Search:**  
   A regex search across `app/`, `resources/`, and `database/` for `14/2012`, `14 Tahun 2012`, `13/2012`, or `13 Tahun 2012` yielded **zero references**.
2. **AI System Prompt Audit:**  
   In [`AiCopilotService.php:258-268`](file:///Users/yusuf/Documents/Project/Angel-Hack/app/Services/AiCopilotService.php#L258-L268), the prompt explicitly instructs:
   ```text
   DASAR HUKUM & STANDAR WAJIB ANDA:
   1. PP No. 33 Tahun 2023 tentang Konservasi Energi.
   2. Permen ESDM No. 8 Tahun 2025 tentang Manajemen Energi (Mencabut Permen ESDM 14/2012).
   ...
   ATURAN KEPATUHAN KETAT (STRICT COMPLIANCE RULES):
   2. LARANGAN RUJUKAN KEDALUWARSA: Dilarang keras mengutip Permen ESDM No. 14 Tahun 2012 atau Permen ESDM No. 13 Tahun 2012 karena telah resmi dicabut pemerintah.
   ```
3. **Live LLM Output Audit:**  
   In live chat sessions, questions regarding the legal basis of energy management prompted answers citing solely PP 33/2023 and Permen ESDM 8/2025.
4. **Verdict:** **FULL COMPLIANCE (100% Verified).**

---

### 3. Gap Analysis Between Prototype and Formal Compliance

To ensure technical and legal rigor, the following distinctions between the software prototype and formal compliance must be explicitly maintained in all communications:

1. **Energy Audit Worksheet vs Official Audit Report:**
   - The export generated by `/export-audit` is a **worksheet tool**, not a formal statutory audit report.
   - Under Permen ESDM No. 8 Tahun 2025, a formal periodic energy audit must be executed and signed by a certified Energy Auditor (*Auditor Energi Bersertifikat*) registered with BNSP / Ditjen EBTKE.
   - The current worksheet template includes simulated auditor signatures (`Ir. Bambang S., IPM, CEM` and `Dr. Eng. Haryanto, CEA`), which must be accompanied by an explicit disclaimer to prevent legal misrepresentation.

2. **Compliance Classification vs Statutory Reporting:**
   - Classifying a facility as $\ge 4,000\text{ TOE/yr}$ indicates **statutory eligibility** under PP 33/2023 Article 4.
   - It does not replace the mandated annual reporting to the Ministry of ESDM via the online POME (*Pelaporan Online Manajemen Energi*) application.

3. **CUSUM Baseline vs Formal ISO 50001 Certification:**
   - The linear baseline and CUSUM tracking provide operational decision support aligned with ISO 50001 Clause 6.5 (Energy Baselines) and Clause 6.6 (Energy Performance Indicators).
   - ISO 50001 certification is an organizational management system certification issued by an accredited certification body (KAN / ISO Registrar), not an attribute of software.
