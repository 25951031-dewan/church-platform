@extends('layouts.public')

@section('title', $study->title . ' - Bible Studies - ' . ($settings->church_name ?? config('app.name')))
@section('meta_description', \Illuminate\Support\Str::limit(strip_tags($study->description), 160))
@section('og_title', $study->title)
@if($study->cover_image)
@section('og_image', url('/storage/' . $study->cover_image))
@endif

@section('content')
<div class="page-hero">
    <div class="container">
        <div class="breadcrumb">
            <a href="/">Home</a> <span class="sep">/</span>
            <a href="/bible-studies">Bible Studies</a> <span class="sep">/</span>
            <span>{{ $study->title }}</span>
        </div>
        <h1 style="font-size:2.2rem;margin-bottom:0.5rem;">{{ $study->title }}</h1>
        <div class="card-meta" style="margin-top:0.75rem;">
            @if($study->category)<span class="badge badge-purple">{{ ucfirst(str_replace('-', ' ', $study->category)) }}</span>@endif
            @if($study->difficulty)<span class="badge badge-green">{{ ucfirst($study->difficulty) }}</span>@endif
            @if($study->author)<span><i class="fas fa-user"></i> {{ $study->author }}</span>@endif
        </div>
    </div>
</div>

<div class="content-body">
    <div class="container">
        <div class="content-layout">
            <article>
                @if($study->cover_image)
                <img src="/storage/{{ $study->cover_image }}" alt="{{ $study->title }}" style="width:100%;border-radius:12px;margin-bottom:1.5rem;max-height:400px;object-fit:cover;">
                @endif

                <div class="meta-bar">
                    <div class="view-count">
                        <i class="fas fa-eye"></i> {{ number_format($study->view_count ?? 0) }} views
                        @if($study->duration_minutes)
                        &nbsp;&bull;&nbsp; <i class="fas fa-clock"></i> {{ $study->duration_minutes }} min read
                        @endif
                    </div>
                    @include('partials.share-buttons', ['title' => $study->title])
                </div>

                @if($study->scripture_reference)
                <div style="background:var(--card);border:1px solid var(--border);border-radius:12px;padding:1rem 1.25rem;margin-bottom:1.5rem;">
                    <div style="font-size:0.8rem;color:var(--text-muted);text-transform:uppercase;letter-spacing:0.05em;margin-bottom:0.25rem;">Scripture Reference</div>
                    <div style="font-size:1.1rem;color:var(--gold);font-weight:600;"><i class="fas fa-bible"></i> {{ $study->scripture_reference }}</div>
                </div>
                @endif

                <div class="content-body">
                    {!! $study->content !!}
                </div>

                @if($study->tags)
                <div style="margin-top:2rem;padding-top:1rem;border-top:1px solid var(--border);">
                    <span style="font-size:0.85rem;color:var(--text-muted);margin-right:0.5rem;"><i class="fas fa-tags"></i> Tags:</span>
                    @foreach(explode(',', $study->tags) as $tag)
                    <span class="badge badge-purple" style="margin:0.15rem;">{{ trim($tag) }}</span>
                    @endforeach
                </div>
                @endif
            </article>

            <aside class="sidebar">
                <div class="sidebar-widget">
                    <h4>Study Details</h4>
                    @if($study->category)
                    <div style="margin-bottom:0.75rem;">
                        <div style="font-size:0.8rem;color:var(--text-muted);text-transform:uppercase;">Category</div>
                        <div>{{ ucfirst(str_replace('-', ' ', $study->category)) }}</div>
                    </div>
                    @endif
                    @if($study->difficulty)
                    <div style="margin-bottom:0.75rem;">
                        <div style="font-size:0.8rem;color:var(--text-muted);text-transform:uppercase;">Difficulty</div>
                        <span class="badge badge-green">{{ ucfirst($study->difficulty) }}</span>
                    </div>
                    @endif
                    @if($study->duration_minutes)
                    <div style="margin-bottom:0.75rem;">
                        <div style="font-size:0.8rem;color:var(--text-muted);text-transform:uppercase;">Duration</div>
                        <div>{{ $study->duration_minutes }} minutes</div>
                    </div>
                    @endif
                </div>

                @if($study->attachment)
                <div class="sidebar-widget">
                    <h4>Download</h4>
                    <a href="/storage/{{ $study->attachment }}" download class="btn btn-gold" style="width:100%;justify-content:center;">
                        <i class="fas fa-download"></i> Download PDF
                    </a>
                </div>
                @endif

                <div class="sidebar-widget">
                    <h4>Share</h4>
                    @include('partials.share-buttons', ['title' => $study->title])
                </div>
            </aside>
        </div>
    </div>
</div>
@endsection
