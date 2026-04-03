@extends('installer.layout')

@section('content')
<div class="p-8" id="finalize-app">
    <div class="text-center mb-8">
        <div class="inline-flex items-center justify-center w-12 h-12 bg-indigo-50 rounded-xl mb-4">
            <svg class="w-6 h-6 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
            </svg>
        </div>
        <h2 class="text-2xl font-bold text-gray-800">Ready to Install</h2>
        <p class="text-gray-500 mt-2">Everything is configured. Click install to set up your church platform.</p>
    </div>

    <div class="bg-indigo-50 border border-indigo-100 rounded-xl p-5 mb-6">
        <h3 class="text-sm font-semibold text-indigo-800 mb-3">The installer will:</h3>
        <ul class="space-y-2 text-sm text-indigo-700">
            <li class="flex items-center">
                <svg class="w-4 h-4 mr-2 text-indigo-500 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" />
                </svg>
                Generate application encryption key
            </li>
            <li class="flex items-center">
                <svg class="w-4 h-4 mr-2 text-indigo-500 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" />
                </svg>
                Create all database tables
            </li>
            <li class="flex items-center">
                <svg class="w-4 h-4 mr-2 text-indigo-500 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" />
                </svg>
                Create your admin account
            </li>
            <li class="flex items-center">
                <svg class="w-4 h-4 mr-2 text-indigo-500 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" />
                </svg>
                Save your church information
            </li>
            <li class="flex items-center">
                <svg class="w-4 h-4 mr-2 text-indigo-500 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" />
                </svg>
                Set up file storage links
            </li>
        </ul>
    </div>

    <div id="install-status" class="hidden mb-6">
        <div id="install-progress" class="bg-gray-50 rounded-xl p-5">
            <div class="flex items-center mb-3">
                <svg class="animate-spin w-5 h-5 text-indigo-600 mr-3" fill="none" viewBox="0 0 24 24">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                </svg>
                <span class="text-sm font-medium text-gray-700">Installing... Please wait.</span>
            </div>
            <div class="w-full bg-gray-200 rounded-full h-2">
                <div id="progress-bar" class="bg-indigo-600 h-2 rounded-full transition-all duration-500" style="width: 0%"></div>
            </div>
        </div>
    </div>

    <div id="install-success" class="hidden mb-6">
        <div class="bg-green-50 border border-green-200 rounded-xl p-5 text-center">
            <svg class="w-12 h-12 text-green-500 mx-auto mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
            </svg>
            <h3 class="text-lg font-bold text-green-800 mb-1">Installation Complete!</h3>
            <p class="text-sm text-green-600 mb-4">Your church platform has been set up successfully.</p>
            <a href="/admin" class="inline-flex items-center px-6 py-3 bg-green-600 hover:bg-green-700 text-white font-semibold rounded-xl transition-colors">
                Go to Admin Panel
                <svg class="w-5 h-5 ml-2" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M13 7l5 5m0 0l-5 5m5-5H6" />
                </svg>
            </a>
        </div>
    </div>

    <div id="install-error" class="hidden mb-6">
        <div class="bg-red-50 border border-red-200 rounded-xl p-5">
            <div class="flex items-start">
                <svg class="w-5 h-5 text-red-500 mt-0.5 mr-3 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
                <div>
                    <h4 class="text-sm font-semibold text-red-800 mb-1">Installation Failed</h4>
                    <p id="error-message" class="text-sm text-red-600"></p>
                </div>
            </div>
        </div>
    </div>

    <div id="install-actions" class="flex items-center justify-between pt-2">
        <a href="{{ url('/install/church') }}"
           class="inline-flex items-center px-4 py-2.5 text-gray-600 hover:text-gray-800 font-medium rounded-xl hover:bg-gray-100 transition-colors">
            <svg class="w-5 h-5 mr-1.5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M11 17l-5-5m0 0l5-5m-5 5h12" />
            </svg>
            Back
        </a>
        <button id="install-btn" onclick="runInstall()"
                class="inline-flex items-center px-6 py-3 bg-indigo-600 hover:bg-indigo-700 text-white font-semibold rounded-xl transition-all duration-200 shadow-lg shadow-indigo-200 hover:shadow-xl hover:shadow-indigo-300 hover:-translate-y-0.5">
            <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4" />
            </svg>
            Install Now
        </button>
    </div>
</div>
@endsection

@push('scripts')
<script>
    async function runInstall() {
        const btn = document.getElementById('install-btn');
        const status = document.getElementById('install-status');
        const success = document.getElementById('install-success');
        const error = document.getElementById('install-error');
        const actions = document.getElementById('install-actions');
        const progressBar = document.getElementById('progress-bar');

        btn.disabled = true;
        btn.classList.add('opacity-50', 'cursor-not-allowed');
        status.classList.remove('hidden');

        let progress = 0;
        const interval = setInterval(() => {
            progress = Math.min(progress + 10, 90);
            progressBar.style.width = progress + '%';
        }, 500);

        try {
            const response = await fetch('{{ url("/install/finalize") }}', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                },
            });

            clearInterval(interval);
            const data = await response.json();

            if (data.success) {
                progressBar.style.width = '100%';
                setTimeout(() => {
                    status.classList.add('hidden');
                    success.classList.remove('hidden');
                    actions.classList.add('hidden');
                }, 500);
            } else {
                throw new Error(data.message || 'Installation failed');
            }
        } catch (err) {
            clearInterval(interval);
            status.classList.add('hidden');
            error.classList.remove('hidden');
            document.getElementById('error-message').textContent = err.message;
            btn.disabled = false;
            btn.classList.remove('opacity-50', 'cursor-not-allowed');
        }
    }
</script>
@endpush
