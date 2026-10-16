<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

final class AuditReport extends Model
{
    use HasFactory;

    protected $table = 'audit_reports';

    protected $fillable = [
        'regulatory_config_id',
        'preset_source',
        'total_kwh',
        'total_toe_tahunan',
        'status_kepatuhan',
        'payload_json',
        'ai_narrative_html',
    ];

    protected $casts = [
        'total_kwh' => 'float',
        'total_toe_tahunan' => 'float',
        'payload_json' => 'array',
    ];

    public function regulatoryConfig(): BelongsTo
    {
        return $this->belongsTo(RegulatoryConfig::class, 'regulatory_config_id');
    }
}
