<?php

namespace Zak\Lists\Jobs;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Auth;
use Maatwebsite\Excel\Facades\Excel;
use Throwable;
use Zak\Lists\Contracts\ComponentLoaderContract;
use Zak\Lists\Fields\Field;
use Zak\Lists\ListImport;
use Zak\Lists\Services\ExportService;
use Zak\Lists\Services\QueryService;

/**
 * Фоновый экспорт списка в Excel.
 *
 * Компонент пересоздаётся из файла внутри job, поскольку PHP-замыкания
 * не подлежат сериализации. В очередь передаются только примитивные данные.
 */
class ExportListJob implements ShouldQueue
{
    use Queueable;

    /** @var array<string, mixed> */
    public readonly array $requestData;

    /**
     * @param  string  $list  Имя файла компонента (без расширения)
     * @param  array<string, mixed>  $requestData  Данные запроса для применения фильтров
     * @param  int  $userId  ID аутентифицированного пользователя
     * @param  string  $filename  Базовое имя Excel-файла (без расширения)
     */
    public function __construct(
        public readonly string $list,
        array $requestData,
        public readonly int $userId,
        public readonly string $filename,
    ) {
        // Очищаем нерелевантные HTTP-параметры DataTables из данных запроса.
        $exportKeys = ['excel', 'order', 'length', 'start', 'draw', 'search', 'columns', '_token'];
        $this->requestData = array_diff_key($requestData, array_flip($exportKeys));
    }

    public function handle(
        ComponentLoaderContract $loader,
        QueryService $queryService,
        ExportService $exportService,
    ): void {
        $user = $this->resolveUser();

        if (! $user) {
            info('Zak.Lists.ExportListJob: пользователь #'.$this->userId.' не найден, задача пропущена.');

            return;
        }

        Auth::login($user);

        $component = $loader->resolve($this->list, true);

        $request = request()->duplicate(query: $this->requestData);

        $query = $queryService->buildIndexQuery($component, $request);

        $curSort = $component->options->value['curSort'] ?? ['id', 'desc'];

        $visibleColumns = $component->options->value['columns'] ?? [];

        /** @var array<int, Field> $fields */
        $fields = array_values(array_filter(
            $component->getFields(),
            fn (Field $field) => $field->show_in_index
                && ! $field->hide_on_export
                && (empty($visibleColumns) || in_array($field->attribute, $visibleColumns, false))
        ));

        foreach ($fields as $field) {
            $field->generateFilter($query);
        }

        $query->orderBy($curSort[0], $curSort[1]);

        $rows = $exportService->buildRowsFromQuery(
            $component,
            $query,
            $fields,
            $this->list,
            (int) config('lists.export_chunk_size', 500),
        );

        $disk = config('lists.export_disk', 'local');
        $path = trim(config('lists.export_path', 'exports'), '/');
        $storedPath = $path.'/'.$this->filename.'-'.now()->format('Ymd-His').'.xlsx';

        try {
            $prepared = $exportService->prepareExportData($rows, $fields);
            $importClass = config('lists.import_class', ListImport::class);

            Excel::store(new $importClass($prepared), $storedPath, $disk);
            info('Zak.Lists.ExportListJob: файл сохранён: '.$storedPath);
        } catch (Throwable $e) {
            info('Zak.Lists.ExportListJob: ошибка сохранения: '.$e->getMessage());

            if (isReportable($e)) {
                report('Zak.Lists.ExportListJob: '.$e->getMessage());
            }

            throw $e;
        }
    }

    /**
     * Находит пользователя по ID. Использует стандартный провайдер аутентификации.
     */
    private function resolveUser(): mixed
    {
        $provider = config('auth.providers.'.config('auth.guards.web.provider', 'users').'.model');

        if (! $provider || ! class_exists($provider)) {
            return null;
        }

        return $provider::find($this->userId);
    }
}
