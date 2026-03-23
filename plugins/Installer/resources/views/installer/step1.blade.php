@extends('installer::installer.layout')
@php $step = 1; @endphp
@section('content')
<h2 class="text-lg font-semibold text-gray-900 mb-5">System Requirements</h2>
@php
$labels = [
    'php'             => 'PHP >= 8.2',
    'pdo_mysql'       => 'Extension: pdo_mysql',
    'mbstring'        => 'Extension: mbstring',
    'openssl'         => 'Extension: openssl',
    'tokenizer'       => 'Extension: tokenizer',
    'xml'             => 'Extension: xml',
    'ctype'           => 'Extension: ctype',
    'json'            => 'Extension: json',
    'bcmath'          => 'Extension: bcmath',
    'storage'         => 'storage/ writable',
    'bootstrap_cache' => 'bootstrap/cache/ writable',
    'root_htaccess'   => 'Root .htaccess',
    'public_htaccess' => 'public/.htaccess',
    'vendor'          => 'Composer dependencies (vendor/)',
];
@endphp
<div class="space-y-2 mb-6">
@foreach($requirements as $key => $pass)
<div class="flex items-center justify-between py-1.5 border-b border-gray-50">
    <span class="text-sm text-gray-700">{{ $labels[$key] ?? $key }}</span>
    <span class="{{ $pass ? 'text-green-600' : 'text-red-500' }} text-sm font-semibold">
        {{ $pass ? 'OK' : 'Failed' }}
    </span>
</div>
@endforeach
</div>
@if(! $allPassed)
    <p class="text-sm text-red-600 mb-4">Fix the items above. Directories and .htaccess are auto-fixed — refresh to re-check.</p>
@endif
<form method="POST" action="/install/step1">
    @csrf
    <button type="submit" {{ ! $allPassed ? 'disabled' : '' }}
        class="w-full py-2.5 rounded-xl font-semibold text-sm transition-colors
               {{ $allPassed ? 'bg-blue-600 text-white hover:bg-blue-700' : 'bg-gray-100 text-gray-400 cursor-not-allowed' }}">
        Next: Database Setup
    </button>
</form>
@endsection
