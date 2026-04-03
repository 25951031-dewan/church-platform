@extends('layouts.public')

@section('title', $ministry->name . ' - Ministries - ' . ($settings->church_name ?? config('app.name')))
@section('meta_description', \Illuminate\Support\Str::limit(strip_tags($ministry->description), 160))
@section('og_title', $ministry->name . ' Ministry')

@section('content')
<div class="page-hero">
    <div class="container">
        <div class="breadcrumb">
            <a href="/">Home</a> <span class="sep">/</span>
            <a href="/ministries">Ministries</a> <span class="sep">/</span>
            <span>{{ $ministry->name }}</span>
        </div>
        <h1 style="font-size:2.2rem;margin-bottom:0.5rem;">{{ $ministry->name }}</h1>
        @if($ministry->category)
        <span class="badge badge-gold">{{ $ministry->category }}</span>
        @endif
    </div>
</div>

<div class="content-body">
    <div class="container">
        <div class="content-layout">
            <article>
                @if($ministry->image)
                <img src="/storage/{{ $ministry->image }}" alt="{{ $ministry->name }}" style="width:100%;border-radius:12px;margin-bottom:1.5rem;max-height:400px;object-fit:cover;">
                @endif

                <div class="meta-bar">
                    <div class="view-count">
                        <i class="fas fa-hands-helping"></i> Ministry
                    </div>
                    @include('partials.share-buttons', ['title' => $ministry->name])
                </div>

                <div class="content-body">
                    {!! $ministry->description !!}
                </div>
            </article>

            <aside class="sidebar">
                <div class="sidebar-widget">
                    <h4>Ministry Details</h4>
                    @if($ministry->leader_name)
                    <div style="margin-bottom:0.75rem;">
                        <div style="font-size:0.8rem;color:var(--text-muted);text-transform:uppercase;letter-spacing:0.05em;">Leader</div>
                        <div style="font-weight:600;">{{ $ministry->leader_name }}</div>
                    </div>
                    @endif
                    @if($ministry->meeting_schedule)
                    <div style="margin-bottom:0.75rem;">
                        <div style="font-size:0.8rem;color:var(--text-muted);text-transform:uppercase;letter-spacing:0.05em;">Schedule</div>
                        <div>{{ $ministry->meeting_schedule }}</div>
                    </div>
                    @endif
                    @if($ministry->meeting_location)
                    <div style="margin-bottom:0.75rem;">
                        <div style="font-size:0.8rem;color:var(--text-muted);text-transform:uppercase;letter-spacing:0.05em;">Location</div>
                        <div>{{ $ministry->meeting_location }}</div>
                    </div>
                    @endif
                    @if($ministry->leader_email)
                    <div style="margin-bottom:0.75rem;">
                        <div style="font-size:0.8rem;color:var(--text-muted);text-transform:uppercase;letter-spacing:0.05em;">Contact</div>
                        <a href="mailto:{{ $ministry->leader_email }}">{{ $ministry->leader_email }}</a>
                    </div>
                    @endif
                </div>

                <div class="sidebar-widget">
                    <h4>Share</h4>
                    @include('partials.share-buttons', ['title' => $ministry->name])
                </div>
            </aside>
        </div>
    </div>
</div>
@endsection
