<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

final class RegulatoryConfig extends Model
{
    use HasFactory;

    protected $table = 'regulatory_configs';

    protected $fillable = [
        'versi_regulasi',
        'tarif_lwbp_rp_kwh',
        'faktor_k_wbp',
        'tarif_wbp_rp_kwh',
        'jam_wbp_mulai',
        'jam_wbp_selesai',
        'tarif_kvarh_rp',
        'batas_cos_phi',
        'rasio_kvarh_bebas',
        'faktor_emisi_grid',
        'ambang_wajib_toe',
    ];

    protected $casts = [
        'tarif_lwbp_rp_kwh' => 'float',
        'faktor_k_wbp' => 'float',
        'tarif_wbp_rp_kwh' => 'float',
        'tarif_kvarh_rp' => 'float',
        'batas_cos_phi' => 'float',
        'rasio_kvarh_bebas' => 'float',
        'faktor_emisi_grid' => 'float',
        'ambang_wajib_toe' => 'float',
    ];

    public function telemetryLogs(): HasMany
    {
        return $this->hasMany(EnergyTelemetryLog::class, 'regulatory_config_id');
    }

    public function auditReports(): HasMany
    {
        return $this->hasMany(AuditReport::class, 'regulatory_config_id');
    }

    public function configLogs(): HasMany
    {
        return $this->hasMany(RegulatoryConfigLog::class, 'regulatory_config_id');
    }
}
