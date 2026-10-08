<?php

namespace App\Exports;

use Closure;
use Illuminate\Database\Eloquent\Builder;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

class SelectedJobDataExport implements FromQuery, WithHeadings, WithMapping
{
    private Closure $mapper;

    public function __construct(
        private Builder $items,
        private array $headings,
        callable $mapper
    ) {
        $this->mapper = Closure::fromCallable($mapper);
    }

    public function query(): Builder
    {
        return $this->items;
    }

    public function headings(): array
    {
        return $this->headings;
    }

    public function map($item): array
    {
        return ($this->mapper)($item);
    }
}
