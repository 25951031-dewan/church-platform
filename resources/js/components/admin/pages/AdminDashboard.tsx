import React, { useEffect, useState } from 'react';
import axios from 'axios';

interface Stats {
    total_users: number;
    total_posts: number;
    total_events: number;
    total_communities: number;
    daily_views?: { date: string; views: number }[];
}

interface StatCardProps {
    label: string;
    value: number | string;
    icon: React.ReactNode;
    color: string;
}

function StatCard({ label, value, icon, color }: StatCardProps) {
    return (
        <div className="bg-white rounded-xl shadow-sm border border-gray-100 p-6 flex items-center gap-4">
            <div className={`w-12 h-12 rounded-xl flex items-center justify-center ${color}`}>
                {icon}
            </div>
            <div>
                <div className="text-2xl font-bold text-gray-800">
                    {typeof value === 'number' ? value.toLocaleString() : value}
                </div>
                <div className="text-sm text-gray-500 mt-0.5">{label}</div>
            </div>
        </div>
    );
}

export default function AdminDashboard() {
    const [stats, setStats] = useState<Stats | null>(null);
    const [loading, setLoading] = useState(true);

    useEffect(() => {
        axios.get('/api/v1/admin/analytics?days=30')
            .then(r => setStats(r.data))
            .catch(() => {})
            .finally(() => setLoading(false));
    }, []);

    if (loading) {
        return (
            <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4 animate-pulse">
                {[...Array(4)].map((_, i) => (
                    <div key={i} className="bg-gray-200 rounded-xl h-24" />
                ))}
            </div>
        );
    }

    return (
        <div className="space-y-6">
            <div>
                <h1 className="text-2xl font-bold text-gray-800">Dashboard</h1>
                <p className="text-sm text-gray-500 mt-1">Platform overview — last 30 days</p>
            </div>

            {/* Stat Cards */}
            <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
                <StatCard
                    label="Total Users"
                    value={stats?.total_users ?? 0}
                    color="bg-indigo-100 text-indigo-600"
                    icon={
                        <svg className="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2}
                                d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z" />
                        </svg>
                    }
                />
                <StatCard
                    label="Total Posts"
                    value={stats?.total_posts ?? 0}
                    color="bg-emerald-100 text-emerald-600"
                    icon={
                        <svg className="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2}
                                d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                        </svg>
                    }
                />
                <StatCard
                    label="Total Events"
                    value={stats?.total_events ?? 0}
                    color="bg-amber-100 text-amber-600"
                    icon={
                        <svg className="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2}
                                d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                        </svg>
                    }
                />
                <StatCard
                    label="Communities"
                    value={stats?.total_communities ?? 0}
                    color="bg-rose-100 text-rose-600"
                    icon={
                        <svg className="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2}
                                d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10" />
                        </svg>
                    }
                />
            </div>

            {/* Quick Links */}
            <div className="bg-white rounded-xl shadow-sm border border-gray-100 p-6">
                <h2 className="text-base font-semibold text-gray-700 mb-4">Quick Actions</h2>
                <div className="grid grid-cols-2 md:grid-cols-4 gap-3">
                    {[
                        { label: 'Manage Users', href: '/admin/users', color: 'bg-indigo-50 text-indigo-700 hover:bg-indigo-100' },
                        { label: 'Moderate Posts', href: '/admin/posts', color: 'bg-emerald-50 text-emerald-700 hover:bg-emerald-100' },
                        { label: 'View Events', href: '/admin/events', color: 'bg-amber-50 text-amber-700 hover:bg-amber-100' },
                        { label: 'Platform Settings', href: '/admin/settings', color: 'bg-gray-50 text-gray-700 hover:bg-gray-100' },
                    ].map(link => (
                        <a
                            key={link.href}
                            href={link.href}
                            className={`rounded-lg px-4 py-3 text-sm font-medium text-center transition-colors ${link.color}`}
                        >
                            {link.label}
                        </a>
                    ))}
                </div>
            </div>
        </div>
    );
}
