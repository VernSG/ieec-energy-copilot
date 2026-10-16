<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

final class EnergyTelemetryLog extends Model
{
    use HasFactory;

    protected $table = 'energy_telemetry_logs';

    protected $fillable = [
        'regulatory_config_id',
        'preset_source',
        'timestamp',
        'panel_id',
        'category',
        'energy_kwh',
        'power_factor',
        'kvarh',
        'production_qty',
        'area_sqm',
        'shift_state',
    ];

    protected $casts = [
        'timestamp' => 'datetime',
        'energy_kwh' => 'float',
        'power_factor' => 'float',
        'kvarh' => 'float',
        'production_qty' => 'integer',
        'area_sqm' => 'float',
    ];

    public function regulatoryConfig(): BelongsTo
    {
        return $this->belongsTo(RegulatoryConfig::class, 'regulatory_config_id');
    }

    public function panel(): BelongsTo
    {
        return $this->belongsTo(SdpPanel::class, 'panel_id', 'panel_id');
    }
}
