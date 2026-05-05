<?php

declare(strict_types=1);

namespace Zak\Lists\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

/**
 * @property int $id
 * @property int $user_id
 * @property string $list
 * @property string $filename
 * @property string $status  pending|done|failed
 * @property string|null $filepath
 * @property string $disk
 * @property string|null $error_message
 * @property \Illuminate\Support\Carbon|null $seen_at
 * @property \Illuminate\Support\Carbon $created_at
 * @property \Illuminate\Support\Carbon $updated_at
 */
class ListExport extends Model
{
    public const STATUS_PENDING = 'pending';

    public const STATUS_DONE = 'done';

    public const STATUS_FAILED = 'failed';

    protected $table = '_list_exports';

    protected $fillable = [
        'user_id',
        'list',
        'filename',
        'status',
        'filepath',
        'disk',
        'error_message',
        'seen_at',
    ];

    protected $casts = [
        'seen_at' => 'datetime',
    ];

    /**
     * Pending or done (but not yet seen) exports for the given user, last 24 hours.
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

