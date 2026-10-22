@extends('layouts.admin')

@section('title', 'Content banners')

@section('actions')
    <button type="button" class="btn btn-primary btn-sm" x-data @click="$dispatch('open-modal', 'banner-new')"><x-lucide-plus class="size-4" aria-hidden="true" />Add banner</button>
@endsection

@php
    $placements = ['announcement' => ['Announcement bar', 'Short messages in the dark strip at the very top: COD, delivery or the current promotion. The first message is always shown on phones.'], 'hero' => ['Homepage hero', 'The headline, text and button of the homepage hero. The first live banner in display order is shown.']];
    $all = $banners->flatten();
@endphp

@section('content')
    <div class="space-y-8" x-data>
        @foreach ($placements as $placement => [$heading, $help])
            <section>
                <h2 class="text-base font-bold">{{ $heading }}</h2>
                <p class="mt-1 max-w-2xl text-sm text-muted">{{ $help }}</p>
                <ul class="card mt-3 divide-y divide-line">
                    @forelse ($banners[$placement] ?? [] as $banner)
                        <li class="flex flex-wrap items-center gap-4 p-4 sm:px-5">
                            <span class="w-6 text-center text-xs font-bold text-muted tabular-nums" title="Display order">{{ $banner->position }}</span>
                            @if ($banner->image)<img src="{{ asset('storage/'.$banner->image) }}" alt="" class="h-12 w-20 rounded-md object-cover">@endif
                            <div class="min-w-0 flex-1">
                                <p class="font-medium">{{ $banner->title }}</p>
                                @if ($banner->subtitle)<p class="line-clamp-1 text-sm text-muted">{{ $banner->subtitle }}</p>@endif
                                @if ($banner->cta_url)<p class="mt-0.5 text-xs text-muted">{{ $banner->cta_label ?: 'Button' }} → {{ $banner->cta_url }}</p>@endif
                            </div>
                            <span class="badge {{ $banner->is_active ? 'badge-success' : 'badge-neutral' }}">{{ $banner->is_active ? 'Live' : 'Hidden' }}</span>
                            <button type="button" class="btn btn-ghost btn-sm" @click="$dispatch('open-modal', 'banner-{{ $banner->id }}')">Edit</button>
                        </li>
                    @empty
                        <li class="p-8 text-center text-sm text-muted">Nothing here yet.</li>
                    @endforelse
                </ul>
            </section>
        @endforeach
    </div>

    @foreach ($all->concat([null]) as $banner)
        @php $bid = $banner?->id ?? 'new'; @endphp
        <x-modal name="banner-{{ $bid }}" :title="$banner ? 'Edit banner' : 'Add banner'">
            <form method="post" action="{{ $banner ? route('admin.banners.update', $banner) : route('admin.banners.store') }}" enctype="multipart/form-data" class="space-y-4" data-once x-data="{ placement: '{{ $banner?->placement ?? 'announcement' }}' }">
                @csrf
                @if ($banner) @method('put') @endif
                <x-field label="Placement" name="placement" id="b-place-{{ $bid }}" required :options="['announcement' => 'Announcement bar', 'hero' => 'Homepage hero']" :value="$banner?->placement ?? 'announcement'" x-model="placement" />
                <x-field label="Headline" name="title" id="b-title-{{ $bid }}" required :value="$banner?->title" />
                <div class="space-y-4" x-show="placement === 'hero'">
                    <x-field label="Supporting text" name="subtitle" id="b-sub-{{ $bid }}" rows="2" :value="$banner?->subtitle" />
                    <div class="grid gap-4 sm:grid-cols-2">
                        <x-field label="Button label" name="cta_label" id="b-cta-{{ $bid }}" :value="$banner?->cta_label" placeholder="Shop now" />
                        <x-field label="Button link" name="cta_url" id="b-url-{{ $bid }}" :value="$banner?->cta_url" placeholder="/c/women" hint="Start with / for a page on this site." />
                    </div>
                    <div>
                        <label for="b-img-{{ $bid }}" class="label">Banner image <span class="font-normal text-muted">(optional)</span></label>
                        <input id="b-img-{{ $bid }}" type="file" name="image_file" accept="image/*" class="block w-full text-sm text-ink-2 file:mr-4 file:h-10 file:cursor-pointer file:rounded-control file:border-0 file:bg-sunken file:px-4 file:text-sm file:font-semibold file:text-ink">
                        <p class="hint">Shown on the right of the hero on desktop. Landscape 4:3, up to 4 MB.</p>
                        @if ($banner?->image)<label class="mt-2 flex items-center gap-2 text-sm"><input type="checkbox" name="remove_image" value="1" class="size-4">Remove current image</label>@endif
                    </div>
                </div>
                <x-field label="Display order" name="position" id="b-pos-{{ $bid }}" type="number" min="0" :value="$banner?->position ?? 0" hint="Lower numbers appear first." />
                <label class="flex min-h-9 items-center gap-3 text-sm"><input type="hidden" name="is_active" value="0"><input type="checkbox" name="is_active" value="1" class="size-4" @checked($banner?->is_active ?? true)>Show on the store</label>
                <button class="btn btn-primary w-full">{{ $banner ? 'Save banner' : 'Add banner' }}</button>
            </form>
            @if ($banner)
                <form method="post" action="{{ route('admin.banners.destroy', $banner) }}" class="mt-4 border-t border-line pt-4" onsubmit="return confirm('Delete this banner?')">@csrf @method('delete')<button class="btn btn-danger btn-sm">Delete banner</button></form>
            @endif
        </x-modal>
    @endforeach
@endsection
