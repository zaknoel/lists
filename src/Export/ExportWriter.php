<?php

declare(strict_types=1);

namespace Zak\Lists\Export;

use Closure;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Zak\Lists\Component;
use Zak\Lists\Fields\Field;

/**
 * Streams a list query into a file on a storage disk.
 */
interface ExportWriter
{
    /**
     * Writes all rows of $query to $storedPath on $disk and returns the path.
     *
     * $onProgress receives the number of processed rows after every chunk and must
     * return false to abort the export. On abort the temporary file is discarded and
     * null is returned.
     *
     * @param  Builder<Model>  $query
     * @param  array<int, Field>  $fields
     * @param  (Closure(int): bool)|null  $onProgress
     */
    public function write(
        Component $component,
        Builder $query,
        array $fields,
        string $list,
        string $storedPath,
        string $disk,
        int $chunkSize = 500,
        ?Closure $onProgress = null,
    ): ?string;
}
