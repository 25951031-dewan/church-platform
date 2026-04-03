@extends('layouts.admin')

@section('title', 'Dashboard')

@section('content')
<div class="space-y-8">

    {{-- Page Header --}}
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between">
        <div>
            <h1 class="text-2xl font-bold text-gray-900">Dashboard</h1>
            <p class="mt-1 text-sm text-gray-500">Welcome back! Here's an overview of your church platform.</p>
        </div>
        <div class="mt-4 sm:mt-0 flex space-x-3">
            <a href="{{ route('admin.manage', 'verses') }}" class="inline-flex items-center px-4 py-2 bg-indigo-600 text-white text-sm font-medium rounded-lg hover:bg-indigo-700 transition-colors shadow-sm">
                <i class="fas fa-plus mr-2"></i>Add Verse
            </a>
            <a href="{{ route('admin.manage', 'events') }}" class="inline-flex items-center px-4 py-2 bg-purple-600 text-white text-sm font-medium rounded-lg hover:bg-purple-700 transition-colors shadow-sm">
                <i class="fas fa-calendar-plus mr-2"></i>Add Event
            </a>
            <a href="{{ route('admin.manage', 'posts') }}" class="inline-flex items-center px-4 py-2 bg-emerald-600 text-white text-sm font-medium rounded-lg hover:bg-emerald-700 transition-colors shadow-sm">
                <i class="fas fa-pen mr-2"></i>New Post
            </a>
        </div>
    </div>

    {{-- Stats Grid --}}
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-5">

        {{-- Users --}}
        <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6 hover:shadow-md transition-shadow">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm font-medium text-gray-500">Total Users</p>
                    <p class="text-3xl font-bold text-gray-900 mt-1">{{ number_format($stats['users'] ?? 0) }}</p>
                </div>
                <div class="w-12 h-12 bg-blue-100 rounded-xl flex items-center justify-center">
                    <i class="fas fa-users text-blue-600 text-xl"></i>
                </div>
            </div>
            <div class="mt-3 flex items-center text-xs text-green-600">
                <i class="fas fa-arrow-up mr-1"></i>
                <span>Active community</span>
            </div>
        </div>

        {{-- Posts --}}
        <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6 hover:shadow-md transition-shadow">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm font-medium text-gray-500">Blog Posts</p>
                    <p class="text-3xl font-bold text-gray-900 mt-1">{{ number_format($stats['posts'] ?? 0) }}</p>
                </div>
                <div class="w-12 h-12 bg-purple-100 rounded-xl flex items-center justify-center">
                    <i class="fas fa-newspaper text-purple-600 text-xl"></i>
                </div>
            </div>
            <div class="mt-3 flex items-center text-xs text-gray-500">
                <i class="fas fa-pen mr-1"></i>
                <span>Published articles</span>
            </div>
        </div>

        {{-- Events --}}
        <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6 hover:shadow-md transition-shadow">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm font-medium text-gray-500">Events</p>
                    <p class="text-3xl font-bold text-gray-900 mt-1">{{ number_format($stats['events'] ?? 0) }}</p>
                </div>
                <div class="w-12 h-12 bg-amber-100 rounded-xl flex items-center justify-center">
                    <i class="fas fa-calendar-alt text-amber-600 text-xl"></i>
                </div>
            </div>
            <div class="mt-3 flex items-center text-xs text-amber-600">
                <i class="fas fa-clock mr-1"></i>
                <span>Upcoming & past</span>
            </div>
        </div>

        {{-- Prayer Requests --}}
        <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6 hover:shadow-md transition-shadow">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm font-medium text-gray-500">Prayer Requests</p>
                    <p class="text-3xl font-bold text-gray-900 mt-1">{{ number_format($stats['prayers'] ?? 0) }}</p>
                </div>
                <div class="w-12 h-12 bg-rose-100 rounded-xl flex items-center justify-center">
                    <i class="fas fa-praying-hands text-rose-600 text-xl"></i>
                </div>
            </div>
            <div class="mt-3 flex items-center text-xs text-rose-600">
                <i class="fas fa-heart mr-1"></i>
                <span>Community prayers</span>
            </div>
        </div>

        {{-- Books --}}
        <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6 hover:shadow-md transition-shadow">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm font-medium text-gray-500">Books</p>
                    <p class="text-3xl font-bold text-gray-900 mt-1">{{ number_format($stats['books'] ?? 0) }}</p>
                </div>
                <div class="w-12 h-12 bg-teal-100 rounded-xl flex items-center justify-center">
                    <i class="fas fa-book text-teal-600 text-xl"></i>
                </div>
            </div>
            <div class="mt-3 flex items-center text-xs text-teal-600">
                <i class="fas fa-bookmark mr-1"></i>
                <span>In library</span>
            </div>
        </div>

        {{-- Sermons --}}
        <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6 hover:shadow-md transition-shadow">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm font-medium text-gray-500">Sermons</p>
                    <p class="text-3xl font-bold text-gray-900 mt-1">{{ number_format($stats['sermons'] ?? 0) }}</p>
                </div>
                <div class="w-12 h-12 bg-indigo-100 rounded-xl flex items-center justify-center">
                    <i class="fas fa-microphone-alt text-indigo-600 text-xl"></i>
                </div>
            </div>
            <div class="mt-3 flex items-center text-xs text-indigo-600">
                <i class="fas fa-play-circle mr-1"></i>
                <span>Recorded sermons</span>
            </div>
        </div>

        {{-- Bible Studies --}}
        <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6 hover:shadow-md transition-shadow">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm font-medium text-gray-500">Bible Studies</p>
                    <p class="text-3xl font-bold text-gray-900 mt-1">{{ number_format($stats['studies'] ?? 0) }}</p>
                </div>
                <div class="w-12 h-12 bg-cyan-100 rounded-xl flex items-center justify-center">
                    <i class="fas fa-book-reader text-cyan-600 text-xl"></i>
                </div>
            </div>
            <div class="mt-3 flex items-center text-xs text-cyan-600">
                <i class="fas fa-graduation-cap mr-1"></i>
                <span>Study materials</span>
            </div>
        </div>

        {{-- Reviews --}}
        <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6 hover:shadow-md transition-shadow">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm font-medium text-gray-500">Reviews</p>
                    <p class="text-3xl font-bold text-gray-900 mt-1">{{ number_format($stats['reviews'] ?? 0) }}</p>
                </div>
                <div class="w-12 h-12 bg-yellow-100 rounded-xl flex items-center justify-center">
                    <i class="fas fa-star text-yellow-600 text-xl"></i>
                </div>
            </div>
            <div class="mt-3 flex items-center text-xs text-yellow-600">
                <i class="fas fa-star-half-alt mr-1"></i>
                <span>Testimonials</span>
            </div>
        </div>

        {{-- Contacts --}}
        <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6 hover:shadow-md transition-shadow">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm font-medium text-gray-500">Contact Messages</p>
                    <p class="text-3xl font-bold text-gray-900 mt-1">{{ number_format($stats['contacts'] ?? 0) }}</p>
                </div>
                <div class="w-12 h-12 bg-green-100 rounded-xl flex items-center justify-center">
                    <i class="fas fa-envelope text-green-600 text-xl"></i>
                </div>
            </div>
            <div class="mt-3 flex items-center text-xs text-green-600">
                <i class="fas fa-inbox mr-1"></i>
                <span>Messages received</span>
            </div>
        </div>

        {{-- Newsletter Subscribers --}}
        <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6 hover:shadow-md transition-shadow">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm font-medium text-gray-500">Subscribers</p>
                    <p class="text-3xl font-bold text-gray-900 mt-1">{{ number_format($stats['subscribers'] ?? 0) }}</p>
                </div>
                <div class="w-12 h-12 bg-pink-100 rounded-xl flex items-center justify-center">
                    <i class="fas fa-mail-bulk text-pink-600 text-xl"></i>
                </div>
            </div>
            <div class="mt-3 flex items-center text-xs text-pink-600">
                <i class="fas fa-chart-line mr-1"></i>
                <span>Newsletter list</span>
            </div>
        </div>

        {{-- Donations --}}
        <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6 hover:shadow-md transition-shadow sm:col-span-2 lg:col-span-1">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm font-medium text-gray-500">Total Donations</p>
                    <p class="text-3xl font-bold text-gray-900 mt-1">${{ number_format($stats['donations'] ?? 0, 2) }}</p>
                </div>
                <div class="w-12 h-12 bg-emerald-100 rounded-xl flex items-center justify-center">
                    <i class="fas fa-donate text-emerald-600 text-xl"></i>
                </div>
            </div>
            <div class="mt-3 flex items-center text-xs text-emerald-600">
                <i class="fas fa-hand-holding-usd mr-1"></i>
                <span>Total received</span>
            </div>
        </div>
    </div>

    {{-- Tables Section --}}
    <div class="grid grid-cols-1 xl:grid-cols-2 gap-6">

        {{-- Recent Prayer Requests --}}
        <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
            <div class="px-6 py-4 border-b border-gray-100 flex items-center justify-between">
                <h2 class="text-lg font-semibold text-gray-900">
                    <i class="fas fa-praying-hands text-rose-500 mr-2"></i>Recent Prayer Requests
                </h2>
                <a href="{{ route('admin.manage', 'prayer-requests') }}" class="text-sm text-indigo-600 hover:text-indigo-800 font-medium">View All</a>
            </div>
            <div class="overflow-x-auto">
                <table class="w-full">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Name</th>
                            <th class="px-6 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Subject</th>
                            <th class="px-6 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Status</th>
                            <th class="px-6 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Date</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        @forelse($recentPrayerRequests ?? [] as $prayer)
                            <tr class="hover:bg-gray-50 transition-colors">
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="flex items-center">
                                        <div class="w-8 h-8 bg-rose-100 rounded-full flex items-center justify-center mr-3">
                                            <span class="text-rose-600 text-xs font-bold">{{ substr($prayer->name ?? 'A', 0, 1) }}</span>
                                        </div>
                                        <span class="text-sm font-medium text-gray-900">{{ $prayer->name ?? 'Anonymous' }}</span>
                                    </div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-600">{{ Str::limit($prayer->subject ?? '', 30) }}</td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    @php
                                        $statusColors = [
                                            'pending' => 'bg-yellow-100 text-yellow-800',
                                            'approved' => 'bg-green-100 text-green-800',
                                            'answered' => 'bg-blue-100 text-blue-800',
                                            'rejected' => 'bg-red-100 text-red-800',
                                        ];
                                        $color = $statusColors[$prayer->status ?? 'pending'] ?? 'bg-gray-100 text-gray-800';
                                    @endphp
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium {{ $color }}">
                                        {{ ucfirst($prayer->status ?? 'pending') }}
                                    </span>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                    {{ $prayer->created_at ? $prayer->created_at->format('M d, Y') : '-' }}
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="4" class="px-6 py-8 text-center text-gray-400">
                                    <i class="fas fa-inbox text-3xl mb-2"></i>
                                    <p class="text-sm">No prayer requests yet.</p>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        {{-- Upcoming Events --}}
        <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
            <div class="px-6 py-4 border-b border-gray-100 flex items-center justify-between">
                <h2 class="text-lg font-semibold text-gray-900">
                    <i class="fas fa-calendar-alt text-amber-500 mr-2"></i>Upcoming Events
                </h2>
                <a href="{{ route('admin.manage', 'events') }}" class="text-sm text-indigo-600 hover:text-indigo-800 font-medium">View All</a>
            </div>
            <div class="overflow-x-auto">
                <table class="w-full">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Event</th>
                            <th class="px-6 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Date</th>
                            <th class="px-6 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Location</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        @forelse($upcomingEvents ?? [] as $event)
                            <tr class="hover:bg-gray-50 transition-colors">
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="flex items-center">
                                        <div class="w-10 h-10 bg-amber-100 rounded-lg flex flex-col items-center justify-center mr-3">
                                            <span class="text-amber-800 text-xs font-bold leading-none">{{ $event->event_date ? $event->event_date->format('d') : '--' }}</span>
                                            <span class="text-amber-600 text-[10px] uppercase leading-none">{{ $event->event_date ? $event->event_date->format('M') : '--' }}</span>
                                        </div>
                                        <span class="text-sm font-medium text-gray-900">{{ Str::limit($event->title ?? '', 35) }}</span>
                                    </div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-600">
                                    {{ $event->event_date ? $event->event_date->format('M d, Y h:i A') : '-' }}
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                    <i class="fas fa-map-marker-alt text-gray-400 mr-1"></i>
                                    {{ Str::limit($event->location ?? 'TBA', 25) }}
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="3" class="px-6 py-8 text-center text-gray-400">
                                    <i class="fas fa-calendar-times text-3xl mb-2"></i>
                                    <p class="text-sm">No upcoming events.</p>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        {{-- Recent Contact Messages --}}
        <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden xl:col-span-2">
            <div class="px-6 py-4 border-b border-gray-100 flex items-center justify-between">
                <h2 class="text-lg font-semibold text-gray-900">
                    <i class="fas fa-envelope text-green-500 mr-2"></i>Recent Contact Messages
                </h2>
                <a href="{{ route('admin.manage', 'contacts') }}" class="text-sm text-indigo-600 hover:text-indigo-800 font-medium">View All</a>
            </div>
            <div class="overflow-x-auto">
                <table class="w-full">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Name</th>
                            <th class="px-6 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Email</th>
                            <th class="px-6 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Subject</th>
                            <th class="px-6 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Message</th>
                            <th class="px-6 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Date</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        @forelse($recentContacts ?? [] as $contact)
                            <tr class="hover:bg-gray-50 transition-colors">
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="flex items-center">
                                        <div class="w-8 h-8 bg-green-100 rounded-full flex items-center justify-center mr-3">
                                            <span class="text-green-600 text-xs font-bold">{{ substr($contact->name ?? 'A', 0, 1) }}</span>
                                        </div>
                                        <span class="text-sm font-medium text-gray-900">{{ $contact->name ?? '-' }}</span>
                                    </div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-600">{{ $contact->email ?? '-' }}</td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-600">{{ Str::limit($contact->subject ?? '', 25) }}</td>
                                <td class="px-6 py-4 text-sm text-gray-500">{{ Str::limit($contact->message ?? '', 40) }}</td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                    {{ $contact->created_at ? $contact->created_at->format('M d, Y') : '-' }}
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="px-6 py-8 text-center text-gray-400">
                                    <i class="fas fa-envelope-open text-3xl mb-2"></i>
                                    <p class="text-sm">No contact messages yet.</p>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

    </div>
</div>
@endsection
