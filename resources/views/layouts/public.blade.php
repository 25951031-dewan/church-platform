<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="theme-color" content="#0C0E12">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">

    {{-- SEO Meta --}}
    <title>@yield('title', $settings->church_name ?? config('app.name', 'Grace Community Church'))</title>
    <meta name="description" content="@yield('meta_description', $settings->meta_description ?? 'Welcome to our church community.')">
    <meta name="keywords" content="@yield('meta_keywords', $settings->meta_keywords ?? '')">

    {{-- Open Graph --}}
    <meta property="og:title" content="@yield('og_title', $settings->church_name ?? config('app.name'))">
    <meta property="og:description" content="@yield('meta_description', $settings->meta_description ?? '')">
    <meta property="og:type" content="@yield('og_type', 'website')">
    <meta property="og:url" content="{{ url()->current() }}">
    @hasSection('og_image')
    <meta property="og:image" content="@yield('og_image')">
    @endif

    {{-- Twitter Card --}}
    <meta name="twitter:card" content="summary_large_image">
    <meta name="twitter:title" content="@yield('og_title', $settings->church_name ?? config('app.name'))">
    <meta name="twitter:description" content="@yield('meta_description', '')">
    @hasSection('og_image')
    <meta name="twitter:image" content="@yield('og_image')">
    @endif

    <link rel="canonical" href="{{ url()->current() }}">
    <link rel="manifest" href="/manifest.json">
    <link rel="icon" href="data:image/svg+xml,<svg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 100 100'><text y='.9em' font-size='90'>&#x271D;&#xFE0F;</text></svg>">
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:ital,wght@0,400;0,600;0,700;0,800;1,400;1,600&family=Source+Sans+3:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css" crossorigin="anonymous" referrerpolicy="no-referrer" />

    <style>
        :root {
            --bg: #0C0E12; --surface: #161921; --card: #1C1F2A; --border: #2A2D3A;
            --text: #E8E6E1; --text-muted: #9CA3AF; --gold: #C9A84C; --cream: #F5F0E8;
        }
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Source Sans 3', sans-serif; background: var(--bg); color: var(--text); line-height: 1.7; }
        h1, h2, h3, h4 { font-family: 'Playfair Display', serif; color: var(--cream); }
        a { color: var(--gold); text-decoration: none; transition: color 0.2s; }
        a:hover { color: #E0C068; }
        .container { max-width: 1200px; margin: 0 auto; padding: 0 1.5rem; }
        .btn { display: inline-flex; align-items: center; gap: 0.5rem; padding: 0.6rem 1.5rem; border-radius: 8px; font-weight: 600; font-size: 0.9rem; border: none; cursor: pointer; transition: all 0.2s; }
        .btn-gold { background: var(--gold); color: #1a1a2e; }
        .btn-gold:hover { background: #E0C068; color: #1a1a2e; }
        .btn-outline { border: 1.5px solid var(--gold); color: var(--gold); background: transparent; }
        .btn-outline:hover { background: var(--gold); color: #1a1a2e; }

        /* Header */
        .site-header { background: var(--surface); border-bottom: 1px solid var(--border); padding: 1rem 0; position: sticky; top: 0; z-index: 100; backdrop-filter: blur(10px); }
        .site-header .container { display: flex; align-items: center; justify-content: space-between; }
        .site-header .logo { font-family: 'Playfair Display', serif; font-size: 1.4rem; font-weight: 700; color: var(--cream); }
        .site-header nav a { color: var(--text-muted); margin-left: 1.5rem; font-size: 0.95rem; font-weight: 500; }
        .site-header nav a:hover, .site-header nav a.active { color: var(--gold); }

        /* Hero / Breadcrumb */
        .page-hero { background: linear-gradient(135deg, var(--surface), var(--card)); padding: 3rem 0 2.5rem; border-bottom: 1px solid var(--border); }
        .breadcrumb { display: flex; align-items: center; gap: 0.5rem; font-size: 0.85rem; color: var(--text-muted); margin-bottom: 1rem; }
        .breadcrumb a { color: var(--gold); }
        .breadcrumb .sep { opacity: 0.5; }

        /* Content */
        .content-body { padding: 3rem 0; }
        .content-body img { max-width: 100%; border-radius: 12px; }
        .content-body p { margin-bottom: 1rem; color: var(--text); }
        .content-body h2 { margin: 2rem 0 1rem; font-size: 1.6rem; }
        .content-body h3 { margin: 1.5rem 0 0.75rem; font-size: 1.3rem; }
        .content-body ul, .content-body ol { margin: 1rem 0; padding-left: 1.5rem; }
        .content-body li { margin-bottom: 0.5rem; }
        .content-body blockquote { border-left: 3px solid var(--gold); padding: 1rem 1.5rem; margin: 1.5rem 0; background: var(--card); border-radius: 0 8px 8px 0; font-style: italic; }

        /* Card Grid */
        .card-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(320px, 1fr)); gap: 1.5rem; }
        .card { background: var(--card); border: 1px solid var(--border); border-radius: 12px; overflow: hidden; transition: transform 0.2s, box-shadow 0.2s; }
        .card:hover { transform: translateY(-2px); box-shadow: 0 8px 24px rgba(0,0,0,0.3); }
        .card img { width: 100%; height: 200px; object-fit: cover; }
        .card-body { padding: 1.25rem; }
        .card-title { font-size: 1.15rem; margin-bottom: 0.5rem; }
        .card-meta { font-size: 0.82rem; color: var(--text-muted); display: flex; align-items: center; gap: 1rem; margin-bottom: 0.5rem; }
        .card-excerpt { font-size: 0.92rem; color: var(--text-muted); line-height: 1.5; }

        /* Sidebar */
        .content-layout { display: grid; grid-template-columns: 1fr 340px; gap: 2.5rem; }
        .sidebar { position: sticky; top: 5rem; align-self: start; }
        .sidebar-widget { background: var(--card); border: 1px solid var(--border); border-radius: 12px; padding: 1.25rem; margin-bottom: 1.5rem; }
        .sidebar-widget h4 { font-size: 1rem; margin-bottom: 1rem; padding-bottom: 0.5rem; border-bottom: 1px solid var(--border); }

        /* View Count & Share */
        .meta-bar { display: flex; align-items: center; justify-content: space-between; padding: 0.75rem 0; margin: 1rem 0; border-top: 1px solid var(--border); border-bottom: 1px solid var(--border); }
        .view-count { display: flex; align-items: center; gap: 0.4rem; color: var(--text-muted); font-size: 0.85rem; }
        .share-buttons { display: flex; gap: 0.5rem; }
        .share-btn { display: inline-flex; align-items: center; justify-content: center; width: 36px; height: 36px; border-radius: 50%; border: 1px solid var(--border); color: var(--text-muted); background: var(--surface); transition: all 0.2s; font-size: 0.9rem; }
        .share-btn:hover { color: var(--gold); border-color: var(--gold); }
        .share-btn.fb:hover { color: #1877F2; border-color: #1877F2; }
        .share-btn.tw:hover { color: #1DA1F2; border-color: #1DA1F2; }
        .share-btn.wa:hover { color: #25D366; border-color: #25D366; }
        .share-btn.li:hover { color: #0A66C2; border-color: #0A66C2; }

        /* Badge */
        .badge { display: inline-block; padding: 0.2rem 0.6rem; border-radius: 999px; font-size: 0.75rem; font-weight: 600; }
        .badge-gold { background: rgba(201,168,76,0.15); color: var(--gold); }
        .badge-green { background: rgba(34,197,94,0.15); color: #22C55E; }
        .badge-purple { background: rgba(168,85,247,0.15); color: #A855F7; }

        /* Footer */
        .site-footer { background: var(--surface); border-top: 1px solid var(--border); padding: 2.5rem 0; margin-top: 3rem; }
        .site-footer .container { display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 1rem; }
        .site-footer p { color: var(--text-muted); font-size: 0.85rem; }
        .social-links a { color: var(--text-muted); margin-left: 1rem; font-size: 1.1rem; }
        .social-links a:hover { color: var(--gold); }

        /* Pagination */
        .pagination { display: flex; gap: 0.25rem; justify-content: center; margin-top: 2rem; }
        .pagination a, .pagination span { padding: 0.5rem 0.85rem; border-radius: 8px; font-size: 0.85rem; font-weight: 500; }
        .pagination a { background: var(--card); color: var(--text-muted); border: 1px solid var(--border); }
        .pagination a:hover { background: var(--gold); color: #1a1a2e; border-color: var(--gold); }
        .pagination .active span { background: var(--gold); color: #1a1a2e; }
        .pagination .disabled span { opacity: 0.4; }

        /* Edit link */
        .admin-edit-link { display: inline-flex; align-items: center; gap: 0.3rem; font-size: 0.8rem; color: var(--text-muted); background: var(--surface); padding: 0.3rem 0.8rem; border-radius: 6px; border: 1px solid var(--border); }
        .admin-edit-link:hover { color: var(--gold); border-color: var(--gold); }

        @media (max-width: 900px) {
            .content-layout { grid-template-columns: 1fr; }
            .sidebar { position: static; }
            .card-grid { grid-template-columns: 1fr; }
        }
    </style>
    @stack('styles')
</head>
<body>
    {{-- Header --}}
    <header class="site-header">
        <div class="container">
            <a href="/" class="logo">
                @if($settings && $settings->logo)
                    <img src="/storage/{{ $settings->logo }}" alt="{{ $settings->church_name ?? 'Church' }}" style="height:36px;">
                @else
                    {{ $settings->church_name ?? config('app.name', 'Grace Community Church') }}
                @endif
            </a>
            <nav>
                <a href="/">Home</a>
                <a href="/blog" class="{{ request()->is('blog*') ? 'active' : '' }}">Blog</a>
                <a href="/ministries" class="{{ request()->is('ministries*') ? 'active' : '' }}">Ministries</a>
                <a href="/bible-studies" class="{{ request()->is('bible-studies*') ? 'active' : '' }}">Bible Study</a>
                <a href="/library" class="{{ request()->is('library*') ? 'active' : '' }}">Library</a>
                <a href="/about" class="{{ request()->is('about') ? 'active' : '' }}">About</a>
            </nav>
        </div>
    </header>

    {{-- Main Content --}}
    @yield('content')

    {{-- Footer --}}
    <footer class="site-footer">
        <div class="container">
            <p>&copy; {{ date('Y') }} {{ $settings->church_name ?? config('app.name') }}. All rights reserved.</p>
            <div class="social-links">
                @if($settings->facebook_url ?? false)<a href="{{ $settings->facebook_url }}" target="_blank"><i class="fab fa-facebook-f"></i></a>@endif
                @if($settings->twitter_url ?? false)<a href="{{ $settings->twitter_url }}" target="_blank"><i class="fab fa-twitter"></i></a>@endif
                @if($settings->instagram_url ?? false)<a href="{{ $settings->instagram_url }}" target="_blank"><i class="fab fa-instagram"></i></a>@endif
                @if($settings->youtube_url ?? false)<a href="{{ $settings->youtube_url }}" target="_blank"><i class="fab fa-youtube"></i></a>@endif
            </div>
        </div>
    </footer>
    @stack('scripts')
</body>
</html>
