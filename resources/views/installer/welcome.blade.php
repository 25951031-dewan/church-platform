@extends('installer.layout')

@section('content')
<div class="p-8">
    <div class="text-center mb-8">
        <div class="inline-flex items-center justify-center w-12 h-12 bg-indigo-50 rounded-xl mb-4">
            <svg class="w-6 h-6 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z" />
            </svg>
        </div>
        <h2 class="text-2xl font-bold text-gray-800">System Requirements</h2>
        <p class="text-gray-500 mt-2">Let's verify your server meets all the requirements</p>
    </div>

    {{-- PHP Version --}}
    <div class="mb-6">
        <h3 class="text-sm font-semibold text-gray-500 uppercase tracking-wider mb-3">PHP Version</h3>
        <div class="bg-gray-50 rounded-xl p-4 flex items-center justify-between">
            <div class="flex items-center">
                <div class="w-10 h-10 rounded-lg {{ $requirements['php_version'] ? 'bg-green-100' : 'bg-red-100' }} flex items-center justify-center mr-3">
                    <svg class="w-5 h-5 {{ $requirements['php_version'] ? 'text-green-600' : 'text-red-600' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M10 20l4-16m4 4l4 4-4 4M6 16l-4-4 4-4" />
                    </svg>
                </div>
                <div>
                    <p class="font-semibold text-gray-700">PHP {{ phpversion() }}</p>
                    <p class="text-xs text-gray-400">Required: >= 8.1</p>
                </div>
            </div>
            @if($requirements['php_version'])
                <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-semibold bg-green-100 text-green-700">
                    <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" />
                    </svg>
                    Passed
                </span>
            @else
                <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-semibold bg-red-100 text-red-700">
                    <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                    Failed
                </span>
            @endif
        </div>
    </div>

    {{-- PHP Extensions --}}
    <div class="mb-6">
        <h3 class="text-sm font-semibold text-gray-500 uppercase tracking-wider mb-3">PHP Extensions</h3>
        <div class="grid grid-cols-1 sm:grid-cols-2 gap-2">
            @php
                $extensions = collect($requirements)->except('php_version');
            @endphp
            @foreach($extensions as $extension => $passed)
                <div class="flex items-center p-3 rounded-lg {{ $passed ? 'bg-green-50 border border-green-100' : 'bg-red-50 border border-red-100' }}">
                    @if($passed)
                        <svg class="w-5 h-5 text-green-500 mr-3 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                    @else
                        <svg class="w-5 h-5 text-red-500 mr-3 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.5">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                    @endif
                    <span class="text-sm font-medium {{ $passed ? 'text-green-700' : 'text-red-700' }}">{{ str_replace('_', ' ', $extension) }}</span>
                </div>
            @endforeach
        </div>
    </div>

    {{-- Directory Permissions --}}
    <div class="mb-8">
        <h3 class="text-sm font-semibold text-gray-500 uppercase tracking-wider mb-3">Directory Permissions</h3>
        <div class="space-y-2">
            @php
                $permissionLabels = [
                    'storage_writable' => 'storage/',
                    'cache_writable' => 'storage/framework/cache/',
                    'sessions_writable' => 'storage/framework/sessions/',
                    'views_writable' => 'storage/framework/views/',
                    'env_writable' => '.env',
                ];
            @endphp
            @foreach($permissionLabels as $key => $directory)
                @php $writable = $permissions[$key] ?? false; @endphp
                <div class="flex items-center justify-between p-3 rounded-lg {{ $writable ? 'bg-green-50 border border-green-100' : 'bg-red-50 border border-red-100' }}">
                    <div class="flex items-center">
                        <svg class="w-5 h-5 {{ $writable ? 'text-green-500' : 'text-red-500' }} mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M3 7v10a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2h-6l-2-2H5a2 2 0 00-2 2z" />
                        </svg>
                        <code class="text-sm font-mono {{ $writable ? 'text-green-700' : 'text-red-700' }}">{{ $directory }}</code>
                    </div>
                    @if($writable)
                        <span class="text-xs font-semibold text-green-600 bg-green-100 px-2 py-0.5 rounded-full">Writable</span>
                    @else
                        <span class="text-xs font-semibold text-red-600 bg-red-100 px-2 py-0.5 rounded-full">Not Writable</span>
                    @endif
                </div>
            @endforeach
        </div>
    </div>

    {{-- Action --}}
    <div class="flex justify-end">
        @if($allPassed)
            <a href="{{ url('/install/database') }}"
               class="inline-flex items-center px-6 py-3 bg-indigo-600 hover:bg-indigo-700 text-white font-semibold rounded-xl transition-all duration-200 shadow-lg shadow-indigo-200 hover:shadow-xl hover:shadow-indigo-300 hover:-translate-y-0.5">
                Continue
                <svg class="w-5 h-5 ml-2" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M13 7l5 5m0 0l-5 5m5-5H6" />
                </svg>
            </a>
        @else
            <button disabled
                    class="inline-flex items-center px-6 py-3 bg-gray-300 text-gray-500 font-semibold rounded-xl cursor-not-allowed">
                <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z" />
                </svg>
                Fix Issues to Continue
            </button>
        @endif
    </div>
</div>
@endsection
