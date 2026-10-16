# PRODUCT REQUIREMENTS DOCUMENT (PRD)
## Industrial Energy Efficiency Copilot (IEEC) — Prototype Alpha
**Versi 1.2 (Final Submission Ready) — Oktober 2026**

- **Kategori / Track Kompetisi:** Sustainable Energy — Topik #6 *Industrial Energy Efficiency Copilot* (ANGEL Innovation Hackathon 2026)
- **Industry Problem Owner:** **PT Indmira Global Energi** (Perusahaan Teknologi Lingkungan & Energi Berkelanjutan Nasional; Problem Owner Topik #4 *Smart Bioenergy Feedstock Optimization*, Topik #5 *Intelligent Waste-to-Energy Decision Engine*, dan Topik #6 *Industrial Energy Efficiency Copilot*)
- **Fasilitas Pabrik Mitra:** Fasilitas Pengolahan Biomassa & Bioenergi Terpadu (Reaktor Pirolisis *Biochar*, *Rotary Dryer*, *Crusher/Grinder* Bahan Baku, *Mixer* Formulasi Pupuk Hayati/Nutrisi Berkelanjutan), Utilitas Pabrik (Kompresor Udara Bertekanan, Pompa Air Sirkulasi, *Blower* Sistem Termal), serta Bangunan Fasilitas Pendukung (Gedung Operasional & Laboratorium R&D Bioteknologi/Bioenergi Ber-AC, dan Gudang Penyimpanan *Feedstock* Biomassa Non-AC).
- **Target Pengguna:** Manajer Energi Bersertifikat, Utility & Maintenance Engineer, Plant Operational Manager, serta Sustainability / ESG Compliance Officer.
- **Acuan Regulasi, Kebijakan Tarif & Standar Teknis (Status Resmi per Oktober 2026):**

| No | Regulasi / Standar Acuan | Status Yuridis | Peran dalam Arsitektur Sistem IEEC |
| :--- | :--- | :--- | :--- |
| 1 | **PP No. 33 Tahun 2023** tentang Konservasi Energi | Berlaku Nasional | Landasan yuridis kewajiban penerapan manajemen energi bagi pengguna energi dengan konsumsi $\ge$ 4.000 TOE/tahun (industri) dan $\ge$ 500 TOE/tahun (gedung). |
| 2 | **Permen ESDM No. 8 Tahun 2025** tentang Manajemen Energi *(ditetapkan 7 Maret 2025, mencabut Permen ESDM 14/2012)* | Berlaku Nasional | Kerangka operasional penunjukan Manajer Energi, penyusunan rencana program efisiensi, pelaksanaan audit energi berkala, serta klasifikasi rekomendasi audit berbasis *payback period* (*No-Cost*, *Low-Cost*, *Medium-Cost*, *High-Cost*). |
| 3 | **Permen ESDM No. 3 Tahun 2025** tentang Konservasi Energi oleh Pemerintah dan Pemerintah Daerah | Berlaku Nasional | Acuan standar Intensitas Konsumsi Energi (IKE) bangunan gedung berdasarkan tipologi fungsi dan luasan lantai (kategori $\le$ 5.000 m² dan > 5.000 m²) pada fasilitas riset dan gedung pendukung pabrik. |
| 4 | **SNI ISO 50001:2018** (Sistem Manajemen Energi) | Standar Nasional / Internasional | Metodologi penetapan Energy Baseline (EnB) melalui regresi linier OLS, penentuan Energy Performance Indicators (EnPI), dan pemantauan kinerja kumulatif (CUSUM). |
| 5 | **Permen ESDM No. 7 Tahun 2024** tentang Tarif Tenaga Listrik PT PLN (Persero) | Berlaku Nasional | Ketetapan penagihan tarif listrik industri golongan I-3/TM (> 200 kVA): diferensiasi jam WBP vs. LWBP melalui faktor $K$, serta penagihan kelebihan pemakaian daya reaktif (kVArh). |
| 6 | **Publikasi Ditjen Ketenagalistrikan (Gatrik) Kementerian ESDM** | Acuan Resmi | Nilai faktor emisi gas rumah kaca (GRK) jaringan ketenagalistrikan per sistem interkoneksi regional (tCO2e/MWh) untuk konversi dekarbonisasi terverifikasi. |
| 7 | **SNI 6196:2011 & Pedoman Teknis Konservasi Bangunan Gedung ESDM** | Standar Teknis | Klasifikasi batas toleransi IKE ruangan ber-AC (kWh/m²/bulan) dan ruangan non-AC pada bangunan fasilitas industri. |

> **Prinsip Kepatuhan Regulasi:** Seluruh rujukan hukum lama yang telah dicabut—khususnya Permen ESDM No. 14 Tahun 2012 (Manajemen Energi) dan Permen ESDM No. 13 Tahun 2012 (Penghematan Tenaga Listrik; dicabut via Permen ESDM 9/2018)—**dilarang keras** dijadikan referensi legalitas dalam mesin analitik, pelaporan, maupun instruksi *system prompt* LLM.

---

## 1. Latar Belakang, Problem Owner & Kriteria Seleksi

### 1.1 Pernyataan Masalah (Problem Statement)
PT Indmira Global Energi mengoperasikan fasilitas manufaktur bioenergi dan agro-teknologi terpadu yang memadukan proses termal bertemperatur tinggi (pirolisis biomassa menjadi biochar) dan proses mekanis berdaya besar (*grinding, crushing, rotary drying*, serta pemompaan utilitas). Fasilitas ini dilengkapi dengan Digital Power Meter pada tiap Sub-Distribution Panel (SDP). 

Kendati data telemetri historis konsumsi listrik tercatat dalam frekuensi tinggi, pabrik menghadapi 4 kendala utama:
1. **Data Terdiam (*Data Graveyard*):** Data log energi menumpuk tanpa diolah otomatis menjadi indikator kinerja terstandarisasi seperti *Specific Energy Consumption* (SEC/IKE Produksi) dan IKE Bangunan Gedung Pendukung sesuai mandat PP 33/2023 dan Permen ESDM 8/2025.
2. **Kebocoran Beban Tak Terdeteksi (*Invisible Waste*):** Tingginya konsumsi beban kosong (*idle load*) pada saat jam istirahat (*break*) dan pergantian giliran kerja (*off-shift*), kebocoran sistem udara bertekanan, serta lonjakan beban termal (*rotary dryer*) pada jam beban puncak PLN (WBP 18:00–22:00) yang memicu lonjakan rekening listrik akibat pengali tarif faktor $K$.
3. **Penalti Daya Reaktif (Biaya Kelebihan kVArh):** Fluktuasi beban motor induksi mesin pencacah biomassa menyebabkan penurunan faktor daya (*power factor* $\cos \varphi$) bulanan di bawah 0,85, menimbulkan tagihan penalti kVArh dari PLN yang signifikan.
4. **Ketiadaan Rekomendasi Preskriptif yang Layak Finansial:** Tim teknis pabrik membutuhkan asisten cerdas yang tidak hanya mendiagnosis pemborosan, tetapi juga menyusun daftar tindakan perbaikan (*actionable roadmap*) yang telah terklasifikasi berdasarkan periode pengembalian modal (*payback period*) sesuai kaidah audit energi formal Kementerian ESDM.

### 1.2 Tujuan Produk (Product Objectives)
1. **Otomasi Ingesti & Harmonisasi Data:** Menerima data historis telemetri energi (format CSV) maupun simulasi *stream* IoT real-time dari 4 zona panel utama fasilitas PT Indmira Global Energi.
2. **Kalkulasi Deterministik Akurat:** Menghitung IKE Produksi (kWh/ton atau kWh/batch biochar), memodelkan *Energy Baseline* (EnB) linier berbasis OLS, mengukur deviasi EnPI & CUSUM ISO 50001, serta menghitung IKE Bangunan Gedung (kWh/m²/bulan) sesuai SNI 6196 dan Permen ESDM 3/2025.
3. **Deteksi Anomali Multi-Vektor:** Mengidentifikasi kebocoran *idle load*, lonjakan anomali beban (*statistical Z-score spike*), konsumsi berlebih di jam WBP, serta proyeksi denda biaya kVArh PLN.
4. **Rekomendasi Preskriptif Terstruktur:** Menghasilkan rekomendasi efisiensi energi terarah yang diklasifikasikan ke dalam 4 tingkatan investasi Permen ESDM 8/2025 (*No-Cost, Low-Cost, Medium-Cost, High-Cost*) lengkap dengan estimasi penghematan Rupiah dan reduksi emisi CO2e.
5. **Kesiapan Kepatuhan Regulasi (Compliance Assurance):** Menyediakan verifikasi ambang kewajiban manajemen energi ($\ge$ 4.000 TOE/tahun) dan ekspor lembar kerja pendukung audit energi berkala.

### 1.3 Pemetaan Kriteria Penilaian ANGEL Innovation Hackathon 2026
Proposal dan Prototype Alpha IEEC dirancang secara presisi untuk memenuhi 5 pilar evaluasi seleksi ANGEL Innovation Hackathon 2026:

| No | Kriteria Evaluasi Seleksi | Bobot | Pembuktian Teknis & Nilai Unggul pada Prototype Alpha IEEC |
| :---: | :--- | :---: | :--- |
| **1** | **Problem-Solution Fit** | **20%** | Solusi menjawab langsung *pain points* nyata operasional pabrik biomassa PT Indmira Global Energi. Mengubah tumpukan data log daya pasif menjadi *decision support system* interaktif bagi Manajer Energi dan teknisi lapangan. |
| **2** | **Kesesuaian Tema & Kedalaman Teknis** *(Technical Relevance)* | **22%** | Kepatuhan mutlak terhadap regulasi energi Indonesia terbaru (PP 33/2023, Permen ESDM 8/2025, Permen ESDM 3/2025, Permen ESDM 7/2024), standar ISO 50001:2018 (regresi OLS, EnPI, CUSUM), serta faktor emisi jaringan Ditjen Gatrik ESDM. |
| **3** | **Inovasi & Kelayakan Desain Arsitektur** *(Innovation & Feasibility)* | **28%** | **Arsitektur Hibrida Anti-Halusinasi:** Pemisahan tegas antara *Deterministic Analytics Engine* (komputasi matematika berbasis kode Python/TypeScript deterministik) dengan *Generative AI Copilot* melalui mekanisme *Structured JSON Context Injection*. AI dilarang melakukan perhitungan liar; AI berfokus pada interpretasi naratif preskriptif. Menerapkan *Data Blending Architecture* dari 3 dataset industri riil Kaggle. |
| **4** | **Rencana Hilirisasi & Potensi Dampak** *(Research Downstreaming & Impact)* | **20%** | Potensi penghematan energi terbukti sebesar **4.120 kWh/bulan (8,54% efisiensi energi)**, penghematan finansial **Rp 5.609.586/bulan**, dan reduksi jejak karbon **3,58 Ton CO2e/bulan** pada skala fasilitas percontohan, dengan *roadmap* ekspansi implementasi ke seluruh unit bisnis bioenergi dan pabrik pupuk hayati PT Indmira Global Energi. |
| **5** | **Penguatan TKDN & Kekayaan Intelektual** *(Local Content & IP Advantage)* | **10%** | **100% Kemandirian Perangkat Lunak Lokal:** Logika algoritma deterministik, rumus penyesuaian tarif PLN I-3/TM, klasifikasi audit ESDM, dan skema orkestrasi prompt dikembangkan secara mandiri oleh tim pengembang dalam negeri tanpa ketergantungan pada vendor SaaS asing. Menjamin kedaulatan data industri (*industrial data sovereignty*) fasilitas energi nasional. |

---

## 2. Konfigurasi Regulasi & Parameter Sistem (FR-07 Engine Settings)

Seluruh parameter tarif kelistrikan, faktor pengali beban, ambang batas kepatuhan, dan faktor konversi dekarbonisasi bersifat **terbuka, bertanggal, memiliki riwayat versi (*version-controlled*), dan dapat dikalibrasi dinamis** melalui modul *Konfigurasi Regulasi* (FR-07). Hal ini menjamin fleksibilitas sistem terhadap penyesuaian tarif berkala (*tariff adjustment* triwulanan PLN) dan lokasi geografis grid pabrik.

| Parameter Sistem | Nilai Default (Demo) | Satuan | Sumber Legalitas / Ketetapan Teknis | Hak Akses Ubah |
| :--- | :--- | :---: | :--- | :---: |
| `tarif_lwbp_rp_kwh` | 1.035,78 | Rp/kWh | Tarif Tenaga Listrik PLN Golongan I-3/TM (> 200 kVA) berbasis Permen ESDM No. 7 Tahun 2024 (*Tariff Adjustment* Triwulan berjalan). | Ya (Manajer Energi) |
| `faktor_k_wbp` | 1,40 | Koefisien | Ketetapan Direksi PLN sesuai beban puncak grid lokal ($1,40 \le K \le 2,00$). Wajib disesuaikan dengan nilai $K$ pada rekening listrik pabrik. | Ya (Manajer Energi) |
| `tarif_wbp_rp_kwh` | 1.450,09 | Rp/kWh | Dihitung otomatis: `faktor_k_wbp` $\times$ `tarif_lwbp_rp_kwh` ($1,40 \times 1.035,78 = 1.450,092$). | Terkunci (Otomatis) |
| `jam_wbp_mulai` | 18:00 | Jam:Menit | Peraturan Pelaksana PLN Distribusi (Waktu Beban Puncak waktu setempat). | Ya (Manajer Energi) |
| `jam_wbp_selesai` | 22:00 | Jam:Menit | Peraturan Pelaksana PLN Distribusi (Total durasi WBP = 4 jam per hari). | Ya (Manajer Energi) |
| `tarif_kvarh_rp` | 1.114,74 | Rp/kVArh | Tarif penagihan kelebihan pemakaian energi reaktif industri Golongan I-3/TM. | Ya (Manajer Energi) |
| `batas_cos_phi` | 0,85 | Koefisien | Ambang batas faktor daya PLN. Penalti kVArh ditagihkan jika rata-rata bulanan < 0,85. | Ya (Manajer Energi) |
| `rasio_kvarh_bebas` | 0,62 | Koefisien | Alokasi pemakaian energi reaktif bebas biaya penalti ($0,62 \times \text{total kWh bulanan}$). | Ya (Manajer Energi) |
| `faktor_emisi_grid` | 0,870 | $\text{tCO}_2\text{e/MWh}$ | Publikasi Ditjen Ketenagalistrikan (Gatrik) Kementerian ESDM untuk Sistem Interkoneksi Jawa-Madura-Bali (Jamali). Tersedia opsi pemilihan grid Sumatera (0,780), Kalimantan (0,890), dan Sulawesi (0,740). | Ya (Manajer Energi) |
| `toe_per_mwh` | 0,086 | TOE/MWh | Faktor konversi energi listrik standar internasional IEA dan Ditjen EBTKE ESDM ($1\text{ MWh} \approx 0,086\text{ TOE}$). | Ya (Auditor) |
| `sbm_per_mwh` | 0,613 | SBM/MWh | Faktor Kesetaraan Setara Barel Minyak (*Barrel Oil Equivalent*) publikasi *Handbook of Energy & Economic Statistics of Indonesia* (HEESI) ESDM. | Ya (Auditor) |
| `ambang_wajib_toe_industri` | 4.000 | TOE/tahun | Batas kewajiban penerapan manajemen energi sektor industri sesuai PP 33/2023 & Permen ESDM 8/2025. | Terkunci (Regulasi) |
| `ambang_wajib_toe_gedung` | 500 | TOE/tahun | Batas kewajiban manajemen energi sektor bangunan gedung komersial/fasilitas pendukung. | Terkunci (Regulasi) |
| `ambang_enpi_normal_pct` | $\le$ 5,0 | % | Batas deviasi konsumsi aktual terhadap baseline ISO 50001 kategori wajar (Ambang Internal). | Ya (Engineer) |
| `ambang_enpi_waspada_pct` | 5,01 – 15,0 | % | Batas deviasi konsumsi aktual terhadap baseline kategori waspada/peringatan dini. | Ya (Engineer) |
| `ambang_enpi_boros_pct` | > 15,0 | % | Batas deviasi konsumsi aktual kategori pemborosan kritis/inefisiensi proses. | Ya (Engineer) |
| `ambang_zscore_spike` | 2,00 | Nilai $\sigma$ | Ambang deteksi anomali lonjakan daya menggunakan kaidah *Standard Normal Deviate*. | Ya (Engineer) |

---

## 3. Spesifikasi Rumus Teknis & Mesin Analitik Deterministik

Semua komputasi matematika dieksekusi secara deterministik pada backend engine (Node.js/Python microservice) untuk menjamin akurasi 100% tanpa risiko halusinasi numerik LLM.

### 3.1 Konversi Satuan Energi & Cek Ambang Kewajiban (PP 33/2023 & Permen ESDM 8/2025)
Sesuai Pasal 4 PP No. 33 Tahun 2023 dan Permen ESDM No. 8 Tahun 2025, kalkulasi batas ambang kewajiban manajemen energi wajib memperhitungkan **seluruh bauran energi** yang digunakan fasilitas selama 12 bulan berturut-turut (energi listrik jaringan PLN, pembangkit mandiri, biomassa bakar langsung, solar genset, LPG, maupun biogas).

$$\text{Total MWh}_{\text{listrik}} = \frac{\sum_{t=1}^{N} \text{energy\_kwh}_t}{1.000}$$

$$\text{TOE}_{\text{listrik\_tahunan}} = \left( \frac{\text{Total MWh}_{\text{listrik}}}{D} \times 365 \right) \times \text{toe\_per\_mwh}$$

$$\text{SBM}_{\text{listrik\_tahunan}} = \left( \frac{\text{Total MWh}_{\text{listrik}}}{D} \times 365 \right) \times \text{sbm\_per\_mwh}$$

$$\text{TOE}_{\text{total\_tahunan}} = \text{TOE}_{\text{listrik\_tahunan}} + \text{TOE}_{\text{non\_listrik\_input}}$$

**Logika Evaluasi Status Kepatuhan:**
- **Jika** $\text{TOE}_{\text{total\_tahunan}} \ge 4.000\text{ TOE/tahun}$ $\rightarrow$ **STATUS: WAJIB MANAJEMEN ENERGI (Mandatory Compliance)**. Memicu kewajiban hukum penunjukan Manajer Energi bersertifikat BNSP, penyusunan program konservasi energi tahunan, audit energi berkala oleh Auditor Energi bersertifikat minimal sekali dalam 3 tahun, serta pelaporan resmi berkala kepada Menteri ESDM melalui sistem POME Ditjen EBTKE.
- **Jika** $\text{TOE}_{\text{total\_tahunan}} < 4.000\text{ TOE/tahun}$ $\rightarrow$ **STATUS: DI BAWAH AMBANG (Manajemen Energi Sukarela / Voluntary)**. Fasilitas didorong menerapkan efisiensi energi sebagai keunggulan kompetitif dan reduksi biaya operasional sebelum mencapai skala wajib.

### 3.2 Analisis Intensitas Konsumsi Energi (IKE) Bangunan Gedung Pendukung
Berlaku khusus untuk zona fasilitas pendukung kantor, ruang kontrol, dan laboratorium riset biomassa (`BUILDING_AC`) serta gudang bahan baku (`BUILDING_NON_AC`). Rumus normalisasi periode 30 hari:

$$\text{IKE}_{\text{normalisasi\_bulanan}} = \left( \frac{\sum_{t=1}^{N} \text{energy\_kwh}_{\text{gedung}, t}}{\text{area\_sqm} \times D} \right) \times 30$$

Dimana $\text{area\_sqm}$ adalah luas lantai yang dikondisikan ($m^2$) dan $D$ adalah jumlah hari periode data.

#### Tabel Standar Acuan IKE Bangunan Gedung (SNI 6196 & Permen ESDM No. 3 Tahun 2025 Lampiran I):
Modul Konfigurasi Regulasi (FR-07) menyediakan profil ambang batas IKE bangunan gedung terkalibrasi berdasarkan luas lantai bangunan fasilitas (kategori $\le$ 5.000 m² dan > 5.000 m²) mengacu pada Lampiran I Permen ESDM No. 3 Tahun 2025:

| Kategori Tipologi Fasilitas | Sangat Efisien | Efisien | Cukup Efisien | Agak Boros | Boros |
| :--- | :---: | :---: | :---: | :---: | :---: |
| **Gedung Perkantoran / Lab Ber-AC ($\le$ 5.000 m²)** | < 8,50 | 8,50 – < 14,00 | 14,00 – < 18,50 | 18,50 – < 23,00 | $\ge$ 23,00 |
| **Gedung Fasilitas Ber-AC (> 5.000 m²)** | < 7,50 | 7,50 – < 12,00 | 12,00 – < 16,50 | 16,50 – < 20,50 | $\ge$ 20,50 |
| **Gudang Non-AC / Ruang Terbuka ($\text{kWh/m}^2\text{/bulan}$)** | < 2,00 | 2,00 – $\le$ 3,40 | 3,41 – 5,60 | 5,61 – 7,40 | > 7,40 |

### 3.3 Analisis IKE Produksi / KES (SEC) & Pemodelan Baseline ISO 50001
Pada lini produksi pengolahan biomassa (`SDP-01_LINE_A`) dan utilitas terdedikasi (`SDP-02_UTILITY`), efisiensi diukur terhadap *Energy Baseline* (EnB) linier fasilitas menggunakan metode regresi kuadrat terkecil (*Ordinary Least Squares / OLS*).

#### A. Konsumsi Energi Spesifik Aktual (Specific Energy Consumption / SEC)
$$\text{IKE}_{\text{produksi}} = \frac{E_{\text{mesin\_aktual\_kWh}}}{P_t} \quad (\text{satuan: kWh/batch atau kWh/ton biomassa})$$

#### B. Pemodelan Linier Energy Baseline (EnB) & Base Load ($E_0$)
$$E_{\text{prediksi}} = (a \cdot P_t) + E_0$$

Dimana koefisien konsumsi marjinal ($a$) dan beban dasar tanpa produksi / *Base Load* ($E_0$) diturunkan melalui:

$$a = \frac{n \sum (P_t \cdot E_t) - \sum P_t \sum E_t}{n \sum (P_t^2) - (\sum P_t)^2}$$

$$E_0 = \bar{E} - a \cdot \bar{P}$$

$$R^2 = \frac{\left[ n \sum (P_t \cdot E_t) - \sum P_t \sum E_t \right]^2}{\left[ n \sum (P_t^2) - (\sum P_t)^2 \right] \left[ n \sum (E_t^2) - (\sum E_t)^2 \right]}$$

*Ketentuan Validitas Baseline:* Model dinyatakan andal jika $R^2 \ge 0,75$. Jika $R^2 < 0,75$, sistem menampilkan notifikasi peringatan bahwa baseline memerlukan normalisasi variabel eksternal (misal: kadar air bahan baku biomassa atau variasi temperatur lingkungan *rotary dryer*). Nilai $E_0 > 0$ mengindikasikan beban siaga (*standby loss*), mesin menyala tanpa umpan material, atau kebocoran jaringan udara tekan.

#### C. Indikator Kinerja Energi (EnPI) & Cumulative Sum (CUSUM)
$$\text{Deviasi EnPI}_t (\%) = \left( \frac{E_{\text{aktual}, t} - E_{\text{prediksi}, t}}{E_{\text{prediksi}, t}} \right) \times 100$$

$$\text{CUSUM}_k = \sum_{t=1}^{k} \left( E_{\text{aktual}, t} - E_{\text{prediksi}, t} \right)$$

Tren grafik CUSUM dengan kemiringan menanjak positif ($\Delta \text{CUSUM} > 0$) mengonfirmasi terjadinya akumulasi pemborosan energi berkelanjutan terhadap target efisiensi pabrik.

### 3.4 Deteksi Anomali & Pemborosan Energi Operasional
1. **Pemborosan Beban Kosong (Idle Load Waste):**
   $$E_{\text{idle\_waste}} = \sum_{t \in T_{\text{idle}}} \max \left( 0, E_{\text{aktual}, t} - E_{\text{standby\_limit}} \right)$$
   Dimana $T_{\text{idle}}$ adalah himpunan jam ketika $P_t = 0$ dan `shift_state` bernilai `BREAK` (istirahat siang/malam) atau `OFF_SHIFT`.
2. **Lonjakan Konsumsi Berlebih (Spike Anomaly):**
   $$Z_t = \frac{E_{\text{aktual}, t} - \mu_{\text{jam\_serupa}}}{\sigma_{\text{jam\_serupa}}}$$
   Jika $Z_t > \text{ambang\_zscore}$ ($Z_t > 2,0$), jam tersebut dicatat sebagai anomali lonjakan daya (*Spike Waste*).
   $$E_{\text{spike\_waste}} = \sum_{t \in T_{\text{spike}}} \left( E_{\text{aktual}, t} - (\mu_{\text{jam\_serupa}} + 2 \sigma_{\text{jam\_serupa}}) \right)$$
3. **Beban Tergeser di Jam Puncak (WBP Load Shifting Potential):**
   Mendeteksi beban operasional non-kontinu (misal *rotary dryer* atau pengisian kompresor) yang berjalan pada jam 18:00–22:00 waktu setempat dengan daya besar yang dapat dipindahkan ke jam LWBP tanpa mengganggu target produksi:
   $$E_{\text{wbp\_shiftable}} = \sum_{t \in T_{\text{wbp\_shift}}} E_{\text{aktual}, t}$$
4. **Evaluasi Penurunan Faktor Daya ($\cos \varphi$):**
   Memantau penurunan $\cos \varphi_t < 0,85$ per jam sebagai sinyal peringatan dini kerusakan kapasitor bank atau beban induktif berlebih.

### 3.5 Biaya Listrik Industri PLN & Tagihan Daya Reaktif (I-3/TM > 200 kVA)
Struktur penagihan tarif tenaga listrik mengacu pada Permen ESDM No. 7 Tahun 2024:

#### A. Biaya Pemakaian Energi Aktif (kWh)
$$\text{Biaya LWBP} = \sum E_{\text{LWBP}} \times \text{tarif\_lwbp\_rp\_kwh}$$

$$\text{Biaya WBP} = \sum E_{\text{WBP}} \times (\text{faktor\_k\_wbp} \times \text{tarif\_lwbp\_rp\_kwh})$$

#### B. Biaya Kelebihan Pemakaian Daya Reaktif (kVArh) Bulanan
Dihitung secara kumulatif agregat pada akhir siklus pembacaan meter bulanan PLN:

$$\cos \varphi_{\text{rata\_bulanan}} = \frac{\text{Total kWh}}{\sqrt{(\text{Total kWh})^2 + (\text{Total kVArh})^2}}$$

$$\text{Batas Bebas Biaya kVArh} = \text{rasio\_kvarh\_bebas} \times \text{Total kWh} = 0,62 \times \text{Total kWh}$$

**Kondisi Pengenaan Biaya:**
$$\text{Jika } \cos \varphi_{\text{rata\_bulanan}} < 0,85 \text{ maka:}$$
$$\text{kVArh}_{\text{kena\_biaya}} = \max \left( 0, \text{Total kVArh} - \text{Batas Bebas Biaya kVArh} \right)$$
$$\text{Biaya kelebihan kVArh} = \text{kVArh}_{\text{kena\_biaya}} \times \text{tarif\_kvarh\_rp}$$
$$\text{Jika } \cos \varphi_{\text{rata\_bulanan}} \ge 0,85 \text{ maka } \text{Biaya kelebihan kVArh} = 0$$

### 3.6 Estimasi Penghematan Finansial, Reduksi Emisi & Klasifikasi Payback Period
Konsistensi matematika dijamin 100% selaras antara rumus backend, kartu skor antarmuka, dan payload JSON:

#### A. Konservasi Energi Fisik & Dekarbonisasi (Hanya dari Pemborosan Aktif)
Pergeseran beban WBP ke jam LWBP **tidak mengurangi** jumlah kWh fisik maupun emisi karbon, melainkan memanfaatkan disparitas tarif finansial PLN. Pengurangan konsumsi energi aktif murni berasal dari eliminasi *idle load* dan *spike*:

$$\text{Total Potensi Hemat kWh} = E_{\text{idle\_waste}} + E_{\text{spike\_waste}} = 2.450 + 1.670 = 4.120\text{ kWh/bulan}$$

$$\text{Reduksi Emisi GRK} = \left( \frac{\text{Total Potensi Hemat kWh}}{1.000} \right) \times \text{faktor\_emisi\_grid}$$

$$\text{Reduksi Emisi GRK} = \left( \frac{4.120}{1.000} \right) \times 0,870 = 3,5844 \approx \mathbf{3,58\text{ Ton CO}_2\text{e/bulan}}$$

#### B. Total Penghematan Finansial (Rupiah per Bulan)
Total efisiensi biaya merupakan akumulasi dari 3 komponen independen:

$$\text{Total Potensi Hemat Rp} = \text{Hemat}_{\text{eliminasi\_aktif}} + \text{Hemat}_{\text{geser\_wbp}} + \text{Biaya}_{\text{kvarh\_tereliminasi}}$$

1. **Hemat Eliminasi Pemborosan Aktif (Idle & Spike):**
   $$\text{Hemat}_{\text{eliminasi\_aktif}} = (E_{\text{idle\_waste}} + E_{\text{spike\_waste}}) \times \text{tarif\_lwbp\_rp\_kwh}$$
   $$\text{Hemat}_{\text{eliminasi\_aktif}} = 4.120\text{ kWh} \times \text{Rp } 1.035,78 = \mathbf{\text{Rp } 4.267.414}$$
   *(Rincian: Idle waste 2.450 kWh = Rp 2.537.661; Spike waste 1.670 kWh = Rp 1.729.753).*
2. **Hemat Geser Beban Puncak WBP ke LWBP:**
   $$\text{Hemat}_{\text{geser\_wbp}} = E_{\text{wbp\_shiftable}} \times (\text{tarif\_wbp} - \text{tarif\_lwbp})$$
   $$\text{Hemat}_{\text{geser\_wbp}} = 1.200\text{ kWh} \times (\text{Rp } 1.450,09 - \text{Rp } 1.035,78) = 1.200 \times \text{Rp } 414,31 = \mathbf{\text{Rp } 497.172}$$
3. **Eliminasi Biaya Kelebihan kVArh PLN:**
   $$\text{Biaya}_{\text{kvarh\_tereliminasi}} = \text{kVArh}_{\text{kena\_biaya}} \times \text{tarif\_kvarh\_rp}$$
   $$\text{Biaya}_{\text{kvarh\_tereliminasi}} = 758\text{ kVArh} \times \text{Rp } 1.114,74 = \mathbf{\text{Rp } 844.973} \quad (\approx \text{Rp } 845.000)$$

$$\text{Total Potensi Hemat Rp} = \text{Rp } 4.267.414 + \text{Rp } 497.172 + \text{Rp } 845.000 = \mathbf{\text{Rp } 5.609.586\text{ / bulan}}$$

#### C. Klasifikasi Rekomendasi Berbasis Payback Period (Permen ESDM No. 8 Tahun 2025)
Selaras dengan metodologi audit energi baku pada Permen ESDM No. 8 Tahun 2025, seluruh rekomendasi efisiensi wajib dikelompokkan ke dalam 4 tingkatan kelayakan investasi:

| Kategori Investasi | Definisi & Karakteristik Biaya | Kriteria Payback Period | Penerapan Spesifik di Pabrik PT Indmira Global Energi |
| :--- | :--- | :---: | :--- |
| **1. Tanpa Biaya** *(No-Cost)* | Tindakan berbasis disiplin operasional, *load scheduling*, *housekeeping*, dan penegakan SOP manual tanpa alokasi belanja modal (*Capex = 0*). | **0 Tahun (Instan)** | Menjadwalkan ulang operasional pemanasan *rotary dryer* dan kompresor utilitas SDP-02 keluar dari jam WBP (18:00–22:00) ke jam LWBP (Hemat Rp 497.172/bln). |
| **2. Biaya Rendah** *(Low-Cost)* | Otomasi minor, pemasangan sensor/timer, perbaikan insulasi termal, eliminasi kebocoran sistem tanpa modifikasi struktur utama. | **< 2 Tahun** | Memasang modul otomasi *timer/contactor interlock* auto-standby pada SDP-01 Line Pirolisis saat istirahat jam 12:00–13:00 (Investasi ~Rp 4 Juta; Hemat Rp 2.537.661/bln; Payback ~1,6 bulan). |
| **3. Biaya Menengah** *(Medium-Cost)* | Peningkatan subsistem penunjang kelistrikan dan utilitas pabrik, perbaikan kompensasi daya reaktif. | **2 – 4 Tahun** | Penambahan unit kapasitor bank otomatis (*Automatic Power Factor Controller / APFC*) pada SDP-01 guna menaikkan $\cos \varphi$ dari 0,81 ke $\ge$ 0,85 untuk menghapus biaya kVArh PLN (Investasi ~Rp 25 Juta; Hemat Rp 845.000/bln; Payback ~2,5 tahun). |
| **4. Biaya Tinggi** *(High-Cost)* | Penggantian mesin/aset utama (*retrofit* motor), pemasangan Variable Speed Drive (VSD), atau perombakan sistem termal. | **> 4 Tahun** | *Retrofit* motor induksi konvensional *crusher/grinder* biomassa ke motor efisiensi tinggi kelas IE3/IE4 serta integrasi VSD pada sistem blower sirkulasi pirolisis (Investasi ~Rp 180 Juta; Payback ~4,8 tahun). |

---

## 4. Gambaran Tata Letak Antarmuka (UI Dashboard Wireframe)

Antarmuka *single-page application* yang responsif, menyajikan visualisasi data komprehensif di sisi kiri dan panel asisten interaktif AI Copilot di sisi kanan. Seluruh metrik numerik pada kartu skor, grafik, tabel, dan rekomendasi AI **100% sinkron secara matematis**.

```text
+-----------------------------------------------------------------------------------------------------------------------------+
| [LOGO] IND-ENERGY COPILOT — PT Indmira Global Energi Facility   [Mode: (•) Data Historis CSV  ( ) IoT Live]   [Upload CSV]  |
+-----------------------------------------------------------------------------------------------------------------------------+
| FILTER: [Periode: 1 Sep - 30 Sep 2026 v]  [Panel: Semua Zona SDP v]  [Konfigurasi Regulasi (FR-07)]  [Export Audit ESDM]    |
+-----------------------------------------------------------------------------------------------------------------------------+
| KARTU RINGKASAN EKSEKUTIF (KPI SCORECARDS)                                                                                  |
| +------------------------+ +------------------------+ +------------------------+ +----------------------------------------+ |
| | TOTAL KONSUMSI ENERGI  | | RATA-RATA IKE PABRIK   | | ENERGI AKTIF TERBUANG  | | POTENSI PENGHEMATAN TOTAL              | |
| | 48.250 kWh / bulan     | | 0,52 kWh/batch         | | 4.120 kWh (8,54%)      | | Rp 5.609.586 / bulan                   | |
| | Setara 4,15 TOE/bulan  | | [STATUS: BOROS]        | | 14 Titik Anomali       | | • Hemat Aktif  : Rp 4.267.414/bln      | |
| | Proyeksi: 49,79 TOE/th | | Baseline: 0,35 kWh/u   | | • Idle  : 2.450 kWh    | | • Geser WBP    : Rp   497.172/bln      | |
| | Status: DI BAWAH AMBANG| | Deviasi : +48,57%      | | • Spike : 1.670 kWh    | | • Bebas kVArh  : Rp   845.000/bln      | |
| | (< 4.000 TOE Sukarela) | | (R² Baseline = 0,91)   | | (12 Idle, 2 Spike)     | | Reduksi Emisi  : -3,58 Ton CO2e/bulan  | |
| +------------------------+ +------------------------+ +------------------------+ +----------------------------------------+ |
+------------------------------------------------------------------------------+----------------------------------------------+
| GRAFIK TIME-SERIES PROFIL BEBAN & DETEKSI ANOMALI ENERGI (kWh)               | AI ENERGY EFFICIENCY COPILOT (ESDM READY)    |
| +--------------------------------------------------------------------------+ | +------------------------------------------+ |
| | kWh                                                                      | | | RINGKASAN AUDIT ENERGI — PERMEN ESDM 8/2025| |
| | 140|              /\              [!] Spike Beban Crusher (14:00)        | | | Fasilitas: Pengolahan Biomassa & Utilitas| |
| | 105|             /  \    /\                 /\                           | | | Status   : DI BAWAH AMBANG (Sukarela)    | |
| |  70|   /\  /\   /    \__/  \    [!] Idle    /  \   [!] WBP Peak (19:00)  | | |                                          | |
| |  35|  /  \/  \_/            \__ 12:00-13:00/    \_/\__________           | | | TEMUAN KRITIS DIAGNOSTIK:                | |
| |   0+------------------------------------------------------------> Waktu  | | | 1. SDP-01 (Line Pirolisis): KES 0,52     | |
| |     00:00   04:00   08:00   12:00   16:00   20:00   23:00                | | |    kWh/batch (+48,57% di atas baseline). | |
| |     [—— Garis Solid: Konsumsi Aktual]  [--- Garis Putus: EnB Baseline]   | | |    Base load E0 = 22,4 kWh saat istirahat| |
| +--------------------------------------------------------------------------+ | | 2. SDP-01 cos phi bulanan 0,81 (< 0,85), | |
|                                                                              | |    memicu tagihan penalti 758 kVArh.     | |
| TABEL MONITORING IKE PER ZONA PANEL (SNI 6196 & ISO 50001)                   | |                                          | |
| +--------------------+-------------+------------+------------+-------------+ | | REKOMENDASI TERKLASIFIKASI PAYBACK:       | |
| | Panel / Zona       | Konsumsi    | Nilai IKE  | Standar    | Status      | | | [NO-COST | Payback: 0 Thn]               | |
| +--------------------+-------------+------------+------------+-------------+ | | 1. Geser 1.200 kWh operasi rotary dryer  | |
| | SDP-01_LINE_A      | 22.400 kWh  | 0,52 kWh/b | 0,35 kWh/b | BOROS       | | |    SDP-02 dari jam WBP (18-22) ke LWBP.  | |
| | SDP-02_UTILITY     | 16.850 kWh  | 0,39 kWh/b | 0,35 kWh/b | WASPADA     | | |    Hemat: Rp 497.172/bulan.              | |
| | SDP-03_GEDUNG_UTAMA|  6.200 kWh  | 10,33 kWh/m| 14,0 kWh/m2| EFISIEN     | | | [LOW-COST | Payback: < 2 Thn (~1,6 Bln)] | |
| | SDP-04_GUDANG_FEED |  2.800 kWh  |  2,33 kWh/m|  3,4 kWh/m2| EFISIEN     | | | 2. Pasang auto-standby timer pd SDP-01   | |
| +--------------------+-------------+------------+------------+-------------+ | |    utk eliminasi 2.450 kWh idle load.    | |
| Total Akumulasi      | 48.250 kWh  | (Luas Gedung: 600 m² & 1.200 m²)      | | |    Hemat: Rp 2.537.661/bulan.            | |
|                                                                              | | [MEDIUM-COST | Payback: 2-4 Thn (~2,5 Thn)]| |
| LOG ANOMALI & PEMBOROSAN OPERASIONAL TERAKHIR                                | | 3. Tambah step APFC Capacitor Bank SDP-01  | |
| +------------------+-----------------+-----------------------+-------------+ | |    utk naikkan cos phi ke 0,88 (bebas    | |
| | Waktu Kejadian   | Lokasi Panel    | Tipe Pemborosan       | Kerugian Rp | | |    biaya kVArh). Hemat: Rp 845.000/bln.  | |
| +------------------+-----------------+-----------------------+-------------+ | | ---------------------------------------- | |
| | 14 Sep 12:00 WIB | SDP-01_LINE_A   | Idle Load (Prod = 0)  | Rp 185.000  | | | TANYA COPILOT ENERGI:                    | |
| | 18 Sep 19:00 WIB | SDP-02_UTILITY  | Beban Puncak Jam WBP  | Rp 310.000  | | | [Bagaimana cara menurunkan E0 Line A?  ] | |
| | 21 Sep 14:00 WIB | SDP-01_LINE_A   | Spike Anomali (Z=2,8) | Rp 215.000  | | | +--------------------------------------+ | |
| | 30 Sep 23:59 WIB | SDP-01_LINE_A   | Penalti kVArh Bulanan | Rp 845.000  | | | | Ketik pertanyaan teknis pabrik...    | | |
| +------------------+-----------------+-----------------------+-------------+ | | +--------------------------------------+ | |
+------------------------------------------------------------------------------+----------------------------------------------+
```

---

## 5. Arsitektur Alur Data & Spesifikasi Fungsional

### 5.1 Alur Kerja Sistem (Pipeline Architecture)
1. **Data Ingestion & Schema Sanitizer:** Pengguna mengunggah berkas CSV log energi historis atau memilih salah satu dari 2 *Preset Dataset*. Sistem memvalidasi kelengkapan 9 kolom standar, integritas tipe data, serta *timestamp* berbasis zona waktu (WIB/WITA/WIT).
2. **Dynamic Configuration Loader:** Memuat nilai aktif dari modul Konfigurasi Regulasi (FR-07): tarif LWBP, faktor $K$, batas jam WBP, tarif kVArh, faktor emisi grid wilayah, dan ambang batas deviasi EnPI.
3. **Deterministic Analytics Execution:** Backend engine memproses perhitungan matematika murni:
   - Evaluasi IKE Gedung Pendukung vs. SNI 6196 & Permen ESDM 3/2025.
   - Regresi OLS Energy Baseline, estimasi *Base Load* $E_0$, dan uji reliabilitas $R^2$.
   - Pemindaian jam *idle load*, perhitungan anomali lonjakan *Z-Score*, dan kalkulasi beban tergeser WBP.
   - Perhitungan cos phi bulanan dan penentuan penalti kelebihan kVArh.
   - Perhitungan konversi total konsumsi tahunan ke satuan TOE dan evaluasi ambang batas kepatuhan regulasi PP 33/2023.
4. **Structured JSON Context Injection:** Seluruh ringkasan analitik dan angka finansial dikompilasi ke dalam skema JSON deterministik yang tervalidasi skema (*schema-validated payload*).
5. **AI Inference & Prescription Generation:** Payload JSON disuntikkan ke dalam *system prompt* LLM. AI berperan menerjemahkan angka deterministik menjadi narasi diagnosis akar penyebab (*root cause*) dan mengelompokkan rekomendasi preskriptif ke dalam 4 kategori *Payback Period* Permen ESDM 8/2025.
6. **Dashboard Visualization & Audit Export:** Antarmuka web merender kartu skor KPI, diagram time-series interaktif, tabel audit panel, log pemborosan, serta panel dialog Copilot interaktif.

### 5.2 Daftar Kebutuhan Fungsional (Functional Requirements)

| Kode ID | Nama Modul Fitur | Deskripsi Fungsional & Spesifikasi Teknis | Prioritas |
| :--- | :--- | :--- | :---: |
| **FR-01** | Historical CSV Upload & Data Sanitizer | Menerima berkas log historis berformat CSV, validasi skema 9 kolom, parsing tanggal & zona waktu, verifikasi kelengkapan baris data. | **P0 (Wajib)** |
| **FR-02** | Deterministik IKE & Baseline Engine | Menghitung IKE Gedung (kWh/m²/bln), KES Produksi (kWh/batch), regresi OLS baseline EnB ($E_0, a, R^2$), serta kalkulasi deret deviasi EnPI & CUSUM ISO 50001. | **P0 (Wajib)** |
| **FR-03** | Multi-Vector Anomaly & Penalty Engine | Memindai anomali beban kosong (*idle waste* saat istirahat), lonjakan konsumsi (*Z-Score* > 2,0), beban jam WBP, serta kalkulasi penalti kVArh bulanan ($\cos \varphi < 0,85$). | **P0 (Wajib)** |
| **FR-04** | AI Energy Efficiency Copilot | Menghasilkan ringkasan audit naratif, diagnosis akar masalah teknis, rekomendasi tindakan terklasifikasi 4 *payback period*, serta antarmuka chat interaktif. | **P0 (Wajib)** |
| **FR-05** | Mockup Live IoT Telemetry Streaming | Simulasi aliran data telemetri Digital Power Meter per 3 detik untuk mendemonstrasikan kesiapan integrasi arsitektur industri masa depan (*Industry 4.0 IoT ready*). | **P1 (Nilai Tambah)** |
| **FR-06** | Kalkulator TOE & Status Kepatuhan PP 33/2023 | Konversi konsumsi total listrik ke TOE/SBM, formulir input energi non-listrik termal biomassa/gas, serta indikator visual status kewajiban manajemen energi ($\ge$ 4.000 TOE). | **P1 (Nilai Tambah)** |
| **FR-07** | Modul Konfigurasi Regulasi & Kalibrasi Grid | Panel pengaturan dinamis tarif LWBP, faktor pengali $K$, jam WBP, tarif kVArh, faktor emisi grid per regional, dan ambang EnPI internal. Menyimpan log versi parameter. | **P0 (Wajib)** |
| **FR-08** | Generator Lembar Kerja Audit Energi ESDM | Fasilitas ekspor ringkasan performa energi ke format PDF/Excel siap pakai sebagai dokumen pendukung Manajer Energi dalam audit energi berkala Permen ESDM 8/2025. | **P1 (Nilai Tambah)** |

### 5.3 Pemetaan Fungsional terhadap Siklus Manajemen Energi (Permen ESDM 8/2025)

| Siklus Wajib Permen ESDM 8/2025 | Dukungan Fungsional Sistem IEEC | Kategori Tindakan Terkait |
| :--- | :--- | :--- |
| **1. Penunjukan & Fasilitasi Manajer Energi** | IEEC berfungsi sebagai *cockpit* kerja harian Manajer Energi untuk memantau indikator EnPI tanpa perlu mengolah *spreadsheet* manual. | Tata Kelola Organisasi |
| **2. Penyusunan Program Efisiensi Energi** | Menyediakan proyeksi kuantitatif penghematan biaya (Rp/bln) dan reduksi emisi (tCO2e/bln) untuk justifikasi pengajuan anggaran ke dewan direksi. | Perencanaan Strategis |
| **3. Pelaksanaan Audit Energi Berkala** | Modul FR-02, FR-03, dan FR-08 menyediakan rekap data historis, identifikasi IKE zona boros, dan bukti matematis *Base Load* $E_0$ untuk auditor energi. | Audit & Pengukuran Lapangan |
| **4. Implementasi Rekomendasi Audit Terstruktur** | Mengklasifikasikan usulan perbaikan ke dalam matriks *No-Cost*, *Low-Cost*, *Medium-Cost*, dan *High-Cost* untuk memudahkan penentuan prioritas eksekusi. | Eksekusi Rekomendasi |
| **5. Verifikasi & Pemantauan Pasca-Tindakan** | Grafik CUSUM dan monitoring deviasi EnPI pasca-perbaikan membuktikan apakah tindakan efisiensi benar-benar menurunkan konsumsi energi secara riil. | Verifikasi *Measurement & Verification* (M&V) |

---

## 6. Arsitektur Data Blending Kaggle & Skema Input Dataset

### 6.1 Riset & Kurasi Kombinasi Dataset Publik Kaggle (*Data Blending Architecture*)
Untuk membangun prototipe yang realistis dan memiliki integritas data industri tinggi tanpa membuka kerahasiaan data rahasia (*non-disclosure agreement*) pabrik mitra pada tahap awal seleksi, IEEC menerapkan teknik **Data Blending Architecture** dengan mengombinasikan dan mentransformasikan 3 dataset industri publik terkemuka dari platform Kaggle:

```text
+------------------------------------------------------------------------------------------------------+
| KAGGLE PUBLIC DATASET SOURCES                                                                        |
+------------------------------------+ +-----------------------------------+ +-------------------------+
| [1] Telemetri Kelistrikan Industri | | [2] Korelasi Produksi & Mesin     | | [3] IKE Fasilitas Gedung|
| "Steel Industry Energy Consumption"| | "Smart Manufacturing Efficiency"  | | "Building Data Genome 2"|
| (Kaggle: cankats / UCI Repository) | | (Kaggle: rabieelkharoua)          | | (BUDS Lab — MIT License)|
+------------------------------------+ +-----------------------------------+ +-------------------------+
                  |                                      |                                |
                  v                                      v                                v
+------------------------------------------------------------------------------------------------------+
| DATA BLENDING & HARMONIZATION PIPELINE (ETL Script)                                                  |
| • Sinkronisasi Resampling Deret Waktu (Hourly Timestamps + Offset Zona Waktu +07:00 WIB)             |
| • Pemetaan Profil Beban ke Zona SDP Fasilitas Bioenergi PT Indmira Global Energi                      |
| • Penyesuaian Variabel Output: Batch/Ton Biochar & Luas Lantai Gedung Pendukung (m²)                 |
+------------------------------------------------------------------------------------------------------+
                                                  |
                                                  v
+------------------------------------------------------------------------------------------------------+
| DATASET AKHIR STANDAR IEEC: dataset_energi_pabrik.csv (Tersedia dlm 2 Preset: Sukarela & Wajib)       |
+------------------------------------------------------------------------------------------------------+
```

#### Rincian Spesifikasi Sumber Dataset Kaggle:

1. **Dataset 1: Telemetri Kelistrikan Panel Industri**
   - **Nama Dataset:** *Steel Industry Energy Consumption Dataset*
   - **Penerbit / Sumber Kaggle:** DAEWOO Steel Co. Ltd via UCI Machine Learning Repository / Cankat Sen (`kaggle.com/datasets/cankats/steel-industry-energy-consumption`).
   - **Atribut Asli Terpilih:** `date`, `Usage_kWh`, `Lagging_Current_Reactive_Power_kVarh`, `Lagging_Current_Power_Factor`, `Load_Type` (*Light Load, Medium Load, Maximum Load*), `NSM` (*Number of Seconds from Midnight*).
   - **Formula Transformasi ke IEEC:**
     - `timestamp`: Format `date` dikonversi ke ISO-8601 dengan offset zona waktu lokal (`YYYY-MM-DD HH:MM:00+07:00`).
     - `energy_kwh`: Menggunakan kolom `Usage_kWh` (agregasi 4 interval 15-menitan menjadi konsumsi per 1 jam).
     - `power_factor`: `Lagging_Current_Power_Factor / 100` (mengubah skala persentase 0–100 ke skala desimal standar kelistrikan 0,00–1,00).
     - `kvarh`: `Lagging_Current_Reactive_Power_kVarh` (dijumlahkan per jam).
     - `shift_state`: Dipetakan dari `NSM` dan `Load_Type` (jika jam 12:00–13:00 atau akhir pekan $\rightarrow$ `BREAK` / `OFF_SHIFT`; jam kerja normal $\rightarrow$ `OPERATIONAL`).

2. **Dataset 2: Korelasi Output Produksi & Pemodelan Baseline Mesin**
   - **Nama Dataset:** *Smart Manufacturing Resource Efficiency Dataset*
   - **Penerbit / Sumber Kaggle:** Rabie El Kharoua (`kaggle.com/datasets/rabieelkharoua/smart-manufacturing-resource-efficiency-dataset`).
   - **Atribut Asli Terpilih:** `Timestamp`, `Production_Output` (unit/ton), `Energy_Consumed` (kWh), `Operational_Cycle`.
   - **Formula Transformasi ke IEEC:**
     - `production_qty`: Mengambil korelasi linier marjinal antara output fisik dan konsumsi daya untuk merepresentasikan jumlah batch biochar teraktivasi atau ton pupuk organik yang diproses pada lini `SDP-01_LINE_A` (bernilai 0 saat `shift_state` = `BREAK` atau `OFF_SHIFT`).
     - Berfungsi langsung untuk pengujian deterministik regresi linier OLS Energy Baseline: $E = a \cdot P_t + E_0$ dan pembuktian $R^2 \ge 0,75$.

3. **Dataset 3: Profil Konsumsi IKE Bangunan Gedung Pendukung**
   - **Nama Dataset:** *Building Data Genome Project 2 (Non-Competition Open Dataset — MIT License)*
   - **Penerbit / Sumber Repositori:** BUDS Lab / Clayton Miller et al., National University of Singapore (`github.com/buds-lab/building-data-genome-project-2` & *Scientific Data*).
   - **Berkas / Atribut Asli Terpilih:**
     - `metadata.csv`: `building_id`, `primary_use` (*Office, Laboratory, Warehouse/storage*), `square_feet` / `sqm`.
     - `electricity_cleaned.csv`: Time-series konsumsi daya listrik per jam (kWh) untuk tipe fasilitas *office/laboratory* dan *warehouse*.
   - **Formula Transformasi ke IEEC:**
     - Luas Lantai: `area_sqm` diambil langsung dari metadata luasan gedung dan dikalibrasi ke luasan 600 m² untuk Gedung Utama & Lab R&D (`SDP-03_GEDUNG_UTAMA`), serta 1.200 m² untuk Gudang Feedstock (`SDP-04_GUDANG_FEEDSTOCK`).
     - `energy_kwh`: Konsumsi meteran listrik bangunan diambil dari kolom meteran relevan pada `electricity_cleaned.csv`, dinormalisasi ke luasan lantai fasilitas PT Indmira Global Energi guna mensimulasikan nilai IKE bulanan pada rentang realistis SNI 6196 dan Permen ESDM 3/2025.

### 6.2 Pemetaan Struktur Panel SDP Fasilitas Bioenergi PT Indmira Global Energi
Struktur hierarki kelistrikan di dalam dataset dipetakan secara akurat sesuai tata letak operasional pabrik:

1. **`SDP-01_LINE_A` (Lini Pengolahan Biomassa & Pirolisis Biochar):**
   - Kategori: `PRODUCTION` (Parameter acuan: `production_qty` dalam satuan batch biochar).
   - Profil Beban: Reaktor pirolisis, motor *crusher* biomassa, dan *mixer* formulasi nutrisi. Menunjukkan fenomena *Base Load* ($E_0$) tinggi saat jam istirahat akibat motor dibiarkan menyala tanpa beban serta penurunan $\cos \varphi$ bulanan (0,81) yang memicu biaya kelebihan kVArh.
2. **`SDP-02_UTILITY` (Sistem Utilitas Kompresor Udara & Rotary Dryer):**
   - Kategori: `PRODUCTION` (Parameter acuan: jam operasional kompresor & sistem termal).
   - Profil Beban: Pemanas dan *blower rotary dryer* biomassa serta kompresor udara sentral. Menunjukkan anomali lonjakan beban tinggi pada jam WBP (18:00–22:00) yang berpotensi digeser (*load shifting*) ke jam LWBP.
3. **`SDP-03_GEDUNG_UTAMA` (Kantor Operasional & Laboratorium R&D Bioteknologi):**
   - Kategori: `BUILDING_AC` (Parameter acuan: `area_sqm` = 600 m²).
   - Profil Beban: Pendingin udara (AC sentral/split), instrumentasi laboratorium bioteknologi, pencahayaan, dan beban komputer kantor operasional.
4. **`SDP-04_GUDANG_FEEDSTOCK` (Gudang Penyimpanan Bahan Baku Biomassa):**
   - Kategori: `BUILDING_NON_AC` (Parameter acuan: `area_sqm` = 1.200 m²).
   - Profil Beban: Sistem ventilasi mekanis, pencahayaan gedung tinggi, dan stasiun pengisian daya *forklift* listrik.

### 6.3 Spesifikasi 2 Preset Dataset Bawaan Sistem (Demo Presets)
Aplikasi menyediakan 2 preset dataset siap pakai untuk mendemonstrasikan fleksibilitas evaluasi regulasi:

- **Preset 1 (`preset_pilot_plant_sukarela.csv`):**
  - Profil: Fasilitas *pilot plant* dan lini produksi biomassa skala percontohan menengah (total konsumsi 48.250 kWh/bulan $\approx$ 4,15 TOE/bulan $\rightarrow$ estimasi 49,79 TOE/tahun energi listrik; input energi termal non-listrik = 0 TOE).
  - Evaluasi Kepatuhan: Total 49,79 TOE/tahun jauh di bawah batas 4.000 TOE/tahun.
  - Status Tampilan Sistem: **"DI BAWAH AMBANG (< 4.000 TOE/tahun — Manajemen Energi Sukarela)"**. Menampilkan kapabilitas IEEC dalam memandu efisiensi finansial sukarela.
- **Preset 2 (`preset_industrial_plant_wajib.csv`):**
  - Profil: Fasilitas industri manufaktur bioenergi komersial skala penuh terintegrasi (konsumsi listrik MDP utama 3.200.000 kWh/bulan $\approx$ 3.302 TOE/tahun + input energi termal biomassa/gas alam sebesar 3.800 TOE/tahun $\rightarrow$ total akumulasi energi tahunan = **7.102 TOE/tahun**).
  - Evaluasi Kepatuhan: Total 7.102 TOE/tahun melampaui batas ambang $\ge$ 4.000 TOE/tahun.
  - Status Tampilan Sistem: **"WAJIB MANAJEMEN ENERGI (>= 4.000 TOE/tahun — Mandat PP 33/2023 & Permen ESDM 8/2025)"**. Mengaktifkan modul daftar periksa (*checklist*) kepatuhan hukum penunjukan Manajer Energi dan penjadwalan audit energi berkala.

### 6.4 Skema Data Input Standar (`dataset_energi_pabrik.csv`)

| Nama Kolom | Tipe Data | Contoh Nilai | Deskripsi & Validasi Integritas |
| :--- | :--- | :--- | :--- |
| `timestamp` | DATETIME | `2026-09-14 12:00:00+07:00` | Format ISO-8601 dengan penanda zona waktu lokal (+07:00 WIB). Menentukan klasifikasi jam kerja dan jam LWBP/WBP. |
| `panel_id` | STRING | `SDP-01_LINE_A` | Kunci identifikasi unik Sub-Distribution Panel dalam hierarki pabrik. |
| `category` | ENUM | `PRODUCTION` | Menentukan rumus analitik: `PRODUCTION` (IKE Produksi/SEC), `BUILDING_AC` (IKE Luas Ber-AC), `BUILDING_NON_AC` (IKE Luas Non-AC). |
| `energy_kwh` | FLOAT | `45.20` | Konsumsi energi aktif interval per jam ($\text{kWh}$). Nilai harus $\ge 0$. |
| `power_factor` | FLOAT | `0.81` | Faktor daya rata-rata jam tersebut ($\cos \varphi$, rentang 0,00 s.d. 1,00). Menjadi sinyal pemantauan dini denda penalti kVArh. |
| `kvarh` | FLOAT | `31.50` | Konsumsi energi reaktif per jam ($\text{kVArh}$). Digunakan untuk kalkulasi penagihan resmi daya reaktif bulanan. |
| `production_qty` | INTEGER | `120` | Jumlah output produksi fisik (batch biochar atau ton). Wajib 0 untuk kategori bangunan atau saat jam istirahat. |
| `area_sqm` | FLOAT | `600.0` | Luas lantai fasilitas ($m^2$). Terisi untuk kategori bangunan; bernilai 0 untuk panel lini produksi. |
| `shift_state` | ENUM | `BREAK` | Status operasional giliran kerja: `OPERATIONAL`, `BREAK` (istirahat), atau `OFF_SHIFT` (luar jam kerja). Validasi utama pemborosan *idle load*. |

---

## 7. Kontrak Payload JSON Backend & System Prompt AI Copilot

### 7.1 Contoh Payload JSON Backend Deterministik (Tervalidasi Skema)
Payload berikut dihasilkan 100% oleh *Deterministic Analytics Engine* tanpa campur tangan LLM, menjamin angka yang bebas halusinasi:

```json
{
  "audit_metadata": {
    "project_name": "IEEC Prototype Alpha - ANGEL Innovation Hackathon 2026",
    "facility_name": "Fasilitas Pengolahan Biomassa Terpadu PT Indmira Global Energi",
    "periode_audit": "2026-09-01 s/d 2026-09-30",
    "jumlah_hari_data": 30,
    "timestamp_analisis": "2026-10-15T20:00:00+07:00"
  },
  "konfigurasi_regulasi": {
    "versi_regulasi": "ESDM-2026-Q4-v1.2",
    "tarif_lwbp_rp_kwh": 1035.78,
    "faktor_k_wbp": 1.4,
    "tarif_wbp_rp_kwh": 1450.09,
    "jam_wbp": "18:00-22:00 WIB",
    "tarif_kvarh_rp": 1114.74,
    "batas_cos_phi": 0.85,
    "rasio_kvarh_bebas": 0.62,
    "faktor_emisi_grid_tco2e_per_mwh": 0.87,
    "nama_sistem_grid": "Sistem Interkoneksi Jawa-Madura-Bali (Jamali)",
    "sumber_faktor_emisi": "Publikasi Resmi Ditjen Ketenagalistrikan Kementerian ESDM"
  },
  "kepatuhan_manajemen_energi": {
    "dasar_hukum": "PP No. 33 Tahun 2023 & Permen ESDM No. 8 Tahun 2025",
    "total_listrik_bulanan_kwh": 48250,
    "total_listrik_bulanan_mwh": 48.25,
    "konsumsi_toe_bulanan": 4.15,
    "proyeksi_toe_tahunan_listrik": 49.79,
    "input_energi_non_listrik_toe_tahunan": 0.0,
    "total_konsumsi_energi_tahunan_toe": 49.79,
    "ambang_wajib_industri_toe": 4000,
    "status_kepatuhan": "DI BAWAH AMBANG (sukarela)",
    "implikasi_hukum": "Fasilitas belum terkena kewajiban formal penunjukan Manajer Energi bersertifikat dan audit berkala ESDM, namun efisiensi mandiri sangat dianjurkan untuk reduksi OPEX."
  },
  "analisis_ike_per_panel": [
    {
      "panel_id": "SDP-01_LINE_A",
      "deskripsi": "Lini Pengolahan Biomassa & Pirolisis Biochar",
      "kategori": "PRODUCTION",
      "konsumsi_kwh": 22400,
      "porsi_konsumsi_pct": 46.42,
      "output_produksi_total": 43077,
      "satuan_output": "batch",
      "ike_aktual": 0.52,
      "baseline_enb": 0.35,
      "deviasi_enpi_pct": 48.57,
      "base_load_e0_kwh": 22.4,
      "r2_baseline": 0.91,
      "cos_phi_rata_rata": 0.81,
      "status_evaluasi": "BOROS (Deviasi EnPI > 15%, ambang internal)"
    },
    {
      "panel_id": "SDP-02_UTILITY",
      "deskripsi": "Utilitas Kompresor Udara & Rotary Dryer",
      "kategori": "PRODUCTION",
      "konsumsi_kwh": 16850,
      "porsi_konsumsi_pct": 34.92,
      "output_produksi_total": 43077,
      "satuan_output": "batch",
      "ike_aktual": 0.39,
      "baseline_enb": 0.35,
      "deviasi_enpi_pct": 11.43,
      "base_load_e0_kwh": 14.1,
      "r2_baseline": 0.88,
      "cos_phi_rata_rata": 0.86,
      "status_evaluasi": "WASPADA (5% < Deviasi EnPI <= 15%, ambang internal)"
    },
    {
      "panel_id": "SDP-03_GEDUNG_UTAMA",
      "deskripsi": "Gedung Kantor & Laboratorium R&D Bioenergi Ber-AC",
      "kategori": "BUILDING_AC",
      "konsumsi_kwh": 6200,
      "porsi_konsumsi_pct": 12.85,
      "area_sqm": 600.0,
      "ike_aktual": 10.33,
      "standar_acuan": "SNI 6196 & Permen ESDM 3/2025: 8,50 - < 14,00 kWh/m2/bulan (Efisien)",
      "status_evaluasi": "EFISIEN"
    },
    {
      "panel_id": "SDP-04_GUDANG_FEEDSTOCK",
      "deskripsi": "Gudang Penyimpanan Bahan Baku Biomassa Non-AC",
      "kategori": "BUILDING_NON_AC",
      "konsumsi_kwh": 2800,
      "porsi_konsumsi_pct": 5.81,
      "area_sqm": 1200.0,
      "ike_aktual": 2.33,
      "standar_acuan": "SNI 6196 & Permen ESDM 3/2025: 2,00 - <= 3,40 kWh/m2/bulan (Efisien)",
      "status_evaluasi": "EFISIEN"
    }
  ],
  "temuan_pemborosan_dan_finansial": {
    "idle_load_waste_kwh": 2450,
    "spike_anomaly_waste_kwh": 1670,
    "total_potensi_hemat_kwh": 4120,
    "hemat_eliminasi_idle_dan_spike_rp": 4267414,
    "wbp_shiftable_kwh": 1200,
    "hemat_geser_wbp_rp": 497172,
    "cos_phi_terendah_panel_bulanan": 0.81,
    "cos_phi_agregat_mdp_bulanan": 0.84,
    "kvarh_kena_biaya": 758,
    "biaya_kvarh_rp": 845000,
    "total_potensi_hemat_rp": 5609586,
    "potensi_reduksi_co2_ton": 3.58
  },
  "klasifikasi_rekomendasi_audit_esdm": [
    {
      "kategori_investasi": "No-Cost",
      "kriteria_payback": "0 Tahun (Instan / Tanpa Modal)",
      "judul_tindakan": "Load Shifting Jam Operasional Rotary Dryer & Kompresor dari WBP ke LWBP",
      "lokasi_panel": "SDP-02_UTILITY",
      "deskripsi_teknis": "Menggeser 1.200 kWh beban termal rotary dryer dan siklus pengisian kompresor udara dari jam beban puncak PLN (18:00-22:00 WIB) ke jam LWBP (pukul 22:30 WIB) melalui revisi SOP jadwal kerja operator tanpa mempengaruhi total output harian.",
      "potensi_hemat_kwh": 0,
      "potensi_hemat_rp_per_bulan": 497172,
      "potensi_reduksi_co2_ton": 0.0,
      "estimasi_biaya_implementasi_rp": 0,
      "payback_period_tahun": 0.0
    },
    {
      "kategori_investasi": "Low-Cost",
      "kriteria_payback": "< 2 Tahun",
      "judul_tindakan": "Instalasi Auto-Standby Timer & Modul Kontaktor Interlock pada Lini Pirolisis",
      "lokasi_panel": "SDP-01_LINE_A",
      "deskripsi_teknis": "Memasang modul timer otomasi pada panel kontrol motor pengumpan biomassa dan mixer agar secara otomatis masuk ke mode sleep/standby saat operator istirahat siang (12:00-13:00 WIB) dan pergantian shift, mengeliminasi beban kosong 2.450 kWh.",
      "potensi_hemat_kwh": 2450,
      "potensi_hemat_rp_per_bulan": 2537661,
      "potensi_reduksi_co2_ton": 2.13,
      "estimasi_biaya_implementasi_rp": 4000000,
      "payback_period_tahun": 0.13
    },
    {
      "kategori_investasi": "Low-Cost",
      "kriteria_payback": "< 2 Tahun",
      "judul_tindakan": "Pemberantasan Kebocoran Udara Tekan & Eliminasi Lonjakan Anomali Beban",
      "lokasi_panel": "SDP-01_LINE_A & SDP-02_UTILITY",
      "deskripsi_teknis": "Melakukan perbaikan insulasi dan audit kebocoran nozzle kompresor udara menggunakan ultrasonic leak detector guna memangkas anomali lonjakan daya sebesar 1.670 kWh.",
      "potensi_hemat_kwh": 1670,
      "potensi_hemat_rp_per_bulan": 1729753,
      "potensi_reduksi_co2_ton": 1.45,
      "estimasi_biaya_implementasi_rp": 3500000,
      "payback_period_tahun": 0.17
    },
    {
      "kategori_investasi": "Medium-Cost",
      "kriteria_payback": "2 - 4 Tahun",
      "judul_tindakan": "Pemasangan Automatic Power Factor Controller (APFC) & Kapasitor Bank 50 kVAR",
      "lokasi_panel": "SDP-01_LINE_A",
      "deskripsi_teknis": "Menambahkan unit kapasitor bank otomatis 50 kVAR pada SDP-01 untuk mengompensasi beban induktif motor crusher, menaikkan cos phi rata-rata bulanan dari 0,81 ke >= 0,88, sehingga sepenuhnya menghapus tagihan penalti kelebihan kVArh PLN.",
      "potensi_hemat_kwh": 0,
      "potensi_hemat_rp_per_bulan": 845000,
      "potensi_reduksi_co2_ton": 0.0,
      "estimasi_biaya_implementasi_rp": 25000000,
      "payback_period_tahun": 2.47
    },
    {
      "kategori_investasi": "High-Cost",
      "kriteria_payback": "> 4 Tahun",
      "judul_tindakan": "Retrofit Motor Induksi Crusher ke Kelas Efisiensi Premium IE3 / IE4 & Integrasi Inverter VSD",
      "lokasi_panel": "SDP-01_LINE_A",
      "deskripsi_teknis": "Mengganti 2 unit motor induksi standar IE1 (total daya 75 kW) dengan motor efisiensi tinggi kelas IE3 serta melengkapinya dengan Variable Speed Drive untuk menyesuaikan putaran rotor crusher terhadap kepadatan umpan biomassa.",
      "potensi_hemat_kwh": 1800,
      "potensi_hemat_rp_per_bulan": 1864404,
      "potensi_reduksi_co2_ton": 1.57,
      "estimasi_biaya_implementasi_rp": 110000000,
      "payback_period_tahun": 4.92
    }
  ]
}
```

### 7.2 Template System Prompt AI Copilot (Rigid Context-Grounding)

```text
Anda adalah "Industrial Energy Efficiency Copilot (IEEC)", asisten kecerdasan buatan senior spesialis manajemen energi industri dan regulasi ketenagalistrikan Indonesia untuk fasilitas pabrik PT Indmira Global Energi.

DASAR HUKUM & STANDAR WAJIB ANDA:
1. PP No. 33 Tahun 2023 tentang Konservasi Energi.
2. Permen ESDM No. 8 Tahun 2025 tentang Manajemen Energi (Mencabut Permen ESDM 14/2012).
3. Permen ESDM No. 3 Tahun 2025 (Standar IKE Bangunan Gedung).
4. Permen ESDM No. 7 Tahun 2024 tentang Tarif Tenaga Listrik PLN (Struktur Tarif I-3/TM > 200 kVA, WBP faktor K, biaya penalti kVArh jika cos phi < 0,85).
5. SNI ISO 50001:2018 (Metodologi Energy Baseline OLS, EnPI, CUSUM).
6. Faktor emisi GRK resmi Ditjen Ketenagalistrikan Kementerian ESDM.

ATURAN KEPATUHAN KETAT (STRICT COMPLIANCE RULES):
1. ZERO-HALLUCINATION MATEMATIKA: Gunakan HANYA data dan angka yang secara eksplisit tercantum dalam payload JSON terlampir. DILARANG menghitung ulang, membulatkan secara liar, atau mengarang nominal Rupiah, kWh, maupun emisi CO2e baru di luar konteks JSON.
2. LARANGAN RUJUKAN KEDALUWARSA: Dilarang keras mengutip Permen ESDM No. 14 Tahun 2012 atau Permen ESDM No. 13 Tahun 2012 karena telah resmi dicabut pemerintah.
3. LABEL REGULASI PRESET: Ambang batas deviasi EnPI (NORMAL/WASPADA/BOROS) adalah ambang batas konfigurasi internal aplikasi. Ambang IKE gedung merujuk pada standar SNI 6196 dan Permen ESDM No. 3 Tahun 2025.
4. INTEGRITAS KONSERVASI FISIK: Pahami dan tegaskan bahwa penjadwalan ulang beban dari jam WBP ke LWBP (load shifting) HANYA menghemat biaya finansial akibat selisih tarif WBP vs LWBP, dan TIDAK mengurangi jumlah kWh fisik maupun emisi CO2e.
5. DISCLAIMER PROFESIONAL: Nyatakan dengan jelas bahwa IEEC adalah instrumen pendukung keputusan Manajer Energi dan tidak menggantikan kedudukan laporan audit resmi yang ditandatangani oleh Auditor Energi Bersertifikat BNSP.

STRUKTUR FORMAT JAWABAN ANDA:
Setiap ringkasan atau rekomendasi audit wajib disajikan dalam struktur berikut:

1. EVALUASI KINERJA IKE & KEPATUHAN REGULASI:
   - Status Kepatuhan PP 33/2023 (jelaskan status wajib atau di bawah ambang sukarela beserta dasar angka TOE tahunan).
   - Analisis Panel: Identifikasi panel berstatus BOROS atau WASPADA. Paparkan IKE aktual vs baseline EnB, nilai Base Load (E0), dan reliabilitas model R2.

2. REKOMENDASI TINDAKAN PRESKRIPTIF TERKLASIFIKASI (Permen ESDM 8/2025):
   Sajikan rekomendasi efisiensi secara runut berdasarkan matriks payback period Kementerian ESDM:
   - Kategori 1: Tanpa Biaya (No-Cost, Payback = 0 Tahun) -> Penjadwalan beban WBP ke LWBP & penegakan SOP.
   - Kategori 2: Biaya Rendah (Low-Cost, Payback < 2 Tahun) -> Otomasi timer auto-standby idle load & perbaikan kebocoran nozzle kompresor.
   - Kategori 3: Biaya Menengah (Medium-Cost, Payback 2-4 Tahun) -> Penambahan step APFC Capacitor Bank untuk menaikkan cos phi bulanan >= 0,85.
   - Kategori 4: Biaya Tinggi (High-Cost, Payback > 4 Tahun) -> Retrofit motor induksi standar ke kelas efisiensi tinggi IE3/IE4 atau VSD.

3. RINGKASAN DAMPAK FINANSIAL & DEKARBONISASI:
   Sebutkan secara presisi:
   - Total Penghematan Energi Aktif (kWh/bulan).
   - Total Penghematan Biaya Finansial (Rp/bulan) beserta rinciannya (eliminasi beban aktif, geser WBP, dan penalti kVArh).
   - Total Reduksi Emisi GRK (Ton CO2e/bulan) dengan mencantumkan nama grid dan faktor emisi resmi yang digunakan.
```

---

## 8. Peta Jalan Validasi Industri & Riwayat Revisi

### 8.1 Peta Jalan Kalibrasi & Validasi Bersama Industry Problem Owner (PT Indmira Global Energi)
Peta jalan berikut disusun selaras dengan linimasa rangkaian program **ANGEL Innovation Hackathon 2026** (Oktober s.d. Desember 2026) untuk memastikan transisi mulus dari Prototype Alpha menuju implementasi lapangan skala penuh:

| Fase Hackathon | Periode Pelaksanaan | Target Kolaborasi & Milestone Bersama PT Indmira Global Energi | Kriteria Kesiapan & *Deliverables* Utama |
| :--- | :---: | :--- | :--- |
| **Fase 1: Problem Validation Bootcamp** | Oktober 2026 | • Wawancara mendalam dengan Tim Manajer Energi & Utility Engineer PT Indmira Global Energi.<br>• Pemetaan Single-Line Diagram (SLD) kelistrikan pabrik dan identifikasi beban motor induksi berdaya besar.<br>• Verifikasi rekening listrik PLN eksisting (Golongan I-3/TM) untuk mencocokkan faktor $K$ dan riwayat penalti kVArh. | Dokumen Spesifikasi Kebutuhan Lapangan Terkalibrasi & Penyelarasan Ambang Alarm Anomali. |
| **Fase 2: Problem Owner Clinic & Data Exchange** | Awal November 2026 | • Pertukaran data historis telemetri 3 bulan terakhir dari Digital Power Meter pabrik Indmira.<br>• Pemodelan awal regresi linier OLS EnB lini pirolisis biochar menggunakan data log produksi nyata.<br>• Kalibrasi ambang batas kebocoran *idle load* saat pergantian shift operasional pabrik biomassa. | Dataset Uji Riil Tervalidasi (*Indmira Anonymized Benchmark Dataset*) & Nilai $R^2$ Baseline > 0,85. |
| **Fase 3: Prototype Refinement & Sandbox Testing** | Pertengahan November 2026 | • *Deploy* Prototype Alpha pada lingkungan *staging/sandbox* terisolasi milik PT Indmira Global Energi.<br>• Simulasi validasi perhitungan biaya deterministik terhadap lembar tagihan resmi PLN.<br>• Uji coba interaktif AI Copilot bersama teknisi lapangan untuk mengevaluasi ketepatan rekomendasi *payback period*. | Laporan *User Acceptance Testing* (UAT) dengan tingkat akurasi komputasi finansial 100% dan *Zero-Hallucination*. |
| **Fase 4: Readiness Review & Final Pitching** | Awal Desember 2026 | • Uji coba *live streaming* telemetri IoT melalui protokol industri Modbus-RTU / MQTT ke gateway IEEC.<br>• Penyusunan studi kelayakan hilirisasi (*downstreaming business case*) penghematan energi tahunan pabrik.<br>• Presentasi demonstrasi langsung (*Live Demo*) di hadapan Dewan Juri ANGEL Hackathon dan Direksi PT Indmira Global Energi. | Prototype Alpha Berstatus *Production-Ready*, Lembar Rekomendasi Audit Energi Standar ESDM, dan *Letters of Intent* (LoI) Hilirisasi. |

### 8.2 Riwayat Revisi Dokumen

| Versi PRD | Tanggal Revisi | Ringkasan Pembaruan Spesifikasi Utama | Penanggung Jawab |
| :---: | :---: | :--- | :--- |
| **v1.0** | 10 Okt 2026 | Rilis inisial spesifikasi Prototype Alpha IEEC untuk seleksi tahap awal hackathon. | Antigravity AI Team |
| **v1.1** | 12 Okt 2026 | • Pembaruan dasar hukum: mencabut Permen ESDM 14/2012 dan 13/2012; mengadopsi PP 33/2023 dan Permen ESDM 8/2025.<br>• Penyesuaian jam WBP menjadi 18:00–22:00 waktu setempat dan tarif WBP berbasis faktor pengali $K$.<br>• Perubahan mekanisme biaya kVArh menjadi agregat bulanan pada $\cos \varphi < 0,85$.<br>• Penambahan modul FR-07 (Konfigurasi Regulasi) dan FR-08 (Ringkasan Audit ESDM). | Regulatory Specialist |
| **v1.2** | 15 Okt 2026 | • **Konsistensi Matematis Mutlak:** Memisahkan *idle waste* (2.450 kWh), *spike waste* (1.670 kWh), beban geser WBP (1.200 kWh), dan penalti kVArh (Rp 845.000) sehingga total hemat konsisten Rp 5.609.586/bln dan reduksi 3,58 Ton CO2e di seluruh bagian dokumen.<br>• **Klasifikasi Payback Period ESDM:** Mewajibkan kategorisasi rekomendasi *No-Cost*, *Low-Cost*, *Medium-Cost*, dan *High-Cost* sesuai kaidah Permen ESDM 8/2025.<br>• **Pembersihan Catatan Draft:** Menghapus seluruh kalimat bernada ragu menjadi spesifikasi fitur terkalibrasi; mengintegrasikan standar IKE Permen ESDM 3/2025.<br>• **Integrasi Problem Owner:** Menyelaraskan seluruh profil panel SDP, karakteristik beban, dan narasi kasus uji dengan fasilitas bioenergi PT Indmira Global Energi.<br>• **Arsitektur Data Blending Multi-Sumber:** Merumuskan integrasi 3 dataset publik (Steel Industry Energy Consumption, Smart Manufacturing Resource Efficiency, dan Building Data Genome Project 2 berlisensi terbuka MIT) serta 2 preset demo (Sukarela vs Wajib).<br>• **Peta Jalan Validasi Industri:** Mengubah Bagian 8.1 menjadi roadmap terstruktur tahapan ANGEL Hackathon 2026 bersama PT Indmira Global Energi.<br>• **Matriks Penilaian Hackathon:** Menambahkan tabel pemetaan 5 kriteria seleksi ANGEL 2026 pada Bagian 1.3. | Senior PM & Data Scientist |