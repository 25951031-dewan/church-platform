@extends('installer::installer.layout')
@php $step = 2; @endphp
@section('content')
<h2 class="text-lg font-semibold text-gray-900 mb-5">Database & App Settings</h2>
@if($errors->any())
    <div class="mb-4 p-3 bg-red-50 border border-red-100 rounded-xl text-sm text-red-700">{{ $errors->first() }}</div>
@endif
<form method="POST" action="/install/step2" class="space-y-4">
    @csrf
    <div>
        <label class="block text-xs font-medium text-gray-600 mb-1">App Name</label>
        <input type="text" name="app_name" value="{{ old('app_name', 'Church Platform') }}"
               class="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500 outline-none">
    </div>
    <div class="grid grid-cols-3 gap-3">
        <div class="col-span-2">
            <label class="block text-xs font-medium text-gray-600 mb-1">DB Host</label>
            <input type="text" name="db_host" value="{{ old('db_host', '127.0.0.1') }}"
                   class="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500 outline-none">
        </div>
        <div>
            <label class="block text-xs font-medium text-gray-600 mb-1">Port</label>
            <input type="number" name="db_port" value="{{ old('db_port', '3306') }}"
                   class="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500 outline-none">
        </div>
    </div>
    <div>
        <label class="block text-xs font-medium text-gray-600 mb-1">Database Name</label>
        <input type="text" name="db_database" value="{{ old('db_database', 'church_platform') }}"
               class="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500 outline-none">
    </div>
    <div class="grid grid-cols-2 gap-3">
        <div>
            <label class="block text-xs font-medium text-gray-600 mb-1">DB Username</label>
            <input type="text" name="db_username" value="{{ old('db_username', 'root') }}"
                   class="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500 outline-none">
        </div>
        <div>
            <label class="block text-xs font-medium text-gray-600 mb-1">DB Password</label>
            <input type="password" name="db_password"
                   class="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500 outline-none">
        </div>
    </div>
    <button type="submit"
        class="w-full py-2.5 rounded-xl font-semibold text-sm bg-blue-600 text-white hover:bg-blue-700 transition-colors mt-2">
        Next: Admin Account
    </button>
</form>
@endsection
