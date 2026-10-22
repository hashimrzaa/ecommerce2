@extends('layouts.store')

@section('title', $category->name)

@section('content')
    {{-- Age confirmation before entering Private & Intimate (spec §12) --}}
    <div class="container-page grid min-h-[60vh] place-items-center py-14">
        <div class="card w-full max-w-md p-8 text-center shadow-e2">
            <span class="mx-auto grid size-14 place-items-center rounded-full bg-sunken text-ink-2"><x-lucide-lock class="size-6" aria-hidden="true" /></span>
            <h1 class="mt-5 text-2xl font-extrabold">Are you 18 years or older?</h1>
            <p class="mt-2 text-sm text-muted">The {{ $category->name }} section contains innerwear and personal products. Please confirm your age to continue.</p>
            <form method="post" action="{{ route('age.confirm') }}" class="mt-7 grid grid-cols-2 gap-3">
                @csrf
                <input type="hidden" name="intended" value="{{ $intended }}">
                <button name="answer" value="no" class="btn btn-outline">Exit</button>
                <button name="answer" value="yes" class="btn btn-primary">Yes, enter</button>
            </form>
            <p class="mt-6 flex items-center justify-center gap-2 text-[0.8125rem] text-muted"><x-lucide-package class="size-4" aria-hidden="true" />Orders from this section ship in plain, discreet packaging.</p>
        </div>
    </div>
@endsection
