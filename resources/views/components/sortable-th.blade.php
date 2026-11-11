@props(['column', 'sort' => null, 'direction' => 'asc'])

@php
    $active = $sort === $column;

    $link = fn($dir) => request()->url() . '?' . http_build_query(array_merge(
        request()->except(['apply_filter', 'reset_filter', 'page']),
        ['sort' => $column, 'direction' => $dir],
    ));

    $icon = !$active ? '↕' : match ($direction) {
        'asc' => '↑',
        'desc' => '↓',
        'empty' => '∅',
        default => '↕',
    };

    $options = ['asc' => 'A → Z', 'desc' => 'Z → A', 'empty' => 'Empty'];
    $aria = $active ? ($direction === 'desc' ? 'descending' : 'ascending') : 'none';
@endphp

<th {{ $attributes }} aria-sort="{{ $aria }}">
    <div class="dropdown d-inline-block">
        <a href="#" class="text-decoration-none text-reset" data-bs-toggle="dropdown"
            data-bs-popper-config='{"strategy":"fixed"}' aria-expanded="false">
            {{ $slot }} <span aria-hidden="true">{{ $icon }}</span>
        </a>
        <ul class="dropdown-menu">
            @foreach ($options as $dir => $label)
                <li>
                    <a class="dropdown-item {{ $active && $direction === $dir ? 'active' : '' }}"
                        href="{{ $link($dir) }}">{{ $label }}</a>
                </li>
            @endforeach
        </ul>
    </div>
</th>