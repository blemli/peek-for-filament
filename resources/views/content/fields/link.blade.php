@php
    $href = \Illuminate\Support\Str::startsWith($field->value, ['http://', 'https://'])
        ? $field->value
        : 'https://' . $field->value;
@endphp
<a href="{{ $href }}" data-peek-off target="_blank" rel="noopener noreferrer" class="fi-peek-link">{{ \Illuminate\Support\Str::limit($field->value, 40) }}</a>
