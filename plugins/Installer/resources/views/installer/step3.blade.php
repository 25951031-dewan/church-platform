@extends('installer::installer.layout')
@php $step = 3; @endphp
@section('content')
<h2 class="text-lg font-semibold text-gray-900 mb-5">Admin Account</h2>
@if($errors->any())
    <div class="mb-4 p-3 bg-red-50 border border-red-100 rounded-xl text-sm text-red-700">{{ $errors->first() }}</div>
@endif
<form method="POST" action="/install/step3" class="space-y-4">
    @csrf
    <div>
        <label class="block text-xs font-medium text-gray-600 mb-1">Your Name</label>
        <input type="text" name="admin_name" value="{{ old('admin_name') }}"
               class="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500 outline-none">
        @error('admin_name')<p class="text-xs text-red-500 mt-1">{{ $message }}</p>@enderror
    </div>
    <div>
        <label class="block text-xs font-medium text-gray-600 mb-1">Admin Email</label>
        <input type="email" name="admin_email" value="{{ old('admin_email') }}"
               class="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500 outline-none">
        @error('admin_email')<p class="text-xs text-red-500 mt-1">{{ $message }}</p>@enderror
    </div>
    <div>
        <label class="block text-xs font-medium text-gray-600 mb-1">Password</label>
        <input type="password" name="admin_password"
               class="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500 outline-none">
        @error('admin_password')<p class="text-xs text-red-500 mt-1">{{ $message }}</p>@enderror
    </div>
    <button type="submit"
        class="w-full py-2.5 rounded-xl font-semibold text-sm bg-green-600 text-white hover:bg-green-700 transition-colors mt-2">
        Complete Installation
    </button>
</form>
@endsection
