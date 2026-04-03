@extends('layouts.public')

@section('title', 'About Us - ' . ($settings->church_name ?? config('app.name')))
@section('meta_description', \Illuminate\Support\Str::limit(strip_tags($settings->about_text ?? ''), 160))

@section('content')
<div class="page-hero">
    <div class="container">
        <div class="breadcrumb">
            <a href="/">Home</a> <span class="sep">/</span>
            <span>About</span>
        </div>
        <h1 style="font-size:2.2rem;margin-bottom:0.5rem;">About {{ $settings->church_name ?? 'Our Church' }}</h1>
        @if($settings->tagline)
        <p style="color:var(--gold);font-size:1.15rem;font-style:italic;">{{ $settings->tagline }}</p>
        @endif
    </div>
</div>

<div class="content-body">
    <div class="container" style="max-width:900px;">
        <div class="meta-bar">
            <div class="view-count"><i class="fas fa-church"></i> About Us</div>
            @include('partials.share-buttons', ['title' => 'About ' . ($settings->church_name ?? 'Our Church')])
        </div>

        @if($settings->banner)
        <img src="/storage/{{ $settings->banner }}" alt="{{ $settings->church_name }}" style="width:100%;border-radius:12px;margin-bottom:2rem;max-height:400px;object-fit:cover;">
        @endif

        @if($settings->about_text)
        <div class="content-body" style="margin-bottom:2.5rem;">
            {!! $settings->about_text !!}
        </div>
        @endif

        <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(280px,1fr));gap:1.5rem;margin-bottom:2.5rem;">
            @if($settings->mission_statement)
            <div style="background:var(--card);border:1px solid var(--border);border-radius:12px;padding:1.5rem;">
                <h3 style="font-size:1.2rem;margin-bottom:0.75rem;display:flex;align-items:center;gap:0.5rem;">
                    <i class="fas fa-bullseye" style="color:var(--gold);"></i> Our Mission
                </h3>
                <p style="color:var(--text-muted);line-height:1.6;">{{ $settings->mission_statement }}</p>
            </div>
            @endif

            @if($settings->vision_statement)
            <div style="background:var(--card);border:1px solid var(--border);border-radius:12px;padding:1.5rem;">
                <h3 style="font-size:1.2rem;margin-bottom:0.75rem;display:flex;align-items:center;gap:0.5rem;">
                    <i class="fas fa-eye" style="color:var(--gold);"></i> Our Vision
                </h3>
                <p style="color:var(--text-muted);line-height:1.6;">{{ $settings->vision_statement }}</p>
            </div>
            @endif
        </div>

        @if($settings->pastor_name)
        <div style="background:var(--card);border:1px solid var(--border);border-radius:12px;padding:2rem;margin-bottom:2.5rem;display:flex;gap:1.5rem;align-items:center;">
            <div style="width:80px;height:80px;background:var(--gold);border-radius:50%;display:flex;align-items:center;justify-content:center;flex-shrink:0;">
                <i class="fas fa-user" style="font-size:2rem;color:#1a1a2e;"></i>
            </div>
            <div>
                <h3 style="font-size:1.3rem;margin-bottom:0.25rem;">{{ $settings->pastor_name }}</h3>
                @if($settings->pastor_title)
                <p style="color:var(--gold);font-size:0.95rem;">{{ $settings->pastor_title }}</p>
                @endif
            </div>
        </div>
        @endif

        @if($settings->service_times)
        <div style="background:var(--card);border:1px solid var(--border);border-radius:12px;padding:1.5rem;margin-bottom:2.5rem;">
            <h3 style="font-size:1.2rem;margin-bottom:1rem;display:flex;align-items:center;gap:0.5rem;">
                <i class="fas fa-clock" style="color:var(--gold);"></i> Service Times
            </h3>
            <div style="color:var(--text-muted);line-height:1.8;white-space:pre-line;">{{ $settings->service_times }}</div>
        </div>
        @endif

        @if($settings->address || $settings->phone || $settings->email)
        <div style="background:var(--card);border:1px solid var(--border);border-radius:12px;padding:1.5rem;margin-bottom:2.5rem;">
            <h3 style="font-size:1.2rem;margin-bottom:1rem;display:flex;align-items:center;gap:0.5rem;">
                <i class="fas fa-map-marker-alt" style="color:var(--gold);"></i> Contact
            </h3>
            @if($settings->address)
            <p style="margin-bottom:0.5rem;color:var(--text-muted);">{{ $settings->address }}@if($settings->city), {{ $settings->city }}@endif @if($settings->state){{ $settings->state }}@endif {{ $settings->zip_code ?? '' }}</p>
            @endif
            @if($settings->phone)
            <p style="margin-bottom:0.5rem;"><a href="tel:{{ $settings->phone }}"><i class="fas fa-phone"></i> {{ $settings->phone }}</a></p>
            @endif
            @if($settings->email)
            <p><a href="mailto:{{ $settings->email }}"><i class="fas fa-envelope"></i> {{ $settings->email }}</a></p>
            @endif
        </div>
        @endif

        @if($ministries->count())
        <h2 style="font-size:1.6rem;margin-bottom:1.5rem;">Featured Ministries</h2>
        <div class="card-grid" style="margin-bottom:2rem;">
            @foreach($ministries as $ministry)
            <a href="/ministries/{{ $ministry->slug }}" class="card" style="color:var(--text);">
                <div class="card-body">
                    <h3 class="card-title">{{ $ministry->name }}</h3>
                    <p class="card-excerpt">{{ \Illuminate\Support\Str::limit(strip_tags($ministry->description), 80) }}</p>
                </div>
            </a>
            @endforeach
        </div>
        @endif

        @if($settings->google_maps_embed)
        <div style="margin-top:2rem;border-radius:12px;overflow:hidden;">
            {!! $settings->google_maps_embed !!}
        </div>
        @endif
    </div>
</div>
@endsection
