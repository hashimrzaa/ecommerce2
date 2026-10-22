@extends('layouts.store')

@section('title', 'My Account')

@section('content')
    <div class="container-page pt-8">
        <x-breadcrumbs :items="['My Account' => null]" />
        <header class="mt-4 flex flex-wrap items-center justify-between gap-4">
            <div>
                <h1 class="text-2xl font-extrabold sm:text-3xl">Hello, {{ Str::before($user->name, ' ') }}</h1>
                <p class="mt-1 text-sm text-muted">{{ $user->email }}</p>
            </div>
            <div class="flex gap-2">
                @if ($user->isStaff())
                    <a href="{{ route('admin.dashboard') }}" class="btn btn-outline btn-sm"><x-lucide-layout-dashboard class="size-4" aria-hidden="true" />Admin panel</a>
                @endif
                <form method="post" action="{{ route('logout') }}">@csrf<button class="btn btn-ghost btn-sm"><x-lucide-log-out class="size-4" aria-hidden="true" />Sign out</button></form>
            </div>
        </header>

        <div class="mt-8 grid gap-8 lg:grid-cols-[1fr_23.75rem]">
            <section aria-labelledby="orders-title">
                <h2 id="orders-title" class="text-lg font-bold">My orders</h2>
                @if ($orders->isEmpty())
                    <div class="card mt-4 p-8 text-center">
                        <span class="mx-auto grid size-14 place-items-center rounded-full bg-sunken text-muted"><x-lucide-package class="size-6" aria-hidden="true" /></span>
                        <p class="mt-4 font-semibold">No orders yet</p>
                        <p class="mt-1 text-sm text-muted">Orders you place while signed in will appear here.</p>
                        <a href="{{ route('shop') }}" class="btn btn-primary mt-5">Start shopping</a>
                    </div>
                @else
                    <ul class="mt-4 space-y-3">
                        @foreach ($orders as $order)
                            <li>
                                <a href="{{ route('order.show', $order->number) }}" class="card flex flex-wrap items-center justify-between gap-3 p-4 transition-shadow hover:shadow-e2 sm:p-5">
                                    <div>
                                        <p class="font-semibold tabular-nums">{{ $order->number }}</p>
                                        <p class="text-[0.8125rem] text-muted">{{ $order->created_at->format('j M Y') }} · {{ $order->items->sum('qty') }} {{ Str::plural('item', $order->items->sum('qty')) }}</p>
                                    </div>
                                    <div class="flex items-center gap-4">
                                        <span class="badge badge-{{ $order->status_tone }}">{{ $order->status_label }}</span>
                                        <span class="font-bold tabular-nums">{{ money($order->total) }}</span>
                                        <x-lucide-chevron-right class="size-5 text-muted" aria-hidden="true" />
                                    </div>
                                </a>
                            </li>
                        @endforeach
                    </ul>
                @endif
            </section>

            <section class="card p-5 sm:p-6 lg:self-start" aria-labelledby="details-title">
                <h2 id="details-title" class="text-lg font-bold">My details</h2>
                <p class="mt-1 text-sm text-muted">Used to fill in checkout for you.</p>
                <form method="post" action="{{ route('account.update') }}" class="mt-5 space-y-4" data-once>
                    @csrf @method('put')
                    <x-field label="Full name" name="name" required :value="$user->name" autocomplete="name" />
                    <x-field label="Mobile number" name="phone" type="tel" required :value="$user->phone" autocomplete="tel" inputmode="tel" />
                    <x-field label="City" name="city" :options="config('store.cities')" placeholder="Select your city" :value="$user->city" />
                    <x-field label="Address" name="address" rows="3" :value="$user->address" autocomplete="street-address" />
                    <button class="btn btn-primary w-full">Save details</button>
                </form>
            </section>
        </div>
    </div>
@endsection
