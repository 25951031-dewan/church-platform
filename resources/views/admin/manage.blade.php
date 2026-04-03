@extends('layouts.admin')

@section('title', ucfirst(str_replace('-', ' ', $section)))

@section('content')
<div id="admin-app" data-section="{{ $section }}"></div>
@endsection

@push('scripts')
@viteReactRefresh
@vite('resources/js/app.jsx')
@endpush
