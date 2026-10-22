@extends('layouts.admin')

@section('title', 'Categories')

@section('actions')
    <button type="button" class="btn btn-primary btn-sm" x-data @click="$dispatch('category-add', { parent: '' })"><x-lucide-plus class="size-4" aria-hidden="true" />Add category</button>
@endsection

@section('content')
    <div x-data="{ add: { parent: '' }, edit: { id: null, name: '', icon: '', dept: false } }"
        @category-add.window="add = $event.detail; $dispatch('open-modal', 'category-add')">
        <p class="max-w-3xl text-sm text-muted">Add departments, groups and sub-categories, rename them, or hide them from the store. Categories are never deleted, so products and old links keep working — hide a category instead and switch it back on any time.</p>

        <div class="mt-5 space-y-4">
            @foreach ($departments as $dept)
                @php $system = $dept->kind !== 'normal'; @endphp
                <section class="card overflow-hidden" x-data="{ open: false }">
                    <div class="flex flex-wrap items-center gap-x-3 gap-y-2 p-4 sm:px-5">
                        <button type="button" class="flex min-h-11 flex-1 items-center gap-3 text-left" @click="open = !open" :aria-expanded="open.toString()">
                            <x-lucide-chevron-right class="size-5 shrink-0 text-muted transition-transform" ::class="open && 'rotate-90'" aria-hidden="true" />
                            <span class="grid size-9 shrink-0 place-items-center rounded-full bg-primary-soft text-primary">@svg('lucide-'.($dept->icon ?: 'tag'), 'size-[1.125rem]', ['aria-hidden' => 'true'])</span>
                            <span><span class="font-semibold">{{ $dept->name }}</span>
                                <span class="block text-xs text-muted">{{ $dept->children->count() }} groups · {{ $dept->children->sum(fn ($g) => $g->children->count()) }} sub-categories @if ($system)· automatic collections @endif</span></span>
                        </button>
                        <form method="post" action="{{ route('admin.categories.update', $dept) }}" class="flex items-center gap-2">
                            @csrf @method('patch')
                            <label for="priority-{{ $dept->id }}" class="sr-only">Launch priority</label>
                            <select id="priority-{{ $dept->id }}" name="priority" class="field field-sm w-auto" onchange="this.form.requestSubmit()" title="Launch priority">
                                @foreach (['High', 'Medium', 'Low'] as $priority)<option @selected($dept->priority === $priority)>{{ $priority }}</option>@endforeach
                            </select>
                        </form>
                        <button type="button" class="btn btn-ghost btn-sm" @click="edit = { id: {{ $dept->id }}, name: @js($dept->name), icon: @js($dept->icon), dept: true }; $dispatch('open-modal', 'category-edit')"><x-lucide-pencil class="size-4" aria-hidden="true" />Edit</button>
                        @unless ($system)
                            <button type="button" class="btn btn-ghost btn-sm" @click="$dispatch('category-add', { parent: '{{ $dept->id }}' })"><x-lucide-plus class="size-4" aria-hidden="true" />Group</button>
                        @endunless
                        @include('admin.partials.visibility', ['category' => $dept])
                    </div>

                    <div x-show="open" x-collapse x-cloak class="border-t border-line bg-canvas">
                        @forelse ($dept->children as $group)
                            <div class="border-b border-line px-4 py-4 last:border-b-0 sm:px-5">
                                <div class="flex flex-wrap items-center gap-2">
                                    <p class="flex flex-1 items-center gap-2 text-sm font-semibold">{{ $group->name }}
                                        @if ($group->is_restricted)<span class="badge badge-neutral"><x-lucide-lock class="size-3" aria-hidden="true" />18+ age gate</span>@endif
                                        @if ($group->kind === 'filter')<span class="badge badge-info">Saved filters</span>@endif
                                    </p>
                                    <button type="button" class="btn btn-ghost btn-sm" @click="edit = { id: {{ $group->id }}, name: @js($group->name), icon: '', dept: false }; $dispatch('open-modal', 'category-edit')"><x-lucide-pencil class="size-4" aria-hidden="true" />Rename</button>
                                    @if ($group->kind === 'normal')
                                        <button type="button" class="btn btn-ghost btn-sm" @click="$dispatch('category-add', { parent: '{{ $group->id }}' })"><x-lucide-plus class="size-4" aria-hidden="true" />Sub-category</button>
                                    @endif
                                    @include('admin.partials.visibility', ['category' => $group])
                                </div>
                                <ul class="mt-3 flex flex-wrap gap-2">
                                    @foreach ($group->children as $leaf)
                                        <li class="inline-flex items-center gap-1 rounded-full border border-line bg-surface py-1 pr-1 pl-3 text-[0.8125rem] {{ $leaf->is_visible ? '' : 'opacity-50' }}">
                                            {{ $leaf->name }}
                                            @if ($leaf->kind === 'normal')<span class="ml-1 text-muted tabular-nums">{{ $counts[$leaf->id] ?? 0 }}</span>@endif
                                            <button type="button" class="grid size-7 place-items-center rounded-full text-muted hover:bg-sunken hover:text-ink" title="Rename {{ $leaf->name }}" aria-label="Rename {{ $leaf->name }}"
                                                @click="edit = { id: {{ $leaf->id }}, name: @js($leaf->name), icon: '', dept: false }; $dispatch('open-modal', 'category-edit')"><x-lucide-pencil class="size-3.5" aria-hidden="true" /></button>
                                            <form method="post" action="{{ route('admin.categories.update', $leaf) }}">
                                                @csrf @method('patch')
                                                <input type="hidden" name="is_visible" value="{{ $leaf->is_visible ? 0 : 1 }}">
                                                <button class="grid size-7 place-items-center rounded-full text-muted hover:bg-sunken hover:text-ink" title="{{ $leaf->is_visible ? 'Hide' : 'Show' }} {{ $leaf->name }}" aria-label="{{ $leaf->is_visible ? 'Hide' : 'Show' }} {{ $leaf->name }}">
                                                    @if ($leaf->is_visible)<x-lucide-eye class="size-3.5" aria-hidden="true" />@else<x-lucide-eye-off class="size-3.5" aria-hidden="true" />@endif
                                                </button>
                                            </form>
                                        </li>
                                    @endforeach
                                    @if ($group->children->isEmpty())<li class="text-[0.8125rem] text-muted">No sub-categories yet. Products are assigned to sub-categories, so add at least one.</li>@endif
                                </ul>
                            </div>
                        @empty
                            <p class="px-5 py-6 text-sm text-muted">This department is empty. Add a group, then sub-categories inside it.</p>
                        @endforelse
                    </div>
                </section>
            @endforeach
        </div>

        {{-- Add --}}
        <x-modal name="category-add" title="Add category">
            <form method="post" action="{{ route('admin.categories.store') }}" class="space-y-4" data-once>
                @csrf
                <div>
                    <label for="cat-parent" class="label">Add inside</label>
                    <select id="cat-parent" name="parent_id" class="field" x-model="add.parent">
                        <option value="">— New department (top level) —</option>
                        @foreach ($departments->where('kind', 'normal') as $dept)
                            <option value="{{ $dept->id }}">{{ $dept->name }} (as a group)</option>
                            @foreach ($dept->children->where('kind', 'normal') as $group)
                                <option value="{{ $group->id }}">&nbsp;&nbsp;&nbsp;{{ $dept->name }} › {{ $group->name }} (as a sub-category)</option>
                            @endforeach
                        @endforeach
                    </select>
                    <p class="hint">Departments appear in the main menu. Products go into sub-categories.</p>
                </div>
                <x-field label="Name" name="name" id="cat-new-name" required placeholder="e.g. Footwear" />
                <fieldset x-show="add.parent === ''">
                    <legend class="label">Icon</legend>
                    <div class="flex flex-wrap gap-2">
                        @foreach ($icons as $i => $icon)
                            <label title="{{ $icon }}">
                                <input type="radio" name="icon" value="{{ $icon }}" class="peer sr-only" @checked($i === 7) :disabled="add.parent !== ''">
                                <span class="grid size-10 place-items-center rounded-control border border-line-strong text-ink-2 peer-checked:border-primary peer-checked:bg-primary-soft peer-checked:text-primary peer-focus-visible:ring-2 peer-focus-visible:ring-ink">@svg('lucide-'.$icon, 'size-5', ['aria-hidden' => 'true'])</span>
                                <span class="sr-only">{{ $icon }}</span>
                            </label>
                        @endforeach
                    </div>
                </fieldset>
                <button class="btn btn-primary w-full">Add category</button>
            </form>
        </x-modal>

        {{-- Edit / rename --}}
        <x-modal name="category-edit" title="Edit category">
            <form method="post" :action="'{{ url('admin/categories') }}/' + edit.id" class="space-y-4" data-once>
                @csrf @method('patch')
                <div>
                    <label for="cat-edit-name" class="label">Name</label>
                    <input id="cat-edit-name" name="name" class="field" x-model="edit.name" required maxlength="80">
                    <p class="hint">Only the name shown to shoppers changes. The page address stays the same, so existing links keep working.</p>
                </div>
                <fieldset x-show="edit.dept">
                    <legend class="label">Icon</legend>
                    <div class="flex flex-wrap gap-2">
                        @foreach ($icons as $icon)
                            <label title="{{ $icon }}">
                                <input type="radio" name="icon" value="{{ $icon }}" class="peer sr-only" x-model="edit.icon" :disabled="!edit.dept">
                                <span class="grid size-10 place-items-center rounded-control border border-line-strong text-ink-2 peer-checked:border-primary peer-checked:bg-primary-soft peer-checked:text-primary peer-focus-visible:ring-2 peer-focus-visible:ring-ink">@svg('lucide-'.$icon, 'size-5', ['aria-hidden' => 'true'])</span>
                                <span class="sr-only">{{ $icon }}</span>
                            </label>
                        @endforeach
                    </div>
                </fieldset>
                <button class="btn btn-primary w-full">Save</button>
            </form>
        </x-modal>
    </div>
@endsection
