<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

final class RegulatoryConfigLog extends Model
{
    use HasFactory;

    protected $table = 'regulatory_config_logs';

    protected $fillable = [
        'regulatory_config_id',
        'versi_regulasi',
        'parameter_name',
        'old_value',
        'new_value',
        'changed_by',
        'notes',
    ];

    public function regulatoryConfig(): BelongsTo
    {
        return $this->belongsTo(RegulatoryConfig::class, 'regulatory_config_id');
    }
}
