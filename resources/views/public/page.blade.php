@extends('layouts.public')

@section('title', ($page->meta_title ?: $page->title) . ' - ' . ($settings->church_name ?? config('app.name')))
@section('meta_description', $page->meta_description ?: \Illuminate\Support\Str::limit(strip_tags($page->content), 160))
@section('meta_keywords', $page->meta_keywords ?? '')
@section('og_title', $page->meta_title ?: $page->title)

@section('content')
<div class="page-hero">
    <div class="container">
        <div class="breadcrumb">
            <a href="/">Home</a> <span class="sep">/</span>
            <span>{{ $page->title }}</span>
        </div>
        <h1 style="font-size:2.2rem;">{{ $page->title }}</h1>
    </div>
</div>

<div class="content-body">
    <div class="container" style="max-width:900px;">
        @if($page->featured_image)
        <img src="/storage/{{ $page->featured_image }}" alt="{{ $page->title }}" style="width:100%;border-radius:12px;margin-bottom:2rem;max-height:400px;object-fit:cover;">
        @endif

        <div class="meta-bar">
            <div class="view-count">
                <i class="fas fa-file-alt"></i> {{ $page->title }}
            </div>
            @include('partials.share-buttons', ['title' => $page->title])
        </div>

        <div class="content-body">
            {!! $page->content !!}
        </div>
    </div>
</div>
@endsection
