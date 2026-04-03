<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Admin Panel') - {{ config('app.name', 'Church Platform') }}</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <style>
        [x-cloak] { display: none !important; }
        .sidebar-link.active { background-color: rgba(99, 102, 241, 0.3); border-right: 3px solid #818cf8; }
        .sidebar-link:hover { background-color: rgba(99, 102, 241, 0.2); }
        ::-webkit-scrollbar { width: 6px; }
        ::-webkit-scrollbar-track { background: #1e1b4b; }
        ::-webkit-scrollbar-thumb { background: #4338ca; border-radius: 3px; }
    </style>
    @stack('styles')
</head>
<body class="bg-gray-100 font-sans antialiased" x-data="{ sidebarOpen: false }">
    <div class="flex h-screen overflow-hidden">

        {{-- Mobile Sidebar Overlay --}}
        <div x-show="sidebarOpen"
             x-transition:enter="transition-opacity ease-linear duration-300"
             x-transition:enter-start="opacity-0"
             x-transition:enter-end="opacity-100"
             x-transition:leave="transition-opacity ease-linear duration-300"
             x-transition:leave-start="opacity-100"
             x-transition:leave-end="opacity-0"
             class="fixed inset-0 z-40 bg-black bg-opacity-50 lg:hidden"
             @click="sidebarOpen = false"
             x-cloak>
        </div>

        {{-- Sidebar --}}
        <aside :class="sidebarOpen ? 'translate-x-0' : '-translate-x-full'"
               class="fixed inset-y-0 left-0 z-50 w-64 transform bg-[#1e1b4b] transition-transform duration-300 ease-in-out lg:relative lg:translate-x-0 lg:z-auto flex flex-col">

            {{-- Logo / Brand --}}
            <div class="flex items-center justify-between h-16 px-6 border-b border-indigo-800">
                <a href="{{ route('admin.dashboard') }}" class="flex items-center space-x-3">
                    <div class="w-9 h-9 bg-indigo-500 rounded-lg flex items-center justify-center">
                        <i class="fas fa-church text-white text-lg"></i>
                    </div>
                    <span class="text-white font-bold text-lg tracking-tight">Admin Panel</span>
                </a>
                <button @click="sidebarOpen = false" class="text-indigo-300 hover:text-white lg:hidden">
                    <i class="fas fa-times text-xl"></i>
                </button>
            </div>

            {{-- Navigation --}}
            <nav class="flex-1 overflow-y-auto py-4 px-3 space-y-1">
                <p class="px-3 text-xs font-semibold text-indigo-400 uppercase tracking-wider mb-2">Main</p>

                <a href="{{ route('admin.dashboard') }}"
                   class="sidebar-link flex items-center px-3 py-2.5 text-sm font-medium text-indigo-100 rounded-lg transition-colors {{ request()->routeIs('admin.dashboard') ? 'active' : '' }}">
                    <i class="fas fa-tachometer-alt w-5 mr-3 text-center text-indigo-300"></i>
                    Dashboard
                </a>

                <a href="{{ route('admin.manage', 'homepage') }}"
                   class="sidebar-link flex items-center px-3 py-2.5 text-sm font-medium text-indigo-100 rounded-lg transition-colors {{ request()->is('admin/manage/homepage') ? 'active' : '' }}">
                    <i class="fas fa-home w-5 mr-3 text-center text-indigo-300"></i>
                    Customize Homepage
                </a>

                <a href="{{ route('admin.manage', 'appearance') }}"
                   class="sidebar-link flex items-center px-3 py-2.5 text-sm font-medium text-indigo-100 rounded-lg transition-colors {{ request()->is('admin/manage/appearance') ? 'active' : '' }}">
                    <i class="fas fa-palette w-5 mr-3 text-center text-indigo-300"></i>
                    Appearance
                </a>

                <a href="{{ route('admin.manage', 'mobile-theme') }}"
                   class="sidebar-link flex items-center px-3 py-2.5 text-sm font-medium text-indigo-100 rounded-lg transition-colors {{ request()->is('admin/manage/mobile-theme') ? 'active' : '' }}">
                    <i class="fas fa-mobile-alt w-5 mr-3 text-center text-indigo-300"></i>
                    Mobile Theme
                </a>

                <p class="px-3 pt-4 text-xs font-semibold text-indigo-400 uppercase tracking-wider mb-2">Content</p>

                <a href="{{ route('admin.manage', 'announcements') }}"
                   class="sidebar-link flex items-center px-3 py-2.5 text-sm font-medium text-indigo-100 rounded-lg transition-colors {{ request()->is('admin/manage/announcements') ? 'active' : '' }}">
                    <i class="fas fa-bullhorn w-5 mr-3 text-center text-indigo-300"></i>
                    Announcements
                </a>

                <a href="{{ route('admin.manage', 'verses') }}"
                   class="sidebar-link flex items-center px-3 py-2.5 text-sm font-medium text-indigo-100 rounded-lg transition-colors {{ request()->is('admin/manage/verses') ? 'active' : '' }}">
                    <i class="fas fa-book-bible w-5 mr-3 text-center text-indigo-300"></i>
                    Verses
                </a>

                <a href="{{ route('admin.manage', 'blessings') }}"
                   class="sidebar-link flex items-center px-3 py-2.5 text-sm font-medium text-indigo-100 rounded-lg transition-colors {{ request()->is('admin/manage/blessings') ? 'active' : '' }}">
                    <i class="fas fa-hand-holding-heart w-5 mr-3 text-center text-indigo-300"></i>
                    Blessings
                </a>

                <a href="{{ route('admin.manage', 'prayer-requests') }}"
                   class="sidebar-link flex items-center px-3 py-2.5 text-sm font-medium text-indigo-100 rounded-lg transition-colors {{ request()->is('admin/manage/prayer-requests') ? 'active' : '' }}">
                    <i class="fas fa-praying-hands w-5 mr-3 text-center text-indigo-300"></i>
                    Prayer Requests
                </a>

                <a href="{{ route('admin.manage', 'events') }}"
                   class="sidebar-link flex items-center px-3 py-2.5 text-sm font-medium text-indigo-100 rounded-lg transition-colors {{ request()->is('admin/manage/events') ? 'active' : '' }}">
                    <i class="fas fa-calendar-alt w-5 mr-3 text-center text-indigo-300"></i>
                    Events
                </a>

                <a href="{{ route('admin.manage', 'sermons') }}"
                   class="sidebar-link flex items-center px-3 py-2.5 text-sm font-medium text-indigo-100 rounded-lg transition-colors {{ request()->is('admin/manage/sermons') ? 'active' : '' }}">
                    <i class="fas fa-microphone-alt w-5 mr-3 text-center text-indigo-300"></i>
                    Sermons
                </a>

                <a href="{{ route('admin.manage', 'books') }}"
                   class="sidebar-link flex items-center px-3 py-2.5 text-sm font-medium text-indigo-100 rounded-lg transition-colors {{ request()->is('admin/manage/books') ? 'active' : '' }}">
                    <i class="fas fa-book w-5 mr-3 text-center text-indigo-300"></i>
                    Books
                </a>

                <a href="{{ route('admin.manage', 'bible-studies') }}"
                   class="sidebar-link flex items-center px-3 py-2.5 text-sm font-medium text-indigo-100 rounded-lg transition-colors {{ request()->is('admin/manage/bible-studies') ? 'active' : '' }}">
                    <i class="fas fa-book-reader w-5 mr-3 text-center text-indigo-300"></i>
                    Bible Studies
                </a>

                <a href="{{ route('admin.manage', 'reviews') }}"
                   class="sidebar-link flex items-center px-3 py-2.5 text-sm font-medium text-indigo-100 rounded-lg transition-colors {{ request()->is('admin/manage/reviews') ? 'active' : '' }}">
                    <i class="fas fa-star w-5 mr-3 text-center text-indigo-300"></i>
                    Reviews
                </a>

                <a href="{{ route('admin.manage', 'testimonies') }}"
                   class="sidebar-link flex items-center px-3 py-2.5 text-sm font-medium text-indigo-100 rounded-lg transition-colors {{ request()->is('admin/manage/testimonies') ? 'active' : '' }}">
                    <i class="fas fa-cross w-5 mr-3 text-center text-indigo-300"></i>
                    Testimonies
                </a>

                <a href="{{ route('admin.manage', 'galleries') }}"
                   class="sidebar-link flex items-center px-3 py-2.5 text-sm font-medium text-indigo-100 rounded-lg transition-colors {{ request()->is('admin/manage/galleries') ? 'active' : '' }}">
                    <i class="fas fa-images w-5 mr-3 text-center text-indigo-300"></i>
                    Galleries
                </a>

                <a href="{{ route('admin.manage', 'ministries') }}"
                   class="sidebar-link flex items-center px-3 py-2.5 text-sm font-medium text-indigo-100 rounded-lg transition-colors {{ request()->is('admin/manage/ministries') ? 'active' : '' }}">
                    <i class="fas fa-hands-helping w-5 mr-3 text-center text-indigo-300"></i>
                    Ministries
                </a>

                <p class="px-3 pt-4 text-xs font-semibold text-indigo-400 uppercase tracking-wider mb-2">Churches</p>

                <a href="{{ route('admin.manage', 'churches') }}"
                   class="sidebar-link flex items-center px-3 py-2.5 text-sm font-medium text-indigo-100 rounded-lg transition-colors {{ request()->is('admin/manage/churches') ? 'active' : '' }}">
                    <i class="fas fa-church w-5 mr-3 text-center text-indigo-300"></i>
                    Church Directory
                </a>

                <a href="{{ route('admin.manage', 'church-builder') }}"
                   class="sidebar-link flex items-center px-3 py-2.5 text-sm font-medium text-indigo-100 rounded-lg transition-colors {{ request()->is('admin/manage/church-builder') ? 'active' : '' }}">
                    <i class="fas fa-tools w-5 mr-3 text-center text-indigo-300"></i>
                    Website Builder
                </a>

                <p class="px-3 pt-4 text-xs font-semibold text-indigo-400 uppercase tracking-wider mb-2">CMS</p>

                <a href="{{ route('admin.manage', 'posts') }}"
                   class="sidebar-link flex items-center px-3 py-2.5 text-sm font-medium text-indigo-100 rounded-lg transition-colors {{ request()->is('admin/manage/posts') ? 'active' : '' }}">
                    <i class="fas fa-newspaper w-5 mr-3 text-center text-indigo-300"></i>
                    Posts
                </a>

                <a href="{{ route('admin.manage', 'pages') }}"
                   class="sidebar-link flex items-center px-3 py-2.5 text-sm font-medium text-indigo-100 rounded-lg transition-colors {{ request()->is('admin/manage/pages') ? 'active' : '' }}">
                    <i class="fas fa-file-alt w-5 mr-3 text-center text-indigo-300"></i>
                    Pages
                </a>

                <a href="{{ route('admin.manage', 'categories') }}"
                   class="sidebar-link flex items-center px-3 py-2.5 text-sm font-medium text-indigo-100 rounded-lg transition-colors {{ request()->is('admin/manage/categories') ? 'active' : '' }}">
                    <i class="fas fa-tags w-5 mr-3 text-center text-indigo-300"></i>
                    Categories
                </a>

                <a href="{{ route('admin.manage', 'menus') }}"
                   class="sidebar-link flex items-center px-3 py-2.5 text-sm font-medium text-indigo-100 rounded-lg transition-colors {{ request()->is('admin/manage/menus') ? 'active' : '' }}">
                    <i class="fas fa-bars w-5 mr-3 text-center text-indigo-300"></i>
                    Menus
                </a>

                <p class="px-3 pt-4 text-xs font-semibold text-indigo-400 uppercase tracking-wider mb-2">Communication</p>

                <a href="{{ route('admin.manage', 'contacts') }}"
                   class="sidebar-link flex items-center px-3 py-2.5 text-sm font-medium text-indigo-100 rounded-lg transition-colors {{ request()->is('admin/manage/contacts') ? 'active' : '' }}">
                    <i class="fas fa-envelope w-5 mr-3 text-center text-indigo-300"></i>
                    Contacts
                </a>

                <a href="{{ route('admin.manage', 'newsletter') }}"
                   class="sidebar-link flex items-center px-3 py-2.5 text-sm font-medium text-indigo-100 rounded-lg transition-colors {{ request()->is('admin/manage/newsletter') ? 'active' : '' }}">
                    <i class="fas fa-mail-bulk w-5 mr-3 text-center text-indigo-300"></i>
                    Newsletter
                </a>

                <a href="{{ route('admin.manage', 'donations') }}"
                   class="sidebar-link flex items-center px-3 py-2.5 text-sm font-medium text-indigo-100 rounded-lg transition-colors {{ request()->is('admin/manage/donations') ? 'active' : '' }}">
                    <i class="fas fa-donate w-5 mr-3 text-center text-indigo-300"></i>
                    Donations
                </a>

                <p class="px-3 pt-4 text-xs font-semibold text-indigo-400 uppercase tracking-wider mb-2">System</p>

                <a href="{{ route('admin.manage', 'sitemap') }}"
                   class="sidebar-link flex items-center px-3 py-2.5 text-sm font-medium text-indigo-100 rounded-lg transition-colors {{ request()->is('admin/manage/sitemap') ? 'active' : '' }}">
                    <i class="fas fa-sitemap w-5 mr-3 text-center text-indigo-300"></i>
                    Sitemap Generator
                </a>

                <a href="{{ route('admin.manage', 'users') }}"
                   class="sidebar-link flex items-center px-3 py-2.5 text-sm font-medium text-indigo-100 rounded-lg transition-colors {{ request()->is('admin/manage/users') ? 'active' : '' }}">
                    <i class="fas fa-users w-5 mr-3 text-center text-indigo-300"></i>
                    Users
                </a>

                <a href="{{ route('admin.manage', 'roles') }}"
                   class="sidebar-link flex items-center px-3 py-2.5 text-sm font-medium text-indigo-100 rounded-lg transition-colors {{ request()->is('admin/manage/roles') ? 'active' : '' }}">
                    <i class="fas fa-user-shield w-5 mr-3 text-center text-indigo-300"></i>
                    Roles
                </a>

                <a href="{{ route('admin.manage', 'settings') }}"
                   class="sidebar-link flex items-center px-3 py-2.5 text-sm font-medium text-indigo-100 rounded-lg transition-colors {{ request()->is('admin/manage/settings') ? 'active' : '' }}">
                    <i class="fas fa-cog w-5 mr-3 text-center text-indigo-300"></i>
                    Settings
                </a>

                <a href="{{ route('admin.manage', 'translations') }}"
                   class="sidebar-link flex items-center px-3 py-2.5 text-sm font-medium text-indigo-100 rounded-lg transition-colors {{ request()->is('admin/manage/translations') ? 'active' : '' }}">
                    <i class="fas fa-language w-5 mr-3 text-center text-indigo-300"></i>
                    Translations
                </a>

                <a href="{{ route('admin.manage', 'system') }}"
                   class="sidebar-link flex items-center px-3 py-2.5 text-sm font-medium text-indigo-100 rounded-lg transition-colors {{ request()->is('admin/manage/system') ? 'active' : '' }}">
                    <i class="fas fa-server w-5 mr-3 text-center text-indigo-300"></i>
                    System & Deploy
                </a>
            </nav>

            {{-- Sidebar Footer --}}
            <div class="border-t border-indigo-800 p-4">
                <a href="{{ url('/') }}" target="_blank" class="flex items-center text-sm text-indigo-300 hover:text-white transition-colors">
                    <i class="fas fa-external-link-alt w-5 mr-3 text-center"></i>
                    View Website
                </a>
            </div>
        </aside>

        {{-- Main Content Area --}}
        <div class="flex-1 flex flex-col overflow-hidden">

            {{-- Top Navbar --}}
            <header class="bg-white shadow-sm border-b border-gray-200 h-16 flex items-center justify-between px-4 lg:px-8 z-30">
                <div class="flex items-center space-x-4">
                    <button @click="sidebarOpen = true" class="text-gray-500 hover:text-gray-700 lg:hidden">
                        <i class="fas fa-bars text-xl"></i>
                    </button>
                    <h1 class="text-lg font-semibold text-gray-800 hidden sm:block">
                        <i class="fas fa-church text-indigo-600 mr-2"></i>
                        {{ config('app.name', 'Church Platform') }}
                    </h1>
                </div>

                <div class="flex items-center space-x-4">
                    {{-- Notifications --}}
                    <button class="relative text-gray-500 hover:text-gray-700 transition-colors">
                        <i class="fas fa-bell text-lg"></i>
                        <span class="absolute -top-1 -right-1 w-4 h-4 bg-red-500 rounded-full text-white text-xs flex items-center justify-center">3</span>
                    </button>

                    {{-- User Dropdown --}}
                    <div x-data="{ open: false }" class="relative">
                        <button @click="open = !open" class="flex items-center space-x-3 text-gray-700 hover:text-gray-900 transition-colors">
                            <div class="w-8 h-8 bg-indigo-600 rounded-full flex items-center justify-center">
                                <span class="text-white text-sm font-bold">{{ substr(Auth::user()->name ?? 'A', 0, 1) }}</span>
                            </div>
                            <span class="hidden md:block text-sm font-medium">{{ Auth::user()->name ?? 'Admin' }}</span>
                            <i class="fas fa-chevron-down text-xs"></i>
                        </button>

                        <div x-show="open"
                             @click.away="open = false"
                             x-transition:enter="transition ease-out duration-100"
                             x-transition:enter-start="opacity-0 scale-95"
                             x-transition:enter-end="opacity-100 scale-100"
                             x-transition:leave="transition ease-in duration-75"
                             x-transition:leave-start="opacity-100 scale-100"
                             x-transition:leave-end="opacity-0 scale-95"
                             class="absolute right-0 mt-2 w-48 bg-white rounded-lg shadow-lg border border-gray-200 py-1 z-50"
                             x-cloak>
                            <div class="px-4 py-2 border-b border-gray-100">
                                <p class="text-sm font-medium text-gray-900">{{ Auth::user()->name ?? 'Admin' }}</p>
                                <p class="text-xs text-gray-500">{{ Auth::user()->email ?? 'admin@church.com' }}</p>
                            </div>
                            <a href="{{ route('admin.manage', 'profile') }}" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-50">
                                <i class="fas fa-user-circle w-5 mr-2 text-gray-400"></i>Profile
                            </a>
                            <a href="{{ route('admin.manage', 'settings') }}" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-50">
                                <i class="fas fa-cog w-5 mr-2 text-gray-400"></i>Settings
                            </a>
                            <div class="border-t border-gray-100"></div>
                            <form method="POST" action="{{ route('logout') }}">
                                @csrf
                                <button type="submit" class="w-full text-left px-4 py-2 text-sm text-red-600 hover:bg-red-50">
                                    <i class="fas fa-sign-out-alt w-5 mr-2"></i>Logout
                                </button>
                            </form>
                        </div>
                    </div>
                </div>
            </header>

            {{-- Page Content --}}
            <main class="flex-1 overflow-y-auto bg-gray-50">
                {{-- Flash Messages --}}
                @if(session('success'))
                    <div x-data="{ show: true }" x-show="show" x-init="setTimeout(() => show = false, 5000)"
                         class="mx-4 lg:mx-8 mt-4 p-4 bg-green-50 border border-green-200 rounded-lg flex items-center justify-between">
                        <div class="flex items-center">
                            <i class="fas fa-check-circle text-green-500 mr-3"></i>
                            <span class="text-green-800 text-sm">{{ session('success') }}</span>
                        </div>
                        <button @click="show = false" class="text-green-400 hover:text-green-600">
                            <i class="fas fa-times"></i>
                        </button>
                    </div>
                @endif

                @if(session('error'))
                    <div x-data="{ show: true }" x-show="show"
                         class="mx-4 lg:mx-8 mt-4 p-4 bg-red-50 border border-red-200 rounded-lg flex items-center justify-between">
                        <div class="flex items-center">
                            <i class="fas fa-exclamation-circle text-red-500 mr-3"></i>
                            <span class="text-red-800 text-sm">{{ session('error') }}</span>
                        </div>
                        <button @click="show = false" class="text-red-400 hover:text-red-600">
                            <i class="fas fa-times"></i>
                        </button>
                    </div>
                @endif

                <div class="p-4 lg:p-8">
                    @yield('content')
                </div>
            </main>
        </div>
    </div>

    {{-- Global JS Helpers --}}
    <script>
        window.ChurchApp = {
            csrfToken: document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
            apiBaseUrl: '/api',
        };

        async function apiCall(endpoint, options = {}) {
            const defaults = {
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': window.ChurchApp.csrfToken,
                },
            };
            const config = { ...defaults, ...options, headers: { ...defaults.headers, ...(options.headers || {}) } };
            const response = await fetch(`${window.ChurchApp.apiBaseUrl}${endpoint}`, config);
            if (!response.ok) {
                const error = await response.json().catch(() => ({ message: 'An error occurred' }));
                throw new Error(error.message || 'Request failed');
            }
            return response.json();
        }
    </script>
    @stack('scripts')
</body>
</html>
