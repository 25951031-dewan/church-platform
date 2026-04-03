<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - {{ config('app.name', 'Church Platform') }}</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        body {
            background: linear-gradient(135deg, #eef2ff 0%, #e0e7ff 25%, #c7d2fe 50%, #ddd6fe 75%, #ede9fe 100%);
        }
    </style>
</head>
<body class="min-h-screen flex items-center justify-center px-4 antialiased">
    <div class="w-full max-w-md">
        <div class="text-center mb-8">
            <div class="inline-flex items-center justify-center w-16 h-16 bg-white rounded-2xl shadow-lg mb-4">
                <svg class="w-9 h-9 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 2v8m0 0v12m0-12H6m6 0h6M8 6h8" />
                </svg>
            </div>
            <h1 class="text-2xl font-bold text-gray-800">Welcome Back</h1>
            <p class="text-gray-500 mt-1">Sign in to your admin panel</p>
        </div>

        <div class="bg-white rounded-2xl shadow-xl p-8">
            @if($errors->any())
                <div class="mb-6 bg-red-50 border border-red-200 rounded-xl p-4">
                    <div class="flex items-start">
                        <svg class="w-5 h-5 text-red-500 mt-0.5 mr-3 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                        <ul class="text-sm text-red-600 space-y-1">
                            @foreach($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                </div>
            @endif

            <!-- Login Form -->
            <div id="login-form-section">
                <form method="POST" action="{{ route('login.post') }}" class="space-y-5">
                    @csrf

                    <div>
                        <label for="email" class="block text-sm font-semibold text-gray-700 mb-1.5">Email Address</label>
                        <input type="email" name="email" id="email" value="{{ old('email') }}" required autofocus
                               class="w-full px-4 py-2.5 border border-gray-200 rounded-xl text-gray-700 focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 transition-colors bg-gray-50 focus:bg-white"
                               placeholder="admin@church.com">
                    </div>

                    <div>
                        <label for="password" class="block text-sm font-semibold text-gray-700 mb-1.5">Password</label>
                        <input type="password" name="password" id="password" required
                               class="w-full px-4 py-2.5 border border-gray-200 rounded-xl text-gray-700 focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 transition-colors bg-gray-50 focus:bg-white"
                               placeholder="Enter your password">
                    </div>

                    <div class="flex items-center justify-between">
                        <label class="flex items-center">
                            <input type="checkbox" name="remember" class="rounded border-gray-300 text-indigo-600 focus:ring-indigo-500 h-4 w-4">
                            <span class="ml-2 text-sm text-gray-600">Remember me</span>
                        </label>
                        <button type="button" onclick="document.getElementById('login-form-section').style.display='none'; document.getElementById('forgot-form-section').style.display='';" class="text-sm text-indigo-600 hover:text-indigo-800 font-medium">
                            Forgot Password?
                        </button>
                    </div>

                    <button type="submit"
                            class="w-full flex items-center justify-center px-6 py-3 bg-indigo-600 hover:bg-indigo-700 text-white font-semibold rounded-xl transition-all duration-200 shadow-lg shadow-indigo-200 hover:shadow-xl hover:shadow-indigo-300">
                        Sign In
                        <svg class="w-5 h-5 ml-2" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M13 7l5 5m0 0l-5 5m5-5H6" />
                        </svg>
                    </button>
                </form>
            </div>

            <!-- Forgot Password Form -->
            <div id="forgot-form-section" style="display: none;">
                <p class="text-sm text-gray-500 mb-5">Enter your email address and we'll send you a link to reset your password.</p>
                <div id="forgot-alert" style="display:none;" class="mb-4 p-3 rounded-xl text-sm"></div>
                <div class="space-y-5">
                    <div>
                        <label class="block text-sm font-semibold text-gray-700 mb-1.5">Email Address</label>
                        <input type="email" id="forgot-email"
                               class="w-full px-4 py-2.5 border border-gray-200 rounded-xl text-gray-700 focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 transition-colors bg-gray-50 focus:bg-white"
                               placeholder="Enter your email">
                    </div>
                    <button type="button" id="forgot-submit-btn" onclick="sendAdminResetLink()"
                            class="w-full flex items-center justify-center px-6 py-3 bg-indigo-600 hover:bg-indigo-700 text-white font-semibold rounded-xl transition-all duration-200 shadow-lg shadow-indigo-200 hover:shadow-xl hover:shadow-indigo-300">
                        Send Reset Link
                    </button>
                    <div class="text-center">
                        <button type="button" onclick="document.getElementById('forgot-form-section').style.display='none'; document.getElementById('login-form-section').style.display='';" class="text-sm text-indigo-600 hover:text-indigo-800 font-medium">
                            Back to Sign In
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <p class="text-center mt-6 text-sm text-gray-400">
            Church Platform &copy; {{ date('Y') }}
        </p>
    </div>
    <script>
    function sendAdminResetLink() {
        var email = document.getElementById('forgot-email').value.trim();
        var alertEl = document.getElementById('forgot-alert');
        var btn = document.getElementById('forgot-submit-btn');
        alertEl.style.display = 'none';

        if (!email) {
            alertEl.className = 'mb-4 p-3 rounded-xl text-sm bg-red-50 border border-red-200 text-red-600';
            alertEl.textContent = 'Please enter your email address.';
            alertEl.style.display = '';
            return;
        }

        btn.disabled = true;
        btn.textContent = 'Sending...';

        fetch('/api/forgot-password', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'Accept': 'application/json' },
            body: JSON.stringify({ email: email })
        })
        .then(function(r) { return r.json(); })
        .then(function(data) {
            btn.disabled = false;
            btn.textContent = 'Send Reset Link';
            alertEl.className = 'mb-4 p-3 rounded-xl text-sm bg-green-50 border border-green-200 text-green-700';
            alertEl.textContent = data.message || 'If that email exists, a reset link has been sent.';
            alertEl.style.display = '';
        })
        .catch(function() {
            btn.disabled = false;
            btn.textContent = 'Send Reset Link';
            alertEl.className = 'mb-4 p-3 rounded-xl text-sm bg-red-50 border border-red-200 text-red-600';
            alertEl.textContent = 'Something went wrong. Please try again.';
            alertEl.style.display = '';
        });
    }
    </script>
</body>
</html>
