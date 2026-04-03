@extends('layouts.public')

@section('title', 'Bible Studies - ' . ($settings->church_name ?? config('app.name')))
@section('meta_description', 'Explore our Bible studies - grow deeper in your understanding of God\'s Word.')

@section('content')
<div class="page-hero">
    <div class="container">
        <div class="breadcrumb">
            <a href="/">Home</a> <span class="sep">/</span>
            <span>Bible Studies</span>
        </div>
        <h1 style="font-size:2.2rem;margin-bottom:0.5rem;">Bible Studies</h1>
        <p style="color:var(--text-muted);max-width:600px;">Grow deeper in your faith through our curated Bible study materials.</p>
    </div>
</div>

<div class="content-body">
    <div class="container">
        <div class="card-grid">
            @forelse($studies as $study)
            <a href="/bible-studies/{{ $study->slug }}" class="card" style="color:var(--text);">
                @if($study->cover_image)
                <img src="/storage/{{ $study->cover_image }}" alt="{{ $study->title }}">
                @else
                <div style="height:200px;background:linear-gradient(135deg,var(--surface),var(--card));display:flex;align-items:center;justify-content:center;">
                    <i class="fas fa-book-open" style="font-size:3rem;color:var(--gold);opacity:0.5;"></i>
                </div>
                @endif
                <div class="card-body">
                    <h3 class="card-title">{{ $study->title }}</h3>
                    <div class="card-meta">
                        @if($study->category)<span class="badge badge-purple">{{ ucfirst(str_replace('-', ' ', $study->category)) }}</span>@endif
                        @if($study->difficulty)<span class="badge badge-green">{{ ucfirst($study->difficulty) }}</span>@endif
                    </div>
                    @if($study->scripture_reference)
                    <div class="card-meta"><span><i class="fas fa-bible"></i> {{ $study->scripture_reference }}</span></div>
                    @endif
                    <p class="card-excerpt">{{ \Illuminate\Support\Str::limit(strip_tags($study->description), 100) }}</p>
                    <div class="card-meta" style="margin-top:0.5rem;">
                        @if($study->duration_minutes)<span><i class="fas fa-clock"></i> {{ $study->duration_minutes }} min</span>@endif
                        <span><i class="fas fa-eye"></i> {{ $study->view_count ?? 0 }} views</span>
                    </div>
                </div>
            </a>
            @empty
            <div style="grid-column:1/-1;text-align:center;padding:3rem;color:var(--text-muted);">
                <i class="fas fa-book-open" style="font-size:2.5rem;opacity:0.3;margin-bottom:1rem;display:block;"></i>
                <p>No Bible studies available at the moment.</p>
            </div>
            @endforelse
        </div>

        @if($studies->hasPages())
        <div class="pagination">
            {{ $studies->links('pagination::simple-default') }}
        </div>
        @endif
    </div>
</div>
@endsection
