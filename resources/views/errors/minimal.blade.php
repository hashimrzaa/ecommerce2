<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('title') — {{ config('store.name') }}</title>
    <link rel="icon" type="image/svg+xml" href="{{ asset('favicon.svg') }}">
    @vite(['resources/css/app.css'])
</head>
<body class="grid min-h-screen place-items-center p-6">
    <main class="max-w-md text-center">
        <p class="font-display text-6xl font-extrabold text-primary">@yield('code')</p>
        <h1 class="mt-4 text-2xl font-extrabold">@yield('title')</h1>
        <p class="mt-2 text-muted">@yield('message')</p>
        <div class="mt-7 flex flex-wrap justify-center gap-3">
            <a href="{{ url('/') }}" class="btn btn-primary">Back to home</a>
            <a href="{{ url('/shop') }}" class="btn btn-outline">Browse products</a>
        </div>
    </main>
</body>
</html>
