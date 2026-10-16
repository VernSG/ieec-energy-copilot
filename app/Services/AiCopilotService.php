<?php

declare(strict_types=1);

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

final class AiCopilotService
{
    /**
     * Generate Pre-computed Executive Audit Narrative based on deterministic payload.
     */
    public function generateExecutiveAuditNarrative(array $payload): string
    {
        $meta = $payload['audit_metadata'];
        $kepatuhan = $payload['kepatuhan_manajemen_energi'];
        $reg = $payload['konfigurasi_regulasi'];
        $panels = $payload['analisis_ike_per_panel'];
        $finansial = $payload['temuan_pemborosan_dan_finansial'];
        $rekomendasi = $payload['klasifikasi_rekomendasi_audit_esdm'];

        $isWajib = str_contains($kepatuhan['status_kepatuhan'], 'WAJIB');
        $badgeClass = $isWajib ? 'bg-amber-50 text-amber-800 border-amber-200' : 'bg-emerald-50 text-emerald-800 border-emerald-200';

        $html = '<div class="space-y-4 text-xs leading-relaxed text-slate-700">';

        // Header Section
        $html .= '<div class="p-3.5 rounded-lg border border-slate-200 bg-slate-50/80">';
        $html .= '<div class="flex items-center justify-between mb-2">';
        $html .= '<span class="font-semibold text-[#243247] uppercase tracking-wider text-[11px]">Ringkasan Eksekutif Audit Energi</span>';
        $html .= '<span class="px-2 py-0.5 rounded text-[10px] font-mono font-medium border '.$badgeClass.'">'.($isWajib ? 'WAJIB MANAJEMEN ENERGI' : 'MANAJEMEN ENERGI SUKARELA').'</span>';
        $html .= '</div>';
        $html .= '<p class="text-slate-800">Fasilitas: <strong class="text-slate-900 font-semibold">'.e($meta['facility_name']).'</strong></p>';
        $html .= '<p class="text-slate-500 mt-0.5 text-[11px]">Periode: <span class="font-mono text-slate-700">'.e($meta['periode_audit']).'</span> (30 Hari) • Acuan: <strong class="text-slate-700">PP 33/2023</strong> &amp; <strong class="text-slate-700">Permen ESDM 8/2025</strong></p>';
        $html .= '</div>';

        // Section 1: Evaluasi Kepatuhan
        $html .= '<div class="space-y-2">';
        $html .= '<h4 class="font-semibold text-slate-900 flex items-center gap-1.5"><span class="w-1.5 h-1.5 rounded-full bg-[#4F83E3]"></span> 1. Evaluasi Kepatuhan Regulasi &amp; Kinerja IKE</h4>';
        $html .= '<p class="text-slate-600">Berdasarkan kalkulasi deterministik, konsumsi listrik bulanan tercatat <strong class="font-mono text-slate-900">'.number_format($kepatuhan['total_listrik_bulanan_kwh'], 0, ',', '.').' kWh</strong> ('.number_format($kepatuhan['konsumsi_toe_bulanan'], 2, ',', '.').' TOE/bln). Proyeksi konsumsi tahunan adalah <strong class="font-mono text-slate-900">'.number_format($kepatuhan['total_konsumsi_energi_tahunan_toe'], 2, ',', '.').' TOE/tahun</strong> terhadap ambang batas 4.000 TOE/tahun.</p>';

        $html .= '<div class="text-slate-700 bg-blue-50/60 p-2.5 rounded-lg border border-blue-100 text-[11px]"><strong class="text-[#243247]">Implikasi Yuridis:</strong> '.e($kepatuhan['implikasi_hukum']).'</div>';

        $html .= '<div class="mt-2 space-y-1.5">';
        $html .= '<p class="text-slate-700 font-medium text-[11px]">Diagnostik Lini Produksi &amp; Utilitas:</p>';
        $html .= '<ul class="list-disc list-inside space-y-1 text-slate-600 pl-1 text-[11px]">';

        foreach ($panels as $panel) {
            $cat = $panel['kategori'] ?? 'PRODUCTION';
            $pId = $panel['panel_id'];
            $desc = $panel['deskripsi'] ?? $pId;

            if ($cat === 'PRODUCTION') {
                $ike = number_format($panel['ike_aktual'] ?? 0.0, 2, ',', '.');
                $enb = number_format($panel['baseline_enb'] ?? 0.35, 2, ',', '.');
                $dev = (float) ($panel['deviasi_enpi_pct'] ?? 0.0);
                $devFormatted = ($dev > 0 ? '+' : '').number_format($dev, 2, ',', '.').'%';
                $e0 = number_format($panel['base_load_e0_kwh'] ?? 0.0, 1, ',', '.');
                $cosPhi = number_format($panel['cos_phi_rata_rata'] ?? 0.85, 2, ',', '.');
                $colorClass = $dev > 15.0 ? 'text-rose-700' : ($dev > 5.0 ? 'text-amber-700' : 'text-emerald-700');

                $wbpText = '';
                if (($panel['panel_id'] === 'SDP-02_UTILITY' || str_contains($panel['panel_id'], 'UTILITY')) && ($finansial['kwh_wbp_bisa_geser'] ?? 0) > 0) {
                    $wbpText = ' Beban puncak pada jam WBP ('.e($reg['jam_wbp_mulai']).'–'.e($reg['jam_wbp_selesai']).') terkena pengali faktor $K = '.number_format($reg['faktor_k_wbp'], 2, ',', '.').'$.';
                }

                $html .= '<li><strong class="'.$colorClass.'">'.e($pId).' ('.e($desc).'):</strong> IKE aktual <span class="font-mono text-slate-900">'.$ike.' kWh/batch</span> (<strong class="'.$colorClass.'">'.$devFormatted.'</strong> vs baseline EnB '.$enb.'). Base Load $E_0 = '.$e0.'$ kW dan faktor daya rata-rata <span class="font-mono text-slate-800">&cos;&phi; = '.$cosPhi.'</span>.'.$wbpText.'</li>';
            } else {
                $ike = number_format($panel['ike_aktual'] ?? 0.0, 2, ',', '.');
                $status = $panel['status_evaluasi'] ?? 'EFISIEN';
                $colorClass = str_contains($status, 'BOROS') ? 'text-rose-700' : (str_contains($status, 'CUKUP') ? 'text-amber-700' : 'text-emerald-700');
                $area = number_format($panel['area_sqm'] ?? 0.0, 0, ',', '.');

                $html .= '<li><strong class="'.$colorClass.'">'.e($pId).' ('.e($desc).'):</strong> IKE bangunan <span class="font-mono text-slate-900">'.$ike.' kWh/m&sup2;/bln</span> (luas '.$area.' m&sup2;) kategori <span class="'.$colorClass.' font-medium">'.e($status).'</span> sesuai acuan standar.</li>';
            }
        }

        $html .= '</ul>';
        $html .= '</div>';
        $html .= '</div>';

        // Section 2: Rekomendasi Preskriptif 4 Payback
        $html .= '<div class="space-y-2 pt-3 border-t border-slate-200">';
        $html .= '<h4 class="font-semibold text-slate-900 flex items-center gap-1.5"><span class="w-1.5 h-1.5 rounded-full bg-[#239B77]"></span> 2. Matriks Rekomendasi Preskriptif (Permen ESDM 8/2025)</h4>';
        $html .= '<div class="space-y-2.5">';

        foreach ($rekomendasi as $rek) {
            $badgeBg = match ($rek['kategori_investasi']) {
                'No-Cost' => 'bg-emerald-50 border-emerald-200 text-emerald-800',
                'Low-Cost' => 'bg-blue-50 border-blue-200 text-blue-800',
                'Medium-Cost' => 'bg-amber-50 border-amber-200 text-amber-800',
                'High-Cost' => 'bg-rose-50 border-rose-200 text-rose-800',
                default => 'bg-slate-50 border-slate-200 text-slate-800',
            };

            $html .= '<div class="p-3 rounded-lg border border-slate-200 bg-white hover:border-slate-300 transition shadow-xs space-y-1">';
            $html .= '<div class="flex items-center justify-between text-[11px]">';
            $html .= '<span class="px-1.5 py-0.5 rounded text-[10px] font-semibold tracking-wide uppercase border '.$badgeBg.'">'.e($rek['kategori_investasi']).' • '.e($rek['kriteria_payback']).'</span>';
            $html .= '<span class="font-mono font-semibold text-[#239B77]">Hemat: Rp '.number_format($rek['potensi_hemat_rp_per_bulan'], 0, ',', '.').'/bln</span>';
            $html .= '</div>';
            $html .= '<p class="font-semibold text-slate-900 text-xs mt-1">'.e($rek['judul_tindakan']).'</p>';
            $html .= '<p class="text-slate-600 text-[11px] leading-relaxed">'.e($rek['deskripsi_teknis']).'</p>';
            $html .= '</div>';
        }

        $html .= '</div>';
        $html .= '</div>';

        // Section 3: Ringkasan Finansial & Karbon
        $html .= '<div class="space-y-2 pt-3 border-t border-slate-200">';
        $html .= '<h4 class="font-semibold text-slate-900 flex items-center gap-1.5"><span class="w-1.5 h-1.5 rounded-full bg-[#4F83E3]"></span> 3. Neraca Dampak Finansial &amp; Dekarbonisasi</h4>';
        $html .= '<div class="grid grid-cols-2 gap-2 text-[11px] font-mono">';
        $html .= '<div class="bg-slate-50 p-2.5 rounded-lg border border-slate-200">';
        $html .= '<span class="text-slate-500 block text-[10px] font-sans">Total Penghematan Biaya</span>';
        $html .= '<span class="text-[#239B77] font-bold text-sm">Rp '.number_format($finansial['total_potensi_hemat_rp'], 0, ',', '.').'</span><span class="text-slate-500 text-[10px]">/bln</span>';
        $html .= '</div>';
        $html .= '<div class="bg-slate-50 p-2.5 rounded-lg border border-slate-200">';
        $html .= '<span class="text-slate-500 block text-[10px] font-sans">Reduksi Emisi GRK</span>';
        $html .= '<span class="text-[#4F83E3] font-bold text-sm">-'.number_format($finansial['potensi_reduksi_co2_ton'], 2, ',', '.').'</span><span class="text-slate-500 text-[10px]"> tCO2e/bln</span>';
        $html .= '</div>';
        $html .= '</div>';

        $html .= '<div class="text-[10px] text-slate-600 bg-slate-50 p-2.5 rounded-lg border border-slate-200 leading-normal">';
        $html .= '<strong class="text-slate-800">Rincian Finansial:</strong> Eliminasi aktif Rp '.number_format($finansial['hemat_eliminasi_idle_dan_spike_rp'], 0, ',', '.').' + Load Shifting WBP Rp '.number_format($finansial['hemat_geser_wbp_rp'], 0, ',', '.').' + Penghapusan Penalti kVArh Rp '.number_format($finansial['biaya_kvarh_rp'], 0, ',', '.').'. Grid: <em class="text-slate-700">'.e($reg['nama_sistem_grid']).'</em> ('.number_format($reg['faktor_emisi_grid_tco2e_per_mwh'], 3, ',', '.').' tCO2e/MWh).';
        $html .= '</div>';

        // Disclaimer
        $html .= '<p class="text-[10px] text-slate-400 italic mt-2 border-t border-slate-200 pt-2">';
        $html .= '<strong class="text-slate-500">Catatan Profesional:</strong> IEEC Prototype Alpha adalah instrumen pendukung keputusan Manajer Energi dan tidak menggantikan kedudukan laporan audit formal yang ditandatangani oleh Auditor Energi Bersertifikat BNSP.';
        $html .= '</p>';

        $html .= '</div>';
        $html .= '</div>';

        return $html;
    }

    /**
     * Interactive Chat Endpoint with SumoPod / Gemini / OpenAI API or Deterministic Local Expert Fallback.
     */
    public function chat(string $question, array $payload, array $history = []): string
    {
        $sumopodApiKey = config('services.sumopod.api_key') ?: env('SUMOPOD_API') ?: env('SUMOPOD_API_KEY');
        $sumopodBaseUrl = rtrim((string) (config('services.sumopod.base_url') ?: env('SUMOPOD_BASE_URL', 'https://ai.sumopod.com/v1')), '/');
        $sumopodModel = (string) (config('services.sumopod.model') ?: env('SUMOPOD_MODEL', 'deepseek-v4-flash-0731:netra'));

        $geminiApiKey = config('services.gemini.api_key') ?: env('GEMINI_API_KEY');
        $openAiApiKey = config('services.openai.api_key') ?: env('OPENAI_API_KEY');

        // 1. Primary: SumoPod AI (DeepSeek model)
        if (! empty($sumopodApiKey)) {
            try {
                $systemInstruction = $this->buildSystemInstruction($payload);
                $messages = [
                    ['role' => 'system', 'content' => $systemInstruction],
                ];

                foreach ($history as $h) {
                    if (isset($h['role'], $h['content'])) {
                        $role = ($h['role'] === 'assistant' || $h['role'] === 'model') ? 'assistant' : 'user';
                        $messages[] = [
                            'role' => $role,
                            'content' => (string) $h['content'],
                        ];
                    }
                }

                $messages[] = [
                    'role' => 'user',
                    'content' => $question,
                ];

                $response = Http::timeout(30)
                    ->withToken($sumopodApiKey)
                    ->post("{$sumopodBaseUrl}/chat/completions", [
                        'model' => $sumopodModel,
                        'messages' => $messages,
                        'max_tokens' => 1200,
                        'temperature' => 0.2, // Strict low temperature for zero hallucination
                    ]);

                if ($response->successful()) {
                    $json = $response->json();
                    $text = $json['choices'][0]['message']['content'] ?? null;
                    if (! empty($text)) {
                        return trim($text);
                    }
                } else {
                    Log::warning('SumoPod API returned HTTP '.$response->status().': '.$response->body());
                }
            } catch (\Throwable $e) {
                Log::warning('SumoPod API call failed, falling back to secondary providers: '.$e->getMessage());
            }
        }

        // 2. Secondary fallback: Gemini API
        if (! empty($geminiApiKey)) {
            try {
                $systemInstruction = $this->buildSystemInstruction($payload);
                $endpoint = "https://generativelanguage.googleapis.com/v1beta/models/gemini-1.5-flash:generateContent?key={$geminiApiKey}";

                $contents = [];
                // Include conversation history
                foreach ($history as $h) {
                    $contents[] = [
                        'role' => ($h['role'] === 'user' ? 'user' : 'model'),
                        'parts' => [['text' => $h['content']]],
                    ];
                }
                // Append current user message
                $contents[] = [
                    'role' => 'user',
                    'parts' => [['text' => $question]],
                ];

                $response = Http::timeout(12)->post($endpoint, [
                    'system_instruction' => [
                        'parts' => [['text' => $systemInstruction]],
                    ],
                    'contents' => $contents,
                    'generationConfig' => [
                        'temperature' => 0.2, // Strict low temperature for zero hallucination
                        'maxOutputTokens' => 1024,
                    ],
                ]);

                if ($response->successful()) {
                    $json = $response->json();
                    $text = $json['candidates'][0]['content']['parts'][0]['text'] ?? null;
                    if ($text) {
                        return trim($text);
                    }
                }
            } catch (\Throwable $e) {
                Log::warning('Gemini API call failed, activating deterministic expert fallback: '.$e->getMessage());
            }
        }

        // If OpenAI API key is available
        if (! empty($openAiApiKey)) {
            try {
                $systemInstruction = $this->buildSystemInstruction($payload);
                $messages = [['role' => 'system', 'content' => $systemInstruction]];
                foreach ($history as $h) {
                    $messages[] = ['role' => $h['role'], 'content' => $h['content']];
                }
                $messages[] = ['role' => 'user', 'content' => $question];

                $response = Http::timeout(12)
                    ->withToken($openAiApiKey)
                    ->post('https://api.openai.com/v1/chat/completions', [
                        'model' => 'gpt-4o-mini',
                        'messages' => $messages,
                        'temperature' => 0.2,
                    ]);

                if ($response->successful()) {
                    $json = $response->json();
                    $text = $json['choices'][0]['message']['content'] ?? null;
                    if ($text) {
                        return trim($text);
                    }
                }
            } catch (\Throwable $e) {
                Log::warning('OpenAI API call failed, activating deterministic expert fallback: '.$e->getMessage());
            }
        }

        // Deterministic Industrial Knowledge Rule Engine (Zero Hallucination Offline Mode)
        return $this->deterministicKnowledgeFallback($question, $payload);
    }

    /**
     * Build the rigid System Prompt with Context Injection per PRD Section 7.2.
     */
    private function buildSystemInstruction(array $payload): string
    {
        $payloadJson = json_encode($payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);

        return <<<PROMPT
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
1. Evaluasi Kinerja IKE & Kepatuhan Regulasi
2. Rekomendasi Tindakan Preskriptif Terklasifikasi (Permen ESDM 8/2025: No-Cost, Low-Cost, Medium-Cost, High-Cost)
3. Ringkasan Dampak Finansial & Dekarbonisasi

KONTEKS DETERMINISTIK TERKINI (JSON PAYLOAD):
```json
{$payloadJson}
```
PROMPT;
    }

    /**
     * Deterministic rule-based response engine when offline or API key is absent.
     */
    private function deterministicKnowledgeFallback(string $question, array $payload): string
    {
        $q = strtolower($question);
        $meta = $payload['audit_metadata'];
        $fin = $payload['temuan_pemborosan_dan_finansial'];
        $kep = $payload['kepatuhan_manajemen_energi'];
        $reg = $payload['konfigurasi_regulasi'];
        $panels = $payload['analisis_ike_per_panel'];
        $rekomendasi = $payload['klasifikasi_rekomendasi_audit_esdm'];
        $facility = $meta['facility_name'] ?? 'Fasilitas Industri';

        $prodPanels = collect($panels)->filter(fn ($p) => ($p['kategori'] ?? '') === 'PRODUCTION');
        $mainProd = $prodPanels->first() ?? ($panels[0] ?? []);
        $mainProdId = $mainProd['panel_id'] ?? 'SDP-01';
        $mainProdDesc = $mainProd['deskripsi'] ?? 'Lini Produksi';
        $e0 = number_format($mainProd['base_load_e0_kwh'] ?? 21.8, 1, ',', '.');
        $cosPhi = number_format($mainProd['cos_phi_rata_rata'] ?? 0.81, 2, ',', '.');

        if (str_contains($q, 'base load') || str_contains($q, 'e0') || str_contains($q, 'line a') || str_contains($q, 'pirolisis') || str_contains($q, 'sdp-01') || str_contains($q, 'motor')) {
            $lowCostRec = collect($rekomendasi)->firstWhere('kategori_investasi', 'Low-Cost');
            $savingRp = number_format($lowCostRec['potensi_hemat_rp_per_bulan'] ?? $fin['hemat_eliminasi_idle_dan_spike_rp'], 0, ',', '.');
            $idleKwh = number_format($lowCostRec['potensi_hemat_kwh'] ?? ($fin['idle_load_waste_kwh'] ?? 2450), 0, ',', '.');
            $payback = $lowCostRec['payback_period_tahun'] ?? 0.13;
            $paybackStr = $payback ? number_format($payback * 12, 1, ',', '.').' bulan' : 'cepat';

            return "Berdasarkan pemodelan regresi OLS ISO 50001 pada {$mainProdId} ({$mainProdDesc}), ditemukan **Base Load E0 sebesar {$e0} kW** saat motor beroperasi tanpa beban umpan (termasuk pada jam istirahat dan pergantian shift).\n\n".
                   "**Langkah Preskriptif Rekomendasi (Permen ESDM 8/2025):**\n".
                   "1. **Low-Cost (< 2 Tahun):** Pasang modul *Auto-Standby Timer & Contactor Interlock* untuk mematikan motor secara otomatis saat status break, mengeliminasi beban siaga **{$idleKwh} kWh/bulan** (Hemat **Rp {$savingRp}/bulan**, Payback ~{$paybackStr}).\n".
                   '2. **High-Cost (> 4 Tahun):** Retrofit motor induksi konvensional dengan motor efisiensi tinggi kelas **IE3/IE4** dan pasang Variable Speed Drive (VSD) untuk modulasi kecepatan umpan.';
        }

        if (str_contains($q, 'cos phi') || str_contains($q, 'kvarh') || str_contains($q, 'reaktif') || str_contains($q, 'kapasitor') || str_contains($q, 'denda') || str_contains($q, 'penalti')) {
            $kvarhBillable = number_format($fin['kvarh_kena_biaya'], 0, ',', '.');
            $kvarhCost = number_format($fin['biaya_kvarh_rp'], 0, ',', '.');
            $medRec = collect($rekomendasi)->firstWhere('kategori_investasi', 'Medium-Cost');
            $medPayback = $medRec['payback_period_tahun'] ?? 2.47;
            $medPaybackStr = $medPayback ? number_format($medPayback, 2, ',', '.').' tahun' : '2-4 tahun';

            return "Berdasarkan data telemetri bulanan, faktor daya rata-rata {$mainProdId} tercatat **&cos;&phi; = {$cosPhi}**, berada di bawah ambang batas minimum PLN (".number_format($reg['batas_cos_phi'], 2, ',', '.')." sesuai regulasi). Hal ini menimbulkan kelebihan daya reaktif kena denda sebesar **{$kvarhBillable} kVArh** atau penalti rekening listrik **Rp {$kvarhCost}/bulan**.\n\n".
                   "**Rekomendasi Preskriptif (Permen ESDM 8/2025):**\n".
                   "Pasang unit **Automatic Power Factor Controller (APFC) Kapasitor Bank** pada {$mainProdId}. Tindakan ini akan menaikkan cos phi menjadi &ge; 0,88, sepenuhnya menghapus tagihan penalti kVArh dengan periode pengembalian modal sekitar **{$medPaybackStr}**.";
        }

        if (str_contains($q, 'wbp') || str_contains($q, 'geser') || str_contains($q, 'shift') || str_contains($q, 'rotary dryer') || str_contains($q, 'kompresor')) {
            $wbpKwh = number_format($fin['kwh_wbp_bisa_geser'], 0, ',', '.');
            $wbpSaving = number_format($fin['hemat_geser_wbp_rp'], 0, ',', '.');
            $wbpMulai = e($reg['jam_wbp_mulai']);
            $wbpSelesai = e($reg['jam_wbp_selesai']);
            $kFactor = number_format($reg['faktor_k_wbp'], 2, ',', '.');
            $tarifWbp = number_format($reg['tarif_wbp_rp_kwh'], 2, ',', '.');
            $tarifLwbp = number_format($reg['tarif_lwbp_rp_kwh'], 2, ',', '.');

            return "Fasilitas tercatat mengonsumsi beban yang dapat digeser sebesar **{$wbpKwh} kWh** pada jam beban puncak WBP ({$wbpMulai}–{$wbpSelesai} WIB). Sesuai tarif PLN, jam WBP dikenakan faktor pengali K = {$kFactor} (Rp {$tarifWbp}/kWh vs LWBP Rp {$tarifLwbp}/kWh).\n\n".
                   "**Rekomendasi Preskriptif (No-Cost, Payback 0 Tahun / Instan):**\n".
                   "Lakukan *Load Shifting* dengan menjadwalkan ulang operasional peralatan beban tinggi ke jam LWBP (di luar {$wbpMulai}–{$wbpSelesai} WIB). Tindakan ini menghasilkan efisiensi biaya **Rp {$wbpSaving}/bulan** tanpa biaya investasi modal (*Capex = 0*).\n\n".
                   '*Catatan Integritas Fisik:* Pergeseran jam beban murni menghemat biaya finansial akibat disparitas tarif, namun tidak mengurangi jumlah fisik kWh maupun emisi karbon.';
        }

        if (str_contains($q, 'wajib') || str_contains($q, 'regulasi') || str_contains($q, 'pp 33') || str_contains($q, 'permen esdm 8') || str_contains($q, 'status')) {
            $totalToe = number_format($kep['total_konsumsi_energi_tahunan_toe'], 2, ',', '.');
            $ambang = number_format($reg['ambang_wajib_toe'], 0, ',', '.');

            return "**Evaluasi Kepatuhan Yuridis (PP No. 33 Tahun 2023 & Permen ESDM No. 8 Tahun 2025):**\n\n".
                   "- **Total Konsumsi Tahunan:** {$totalToe} TOE/tahun (Ambang wajib industri: {$ambang} TOE/tahun).\n".
                   '- **Status Fasilitas:** **'.$kep['status_kepatuhan']."**\n".
                   '- **Konsekuensi Hukum:** '.$kep['implikasi_hukum']."\n\n".
                   '*Rujukan Regulasi Resmi:* Mengacu secara presisi pada PP 33/2023 dan Permen ESDM 8/2025 (mencabut Permen ESDM 14/2012).';
        }

        // Default comprehensive summary
        $totalToe = number_format($kep['total_konsumsi_energi_tahunan_toe'], 2, ',', '.');
        $totalHematRp = number_format($fin['total_potensi_hemat_rp'], 0, ',', '.');
        $co2 = number_format($fin['potensi_reduksi_co2_ton'], 2, ',', '.');
        $hematAktif = number_format($fin['hemat_eliminasi_idle_dan_spike_rp'], 0, ',', '.');
        $totalHematKwh = number_format($fin['total_potensi_hemat_kwh'], 0, ',', '.');
        $hematWbp = number_format($fin['hemat_geser_wbp_rp'], 0, ',', '.');
        $biayaKvarh = number_format($fin['biaya_kvarh_rp'], 0, ',', '.');

        return "**Ringkasan Analitik Deterministik Copilot (Fasilitas {$facility}):**\n\n".
               "1. **Status Regulasi:** {$kep['status_kepatuhan']} dengan total energi primer **{$totalToe} TOE/tahun**.\n".
               "2. **Potensi Efisiensi Total:** **Rp {$totalHematRp}/bulan** dan reduksi emisi **{$co2} Ton CO2e/bulan**.\n".
               "   - Eliminasi Idle & Spike: Rp {$hematAktif}/bln ({$totalHematKwh} kWh)\n".
               "   - Load Shifting WBP -> LWBP: Rp {$hematWbp}/bln\n".
               "   - Denda kVArh Terhapus: Rp {$biayaKvarh}/bln\n".
               "3. **Tindakan Prioritas:** Fokuskan penanganan pada modul Auto-Standby {$mainProdId} dan optimasi faktor daya cos phi < 0,85.";
    }
}
