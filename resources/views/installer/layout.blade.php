<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Church Platform Installer</title>
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
    <style>
        body {
            background: linear-gradient(135deg, #eef2ff 0%, #e0e7ff 25%, #c7d2fe 50%, #ddd6fe 75%, #ede9fe 100%);
            min-height: 100vh;
        }
        .step-connector {
            height: 2px;
            flex: 1;
            transition: background-color 0.5s ease;
        }
        .step-circle {
            transition: all 0.3s ease;
        }
        .step-circle.active {
            box-shadow: 0 0 0 4px rgba(99, 102, 241, 0.2);
        }
        .step-circle.completed {
            box-shadow: 0 0 0 4px rgba(34, 197, 94, 0.2);
        }
        .card-shadow {
            box-shadow: 0 20px 60px -15px rgba(79, 70, 229, 0.15), 0 10px 30px -10px rgba(0, 0, 0, 0.08);
        }
        .pulse-glow {
            animation: pulseGlow 2s infinite;
        }
        @keyframes pulseGlow {
            0%, 100% { box-shadow: 0 0 0 0 rgba(99, 102, 241, 0.4); }
            50% { box-shadow: 0 0 0 8px rgba(99, 102, 241, 0); }
        }
        .fade-in {
            animation: fadeIn 0.6s ease-out;
        }
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(10px); }
            to { opacity: 1; transform: translateY(0); }
        }
    </style>
    @stack('styles')
</head>
<body class="antialiased">
    <div class="min-h-screen flex flex-col">
        {{-- Header --}}
        <header class="pt-8 pb-4">
            <div class="max-w-3xl mx-auto px-4 text-center">
                <div class="inline-flex items-center justify-center w-16 h-16 bg-white rounded-2xl card-shadow mb-4">
                    <svg class="w-9 h-9 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 2v8m0 0v12m0-12H6m6 0h6M8 6h8" />
                    </svg>
                </div>
                <h1 class="text-2xl font-bold text-gray-800 tracking-tight">Church Platform</h1>
                <p class="text-sm text-indigo-500 font-medium mt-1">Installation Wizard</p>
            </div>
        </header>

        {{-- Step Indicator --}}
        @php
            $steps = [
                1 => 'Requirements',
                2 => 'Database',
                3 => 'Admin',
                4 => 'Church Info',
                5 => 'Install',
            ];
            $currentStep = $currentStep ?? 1;
        @endphp
        <nav class="max-w-2xl mx-auto px-4 w-full mt-2 mb-8">
            <div class="flex items-center justify-between">
                @foreach($steps as $stepNum => $stepLabel)
                    <div class="flex flex-col items-center relative" style="z-index: 1;">
                        @if($stepNum < $currentStep)
                            {{-- Completed step --}}
                            <div class="step-circle completed w-10 h-10 rounded-full bg-green-500 flex items-center justify-center">
                                <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" />
                                </svg>
                            </div>
                        @elseif($stepNum === $currentStep)
                            {{-- Active step --}}
                            <div class="step-circle active pulse-glow w-10 h-10 rounded-full bg-indigo-600 flex items-center justify-center">
                                <span class="text-white font-bold text-sm">{{ $stepNum }}</span>
                            </div>
                        @else
                            {{-- Future step --}}
                            <div class="step-circle w-10 h-10 rounded-full bg-white border-2 border-gray-200 flex items-center justify-center">
                                <span class="text-gray-400 font-semibold text-sm">{{ $stepNum }}</span>
                            </div>
                        @endif
                        <span class="mt-2 text-xs font-medium {{ $stepNum === $currentStep ? 'text-indigo-700' : ($stepNum < $currentStep ? 'text-green-600' : 'text-gray-400') }} hidden sm:block">
                            {{ $stepLabel }}
                        </span>
                    </div>
                    @if(!$loop->last)
                        <div class="step-connector {{ $stepNum < $currentStep ? 'bg-green-400' : ($stepNum === $currentStep ? 'bg-indigo-200' : 'bg-gray-200') }} mx-1 mt-{{ $stepNum === $currentStep ? '0' : '0' }}" style="margin-bottom: 1.25rem;"></div>
                    @endif
                @endforeach
            </div>
        </nav>

        {{-- Main Content --}}
        <main class="flex-1 pb-12">
            <div class="max-w-2xl mx-auto px-4">
                <div class="bg-white rounded-2xl card-shadow overflow-hidden fade-in">
                    @yield('content')
                </div>
            </div>
        </main>

        {{-- Footer --}}
        <footer class="py-6 text-center">
            <p class="text-sm text-indigo-400 font-medium">
                <svg class="w-4 h-4 inline-block mr-1 -mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 2v8m0 0v12m0-12H6m6 0h6M8 6h8" />
                </svg>
                Church Platform Installer
            </p>
        </footer>
    </div>

    @stack('scripts')
</body>
</html>
