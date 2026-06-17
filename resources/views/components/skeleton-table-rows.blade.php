@props([
    'columns' => 4,
    'rows' => 5,
])

@for ($r = 0; $r < $rows; $r++)
    <tr class="animate-pulse" role="presentation" aria-hidden="true" {{ $attributes }}>
        @for ($c = 0; $c < $columns; $c++)
            <td class="px-6 py-4">
                <div class="h-4 rounded-md bg-gray-200 dark:bg-gray-700 @if($c === $columns - 1) ms-auto max-w-[5rem] @endif"></div>
            </td>
        @endfor
    </tr>
@endfor
