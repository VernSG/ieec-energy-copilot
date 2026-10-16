<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Lembar Kerja Audit Energi ESDM - PT Indmira Global Energi</title>
    <style>
        @page {
            margin: 15mm 12mm 15mm 12mm;
            size: A4 portrait;
        }
        body {
            font-family: DejaVu Sans, Helvetica, Arial, sans-serif;
            color: #1e293b;
            font-size: 9.5px;
            line-height: 1.35;
            margin: 0;
            padding: 0;
        }
        .text-center { text-align: center; }
        .text-right { text-align: right; }
        .font-bold { font-weight: bold; }
        .uppercase { text-transform: uppercase; }
        .font-mono { font-family: DejaVu Sans Mono, monospace; font-size: 8.5px; }

        /* Disclaimer Banner */
        .disclaimer-banner {
            background-color: #fef3c7;
            border: 1.5px solid #d97706;
            color: #92400e;
            padding: 7px 10px;
            text-align: center;
            font-weight: bold;
            font-size: 9px;
            margin-bottom: 12px;
            border-radius: 4px;
        }

        /* Header Table */
        .header-table {
            width: 100%;
            border-bottom: 2px solid #0f172a;
            padding-bottom: 8px;
            margin-bottom: 12px;
        }
        .header-table td {
            vertical-align: middle;
            border: none;
            padding: 0;
        }
        .logo-img {
            max-height: 48px;
            width: auto;
        }
        .ministry-title {
            color: #15803d;
            font-size: 8px;
            font-weight: bold;
            letter-spacing: 0.5px;
            text-transform: uppercase;
        }
        .doc-title {
            font-size: 14px;
            font-weight: bold;
            color: #0f172a;
            margin: 2px 0;
            text-transform: uppercase;
        }
        .doc-subtitle {
            font-size: 8px;
            color: #64748b;
        }

        /* Section Headings */
        .section-header {
            font-size: 10px;
            font-weight: bold;
            color: #0f172a;
            text-transform: uppercase;
            border-left: 3.5px solid #15803d;
            padding-left: 6px;
            margin-top: 14px;
            margin-bottom: 6px;
        }

        /* Tables */
        table.data-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 10px;
        }
        table.data-table th {
            background-color: #f8fafc;
            color: #0f172a;
            font-weight: bold;
            font-size: 8px;
            border: 1px solid #cbd5e1;
            padding: 5px 6px;
            text-align: left;
        }
        table.data-table td {
            border: 1px solid #cbd5e1;
            padding: 4px 6px;
            font-size: 8.5px;
            vertical-align: top;
        }

        /* Status & Alert Boxes */
        .box-info {
            background-color: #f8fafc;
            border: 1px solid #cbd5e1;
            border-radius: 4px;
            padding: 8px;
            margin-bottom: 8px;
        }
        .box-compliance-wajib {
            background-color: #fffbeb;
            border: 1px solid #fcd34d;
            color: #92400e;
            padding: 6px 8px;
            border-radius: 4px;
            margin-top: 6px;
            font-size: 8.5px;
        }
        .box-compliance-sukarela {
            background-color: #f0fdf4;
            border: 1px solid #86efac;
            color: #166534;
            padding: 6px 8px;
            border-radius: 4px;
            margin-top: 6px;
            font-size: 8.5px;
        }

        /* Recommendation cards */
        .rec-item {
            border: 1px solid #cbd5e1;
            background-color: #ffffff;
            border-radius: 4px;
            padding: 6px 8px;
            margin-bottom: 6px;
        }
        .badge {
            display: inline-block;
            font-size: 7.5px;
            font-weight: bold;
            padding: 1.5px 4px;
            border-radius: 2px;
        }
        .badge-green { background-color: #dcfce7; color: #166534; }
        .badge-amber { background-color: #fef3c7; color: #92400e; }
        .badge-blue { background-color: #dbeafe; color: #1e40af; }
        .badge-red { background-color: #fee2e2; color: #991b1b; }

        /* Sign-off Table */
        .signoff-table {
            width: 100%;
            margin-top: 18px;
            border-collapse: collapse;
            page-break-inside: avoid;
        }
        .signoff-table td {
            width: 33.33%;
            text-align: center;
            vertical-align: top;
            padding: 0 10px;
            font-size: 8.5px;
        }
        .sign-line {
            border-bottom: 1px solid #64748b;
            margin: 40px auto 4px auto;
            width: 85%;
        }
    </style>
</head>
<body>

    <!-- Simulation Disclaimer Banner (F-13) -->
    <div class="disclaimer-banner">
        DOKUMEN SIMULASI PENDUKUNG KEPUTUSAN &mdash; BUKAN LAPORAN AUDIT ENERGI RESMI (PERMEN ESDM NO. 8 TAHUN 2025)
    </div>

    <!-- Official Header -->
    <table class="header-table">
        <tr>
            @if (!empty($logoBase64))
            <td style="width: 60px;">
                <img src="data:image/jpeg;base64,{{ $logoBase64 }}" alt="Logo" class="logo-img">
            </td>
            <td style="width: 10px;"></td>
            @endif
            <td>
                <div class="ministry-title">KEMENTERIAN ENERGI DAN SUMBER DAYA MINERAL REPUBLIK INDONESIA</div>
                <div class="doc-title">LEMBAR KERJA AUDIT ENERGI BERKALA</div>
                <div class="doc-subtitle">Sesuai Ketentuan <strong>Permen ESDM No. 8 Tahun 2025</strong> &amp; <strong>PP No. 33 Tahun 2023</strong> tentang Konservasi Energi</div>
            </td>
            <td style="width: 180px;" class="text-right font-mono">
                <div>Dokumen: <strong>ESDM-IEEC-{{ date('Ymd-His') }}</strong></div>
                <div>Rujukan: <span style="color: #15803d; font-weight: bold;">{{ $config->versi_regulasi ?? 'Permen ESDM 8/2025' }}</span></div>
                <div>Tanggal: {{ $exportTime }}</div>
            </td>
        </tr>
    </table>

    <!-- Section 1: Data Fasilitas & Evaluasi Kepatuhan -->
    <div class="section-header">1. Data Fasilitas &amp; Neraca Evaluasi Kepatuhan Regulasi</div>
    <div class="box-info">
        <table style="width: 100%; border-collapse: collapse;">
            <tr>
                <td style="width: 50%; vertical-align: top; padding-right: 10px;">
                    <div><span style="color: #64748b;">Nama Fasilitas:</span> <strong>{{ $payload['audit_metadata']['facility_name'] }}</strong></div>
                    <div><span style="color: #64748b;">Industry Problem Owner:</span> <strong>PT Indmira Global Energi</strong></div>
                    <div><span style="color: #64748b;">Periode Audit:</span> <span class="font-mono">{{ $payload['audit_metadata']['periode_audit'] }}</span> ({{ $payload['audit_metadata']['jumlah_hari_data'] }} Hari)</div>
                    <div><span style="color: #64748b;">Karakteristik Operasional:</span> Pengolahan Biomassa (Pirolisis Biochar, Rotary Dryer, Utilitas Kompresor)</div>
                </td>
                <td style="width: 50%; vertical-align: top; font-family: DejaVu Sans Mono, monospace; font-size: 8px;">
                    <div>Konsumsi Listrik Bulanan: <strong>{{ number_format($payload['kepatuhan_manajemen_energi']['total_listrik_bulanan_kwh'], 0, ',', '.') }} kWh</strong> ({{ number_format($payload['kepatuhan_manajemen_energi']['total_listrik_bulanan_mwh'], 2, ',', '.') }} MWh)</div>
                    <div>Konsumsi TOE Primer Bulanan: <strong>{{ number_format($payload['kepatuhan_manajemen_energi']['konsumsi_toe_bulanan'], 2, ',', '.') }} TOE / bln</strong></div>
                    <div>Proyeksi Energi Listrik Tahunan: <strong>{{ number_format($payload['kepatuhan_manajemen_energi']['proyeksi_toe_tahunan_listrik'], 2, ',', '.') }} TOE / th</strong></div>
                    <div>Input Energi Termal Non-Listrik: <strong>{{ number_format($payload['kepatuhan_manajemen_energi']['input_energi_non_listrik_toe_tahunan'], 2, ',', '.') }} TOE / th</strong></div>
                    <div style="border-top: 1px solid #cbd5e1; margin-top: 4px; padding-top: 4px; color: #15803d; font-weight: bold; font-size: 9px;">
                        Total Konsumsi Energi Tahunan: {{ number_format($payload['kepatuhan_manajemen_energi']['total_konsumsi_energi_tahunan_toe'], 2, ',', '.') }} TOE / th
                    </div>
                </td>
            </tr>
        </table>

        @php $isWajib = str_contains($payload['kepatuhan_manajemen_energi']['status_kepatuhan'], 'WAJIB'); @endphp
        <div class="{{ $isWajib ? 'box-compliance-wajib' : 'box-compliance-sukarela' }}">
            <div class="font-bold">
                STATUS KEPATUHAN PP 33/2023 &amp; PERMEN ESDM 8/2025: <span class="uppercase underline">{{ $payload['kepatuhan_manajemen_energi']['status_kepatuhan'] }}</span>
            </div>
            <div style="margin-top: 2px; font-size: 8px;">
                {{ $payload['kepatuhan_manajemen_energi']['implikasi_hukum'] }}
            </div>
        </div>
    </div>

    <!-- Section 2: Matriks Pengukuran & Evaluasi Kinerja Energi SDP -->
    <div class="section-header">2. Matriks Pengukuran &amp; Evaluasi Kinerja Energi Sub-Distribution Panel (SDP)</div>
    <table class="data-table">
        <thead>
            <tr>
                <th style="width: 14%;">Panel ID</th>
                <th style="width: 20%;">Deskripsi Beban</th>
                <th style="width: 12%;">Kategori</th>
                <th style="width: 14%;" class="text-right">Konsumsi Listrik</th>
                <th style="width: 12%;" class="text-right">Beban Dasar E0</th>
                <th style="width: 8%;" class="text-center">R² EnB</th>
                <th style="width: 8%;" class="text-center">Cos φ</th>
                <th style="width: 12%;" class="text-center">Status IKE</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($payload['analisis_ike_per_panel'] as $panel)
            <tr>
                <td class="font-mono font-bold">{{ $panel['panel_id'] }}</td>
                <td>{{ $panel['deskripsi'] }}</td>
                <td><span class="badge {{ $panel['kategori'] === 'PRODUCTION' ? 'badge-blue' : 'badge-green' }}">{{ $panel['kategori'] }}</span></td>
                <td class="text-right font-mono">{{ number_format($panel['konsumsi_kwh'], 1, ',', '.') }} kWh</td>
                <td class="text-right font-mono">{{ isset($panel['base_load_e0_kwh']) && $panel['base_load_e0_kwh'] > 0 ? number_format($panel['base_load_e0_kwh'], 1, ',', '.').' kW' : '-' }}</td>
                <td class="text-center font-mono">{{ $panel['r2_baseline'] ?? '-' }}</td>
                <td class="text-center font-mono" style="color: {{ ($panel['cos_phi_rata_rata'] ?? 1.0) < 0.85 ? '#b91c1c' : '#15803d' }}; font-weight: bold;">
                    {{ $panel['cos_phi_rata_rata'] ?? '-' }}
                </td>
                <td class="text-center font-bold" style="font-size: 7.5px; color: {{ in_array($panel['status_evaluasi'], ['EFISIEN', 'SANGAT EFISIEN']) ? '#15803d' : '#b91c1c' }};">
                    {{ $panel['status_evaluasi'] }}
                </td>
            </tr>
            @endforeach
        </tbody>
    </table>

    <!-- Section 3: Ringkasan Pemborosan Multi-Vektor & Finansial -->
    <div class="section-header">3. Ringkasan Pemborosan Multi-Vektor &amp; Beban Finansial Bulanan</div>
    <table class="data-table">
        <thead>
            <tr>
                <th style="width: 32%;">Vektor Pemborosan Energi</th>
                <th style="width: 28%;">Parameter Fisik Terukur</th>
                <th style="width: 20%;">Rujukan Regulasi / Standar</th>
                <th style="width: 20%;" class="text-right">Beban Finansial / Bulan</th>
            </tr>
        </thead>
        <tbody>
            <tr>
                <td><strong>Denda Penalti Daya Reaktif (Cos φ Rendah)</strong><br><span style="font-size: 7.5px; color: #64748b;">Denda kVARh akibat Cos φ di bawah standar 0,85</span></td>
                <td class="font-mono">{{ number_format($payload['temuan_pemborosan_dan_finansial']['kvarh_kena_biaya'], 1, ',', '.') }} kVARh</td>
                <td>Permen ESDM No. 8/2025 &amp; SPLN</td>
                <td class="text-right font-mono font-bold" style="color: #b91c1c;">Rp {{ number_format($payload['temuan_pemborosan_dan_finansial']['biaya_kvarh_rp'], 0, ',', '.') }}</td>
            </tr>
            <tr>
                <td><strong>Beban Puncak WBP (18:00 - 22:00 WIB)</strong><br><span style="font-size: 7.5px; color: #64748b;">Potensi penghematan pemindahan beban ke LWBP</span></td>
                <td class="font-mono">{{ number_format($payload['temuan_pemborosan_dan_finansial']['wbp_shiftable_kwh'], 1, ',', '.') }} kWh</td>
                <td>Tarif Tenaga Listrik PLN Gol. {{ $config->golongan_tarif ?? 'I-3/TM' }}</td>
                <td class="text-right font-mono font-bold" style="color: #b91c1c;">Rp {{ number_format($payload['temuan_pemborosan_dan_finansial']['hemat_geser_wbp_rp'], 0, ',', '.') }}</td>
            </tr>
            <tr>
                <td><strong>Idle Load Waste (Break &amp; Jam Kosong)</strong><br><span style="font-size: 7.5px; color: #64748b;">Konsumsi tanpa aktivitas produksi aktif</span></td>
                <td class="font-mono">{{ number_format($payload['temuan_pemborosan_dan_finansial']['idle_load_waste_kwh'], 1, ',', '.') }} kWh</td>
                <td>Metode Baseline ISO 50006:2014</td>
                <td class="text-right font-mono font-bold" style="color: #b91c1c;">Rp {{ number_format($payload['temuan_pemborosan_dan_finansial']['idle_load_waste_kwh'] * ($config->tarif_lwbp_rp_kwh ?? 1115), 0, ',', '.') }}</td>
            </tr>
            <tr>
                <td><strong>Spike Anomaly Waste (Z-Score &gt; 2.0)</strong><br><span style="font-size: 7.5px; color: #64748b;">Lonjakan beban transient di luar profil operasi normal</span></td>
                <td class="font-mono">{{ number_format($payload['temuan_pemborosan_dan_finansial']['spike_anomaly_waste_kwh'], 1, ',', '.') }} kWh</td>
                <td>Analisis Z-Score ISO 50001</td>
                <td class="text-right font-mono font-bold" style="color: #b91c1c;">Rp {{ number_format($payload['temuan_pemborosan_dan_finansial']['spike_anomaly_waste_kwh'] * ($config->tarif_lwbp_rp_kwh ?? 1115), 0, ',', '.') }}</td>
            </tr>
            <tr style="background-color: #f0fdf4;">
                <td colspan="3" style="text-align: right; font-weight: bold; color: #15803d; font-size: 9px;">
                    TOTAL POTENSI PENGHEMATAN FINANSIAL OPERASIONAL:
                </td>
                <td class="text-right font-mono font-bold" style="color: #15803d; font-size: 10px;">
                    Rp {{ number_format($payload['temuan_pemborosan_dan_finansial']['total_potensi_hemat_rp'], 0, ',', '.') }} / bln
                </td>
            </tr>
        </tbody>
    </table>

    <!-- Section 4: Rekomendasi Audit Terklasifikasi Payback Period (Permen ESDM 8/2025) -->
    <div class="section-header">4. Rencana Tindakan Efisiensi Energi Terklasifikasi Payback Period (Permen ESDM 8/2025)</div>
    @foreach ($payload['klasifikasi_rekomendasi_audit_esdm'] as $rek)
    <div class="rec-item">
        <table style="width: 100%; border-collapse: collapse;">
            <tr>
                <td style="border: none; padding: 0;">
                    <span class="badge badge-green uppercase">[{{ $rek['kategori_investasi'] }} &bull; Payback: {{ $rek['kriteria_payback'] }}]</span>
                    <strong style="margin-left: 5px; font-size: 9px; color: #0f172a;">{{ $rek['judul_tindakan'] }}</strong>
                    <span style="color: #64748b; font-size: 8px;">(Lokasi: {{ $rek['lokasi_panel'] }})</span>
                </td>
                <td style="border: none; padding: 0; text-align: right; font-family: DejaVu Sans Mono, monospace; font-size: 8.5px; color: #15803d; font-weight: bold;">
                    Potensi Hemat: Rp {{ number_format($rek['potensi_hemat_rp_per_bulan'], 0, ',', '.') }}/bln
                </td>
            </tr>
            <tr>
                <td colspan="2" style="border: none; padding: 3px 0 2px 0; color: #475569; font-size: 8px;">
                    {{ $rek['deskripsi_teknis'] }}
                </td>
            </tr>
            <tr>
                <td colspan="2" style="border-top: 1px dashed #e2e8f0; padding-top: 3px; font-family: DejaVu Sans Mono, monospace; font-size: 7.5px; color: #64748b;">
                    Investasi Capex: <strong>Rp {{ number_format($rek['estimasi_biaya_implementasi_rp'], 0, ',', '.') }}</strong> &bull;
                    Payback Period: <strong>{{ $rek['payback_period_tahun'] }} Tahun</strong> &bull;
                    Reduksi CO2e: <strong>{{ $rek['potensi_reduksi_co2_ton'] }} Ton/bln</strong>
                </td>
            </tr>
        </table>
    </div>
    @endforeach

    <!-- Sign-off Section with Formal Configurable Placeholders (F-13) -->
    <div style="margin-top: 20px; border-top: 1.5px solid #0f172a; padding-top: 8px; page-break-inside: avoid;">
        <div class="text-center font-bold uppercase" style="font-size: 9px; margin-bottom: 15px;">
            LEMBAR PENGESAHAN AUDIT ENERGI BERKALA
        </div>
        <table class="signoff-table">
            <tr>
                <td>
                    <div style="font-weight: bold;">Disusun &amp; Diverifikasi Oleh:</div>
                    <div style="color: #64748b; font-size: 7.5px;">Manajer Energi Bersertifikat</div>
                    <div class="sign-line"></div>
                    <div style="font-weight: bold;">( [Nama Manajer Energi Bersertifikat] )</div>
                    <div style="color: #64748b; font-size: 7.5px;">Reg. BNSP: [Nomor Registrasi]</div>
                </td>
                <td>
                    <div style="font-weight: bold;">Ditinjau &amp; Diaudit Oleh:</div>
                    <div style="color: #64748b; font-size: 7.5px;">Auditor Energi Terakreditasi ESDM</div>
                    <div class="sign-line"></div>
                    <div style="font-weight: bold;">( [Nama Auditor Energi Bersertifikat] )</div>
                    <div style="color: #64748b; font-size: 7.5px;">Reg. EBTKE: [Nomor Registrasi]</div>
                </td>
                <td>
                    <div style="font-weight: bold;">Disetujui Oleh:</div>
                    <div style="color: #64748b; font-size: 7.5px;">Direktur Operasional Fasilitas</div>
                    <div class="sign-line"></div>
                    <div style="font-weight: bold;">( [Direktur Operasional / Plant Manager] )</div>
                    <div style="color: #64748b; font-size: 7.5px;">Plant Operation &amp; ESG Division</div>
                </td>
            </tr>
        </table>
    </div>

</body>
</html>
