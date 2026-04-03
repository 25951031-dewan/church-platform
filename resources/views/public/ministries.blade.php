@extends('layouts.public')

@section('title', 'Ministries - ' . ($settings->church_name ?? config('app.name')))
@section('meta_description', 'Explore our church ministries and find ways to serve and grow in your faith.')

@section('content')
<div class="page-hero">
    <div class="container">
        <div class="breadcrumb">
            <a href="/">Home</a> <span class="sep">/</span>
            <span>Ministries</span>
        </div>
        <h1 style="font-size:2.2rem;margin-bottom:0.5rem;">Our Ministries</h1>
        <p style="color:var(--text-muted);max-width:600px;">Discover the many ways you can serve, grow, and connect with our church community.</p>
    </div>
</div>

<div class="content-body">
    <div class="container">
        <div class="card-grid">
            @forelse($ministries as $ministry)
            <a href="/ministries/{{ $ministry->slug }}" class="card" style="color:var(--text);">
                @if($ministry->image)
                <img src="/storage/{{ $ministry->image }}" alt="{{ $ministry->name }}">
                @else
                <div style="height:200px;background:linear-gradient(135deg,var(--surface),var(--card));display:flex;align-items:center;justify-content:center;">
                    <i class="fas fa-hands-helping" style="font-size:3rem;color:var(--gold);opacity:0.5;"></i>
                </div>
                @endif
                <div class="card-body">
                    <h3 class="card-title">{{ $ministry->name }}</h3>
                    @if($ministry->category)
                    <span class="badge badge-gold" style="margin-bottom:0.5rem;">{{ $ministry->category }}</span>
                    @endif
                    <p class="card-excerpt">{{ \Illuminate\Support\Str::limit(strip_tags($ministry->description), 120) }}</p>
                    @if($ministry->leader_name)
                    <div class="card-meta" style="margin-top:0.75rem;">
                        <span><i class="fas fa-user"></i> {{ $ministry->leader_name }}</span>
                    </div>
                    @endif
                    @if($ministry->meeting_schedule)
                    <div class="card-meta">
                        <span><i class="fas fa-clock"></i> {{ $ministry->meeting_schedule }}</span>
                    </div>
                    @endif
                </div>
            </a>
            @empty
            <div style="grid-column:1/-1;text-align:center;padding:3rem;color:var(--text-muted);">
                <i class="fas fa-hands-helping" style="font-size:2.5rem;opacity:0.3;margin-bottom:1rem;display:block;"></i>
                <p>No ministries available at the moment.</p>
            </div>
            @endforelse
        </div>
    </div>
</div>
@endsection
