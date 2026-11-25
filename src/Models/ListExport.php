<?php

declare(strict_types=1);

namespace Zak\Lists\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $user_id
 * @property string $list
 * @property string $filename
 * @property string $format xlsx|csv
 * @property array<int, string>|null $columns
 * @property array<string, mixed>|null $request_data
 * @property int $total_rows
 * @property int $processed_rows
 * @property string $status pending|processing|done|failed|cancelled
 * @property string|null $filepath
 * @property string $disk
 * @property string|null $error_message
 * @property Carbon|null $started_at
 * @property Carbon|null $finished_at
 * @property Carbon|null $seen_at
 * @property Carbon $created_at
 * @property Carbon $updated_at
 * @property-read int $progress_percent
 */
class ListExport extends Model
{
    public const STATUS_PENDING = 'pending';

    public const STATUS_PROCESSING = 'processing';

    public const STATUS_DONE = 'done';

    public const STATUS_FAILED = 'failed';

    public const STATUS_CANCELLED = 'cancelled';

    public const FORMAT_XLSX = 'xlsx';

    public const FORMAT_CSV = 'csv';

    /** @var array<int, string> */
    public const FORMATS = [self::FORMAT_XLSX, self::FORMAT_CSV];

    /** @var array<int, string> */
    public const ACTIVE_STATUSES = [self::STATUS_PENDING, self::STATUS_PROCESSING];

    protected $table = '_list_exports';

    protected $fillable = [
        'user_id',
        'list',
        'filename',
        'format',
        'columns',
        'request_data',
        'total_rows',
        'processed_rows',
        'status',
        'filepath',
        'disk',
        'error_message',
        'started_at',
        'finished_at',
        'seen_at',
    ];

    protected $casts = [
        'user_id' => 'integer',
        'columns' => 'array',
        'request_data' => 'array',
        'total_rows' => 'integer',
        'processed_rows' => 'integer',
        'started_at' => 'datetime',
        'finished_at' => 'datetime',
        'seen_at' => 'datetime',
    ];

    public function isActive(): bool
    {
        return in_array($this->status, self::ACTIVE_STATUSES, true);
    }

    public function isFinished(): bool
    {
        return ! $this->isActive();
    }

    public function getProgressPercentAttribute(): int
    {
        if ($this->status === self::STATUS_DONE) {
            return 100;
        }

        if ($this->total_rows <= 0) {
            return 0;
        }

        return (int) min(99, floor($this->processed_rows / $this->total_rows * 100));
    }

    public function mimeType(): string
    {
        return $this->format === self::FORMAT_CSV
            ? 'text/csv; charset=UTF-8'
            : 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet';
    }

    /**
     * Active exports, or finished ones that the user has not seen yet, for the last 24 hours.
     *
     * @param  Builder<self>  $query
     * @return Builder<self>
     */
    public function scopeVisibleForUser(Builder $query, int $userId): Builder
    {
        return $query
            ->where('user_id', $userId)
            ->where('created_at', '>=', now()->subDay())
            ->whereNull('seen_at')
            ->orderByDesc('created_at');
    }
}
