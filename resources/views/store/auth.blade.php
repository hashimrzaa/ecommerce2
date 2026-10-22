@extends('layouts.store')

@section('title', $mode === 'login' ? 'Sign in' : 'Create account')

@section('content')
    <div class="container-page grid place-items-center py-12">
        <div class="card w-full max-w-md p-6 shadow-e1 sm:p-8">
            <h1 class="text-2xl font-extrabold">{{ $mode === 'login' ? 'Sign in' : 'Create your account' }}</h1>
            <p class="mt-1 text-sm text-muted">{{ $mode === 'login' ? 'Welcome back. See your orders and check out faster.' : 'Save your details and keep all your orders in one place.' }}</p>

            <form method="post" action="{{ route($mode) }}" class="mt-6 space-y-4" data-once>
                @csrf
                @if ($mode === 'register')
                    <x-field label="Full name" name="name" required autocomplete="name" />
                    <x-field label="Mobile number" name="phone" type="tel" required autocomplete="tel" inputmode="tel" placeholder="0300 1234567" />
                @endif
                <x-field label="Email" name="email" type="email" required autocomplete="email" />
                <x-field label="Password" name="password" type="password" required :autocomplete="$mode === 'login' ? 'current-password' : 'new-password'" :hint="$mode === 'register' ? 'At least 8 characters.' : null" />
                @if ($mode === 'register')
                    <x-field label="Confirm password" name="password_confirmation" type="password" required autocomplete="new-password" />
                @else
                    <label class="flex min-h-9 items-center gap-2.5 text-sm text-ink-2"><input type="checkbox" name="remember" value="1" class="size-4">Keep me signed in</label>
                @endif
                <button class="btn btn-primary btn-lg w-full">{{ $mode === 'login' ? 'Sign in' : 'Create account' }}</button>
            </form>

            <p class="mt-6 border-t border-line pt-5 text-center text-sm text-muted">
                @if ($mode === 'login')
                    New here? <a href="{{ route('register') }}" class="link">Create an account</a>
                @else
                    Already have an account? <a href="{{ route('login') }}" class="link">Sign in</a>
                @endif
            </p>
            <p class="mt-3 text-center text-[0.8125rem] text-muted">You can also <a href="{{ route('shop') }}" class="link">shop as a guest</a> — no account needed for Cash on Delivery.</p>
        </div>
    </div>
@endsection
