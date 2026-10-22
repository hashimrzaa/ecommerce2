@extends('layouts.admin')

@section('title', 'Store settings')

@php $groupIcons = ['Brand' => 'store', 'Contact' => 'phone', 'Delivery' => 'truck', 'Stock' => 'boxes']; @endphp

@section('content')
    <form method="post" action="{{ route('admin.settings.update') }}" class="max-w-4xl space-y-6" data-once>
        @csrf @method('put')
        @foreach ($groups as $group => $fields)
            <section class="card p-5 sm:p-6">
                <h2 class="flex items-center gap-2.5 text-base font-bold">
                    <span class="grid size-8 place-items-center rounded-full bg-primary-soft text-primary">@svg('lucide-'.($groupIcons[$group] ?? 'settings'), 'size-4', ['aria-hidden' => 'true'])</span>{{ $group }}
                </h2>
                <div class="mt-5 grid gap-4 sm:grid-cols-2">
                    @foreach ($fields as $field)
                        <x-field :label="$field['label']" :name="$field['key']" :type="$field['type']" :value="$field['value']" :hint="$field['help']"
                            :required="$field['type'] !== 'url'" :min="$field['type'] === 'number' ? 0 : null"
                            class="{{ $field['help'] && strlen($field['help']) > 60 ? 'sm:col-span-2' : '' }}" />
                    @endforeach
                </div>
            </section>
        @endforeach
        <div class="sticky bottom-4 flex justify-end">
            <button class="btn btn-primary btn-lg shadow-e2">Save settings</button>
        </div>
    </form>
@endsection
