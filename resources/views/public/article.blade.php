@extends('layouts.public')

@section('title', ($article->meta_title ?? $article->title) . ' - ' . ($settings->church_name ?? config('app.name')))
@section('meta_description', $article->meta_description ?? \Illuminate\Support\Str::limit(strip_tags($article->excerpt ?? $article->body ?? ''), 160))
@section('meta_keywords', $article->meta_keywords ?? '')
@section('og_title', $article->meta_title ?? $article->title)
@section('og_type', 'article')
@if($article->cover_image)
@section('og_image', $article->cover_image)
@endif

@push('scripts')
<script type="application/ld+json">
{
    "@context": "https://schema.org",
    "@type": "Article",
    "headline": "{{ addslashes($article->title) }}",
    "description": "{{ addslashes(\Illuminate\Support\Str::limit(strip_tags($article->excerpt ?? $article->body ?? ''), 160)) }}",
    @if($article->cover_image)
    "image": "{{ $article->cover_image }}",
    @endif
    "author": {
        "@type": "Person",
        "name": "{{ addslashes($article->author->display_name ?? $article->author->name ?? '') }}"
    },
    "datePublished": "{{ $article->published_at?->toIso8601String() }}",
    "dateModified": "{{ $article->updated_at->toIso8601String() }}"
}
</script>
@endpush

@section('content')
<div class="page-hero">
    <div class="container">
        <div class="breadcrumb">
            <a href="/">Home</a> <span class="sep">/</span>
            <a href="/blog">Blog</a> <span class="sep">/</span>
            <span>{{ $article->title }}</span>
        </div>
        <h1 style="font-size:2.2rem;margin-bottom:0.5rem;">{{ $article->title }}</h1>
        <div class="card-meta" style="margin-top:0.75rem;">
            @if($article->author ?? false)<span><i class="fas fa-user"></i> {{ $article->author->display_name ?? $article->author->name }}</span>@endif
            @if($article->published_at)<span><i class="fas fa-calendar"></i> {{ $article->published_at->format('M d, Y') }}</span>@endif
            @if($article->category ?? false)<span class="badge badge-gold">{{ $article->category->name ?? $article->category }}</span>@endif
        </div>
    </div>
</div>

<div class="content-body">
    <div class="container">
        <div class="content-layout">
            <article>
                @if($article->cover_image)
                <img src="{{ $article->cover_image }}" alt="{{ $article->title }}" style="width:100%;border-radius:12px;margin-bottom:1.5rem;max-height:500px;object-fit:cover;">
                @endif

                <div class="content-body">
                    {!! $article->body !!}
                </div>

                @if($article->tags && $article->tags->count())
                <div style="margin-top:2rem;padding-top:1rem;border-top:1px solid var(--border);">
                    <span style="font-size:0.85rem;color:var(--text-muted);margin-right:0.5rem;"><i class="fas fa-tags"></i> Tags:</span>
                    @foreach($article->tags as $tag)
                    <span class="badge badge-purple" style="margin:0.15rem;">{{ $tag->name }}</span>
                    @endforeach
                </div>
                @endif
            </article>

            <aside class="sidebar">
                <div class="sidebar-widget">
                    <h4>Share This Article</h4>
                    @include('partials.share-buttons', ['title' => $article->title])
                </div>
            </aside>
        </div>
    </div>
</div>
@endsection
