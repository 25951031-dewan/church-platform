<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ config('app.name', 'Church Platform') }}</title>
    <meta name="csrf-token" content="{{ csrf_token() }}">

    {{-- Bootstrap data for React hydration --}}
    <script>
        window.__BOOTSTRAP_DATA__ = @json($bootstrapData ?? []);
    </script>

    @viteReactRefresh
    @vite(['resources/client/main.tsx'])
</head>
<body class="antialiased">
    <div id="root"></div>
</body>
</html>
