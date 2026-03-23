<!DOCTYPE html>
<html lang="en" class="h-full bg-gray-50">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ $title ?? 'Church Platform Installer' }}</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="h-full flex items-center justify-center p-4">
<div class="w-full max-w-lg">
    <div class="text-center mb-8">
        <h1 class="text-2xl font-bold text-gray-900">&#9962; Church Platform</h1>
        <p class="text-gray-500 text-sm mt-1">{{ $subtitle ?? 'Installation Wizard' }}</p>
    </div>
    @isset($step)
    <div class="flex items-center justify-center gap-2 mb-6">
        @foreach(['Requirements', 'Database', 'Admin Account'] as $i => $label)
            @php $num = $i + 1; $active = $num === $step; $done = $num < $step; @endphp
            <div class="flex items-center gap-1">
                <div class="w-7 h-7 rounded-full flex items-center justify-center text-xs font-bold
                    {{ $done ? 'bg-green-500 text-white' : ($active ? 'bg-blue-600 text-white' : 'bg-gray-200 text-gray-500') }}">
                    {{ $done ? '&#10003;' : $num }}
                </div>
                <span class="text-xs {{ $active ? 'text-blue-700 font-semibold' : 'text-gray-400' }}">{{ $label }}</span>
            </div>
            @if($i < 2)<div class="w-8 h-px bg-gray-300"></div>@endif
        @endforeach
    </div>
    @endisset
    <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-8">
        @yield('content')
    </div>
</div>
</body>
</html>
