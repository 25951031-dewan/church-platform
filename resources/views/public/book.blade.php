@extends('layouts.public')

@section('title', $book->title . ' - Library - ' . ($settings->church_name ?? config('app.name')))
@section('meta_description', \Illuminate\Support\Str::limit(strip_tags($book->description), 160))
@section('og_title', $book->title . ' by ' . $book->author)
@if($book->cover_image)
@section('og_image', url('/storage/' . $book->cover_image))
@endif

@section('content')
<div class="page-hero">
    <div class="container">
        <div class="breadcrumb">
            <a href="/">Home</a> <span class="sep">/</span>
            <a href="/library">Library</a> <span class="sep">/</span>
            <span>{{ $book->title }}</span>
        </div>
        <h1 style="font-size:2.2rem;margin-bottom:0.5rem;">{{ $book->title }}</h1>
        <div class="card-meta" style="margin-top:0.75rem;">
            @if($book->author)<span><i class="fas fa-pen-fancy"></i> {{ $book->author }}</span>@endif
            @if($book->category)<span class="badge badge-gold">{{ $book->category }}</span>@endif
            @if($book->is_free)<span class="badge badge-green">Free</span>@endif
        </div>
    </div>
</div>

<div class="content-body">
    <div class="container">
        <div class="content-layout">
            <article>
                @if($book->cover_image)
                <img src="/storage/{{ $book->cover_image }}" alt="{{ $book->title }}" style="max-width:300px;border-radius:12px;margin-bottom:1.5rem;">
                @endif

                <div class="meta-bar">
                    <div class="view-count">
                        <i class="fas fa-eye"></i> {{ number_format($book->view_count ?? 0) }} views
                        &nbsp;&bull;&nbsp; <i class="fas fa-download"></i> {{ number_format($book->download_count ?? 0) }} downloads
                    </div>
                    @include('partials.share-buttons', ['title' => $book->title])
                </div>

                <div class="content-body">
                    {!! $book->description !!}
                </div>
            </article>

            <aside class="sidebar">
                <div class="sidebar-widget">
                    <h4>Book Details</h4>
                    @if($book->author)
                    <div style="margin-bottom:0.75rem;">
                        <div style="font-size:0.8rem;color:var(--text-muted);text-transform:uppercase;">Author</div>
                        <div>{{ $book->author }}</div>
                    </div>
                    @endif
                    @if($book->publisher)
                    <div style="margin-bottom:0.75rem;">
                        <div style="font-size:0.8rem;color:var(--text-muted);text-transform:uppercase;">Publisher</div>
                        <div>{{ $book->publisher }}</div>
                    </div>
                    @endif
                    @if($book->isbn)
                    <div style="margin-bottom:0.75rem;">
                        <div style="font-size:0.8rem;color:var(--text-muted);text-transform:uppercase;">ISBN</div>
                        <div>{{ $book->isbn }}</div>
                    </div>
                    @endif
                    @if($book->pages)
                    <div style="margin-bottom:0.75rem;">
                        <div style="font-size:0.8rem;color:var(--text-muted);text-transform:uppercase;">Pages</div>
                        <div>{{ $book->pages }}</div>
                    </div>
                    @endif
                    @if($book->publish_year)
                    <div style="margin-bottom:0.75rem;">
                        <div style="font-size:0.8rem;color:var(--text-muted);text-transform:uppercase;">Year</div>
                        <div>{{ $book->publish_year }}</div>
                    </div>
                    @endif
                </div>

                @if($book->pdf_file)
                <div class="sidebar-widget">
                    <h4>Download</h4>
                    <a href="/storage/{{ $book->pdf_file }}" download class="btn btn-gold" style="width:100%;justify-content:center;">
                        <i class="fas fa-download"></i> Download PDF
                    </a>
                </div>
                @endif

                <div class="sidebar-widget">
                    <h4>Share</h4>
                    @include('partials.share-buttons', ['title' => $book->title])
                </div>
            </aside>
        </div>
    </div>
</div>
@endsection
