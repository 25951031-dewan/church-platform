@extends('layouts.public')

@section('title', ($post->meta_title ?: $post->title) . ' - ' . ($settings->church_name ?? config('app.name')))
@section('meta_description', $post->meta_description ?: \Illuminate\Support\Str::limit(strip_tags($post->excerpt ?: $post->content), 160))
@section('meta_keywords', $post->meta_keywords ?? '')
@section('og_title', $post->meta_title ?: $post->title)
@section('og_type', 'article')
@if($post->featured_image)
@section('og_image', url('/storage/' . $post->featured_image))
@endif

@section('content')
<div class="page-hero">
    <div class="container">
        <div class="breadcrumb">
            <a href="/">Home</a> <span class="sep">/</span>
            <a href="/blog">Blog</a> <span class="sep">/</span>
            <span>{{ $post->title }}</span>
        </div>
        <h1 style="font-size:2.2rem;margin-bottom:0.5rem;">{{ $post->title }}</h1>
        <div class="card-meta" style="margin-top:0.75rem;">
            @if($post->author_name)<span><i class="fas fa-user"></i> {{ $post->author_name }}</span>@endif
            @if($post->published_at)<span><i class="fas fa-calendar"></i> {{ \Carbon\Carbon::parse($post->published_at)->format('M d, Y') }}</span>@endif
            @if($post->category)<span class="badge badge-gold">{{ $post->category }}</span>@endif
        </div>
    </div>
</div>

<div class="content-body">
    <div class="container">
        <div class="content-layout">
            <article>
                @if($post->featured_image)
                <img src="/storage/{{ $post->featured_image }}" alt="{{ $post->title }}" style="width:100%;border-radius:12px;margin-bottom:1.5rem;max-height:500px;object-fit:cover;">
                @endif

                <div class="meta-bar">
                    <div class="view-count">
                        <i class="fas fa-eye"></i> {{ number_format($post->view_count) }} views
                    </div>
                    @include('partials.share-buttons', ['title' => $post->title])
                </div>

                <div class="content-body">
                    {!! $post->content !!}
                </div>

                @if($post->tags)
                <div style="margin-top:2rem;padding-top:1rem;border-top:1px solid var(--border);">
                    <span style="font-size:0.85rem;color:var(--text-muted);margin-right:0.5rem;"><i class="fas fa-tags"></i> Tags:</span>
                    @foreach(explode(',', $post->tags) as $tag)
                    <span class="badge badge-purple" style="margin:0.15rem;">{{ trim($tag) }}</span>
                    @endforeach
                </div>
                @endif
            </article>

            <aside class="sidebar">
                @if($relatedPosts->count())
                <div class="sidebar-widget">
                    <h4>Related Posts</h4>
                    @foreach($relatedPosts as $related)
                    <a href="/blog/{{ $related->slug }}" style="display:flex;gap:0.75rem;margin-bottom:1rem;color:var(--text);">
                        @if($related->featured_image)
                        <img src="/storage/{{ $related->featured_image }}" alt="" style="width:60px;height:60px;border-radius:8px;object-fit:cover;flex-shrink:0;">
                        @endif
                        <div>
                            <div style="font-size:0.9rem;font-weight:600;line-height:1.3;">{{ $related->title }}</div>
                            <div style="font-size:0.75rem;color:var(--text-muted);">{{ $related->published_at ? \Carbon\Carbon::parse($related->published_at)->format('M d, Y') : '' }}</div>
                        </div>
                    </a>
                    @endforeach
                </div>
                @endif

                <div class="sidebar-widget">
                    <h4>Share This Post</h4>
                    @include('partials.share-buttons', ['title' => $post->title])
                </div>
            </aside>
        </div>
    </div>
</div>
@endsection
