<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

final class SdpPanel extends Model
{
    use HasFactory;

    protected $table = 'sdp_panels';

    protected $primaryKey = 'panel_id';

    public $incrementing = false;

    protected $keyType = 'string';

    protected $fillable = [
        'panel_id',
        'deskripsi',
        'category',
        'baseline_enb',
        'default_area_sqm',
    ];

    protected $casts = [
        'baseline_enb' => 'float',
        'default_area_sqm' => 'float',
    ];

    public function telemetryLogs(): HasMany
    {
        return $this->hasMany(EnergyTelemetryLog::class, 'panel_id', 'panel_id');
    }
}
