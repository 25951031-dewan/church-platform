@extends('layouts.public')

@section('title', 'Library - ' . ($settings->church_name ?? config('app.name')))
@section('meta_description', 'Browse our church library - free books, resources, and study materials.')

@section('content')
<div class="page-hero">
    <div class="container">
        <div class="breadcrumb">
            <a href="/">Home</a> <span class="sep">/</span>
            <span>Library</span>
        </div>
        <h1 style="font-size:2.2rem;margin-bottom:0.5rem;">Church Library</h1>
        <p style="color:var(--text-muted);max-width:600px;">Browse our collection of books and resources to deepen your faith.</p>
    </div>
</div>

<div class="content-body">
    <div class="container">
        <div class="card-grid">
            @forelse($books as $book)
            <a href="/library/{{ $book->slug }}" class="card" style="color:var(--text);">
                @if($book->cover_image)
                <img src="/storage/{{ $book->cover_image }}" alt="{{ $book->title }}">
                @else
                <div style="height:200px;background:linear-gradient(135deg,var(--surface),var(--card));display:flex;align-items:center;justify-content:center;">
                    <i class="fas fa-book" style="font-size:3rem;color:var(--gold);opacity:0.5;"></i>
                </div>
                @endif
                <div class="card-body">
                    <h3 class="card-title">{{ $book->title }}</h3>
                    <div class="card-meta">
                        @if($book->author)<span><i class="fas fa-pen-fancy"></i> {{ $book->author }}</span>@endif
                        @if($book->is_free)<span class="badge badge-green">Free</span>@endif
                    </div>
                    @if($book->category)
                    <div class="card-meta"><span class="badge badge-gold">{{ $book->category }}</span></div>
                    @endif
                    <p class="card-excerpt">{{ \Illuminate\Support\Str::limit(strip_tags($book->description), 100) }}</p>
                    <div class="card-meta" style="margin-top:0.5rem;">
                        <span><i class="fas fa-eye"></i> {{ $book->view_count ?? 0 }} views</span>
                        <span><i class="fas fa-download"></i> {{ $book->download_count ?? 0 }} downloads</span>
                    </div>
                </div>
            </a>
            @empty
            <div style="grid-column:1/-1;text-align:center;padding:3rem;color:var(--text-muted);">
                <i class="fas fa-book" style="font-size:2.5rem;opacity:0.3;margin-bottom:1rem;display:block;"></i>
                <p>No books available at the moment.</p>
            </div>
            @endforelse
        </div>

        @if($books->hasPages())
        <div class="pagination">
            {{ $books->links('pagination::simple-default') }}
        </div>
        @endif
    </div>
</div>
@endsection
