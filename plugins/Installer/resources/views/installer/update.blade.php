@extends('installer::installer.layout')
@php $subtitle = 'System Update'; @endphp
@section('content')
<h2 class="text-lg font-semibold text-gray-900 mb-4">System Update</h2>

<div class="flex items-center justify-between py-3 border-b border-gray-100 mb-1">
    <span class="text-sm text-gray-600">Current version</span>
    <span class="text-sm font-mono font-semibold text-gray-800">v{{ $versionInfo['current'] }}</span>
</div>
<div class="flex items-center justify-between py-3 border-b border-gray-100 mb-4">
    <span class="text-sm text-gray-600">Latest version</span>
    <span class="text-sm font-mono font-semibold {{ $versionInfo['update_available'] ? 'text-green-600' : 'text-gray-500' }}">
        v{{ $versionInfo['latest'] }}
        @if($versionInfo['update_available'])
            <span class="text-xs bg-green-100 text-green-700 px-1.5 py-0.5 rounded ml-1">Update available</span>
        @endif
    </span>
</div>

@if(! $versionInfo['update_available'])
    <p class="text-sm text-gray-500 text-center py-2">You are on the latest version.</p>
@else
    <div class="mb-4 p-3 bg-amber-50 border border-amber-100 rounded-xl text-xs text-amber-700">
        This will put the site in maintenance mode for approximately 30-60 seconds.
    </div>
    {{--
        EventSource only supports GET. We generate a signed URL so the GET /update/run
        endpoint cannot be triggered by anyone who doesn't have a valid signed token
        (signed middleware verifies the Laravel URL signature).
    --}}
    {{-- temporarySignedRoute expires in 10 min — prevents replay of a destructive action --}}
    <button id="updateBtn" data-url="{{ URL::temporarySignedRoute('update.run', now()->addMinutes(10)) }}"
        class="w-full py-2.5 rounded-xl font-semibold text-sm bg-blue-600 text-white hover:bg-blue-700 transition-colors">
        Update Now to v{{ $versionInfo['latest'] }}
    </button>
@endif

<div id="log" class="mt-6 hidden">
    <p class="text-xs font-medium text-gray-500 mb-2">Update Log</p>
    <div id="logLines" class="bg-gray-900 rounded-xl p-4 text-xs font-mono text-gray-100 space-y-1 max-h-64 overflow-y-auto"></div>
</div>
<div id="reloadBtn" class="hidden mt-4">
    <a href="/" class="block w-full text-center py-2.5 rounded-xl font-semibold text-sm bg-green-600 text-white hover:bg-green-700">
        Reload App
    </a>
</div>

<script>
document.getElementById('updateBtn')?.addEventListener('click', function() {
    this.disabled = true;
    this.textContent = 'Updating...';
    document.getElementById('log').classList.remove('hidden');
    // EventSource only supports GET — the signed URL provides CSRF-equivalent protection
    const source = new EventSource(this.dataset.url);
    source.onmessage = function(e) {
        const data = JSON.parse(e.data);
        const p = document.createElement('p');
        p.textContent = data.message;
        if (data.status === 'error') p.style.color = '#f87171';
        document.getElementById('logLines').appendChild(p);
        document.getElementById('logLines').scrollTop = 99999;
        if (data.step === 'complete' || data.status === 'error') {
            source.close();
            if (data.step === 'complete') document.getElementById('reloadBtn').classList.remove('hidden');
        }
    };
});
</script>
@endsection
