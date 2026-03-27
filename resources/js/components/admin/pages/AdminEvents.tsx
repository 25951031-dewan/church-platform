import React, { useCallback, useEffect, useState } from 'react';
import axios from 'axios';

interface Creator { id: number; name: string }
interface Event {
    id: number;
    title: string;
    category: string | null;
    start_at: string;
    end_at: string;
    location: string | null;
    is_online: boolean;
    status: string;
    attendees_count: number;
    creator: Creator | null;
    created_at: string;
}
interface Paginated { data: Event[]; current_page: number; last_page: number; total: number }

const CATEGORIES = ['worship', 'prayer', 'outreach', 'youth', 'women', 'men', 'family', 'other'];

const CAT_COLORS: Record<string, string> = {
    worship:  'bg-purple-100 text-purple-700',
    prayer:   'bg-blue-100 text-blue-700',
    outreach: 'bg-green-100 text-green-700',
    youth:    'bg-yellow-100 text-yellow-700',
    women:    'bg-pink-100 text-pink-700',
    men:      'bg-indigo-100 text-indigo-700',
    family:   'bg-orange-100 text-orange-700',
    other:    'bg-gray-100 text-gray-600',
};

export default function AdminEvents() {
    const [data, setData]       = useState<Paginated | null>(null);
    const [loading, setLoading] = useState(true);
    const [search, setSearch]   = useState('');
    const [category, setCategory] = useState('');
    const [page, setPage]       = useState(1);
    const [deleting, setDeleting] = useState<number | null>(null);

    const load = useCallback(() => {
        setLoading(true);
        axios.get('/api/v1/admin/events', { params: { search, category, page } })
            .then(r => setData(r.data))
            .catch(() => {})
            .finally(() => setLoading(false));
    }, [search, category, page]);

    useEffect(() => { load(); }, [load]);

    useEffect(() => {
        const t = setTimeout(() => { setPage(1); load(); }, 400);
        return () => clearTimeout(t);
    }, [search]); // eslint-disable-line

    const handleDelete = async (id: number) => {
        if (!confirm('Delete this event?')) return;
        setDeleting(id);
        await axios.delete(`/api/v1/admin/events/${id}`).catch(() => {});
        setDeleting(null);
        load();
    };

    return (
        <div className="space-y-4">
            <div>
                <h1 className="text-2xl font-bold text-gray-800">Events</h1>
                {data && <p className="text-sm text-gray-500">{data.total} total events</p>}
            </div>

            {/* Filters */}
            <div className="bg-white rounded-xl border border-gray-100 shadow-sm p-4 flex flex-wrap gap-3">
                <input
                    type="text"
                    placeholder="Search events…"
                    value={search}
                    onChange={e => setSearch(e.target.value)}
                    className="flex-1 min-w-48 border border-gray-200 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-300"
                />
                <select
                    value={category}
                    onChange={e => { setCategory(e.target.value); setPage(1); }}
                    className="border border-gray-200 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-300"
                >
                    <option value="">All Categories</option>
                    {CATEGORIES.map(c => <option key={c} value={c}>{c}</option>)}
                </select>
            </div>

            {/* Table */}
            <div className="bg-white rounded-xl border border-gray-100 shadow-sm overflow-hidden">
                <table className="w-full text-sm">
                    <thead>
                        <tr className="border-b border-gray-100 bg-gray-50">
                            <th className="text-left px-4 py-3 font-semibold text-gray-600">Title</th>
                            <th className="text-left px-4 py-3 font-semibold text-gray-600">Category</th>
                            <th className="text-left px-4 py-3 font-semibold text-gray-600">Date & Time</th>
                            <th className="text-left px-4 py-3 font-semibold text-gray-600">Location</th>
                            <th className="text-left px-4 py-3 font-semibold text-gray-600">RSVPs</th>
                            <th className="text-left px-4 py-3 font-semibold text-gray-600">Created by</th>
                            <th className="text-right px-4 py-3 font-semibold text-gray-600">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        {loading ? (
                            [...Array(5)].map((_, i) => (
                                <tr key={i} className="border-b border-gray-50 animate-pulse">
                                    {[...Array(7)].map((__, j) => (
                                        <td key={j} className="px-4 py-3"><div className="h-4 bg-gray-200 rounded" /></td>
                                    ))}
                                </tr>
                            ))
                        ) : data?.data.length === 0 ? (
                            <tr>
                                <td colSpan={7} className="px-4 py-10 text-center text-gray-400">No events found</td>
                            </tr>
                        ) : data?.data.map(event => (
                            <tr key={event.id} className="border-b border-gray-50 hover:bg-gray-50 transition-colors">
                                <td className="px-4 py-3 font-medium text-gray-800 max-w-xs">
                                    <span className="line-clamp-1">{event.title}</span>
                                </td>
                                <td className="px-4 py-3">
                                    {event.category ? (
                                        <span className={`text-xs font-semibold px-2 py-0.5 rounded-full ${CAT_COLORS[event.category] ?? 'bg-gray-100 text-gray-600'}`}>
                                            {event.category}
                                        </span>
                                    ) : <span className="text-gray-400 text-xs">—</span>}
                                </td>
                                <td className="px-4 py-3 text-gray-500 text-xs whitespace-nowrap">
                                    {new Date(event.start_at).toLocaleDateString()}<br />
                                    <span className="text-gray-400">{new Date(event.start_at).toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' })}</span>
                                </td>
                                <td className="px-4 py-3 text-gray-500 text-xs">
                                    {event.is_online
                                        ? <span className="text-blue-500 font-medium">Online</span>
                                        : (event.location?.slice(0, 30) ?? <span className="text-gray-400">—</span>)}
                                </td>
                                <td className="px-4 py-3 text-center">
                                    <span className="inline-flex items-center justify-center w-8 h-8 rounded-full bg-indigo-50 text-indigo-700 text-xs font-bold">
                                        {event.attendees_count}
                                    </span>
                                </td>
                                <td className="px-4 py-3 text-gray-500 text-xs">{event.creator?.name ?? '—'}</td>
                                <td className="px-4 py-3 text-right">
                                    <button
                                        onClick={() => handleDelete(event.id)}
                                        disabled={deleting === event.id}
                                        className="text-xs text-red-500 hover:text-red-700 hover:underline disabled:opacity-40"
                                    >
                                        {deleting === event.id ? '…' : 'Delete'}
                                    </button>
                                </td>
                            </tr>
                        ))}
                    </tbody>
                </table>

                {data && data.last_page > 1 && (
                    <div className="flex items-center justify-between px-4 py-3 border-t border-gray-100 bg-gray-50">
                        <span className="text-xs text-gray-500">Page {data.current_page} of {data.last_page}</span>
                        <div className="flex gap-2">
                            <button onClick={() => setPage(p => Math.max(1, p - 1))} disabled={page === 1}
                                className="px-3 py-1 text-xs rounded border border-gray-200 disabled:opacity-40 hover:bg-gray-100">← Prev</button>
                            <button onClick={() => setPage(p => Math.min(data.last_page, p + 1))} disabled={page === data.last_page}
                                className="px-3 py-1 text-xs rounded border border-gray-200 disabled:opacity-40 hover:bg-gray-100">Next →</button>
                        </div>
                    </div>
                )}
            </div>
        </div>
    );
}
