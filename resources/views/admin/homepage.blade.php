@extends('layouts.admin')

@section('title', 'Homepage')

@section('actions')
    <button type="button" class="btn btn-primary btn-sm" x-data @click="$dispatch('open-modal', 'section-new')"><x-lucide-plus class="size-4" aria-hidden="true" />Add product row</button>
@endsection

@php
    $kindIcons = ['showcase' => 'gallery-horizontal', 'categories' => 'layout-grid', 'marquee' => 'move-horizontal', 'rail' => 'rows-3', 'deals' => 'badge-percent', 'reviews' => 'message-square-quote'];
@endphp

@section('content')
    <p class="max-w-3xl text-sm text-muted">The homepage is built from these sections, top to bottom. Reorder them with the arrows, switch any of them off, change their headings, or add more product rows. Changes show on the store straight away.</p>

    <ol class="mt-5 space-y-3" x-data>
        @foreach ($sections as $section)
            <li class="card flex flex-wrap items-center gap-3 p-3 sm:gap-4 sm:p-4 {{ $section->is_active ? '' : 'opacity-60' }}">
                <div class="flex flex-col gap-1">
                    @foreach (['up' => 'chevron-up', 'down' => 'chevron-down'] as $direction => $icon)
                        <form method="post" action="{{ route('admin.homepage.move', $section) }}">
                            @csrf<input type="hidden" name="direction" value="{{ $direction }}">
                            <button class="grid size-7 place-items-center rounded-md border border-line text-ink-2 hover:bg-sunken disabled:opacity-30" @disabled(($direction === 'up' && $loop->parent->first) || ($direction === 'down' && $loop->parent->last)) aria-label="Move {{ $section->title ?: $section->kind_label }} {{ $direction }}">@svg('lucide-'.$icon, 'size-4', ['aria-hidden' => 'true'])</button>
                        </form>
                    @endforeach
                </div>
                <span class="grid size-11 shrink-0 place-items-center rounded-xl bg-primary-soft text-primary">@svg('lucide-'.($kindIcons[$section->kind] ?? 'square'), 'size-5', ['aria-hidden' => 'true'])</span>
                <div class="min-w-0 flex-1">
                    <p class="font-semibold">{{ $section->title ?: $section->kind_label }}</p>
                    <p class="text-[0.8125rem] text-muted">{{ $section->kind_label }}@if ($section->source) · {{ $sources[$section->source] ?? $section->source }}@endif @if (in_array($section->kind, ['rail', 'showcase', 'reviews'], true))· {{ $section->item_limit }} items @endif</p>
                </div>
                <span class="badge {{ $section->is_active ? 'badge-success' : 'badge-neutral' }}">{{ $section->is_active ? 'Shown' : 'Hidden' }}</span>
                <button type="button" class="btn btn-ghost btn-sm" @click="$dispatch('open-modal', 'section-{{ $section->id }}')">Edit</button>
            </li>
        @endforeach
    </ol>

    @foreach ($sections->concat([null]) as $section)
        @php
            $sid = $section?->id ?? 'new';
            $kind = $section?->kind ?? 'rail';
            $hasText = in_array($kind, ['rail', 'showcase', 'categories', 'reviews'], true);
            $hasSource = in_array($kind, ['rail', 'showcase'], true);
        @endphp
        <x-modal name="section-{{ $sid }}" :title="$section ? 'Edit: '.($section->title ?: $section->kind_label) : 'Add product row'">
            <form method="post" action="{{ $section ? route('admin.homepage.update', $section) : route('admin.homepage.store') }}" class="space-y-4" data-once>
                @csrf
                @if ($section) @method('put') @endif
                @if ($hasText)
                    <x-field label="Heading" name="title" id="hs-title-{{ $sid }}" :required="$kind === 'rail'" :value="$section?->title" />
                    <div class="grid gap-4 sm:grid-cols-2">
                        <x-field label="Small label above" name="eyebrow" id="hs-eyebrow-{{ $sid }}" :value="$section?->eyebrow" placeholder="e.g. Just landed" />
                        @if ($kind !== 'categories')
                            <x-field label="Number of items" name="item_limit" id="hs-limit-{{ $sid }}" type="number" min="1" max="12" :value="$section?->item_limit ?? 5" />
                        @endif
                    </div>
                    @if ($kind !== 'categories' && $kind !== 'reviews')
                        <x-field label="Supporting line" name="subtitle" id="hs-sub-{{ $sid }}" :value="$section?->subtitle" />
                    @endif
                @else
                    <p class="text-sm text-muted">This section has no text to edit. You can show or hide it and change its position.</p>
                @endif
                @if ($hasSource)
                    <x-field label="Products from" name="source" id="hs-source-{{ $sid }}" required :options="$sources" :value="$section?->source ?? 'best'" />
                @endif
                <label class="flex min-h-9 items-center gap-3 text-sm"><input type="hidden" name="is_active" value="0"><input type="checkbox" name="is_active" value="1" class="size-4" @checked($section?->is_active ?? true)>Show on the homepage</label>
                <button class="btn btn-primary w-full">{{ $section ? 'Save section' : 'Add product row' }}</button>
            </form>
            @if ($section?->kind === 'rail')
                <form method="post" action="{{ route('admin.homepage.destroy', $section) }}" class="mt-4 border-t border-line pt-4" onsubmit="return confirm('Remove this product row from the homepage?')">@csrf @method('delete')<button class="btn btn-danger btn-sm">Remove row</button></form>
            @endif
        </x-modal>
    @endforeach
@endsection
