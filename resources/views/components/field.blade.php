@props(['label', 'name', 'type' => 'text', 'value' => null, 'hint' => null, 'required' => false, 'options' => null, 'rows' => null, 'placeholder' => null])
@php
    $id = $attributes->get('id', 'f-'.str_replace(['[', ']', '.'], '-', $name));
    $dot = trim(str_replace(['[', ']'], ['.', ''], $name), '.');
    $current = old($dot, $value);
    $invalid = $errors->has($dot);
    $describedBy = trim(($hint ? "$id-hint " : '').($invalid ? "$id-error" : ''));
@endphp
<div {{ $attributes->only('class') }}>
    <label for="{{ $id }}" class="label">{{ $label }} @unless ($required)<span class="font-normal text-muted">(optional)</span>@endunless</label>
    @if ($options !== null)
        <select id="{{ $id }}" name="{{ $name }}" class="field" @required($required) aria-invalid="{{ $invalid ? 'true' : 'false' }}" @if ($describedBy) aria-describedby="{{ $describedBy }}" @endif {{ $attributes->except(['class', 'id']) }}>
            @if ($placeholder)
                <option value="">{{ $placeholder }}</option>
            @endif
            @foreach ($options as $optValue => $optLabel)
                @php $v = is_int($optValue) && array_is_list((array) $options) ? $optLabel : $optValue; @endphp
                <option value="{{ $v }}" @selected((string) $current === (string) $v)>{{ $optLabel }}</option>
            @endforeach
        </select>
    @elseif ($rows)
        <textarea id="{{ $id }}" name="{{ $name }}" rows="{{ $rows }}" class="field" placeholder="{{ $placeholder }}" @required($required) aria-invalid="{{ $invalid ? 'true' : 'false' }}" @if ($describedBy) aria-describedby="{{ $describedBy }}" @endif {{ $attributes->except(['class', 'id']) }}>{{ $current }}</textarea>
    @else
        <input id="{{ $id }}" name="{{ $name }}" type="{{ $type }}" @if ($type !== 'password') value="{{ $current }}" @endif class="field" placeholder="{{ $placeholder }}" @required($required) aria-invalid="{{ $invalid ? 'true' : 'false' }}" @if ($describedBy) aria-describedby="{{ $describedBy }}" @endif {{ $attributes->except(['class', 'id']) }}>
    @endif
    @if ($hint)
        <p id="{{ $id }}-hint" class="hint">{{ $hint }}</p>
    @endif
    @error($dot)
        <p id="{{ $id }}-error" class="error"><x-lucide-circle-alert class="mt-0.5 size-3.5 shrink-0" aria-hidden="true" />{{ $message }}</p>
    @enderror
</div>
