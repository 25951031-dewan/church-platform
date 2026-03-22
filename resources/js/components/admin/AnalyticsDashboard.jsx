import { useQuery } from '@tanstack/react-query';
import axios from 'axios';
import { useState } from 'react';
import {
    Bar,
    BarChart,
    CartesianGrid,
    Cell,
    Legend,
    Line,
    LineChart,
    Pie,
    PieChart,
    ResponsiveContainer,
    Tooltip,
    XAxis,
    YAxis,
} from 'recharts';

// ─── Colour palette ───────────────────────────────────────────────────────────
const COLORS = ['#2563eb', '#7c3aed', '#059669', '#d97706', '#dc2626', '#0891b2'];

// ─── Stat card ────────────────────────────────────────────────────────────────
function StatCard({ label, value, sub }) {
    return (
        <div className="rounded-xl border border-gray-200 bg-white p-5 shadow-sm">
            <p className="text-sm text-gray-500">{label}</p>
            <p className="mt-1 text-3xl font-bold text-gray-900">{value?.toLocaleString() ?? '—'}</p>
            {sub && <p className="mt-0.5 text-xs text-gray-400">{sub}</p>}
        </div>
    );
}

// ─── Empty state ──────────────────────────────────────────────────────────────
function Empty({ message }) {
    return (
        <div className="flex h-40 items-center justify-center text-sm text-gray-400">
            {message}
        </div>
    );
}

// ─── Main component ───────────────────────────────────────────────────────────
export default function AnalyticsDashboard() {
    const [days,      setDays]      = useState(30);
    const [churchId,  setChurchId]  = useState('');

    const { data, isLoading, isError } = useQuery({
        queryKey: ['admin-analytics', days, churchId],
        queryFn:  () => axios.get('/api/v1/admin/analytics', {
            params: { days, church_id: churchId || undefined },
        }).then(r => r.data),
    });

    const stats      = data?.stats       ?? {};
    const dailyViews = data?.daily_views ?? [];
    const topPages   = data?.top_pages   ?? [];
    const userRoles  = data?.user_roles  ?? [];

    return (
        <div className="mx-auto max-w-6xl px-4 py-8 sm:px-6">

            {/* Header */}
            <div className="mb-6 flex flex-wrap items-center justify-between gap-4">
                <h1 className="text-xl font-bold text-gray-900">Analytics</h1>

                <div className="flex items-center gap-3">
                    <input
                        type="number"
                        min="1"
                        placeholder="Church ID"
                        className="w-28 rounded border border-gray-300 px-3 py-1.5 text-sm focus:border-blue-500 focus:outline-none"
                        value={churchId}
                        onChange={e => setChurchId(e.target.value)}
                    />
                    <select
                        className="rounded border border-gray-300 px-3 py-1.5 text-sm focus:border-blue-500 focus:outline-none"
                        value={days}
                        onChange={e => setDays(Number(e.target.value))}
                    >
                        <option value={7}>Last 7 days</option>
                        <option value={30}>Last 30 days</option>
                        <option value={90}>Last 90 days</option>
                    </select>
                </div>
            </div>

            {isError && (
                <div className="mb-6 rounded-lg bg-red-50 px-4 py-3 text-sm text-red-700 ring-1 ring-red-200">
                    Failed to load analytics. Please try again.
                </div>
            )}

            {/* ── Stats cards ──────────────────────────────────────────── */}
            <div className="mb-8 grid grid-cols-2 gap-4 sm:grid-cols-4">
                <StatCard label="Total Users"      value={stats.totalUsers}    sub="all time" />
                <StatCard label="Active Users"     value={stats.activeUsers7d} sub="last 7 days" />
                <StatCard label="Posts This Month" value={stats.postsMonth}    />
                <StatCard label="Prayers This Month" value={stats.prayers}     />
            </div>

            {/* ── Line chart: daily page views ─────────────────────────── */}
            <section className="mb-8 rounded-xl border border-gray-200 bg-white p-5 shadow-sm">
                <h2 className="mb-4 text-sm font-semibold text-gray-700">
                    Daily Page Views — Last {days} days
                </h2>
                {isLoading ? (
                    <div className="h-56 animate-pulse rounded bg-gray-100" />
                ) : dailyViews.length === 0 ? (
                    <Empty message="No page view data yet. Views appear after TrackPageView middleware fires." />
                ) : (
                    <ResponsiveContainer width="100%" height={224}>
                        <LineChart data={dailyViews} margin={{ top: 4, right: 16, left: 0, bottom: 0 }}>
                            <CartesianGrid strokeDasharray="3 3" stroke="#f0f0f0" />
                            <XAxis dataKey="date" tick={{ fontSize: 11 }} tickLine={false} axisLine={false} />
                            <YAxis tick={{ fontSize: 11 }} tickLine={false} axisLine={false} />
                            <Tooltip />
                            <Line
                                type="monotone"
                                dataKey="views"
                                stroke="#2563eb"
                                strokeWidth={2}
                                dot={false}
                                activeDot={{ r: 4 }}
                            />
                        </LineChart>
                    </ResponsiveContainer>
                )}
            </section>

            {/* ── Bar chart: top pages ─────────────────────────────────── */}
            <section className="mb-8 rounded-xl border border-gray-200 bg-white p-5 shadow-sm">
                <h2 className="mb-4 text-sm font-semibold text-gray-700">Top Pages</h2>
                {isLoading ? (
                    <div className="h-56 animate-pulse rounded bg-gray-100" />
                ) : topPages.length === 0 ? (
                    <Empty message="No page view data yet." />
                ) : (
                    <ResponsiveContainer width="100%" height={224}>
                        <BarChart
                            data={topPages}
                            layout="vertical"
                            margin={{ top: 0, right: 16, left: 8, bottom: 0 }}
                        >
                            <CartesianGrid strokeDasharray="3 3" stroke="#f0f0f0" horizontal={false} />
                            <XAxis type="number" tick={{ fontSize: 11 }} tickLine={false} axisLine={false} />
                            <YAxis
                                type="category"
                                dataKey="url"
                                tick={{ fontSize: 11 }}
                                tickLine={false}
                                axisLine={false}
                                width={160}
                            />
                            <Tooltip />
                            <Bar dataKey="views" fill="#2563eb" radius={[0, 4, 4, 0]} />
                        </BarChart>
                    </ResponsiveContainer>
                )}
            </section>

            {/* ── Pie chart: user roles ────────────────────────────────── */}
            <section className="rounded-xl border border-gray-200 bg-white p-5 shadow-sm">
                <h2 className="mb-4 text-sm font-semibold text-gray-700">Users by Role</h2>
                {isLoading ? (
                    <div className="h-56 animate-pulse rounded bg-gray-100" />
                ) : userRoles.length === 0 ? (
                    <Empty message="No role data found. Roles are assigned via Spatie Permissions." />
                ) : (
                    <div className="flex flex-wrap items-center gap-6">
                        <ResponsiveContainer width={220} height={220}>
                            <PieChart>
                                <Pie
                                    data={userRoles}
                                    dataKey="count"
                                    nameKey="role"
                                    cx="50%"
                                    cy="50%"
                                    outerRadius={90}
                                    innerRadius={50}
                                >
                                    {userRoles.map((_, i) => (
                                        <Cell key={i} fill={COLORS[i % COLORS.length]} />
                                    ))}
                                </Pie>
                                <Tooltip />
                            </PieChart>
                        </ResponsiveContainer>

                        {/* Legend */}
                        <ul className="space-y-1.5">
                            {userRoles.map((r, i) => (
                                <li key={r.role} className="flex items-center gap-2 text-sm">
                                    <span
                                        className="inline-block h-3 w-3 rounded-full"
                                        style={{ background: COLORS[i % COLORS.length] }}
                                    />
                                    <span className="capitalize text-gray-700">{r.role}</span>
                                    <span className="ml-1 text-gray-400">{r.count.toLocaleString()}</span>
                                </li>
                            ))}
                        </ul>
                    </div>
                )}
            </section>

        </div>
    );
}
