<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Lembar Kerja Audit Energi ESDM - PT Indmira Global Energi</title>
    <link rel="icon" type="image/jpeg" href="{{ asset('images/indmira-logo.jpeg') }}">
    @vite(['resources/css/app.css'])
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=JetBrains+Mono:wght@400;600&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Inter', sans-serif; }
        .font-mono { font-family: 'JetBrains Mono', monospace; }
        @media print {
            .no-print { display: none !important; }
            body { background: white !important; color: black !important; padding: 0 !important; }
            .page-break { page-break-after: always; }
        }
    </style>
</head>
<body class="bg-slate-100 text-slate-800 p-6 md:p-10">

    <!-- Print / Back / Export Navigation -->
    <div class="max-w-5xl mx-auto mb-6 flex flex-wrap items-center justify-between gap-3 no-print">
        <a href="{{ route('dashboard', ['preset' => $payload['audit_metadata']['preset_source'] ?? 'sukarela']) }}" 
           class="inline-flex items-center gap-2 px-4 py-2 bg-slate-800 hover:bg-slate-700 text-white rounded-lg text-sm font-medium transition shadow-xs">
            &larr; Kembali ke Dashboard Telemetri
        </a>
        <div class="flex items-center gap-2">
            <a href="{{ route('audit.export', array_merge(request()->query(), ['format' => 'pdf'])) }}" 
               class="inline-flex items-center gap-1.5 px-3.5 py-2 bg-rose-700 hover:bg-rose-800 text-white rounded-lg text-xs font-semibold transition shadow-xs"
               title="Unduh Lembar Kerja Audit Format PDF">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                <span>Unduh PDF</span>
            </a>
            <a href="{{ route('audit.export', array_merge(request()->query(), ['format' => 'excel'])) }}" 
               class="inline-flex items-center gap-1.5 px-3.5 py-2 bg-emerald-700 hover:bg-emerald-800 text-white rounded-lg text-xs font-semibold transition shadow-xs"
               title="Unduh Data Matriks Audit Format Excel Spreadsheet / CSV">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                <span>Unduh Excel / CSV</span>
            </a>
            <button onclick="window.print()" class="inline-flex items-center gap-1.5 px-3.5 py-2 bg-[#176B52] hover:bg-[#125541] text-white rounded-lg text-xs font-semibold transition shadow-xs">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"/></svg>
                <span>Cetak / Cetak PDF</span>
            </button>
        </div>
    </div>

    <!-- Official Document Sheet -->
    <div class="max-w-5xl mx-auto bg-white p-8 md:p-12 shadow-xl rounded-xl border border-slate-200">
        
        <!-- Simulation Disclaimer Banner (F-13) -->
        <div class="mb-6 p-3.5 bg-amber-50 border-2 border-amber-400 rounded-lg text-amber-900 text-xs text-center font-bold tracking-wide uppercase shadow-xs">
            DOKUMEN SIMULASI PENDUKUNG KEPUTUSAN &mdash; BUKAN LAPORAN AUDIT ENERGI RESMI (PERMEN ESDM NO. 8 TAHUN 2025)
        </div>
        
        <!-- Header -->
        <div class="border-b-2 border-slate-900 pb-5 mb-6 flex flex-col md:flex-row justify-between items-start md:items-center gap-4">
            <div class="flex items-start gap-4">
                <img src="{{ asset('images/indmira-logo.jpeg') }}" alt="PT Indmira Logo" class="h-14 w-auto object-contain shrink-0 border border-slate-200 rounded p-1 bg-white shadow-xs">
                <div>
                    <div class="flex items-center gap-2 text-xs font-semibold tracking-wider text-[#176B52] uppercase mb-1">
                        <span>KEMENTERIAN ENERGI DAN SUMBER DAYA MINERAL REPUBLIK INDONESIA</span>
                    </div>
                    <h1 class="text-xl md:text-2xl font-bold text-slate-900 uppercase tracking-tight">
                        LEMBAR KERJA AUDIT ENERGI BERKALA
                    </h1>
                    <p class="text-xs text-slate-500 mt-1">
                        Sesuai Ketentuan <strong>Permen ESDM No. 8 Tahun 2025</strong> &amp; <strong>PP No. 33 Tahun 2023</strong> tentang Konservasi Energi
                    </p>
                </div>
            </div>
            <div class="text-right text-xs text-slate-600 font-mono">
                <div>Dokumen: <strong class="text-slate-900">ESDM-IEEC-{{ date('Ymd-His') }}</strong></div>
                <div>Status Rujukan: <span class="px-2 py-0.5 bg-emerald-100 text-[#176B52] rounded font-semibold">{{ $config->versi_regulasi }}</span></div>
                <div>Tanggal: {{ $exportTime }}</div>
            </div>
        </div>

        <!-- Section 1: Data Fasilitas & Evaluasi Kepatuhan -->
        <div class="mb-6">
            <h2 class="text-sm font-bold text-slate-900 uppercase tracking-wide border-l-4 border-[#176B52] pl-2 mb-3">
                1. Data Fasilitas &amp; Neraca Evaluasi Kepatuhan Regulasi
            </h2>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4 text-xs bg-slate-50 p-4 rounded-lg border border-slate-200">
                <div class="space-y-1.5">
                    <div><span class="text-slate-500">Nama Fasilitas:</span> <strong class="text-slate-900">{{ $payload['audit_metadata']['facility_name'] }}</strong></div>
                    <div><span class="text-slate-500">Industry Problem Owner:</span> <strong class="text-slate-900">PT Indmira Global Energi</strong></div>
                    <div><span class="text-slate-500">Periode Audit:</span> <span class="font-mono">{{ $payload['audit_metadata']['periode_audit'] }}</span> ({{ $payload['audit_metadata']['jumlah_hari_data'] }} Hari)</div>
                    <div><span class="text-slate-500">Karakteristik Operasional:</span> Pengolahan Biomassa (Pirolisis Biochar, Rotary Dryer, Utilitas Kompresor)</div>
                </div>
                <div class="space-y-1.5 font-mono">
                    <div><span class="text-slate-500 font-sans">Konsumsi Listrik Bulanan:</span> <strong>{{ number_format($payload['kepatuhan_manajemen_energi']['total_listrik_bulanan_kwh'], 0, ',', '.') }} kWh</strong> ({{ number_format($payload['kepatuhan_manajemen_energi']['total_listrik_bulanan_mwh'], 2, ',', '.') }} MWh)</div>
                    <div><span class="text-slate-500 font-sans">Konsumsi TOE Primer Bulanan:</span> <strong>{{ number_format($payload['kepatuhan_manajemen_energi']['konsumsi_toe_bulanan'], 2, ',', '.') }} TOE / bln</strong></div>
                    <div><span class="text-slate-500 font-sans">Proyeksi Energi Listrik Tahunan:</span> <strong>{{ number_format($payload['kepatuhan_manajemen_energi']['proyeksi_toe_tahunan_listrik'], 2, ',', '.') }} TOE / th</strong></div>
                    <div><span class="text-slate-500 font-sans">Input Energi Termal Non-Listrik:</span> <strong>{{ number_format($payload['kepatuhan_manajemen_energi']['input_energi_non_listrik_toe_tahunan'], 2, ',', '.') }} TOE / th</strong></div>
                    <div class="pt-1 border-t border-slate-300">
                        <span class="text-slate-700 font-sans font-semibold">Total Konsumsi Energi Tahunan:</span> 
                        <strong class="text-emerald-700 text-sm">{{ number_format($payload['kepatuhan_manajemen_energi']['total_konsumsi_energi_tahunan_toe'], 2, ',', '.') }} TOE / th</strong>
                    </div>
                </div>
            </div>

            <!-- Status Box -->
            <div class="mt-3 p-3 rounded-lg border {{ str_contains($payload['kepatuhan_manajemen_energi']['status_kepatuhan'], 'WAJIB') ? 'bg-amber-50 border-amber-300 text-amber-900' : 'bg-emerald-50 border-emerald-300 text-emerald-900' }} text-xs">
                <div class="font-bold flex items-center justify-between">
                    <span>STATUS KEPATUHAN PP 33/2023 &amp; PERMEN ESDM 8/2025:</span>
                    <span class="uppercase underline">{{ $payload['kepatuhan_manajemen_energi']['status_kepatuhan'] }}</span>
                </div>
                <div class="mt-1 text-[11px] leading-relaxed">
                    {{ $payload['kepatuhan_manajemen_energi']['implikasi_hukum'] }}
                </div>
            </div>
        </div>

        <!-- Section 2: Tabel Kinerja Panel SDP -->
        <div class="mb-6">
            <h2 class="text-sm font-bold text-slate-900 uppercase tracking-wide border-l-4 border-[#176B52] pl-2 mb-3">
                2. Matriks Pengukuran &amp; Evaluasi Kinerja Energi Sub-Distribution Panel (SDP)
            </h2>
            <div class="overflow-x-auto border border-slate-200 rounded-lg">
                <table class="w-full text-xs text-left">
                    <thead class="bg-slate-100 text-slate-700 font-semibold uppercase text-[10px] border-b border-slate-200">
                        <tr>
                            <th class="p-2.5">Panel ID &amp; Zona</th>
                            <th class="p-2.5">Kategori</th>
                            <th class="p-2.5 text-right">Konsumsi</th>
                            <th class="p-2.5 text-right">Porsi</th>
                            <th class="p-2.5 text-right">IKE Aktual</th>
                            <th class="p-2.5 text-right">Standar/EnB</th>
                            <th class="p-2.5 text-right">Base Load E0</th>
                            <th class="p-2.5 text-center">R² OLS</th>
                            <th class="p-2.5 text-center">cos &phi;</th>
                            <th class="p-2.5 text-center">Status</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-200">
                        @foreach ($payload['analisis_ike_per_panel'] as $panel)
                        <tr class="hover:bg-slate-50 font-mono text-[11px]">
                            <td class="p-2.5 font-sans font-medium text-slate-900">{{ $panel['panel_id'] }}<br><span class="text-[10px] text-slate-500 font-sans">{{ $panel['deskripsi'] }}</span></td>
                            <td class="p-2.5 text-slate-600 font-sans">{{ $panel['kategori'] }}</td>
                            <td class="p-2.5 text-right">{{ number_format($panel['konsumsi_kwh'], 0, ',', '.') }} kWh</td>
                            <td class="p-2.5 text-right">{{ $panel['porsi_konsumsi_pct'] }}%</td>
                            <td class="p-2.5 text-right font-semibold">{{ $panel['ike_aktual'] }} {{ $panel['kategori'] === 'PRODUCTION' ? 'kWh/b' : 'kWh/m²' }}</td>
                            <td class="p-2.5 text-right">{{ $panel['baseline_enb'] ?? '-' }}</td>
                            <td class="p-2.5 text-right">{{ isset($panel['base_load_e0_kwh']) ? $panel['base_load_e0_kwh'] . ' kW' : '-' }}</td>
                            <td class="p-2.5 text-center">{{ $panel['r2_baseline'] ?? '-' }}</td>
                            <td class="p-2.5 text-center {{ ($panel['cos_phi_rata_rata'] ?? 1.0) < 0.85 ? 'text-rose-600 font-bold' : '' }}">{{ $panel['cos_phi_rata_rata'] ?? '-' }}</td>
                            <td class="p-2.5 text-center font-sans font-semibold">
                                @if (str_contains($panel['status_evaluasi'], 'BOROS'))
                                    <span class="text-rose-700 bg-rose-50 px-2 py-0.5 rounded text-[10px]">BOROS</span>
                                @elseif (str_contains($panel['status_evaluasi'], 'WASPADA'))
                                    <span class="text-amber-700 bg-amber-50 px-2 py-0.5 rounded text-[10px]">WASPADA</span>
                                @else
                                    <span class="text-emerald-700 bg-emerald-50 px-2 py-0.5 rounded text-[10px]">EFISIEN</span>
                                @endif
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Section 3: Neraca Pemborosan & Finansial -->
        <div class="mb-6">
            <h2 class="text-sm font-bold text-slate-900 uppercase tracking-wide border-l-4 border-[#176B52] pl-2 mb-3">
                3. Identifikasi Pemborosan Spesifik &amp; Neraca Rekonsiliasi Tarif PLN (I-3/TM)
            </h2>
            <div class="grid grid-cols-1 md:grid-cols-4 gap-3 text-xs">
                <div class="p-3 bg-slate-50 border border-slate-200 rounded-lg">
                    <span class="text-slate-500 block text-[11px]">Idle Load Waste (Break)</span>
                    <strong class="font-mono text-sm text-slate-900">{{ number_format($payload['temuan_pemborosan_dan_finansial']['idle_load_waste_kwh'], 0, ',', '.') }} kWh</strong>
                    <span class="text-[10px] text-slate-500 block mt-1">Rp {{ number_format($payload['temuan_pemborosan_dan_finansial']['idle_load_waste_kwh'] * $config->tarif_lwbp_rp_kwh, 0, ',', '.') }}</span>
                </div>
                <div class="p-3 bg-slate-50 border border-slate-200 rounded-lg">
                    <span class="text-slate-500 block text-[11px]">Spike Waste (Z > 2.0)</span>
                    <strong class="font-mono text-sm text-slate-900">{{ number_format($payload['temuan_pemborosan_dan_finansial']['spike_anomaly_waste_kwh'], 0, ',', '.') }} kWh</strong>
                    <span class="text-[10px] text-slate-500 block mt-1">Rp {{ number_format($payload['temuan_pemborosan_dan_finansial']['spike_anomaly_waste_kwh'] * $config->tarif_lwbp_rp_kwh, 0, ',', '.') }}</span>
                </div>
                <div class="p-3 bg-slate-50 border border-slate-200 rounded-lg">
                    <span class="text-slate-500 block text-[11px]">Beban Geser WBP (18-22)</span>
                    <strong class="font-mono text-sm text-slate-900">{{ number_format($payload['temuan_pemborosan_dan_finansial']['wbp_shiftable_kwh'], 0, ',', '.') }} kWh</strong>
                    <span class="text-[10px] text-slate-500 block mt-1">Hemat Rp {{ number_format($payload['temuan_pemborosan_dan_finansial']['hemat_geser_wbp_rp'], 0, ',', '.') }}</span>
                </div>
                <div class="p-3 bg-slate-50 border border-slate-200 rounded-lg">
                    <span class="text-slate-500 block text-[11px]">Denda Penalti kVArh Bulanan</span>
                    <strong class="font-mono text-sm text-rose-600">{{ number_format($payload['temuan_pemborosan_dan_finansial']['kvarh_kena_biaya'], 0, ',', '.') }} kVArh</strong>
                    <span class="text-[10px] text-slate-500 block mt-1">Rp {{ number_format($payload['temuan_pemborosan_dan_finansial']['biaya_kvarh_rp'], 0, ',', '.') }}</span>
                </div>
            </div>

            <!-- Grand Totals -->
            <div class="mt-3 p-4 bg-emerald-50 border border-emerald-300 rounded-lg flex flex-col md:flex-row justify-between items-center text-xs">
                <div>
                    <span class="font-semibold text-emerald-900">Total Potensi Penghematan Fisik &amp; Dekarbonisasi:</span>
                    <span class="font-mono font-bold text-emerald-800 ml-1">{{ number_format($payload['temuan_pemborosan_dan_finansial']['total_potensi_hemat_kwh'], 0, ',', '.') }} kWh/bulan</span>
                    <span class="text-slate-600 ml-2">({{ number_format($payload['temuan_pemborosan_dan_finansial']['potensi_reduksi_co2_ton'], 2, ',', '.') }} Ton CO2e/bulan)</span>
                </div>
                <div class="mt-2 md:mt-0 font-mono font-bold text-base text-emerald-700">
                    Rp {{ number_format($payload['temuan_pemborosan_dan_finansial']['total_potensi_hemat_rp'], 0, ',', '.') }} / bulan
                </div>
            </div>
        </div>

        <!-- Section 4: Rekomendasi Audit 4 Payback Period -->
        <div class="mb-8">
            <h2 class="text-sm font-bold text-slate-900 uppercase tracking-wide border-l-4 border-[#176B52] pl-2 mb-3">
                4. Rencana Tindakan Efisiensi Energi Terklasifikasi Payback Period (Permen ESDM 8/2025)
            </h2>
            <div class="space-y-3">
                @foreach ($payload['klasifikasi_rekomendasi_audit_esdm'] as $rek)
                <div class="p-3.5 border border-slate-200 rounded-lg text-xs bg-white">
                    <div class="flex items-center justify-between mb-1">
                        <span class="font-bold text-[11px] uppercase tracking-wider text-slate-800">
                            [{{ $rek['kategori_investasi'] }} • Payback: {{ $rek['kriteria_payback'] }}]
                        </span>
                        <span class="font-mono font-bold text-emerald-700">
                            Potensi Hemat: Rp {{ number_format($rek['potensi_hemat_rp_per_bulan'], 0, ',', '.') }} / bulan
                        </span>
                    </div>
                    <h3 class="font-semibold text-slate-900 text-xs mb-1">{{ $rek['judul_tindakan'] }} (Lokasi: {{ $rek['lokasi_panel'] }})</h3>
                    <p class="text-slate-600 text-[11px] leading-relaxed mb-2">{{ $rek['deskripsi_teknis'] }}</p>
                    <div class="flex flex-wrap gap-4 text-[10.5px] font-mono text-slate-500 pt-1.5 border-t border-slate-100">
                        <span>Investasi Capex: <strong>Rp {{ number_format($rek['estimasi_biaya_implementasi_rp'], 0, ',', '.') }}</strong></span>
                        <span>Payback Period: <strong>{{ $rek['payback_period_tahun'] }} Tahun</strong></span>
                        <span>Reduksi CO2e: <strong>{{ $rek['potensi_reduksi_co2_ton'] }} Ton/bln</strong></span>
                    </div>
                </div>
                @endforeach
            </div>
        </div>

        <!-- Sign-off Section with Formal Configurable Placeholders (F-13) -->
        <div class="border-t-2 border-slate-800 pt-6 mt-8">
            <h3 class="text-xs font-bold text-slate-900 uppercase tracking-wide mb-8 text-center">
                LEMBAR PENGESAHAN AUDIT ENERGI BERKALA
            </h3>
            <div class="grid grid-cols-3 gap-6 text-center text-xs">
                <div>
                    <div class="font-semibold text-slate-800 mb-16">Disusun &amp; Diverifikasi Oleh:<br><span class="text-slate-500 font-normal">Manajer Energi Bersertifikat</span></div>
                    <div class="border-b border-slate-400 w-44 mx-auto mb-1"></div>
                    <div class="font-semibold text-slate-900">( [Nama Manajer Energi Bersertifikat] )</div>
                    <div class="text-[10px] text-slate-500">Reg. BNSP: [Nomor Registrasi]</div>
                </div>
                <div>
                    <div class="font-semibold text-slate-800 mb-16">Ditinjau &amp; Diaudit Oleh:<br><span class="text-slate-500 font-normal">Auditor Energi Terakreditasi ESDM</span></div>
                    <div class="border-b border-slate-400 w-44 mx-auto mb-1"></div>
                    <div class="font-semibold text-slate-900">( [Nama Auditor Energi Bersertifikat] )</div>
                    <div class="text-[10px] text-slate-500">Reg. EBTKE: [Nomor Registrasi]</div>
                </div>
                <div>
                    <div class="font-semibold text-slate-800 mb-16">Disetujui Oleh:<br><span class="text-slate-500 font-normal">Direktur Operasional Fasilitas</span></div>
                    <div class="border-b border-slate-400 w-44 mx-auto mb-1"></div>
                    <div class="font-semibold text-slate-900">( [Direktur Operasional / Plant Manager] )</div>
                    <div class="text-[10px] text-slate-500">Plant Operation &amp; ESG Division</div>
                </div>
            </div>
        </div>

    </div>

</body>
</html>
