<?php

declare(strict_types=1);

namespace Zak\Lists\Requests;

use Illuminate\Validation\Rule;
use Zak\Lists\Fields\Field;
use Zak\Lists\Services\ExportService;

/**
 * Validates the export modal submission: chosen columns, file format and the current list filters.
 */
class StartExportRequest extends BaseListRequest
{
    public function authorize(): bool
    {
        return $this->component()->userCanViewAny();
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        $allowedColumns = array_map(
            static fn (Field $field): string => $field->attribute,
            app(ExportService::class)->exportableFields($this->component())
        );

        return [
            'format' => ['required', 'string', Rule::in((array) config('lists.export_formats', ['xlsx', 'csv']))],
            'columns' => ['required', 'array', 'min:1'],
            'columns.*' => ['required', 'string', Rule::in($allowedColumns)],
            'query' => ['nullable', 'string', 'max:8000'],
        ];
    }

    /**
     * Active list filters, parsed from the query string of the page the modal was opened on.
     *
     * @return array<string, mixed>
     */
    public function filters(): array
    {
        parse_str((string) $this->input('query', ''), $params);

        return app(ExportService::class)->extractFilters($params);
    }
}
