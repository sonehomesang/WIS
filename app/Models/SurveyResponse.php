<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SurveyResponse extends Model
{
    protected $fillable = [
        'unit_id', 'user_id', 'frequency',
        'wh_receiving', 'wh_condition', 'wh_storage',
        'ie_customs', 'ie_communication', 'ie_urgent',
        'overall_wh', 'overall_ie',
        'doing_well', 'improve',
    ];

    /** Rating question keys grouped by service area (label lookup lives in the views/lang). */
    public const WH_FIELDS = ['wh_receiving', 'wh_condition', 'wh_storage'];

    public const IE_FIELDS = ['ie_customs', 'ie_communication', 'ie_urgent'];

    public const OVERALL_FIELDS = ['overall_wh', 'overall_ie'];

    /** All rating columns (used for "at least one rating" validation + all-service distribution). */
    public const RATING_FIELDS = [
        'wh_receiving', 'wh_condition', 'wh_storage',
        'ie_customs', 'ie_communication', 'ie_urgent',
        'overall_wh', 'overall_ie',
    ];

    public const FREQUENCIES = ['daily', 'weekly', 'monthly', 'occasionally'];

    protected function casts(): array
    {
        return [
            'wh_receiving' => 'integer', 'wh_condition' => 'integer', 'wh_storage' => 'integer',
            'ie_customs' => 'integer', 'ie_communication' => 'integer', 'ie_urgent' => 'integer',
            'overall_wh' => 'integer', 'overall_ie' => 'integer',
        ];
    }

    public function unit(): BelongsTo
    {
        return $this->belongsTo(Unit::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /** Dashboard filters. */
    public function scopeFilter(Builder $q, array $f): Builder
    {
        return $q
            ->when($f['frequency'] ?? null, fn ($q, $v) => $q->where('frequency', $v))
            ->when($f['unit_id'] ?? null, fn ($q, $v) => $q->where('unit_id', $v))
            ->when($f['from'] ?? null, fn ($q, $v) => $q->whereDate('created_at', '>=', $v))
            ->when($f['to'] ?? null, fn ($q, $v) => $q->whereDate('created_at', '<=', $v));
    }
}
