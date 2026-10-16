<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\EnergyTelemetryLog;
use App\Models\RegulatoryConfig;
use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;

final class EnergyTelemetryLogSeeder extends Seeder
{
    public function run(): void
    {
        $config = RegulatoryConfig::first();
        $configId = $config?->id;

        $presets = [
            'sukarela' => storage_path('app/presets/preset_pilot_plant_sukarela.csv'),
            'wajib' => storage_path('app/presets/preset_industrial_plant_wajib.csv'),
        ];

        foreach ($presets as $source => $filePath) {
            if (! File::exists($filePath)) {
                // Check root directory fallback
                $fallback = base_path(basename($filePath));
                if (File::exists($fallback)) {
                    File::ensureDirectoryExists(dirname($filePath));
                    File::copy($fallback, $filePath);
                } else {
                    $this->command?->warn("Preset file not found: {$filePath}");

                    continue;
                }
            }

            $this->command?->info("Ingesting {$source} from {$filePath}...");

            // Delete existing records for this preset_source to make seeder idempotent
            EnergyTelemetryLog::where('preset_source', $source)->delete();

            $handle = fopen($filePath, 'r');
            if ($handle === false) {
                continue;
            }

            $headers = fgetcsv($handle);
            if (! $headers) {
                fclose($handle);

                continue;
            }

            // Normalize headers (trim BOM or whitespace)
            $headers = array_map(fn ($h) => trim(str_replace("\xEF\xBB\xBF", '', $h)), $headers);

            $batch = [];
            $batchSize = 500;
            $now = Carbon::now();

            while (($row = fgetcsv($handle)) !== false) {
                if (count($row) < count($headers)) {
                    continue;
                }

                $data = array_combine($headers, $row);

                $batch[] = [
                    'regulatory_config_id' => $configId,
                    'preset_source' => $source,
                    'timestamp' => Carbon::parse($data['timestamp'])->format('Y-m-d H:i:s'),
                    'panel_id' => $data['panel_id'],
                    'category' => $data['category'],
                    'energy_kwh' => (float) $data['energy_kwh'],
                    'power_factor' => (float) $data['power_factor'],
                    'kvarh' => (float) $data['kvarh'],
                    'production_qty' => (int) $data['production_qty'],
                    'area_sqm' => (float) $data['area_sqm'],
                    'shift_state' => $data['shift_state'] ?? 'OPERATIONAL',
                    'created_at' => $now,
                    'updated_at' => $now,
                ];

                if (count($batch) >= $batchSize) {
                    DB::table('energy_telemetry_logs')->insert($batch);
                    $batch = [];
                }
            }

            if (! empty($batch)) {
                DB::table('energy_telemetry_logs')->insert($batch);
            }

            fclose($handle);
            $totalCount = EnergyTelemetryLog::where('preset_source', $source)->count();
            $this->command?->info("Completed {$source}: {$totalCount} rows ingested.");
        }
    }
}
