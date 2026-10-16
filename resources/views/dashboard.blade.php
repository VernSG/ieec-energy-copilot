<!DOCTYPE html>
<html lang="id" class="h-full">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Industrial Energy Efficiency Copilot (IEEC) — PT Indmira Global Energi</title>
    <meta name="description" content="Industrial Energy Efficiency Copilot (IEEC) Prototype Alpha. Kepatuhan PP 33/2023, Permen ESDM 8/2025, ISO 50001 OLS Baseline & Rekomendasi Preskriptif 4 Payback Period.">
    <link rel="icon" type="image/jpeg" href="{{ asset('images/indmira-logo.jpeg') }}">
    <link rel="apple-touch-icon" href="{{ asset('images/indmira-logo.jpeg') }}">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&family=JetBrains+Mono:wght@400;500;600&display=swap" rel="stylesheet">
    <style>
        body {
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;
            background-color: #F6F7F5;
            color: #252A2E;
        }
        .font-mono {
            font-family: 'JetBrains Mono', monospace;
            font-feature-settings: "tnum" 1, "zero" 1;
        }
        /* Enterprise clean scrollbars */
        ::-webkit-scrollbar { width: 6px; height: 6px; }
        ::-webkit-scrollbar-track { background: #F1F3F2; }
        ::-webkit-scrollbar-thumb { background: #CBD2CE; border-radius: 4px; }
        ::-webkit-scrollbar-thumb:hover { background: #9BA39E; }
        .clean-scrollbar {
            scrollbar-width: thin;
            scrollbar-color: #CBD2CE transparent;
        }
    </style>
</head>
<body class="bg-neutral-50 text-neutral-900 h-screen overflow-hidden antialiased flex selection:bg-brand-800 selection:text-white">

    @php
        $isMandatory = str_contains($payload['kepatuhan_manajemen_energi']['status_kepatuhan'], 'WAJIB');
        $lineA = collect($payload['analisis_ike_per_panel'])->firstWhere('panel_id', 'SDP-01_LINE_A');
    @endphp

    <!-- Toast Notifications -->
    @if (session('status'))
    <div id="toastNotification" class="fixed top-4 right-4 z-50 flex items-start gap-3 px-4 py-3 bg-white border border-emerald-300 text-emerald-950 rounded-lg shadow-lg max-w-md animate-fade-in">
        <svg class="w-5 h-5 text-emerald-600 shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
        <div class="flex-1">
            <p class="text-xs font-semibold text-emerald-900">Operasi Berhasil</p>
            <p class="text-xs text-neutral-600 mt-0.5">{{ session('status') }}</p>
        </div>
        <button onclick="this.closest('#toastNotification').remove()" class="text-neutral-400 hover:text-neutral-600 text-sm leading-none">&times;</button>
    </div>
    @endif

    @if (session('error'))
    <div id="toastNotificationError" class="fixed top-4 right-4 z-50 flex items-start gap-3 px-4 py-3 bg-white border border-rose-300 text-rose-950 rounded-lg shadow-lg max-w-md">
        <svg class="w-5 h-5 text-rose-600 shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
        <div class="flex-1">
            <p class="text-xs font-semibold text-rose-900">Perhatian</p>
            <p class="text-xs text-neutral-600 mt-0.5">{{ session('error') }}</p>
        </div>
        <button onclick="this.closest('#toastNotificationError').remove()" class="text-neutral-400 hover:text-neutral-600 text-sm leading-none">&times;</button>
    </div>
    @endif

    <!-- Mobile Sidebar Backdrop Overlay -->
    <div id="sidebarBackdrop" onclick="toggleMobileSidebar()" 
         class="fixed inset-0 bg-neutral-900/40 z-40 hidden md:hidden backdrop-blur-xs transition-opacity duration-200">
    </div>

    <!-- ========================================================================= -->
    <!-- 1. PERSISTENT ENTERPRISE LEFT SIDEBAR -->
    <!-- ========================================================================= -->
    <aside id="sidebar" 
           class="fixed inset-y-0 left-0 z-50 md:static md:z-auto w-64 bg-white border-r border-neutral-200 flex flex-col transition-all duration-200 ease-in-out shrink-0 -translate-x-full md:translate-x-0 select-none">
        
        <!-- Brand Area -->
        <div class="h-14 px-4 flex items-center justify-between border-b border-neutral-200 shrink-0">
            <div class="flex items-center gap-2.5 min-w-0">
                <img src="{{ asset('images/indmira-logo.jpeg') }}" alt="PT Indmira Logo" 
                     class="w-8 h-8 rounded-md object-contain bg-white border border-neutral-200/90 p-0.5 shrink-0 shadow-xs">
                <div class="min-w-0 sidebar-label">
                    <div class="flex items-center gap-1.5">
                        <span class="text-xs font-bold tracking-tight text-neutral-900 leading-tight">IND-ENERGY COPILOT</span>
                    </div>
                    <div class="text-[10px] font-mono text-neutral-500 uppercase tracking-wider">PT Indmira Global Energi</div>
                </div>
            </div>

            <!-- Mobile Close Button -->
            <button onclick="toggleMobileSidebar()" class="md:hidden p-1 rounded text-neutral-400 hover:text-neutral-600 hover:bg-neutral-100" title="Tutup Menu">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
            </button>
        </div>

        <!-- Sidebar Navigation Items (Scrollable) -->
        <div class="flex-1 overflow-y-auto px-3 py-4 space-y-6">

            <!-- Group: WORKSPACE -->
            <div>
                <div class="sidebar-label px-3 mb-1.5 text-[10px] font-semibold text-neutral-400 uppercase tracking-wider">
                    Workspace
                </div>
                <nav class="space-y-1">
                    <!-- Ikhtisar -->
                    <button onclick="switchTab('overview')" id="tab-btn-overview" 
                            class="tab-btn w-full px-3 py-2 rounded-md transition flex items-center justify-between text-xs font-medium text-brand-900 bg-emerald-50 border-l-2 border-brand-800 group"
                            title="Ikhtisar Kinerja Energi">
                        <div class="flex items-center gap-2.5 min-w-0">
                            <svg class="w-4 h-4 text-brand-800 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2V6zM14 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2V6zM4 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2v-2zM14 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2v-2z"/></svg>
                            <span class="sidebar-label truncate">Ikhtisar</span>
                        </div>
                    </button>

                    <!-- Analitik Energi & ISO 50001 -->
                    <button onclick="switchTab('analytics')" id="tab-btn-analytics" 
                            class="tab-btn w-full px-3 py-2 rounded-md transition flex items-center justify-between text-xs font-medium text-neutral-600 hover:text-neutral-900 hover:bg-neutral-50 group"
                            title="Analitik Baseline Energi & ISO 50001">
                        <div class="flex items-center gap-2.5 min-w-0">
                            <svg class="w-4 h-4 text-neutral-400 group-hover:text-neutral-600 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/></svg>
                            <span class="sidebar-label truncate">Analitik Energi &amp; ISO 50001</span>
                        </div>
                    </button>

                    <!-- Anomali & Pemborosan -->
                    <button onclick="switchTab('anomalies')" id="tab-btn-anomalies" 
                            class="tab-btn w-full px-3 py-2 rounded-md transition flex items-center justify-between text-xs font-medium text-neutral-600 hover:text-neutral-900 hover:bg-neutral-50 group"
                            title="Anomali & Pemborosan">
                        <div class="flex items-center gap-2.5 min-w-0">
                            <svg class="w-4 h-4 text-neutral-400 group-hover:text-neutral-600 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>
                            <span class="sidebar-label truncate">Anomali &amp; Pemborosan</span>
                        </div>
                        <span class="sidebar-label px-1.5 py-0.5 rounded text-[10px] bg-amber-100 text-amber-800 font-mono font-semibold shrink-0">14</span>
                    </button>

                    <!-- Matriks Rekomendasi -->
                    <button onclick="switchTab('recommendations')" id="tab-btn-recommendations" 
                            class="tab-btn w-full px-3 py-2 rounded-md transition flex items-center justify-between text-xs font-medium text-neutral-600 hover:text-neutral-900 hover:bg-neutral-50 group"
                            title="Matriks Rekomendasi Permen ESDM 8/2025">
                        <div class="flex items-center gap-2.5 min-w-0">
                            <svg class="w-4 h-4 text-neutral-400 group-hover:text-neutral-600 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4"/></svg>
                            <span class="sidebar-label truncate">Matriks Rekomendasi</span>
                        </div>
                    </button>

                    <!-- Kepatuhan & Audit -->
                    <button onclick="switchTab('compliance')" id="tab-btn-compliance" 
                            class="tab-btn w-full px-3 py-2 rounded-md transition flex items-center justify-between text-xs font-medium text-neutral-600 hover:text-neutral-900 hover:bg-neutral-50 group"
                            title="Kepatuhan PP 33/2023 & Audit">
                        <div class="flex items-center gap-2.5 min-w-0">
                            <svg class="w-4 h-4 text-neutral-400 group-hover:text-neutral-600 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/></svg>
                            <span class="sidebar-label truncate">Kepatuhan &amp; Audit</span>
                        </div>
                    </button>
                </nav>
            </div>

            <!-- Group: AI ADVISORY -->
            <div>
                <div class="sidebar-label px-3 mb-1.5 text-[10px] font-semibold text-neutral-400 uppercase tracking-wider">
                    AI Advisory
                </div>
                <nav class="space-y-1">
                    <button onclick="switchTab('copilot')" id="tab-btn-copilot" 
                            class="tab-btn w-full px-3 py-2 rounded-md transition flex items-center justify-between text-xs font-medium text-neutral-600 hover:text-neutral-900 hover:bg-neutral-50 group"
                            title="Workspace Khusus AI Copilot Energi & Audit">
                        <div class="flex items-center gap-2.5 min-w-0">
                            <svg class="w-4 h-4 text-neutral-400 group-hover:text-neutral-600 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.663 17h4.673M12 3v1m6.364 1.636l-.707.707M21 12h-1M4 12H3m3.343-5.657l-.707-.707m2.828 9.9a5 5 0 117.072 0l-.548.547A3.374 3.374 0 0014 18.469V19a2 2 0 11-4 0v-.531c0-.895-.356-1.754-.988-2.386l-.548-.547z"/></svg>
                            <span class="sidebar-label truncate">AI Copilot</span>
                        </div>
                        <span class="sidebar-label inline-flex items-center gap-1 text-[10px] text-emerald-800 font-medium">
                            <span class="w-1.5 h-1.5 rounded-full bg-emerald-600 animate-pulse"></span>
                            <span>Online</span>
                        </span>
                    </button>
                </nav>
            </div>

            <!-- Group: SYSTEM -->
            <div>
                <div class="sidebar-label px-3 mb-1.5 text-[10px] font-semibold text-neutral-400 uppercase tracking-wider">
                    System
                </div>
                <nav class="space-y-1">
                    <!-- Data & Import -->
                    <button onclick="switchTab('data-import')" id="tab-btn-data-import" 
                            class="tab-btn w-full px-3 py-2 rounded-md transition flex items-center justify-between text-xs font-medium text-neutral-600 hover:text-neutral-900 hover:bg-neutral-50 group"
                            title="Data & Ingesti Telemetri CSV">
                        <div class="flex items-center gap-2.5 min-w-0">
                            <svg class="w-4 h-4 text-neutral-400 group-hover:text-neutral-600 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 7v10c0 2 1.5 3 3.5 3h9c2 0 3.5-1 3.5-3V7c0-2-1.5-3-3.5-3h-9C5.5 4 4 5 4 7zm0 5h16M9 4v16"/></svg>
                            <span class="sidebar-label truncate">Data &amp; Import</span>
                        </div>
                    </button>

                    <!-- Konfigurasi Regulasi -->
                    <button onclick="switchTab('config-regulasi')" id="tab-btn-config-regulasi" 
                            class="tab-btn w-full px-3 py-2 rounded-md transition flex items-center justify-between text-xs font-medium text-neutral-600 hover:text-neutral-900 hover:bg-neutral-50 group"
                            title="Konfigurasi Regulasi & Tarif Listrik">
                        <div class="flex items-center gap-2.5 min-w-0">
                            <svg class="w-4 h-4 text-neutral-400 group-hover:text-neutral-600 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6V4m0 2a2 2 0 100 4m0-4a2 2 0 110 4m-6 8a2 2 0 100-4m0 4a2 2 0 110-4m0 4v2m0-6V4m6 6v10m6-2a2 2 0 100-4m0 4a2 2 0 110-4m0 4v2m0-6V4"/></svg>
                            <span class="sidebar-label truncate">Konfigurasi Regulasi</span>
                        </div>
                    </button>
                </nav>
            </div>

        </div>

        <!-- Sidebar Footer: Facility & Collapse Controls -->
        <div class="p-3 border-t border-neutral-200 shrink-0 bg-neutral-50/50">
            <div class="sidebar-label bg-white p-2.5 rounded border border-neutral-200/80 mb-2">
                <div class="flex items-center gap-2">
                    <img src="{{ asset('images/indmira-logo.jpeg') }}" alt="PT Indmira Logo" 
                         class="w-6 h-6 rounded object-contain bg-white border border-neutral-200/80 p-0.5 shrink-0">
                    <div class="min-w-0">
                        <div class="text-[11px] font-semibold text-neutral-900 truncate">PT Indmira Global Energi</div>
                        <div class="text-[10px] text-neutral-500 truncate">Fasilitas Biomassa Terpadu</div>
                    </div>
                </div>
                <div class="flex items-center justify-between text-[10px] text-neutral-400 font-mono mt-1.5 pt-1.5 border-t border-neutral-100">
                    <span>Grid Jamali</span>
                    <span class="text-neutral-600 font-semibold">{{ $config->faktor_emisi_grid }} tCO₂e/MWh</span>
                </div>
            </div>
            
            <div class="flex items-center justify-between text-xs text-neutral-500">
                <button onclick="toggleSidebarCollapse()" id="sidebarCollapseBtn" 
                        class="hidden md:flex items-center gap-2 px-2 py-1.5 rounded hover:bg-neutral-200/70 text-neutral-600 transition w-full"
                        title="Perkecil Sidebar (Icon-Only Mode)">
                    <svg id="collapseIcon" class="w-4 h-4 transition-transform duration-200" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 19l-7-7 7-7m8 14l-7-7 7-7"/></svg>
                    <span class="sidebar-label text-xs">Perkecil Sidebar</span>
                </button>
            </div>
        </div>

    </aside>

    <!-- ========================================================================= -->
    <!-- 2. MAIN APPLICATION WORKSPACE COLUMN -->
    <!-- ========================================================================= -->
    <div class="flex-1 flex flex-col min-w-0 h-screen overflow-hidden">

        <!-- COMPACT GLOBAL TOPBAR -->
        <header class="h-14 bg-white border-b border-neutral-200 px-4 sm:px-6 flex items-center justify-between gap-4 shrink-0 z-30">
            <!-- Left: Mobile Menu & Facility Context -->
            <div class="flex items-center gap-3 min-w-0">
                <button onclick="toggleMobileSidebar()" class="md:hidden p-1.5 rounded text-neutral-600 hover:text-neutral-900 hover:bg-neutral-100" title="Buka Menu">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"/></svg>
                </button>

                <div class="flex items-center gap-2.5 shrink-0">
                    <img src="{{ asset('images/indmira-logo.jpeg') }}" alt="PT Indmira Logo" 
                         class="w-6 h-6 rounded object-contain bg-white border border-neutral-200/90 p-0.5 shrink-0">
                    <span class="font-semibold text-neutral-900 text-xs sm:text-sm whitespace-nowrap">
                        PT Indmira Global Energi
                    </span>
                    <span class="text-neutral-300 hidden sm:inline">•</span>
                    <span class="text-xs text-neutral-500 hidden sm:inline font-mono whitespace-nowrap">1 Sep – 30 Sep 2026 (30 Hari)</span>
                    <span class="hidden md:inline-flex items-center gap-1.5 px-2 py-0.5 rounded text-[10px] font-mono font-semibold {{ $isMandatory ? 'bg-amber-50 text-amber-800 border border-amber-200' : 'bg-emerald-50 text-emerald-800 border border-emerald-200' }}">
                        <span class="w-1.5 h-1.5 rounded-full {{ $isMandatory ? 'bg-amber-600' : 'bg-brand-800' }}"></span>
                        <span>{{ $isMandatory ? 'WAJIB (≥4.000 TOE)' : 'SUKARELA (<4.000 TOE)' }}</span>
                    </span>
                </div>
            </div>

            <!-- Right: Preset Switcher, IoT Live & Actions -->
            <div class="flex items-center gap-2 sm:gap-3 shrink-0">
                <!-- Preset Switcher -->
                <div class="hidden lg:inline-flex items-center p-0.5 bg-neutral-100 border border-neutral-200 rounded-md text-xs font-medium">
                    <a href="{{ route('dashboard', ['preset' => 'sukarela']) }}" 
                       class="px-2.5 py-1 rounded transition flex items-center gap-1.5 {{ $preset === 'sukarela' ? 'bg-white text-neutral-900 shadow-xs font-semibold' : 'text-neutral-600 hover:text-neutral-900' }}">
                        <span class="w-1.5 h-1.5 rounded-full {{ $preset === 'sukarela' ? 'bg-brand-800' : 'bg-neutral-400' }}"></span>
                        <span>Pilot Plant (&lt; 4.000 TOE)</span>
                    </a>
                    <a href="{{ route('dashboard', ['preset' => 'wajib']) }}" 
                       class="px-2.5 py-1 rounded transition flex items-center gap-1.5 {{ $preset === 'wajib' ? 'bg-white text-neutral-900 shadow-xs font-semibold' : 'text-neutral-600 hover:text-neutral-900' }}">
                        <span class="w-1.5 h-1.5 rounded-full {{ $preset === 'wajib' ? 'bg-amber-600' : 'bg-neutral-400' }}"></span>
                        <span>Skala Industri (≥ 4.000 TOE)</span>
                    </a>
                    @if ($preset === 'custom_upload')
                    <span class="px-2.5 py-1 rounded bg-sky-50 text-sky-800 border border-sky-200 font-semibold flex items-center gap-1.5">
                        <span class="w-1.5 h-1.5 rounded-full bg-sky-600"></span>
                        <span>Custom CSV</span>
                    </span>
                    @endif
                </div>

                <!-- IoT Live Telemetry Simulator Toggle (FR-05) -->
                <button id="toggleIotBtn" onclick="toggleIotStreaming()" 
                        class="px-2.5 py-1.5 bg-white hover:bg-neutral-50 border border-neutral-200 text-neutral-700 rounded-md text-xs font-medium flex items-center gap-1.5 transition shadow-xs"
                        title="Aktifkan simulasi streaming telemetri IoT 3 detik">
                    <span id="iotLed" class="w-2 h-2 rounded-full bg-neutral-400"></span>
                    <span id="iotLabel" class="font-mono text-[11px]">IoT Live: OFF</span>
                </button>

                <!-- CSV Upload Modal Button (FR-01) -->
                <button onclick="document.getElementById('uploadModal').classList.remove('hidden')" 
                        class="px-2.5 py-1.5 bg-white hover:bg-neutral-50 text-neutral-700 border border-neutral-200 rounded-md text-xs font-medium flex items-center gap-1.5 transition shadow-xs"
                        title="Unggah data telemetri historis CSV">
                    <svg class="w-3.5 h-3.5 text-neutral-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12"/></svg>
                    <span class="hidden xl:inline">Impor CSV</span>
                </button>

                <!-- Export Audit Menu (FR-08 & F-06) -->
                <div class="relative inline-block text-left" id="exportMenuContainer">
                    <button type="button" onclick="toggleExportMenu(event)" 
                            class="px-2.5 py-1.5 bg-white hover:bg-neutral-50 text-neutral-700 border border-neutral-200 rounded-md text-xs font-medium flex items-center gap-1.5 transition shadow-xs"
                            title="Ekspor Lembar Kerja Audit Energi ESDM (PDF, Excel, Cetak)">
                        <svg class="w-3.5 h-3.5 text-neutral-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                        <span class="hidden 2xl:inline">Ekspor Audit ESDM</span>
                        <svg class="w-3 h-3 text-neutral-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
                    </button>
                    <div id="exportMenuDropdown" class="hidden absolute right-0 mt-1.5 w-56 bg-white rounded-lg shadow-lg border border-neutral-200 py-1.5 z-50 text-xs">
                        <a href="{{ route('audit.export', ['preset' => $preset, 'non_electric_toe' => $nonElectricToe]) }}" target="_blank"
                           class="flex items-center gap-2 px-3 py-2 text-neutral-700 hover:bg-neutral-50 transition">
                            <span class="w-2 h-2 rounded-full bg-brand-800 shrink-0"></span>
                            <span>Buka / Cetak Lembar Kerja</span>
                        </a>
                        <a href="{{ route('audit.export', ['preset' => $preset, 'non_electric_toe' => $nonElectricToe, 'format' => 'pdf']) }}" target="_blank"
                           class="flex items-center gap-2 px-3 py-2 text-neutral-700 hover:bg-neutral-50 transition">
                            <span class="w-2 h-2 rounded-full bg-rose-600 shrink-0"></span>
                            <span>Unduh Format PDF (.pdf)</span>
                        </a>
                        <a href="{{ route('audit.export', ['preset' => $preset, 'non_electric_toe' => $nonElectricToe, 'format' => 'excel']) }}" target="_blank"
                           class="flex items-center gap-2 px-3 py-2 text-neutral-700 hover:bg-neutral-50 transition">
                            <span class="w-2 h-2 rounded-full bg-emerald-600 shrink-0"></span>
                            <span>Unduh Matriks Excel (.csv)</span>
                        </a>
                    </div>
                </div>

                <!-- Secondary Contextual Action: Tanya AI Drawer Toggle -->
                <button onclick="toggleCopilotDrawer()" 
                        class="px-2.5 py-1.5 bg-white hover:bg-neutral-50 text-neutral-700 border border-neutral-200 rounded-md text-xs font-medium flex items-center gap-1.5 transition shadow-xs"
                        title="Tanya AI secara kontekstual mengenai data halaman saat ini">
                    <svg class="w-3.5 h-3.5 text-brand-800" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 10h.01M12 10h.01M16 10h.01M9 16H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-5l-5 5v-5z"/></svg>
                    <span>Tanya AI</span>
                </button>
            </div>
        </header>

        <!-- REAL-TIME IOT TELEMETRY LIVE TICKER (FR-05) -->
        <div id="iotLiveBanner" class="hidden bg-emerald-50 border-b border-emerald-200 shrink-0">
            <div class="px-4 sm:px-6 py-2 flex flex-wrap items-center justify-between gap-3 text-xs">
                <div class="flex items-center gap-2 text-emerald-900 font-medium">
                    <span class="w-2 h-2 rounded-full bg-emerald-600 animate-pulse"></span>
                    <span>Modbus/MQTT Real-time Stream Aktif (Polling 3s)</span>
                    <span class="text-emerald-700 text-neutral-400 font-mono text-[11px]">• Terakhir: <span id="iotTime" class="font-mono text-emerald-950">--:--:-- WIB</span></span>
                </div>
                <div class="flex items-center gap-6 font-mono text-xs text-emerald-950">
                    <div>Daya Aktif Total: <strong id="iotKw" class="text-emerald-900 font-bold">--</strong> kW</div>
                    <div>Daya Reaktif Total: <strong id="iotKvar" class="text-amber-800 font-bold">--</strong> kVAr</div>
                    <div>cos φ Agregat Pabrik: <strong id="iotPf" class="text-emerald-900 font-bold">--</strong></div>
                </div>
            </div>
        </div>

        <!-- ========================================================================= -->
        <!-- SCROLLABLE WORKSPACE CONTENT -->
        <!-- ========================================================================= -->
        <main class="flex-1 overflow-y-auto px-4 sm:px-6 lg:px-8 py-6 space-y-6">

            <!-- ===================================================================== -->
            <!-- VIEW 1: TAB IKHTISAR (OVERVIEW) -->
            <!-- ===================================================================== -->
            <div id="overview-content" class="tab-content space-y-6">

                <!-- Overview Page Title Header -->
                <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 pb-3 border-b border-neutral-200">
                    <div>
                        <h1 class="text-xl font-bold tracking-tight text-neutral-900">Ikhtisar Kinerja Energi Fasilitas</h1>
                        <p class="text-xs text-neutral-500 mt-0.5">Monitoring efisiensi spesifik, baseline EnB ISO 50001, anomali beban operasional, dan kepatuhan regulasi PP 33/2023.</p>
                    </div>
                    <div class="flex items-center gap-2">
                        <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-md text-xs font-mono font-semibold {{ $isMandatory ? 'bg-amber-50 text-amber-900 border border-amber-300' : 'bg-emerald-50 text-emerald-900 border border-emerald-300' }}">
                            <span class="w-2 h-2 rounded-full {{ $isMandatory ? 'bg-amber-600' : 'bg-brand-800' }}"></span>
                            <span>{{ $isMandatory ? 'STATUS: WAJIB MANAJEMEN ENERGI' : 'STATUS: SUKARELA (< 4.000 TOE)' }}</span>
                        </span>
                    </div>
                </div>

                <!-- Executive KPI Cards Grid (4 Cards) -->
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
                
                <!-- KPI 1: Total Konsumsi Listrik & Status Regulasi -->
                <div class="bg-white p-5 rounded-lg border border-neutral-200 shadow-xs flex flex-col justify-between">
                    <div>
                        <div class="flex items-center justify-between gap-2 mb-2">
                            <span class="text-xs font-medium text-neutral-500">Total Konsumsi Listrik</span>
                            <span class="px-2 py-0.5 rounded text-[10px] font-mono font-semibold {{ $isMandatory ? 'bg-amber-50 text-amber-800 border border-amber-200' : 'bg-emerald-50 text-emerald-800 border border-emerald-200' }}">
                                {{ $isMandatory ? 'WAJIB (≥4.000 TOE)' : 'SUKARELA (<4.000 TOE)' }}
                            </span>
                        </div>
                        <div class="flex items-baseline gap-2">
                            <span class="text-2xl font-bold font-mono tracking-tight text-neutral-900 tabular-nums">
                                {{ number_format($payload['kepatuhan_manajemen_energi']['total_listrik_bulanan_kwh'], 0, ',', '.') }}
                            </span>
                            <span class="text-xs text-neutral-500 font-medium">kWh/bulan</span>
                        </div>
                        <div class="mt-3 text-xs space-y-1 font-mono text-neutral-600 border-t border-neutral-100 pt-2.5">
                            <div class="flex justify-between">
                                <span class="font-sans text-neutral-500">Energi Listrik Bulanan:</span>
                                <span>{{ number_format($payload['kepatuhan_manajemen_energi']['konsumsi_toe_bulanan'], 2, ',', '.') }} TOE/bln</span>
                            </div>
                            <div class="flex justify-between">
                                <span class="font-sans text-neutral-500">Proyeksi Tahunan Listrik:</span>
                                <strong class="{{ $isMandatory ? 'text-amber-800' : 'text-emerald-800' }}">{{ number_format($payload['kepatuhan_manajemen_energi']['proyeksi_toe_tahunan_listrik'], 2, ',', '.') }} TOE/th</strong>
                            </div>
                        </div>
                    </div>
                    <div class="mt-4 pt-2.5 border-t border-neutral-100 flex items-center justify-between text-[11px] text-neutral-500">
                        <span>Dasar Regulasi:</span>
                        <span class="font-medium text-neutral-700">PP 33/2023 &amp; Permen 8/2025</span>
                    </div>
                </div>

                <!-- KPI 2: KES Produksi (Lini A) vs Baseline EnB -->
                <div class="bg-white p-5 rounded-lg border border-neutral-200 shadow-xs flex flex-col justify-between">
                    <div>
                        <div class="flex items-center justify-between gap-2 mb-2">
                            <span class="text-xs font-medium text-neutral-500">Intensitas Produksi (Lini A)</span>
                            <span class="px-2 py-0.5 rounded text-[10px] font-mono font-semibold bg-rose-50 text-rose-800 border border-rose-200">
                                BOROS (+{{ $lineA['deviasi_enpi_pct'] ?? 48.57 }}%)
                            </span>
                        </div>
                        <div class="flex items-baseline gap-2">
                            <span class="text-2xl font-bold font-mono tracking-tight text-neutral-900 tabular-nums">
                                {{ $lineA['ike_aktual'] ?? '0.52' }}
                            </span>
                            <span class="text-xs text-neutral-500 font-medium">kWh / batch</span>
                        </div>
                        <div class="mt-3 text-xs space-y-1 font-mono text-neutral-600 border-t border-neutral-100 pt-2.5">
                            <div class="flex justify-between">
                                <span class="font-sans text-neutral-500">Baseline Target (EnB):</span>
                                <span>0,35 kWh/batch</span>
                            </div>
                            <div class="flex justify-between">
                                <span class="font-sans text-neutral-500">Base Load E₀ (OLS):</span>
                                <strong class="text-rose-700">{{ $lineA['base_load_e0_kwh'] ?? '22.4' }} kW (R² = {{ $lineA['r2_baseline'] ?? '0.91' }})</strong>
                            </div>
                        </div>
                    </div>
                    <div class="mt-4 pt-2.5 border-t border-neutral-100 flex items-center justify-between text-[11px] text-neutral-500">
                        <span>Kondisi Mesin:</span>
                        <span class="font-medium text-rose-700">Beban Siaga Tinggi saat Istirahat</span>
                    </div>
                </div>

                <!-- KPI 3: Energi Aktif Terbuang (Idle & Spike) -->
                <div class="bg-white p-5 rounded-lg border border-neutral-200 shadow-xs flex flex-col justify-between">
                    <div>
                        <div class="flex items-center justify-between gap-2 mb-2">
                            <span class="text-xs font-medium text-neutral-500">Energi Aktif Terbuang</span>
                            <span class="px-2 py-0.5 rounded text-[10px] font-mono font-semibold bg-rose-50 text-rose-800 border border-rose-200">
                                {{ number_format(($payload['temuan_pemborosan_dan_finansial']['total_potensi_hemat_kwh'] / max(1, $payload['kepatuhan_manajemen_energi']['total_listrik_bulanan_kwh'])) * 100, 2) }}% INEFISIENSI
                            </span>
                        </div>
                        <div class="flex items-baseline gap-2">
                            <span class="text-2xl font-bold font-mono tracking-tight text-rose-700 tabular-nums">
                                {{ number_format($payload['temuan_pemborosan_dan_finansial']['total_potensi_hemat_kwh'], 0, ',', '.') }}
                            </span>
                            <span class="text-xs text-neutral-500 font-medium">kWh / bulan</span>
                        </div>
                        <div class="mt-3 text-xs space-y-1 font-mono text-neutral-600 border-t border-neutral-100 pt-2.5">
                            <div class="flex justify-between">
                                <span class="font-sans text-neutral-500">Kebocoran Idle (Break):</span>
                                <span>{{ number_format($payload['temuan_pemborosan_dan_finansial']['idle_load_waste_kwh'], 0, ',', '.') }} kWh</span>
                            </div>
                            <div class="flex justify-between">
                                <span class="font-sans text-neutral-500">Anomali Lonjakan (Z &gt; 2):</span>
                                <span>{{ number_format($payload['temuan_pemborosan_dan_finansial']['spike_anomaly_waste_kwh'], 0, ',', '.') }} kWh</span>
                            </div>
                        </div>
                    </div>
                    <div class="mt-4 pt-2.5 border-t border-neutral-100 flex items-center justify-between text-[11px] text-neutral-500">
                        <span>Nilai Kerugian Energi:</span>
                        <span class="font-mono font-semibold text-rose-700">Rp {{ number_format($payload['temuan_pemborosan_dan_finansial']['hemat_eliminasi_idle_dan_spike_rp'], 0, ',', '.') }}</span>
                    </div>
                </div>

                <!-- KPI 4: Total Potensi Penghematan Finansial -->
                <div class="bg-white p-5 rounded-lg border border-neutral-200 shadow-xs flex flex-col justify-between">
                    <div>
                        <div class="flex items-center justify-between gap-2 mb-2">
                            <span class="text-xs font-medium text-brand-800">Total Potensi Penghematan</span>
                            <span class="px-2 py-0.5 rounded text-[10px] font-mono font-semibold bg-emerald-50 text-emerald-800 border border-emerald-200">
                                -{{ number_format($payload['temuan_pemborosan_dan_finansial']['potensi_reduksi_co2_ton'], 2, ',', '.') }} tCO₂e/bln
                            </span>
                        </div>
                        <div class="flex items-baseline gap-2">
                            <span class="text-2xl font-bold font-mono tracking-tight text-brand-800 tabular-nums">
                                Rp {{ number_format($payload['temuan_pemborosan_dan_finansial']['total_potensi_hemat_rp'], 0, ',', '.') }}
                            </span>
                            <span class="text-xs text-neutral-500 font-medium">/ bulan</span>
                        </div>
                        <div class="mt-3 text-xs space-y-1 font-mono text-neutral-600 border-t border-neutral-100 pt-2.5">
                            <div class="flex justify-between">
                                <span class="font-sans text-neutral-500">Geser Beban WBP:</span>
                                <span>Rp {{ number_format($payload['temuan_pemborosan_dan_finansial']['hemat_geser_wbp_rp'], 0, ',', '.') }}</span>
                            </div>
                            <div class="flex justify-between">
                                <span class="font-sans text-neutral-500">Bebas Penalti kVArh:</span>
                                <span>Rp {{ number_format($payload['temuan_pemborosan_dan_finansial']['biaya_kvarh_rp'], 0, ',', '.') }}</span>
                            </div>
                        </div>
                    </div>
                    <div class="mt-4 pt-2.5 border-t border-neutral-100 flex items-center justify-between text-[11px] text-neutral-500">
                        <span>Estimasi / Tahun:</span>
                        <span class="font-mono font-bold text-brand-800">Rp {{ number_format($payload['temuan_pemborosan_dan_finansial']['total_potensi_hemat_rp'] * 12, 0, ',', '.') }}</span>
                    </div>
                </div>

            </div>

            <!-- Priority Operational Action Callout -->
            <div class="bg-white border border-neutral-200 rounded-lg p-4 flex flex-col md:flex-row items-start md:items-center justify-between gap-4">
                <div class="flex items-start gap-3">
                    <div class="w-8 h-8 rounded-md bg-amber-50 border border-amber-200 text-amber-800 flex items-center justify-center shrink-0 mt-0.5">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                    </div>
                    <div>
                        <h2 class="text-xs font-semibold text-neutral-900">
                            3 Prioritas Tindakan Efisiensi Energi Teridentifikasi
                        </h2>
                        <p class="text-xs text-neutral-600 mt-0.5">
                            1) Eliminasi idle load 2.450 kWh saat istirahat siang di Line Pirolisis, 2) Pergeseran 1.200 kWh rotary dryer keluar dari jam WBP (18:00–22:00), 3) Pemasangan kapasitor bank APFC untuk menaikkan cos φ dari 0,81 ke ≥ 0,85 guna menghapus tagihan penalti kVArh PLN.
                        </p>
                    </div>
                </div>
                <div class="flex items-center gap-2 shrink-0">
                    <button onclick="switchTab('recommendations')" class="px-3 py-1.5 bg-neutral-100 hover:bg-neutral-200 text-neutral-800 rounded-md text-xs font-medium transition">
                        Buka Rekomendasi ESDM
                    </button>
                    <button onclick="switchTab('copilot')" class="px-3 py-1.5 bg-brand-800 hover:bg-brand-900 text-white rounded-md text-xs font-medium transition shadow-xs">
                        Konsultasi Copilot
                    </button>
                </div>
            </div>

            <!-- Primary Chart: Time-Series Load Profile & Anomaly Detection -->
            <div class="bg-white p-5 rounded-lg border border-neutral-200 shadow-xs">
                <div class="flex flex-wrap items-center justify-between gap-3 mb-4">
                    <div>
                        <h2 class="text-sm font-semibold text-neutral-900 flex items-center gap-2">
                            Profil Beban Listrik &amp; Deteksi Anomali Energi
                        </h2>
                        <p class="text-xs text-neutral-500 mt-0.5">
                            Perbandingan konsumsi listrik aktual vs model <em>Energy Baseline</em> (EnB) ISO 50001 (Periode 30 Hari)
                        </p>
                    </div>
                    <div class="flex items-center gap-2">
                        <button id="toggleCusumBtn" onclick="toggleCusumSeries()" 
                                class="px-2.5 py-1 bg-white hover:bg-neutral-50 border border-neutral-300 text-neutral-700 rounded-md text-xs font-medium transition shadow-xs flex items-center gap-1.5">
                            <span class="w-2 h-2 rounded-full bg-purple-600"></span>
                            <span>Tampilkan CUSUM ISO 50001</span>
                        </button>
                    </div>
                </div>

                <!-- ApexChart Container -->
                <div id="timeseriesChart" class="w-full min-h-[380px]"></div>

                <!-- Restrained Semantic Legend -->
                <div class="mt-4 pt-3 border-t border-neutral-100 flex flex-wrap items-center justify-between text-xs text-neutral-600 gap-3">
                    <div class="flex flex-wrap items-center gap-4">
                        <span class="flex items-center gap-1.5">
                            <span class="w-3.5 h-1 bg-blue-700 rounded-sm"></span>
                            <span>Konsumsi Listrik Aktual (kWh)</span>
                        </span>
                        <span class="flex items-center gap-1.5">
                            <span class="w-3.5 h-1 border-t-2 border-dashed border-emerald-700"></span>
                            <span>Energy Baseline (EnB ISO 50001)</span>
                        </span>
                        <span class="flex items-center gap-1.5">
                            <span class="w-3.5 h-1 bg-purple-700 rounded-sm"></span>
                            <span>Kumulatif Selisih CUSUM</span>
                        </span>
                    </div>
                    <div class="flex items-center gap-4 text-[11px]">
                        <span class="flex items-center gap-1.5 text-rose-700">
                            <span class="w-2 h-2 rounded-full bg-rose-600"></span>
                            <span>Spike Anomaly (Z &gt; 2,0)</span>
                        </span>
                        <span class="flex items-center gap-1.5 text-amber-700">
                            <span class="w-2 h-2 rounded-full bg-amber-500"></span>
                            <span>Idle Waste (Jam Istirahat)</span>
                        </span>
                    </div>
                </div>
            </div>

            <!-- 2-Column Section: Zone Performance Table & Recent Findings -->
            <div class="grid grid-cols-1 lg:grid-cols-12 gap-6 items-start">
                
                <!-- Left: Zone Monitoring Table (8 Cols, ~2:1 Ratio - Defines natural height) -->
                <div id="sdpTablePanel" class="lg:col-span-8 bg-white p-5 rounded-lg border border-neutral-200 shadow-xs">
                    <div class="flex items-center justify-between mb-4">
                        <div>
                            <h2 class="text-sm font-semibold text-neutral-900">
                                Kinerja Energi per Sub-Distribution Panel (SDP)
                            </h2>
                            <p class="text-xs text-neutral-500 mt-0.5">
                                Evaluasi KES Produksi (ISO 50001) &amp; IKE Bangunan Gedung (SNI 6196 / Permen ESDM 3/2025)
                            </p>
                        </div>
                        <span class="text-xs font-mono text-neutral-500 bg-neutral-100 px-2 py-0.5 rounded border border-neutral-200 shrink-0">
                            4 Zona SDP
                        </span>
                    </div>

                    <div class="overflow-x-auto border border-neutral-200 rounded-md">
                        <table class="w-full text-xs text-left">
                            <thead class="bg-neutral-50 text-neutral-600 font-semibold border-b border-neutral-200 text-[11px]">
                                <tr>
                                    <th class="p-3">Panel / Zona</th>
                                    <th class="p-3">Kategori</th>
                                    <th class="p-3 text-right">Konsumsi</th>
                                    <th class="p-3 text-right">Porsi</th>
                                    <th class="p-3 text-right">IKE Aktual</th>
                                    <th class="p-3 text-right">Standar/EnB</th>
                                    <th class="p-3 text-right">Base Load E₀</th>
                                    <th class="p-3 text-center">R² OLS</th>
                                    <th class="p-3 text-center">cos φ</th>
                                    <th class="p-3 text-center">Status</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-neutral-200 font-mono text-xs">
                                @foreach ($payload['analisis_ike_per_panel'] as $p)
                                <tr class="hover:bg-neutral-50 transition">
                                    <td class="p-3 font-sans font-medium text-neutral-900">
                                        <div>{{ $p['panel_id'] }}</div>
                                        <div class="text-[11px] text-neutral-500 font-normal">{{ $p['deskripsi'] }}</div>
                                    </td>
                                    <td class="p-3 font-sans text-neutral-600">
                                        <span class="px-1.5 py-0.5 bg-neutral-100 border border-neutral-200 rounded text-[10px] text-neutral-700">
                                            {{ $p['kategori'] }}
                                        </span>
                                    </td>
                                    <td class="p-3 text-right text-neutral-900 font-medium">
                                        {{ number_format($p['konsumsi_kwh'], 0, ',', '.') }} kWh
                                    </td>
                                    <td class="p-3 text-right text-neutral-600">{{ $p['porsi_konsumsi_pct'] }}%</td>
                                    <td class="p-3 text-right font-bold text-neutral-900">
                                        {{ $p['ike_aktual'] }} <span class="text-[10px] font-normal text-neutral-500">{{ $p['kategori'] === 'PRODUCTION' ? 'kWh/btch' : 'kWh/m²' }}</span>
                                    </td>
                                    <td class="p-3 text-right text-neutral-600">{{ $p['baseline_enb'] ?? '-' }}</td>
                                    <td class="p-3 text-right {{ isset($p['base_load_e0_kwh']) && $p['base_load_e0_kwh'] > 15 ? 'text-rose-700 font-semibold' : 'text-neutral-700' }}">
                                        {{ isset($p['base_load_e0_kwh']) ? $p['base_load_e0_kwh'] . ' kW' : '-' }}
                                    </td>
                                    <td class="p-3 text-center text-neutral-700">{{ $p['r2_baseline'] ?? '-' }}</td>
                                    <td class="p-3 text-center {{ ($p['cos_phi_rata_rata'] ?? 1.0) < 0.85 ? 'text-rose-700 font-semibold' : 'text-neutral-700' }}">
                                        {{ $p['cos_phi_rata_rata'] ?? '-' }}
                                    </td>
                                    <td class="p-3 text-center font-sans font-medium">
                                        @if (str_contains($p['status_evaluasi'], 'BOROS'))
                                            <span class="px-2 py-0.5 rounded text-[10px] bg-rose-50 text-rose-800 border border-rose-200 font-semibold">BOROS</span>
                                        @elseif (str_contains($p['status_evaluasi'], 'WASPADA'))
                                            <span class="px-2 py-0.5 rounded text-[10px] bg-amber-50 text-amber-800 border border-amber-200 font-semibold">WASPADA</span>
                                        @else
                                            <span class="px-2 py-0.5 rounded text-[10px] bg-emerald-50 text-emerald-800 border border-emerald-200 font-semibold">EFISIEN</span>
                                        @endif
                                    </td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- Right: Recent Operational Anomalies (4 Cols, ~2:1 Ratio, Independently Scrollable) -->
                <div id="recentAnomaliesPanel" class="lg:col-span-4 bg-white p-5 rounded-lg border border-neutral-200 shadow-xs flex flex-col min-h-0 max-h-[420px] lg:max-h-none">
                    <!-- Fixed Header -->
                    <div class="flex items-center justify-between mb-3 shrink-0">
                        <div>
                            <h2 class="text-sm font-semibold text-neutral-900">
                                Temuan Anomali Terakhir
                            </h2>
                            <p class="text-xs text-neutral-500 mt-0.5">
                                Log kejadian pemborosan energi terdeteksi
                            </p>
                        </div>
                        <button onclick="switchTab('anomalies')" class="text-xs text-brand-800 hover:underline font-medium shrink-0 ml-2" title="Buka tab Anomali &amp; Pemborosan">
                            Lihat Semua &rarr;
                        </button>
                    </div>

                    <!-- Scrollable Content Region -->
                    <div class="flex-1 overflow-y-auto min-h-0 space-y-2.5 pr-1 clean-scrollbar">
                        @forelse ($payload['visualisasi_grafik']['anomaly_logs'] as $log)
                        <div class="p-3 rounded-md border border-neutral-200 bg-neutral-50/50 hover:bg-neutral-50 transition text-xs">
                            <div class="flex items-center justify-between text-[11px] mb-1">
                                <span class="font-mono text-neutral-500">{{ $log['timestamp'] }}</span>
                                @if ($log['severity'] === 'danger')
                                    <span class="px-1.5 py-0.2 rounded text-[10px] font-semibold bg-rose-50 text-rose-800 border border-rose-200">Kritis</span>
                                @elseif ($log['severity'] === 'warning')
                                    <span class="px-1.5 py-0.2 rounded text-[10px] font-semibold bg-amber-50 text-amber-800 border border-amber-200">Waspada</span>
                                @else
                                    <span class="px-1.5 py-0.2 rounded text-[10px] font-semibold bg-blue-50 text-blue-800 border border-blue-200">Info WBP</span>
                                @endif
                            </div>
                            <p class="font-semibold text-neutral-900">{{ $log['type'] }} • <span class="font-mono text-neutral-600">{{ $log['panel_id'] }}</span></p>
                            <p class="text-neutral-600 text-[11px] mt-0.5 line-clamp-2">{{ $log['description'] }}</p>
                            <div class="mt-2 flex items-center justify-between font-mono text-[11px] pt-1.5 border-t border-neutral-200/60">
                                <span class="text-neutral-500">Hilang: <strong class="text-rose-700">{{ $log['loss_kwh'] }} kWh</strong></span>
                                <span class="text-neutral-700">Rp {{ number_format($log['loss_rp'], 0, ',', '.') }}</span>
                            </div>
                        </div>
                        @empty
                        <div class="p-6 text-center text-neutral-500 text-xs">
                            Tidak ada anomali terdeteksi dalam periode ini.
                        </div>
                        @endforelse
                    </div>
                </div>

            </div>

        </div>

        <!-- ===================================================================== -->
        <!-- VIEW 2: TAB ANALITIK ENERGI & ISO 50001 -->
        <!-- ===================================================================== -->
        <div id="analytics-content" class="tab-content hidden space-y-6">

            <!-- Title & Concept Spine -->
            <div class="bg-white p-5 rounded-lg border border-neutral-200 shadow-xs">
                <div class="flex flex-col md:flex-row md:items-center justify-between gap-4">
                    <div>
                        <h2 class="text-base font-semibold text-neutral-900">
                            Model Energy Baseline (EnB) &amp; Analisis Kinerja ISO 50001
                        </h2>
                        <p class="text-xs text-neutral-600 mt-1 max-w-4xl leading-relaxed">
                            Penetapan garis dasar energi fasilitas pengolahan biomassa menggunakan regresi linier kuadrat terkecil (<em>Ordinary Least Squares / OLS</em>) memisahkan beban produksi marjinal dengan beban dasar siaga tanpa produksi (<em>Base Load E₀</em>).
                        </p>
                    </div>
                    <div class="flex items-center gap-2 shrink-0">
                        <span class="px-2.5 py-1 rounded text-xs font-mono font-medium bg-emerald-50 text-emerald-800 border border-emerald-200">
                            SNI ISO 50001:2018 Terverifikasi
                        </span>
                    </div>
                </div>
            </div>

            <!-- OLS Models Comparison Cards -->
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                
                <!-- Line A Model -->
                <div class="bg-white p-5 rounded-lg border border-neutral-200 shadow-xs space-y-4">
                    <div class="flex items-center justify-between">
                        <div>
                            <h3 class="text-sm font-semibold text-neutral-900">SDP-01: Lini Pirolisis Biochar &amp; Crusher</h3>
                            <p class="text-xs text-neutral-500">Model Regresi Linier OLS Beban Utama Produksi</p>
                        </div>
                        <span class="px-2 py-0.5 rounded text-xs font-mono font-semibold bg-emerald-50 text-emerald-800 border border-emerald-200">
                            R² = 0,91 (Sangat Andal)
                        </span>
                    </div>

                    <div class="bg-neutral-50 p-4 rounded-md border border-neutral-200 font-mono text-xs space-y-2">
                        <div class="text-neutral-500 text-[11px] font-sans">Persamaan Energy Baseline (EnB):</div>
                        <div class="text-base font-bold text-neutral-900">
                            E = 0,25 · P + 22,4 <span class="text-xs font-normal text-neutral-500">(kW)</span>
                        </div>
                        <div class="grid grid-cols-2 gap-2 pt-2 border-t border-neutral-200 text-[11px]">
                            <div>Konsumsi Marjinal (a): <strong class="text-neutral-800">0,25 kWh/batch</strong></div>
                            <div>Base Load (E₀): <strong class="text-rose-700">22,4 kW</strong></div>
                        </div>
                    </div>

                    <div class="text-xs text-neutral-600 leading-relaxed space-y-2">
                        <p>
                            <strong>Evaluasi Teknis:</strong> Nilai R² = 0,91 melampaui batas minimum R² ≥ 0,75, menandakan model regresi sangat dapat diandalkan untuk memprediksi konsumsi energi lini pirolisis. 
                        </p>
                        <p class="text-rose-800 bg-rose-50 p-2.5 rounded border border-rose-200 text-[11px]">
                            <strong>Temuan Kritis:</strong> Nilai beban dasar E₀ = 22,4 kW mengindikasikan bahwa motor pengumpan biomassa dan mixer tetap berputar tanpa muatan saat operator istirahat siang, menyumbang 2.450 kWh pemborosan aktif per bulan.
                        </p>
                    </div>
                </div>

                <!-- Utility Model -->
                <div class="bg-white p-5 rounded-lg border border-neutral-200 shadow-xs space-y-4">
                    <div class="flex items-center justify-between">
                        <div>
                            <h3 class="text-sm font-semibold text-neutral-900">SDP-02: Utilitas Pabrik &amp; Rotary Dryer</h3>
                            <p class="text-xs text-neutral-500">Model Regresi Linier OLS Sistem Termal &amp; Kompresor</p>
                        </div>
                        <span class="px-2 py-0.5 rounded text-xs font-mono font-semibold bg-emerald-50 text-emerald-800 border border-emerald-200">
                            R² = 0,88 (Andal)
                        </span>
                    </div>

                    <div class="bg-neutral-50 p-4 rounded-md border border-neutral-200 font-mono text-xs space-y-2">
                        <div class="text-neutral-500 text-[11px] font-sans">Persamaan Energy Baseline (EnB):</div>
                        <div class="text-base font-bold text-neutral-900">
                            E = 0,20 · P + 14,1 <span class="text-xs font-normal text-neutral-500">(kW)</span>
                        </div>
                        <div class="grid grid-cols-2 gap-2 pt-2 border-t border-neutral-200 text-[11px]">
                            <div>Konsumsi Marjinal (a): <strong class="text-neutral-800">0,20 kWh/batch</strong></div>
                            <div>Base Load (E₀): <strong class="text-amber-800">14,1 kW</strong></div>
                        </div>
                    </div>

                    <div class="text-xs text-neutral-600 leading-relaxed space-y-2">
                        <p>
                            <strong>Evaluasi Teknis:</strong> Korelasi produksi terhadap beban utilitas memiliki R² = 0,88. Base load 14,1 kW sebagian besar diserap oleh siklus pemompaan pendingin pirolisis dan pengisian tangki udara kompresor.
                        </p>
                        <p class="text-amber-900 bg-amber-50 p-2.5 rounded border border-amber-200 text-[11px]">
                            <strong>Peluang Finansial:</strong> Operasi pemanasan rotary dryer pada jam WBP (18:00–22:00 WIB) menyerap 1.200 kWh dengan tarif faktor K=1,40 (Rp 1.450,09/kWh). Pergeseran jadwal pemanasan ke jam LWBP menghemat Rp 497.172/bulan murni tanpa biaya investasi.
                        </p>
                    </div>
                </div>

            </div>

            <!-- EnPI & CUSUM Mathematical Guidance -->
            <div class="bg-white p-5 rounded-lg border border-neutral-200 shadow-xs space-y-4">
                <h3 class="text-sm font-semibold text-neutral-900">
                    Kriteria Deviasi Indikator Kinerja Energi (EnPI) &amp; CUSUM ISO 50001
                </h3>

                <div class="grid grid-cols-1 md:grid-cols-3 gap-4 text-xs">
                    <div class="p-3.5 rounded-md border border-emerald-200 bg-emerald-50/50">
                        <div class="flex items-center justify-between mb-1">
                            <span class="font-semibold text-emerald-900">1. Kategori Normal</span>
                            <span class="font-mono text-[11px] text-emerald-800 font-bold">&le; 5,0%</span>
                        </div>
                        <p class="text-emerald-800 text-[11px] leading-relaxed">
                            Konsumsi aktual berada dalam toleransi wajar fluktuasi produksi terhadap baseline target. Tidak diperlukan investigasi operasional khusus.
                        </p>
                    </div>

                    <div class="p-3.5 rounded-md border border-amber-200 bg-amber-50/50">
                        <div class="flex items-center justify-between mb-1">
                            <span class="font-semibold text-amber-900">2. Kategori Waspada</span>
                            <span class="font-mono text-[11px] text-amber-800 font-bold">5,01% – 15,0%</span>
                        </div>
                        <p class="text-amber-800 text-[11px] leading-relaxed">
                            Terjadi indikasi awal penurunan efisiensi termal atau mekanis. Periksa tekanan kompresor dan kebocoran pipa udara bertekanan.
                        </p>
                    </div>

                    <div class="p-3.5 rounded-md border border-rose-200 bg-rose-50/50">
                        <div class="flex items-center justify-between mb-1">
                            <span class="font-semibold text-rose-900">3. Kategori Boros</span>
                            <span class="font-mono text-[11px] text-rose-800 font-bold">&gt; 15,0%</span>
                        </div>
                        <p class="text-rose-800 text-[11px] leading-relaxed">
                            Inefisiensi signifikan. SDP-01 tercatat mengalami deviasi +48,57% akibat kebocoran beban siaga istirahat dan penalti lonjakan motor crusher.
                        </p>
                    </div>
                </div>

                <div class="p-3 bg-neutral-50 rounded-md border border-neutral-200 text-xs text-neutral-600">
                    <strong>Prinsip Metodologi CUSUM (Cumulative Sum):</strong> Grafik CUSUM menghitung akumulasi selisih harian CUSUM<sub>k</sub> = Σ (E<sub>aktual</sub> − E<sub>prediksi</sub>). Kemiringan kurva yang terus menanjak positif mengonfirmasi bahwa pemborosan energi bersifat sistemik dan tidak mengoreksi dirinya sendiri tanpa intervensi rekayasa operasional.
                </div>
            </div>

        </div>

        <!-- ===================================================================== -->
        <!-- VIEW 3: TAB ANOMALI & PEMBOROSAN -->
        <!-- ===================================================================== -->
        <div id="anomalies-content" class="tab-content hidden space-y-6">

            <!-- 4 Vector Breakdown Cards -->
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
                
                <div class="bg-white p-4 rounded-lg border border-neutral-200 shadow-xs">
                    <div class="flex items-center justify-between text-xs text-neutral-500 mb-1">
                        <span>1. Idle Load Waste</span>
                        <span class="px-1.5 py-0.5 rounded text-[10px] font-mono font-semibold bg-amber-50 text-amber-800 border border-amber-200">Jam Istirahat</span>
                    </div>
                    <div class="text-xl font-bold font-mono text-neutral-900 mt-1">
                        {{ number_format($payload['temuan_pemborosan_dan_finansial']['idle_load_waste_kwh'], 0, ',', '.') }} <span class="text-xs font-normal text-neutral-500">kWh</span>
                    </div>
                    <div class="mt-2 text-xs font-mono text-neutral-600">
                        Nilai Rugi: <strong class="text-rose-700">Rp 2.537.661</strong>
                    </div>
                    <p class="text-[11px] text-neutral-500 mt-2 border-t border-neutral-100 pt-1.5">
                        Motor pengumpan berputar tanpa material pada jam 12:00–13:00 WIB.
                    </p>
                </div>

                <div class="bg-white p-4 rounded-lg border border-neutral-200 shadow-xs">
                    <div class="flex items-center justify-between text-xs text-neutral-500 mb-1">
                        <span>2. Spike Anomaly</span>
                        <span class="px-1.5 py-0.5 rounded text-[10px] font-mono font-semibold bg-rose-50 text-rose-800 border border-rose-200">Z &gt; 2,0</span>
                    </div>
                    <div class="text-xl font-bold font-mono text-neutral-900 mt-1">
                        {{ number_format($payload['temuan_pemborosan_dan_finansial']['spike_anomaly_waste_kwh'], 0, ',', '.') }} <span class="text-xs font-normal text-neutral-500">kWh</span>
                    </div>
                    <div class="mt-2 text-xs font-mono text-neutral-600">
                        Nilai Rugi: <strong class="text-rose-700">Rp 1.729.753</strong>
                    </div>
                    <p class="text-[11px] text-neutral-500 mt-2 border-t border-neutral-100 pt-1.5">
                        Lonjakan arus start crusher melebihi deviasi standar normal.
                    </p>
                </div>

                <div class="bg-white p-4 rounded-lg border border-neutral-200 shadow-xs">
                    <div class="flex items-center justify-between text-xs text-neutral-500 mb-1">
                        <span>3. WBP Peak Exposure</span>
                        <span class="px-1.5 py-0.5 rounded text-[10px] font-mono font-semibold bg-blue-50 text-blue-800 border border-blue-200">Faktor K=1,40</span>
                    </div>
                    <div class="text-xl font-bold font-mono text-neutral-900 mt-1">
                        {{ number_format($payload['temuan_pemborosan_dan_finansial']['wbp_shiftable_kwh'], 0, ',', '.') }} <span class="text-xs font-normal text-neutral-500">kWh</span>
                    </div>
                    <div class="mt-2 text-xs font-mono text-neutral-600">
                        Potensi Hemat: <strong class="text-brand-800">Rp {{ number_format($payload['temuan_pemborosan_dan_finansial']['hemat_geser_wbp_rp'], 0, ',', '.') }}</strong>
                    </div>
                    <p class="text-[11px] text-neutral-500 mt-2 border-t border-neutral-100 pt-1.5">
                        Pemanasan rotary dryer dapat digeser ke jam 22:30 WIB tanpa Capex.
                    </p>
                </div>

                <div class="bg-white p-4 rounded-lg border border-neutral-200 shadow-xs">
                    <div class="flex items-center justify-between text-xs text-neutral-500 mb-1">
                        <span>4. Penalti Daya Reaktif</span>
                        <span class="px-1.5 py-0.5 rounded text-[10px] font-mono font-semibold bg-rose-50 text-rose-800 border border-rose-200">cos φ = 0,81</span>
                    </div>
                    <div class="text-xl font-bold font-mono text-neutral-900 mt-1">
                        {{ number_format($payload['temuan_pemborosan_dan_finansial']['kvarh_kena_biaya'], 0, ',', '.') }} <span class="text-xs font-normal text-neutral-500">kVArh</span>
                    </div>
                    <div class="mt-2 text-xs font-mono text-neutral-600">
                        Denda PLN: <strong class="text-rose-700">Rp {{ number_format($payload['temuan_pemborosan_dan_finansial']['biaya_kvarh_rp'], 0, ',', '.') }}</strong>
                    </div>
                    <p class="text-[11px] text-neutral-500 mt-2 border-t border-neutral-100 pt-1.5">
                        Faktor daya bulanan di bawah 0,85 memicu penalti daya reaktif.
                    </p>
                </div>

            </div>

            <!-- Complete Anomaly Log Table -->
            <div class="bg-white p-5 rounded-lg border border-neutral-200 shadow-xs space-y-4">
                <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3">
                    <div>
                        <h2 class="text-sm font-semibold text-neutral-900">
                            Log Kejadian Anomali Operasional Terdeteksi
                        </h2>
                        <p class="text-xs text-neutral-500 mt-0.5">
                            Data historis anomali yang diekstraksi secara otomatis oleh mesin analitik deterministik
                        </p>
                    </div>
                    <div class="flex items-center gap-1.5 text-xs">
                        <span class="text-neutral-500 text-[11px]">Filter Kategori:</span>
                        <button onclick="filterAnomalyRows('ALL')" id="filter-all" class="px-2 py-1 rounded bg-neutral-200 text-neutral-800 font-medium">Semua</button>
                        <button onclick="filterAnomalyRows('Idle')" id="filter-idle" class="px-2 py-1 rounded bg-neutral-100 text-neutral-600 hover:bg-neutral-200">Idle Load</button>
                        <button onclick="filterAnomalyRows('Spike')" id="filter-spike" class="px-2 py-1 rounded bg-neutral-100 text-neutral-600 hover:bg-neutral-200">Spike</button>
                        <button onclick="filterAnomalyRows('WBP')" id="filter-wbp" class="px-2 py-1 rounded bg-neutral-100 text-neutral-600 hover:bg-neutral-200">WBP</button>
                    </div>
                </div>

                <div class="overflow-x-auto border border-neutral-200 rounded-md">
                    <table class="w-full text-xs text-left" id="anomalyTable">
                        <thead class="bg-neutral-50 text-neutral-600 font-semibold border-b border-neutral-200 text-[11px]">
                            <tr>
                                <th class="p-3">Waktu Kejadian</th>
                                <th class="p-3">Panel / Lokasi</th>
                                <th class="p-3">Tipe Pemborosan</th>
                                <th class="p-3">Karakteristik &amp; Penyebab Teridentifikasi</th>
                                <th class="p-3 text-right">Energi Terbuang</th>
                                <th class="p-3 text-right">Potensi Kerugian</th>
                                <th class="p-3 text-center">Tingkat Keparahan</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-neutral-200 font-mono text-xs">
                            @forelse ($payload['visualisasi_grafik']['anomaly_logs'] as $log)
                            <tr class="hover:bg-neutral-50 transition anomaly-row" data-type="{{ $log['type'] }}">
                                <td class="p-3 text-neutral-600 whitespace-nowrap">{{ $log['timestamp'] }}</td>
                                <td class="p-3 font-semibold text-neutral-900 font-sans">{{ $log['panel_id'] }}</td>
                                <td class="p-3 font-sans font-medium text-neutral-800">{{ $log['type'] }}</td>
                                <td class="p-3 text-neutral-600 font-sans text-xs max-w-sm">{{ $log['description'] }}</td>
                                <td class="p-3 text-right text-rose-700 font-bold whitespace-nowrap">{{ $log['loss_kwh'] }} kWh</td>
                                <td class="p-3 text-right text-neutral-900 font-semibold whitespace-nowrap">Rp {{ number_format($log['loss_rp'], 0, ',', '.') }}</td>
                                <td class="p-3 text-center font-sans">
                                    @if ($log['severity'] === 'danger')
                                        <span class="px-2 py-0.5 rounded text-[10px] font-semibold bg-rose-50 text-rose-800 border border-rose-200">Kritis</span>
                                    @elseif ($log['severity'] === 'warning')
                                        <span class="px-2 py-0.5 rounded text-[10px] font-semibold bg-amber-50 text-amber-800 border border-amber-200">Waspada</span>
                                    @else
                                        <span class="px-2 py-0.5 rounded text-[10px] font-semibold bg-blue-50 text-blue-800 border border-blue-200">Info WBP</span>
                                    @endif
                                </td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="7" class="p-6 text-center text-neutral-500 font-sans">Tidak ada catatan anomali terdeteksi.</td>
                            </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

        </div>

        <!-- ===================================================================== -->
        <!-- VIEW 4: TAB MATRIKS REKOMENDASI (PERMEN ESDM 8/2025) -->
        <!-- ===================================================================== -->
        <div id="recommendations-content" class="tab-content hidden space-y-6">

            <!-- Header & ESDM Framework Introduction -->
            <div class="bg-white p-5 rounded-lg border border-neutral-200 shadow-xs flex flex-col md:flex-row md:items-center justify-between gap-4">
                <div>
                    <h2 class="text-base font-semibold text-neutral-900">
                        Matriks Rekomendasi Audit Energi (Permen ESDM No. 8 Tahun 2025)
                    </h2>
                    <p class="text-xs text-neutral-600 mt-1 max-w-4xl leading-relaxed">
                        Seluruh usulan efisiensi diklasifikasikan ke dalam 4 tingkatan investasi baku berbasis <em>Payback Period</em> lengkap dengan proyeksi penghematan finansial (Rupiah/bulan), konservasi fisik kWh, dan reduksi emisi karbon terverifikasi.
                    </p>
                </div>
                <div class="flex items-center gap-2 font-mono text-xs text-brand-800 bg-brand-50 px-3 py-1.5 rounded border border-brand-200">
                    <span>Total Penghematan: <strong>Rp {{ number_format($payload['temuan_pemborosan_dan_finansial']['total_potensi_hemat_rp'], 0, ',', '.') }}</strong> / bln</span>
                </div>
            </div>

            <!-- Recommendation Cards List (5 Actionable Cards) -->
            <div class="space-y-4">
                @foreach ($payload['klasifikasi_rekomendasi_audit_esdm'] as $rek)
                @php
                    $badgeStyle = match ($rek['kategori_investasi']) {
                        'No-Cost' => 'bg-emerald-50 text-emerald-900 border-emerald-300',
                        'Low-Cost' => 'bg-blue-50 text-blue-900 border-blue-300',
                        'Medium-Cost' => 'bg-amber-50 text-amber-900 border-amber-300',
                        'High-Cost' => 'bg-purple-50 text-purple-900 border-purple-300',
                        default => 'bg-neutral-100 text-neutral-800 border-neutral-300',
                    };
                @endphp
                <div class="bg-white p-5 rounded-lg border border-neutral-200 shadow-xs hover:border-neutral-300 transition">
                    <div class="flex flex-col lg:flex-row lg:items-start justify-between gap-4">
                        
                        <div class="space-y-2 flex-1">
                            <div class="flex flex-wrap items-center gap-2">
                                <span class="px-2 py-0.5 rounded text-xs font-semibold uppercase border {{ $badgeStyle }}">
                                    {{ $rek['kategori_investasi'] }} • Kriteria Payback: {{ $rek['kriteria_payback'] }}
                                </span>
                                <span class="text-xs font-mono text-neutral-500 bg-neutral-100 px-2 py-0.5 rounded border border-neutral-200">
                                    Lokasi: {{ $rek['lokasi_panel'] }}
                                </span>
                            </div>

                            <h3 class="text-sm font-semibold text-neutral-900">
                                {{ $rek['judul_tindakan'] }}
                            </h3>

                            <p class="text-xs text-neutral-600 leading-relaxed max-w-3xl">
                                {{ $rek['deskripsi_teknis'] }}
                            </p>
                        </div>

                        <!-- Financial & Payback Metrics Box -->
                        <div class="grid grid-cols-2 sm:grid-cols-4 lg:grid-cols-2 gap-3 shrink-0 lg:w-72 bg-neutral-50 p-3.5 rounded-md border border-neutral-200 font-mono text-xs">
                            <div>
                                <span class="text-[11px] font-sans text-neutral-500 block">Potensi Hemat:</span>
                                <strong class="text-brand-800 text-sm">Rp {{ number_format($rek['potensi_hemat_rp_per_bulan'], 0, ',', '.') }}</strong>
                                <span class="text-[10px] text-neutral-500 font-sans block">/ bulan</span>
                            </div>

                            <div>
                                <span class="text-[11px] font-sans text-neutral-500 block">Investasi Capex:</span>
                                <strong class="text-neutral-900 text-sm">
                                    {{ $rek['estimasi_biaya_implementasi_rp'] > 0 ? 'Rp ' . number_format($rek['estimasi_biaya_implementasi_rp'], 0, ',', '.') : 'Rp 0 (Tanpa Biaya)' }}
                                </strong>
                            </div>

                            <div>
                                <span class="text-[11px] font-sans text-neutral-500 block">Payback Period:</span>
                                <strong class="text-neutral-900 text-sm">
                                    {{ $rek['payback_period_tahun'] > 0 ? number_format($rek['payback_period_tahun'], 2, ',', '.') . ' Tahun' : 'Instan' }}
                                </strong>
                            </div>

                            <div>
                                <span class="text-[11px] font-sans text-neutral-500 block">Reduksi Emisi:</span>
                                <strong class="text-neutral-700 text-sm">
                                    {{ $rek['potensi_reduksi_co2_ton'] > 0 ? '-' . number_format($rek['potensi_reduksi_co2_ton'], 2, ',', '.') . ' tCO₂e' : '0 tCO₂e (Finansial)' }}
                                </strong>
                            </div>
                        </div>

                    </div>
                </div>
                @endforeach
            </div>

            <!-- Clear Legal Note on Load Shifting vs Physical Conservation -->
            <div class="p-4 bg-neutral-100 rounded-lg border border-neutral-200 text-xs text-neutral-600 leading-relaxed">
                <strong class="text-neutral-800">Catatan Kaidah Audit Energi:</strong> Tindakan pergeseran beban WBP ke jam LWBP (Rekomendasi No-Cost) merupakan optimasi disparitas tarif penagihan PLN dan tidak dihitung sebagai konservasi energi fisik kWh maupun reduksi emisi karbon. Reduksi emisi karbon murni diturunkan dari eliminasi pemborosan aktif kWh (idle load &amp; spike).
            </div>

        </div>

        <!-- ===================================================================== -->
        <!-- VIEW 5: TAB KEPATUHAN & AUDIT -->
        <!-- ===================================================================== -->
        <div id="compliance-content" class="tab-content hidden space-y-6">

            <!-- Regulatory Threshold Assessment Box -->
            <div class="bg-white p-5 rounded-lg border border-neutral-200 shadow-xs space-y-4">
                <div class="flex flex-col md:flex-row md:items-center justify-between gap-4">
                    <div>
                        <h2 class="text-base font-semibold text-neutral-900">
                            Evaluasi Ambang Batas Wajib Manajemen Energi (PP 33/2023 &amp; Permen ESDM 8/2025)
                        </h2>
                        <p class="text-xs text-neutral-600 mt-1 max-w-4xl leading-relaxed">
                            Berdasarkan Pasal 4 PP No. 33 Tahun 2023, pengguna energi dengan total konsumsi ≥ 4.000 TOE per tahun pada seluruh bauran energi (listrik, termal, bahan bakar) diwajibkan menerapkan sistem manajemen energi terstandarisasi.
                        </p>
                    </div>

                    <span class="px-3 py-1 rounded text-xs font-mono font-semibold uppercase border {{ $isMandatory ? 'bg-amber-50 text-amber-900 border-amber-300' : 'bg-emerald-50 text-emerald-900 border-emerald-300' }}">
                        {{ $isMandatory ? 'WAJIB MANAJEMEN ENERGI' : 'SUKARELA (< 4.000 TOE)' }}
                    </span>
                </div>

                <!-- Visual Threshold Gauge -->
                <div class="bg-neutral-50 p-4 rounded-md border border-neutral-200 space-y-2">
                    <div class="flex justify-between text-xs text-neutral-600 font-mono">
                        <span>Konsumsi Fasilitas Saat Ini: <strong class="text-neutral-900">{{ number_format($payload['kepatuhan_manajemen_energi']['total_konsumsi_energi_tahunan_toe'], 2, ',', '.') }} TOE/th</strong></span>
                        <span>Ambang Batas Wajib Industri: <strong class="text-neutral-900">4.000,00 TOE/th</strong></span>
                    </div>

                    @php
                        $progressPct = min(100, max(1, ($payload['kepatuhan_manajemen_energi']['total_konsumsi_energi_tahunan_toe'] / 4000.0) * 100));
                    @endphp
                    <div class="w-full bg-neutral-200 rounded-full h-3 overflow-hidden">
                        <div class="h-3 rounded-full transition-all duration-500 {{ $isMandatory ? 'bg-amber-600' : 'bg-brand-800' }}" style="width: {{ $progressPct }}%"></div>
                    </div>

                    <div class="flex justify-between text-[11px] text-neutral-500">
                        <span>0 TOE</span>
                        <span>2.000 TOE</span>
                        <span class="font-bold text-neutral-700">4.000 TOE (Ambang Mandat Regulasi)</span>
                        <span>6.000 TOE</span>
                    </div>
                </div>

                <!-- Energy Conversion Breakdown Cards (TOE & SBM Standar Ditjen EBTKE ESDM - FR-06) -->
                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-3 text-xs">
                    <div class="p-3 bg-neutral-50 rounded-md border border-neutral-200">
                        <span class="text-neutral-500 block text-[11px]">Listrik Terpantau:</span>
                        <strong class="text-neutral-900 font-mono text-sm block mt-0.5">{{ number_format($payload['kepatuhan_manajemen_energi']['total_listrik_bulanan_kwh'], 0, ',', '.') }} kWh</strong>
                        <span class="text-[10px] text-neutral-500 font-mono">({{ number_format($payload['kepatuhan_manajemen_energi']['total_listrik_bulanan_mwh'], 2, ',', '.') }} MWh/bulan)</span>
                    </div>
                    <div class="p-3 bg-neutral-50 rounded-md border border-neutral-200">
                        <span class="text-neutral-500 block text-[11px]">Konversi Listrik ke TOE:</span>
                        <strong class="text-neutral-900 font-mono text-sm block mt-0.5">{{ number_format($payload['kepatuhan_manajemen_energi']['proyeksi_toe_tahunan_listrik'], 2, ',', '.') }} TOE/th</strong>
                        <span class="text-[10px] text-neutral-500 font-mono">Faktor: 0,086 TOE/MWh</span>
                    </div>
                    <div class="p-3 bg-neutral-50 rounded-md border border-neutral-200">
                        <span class="text-neutral-500 block text-[11px]">Konversi Listrik ke SBM (EBTKE):</span>
                        <strong class="text-brand-900 font-mono text-sm block mt-0.5">{{ number_format($payload['kepatuhan_manajemen_energi']['proyeksi_sbm_tahunan_listrik'] ?? 0, 2, ',', '.') }} SBM/th</strong>
                        <span class="text-[10px] text-brand-800 font-mono">Faktor: 0,613 SBM/MWh</span>
                    </div>
                    <div class="p-3 bg-neutral-50 rounded-md border border-neutral-200">
                        <span class="text-neutral-500 block text-[11px]">Konsumsi Termal Non-Listrik:</span>
                        <strong class="text-neutral-900 font-mono text-sm block mt-0.5">{{ number_format($payload['kepatuhan_manajemen_energi']['input_energi_non_listrik_toe_tahunan'], 2, ',', '.') }} TOE/th</strong>
                        <span class="text-[10px] text-neutral-500 font-mono">Biomassa, Batu Bara, Genset</span>
                    </div>
                </div>

                <!-- Dynamic Non-Electric Thermal TOE Simulator Form (FR-06) -->
                <div class="bg-white p-4 rounded-md border border-neutral-200 space-y-3">
                    <div class="flex items-center justify-between">
                        <div>
                            <h3 class="text-xs font-semibold text-neutral-900">
                                Simulasi Konsumsi Energi Termal / Non-Listrik (Modul FR-06)
                            </h3>
                            <p class="text-[11px] text-neutral-500">
                                Uji ambang batas jika pabrik menambah boiler biomassa bakar langsung, solar genset, atau gasifikasi
                            </p>
                        </div>
                    </div>

                    <form action="{{ route('dashboard') }}" method="GET" class="flex flex-wrap items-center gap-3 text-xs">
                        <input type="hidden" name="preset" value="{{ $preset }}">
                        <input type="hidden" name="tab" value="compliance">
                        <label for="comp_non_electric_toe" class="text-neutral-600">Input Energi Termal Non-Listrik:</label>
                        <div class="flex items-center gap-1.5">
                            <input type="number" step="10" name="non_electric_toe" id="comp_non_electric_toe" 
                                   value="{{ $nonElectricToe }}" 
                                   class="w-28 px-3 py-1.5 bg-white border border-neutral-300 rounded text-xs font-mono text-neutral-900 text-right focus:outline-none focus:border-brand-800"
                                   placeholder="0.0">
                            <span class="text-neutral-500 font-mono">TOE/tahun</span>
                        </div>
                        <button type="submit" class="px-3.5 py-1.5 bg-brand-800 hover:bg-brand-900 text-white rounded text-xs font-medium transition shadow-xs">
                            Hitung Ulang Status Regulasi
                        </button>
                    </form>
                </div>

                <!-- Legal Implications Table -->
                <div class="p-4 rounded-md border border-neutral-200 bg-neutral-50 text-xs text-neutral-700 space-y-1.5">
                    <strong class="text-neutral-900 block font-semibold">Implikasi Yuridis &amp; Kewajiban Kepatuhan:</strong>
                    <p class="leading-relaxed">{{ $payload['kepatuhan_manajemen_energi']['implikasi_hukum'] }}</p>
                </div>
            </div>

            <!-- Building IKE Standards (Permen ESDM 3/2025 & SNI 6196) -->
            <div class="bg-white p-5 rounded-lg border border-neutral-200 shadow-xs space-y-4">
                <h3 class="text-sm font-semibold text-neutral-900">
                    Standar IKE Bangunan Gedung Pendukung (Permen ESDM No. 3 Tahun 2025 Lampiran I)
                </h3>
                
                <div class="overflow-x-auto border border-neutral-200 rounded-md">
                    <table class="w-full text-xs text-left">
                        <thead class="bg-neutral-50 text-neutral-600 font-semibold border-b border-neutral-200 text-[11px]">
                            <tr>
                                <th class="p-3">Tipologi Bangunan Fasilitas</th>
                                <th class="p-3 text-center">Sangat Efisien</th>
                                <th class="p-3 text-center">Efisien (Standar)</th>
                                <th class="p-3 text-center">Cukup Efisien</th>
                                <th class="p-3 text-center">Agak Boros</th>
                                <th class="p-3 text-center">Boros</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-neutral-200 font-mono text-xs">
                            <tr class="hover:bg-neutral-50">
                                <td class="p-3 font-sans font-medium text-neutral-900">Gedung Kantor / Lab AC (&le; 5.000 m²)</td>
                                <td class="p-3 text-center text-emerald-800">&lt; 8,50</td>
                                <td class="p-3 text-center font-bold text-emerald-800 bg-emerald-50/50">8,50 – &lt; 14,00</td>
                                <td class="p-3 text-center text-neutral-700">14,00 – &lt; 18,50</td>
                                <td class="p-3 text-center text-amber-800">18,50 – &lt; 23,00</td>
                                <td class="p-3 text-center text-rose-800">&ge; 23,00</td>
                            </tr>
                            <tr class="hover:bg-neutral-50">
                                <td class="p-3 font-sans font-medium text-neutral-900">Gudang Penyimpanan Non-AC (kWh/m²/bln)</td>
                                <td class="p-3 text-center text-emerald-800">&lt; 2,00</td>
                                <td class="p-3 text-center font-bold text-emerald-800 bg-emerald-50/50">2,00 – &le; 3,40</td>
                                <td class="p-3 text-center text-neutral-700">3,41 – 5,60</td>
                                <td class="p-3 text-center text-amber-800">5,61 – 7,40</td>
                                <td class="p-3 text-center text-rose-800">&gt; 7,40</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Direct Export Card (FR-08 & F-06) -->
            <div class="bg-white p-5 rounded-lg border border-neutral-200 shadow-xs flex flex-col md:flex-row md:items-center justify-between gap-4">
                <div>
                    <h3 class="text-sm font-semibold text-neutral-900">
                        Ekspor Lembar Kerja Audit Energi Resmi ESDM (FR-08)
                    </h3>
                    <p class="text-xs text-neutral-500 mt-0.5">
                        Dokumen lembar kerja komprehensif terformat siap cetak / unduh PDF dan spreadsheet Excel untuk pelaporan audit berkala.
                    </p>
                </div>
                <div class="flex flex-wrap items-center gap-2 shrink-0">
                    <a href="{{ route('audit.export', ['preset' => $preset, 'non_electric_toe' => $nonElectricToe, 'format' => 'pdf']) }}" target="_blank"
                       class="px-3.5 py-2 bg-rose-700 hover:bg-rose-800 text-white rounded-md text-xs font-semibold flex items-center gap-1.5 transition shadow-xs">
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                        <span>Unduh PDF</span>
                    </a>
                    <a href="{{ route('audit.export', ['preset' => $preset, 'non_electric_toe' => $nonElectricToe, 'format' => 'excel']) }}" target="_blank"
                       class="px-3.5 py-2 bg-emerald-700 hover:bg-emerald-800 text-white rounded-md text-xs font-semibold flex items-center gap-1.5 transition shadow-xs">
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                        <span>Unduh Excel / CSV</span>
                    </a>
                    <a href="{{ route('audit.export', ['preset' => $preset, 'non_electric_toe' => $nonElectricToe]) }}" target="_blank"
                       class="px-3.5 py-2 bg-brand-800 hover:bg-brand-900 text-white rounded-md text-xs font-semibold flex items-center gap-1.5 transition shadow-xs">
                        <span>Buka Lembar Kerja (HTML)</span>
                    </a>
                </div>
            </div>

        </div>

        <!-- ===================================================================== -->
        <!-- VIEW 6: DEDICATED AI COPILOT WORKSPACE (FR-04) -->
        <!-- ===================================================================== -->
        <div id="copilot-content" class="tab-content hidden space-y-6">

            <!-- 1. Dedicated Workspace Header & Context Bar -->
            <div class="flex flex-col lg:flex-row lg:items-center justify-between gap-4 pb-4 border-b border-neutral-200">
                <div>
                    <div class="flex items-center gap-2.5">
                        <span class="p-1.5 rounded-md bg-brand-800 text-white shadow-xs">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.663 17h4.673M12 3v1m6.364 1.636l-.707.707M21 12h-1M4 12H3m3.343-5.657l-.707-.707m2.828 9.9a5 5 0 117.072 0l-.548.547A3.374 3.374 0 0014 18.469V19a2 2 0 11-4 0v-.531c0-.895-.356-1.754-.988-2.386l-.548-.547z"/></svg>
                        </span>
                        <h1 class="text-xl font-bold tracking-tight text-neutral-900">AI Energy Efficiency Copilot Workspace</h1>
                    </div>
                    <p class="text-xs text-neutral-500 mt-1">
                        Workspace konsultasi teknis diagnostik energi ISO 50001, audit berkala ESDM, dan preskripsi efisiensi operasional terintegrasi.
                    </p>
                </div>

                <!-- Context Badges & Actions -->
                <div class="flex flex-wrap items-center gap-2">
                    <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-md text-xs font-mono font-semibold {{ $isMandatory ? 'bg-amber-50 text-amber-800 border border-amber-200' : 'bg-emerald-50 text-emerald-800 border border-emerald-200' }}">
                        <span class="w-1.5 h-1.5 rounded-full {{ $isMandatory ? 'bg-amber-600' : 'bg-brand-800' }}"></span>
                        <span>{{ $isMandatory ? 'STATUS: WAJIB (PP 33/2023)' : 'STATUS: SUKARELA (<4.000 TOE)' }}</span>
                    </span>
                    <div class="flex items-center gap-1.5">
                        <a href="{{ route('audit.export', ['preset' => $preset, 'non_electric_toe' => $nonElectricToe, 'format' => 'pdf']) }}" target="_blank"
                           class="px-2.5 py-1 bg-white hover:bg-neutral-50 text-rose-700 border border-neutral-200 rounded-md text-xs font-semibold flex items-center gap-1 transition shadow-xs" title="Unduh format PDF">
                            <span>PDF</span>
                        </a>
                        <a href="{{ route('audit.export', ['preset' => $preset, 'non_electric_toe' => $nonElectricToe, 'format' => 'excel']) }}" target="_blank"
                           class="px-2.5 py-1 bg-white hover:bg-neutral-50 text-emerald-700 border border-neutral-200 rounded-md text-xs font-semibold flex items-center gap-1 transition shadow-xs" title="Unduh format Excel/CSV">
                            <span>Excel</span>
                        </a>
                        <a href="{{ route('audit.export', ['preset' => $preset, 'non_electric_toe' => $nonElectricToe]) }}" target="_blank"
                           class="px-2.5 py-1 bg-white hover:bg-neutral-50 text-neutral-700 border border-neutral-200 rounded-md text-xs font-medium flex items-center gap-1.5 transition shadow-xs">
                            <svg class="w-3.5 h-3.5 text-neutral-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                            <span>Lembar Kerja ESDM</span>
                        </a>
                    </div>
                </div>
            </div>

            <!-- 2. Main Multi-Column Grid (7 cols Left / 5 cols Right on desktop) -->
            <div class="grid grid-cols-1 lg:grid-cols-12 gap-6 items-start">

                <!-- LEFT COLUMN: Structured Audit, Findings, Diagnostics & Recommendations (7 cols) -->
                <div class="lg:col-span-7 space-y-6">

                    <!-- Section 1: Executive Audit Summary & Legal Compliance -->
                    <div class="bg-white p-5 rounded-lg border border-neutral-200 shadow-xs space-y-4">
                        <div class="flex items-center justify-between pb-3 border-b border-neutral-100">
                            <h2 class="text-xs font-semibold uppercase tracking-wider text-neutral-900 flex items-center gap-2">
                                <span class="w-2 h-2 rounded-full bg-brand-800"></span>
                                <span>1. Ringkasan Eksekutif &amp; Kepatuhan Regulasi ESDM</span>
                            </h2>
                            <span class="text-[11px] text-neutral-400 font-mono">PP No. 33/2023 &amp; Permen ESDM 8/2025</span>
                        </div>

                        <!-- 4 Highlight Cards in 2x2 Grid -->
                        <div class="grid grid-cols-2 sm:grid-cols-4 gap-3">
                            <div class="p-3 bg-neutral-50 rounded-lg border border-neutral-200/80">
                                <span class="text-[10px] text-neutral-500 block uppercase font-medium">Konsumsi Bulanan</span>
                                <span class="text-sm font-bold font-mono text-neutral-900">{{ number_format($payload['kepatuhan_manajemen_energi']['total_listrik_bulanan_kwh'], 0, ',', '.') }}</span>
                                <span class="text-[10px] text-neutral-500 block">kWh ({{ number_format($payload['kepatuhan_manajemen_energi']['konsumsi_toe_bulanan'], 2, ',', '.') }} TOE)</span>
                            </div>
                            <div class="p-3 bg-neutral-50 rounded-lg border border-neutral-200/80">
                                <span class="text-[10px] text-neutral-500 block uppercase font-medium">Proyeksi Tahunan</span>
                                <span class="text-sm font-bold font-mono text-neutral-900">{{ number_format($payload['kepatuhan_manajemen_energi']['total_konsumsi_energi_tahunan_toe'], 2, ',', '.') }}</span>
                                <span class="text-[10px] text-neutral-500 block">TOE / tahun</span>
                            </div>
                            <div class="p-3 bg-emerald-50/60 rounded-lg border border-emerald-200/80">
                                <span class="text-[10px] text-emerald-800 block uppercase font-medium">Potensi Hemat</span>
                                <span class="text-sm font-bold font-mono text-emerald-700">Rp {{ number_format($payload['temuan_pemborosan_dan_finansial']['total_potensi_hemat_rp'], 0, ',', '.') }}</span>
                                <span class="text-[10px] text-emerald-700 block">per bulan</span>
                            </div>
                            <div class="p-3 bg-sky-50/60 rounded-lg border border-sky-200/80">
                                <span class="text-[10px] text-sky-800 block uppercase font-medium">Reduksi CO₂e</span>
                                <span class="text-sm font-bold font-mono text-sky-700">-{{ number_format($payload['temuan_pemborosan_dan_finansial']['potensi_reduksi_co2_ton'], 2, ',', '.') }}</span>
                                <span class="text-[10px] text-sky-700 block">ton CO₂e / bln</span>
                            </div>
                        </div>

                        <!-- Legal Obligation Insight Box -->
                        <div class="p-3.5 bg-neutral-50/80 rounded-lg border border-neutral-200 text-xs leading-relaxed text-neutral-700 space-y-1">
                            <div class="flex items-center gap-1.5 font-semibold text-neutral-900 text-[11px]">
                                <svg class="w-3.5 h-3.5 text-brand-800" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                                <span>Status Kewajiban Yuridis:</span>
                            </div>
                            <p class="text-neutral-600 text-[11px] leading-relaxed">
                                {{ $payload['kepatuhan_manajemen_energi']['implikasi_hukum'] }}
                            </p>
                        </div>
                    </div>

                    <!-- Section 2: Panel-Specific Diagnostics (2x2 Grid) -->
                    <div class="bg-white p-5 rounded-lg border border-neutral-200 shadow-xs space-y-4">
                        <div class="flex items-center justify-between pb-3 border-b border-neutral-100">
                            <h2 class="text-xs font-semibold uppercase tracking-wider text-neutral-900 flex items-center gap-2">
                                <span class="w-2 h-2 rounded-full bg-amber-600"></span>
                                <span>2. Diagnostik Kinerja Panel Distribusi (SDP Diagnostics)</span>
                            </h2>
                            <span class="text-[11px] text-neutral-400 font-mono">SNI ISO 50001:2018</span>
                        </div>

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-3.5">
                            <!-- SDP-01 -->
                            <div class="p-3.5 rounded-lg border border-rose-200 bg-rose-50/30 space-y-2">
                                <div class="flex items-center justify-between">
                                    <span class="font-bold text-xs text-neutral-900">SDP-01: Line Pirolisis</span>
                                    <span class="px-1.5 py-0.5 rounded text-[10px] font-semibold bg-rose-100 text-rose-800 border border-rose-200">BOROS (+48.57%)</span>
                                </div>
                                <p class="text-[11px] text-neutral-600 leading-normal">
                                    IKE aktual <strong class="font-mono text-neutral-900">0,52 kWh/batch</strong> vs baseline <span class="font-mono">0,35</span>. Base Load <strong class="text-rose-700 font-mono">E₀ = 22,4 kW</strong> terdeteksi aktif saat istirahat (idle leakage 2.450 kWh). Power factor rendah (<strong class="font-mono text-rose-700">cos φ = 0,81</strong>).
                                </p>
                                <div class="pt-1.5 border-t border-rose-100 flex items-center justify-between text-[10px] text-rose-800 font-medium">
                                    <span>Solusi: Standby Timer &amp; APFC</span>
                                    <button onclick="sendQuickPromptFromPage('Bagaimana langkah menurunkan Base Load E0 pada SDP-01 Line Pirolisis?')" class="text-brand-800 hover:underline">Tanya AI &rarr;</button>
                                </div>
                            </div>

                            <!-- SDP-02 -->
                            <div class="p-3.5 rounded-lg border border-amber-200 bg-amber-50/30 space-y-2">
                                <div class="flex items-center justify-between">
                                    <span class="font-bold text-xs text-neutral-900">SDP-02: Sistem Utilitas</span>
                                    <span class="px-1.5 py-0.5 rounded text-[10px] font-semibold bg-amber-100 text-amber-800 border border-amber-200">WASPADA (+11.43%)</span>
                                </div>
                                <p class="text-[11px] text-neutral-600 leading-normal">
                                    IKE aktual <strong class="font-mono text-neutral-900">0,39 kWh/batch</strong>. Ditemukan 1.200 kWh beban rotary dryer beroperasi di jam WBP PLN (18:00–22:00) yang terkena pengali tarif puncak <strong class="font-mono text-amber-800">K = 1,40</strong>.
                                </p>
                                <div class="pt-1.5 border-t border-amber-100 flex items-center justify-between text-[10px] text-amber-800 font-medium">
                                    <span>Solusi: WBP Load Shifting</span>
                                    <button onclick="sendQuickPromptFromPage('Hitung potensi penghematan geser beban rotary dryer SDP-02 ke LWBP')" class="text-brand-800 hover:underline">Tanya AI &rarr;</button>
                                </div>
                            </div>

                            <!-- SDP-03 -->
                            <div class="p-3.5 rounded-lg border border-emerald-200 bg-emerald-50/20 space-y-2">
                                <div class="flex items-center justify-between">
                                    <span class="font-bold text-xs text-neutral-900">SDP-03: Gedung Utama</span>
                                    <span class="px-1.5 py-0.5 rounded text-[10px] font-semibold bg-emerald-100 text-emerald-800 border border-emerald-200">EFISIEN (SNI 6196)</span>
                                </div>
                                <p class="text-[11px] text-neutral-600 leading-normal">
                                    IKE tercatat <strong class="font-mono text-neutral-900">10,33 kWh/m²/bulan</strong>, di bawah standar intensitas bangunan gedung hemat energi ESDM (15 kWh/m²). Pencahayaan LED &amp; HVAC inverter bekerja optimal.
                                </p>
                                <div class="pt-1.5 border-t border-emerald-100 flex items-center justify-between text-[10px] text-emerald-800 font-medium">
                                    <span>Kondisi: Memenuhi Target SNI</span>
                                    <span class="text-emerald-700 font-mono">Normal</span>
                                </div>
                            </div>

                            <!-- SDP-04 -->
                            <div class="p-3.5 rounded-lg border border-emerald-200 bg-emerald-50/20 space-y-2">
                                <div class="flex items-center justify-between">
                                    <span class="font-bold text-xs text-neutral-900">SDP-04: Gudang Feedstock</span>
                                    <span class="px-1.5 py-0.5 rounded text-[10px] font-semibold bg-emerald-100 text-emerald-800 border border-emerald-200">EFISIEN</span>
                                </div>
                                <p class="text-[11px] text-neutral-600 leading-normal">
                                    IKE tercatat <strong class="font-mono text-neutral-900">2,33 kWh/m²/bulan</strong>. Beban intermiten motor konveyor bongkar bahan baku biomassa beroperasi sesuai jadwal kedatangan logistik.
                                </p>
                                <div class="pt-1.5 border-t border-emerald-100 flex items-center justify-between text-[10px] text-emerald-800 font-medium">
                                    <span>Kondisi: Sesuai Siklus Muat</span>
                                    <span class="text-emerald-700 font-mono">Normal</span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Section 3: Prescriptive Recommendations by Investment Category (Permen ESDM 8/2025) -->
                    <div class="bg-white p-5 rounded-lg border border-neutral-200 shadow-xs space-y-4">
                        <div class="flex items-center justify-between pb-3 border-b border-neutral-100">
                            <h2 class="text-xs font-semibold uppercase tracking-wider text-neutral-900 flex items-center gap-2">
                                <span class="w-2 h-2 rounded-full bg-emerald-600"></span>
                                <span>3. Matriks Rekomendasi Preskriptif (Permen ESDM 8/2025)</span>
                            </h2>
                            <span class="text-[11px] text-neutral-400 font-mono">4 Kategori Investasi</span>
                        </div>

                        <div class="space-y-3">
                            @foreach ($payload['klasifikasi_rekomendasi_audit_esdm'] as $rek)
                            @php
                                $badgeStyle = match ($rek['kategori_investasi']) {
                                    'No-Cost' => 'bg-emerald-50 border-emerald-200 text-emerald-800',
                                    'Low-Cost' => 'bg-sky-50 border-sky-200 text-sky-800',
                                    'Medium-Cost' => 'bg-amber-50 border-amber-200 text-amber-800',
                                    'High-Cost' => 'bg-purple-50 border-purple-200 text-purple-800',
                                    default => 'bg-neutral-50 border-neutral-200 text-neutral-800',
                                };
                            @endphp
                            <div class="p-3.5 rounded-lg border border-neutral-200 bg-white hover:border-neutral-300 transition shadow-2xs space-y-2">
                                <div class="flex flex-wrap items-center justify-between gap-2">
                                    <span class="px-2 py-0.5 rounded text-[10px] font-semibold border {{ $badgeStyle }}">
                                        {{ $rek['kategori_investasi'] }} • Payback: {{ $rek['kriteria_payback'] }}
                                    </span>
                                    <div class="flex items-center gap-3 text-xs font-mono">
                                        <span class="text-emerald-700 font-bold">Hemat: Rp {{ number_format($rek['potensi_hemat_rp_per_bulan'], 0, ',', '.') }}/bln</span>
                                        @if($rek['estimasi_biaya_implementasi_rp'] > 0)
                                        <span class="text-neutral-500">Biaya: Rp {{ number_format($rek['estimasi_biaya_implementasi_rp'], 0, ',', '.') }}</span>
                                        @else
                                        <span class="text-emerald-600 font-semibold">Biaya: Rp 0</span>
                                        @endif
                                    </div>
                                </div>

                                <div>
                                    <h3 class="text-xs font-bold text-neutral-900">{{ $rek['judul_tindakan'] }}</h3>
                                    <p class="text-[11px] text-neutral-600 mt-1 leading-relaxed">{{ $rek['deskripsi_teknis'] }}</p>
                                </div>

                                <div class="flex items-center justify-between pt-2 border-t border-neutral-100 text-[10px] text-neutral-500">
                                    <span class="font-mono">Lokasi: <strong class="text-neutral-700">{{ $rek['lokasi_panel'] }}</strong></span>
                                    @if($rek['potensi_reduksi_co2_ton'] > 0)
                                    <span class="text-sky-700 font-mono">Reduksi Emisi: -{{ $rek['potensi_reduksi_co2_ton'] }} tCO₂e/bln</span>
                                    @endif
                                    <button onclick="sendQuickPromptFromPage('Jelaskan detail eksekusi rekomendasi: {{ addslashes($rek['judul_tindakan']) }}')" 
                                            class="text-brand-800 hover:text-brand-900 font-medium">
                                        Konsultasikan &rarr;
                                    </button>
                                </div>
                            </div>
                            @endforeach
                        </div>
                    </div>

                </div>

                <!-- RIGHT COLUMN: Dedicated Interactive AI Copilot Chat Console (5 cols) -->
                <div class="lg:col-span-5 sticky top-4 space-y-4">

                    <div class="bg-white rounded-lg border border-neutral-200 shadow-sm flex flex-col h-[780px] overflow-hidden">
                        
                        <!-- Chat Console Header -->
                        <div class="p-4 border-b border-neutral-200 bg-neutral-50/70 flex items-center justify-between shrink-0">
                            <div class="flex items-center gap-2.5">
                                <img src="{{ asset('images/indmira-logo.jpeg') }}" alt="PT Indmira Logo" 
                                     class="w-8 h-8 rounded-md object-contain bg-white border border-neutral-200/90 p-0.5 shrink-0 shadow-xs">
                                <div>
                                    <h2 class="text-xs font-bold text-neutral-900">IEEC Advisory Assistant</h2>
                                </div>
                            </div>

                            <button onclick="clearCopilotConversation()" 
                                    class="px-2 py-1 text-[11px] text-neutral-500 hover:text-neutral-800 rounded border border-neutral-200 hover:bg-neutral-100 transition"
                                    title="Bersihkan riwayat percakapan">
                                Reset Sesi
                            </button>
                        </div>

                        <!-- Chat History & Live Messages Area -->
                        <div id="copilotPageMessages" class="flex-1 overflow-y-auto p-4 space-y-3.5 text-xs leading-relaxed">
                            <!-- Initial Welcome Message from Copilot -->
                            <div class="p-3.5 bg-neutral-50 border border-neutral-200 rounded-lg text-neutral-800 space-y-2 shadow-2xs">
                                <div class="flex items-center justify-between text-[10px] text-neutral-400 border-b border-neutral-200 pb-1">
                                    <span class="font-semibold text-brand-800 flex items-center gap-1.5">
                                        <img src="{{ asset('images/indmira-logo.jpeg') }}" alt="PT Indmira Logo" class="w-4 h-4 rounded-xs object-contain bg-white border border-neutral-200 p-0.5 shrink-0">
                                        <span>Asisten Teknis IEEC</span>
                                    </span>
                                    <span class="font-mono text-neutral-500">Sesi Aktif</span>
                                </div>
                                <p class="text-neutral-700 leading-relaxed text-xs">
                                    Selamat datang di workspace khusus **AI Energy Efficiency Copilot**. Saya telah menganalisis data telemetri 30 hari untuk **PT Indmira Global Energi**:
                                </p>
                                <ul class="list-disc list-inside space-y-1 text-neutral-600 text-[11px] pl-1">
                                    <li>Terdeteksi kebocoran idle load <strong class="font-mono text-neutral-900">2.450 kWh</strong> di SDP-01 (Line Pirolisis).</li>
                                    <li>Beban puncak jam WBP 18:00–22:00 di SDP-02 sebesar <strong class="font-mono text-neutral-900">1.200 kWh</strong> terkena faktor K=1,40.</li>
                                    <li>Faktor daya rata-rata <strong class="font-mono text-rose-700">cos φ = 0,81</strong> memicu penalti kVArh <strong class="font-mono text-neutral-900">Rp 845.000/bln</strong>.</li>
                                </ul>
                                <p class="text-[11px] text-neutral-500 italic pt-1">
                                    Silakan ajukan pertanyaan teknis, kalkulasi APFC, evaluasi regulasi PP 33/2023, atau pilih topik di bawah ini.
                                </p>
                            </div>
                        </div>

                        <!-- Quick Query Pills Bar -->
                        <div class="p-2.5 bg-neutral-50/60 border-t border-neutral-200 flex flex-wrap gap-1.5 text-[10px] shrink-0">
                            <button onclick="sendQuickPromptFromPage('Bagaimana cara menurunkan Base Load E0 pada Line A?')" 
                                    class="px-2 py-1 bg-white hover:bg-neutral-100 text-neutral-700 rounded border border-neutral-200 transition">
                                Turunkan E₀ 22,4 kW
                            </button>
                            <button onclick="sendQuickPromptFromPage('Hitung spesifikasi kapasitas kapasitor bank APFC untuk cos phi 0,81 ke 0,95')" 
                                    class="px-2 py-1 bg-white hover:bg-neutral-100 text-neutral-700 rounded border border-neutral-200 transition">
                                Spesifikasi APFC 125 kVAR
                            </button>
                            <button onclick="sendQuickPromptFromPage('Bagaimana SOP penjadwalan load shifting rotary dryer jam WBP 18-22?')" 
                                    class="px-2 py-1 bg-white hover:bg-neutral-100 text-neutral-700 rounded border border-neutral-200 transition">
                                SOP WBP Shifting
                            </button>
                            <button onclick="sendQuickPromptFromPage('Apa saja kewajiban kepatuhan fasilitas jika konsumsi mencapai ambang 4.000 TOE PP 33/2023?')" 
                                    class="px-2 py-1 bg-white hover:bg-neutral-100 text-neutral-700 rounded border border-neutral-200 transition">
                                Mandat PP 33/2023
                            </button>
                        </div>

                        <!-- Persistent Chat Composer Form -->
                        <form id="copilotPageChatForm" onsubmit="handleCopilotPageSubmit(event)" class="p-3 bg-white border-t border-neutral-200 flex gap-2 shrink-0">
                            <input type="text" id="copilotPageInput" 
                                   placeholder="Tanyakan analisis teknis, regulasi ESDM, atau kalkulasi..." 
                                   class="flex-1 bg-neutral-50 border border-neutral-300 rounded-md px-3 py-2 text-xs text-neutral-900 placeholder-neutral-400 focus:outline-none focus:border-brand-800"
                                   autocomplete="off">
                            <button type="submit" id="copilotPageSubmitBtn" 
                                    class="px-4 py-2 bg-brand-800 hover:bg-brand-900 text-white rounded-md text-xs font-semibold flex items-center gap-1.5 transition shadow-xs">
                                <span>Kirim</span>
                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3"/></svg>
                            </button>
                        </form>

                    </div>

                </div>

            </div>

        </div>

        <!-- ===================================================================== -->
        <!-- VIEW 7: TAB DATA & IMPORT (FR-01, FR-05) -->
        <!-- ===================================================================== -->
        <div id="data-import-content" class="tab-content hidden space-y-6">

            <!-- Title & Subtitle Header -->
            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 pb-3 border-b border-neutral-200">
                <div>
                    <h1 class="text-xl font-bold tracking-tight text-neutral-900">Data Telemetri &amp; Ingesti CSV</h1>
                    <p class="text-xs text-neutral-500 mt-0.5">Manajemen log telemetri energi historis CSV 9-kolom standar IEEC, dataset operasional, dan simulasi IoT stream.</p>
                </div>
                <span class="px-2.5 py-1 rounded text-xs font-mono font-medium bg-neutral-100 text-neutral-700 border border-neutral-200">
                    Modul: FR-01 &amp; FR-05
                </span>
            </div>

            <!-- Operating Mode & Dataset Preset Switcher Card -->
            <div class="bg-white p-5 rounded-lg border border-neutral-200 shadow-xs space-y-4">
                <div class="flex items-center justify-between">
                    <div>
                        <h2 class="text-sm font-semibold text-neutral-900">Mode Operasional &amp; Pemilihan Dataset Aktif</h2>
                        <p class="text-xs text-neutral-500 mt-0.5">Pilih skenario beban fasilitas industri untuk evaluasi ambang batas kepatuhan PP 33/2023</p>
                    </div>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-3 gap-4 text-xs">
                    <a href="{{ route('dashboard', ['preset' => 'sukarela']) }}" 
                       class="p-4 rounded-lg border transition block {{ $preset === 'sukarela' ? 'border-brand-800 bg-emerald-50/50 shadow-xs' : 'border-neutral-200 bg-white hover:bg-neutral-50' }}">
                        <div class="flex items-center justify-between mb-2">
                            <span class="font-bold text-neutral-900">Pilot Plant (&lt; 4.000 TOE)</span>
                            <span class="px-2 py-0.5 rounded text-[10px] font-mono font-semibold bg-emerald-100 text-emerald-800">SUKARELA</span>
                        </div>
                        <p class="text-neutral-600 leading-relaxed text-[11px]">
                            Konsumsi 48.250 kWh/bulan (~50,49 TOE/tahun). Berada di bawah ambang batas wajib PP 33/2023. Efisiensi bersifat sukarela namun strategis untuk OPEX.
                        </p>
                    </a>

                    <a href="{{ route('dashboard', ['preset' => 'wajib']) }}" 
                       class="p-4 rounded-lg border transition block {{ $preset === 'wajib' ? 'border-amber-700 bg-amber-50/50 shadow-xs' : 'border-neutral-200 bg-white hover:bg-neutral-50' }}">
                        <div class="flex items-center justify-between mb-2">
                            <span class="font-bold text-neutral-900">Skala Industri (≥ 4.000 TOE)</span>
                            <span class="px-2 py-0.5 rounded text-[10px] font-mono font-semibold bg-amber-100 text-amber-800">WAJIB</span>
                        </div>
                        <p class="text-neutral-600 leading-relaxed text-[11px]">
                            Konsumsi &gt; 4.000 TOE/tahun. Terkena mandat yuridis PP 33/2023 &amp; Permen ESDM 8/2025: wajib manajer energi tersertifikasi dan audit berkala.
                        </p>
                    </a>

                    <div class="p-4 rounded-lg border {{ $preset === 'custom_upload' ? 'border-sky-600 bg-sky-50/50 shadow-xs' : 'border-neutral-200 bg-neutral-50/60' }}">
                        <div class="flex items-center justify-between mb-2">
                            <span class="font-bold text-neutral-900">Custom Dataset CSV</span>
                            <span class="px-2 py-0.5 rounded text-[10px] font-mono font-semibold {{ $preset === 'custom_upload' ? 'bg-sky-100 text-sky-800' : 'bg-neutral-200 text-neutral-600' }}">
                                {{ $preset === 'custom_upload' ? 'AKTIF' : 'TERSEDIA' }}
                            </span>
                        </div>
                        <p class="text-neutral-600 leading-relaxed text-[11px]">
                            Data telemetri yang diunggah langsung melalui formulir validasi skema 9-kolom di bawah ini.
                        </p>
                    </div>
                </div>
            </div>

            <!-- Data Ingestion (FR-01) Section -->
            <div class="bg-white p-5 rounded-lg border border-neutral-200 shadow-xs space-y-4">
                <div class="flex items-center justify-between">
                    <div>
                        <h2 class="text-sm font-semibold text-neutral-900">
                            Ingesti &amp; Validasi Data Telemetri Historis CSV (FR-01)
                        </h2>
                        <p class="text-xs text-neutral-500 mt-0.5">
                            Unggah berkas log telemetri energi format CSV dengan skema 9-kolom standar IEEC
                        </p>
                    </div>
                </div>

                <form action="{{ route('csv.upload') }}" method="POST" enctype="multipart/form-data" class="space-y-4">
                    @csrf
                    <div class="border-2 border-dashed border-neutral-300 hover:border-brand-800 rounded-lg p-6 text-center cursor-pointer bg-neutral-50 transition">
                        <svg class="w-8 h-8 mx-auto text-neutral-400 mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12"/></svg>
                        <p class="text-xs text-neutral-700 font-medium">Pilih atau letakkan berkas CSV telemetri energi di sini</p>
                        <p class="text-[11px] text-neutral-500 mt-0.5">Maksimal 10 MB • Mendukung format UTF-8 CSV</p>
                        <input type="file" name="csv_file" accept=".csv" required class="mt-3 block mx-auto text-xs text-neutral-500 file:mr-4 file:py-1.5 file:px-3 file:rounded file:border-0 file:text-xs file:font-medium file:bg-brand-800 file:text-white hover:file:bg-brand-900 cursor-pointer">
                    </div>

                    <div class="p-3 bg-neutral-50 rounded-md border border-neutral-200 text-xs text-neutral-600 space-y-1">
                        <span class="font-medium text-neutral-800 block">Skema Kolom Wajib Standar IEEC:</span>
                        <code class="font-mono text-[11px] text-brand-800 block">timestamp, panel_id, category, energy_kwh, power_factor, kvarh, production_qty, area_sqm, shift_state</code>
                    </div>

                    <div class="flex justify-end">
                        <button type="submit" class="px-4 py-2 bg-brand-800 hover:bg-brand-900 text-white rounded-md text-xs font-semibold transition shadow-xs">
                            Proses &amp; Validasi Data CSV
                        </button>
                    </div>
                </form>
            </div>

            <!-- IoT Real-time Telemetry Simulator Control Card (FR-05) -->
            <div class="bg-white p-5 rounded-lg border border-neutral-200 shadow-xs space-y-4">
                <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 border-b border-neutral-200 pb-3">
                    <div>
                        <h2 class="text-sm font-semibold text-neutral-900">
                            Simulasi Data Telemetri Real-Time IoT (FR-05)
                        </h2>
                        <p class="text-xs text-neutral-500 mt-0.5">
                            Protokol simulasi Modbus/MQTT streaming daya aktif, reaktif, dan cos phi per 3 detik
                        </p>
                    </div>
                    <button onclick="toggleIotStreaming()" 
                            class="px-3 py-1.5 bg-neutral-100 hover:bg-neutral-200 text-neutral-800 rounded-md text-xs font-medium flex items-center gap-2 transition shrink-0">
                        <span class="w-2 h-2 rounded-full bg-neutral-400"></span>
                        <span>Toggle Real-Time Stream</span>
                    </button>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-4 gap-4 text-xs font-mono">
                    <div class="p-3 bg-neutral-50 rounded border border-neutral-200">
                        <span class="text-neutral-500 text-[11px] block font-sans">Status Koneksi:</span>
                        <strong class="text-neutral-900 text-sm">Modbus TCP / MQTT</strong>
                    </div>
                    <div class="p-3 bg-neutral-50 rounded border border-neutral-200">
                        <span class="text-neutral-500 text-[11px] block font-sans">Interval Sampling:</span>
                        <strong class="text-neutral-900 text-sm">3.000 ms (3 detik)</strong>
                    </div>
                    <div class="p-3 bg-neutral-50 rounded border border-neutral-200">
                        <span class="text-neutral-500 text-[11px] block font-sans">Jumlah Titik Ukur:</span>
                        <strong class="text-neutral-900 text-sm">4 Sub-Distribution Panels</strong>
                    </div>
                    <div class="p-3 bg-neutral-50 rounded border border-neutral-200">
                        <span class="text-neutral-500 text-[11px] block font-sans">WBP Rate Mode:</span>
                        <strong class="text-neutral-900 text-sm">Otomatis (18:00 - 22:00)</strong>
                    </div>
                </div>
            </div>

        </div>

        <!-- ===================================================================== -->
        <!-- VIEW 7: TAB KONFIGURASI REGULASI (FR-07) -->
        <!-- ===================================================================== -->
        <div id="config-regulasi-content" class="tab-content hidden space-y-6">

            <!-- Title & Subtitle Header -->
            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 pb-3 border-b border-neutral-200">
                <div>
                    <h1 class="text-xl font-bold tracking-tight text-neutral-900">Konfigurasi Regulasi &amp; Tarif Listrik</h1>
                    <p class="text-xs text-neutral-500 mt-0.5">Kalibrasi parameter tarif industri I-3/TM PLN, faktor K WBP, denda kVArh, faktor emisi grid Jamali, dan ambang batas PP 33/2023.</p>
                </div>
                <span class="px-2.5 py-1 rounded text-xs font-mono font-medium bg-neutral-100 text-neutral-700 border border-neutral-200">
                    Versi Aktif: {{ $config->versi_regulasi }}
                </span>
            </div>

            <!-- Regulatory & Tariff Parameters Form (FR-07) -->
            <div class="bg-white p-5 rounded-lg border border-neutral-200 shadow-xs space-y-4">
                <div class="flex items-center justify-between border-b border-neutral-200 pb-3">
                    <div>
                        <h2 class="text-sm font-semibold text-neutral-900">
                            Parameter Tarif Listrik Industri I-3/TM &amp; Koefisien Regulasi (FR-07)
                        </h2>
                        <p class="text-xs text-neutral-500 mt-0.5">
                            Perubahan parameter di bawah ini akan langsung mengkalibrasi ulang seluruh perhitungan finansial, denda, dan reduksi emisi di dashboard
                        </p>
                    </div>
                </div>

                <form action="{{ route('config.update') }}" method="POST" class="space-y-4 text-xs">
                    @csrf
                    <input type="hidden" name="preset" value="{{ $preset }}">

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label class="block font-medium text-neutral-700 mb-1">Tarif LWBP (Rp/kWh)</label>
                            <input type="number" step="0.01" name="tarif_lwbp_rp_kwh" value="{{ $config->tarif_lwbp_rp_kwh }}" required class="w-full bg-white border border-neutral-300 rounded p-2 text-neutral-900 font-mono text-xs focus:outline-none focus:border-brand-800">
                            <span class="text-[11px] text-neutral-500 mt-0.5 block">Tarif Luar Waktu Beban Puncak Golongan I-3/TM</span>
                        </div>
                        <div>
                            <label class="block font-medium text-neutral-700 mb-1">Faktor K WBP (1.40 - 2.00)</label>
                            <input type="number" step="0.01" name="faktor_k_wbp" value="{{ $config->faktor_k_wbp }}" required class="w-full bg-white border border-neutral-300 rounded p-2 text-neutral-900 font-mono text-xs focus:outline-none focus:border-brand-800">
                            <span class="text-[11px] text-neutral-500 mt-0.5 block">Pengali tarif pada Waktu Beban Puncak PLN</span>
                        </div>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label class="block font-medium text-neutral-700 mb-1">Jam WBP Mulai</label>
                            <input type="text" name="jam_wbp_mulai" value="{{ $config->jam_wbp_mulai }}" required class="w-full bg-white border border-neutral-300 rounded p-2 text-neutral-900 font-mono text-xs focus:outline-none focus:border-brand-800">
                            <span class="text-[11px] text-neutral-500 mt-0.5 block">Format HH:MM (Default: 18:00 WIB)</span>
                        </div>
                        <div>
                            <label class="block font-medium text-neutral-700 mb-1">Jam WBP Selesai</label>
                            <input type="text" name="jam_wbp_selesai" value="{{ $config->jam_wbp_selesai }}" required class="w-full bg-white border border-neutral-300 rounded p-2 text-neutral-900 font-mono text-xs focus:outline-none focus:border-brand-800">
                            <span class="text-[11px] text-neutral-500 mt-0.5 block">Format HH:MM (Default: 22:00 WIB)</span>
                        </div>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label class="block font-medium text-neutral-700 mb-1">Tarif kVArh (Rp/kVArh)</label>
                            <input type="number" step="0.01" name="tarif_kvarh_rp" value="{{ $config->tarif_kvarh_rp }}" required class="w-full bg-white border border-neutral-300 rounded p-2 text-neutral-900 font-mono text-xs focus:outline-none focus:border-brand-800">
                            <span class="text-[11px] text-neutral-500 mt-0.5 block">Denda kelebihan daya reaktif jika cos φ &lt; 0,85</span>
                        </div>
                        <div>
                            <label class="block font-medium text-neutral-700 mb-1">Batas Minimum cos φ (Faktor Daya)</label>
                            <input type="number" step="0.01" name="batas_cos_phi" value="{{ $config->batas_cos_phi }}" required class="w-full bg-white border border-neutral-300 rounded p-2 text-neutral-900 font-mono text-xs focus:outline-none focus:border-brand-800">
                            <span class="text-[11px] text-neutral-500 mt-0.5 block">Ambang penagihan biaya reaktif PLN (Default: 0.85)</span>
                        </div>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label class="block font-medium text-neutral-700 mb-1">Rasio Bebas kVArh</label>
                            <input type="number" step="0.01" name="rasio_kvarh_bebas" value="{{ $config->rasio_kvarh_bebas }}" required class="w-full bg-white border border-neutral-300 rounded p-2 text-neutral-900 font-mono text-xs focus:outline-none focus:border-brand-800">
                            <span class="text-[11px] text-neutral-500 mt-0.5 block">Ketetapan batas bebas penalti (Default: 0.62 x Total kWh)</span>
                        </div>
                        <div>
                            <div class="flex items-center justify-between mb-1">
                                <label class="block font-medium text-neutral-700">Faktor Emisi Grid (tCO₂e/MWh)</label>
                                <span class="text-[10px] text-brand-800 font-semibold bg-emerald-50 px-1.5 py-0.5 rounded border border-emerald-200">Preset Wilayah ESDM</span>
                            </div>
                            <div class="flex gap-2">
                                <select onchange="document.getElementById('input_tab_faktor_emisi_grid').value = this.value" class="bg-neutral-50 border border-neutral-300 rounded p-2 text-neutral-800 text-xs focus:outline-none focus:border-brand-800">
                                    <option value="" disabled selected>Pilih Grid Wilayah...</option>
                                    <option value="0.870">Jamali (Jawa-Madura-Bali): 0,870</option>
                                    <option value="0.780">Sumatera: 0,780</option>
                                    <option value="0.890">Kalimantan: 0,890</option>
                                    <option value="0.740">Sulawesi: 0,740</option>
                                </select>
                                <input type="number" step="0.001" id="input_tab_faktor_emisi_grid" name="faktor_emisi_grid" value="{{ $config->faktor_emisi_grid }}" required class="flex-1 bg-white border border-neutral-300 rounded p-2 text-neutral-900 font-mono text-xs focus:outline-none focus:border-brand-800">
                            </div>
                            <span class="text-[11px] text-neutral-500 mt-0.5 block">Publikasi Resmi Ditjen Gatrik ESDM (Jamali: 0.870, Sumatera: 0.780, Kalimantan: 0.890, Sulawesi: 0.740)</span>
                        </div>
                    </div>

                    <div class="p-3 bg-neutral-50 border border-neutral-200 rounded-md">
                        <div class="flex items-center justify-between mb-1">
                            <label class="font-medium text-neutral-700">Ambang Batas Wajib Industri PP 33/2023 (TOE/tahun)</label>
                            <span class="text-[10px] text-amber-900 font-semibold bg-amber-50 px-2 py-0.5 rounded border border-amber-300 flex items-center gap-1">
                                <svg class="w-3 h-3 text-amber-700" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/></svg>
                                Terkunci (Statutori PP 33/2023)
                            </span>
                        </div>
                        <input type="number" step="100" name="ambang_wajib_toe" value="4000" readonly class="w-full bg-neutral-100 border border-neutral-300 rounded p-2 text-neutral-700 font-mono text-xs cursor-not-allowed">
                        <span class="text-[11px] text-neutral-500 mt-0.5 block">Sesuai Pasal 4 PP 33/2023, ambang batas statutori wajib manajemen energi nasional adalah tetap 4.000 TOE/tahun (tidak dapat dimodifikasi sembarang).</span>
                    </div>

                    <input type="hidden" name="tab" value="config-regulasi">

                    <div class="flex justify-end pt-3 border-t border-neutral-200">
                        <button type="submit" class="px-4 py-2 bg-brand-800 hover:bg-brand-900 text-white rounded-md text-xs font-semibold transition shadow-xs">
                            Simpan &amp; Kalibrasi Ulang Seluruh Metrik
                        </button>
                    </div>
                </form>
            </div>

            <!-- Audit Trail / Version History (FR-07) -->
            <div class="bg-white p-5 rounded-lg border border-neutral-200 shadow-xs space-y-3">
                <div class="flex items-center justify-between border-b border-neutral-200 pb-3">
                    <div>
                        <h3 class="text-sm font-semibold text-neutral-900">
                            Jejak Audit &amp; Riwayat Kalibrasi Regulasi (Audit Trail FR-07)
                        </h3>
                        <p class="text-xs text-neutral-500 mt-0.5">
                            Log histori perubahan parameter tarif dan koefisien grid untuk akuntabilitas inspeksi auditor ESDM
                        </p>
                    </div>
                    <span class="text-[11px] font-mono text-neutral-500">
                        {{ isset($configLogs) ? $configLogs->count() : 0 }} Catatan Terakhir
                    </span>
                </div>

                <div class="overflow-x-auto border border-neutral-200 rounded-md">
                    <table class="w-full text-xs text-left">
                        <thead class="bg-neutral-50 text-neutral-600 font-semibold border-b border-neutral-200 text-[11px]">
                            <tr>
                                <th class="p-2.5">Waktu Kalibrasi</th>
                                <th class="p-2.5">Parameter Diubah</th>
                                <th class="p-2.5">Nilai Sebelumnya</th>
                                <th class="p-2.5">Nilai Baru</th>
                                <th class="p-2.5">Otoritas Pengubah</th>
                                <th class="p-2.5">Keterangan</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-neutral-200 font-mono text-xs">
                            @forelse ($configLogs ?? [] as $log)
                            <tr class="hover:bg-neutral-50">
                                <td class="p-2.5 text-neutral-600 font-sans">{{ $log->created_at->timezone('Asia/Jakarta')->format('d M Y H:i:s') }} WIB</td>
                                <td class="p-2.5 font-semibold text-neutral-900">{{ $log->parameter_name }}</td>
                                <td class="p-2.5 text-rose-800">{{ $log->old_value }}</td>
                                <td class="p-2.5 text-emerald-800 font-bold">{{ $log->new_value }}</td>
                                <td class="p-2.5 text-neutral-700 font-sans">{{ $log->changed_by }}</td>
                                <td class="p-2.5 text-neutral-500 font-sans text-[11px]">{{ $log->notes }}</td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="6" class="p-4 text-center text-neutral-400 font-sans italic text-xs">
                                    Belum ada riwayat perubahan parameter regulasi. Parameter masih menggunakan baseline standar ESDM-2026.
                                </td>
                            </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Legal Standards Reference Card -->
            <div class="bg-neutral-50 p-5 rounded-lg border border-neutral-200 text-xs text-neutral-600 space-y-3">
                <h3 class="font-semibold text-neutral-900">Dasar Hukum &amp; Referensi Regulasi Indonesia:</h3>
                <ul class="space-y-1.5 list-disc list-inside text-neutral-700 leading-relaxed text-[11px]">
                    <li><strong>PP No. 33 Tahun 2023</strong> tentang Konservasi Energi (Pasal 4: ambang batas wajib manajemen energi ≥ 4.000 TOE).</li>
                    <li><strong>Permen ESDM No. 8 Tahun 2025</strong> tentang Manajemen Energi dan Pelaporan Berkala (Mencabut Permen ESDM No. 14 Tahun 2012).</li>
                    <li><strong>Permen ESDM No. 3 Tahun 2025</strong> tentang Standar Kinerja Energi Bangunan Gedung (IKE).</li>
                    <li><strong>Permen ESDM No. 7 Tahun 2024</strong> tentang Tarif Tenaga Listrik yang Disediakan oleh PT PLN (Persero).</li>
                    <li><strong>SNI ISO 50001:2018</strong> tentang Sistem Manajemen Energi (Persyaratan dengan Pedoman Penggunaan).</li>
                </ul>
            </div>

        </div>

    </main>

    </div> <!-- Close of Right Main Viewport Column -->

    <!-- ========================================================================= -->
    <!-- AI COPILOT SLIDE-OVER DRAWER -->
    <!-- ========================================================================= -->
    <div id="copilotBackdrop" onclick="toggleCopilotDrawer()" class="hidden fixed inset-0 z-50 bg-neutral-900/20 backdrop-blur-xs transition-opacity"></div>
    <div id="copilotDrawer" class="fixed inset-y-0 right-0 z-50 w-full sm:max-w-lg md:max-w-xl bg-white shadow-2xl border-l border-neutral-200 transform translate-x-full transition-transform duration-300 flex flex-col">
        
        <!-- Drawer Header -->
        <div class="px-5 py-4 border-b border-neutral-200 flex items-center justify-between bg-neutral-50">
            <div class="flex items-center gap-3">
                <img src="{{ asset('images/indmira-logo.jpeg') }}" alt="PT Indmira Logo" 
                     class="w-8 h-8 rounded-md object-contain bg-white border border-neutral-200/90 p-0.5 shrink-0 shadow-xs">
                <div>
                    <h2 class="text-xs font-semibold text-neutral-900 uppercase tracking-wider">IEEC Advisory Copilot</h2>
                </div>
            </div>

            <!-- Tab Switcher & Obvious Close Button -->
            <div class="flex items-center gap-2">
                <div class="flex items-center bg-neutral-200/70 p-0.5 rounded-md text-xs font-medium">
                    <button id="tabNarrativeBtn" onclick="switchCopilotTab('narrative')" 
                            class="px-2.5 py-1 rounded transition text-neutral-900 bg-white shadow-xs font-semibold">
                        Audit ESDM
                    </button>
                    <button id="tabChatBtn" onclick="switchCopilotTab('chat')" 
                            class="px-2.5 py-1 rounded transition text-neutral-600 hover:text-neutral-900">
                        Tanya AI
                    </button>
                </div>

                <button onclick="toggleCopilotDrawer()" class="p-1.5 text-neutral-400 hover:text-neutral-700 hover:bg-neutral-200/60 rounded-md transition" title="Tutup panel drawer (Esc)">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                </button>
            </div>
        </div>

        <!-- Contextual Bar: Current Page & Facility Context -->
        <div class="px-5 py-2 bg-neutral-100/90 border-b border-neutral-200 text-[11px] text-neutral-600 flex items-center justify-between shrink-0">
            <span class="flex items-center gap-1.5">
                <span class="w-1.5 h-1.5 rounded-full bg-brand-800"></span>
                <span>Konteks Halaman: <strong id="drawerContextPageName" class="text-neutral-900 font-medium">Ikhtisar</strong></span>
            </span>
            <span class="font-mono text-neutral-500 text-[10px]">PT Indmira • 1–30 Sep 2026</span>
        </div>

        <!-- Drawer Content 1: Executive Audit Narrative -->
        <div id="narrativePane" class="flex-1 overflow-y-auto p-5 space-y-4">
            {!! $narrativeHtml !!}
        </div>

        <!-- Drawer Content 2: Interactive Chat Stream -->
        <div id="chatPane" class="hidden flex-1 flex flex-col justify-between overflow-hidden bg-neutral-50/50">
            
            <!-- Message Thread -->
            <div id="chatMessages" class="flex-1 overflow-y-auto p-4 space-y-3 text-xs leading-relaxed">
                <div class="p-3.5 bg-white border border-neutral-200 rounded-lg text-neutral-800 shadow-xs">
                    <p class="font-semibold text-brand-800 mb-1 flex items-center gap-1.5 text-xs">
                        <img src="{{ asset('images/indmira-logo.jpeg') }}" alt="PT Indmira Logo" class="w-4 h-4 rounded-xs object-contain bg-white border border-neutral-200 p-0.5 shrink-0">
                        <span>Asisten Teknis IEEC:</span>
                    </p>
                    <p class="text-neutral-700 leading-relaxed">
                        Halo! Saya adalah Industrial Energy Efficiency Copilot untuk fasilitas PT Indmira Global Energi. Saya siap membantu diagnosis beban daya, evaluasi ISO 50001, estimasi denda kVArh, hingga rekomendasi investasi efisiensi energi Permen ESDM 8/2025.
                    </p>
                </div>
            </div>

            <!-- Quick Question Pills -->
            <div class="p-2.5 bg-white border-t border-neutral-200 flex flex-wrap gap-1.5 text-[10px]">
                <button onclick="sendQuickPrompt('Bagaimana cara menurunkan Base Load E0 pada Line A?')" 
                        class="px-2.5 py-1 bg-neutral-100 hover:bg-neutral-200 text-neutral-700 rounded border border-neutral-200 transition">
                    Menurunkan E₀ Line A
                </button>
                <button onclick="sendQuickPrompt('Berapa estimasi denda penalti kVArh bulanan pabrik?')" 
                        class="px-2.5 py-1 bg-neutral-100 hover:bg-neutral-200 text-neutral-700 rounded border border-neutral-200 transition">
                    Denda Penalti kVArh
                </button>
                <button onclick="sendQuickPrompt('Jelaskan evaluasi status wajib PP 33/2023 pada fasilitas ini')" 
                        class="px-2.5 py-1 bg-neutral-100 hover:bg-neutral-200 text-neutral-700 rounded border border-neutral-200 transition">
                    Status PP 33/2023
                </button>
                <button onclick="sendQuickPrompt('Bagaimana potensi penghematan load shifting rotary dryer WBP?')" 
                        class="px-2.5 py-1 bg-neutral-100 hover:bg-neutral-200 text-neutral-700 rounded border border-neutral-200 transition">
                    Geser Beban WBP
                </button>
            </div>

            <!-- Chat Input Form -->
            <form id="chatForm" onsubmit="handleChatSubmit(event)" class="p-3 bg-white border-t border-neutral-200 flex gap-2">
                <input type="text" id="chatInput" 
                       placeholder="Ketik pertanyaan teknis atau regulasi ESDM..." 
                       class="flex-1 bg-neutral-50 border border-neutral-300 rounded-md px-3 py-2 text-xs text-neutral-900 placeholder-neutral-400 focus:outline-none focus:border-brand-800"
                       autocomplete="off">
                <button type="submit" id="chatSubmitBtn" 
                        class="px-4 py-2 bg-brand-800 hover:bg-brand-900 text-white rounded-md text-xs font-semibold flex items-center gap-1.5 transition shadow-xs">
                    <span>Kirim</span>
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3"/></svg>
                </button>
            </form>

            <!-- Link to Dedicated Workspace -->
            <div class="px-4 py-2 bg-neutral-100/70 border-t border-neutral-200 flex items-center justify-between text-[11px] text-neutral-500 shrink-0">
                <span>Butuh visualisasi dan audit lengkap?</span>
                <button onclick="toggleCopilotDrawer(); switchTab('copilot');" class="text-brand-800 font-semibold hover:underline">
                    Buka Workspace AI Copilot &rarr;
                </button>
            </div>

        </div>

    </div>

    <!-- ========================================================================= -->
    <!-- MODAL 1: CSV UPLOAD MODAL (FR-01) -->
    <!-- ========================================================================= -->
    <div id="uploadModal" class="hidden fixed inset-0 z-50 bg-neutral-900/30 backdrop-blur-xs flex items-center justify-center p-4">
        <div class="bg-white border border-neutral-200 rounded-xl p-6 max-w-lg w-full shadow-2xl space-y-4 animate-fade-in">
            <div class="flex items-center justify-between border-b border-neutral-200 pb-3">
                <h3 class="text-sm font-semibold text-neutral-900 flex items-center gap-2">
                    <svg class="w-4 h-4 text-brand-800" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12"/></svg>
                    <span>Unggah Berkas Telemetri CSV (FR-01)</span>
                </h3>
                <button onclick="document.getElementById('uploadModal').classList.add('hidden')" class="text-neutral-400 hover:text-neutral-700 text-base">&times;</button>
            </div>

            <form action="{{ route('csv.upload') }}" method="POST" enctype="multipart/form-data" class="space-y-4">
                @csrf
                <div class="border-2 border-dashed border-neutral-300 hover:border-brand-800 rounded-lg p-6 text-center cursor-pointer bg-neutral-50 transition">
                    <svg class="w-8 h-8 mx-auto text-neutral-400 mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12"/></svg>
                    <p class="text-xs text-neutral-700 font-medium">Pilih berkas CSV telemetri energi</p>
                    <p class="text-[11px] text-neutral-500 mt-1">Maksimal 10 MB • Skema 9 Kolom Standar IEEC</p>
                    <input type="file" name="csv_file" accept=".csv" required class="mt-3 block w-full text-xs text-neutral-500 file:mr-4 file:py-1.5 file:px-3 file:rounded file:border-0 file:text-xs file:font-medium file:bg-brand-800 file:text-white hover:file:bg-brand-900 cursor-pointer">
                </div>

                <div class="bg-neutral-50 p-3 rounded-md border border-neutral-200 text-[11px] text-neutral-600 space-y-1">
                    <strong class="text-neutral-800 block">Skema Kolom Wajib:</strong>
                    <code class="text-brand-800 font-mono text-[10px] block">timestamp, panel_id, category, energy_kwh, power_factor, kvarh, production_qty, area_sqm, shift_state</code>
                </div>

                <div class="flex justify-end gap-2 pt-2">
                    <button type="button" onclick="document.getElementById('uploadModal').classList.add('hidden')" class="px-3 py-1.5 bg-neutral-100 hover:bg-neutral-200 text-neutral-700 rounded-md text-xs">Batal</button>
                    <button type="submit" class="px-4 py-1.5 bg-brand-800 hover:bg-brand-900 text-white rounded-md text-xs font-semibold">Proses &amp; Ingest Data</button>
                </div>
            </form>
        </div>
    </div>

    <!-- ========================================================================= -->
    <!-- MODAL 2: CONFIGURATION MODAL (FR-07) -->
    <!-- ========================================================================= -->
    <div id="configModal" class="hidden fixed inset-0 z-50 bg-neutral-900/30 backdrop-blur-xs flex items-center justify-center p-4">
        <div class="bg-white border border-neutral-200 rounded-xl p-6 max-w-xl w-full shadow-2xl space-y-4 max-h-[90vh] overflow-y-auto animate-fade-in">
            <div class="flex items-center justify-between border-b border-neutral-200 pb-3">
                <div>
                    <h3 class="text-sm font-semibold text-neutral-900 flex items-center gap-2">
                        <svg class="w-4 h-4 text-brand-800" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                        <span>Konfigurasi Regulasi &amp; Tarif PLN (FR-07)</span>
                    </h3>
                    <p class="text-[11px] text-neutral-500 mt-0.5">Penyesuaian parameter tarif dan ambang batas secara dinamis</p>
                </div>
                <button onclick="document.getElementById('configModal').classList.add('hidden')" class="text-neutral-400 hover:text-neutral-700 text-base">&times;</button>
            </div>

            <form action="{{ route('config.update') }}" method="POST" class="space-y-4 text-xs">
                @csrf
                <input type="hidden" name="preset" value="{{ $preset }}">

                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="block text-neutral-700 font-medium mb-1">Tarif LWBP (Rp/kWh)</label>
                        <input type="number" step="0.01" name="tarif_lwbp_rp_kwh" value="{{ $config->tarif_lwbp_rp_kwh }}" required class="w-full bg-white border border-neutral-300 rounded p-2 text-neutral-900 font-mono text-xs focus:outline-none focus:border-brand-800">
                    </div>
                    <div>
                        <label class="block text-neutral-700 font-medium mb-1">Faktor K WBP (1.40 - 2.00)</label>
                        <input type="number" step="0.01" name="faktor_k_wbp" value="{{ $config->faktor_k_wbp }}" required class="w-full bg-white border border-neutral-300 rounded p-2 text-neutral-900 font-mono text-xs focus:outline-none focus:border-brand-800">
                    </div>
                </div>

                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="block text-neutral-700 font-medium mb-1">Jam WBP Mulai</label>
                        <input type="text" name="jam_wbp_mulai" value="{{ $config->jam_wbp_mulai }}" required class="w-full bg-white border border-neutral-300 rounded p-2 text-neutral-900 font-mono text-xs focus:outline-none focus:border-brand-800">
                    </div>
                    <div>
                        <label class="block text-neutral-700 font-medium mb-1">Jam WBP Selesai</label>
                        <input type="text" name="jam_wbp_selesai" value="{{ $config->jam_wbp_selesai }}" required class="w-full bg-white border border-neutral-300 rounded p-2 text-neutral-900 font-mono text-xs focus:outline-none focus:border-brand-800">
                    </div>
                </div>

                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="block text-neutral-700 font-medium mb-1">Tarif kVArh (Rp/kVArh)</label>
                        <input type="number" step="0.01" name="tarif_kvarh_rp" value="{{ $config->tarif_kvarh_rp }}" required class="w-full bg-white border border-neutral-300 rounded p-2 text-neutral-900 font-mono text-xs focus:outline-none focus:border-brand-800">
                    </div>
                    <div>
                        <label class="block text-neutral-700 font-medium mb-1">Batas Minimum cos φ</label>
                        <input type="number" step="0.01" name="batas_cos_phi" value="{{ $config->batas_cos_phi }}" required class="w-full bg-white border border-neutral-300 rounded p-2 text-neutral-900 font-mono text-xs focus:outline-none focus:border-brand-800">
                    </div>
                </div>

                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="block text-neutral-700 font-medium mb-1">Rasio Bebas kVArh (Default 0.62)</label>
                        <input type="number" step="0.01" name="rasio_kvarh_bebas" value="{{ $config->rasio_kvarh_bebas }}" required class="w-full bg-white border border-neutral-300 rounded p-2 text-neutral-900 font-mono text-xs focus:outline-none focus:border-brand-800">
                    </div>
                    <div>
                        <div class="flex items-center justify-between mb-1">
                            <label class="block text-neutral-700 font-medium">Faktor Emisi Grid</label>
                            <span class="text-[9px] text-brand-800 font-semibold bg-emerald-50 px-1 rounded border border-emerald-200">ESDM</span>
                        </div>
                        <div class="flex gap-1.5">
                            <select onchange="document.getElementById('input_modal_faktor_emisi_grid').value = this.value" class="bg-neutral-50 border border-neutral-300 rounded p-1.5 text-neutral-800 text-[11px] focus:outline-none focus:border-brand-800">
                                <option value="" disabled selected>Pilih Grid...</option>
                                <option value="0.870">Jamali: 0,870</option>
                                <option value="0.780">Sumatera: 0,780</option>
                                <option value="0.890">Kalimantan: 0,890</option>
                                <option value="0.740">Sulawesi: 0,740</option>
                            </select>
                            <input type="number" step="0.001" id="input_modal_faktor_emisi_grid" name="faktor_emisi_grid" value="{{ $config->faktor_emisi_grid }}" required class="flex-1 bg-white border border-neutral-300 rounded p-1.5 text-neutral-900 font-mono text-xs focus:outline-none focus:border-brand-800">
                        </div>
                    </div>
                </div>

                <div class="p-2.5 bg-neutral-50 border border-neutral-200 rounded">
                    <div class="flex items-center justify-between mb-1">
                        <label class="block text-neutral-700 font-medium">Ambang Wajib Industri (PP 33/2023)</label>
                        <span class="text-[9px] text-amber-900 font-semibold bg-amber-50 px-1.5 py-0.5 rounded border border-amber-300">Terkunci Statutori</span>
                    </div>
                    <input type="number" step="100" name="ambang_wajib_toe" value="4000" readonly class="w-full bg-neutral-100 border border-neutral-300 rounded p-1.5 text-neutral-600 font-mono text-xs cursor-not-allowed">
                    <span class="text-[10px] text-neutral-500 mt-0.5 block">Pasal 4 PP 33/2023: Tetap 4.000 TOE/th.</span>
                </div>

                <input type="hidden" name="tab" value="config-regulasi">

                <div class="flex justify-end gap-2 pt-3 border-t border-neutral-200">
                    <button type="button" onclick="document.getElementById('configModal').classList.add('hidden')" class="px-3 py-1.5 bg-neutral-100 hover:bg-neutral-200 text-neutral-700 rounded-md text-xs">Batal</button>
                    <button type="submit" class="px-4 py-1.5 bg-brand-800 hover:bg-brand-900 text-white rounded-md text-xs font-semibold">Simpan &amp; Kalibrasi Ulang</button>
                </div>
            </form>
        </div>
    </div>

    <!-- ========================================================================= -->
    <!-- CLIENT JAVASCRIPT: APEXCHARTS, TAB SWITCHING, IOT & CHAT -->
    <!-- ========================================================================= -->
    <script>
        // 1. SIDEBAR COLLAPSE & MOBILE DRAWER CONTROLS
        function toggleSidebarCollapse() {
            const sidebar = document.getElementById('sidebar');
            const isCollapsed = sidebar.classList.contains('w-18') || sidebar.classList.contains('w-16');
            const labels = document.querySelectorAll('.sidebar-label');
            const collapseIcon = document.getElementById('collapseIcon');

            if (isCollapsed) {
                sidebar.classList.remove('w-18', 'w-16');
                sidebar.classList.add('w-64');
                labels.forEach(el => el.classList.remove('hidden'));
                if (collapseIcon) collapseIcon.classList.remove('rotate-180');
                localStorage.setItem('ieec_sidebar_collapsed', 'false');
            } else {
                sidebar.classList.remove('w-64');
                sidebar.classList.add('w-18');
                labels.forEach(el => el.classList.add('hidden'));
                if (collapseIcon) collapseIcon.classList.add('rotate-180');
                localStorage.setItem('ieec_sidebar_collapsed', 'true');
            }

            setTimeout(() => {
                window.dispatchEvent(new Event('resize'));
            }, 200);
        }

        function toggleMobileSidebar() {
            const sidebar = document.getElementById('sidebar');
            const backdrop = document.getElementById('sidebarBackdrop');
            if (!sidebar || !backdrop) return;
            const isClosed = sidebar.classList.contains('-translate-x-full');

            if (isClosed) {
                sidebar.classList.remove('-translate-x-full');
                backdrop.classList.remove('hidden');
            } else {
                sidebar.classList.add('-translate-x-full');
                backdrop.classList.add('hidden');
            }
        }

        // 2. TAB NAVIGATION (WORKSPACE & SYSTEM)
        const tabNames = {
            'overview': 'Ikhtisar',
            'analytics': 'Analitik Energi & ISO 50001',
            'anomalies': 'Anomali & Pemborosan',
            'recommendations': 'Matriks Rekomendasi',
            'compliance': 'Kepatuhan & Audit',
            'copilot': 'Workspace AI Copilot',
            'data-import': 'Data & Import',
            'config-regulasi': 'Konfigurasi Regulasi'
        };
        let currentActiveTab = 'overview';

        function updateDrawerContext(tabId) {
            currentActiveTab = tabId;
            const badge = document.getElementById('drawerContextPageName');
            if (badge) {
                badge.textContent = tabNames[tabId] || tabId;
            }
        }

        function switchTab(tabId) {
            // Backward-compatible alias
            if (tabId === 'data-config') {
                tabId = 'data-import';
            }

            // Hide all tab content panes
            document.querySelectorAll('.tab-content').forEach(el => el.classList.add('hidden'));
            
            // Reset all tab button styles in sidebar
            document.querySelectorAll('.tab-btn').forEach(btn => {
                btn.classList.remove('bg-emerald-50', 'text-brand-900', 'font-semibold', 'border-l-2', 'border-brand-800');
                btn.classList.add('text-neutral-600');
                const icon = btn.querySelector('svg');
                if (icon) {
                    icon.classList.remove('text-brand-800');
                    icon.classList.add('text-neutral-400');
                }
            });

            // Show selected tab content pane
            const targetPane = document.getElementById(tabId + '-content');
            if (targetPane) {
                targetPane.classList.remove('hidden');
            }

            // Activate button in sidebar
            const activeBtn = document.getElementById('tab-btn-' + tabId);
            if (activeBtn) {
                activeBtn.classList.add('bg-emerald-50', 'text-brand-900', 'font-semibold', 'border-l-2', 'border-brand-800');
                activeBtn.classList.remove('text-neutral-600');
                const icon = activeBtn.querySelector('svg');
                if (icon) {
                    icon.classList.remove('text-neutral-400');
                    icon.classList.add('text-brand-800');
                }
            }

            // Sync drawer context
            updateDrawerContext(tabId);

            // Sync URL hash
            if (history.replaceState) {
                history.replaceState(null, null, '#' + tabId);
            }

            // Trigger resize event for ApexCharts and sync panel heights
            setTimeout(() => {
                window.dispatchEvent(new Event('resize'));
                syncOverviewPanelHeights();
            }, 100);

            // Auto-close mobile sidebar if open
            if (window.innerWidth < 768) {
                const sidebar = document.getElementById('sidebar');
                const backdrop = document.getElementById('sidebarBackdrop');
                if (sidebar && !sidebar.classList.contains('-translate-x-full')) {
                    sidebar.classList.add('-translate-x-full');
                    if (backdrop) backdrop.classList.add('hidden');
                }
            }
        }

        // Synchronize outer heights between SDP table panel and recent anomalies panel on desktop
        function syncOverviewPanelHeights() {
            const tablePanel = document.getElementById('sdpTablePanel');
            const anomalyPanel = document.getElementById('recentAnomaliesPanel');
            if (!tablePanel || !anomalyPanel) return;

            if (window.innerWidth >= 1024) { // lg breakpoint
                const tableHeight = tablePanel.offsetHeight;
                if (tableHeight > 0) {
                    anomalyPanel.style.height = `${tableHeight}px`;
                }
            } else {
                anomalyPanel.style.height = '';
            }
        }

        window.addEventListener('resize', syncOverviewPanelHeights);
        window.addEventListener('load', syncOverviewPanelHeights);

        // Initialize Tab from hash or server-rendered initial tab, or default to overview
        window.addEventListener('DOMContentLoaded', () => {
            // Restore sidebar collapsed preference on desktop
            if (window.innerWidth >= 768 && localStorage.getItem('ieec_sidebar_collapsed') === 'true') {
                toggleSidebarCollapse();
            }

            syncOverviewPanelHeights();
            if (window.ResizeObserver) {
                const tablePanel = document.getElementById('sdpTablePanel');
                if (tablePanel) {
                    new ResizeObserver(() => syncOverviewPanelHeights()).observe(tablePanel);
                }
            }

            const initialServerTab = "{{ $initialTab ?? '' }}";
            const hash = window.location.hash.replace('#', '');
            if (hash && (document.getElementById(hash + '-content') || hash === 'data-config')) {
                switchTab(hash);
            } else if (initialServerTab && document.getElementById(initialServerTab + '-content')) {
                switchTab(initialServerTab);
            } else {
                switchTab('overview');
            }
        });

        // Browser back/forward navigation support
        window.addEventListener('hashchange', () => {
            const hash = window.location.hash.replace('#', '');
            if (hash && document.getElementById(hash + '-content')) {
                switchTab(hash);
            }
        });

        // Accessible ESC key listener for closing secondary drawer and modals
        document.addEventListener('keydown', (e) => {
            if (e.key === 'Escape') {
                const drawer = document.getElementById('copilotDrawer');
                if (drawer && !drawer.classList.contains('translate-x-full')) {
                    toggleCopilotDrawer();
                }
                const uploadModal = document.getElementById('uploadModal');
                if (uploadModal && !uploadModal.classList.contains('hidden')) {
                    uploadModal.classList.add('hidden');
                }
            }
        });

        // 2. COPILOT DRAWER TOGGLE & TABS
        function toggleCopilotDrawer() {
            const drawer = document.getElementById('copilotDrawer');
            const backdrop = document.getElementById('copilotBackdrop');
            const isClosed = drawer.classList.contains('translate-x-full');

            if (isClosed) {
                drawer.classList.remove('translate-x-full');
                backdrop.classList.remove('hidden');
            } else {
                drawer.classList.add('translate-x-full');
                backdrop.classList.add('hidden');
            }
        }

        function switchCopilotTab(tab) {
            const narrativePane = document.getElementById('narrativePane');
            const chatPane = document.getElementById('chatPane');
            const tabNarrativeBtn = document.getElementById('tabNarrativeBtn');
            const tabChatBtn = document.getElementById('tabChatBtn');

            if (tab === 'chat') {
                narrativePane.classList.add('hidden');
                chatPane.classList.remove('hidden');
                tabNarrativeBtn.classList.remove('bg-white', 'text-neutral-900', 'shadow-xs', 'font-semibold');
                tabNarrativeBtn.classList.add('text-neutral-600');
                tabChatBtn.classList.add('bg-white', 'text-neutral-900', 'shadow-xs', 'font-semibold');
                tabChatBtn.classList.remove('text-neutral-600');
                document.getElementById('chatInput').focus();
            } else {
                narrativePane.classList.remove('hidden');
                chatPane.classList.add('hidden');
                tabChatBtn.classList.remove('bg-white', 'text-neutral-900', 'shadow-xs', 'font-semibold');
                tabChatBtn.classList.add('text-neutral-600');
                tabNarrativeBtn.classList.add('bg-white', 'text-neutral-900', 'shadow-xs', 'font-semibold');
                tabNarrativeBtn.classList.remove('text-neutral-600');
            }
        }

        // 3. APEXCHARTS TIME-SERIES VISUALIZATION
        const chartTimes = {!! json_encode($payload['visualisasi_grafik']['timestamps']) !!};
        const actualKwhData = {!! json_encode($payload['visualisasi_grafik']['actual_kwh']) !!};
        const baselineKwhData = {!! json_encode($payload['visualisasi_grafik']['baseline_kwh']) !!};
        const cusumData = {!! json_encode($payload['visualisasi_grafik']['cusum']) !!};

        let showCusum = false;

        const chartOptions = {
            series: [
                {
                    name: 'Konsumsi Listrik Aktual (kWh)',
                    type: 'area',
                    data: actualKwhData
                },
                {
                    name: 'Energy Baseline (EnB ISO 50001)',
                    type: 'line',
                    data: baselineKwhData
                }
            ],
            chart: {
                height: 380,
                type: 'line',
                background: '#FFFFFF',
                toolbar: {
                    show: true,
                    tools: {
                        download: true,
                        selection: true,
                        zoom: true,
                        zoomin: true,
                        zoomout: true,
                        pan: true,
                        reset: true
                    }
                },
                fontFamily: 'Inter, sans-serif'
            },
            theme: { mode: 'light' },
            colors: ['#1E40AF', '#15803D', '#7C3AED'], // Industrial Blue, Forest Green, Purple CUSUM
            stroke: {
                curve: 'smooth',
                width: [2, 2, 2],
                dashArray: [0, 4, 0]
            },
            fill: {
                type: ['gradient', 'solid', 'solid'],
                gradient: {
                    shade: 'light',
                    type: 'vertical',
                    shadeIntensity: 0.2,
                    gradientToColors: ['#3B82F6'],
                    inverseColors: false,
                    opacityFrom: 0.15,
                    opacityTo: 0.02,
                    stops: [0, 100]
                }
            },
            dataLabels: { enabled: false },
            xaxis: {
                categories: chartTimes,
                labels: {
                    style: { colors: '#64748B', fontSize: '10px', fontFamily: 'JetBrains Mono' },
                    rotate: -45,
                    rotateAlways: false
                },
                axisBorder: { color: '#E2E8F0' },
                axisTicks: { color: '#E2E8F0' }
            },
            yaxis: {
                title: { text: 'Energi Listrik (kWh)', style: { color: '#475569', fontSize: '11px', fontWeight: 500 } },
                labels: {
                    style: { colors: '#64748B', fontSize: '10px', fontFamily: 'JetBrains Mono' },
                    formatter: (val) => val ? val.toFixed(1) : ''
                }
            },
            grid: {
                borderColor: '#F1F5F9',
                strokeDashArray: 3
            },
            tooltip: {
                theme: 'light',
                shared: true,
                intersect: false,
                x: { format: 'dd MMM HH:mm' }
            },
            legend: {
                position: 'top',
                horizontalAlign: 'right',
                labels: { colors: '#334155' }
            }
        };

        let chart = null;
        function initTimeseriesChart() {
            const chartEl = document.querySelector("#timeseriesChart");
            if (!chartEl) return;
            if (typeof ApexCharts === 'undefined') {
                setTimeout(initTimeseriesChart, 50);
                return;
            }
            if (!chart) {
                chart = new ApexCharts(chartEl, chartOptions);
                chart.render();
            }
        }
        if (document.readyState === 'loading') {
            document.addEventListener('DOMContentLoaded', initTimeseriesChart);
        } else {
            initTimeseriesChart();
        }

        // Preserve initial snapshot for state preservation
        const initialChartTimes = [...chartTimes];
        const initialActualKwhData = [...actualKwhData];
        const initialBaselineKwhData = [...baselineKwhData];
        const initialCusumData = [...cusumData];

        function getTimeseriesSeries() {
            const series = [
                {
                    name: 'Konsumsi Listrik Aktual (kWh)',
                    type: 'area',
                    data: actualKwhData
                },
                {
                    name: 'Energy Baseline (EnB ISO 50001)',
                    type: 'line',
                    data: baselineKwhData
                }
            ];
            if (showCusum) {
                series.push({
                    name: 'Kumulatif Selisih CUSUM (ISO 50001)',
                    type: 'line',
                    data: cusumData
                });
            }
            return series;
        }

        function toggleCusumSeries() {
            if (!chart) return;
            showCusum = !showCusum;
            const btn = document.getElementById('toggleCusumBtn');
            chart.updateSeries(getTimeseriesSeries());
            if (showCusum) {
                btn.classList.add('bg-purple-50', 'text-purple-800', 'border-purple-300');
            } else {
                btn.classList.remove('bg-purple-50', 'text-purple-800', 'border-purple-300');
            }
        }

        // 4. ANOMALY LOG FILTERING
        function filterAnomalyRows(type) {
            const rows = document.querySelectorAll('.anomaly-row');
            const btns = ['filter-all', 'filter-idle', 'filter-spike', 'filter-wbp'];
            btns.forEach(id => {
                const b = document.getElementById(id);
                if (b) {
                    b.classList.remove('bg-neutral-200', 'text-neutral-800', 'font-medium');
                    b.classList.add('bg-neutral-100', 'text-neutral-600');
                }
            });

            if (type === 'ALL') {
                rows.forEach(r => r.classList.remove('hidden'));
                document.getElementById('filter-all')?.classList.add('bg-neutral-200', 'text-neutral-800', 'font-medium');
            } else {
                rows.forEach(r => {
                    const rowType = r.getAttribute('data-type') || '';
                    if (rowType.toLowerCase().includes(type.toLowerCase())) {
                        r.classList.remove('hidden');
                    } else {
                        r.classList.add('hidden');
                    }
                });
                if (type === 'Idle') document.getElementById('filter-idle')?.classList.add('bg-neutral-200', 'text-neutral-800', 'font-medium');
                if (type === 'Spike') document.getElementById('filter-spike')?.classList.add('bg-neutral-200', 'text-neutral-800', 'font-medium');
                if (type === 'WBP') document.getElementById('filter-wbp')?.classList.add('bg-neutral-200', 'text-neutral-800', 'font-medium');
            }
        }

        // 5. INTERACTIVE COPILOT CHAT CLIENT
        let chatHistory = [];

        function sendQuickPrompt(promptText) {
            toggleCopilotDrawer();
            switchCopilotTab('chat');
            document.getElementById('chatInput').value = promptText;
            document.getElementById('chatForm').dispatchEvent(new Event('submit'));
        }

        async function handleChatSubmit(e) {
            e.preventDefault();
            const input = document.getElementById('chatInput');
            const q = input.value.trim();
            if (!q) return;

            const chatMessages = document.getElementById('chatMessages');
            const submitBtn = document.getElementById('chatSubmitBtn');

            // Render User Bubble
            chatMessages.innerHTML += `
                <div class="p-3 bg-brand-50 border border-brand-200 rounded-lg text-neutral-900 ml-6 shadow-2xs">
                    <p class="font-semibold text-brand-800 mb-0.5 text-[11px]">Anda (Manajer Energi):</p>
                    <p class="text-xs text-neutral-800">${escapeHtml(q)}</p>
                </div>
            `;
            input.value = '';
            chatMessages.scrollTop = chatMessages.scrollHeight;

            // Loading indicator
            const loadingId = 'loading-' + Date.now();
            chatMessages.innerHTML += `
                <div id="${loadingId}" class="p-3 bg-white border border-neutral-200 rounded-lg text-neutral-500 mr-6 flex items-center gap-2 text-xs">
                    <span class="w-2 h-2 rounded-full bg-brand-800 animate-ping"></span>
                    <span>IEEC sedang memproses data regulasi &amp; analitik...</span>
                </div>
            `;
            chatMessages.scrollTop = chatMessages.scrollHeight;
            submitBtn.disabled = true;

            try {
                const res = await fetch("{{ route('copilot.chat') }}", {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': '{{ csrf_token() }}'
                    },
                    body: JSON.stringify({
                        question: q,
                        preset: "{{ $preset }}",
                        history: chatHistory
                    })
                });

                const data = await res.json();
                document.getElementById(loadingId)?.remove();

                const formattedReply = formatMarkdown(data.reply);

                chatMessages.innerHTML += `
                    <div class="p-3.5 bg-white border border-neutral-200 rounded-lg text-neutral-800 mr-2 space-y-2 shadow-xs">
                        <div class="flex items-center justify-between text-[10px] text-neutral-400 mb-1 border-b border-neutral-100 pb-1">
                            <span class="font-semibold text-brand-800 flex items-center gap-1.5">
                                <img src="{{ asset('images/indmira-logo.jpeg') }}" alt="PT Indmira Logo" class="w-4 h-4 rounded-xs object-contain bg-white border border-neutral-200 p-0.5 shrink-0">
                                <span>IEEC Copilot</span>
                            </span>
                            <span class="font-mono text-neutral-500">${data.timestamp || ''}</span>
                        </div>
                        <div class="text-xs leading-relaxed text-neutral-700">${formattedReply}</div>
                    </div>
                `;

                chatHistory.push({ role: 'user', content: q });
                chatHistory.push({ role: 'assistant', content: data.reply });
            } catch (err) {
                document.getElementById(loadingId)?.remove();
                chatMessages.innerHTML += `
                    <div class="p-3 bg-rose-50 border border-rose-200 rounded-lg text-rose-800 text-xs">
                        Gagal menghubungi layanan Copilot. Silakan coba kembali.
                    </div>
                `;
            } finally {
                submitBtn.disabled = false;
                chatMessages.scrollTop = chatMessages.scrollHeight;
            }
        }

        // Dedicated Copilot Page Chat Functions
        function sendQuickPromptFromPage(promptText) {
            const input = document.getElementById('copilotPageInput');
            if (input) {
                input.value = promptText;
                document.getElementById('copilotPageChatForm')?.dispatchEvent(new Event('submit'));
            }
        }

        function clearCopilotConversation() {
            chatHistory = [];
            const resetHtml = `
                <div class="p-3.5 bg-neutral-50 border border-neutral-200 rounded-lg text-neutral-800 space-y-2 shadow-2xs">
                    <div class="flex items-center justify-between text-[10px] text-neutral-400 border-b border-neutral-200 pb-1">
                        <span class="font-semibold text-brand-800 flex items-center gap-1.5">
                            <img src="{{ asset('images/indmira-logo.jpeg') }}" alt="PT Indmira Logo" class="w-4 h-4 rounded-xs object-contain bg-white border border-neutral-200 p-0.5 shrink-0">
                            <span>Asisten Teknis IEEC</span>
                        </span>
                        <span class="font-mono text-neutral-500">Sesi Baru</span>
                    </div>
                    <p class="text-neutral-700 leading-relaxed text-xs">
                        Sesi percakapan telah direset. Silakan tanyakan diagnostik beban, evaluasi ISO 50001, atau kepatuhan PP 33/2023.
                    </p>
                </div>
            `;
            const pageMsg = document.getElementById('copilotPageMessages');
            if (pageMsg) pageMsg.innerHTML = resetHtml;
            const drawerMsg = document.getElementById('chatMessages');
            if (drawerMsg) drawerMsg.innerHTML = resetHtml;
        }

        async function handleCopilotPageSubmit(e) {
            e.preventDefault();
            const input = document.getElementById('copilotPageInput');
            const q = input.value.trim();
            if (!q) return;

            const pageMessages = document.getElementById('copilotPageMessages');
            const submitBtn = document.getElementById('copilotPageSubmitBtn');

            // Render User Bubble
            pageMessages.innerHTML += `
                <div class="p-3 bg-brand-50 border border-brand-200 rounded-lg text-neutral-900 ml-6 shadow-2xs">
                    <p class="font-semibold text-brand-800 mb-0.5 text-[11px]">Anda (Manajer Energi):</p>
                    <p class="text-xs text-neutral-800">${escapeHtml(q)}</p>
                </div>
            `;
            input.value = '';
            pageMessages.scrollTop = pageMessages.scrollHeight;

            // Loading indicator
            const loadingId = 'loading-page-' + Date.now();
            pageMessages.innerHTML += `
                <div id="${loadingId}" class="p-3 bg-white border border-neutral-200 rounded-lg text-neutral-500 mr-6 flex items-center gap-2 text-xs">
                    <span class="w-2 h-2 rounded-full bg-brand-800 animate-ping"></span>
                    <span>IEEC sedang memproses data regulasi &amp; analitik...</span>
                </div>
            `;
            pageMessages.scrollTop = pageMessages.scrollHeight;
            submitBtn.disabled = true;

            try {
                const res = await fetch("{{ route('copilot.chat') }}", {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': '{{ csrf_token() }}'
                    },
                    body: JSON.stringify({
                        question: q,
                        preset: "{{ $preset }}",
                        history: chatHistory
                    })
                });

                const data = await res.json();
                document.getElementById(loadingId)?.remove();

                const formattedReply = formatMarkdown(data.reply);

                pageMessages.innerHTML += `
                    <div class="p-3.5 bg-white border border-neutral-200 rounded-lg text-neutral-800 mr-2 space-y-2 shadow-xs">
                        <div class="flex items-center justify-between text-[10px] text-neutral-400 mb-1 border-b border-neutral-100 pb-1">
                            <span class="font-semibold text-brand-800 flex items-center gap-1.5">
                                <img src="{{ asset('images/indmira-logo.jpeg') }}" alt="PT Indmira Logo" class="w-4 h-4 rounded-xs object-contain bg-white border border-neutral-200 p-0.5 shrink-0">
                                <span>IEEC Copilot</span>
                            </span>
                            <span class="font-mono text-neutral-500">${data.timestamp || ''}</span>
                        </div>
                        <div class="text-xs leading-relaxed text-neutral-700">${formattedReply}</div>
                    </div>
                `;

                chatHistory.push({ role: 'user', content: q });
                chatHistory.push({ role: 'assistant', content: data.reply });

                // Also synchronize message to drawer thread
                const drawerMessages = document.getElementById('chatMessages');
                if (drawerMessages) {
                    drawerMessages.innerHTML += `
                        <div class="p-3 bg-brand-50 border border-brand-200 rounded-lg text-neutral-900 ml-6 shadow-2xs">
                            <p class="font-semibold text-brand-800 mb-0.5 text-[11px]">Anda (Manajer Energi):</p>
                            <p class="text-xs text-neutral-800">${escapeHtml(q)}</p>
                        </div>
                        <div class="p-3.5 bg-white border border-neutral-200 rounded-lg text-neutral-800 mr-2 space-y-2 shadow-xs">
                            <div class="flex items-center justify-between text-[10px] text-neutral-400 mb-1 border-b border-neutral-100 pb-1">
                                <span class="font-semibold text-brand-800 flex items-center gap-1.5">
                                    <span class="w-1.5 h-1.5 rounded-full bg-emerald-600"></span>
                                    IEEC Copilot
                                </span>
                                <span class="font-mono text-neutral-500">${data.timestamp || ''}</span>
                            </div>
                            <div class="text-xs leading-relaxed text-neutral-700">${formattedReply}</div>
                        </div>
                    `;
                }
            } catch (err) {
                document.getElementById(loadingId)?.remove();
                pageMessages.innerHTML += `
                    <div class="p-3 bg-rose-50 border border-rose-200 rounded-lg text-rose-800 text-xs">
                        Gagal menghubungi layanan Copilot. Silakan coba kembali.
                    </div>
                `;
            } finally {
                submitBtn.disabled = false;
                pageMessages.scrollTop = pageMessages.scrollHeight;
            }
        }

        function escapeHtml(text) {
            return text.replace(/[&<>"']/g, m => ({ '&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#039;' })[m]);
        }

        function formatMarkdown(text) {
            if (!text) return '';

            // Code blocks
            text = text.replace(/```([\s\S]*?)```/g, '<pre class="bg-neutral-100 p-2.5 rounded border border-neutral-200 text-[11px] font-mono overflow-x-auto my-2 text-neutral-900"><code>$1</code></pre>');

            // Inline code
            text = text.replace(/`([^`]+)`/g, '<code class="bg-neutral-100 px-1.5 py-0.5 rounded text-[10px] font-mono text-brand-800 border border-neutral-200">$1</code>');

            // Tables: parse markdown tables
            text = text.replace(/((?:\|[^\n]+\|\r?\n)+)/g, function(tableMatch) {
                const rows = tableMatch.trim().split(/\r?\n/).map(r => r.trim()).filter(Boolean);
                if (rows.length < 2) return tableMatch;

                let html = '<div class="overflow-x-auto my-2 rounded border border-neutral-200"><table class="min-w-full text-[11px] text-left">';
                let isHeader = true;

                for (let i = 0; i < rows.length; i++) {
                    const row = rows[i];
                    if (/^\|[\s\-:]+\|\s*$/.test(row)) {
                        isHeader = false;
                        continue;
                    }
                    const cells = row.split('|').slice(1, -1);
                    if (isHeader) {
                        html += '<thead class="bg-neutral-50 text-neutral-700 font-semibold border-b border-neutral-200"><tr>';
                        cells.forEach(c => html += `<th class="px-2.5 py-1.5">${c.trim()}</th>`);
                        html += '</tr></thead><tbody>';
                    } else {
                        html += '<tr class="border-b border-neutral-100 hover:bg-neutral-50">';
                        cells.forEach(c => html += `<td class="px-2.5 py-1.5 text-neutral-800">${c.trim()}</td>`);
                        html += '</tr>';
                    }
                }
                html += '</tbody></table></div>';
                return html;
            });

            // Headers
            text = text.replace(/^###\s+(.+)$/gm, '<h5 class="text-xs font-semibold text-brand-800 mt-2.5 mb-1">$1</h5>');
            text = text.replace(/^##\s+(.+)$/gm, '<h4 class="text-xs font-semibold text-neutral-900 mt-3 mb-1.5 pb-1 border-b border-neutral-200">$1</h4>');
            text = text.replace(/^#\s+(.+)$/gm, '<h3 class="text-sm font-semibold text-neutral-900 mt-3.5 mb-2">$1</h3>');

            // Horizontal rules
            text = text.replace(/^---\s*$/gm, '<hr class="border-neutral-200 my-2">');

            // Bold & italic
            text = text.replace(/\*\*(.+?)\*\*/g, '<strong class="text-neutral-900 font-semibold">$1</strong>');
            text = text.replace(/\*(.+?)\*/g, '<em class="text-neutral-700">$1</em>');

            // Bullet points
            text = text.replace(/^\s*[\*\-]\s+(.+)$/gm, '<li class="ml-4 list-disc text-neutral-700">$1</li>');

            // Line breaks
            text = text.replace(/\n\n/g, '<div class="h-2"></div>');
            text = text.replace(/\n/g, '<br>');

            return text;
        }

        // 6. REAL-TIME IOT TELEMETRY SIMULATION & APEXCHARTS SYNCHRONIZATION (FR-05 & F-08)
        let iotInterval = null;
        let isIotActive = false;
        const MAX_CHART_POINTS = 60; // Clean sliding window for dynamic timeseries

        function toggleExportMenu(e) {
            if (e) e.stopPropagation();
            const menu = document.getElementById('exportMenuDropdown');
            if (menu) menu.classList.toggle('hidden');
        }

        document.addEventListener('click', function(e) {
            const container = document.getElementById('exportMenuContainer');
            const menu = document.getElementById('exportMenuDropdown');
            if (container && menu && !container.contains(e.target)) {
                menu.classList.add('hidden');
            }
        });

        function toggleIotStreaming() {
            isIotActive = !isIotActive;
            const led = document.getElementById('iotLed');
            const label = document.getElementById('iotLabel');
            const banner = document.getElementById('iotLiveBanner');

            if (isIotActive) {
                led.classList.remove('bg-neutral-400');
                led.classList.add('bg-emerald-600', 'animate-pulse');
                label.textContent = 'IoT Live: ON';
                banner.classList.remove('hidden');

                if (iotInterval) {
                    clearInterval(iotInterval);
                    iotInterval = null;
                }

                pollIotData();
                iotInterval = setInterval(pollIotData, 3000);
            } else {
                led.classList.add('bg-neutral-400');
                led.classList.remove('bg-emerald-600', 'animate-pulse');
                label.textContent = 'IoT Live: OFF';
                banner.classList.add('hidden');

                if (iotInterval) {
                    clearInterval(iotInterval);
                    iotInterval = null;
                }
            }
        }

        async function pollIotData() {
            try {
                const res = await fetch("{{ route('iot.stream', ['preset' => $preset]) }}");
                if (!res.ok) return;
                const data = await res.json();

                // Update Real-Time Widget Indicators
                const timeEl = document.getElementById('iotTime');
                const kwEl = document.getElementById('iotKw');
                const kvarEl = document.getElementById('iotKvar');
                const pfEl = document.getElementById('iotPf');

                if (timeEl) timeEl.textContent = (data.timestamp || '--:--:--') + ' WIB';
                if (kwEl) kwEl.textContent = Number(data.total_active_kw).toLocaleString('id-ID');
                if (kvarEl) kvarEl.textContent = Number(data.total_reactive_kvar).toLocaleString('id-ID');
                if (pfEl) pfEl.textContent = data.aggregate_cos_phi;

                // Dynamic Live IoT ApexCharts Timeseries Synchronization (F-08)
                if (chart && data.total_active_kw !== undefined) {
                    const liveTimestamp = data.timestamp || new Date().toLocaleTimeString('id-ID');
                    const liveActiveKw = Number(data.total_active_kw);

                    // Compute baseline reference point (based on last baseline or EnB regression reference)
                    const lastBaseline = baselineKwhData.length > 0 
                        ? baselineKwhData[baselineKwhData.length - 1] 
                        : Math.round(liveActiveKw * 0.92 * 10) / 10;
                    const liveBaseline = Number(lastBaseline);

                    // Append live telemetry point to sliding window
                    chartTimes.push(liveTimestamp);
                    actualKwhData.push(liveActiveKw);
                    baselineKwhData.push(liveBaseline);

                    // Accumulate CUSUM delta if CUSUM is tracked
                    if (cusumData.length > 0) {
                        const delta = liveActiveKw - liveBaseline;
                        const lastCusum = cusumData[cusumData.length - 1] ?? 0;
                        cusumData.push(Math.round((lastCusum + delta) * 10) / 10);
                    }

                    // Sliding window: maintain bounded length to prevent memory leakage
                    while (chartTimes.length > MAX_CHART_POINTS) {
                        chartTimes.shift();
                        actualKwhData.shift();
                        baselineKwhData.shift();
                        if (cusumData.length > 0) {
                            cusumData.shift();
                        }
                    }

                    // Dynamically update ApexCharts series & axis categories
                    chart.updateSeries(getTimeseriesSeries(), false);
                    chart.updateOptions({
                        xaxis: {
                            categories: chartTimes
                        }
                    }, false, false);
                }
            } catch (e) {
                console.error("IoT streaming polling error", e);
            }
        }
    </script>

</body>
</html>
