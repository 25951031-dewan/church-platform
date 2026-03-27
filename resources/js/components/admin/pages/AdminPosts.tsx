import React, { useCallback, useEffect, useState } from 'react';
import axios from 'axios';

interface Author { id: number; name: string }
interface Post {
    id: number;
    type: string;
    body: string | null;
    status: string;
    is_approved: boolean | null;
    created_at: string;
    author: Author | null;
    comments_count: number;
    reactions_count: number;
}
interface Paginated { data: Post[]; current_page: number; last_page: number; total: number }

const TYPE_COLORS: Record<string, string> = {
    post:        'bg-blue-100 text-blue-700',
    prayer:      'bg-purple-100 text-purple-700',
    blessing:    'bg-yellow-100 text-yellow-700',
    poll:        'bg-green-100 text-green-700',
    bible_study: 'bg-indigo-100 text-indigo-700',
};

const STATUS_COLORS: Record<string, string> = {
    published: 'bg-emerald-100 text-emerald-700',
    draft:     'bg-gray-100 text-gray-600',
    rejected:  'bg-red-100 text-red-600',
};

const POST_TYPES = ['post', 'prayer', 'blessing', 'poll', 'bible_study'];

export default function AdminPosts() {
    const [data, setData]       = useState<Paginated | null>(null);
    const [loading, setLoading] = useState(true);
    const [search, setSearch]   = useState('');
    const [type, setType]       = useState('');
    const [page, setPage]       = useState(1);
    const [busy, setBusy]       = useState<number | null>(null);

    const load = useCallback(() => {
        setLoading(true);
        axios.get('/api/v1/admin/posts', { params: { search, type, page } })
            .then(r => setData(r.data))
            .catch(() => {})
            .finally(() => setLoading(false));
    }, [search, type, page]);

    useEffect(() => { load(); }, [load]);

    // Debounce search input
    useEffect(() => {
        const t = setTimeout(() => { setPage(1); load(); }, 400);
        return () => clearTimeout(t);
    }, [search]); // eslint-disable-line

    const handleDelete = async (id: number) => {
        if (!confirm('Permanently delete this post?')) return;
        setBusy(id);
        await axios.delete(`/api/v1/admin/posts/${id}`).catch(() => {});
        setBusy(null);
        load();
    };

    const handleApprove = async (id: number) => {
        setBusy(id);
        await axios.patch(`/api/v1/admin/posts/${id}/moderate`, { status: 'published' }).catch(() => {});
        setBusy(null);
        load();
    };

    return (
        <div className="space-y-4">
            <div>
                <h1 className="text-2xl font-bold text-gray-800">Posts</h1>
                {data && <p className="text-sm text-gray-500">{data.total} total posts</p>}
            </div>

            {/* Filters */}
            <div className="bg-white rounded-xl border border-gray-100 shadow-sm p-4 flex flex-wrap gap-3 items-center">
                <input
                    type="text"
                    placeholder="Search content…"
                    value={search}
                    onChange={e => setSearch(e.target.value)}
                    className="flex-1 min-w-48 border border-gray-200 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-300"
                />
                {/* Type filter tabs */}
                <div className="flex gap-1 flex-wrap">
                    {['', ...POST_TYPES].map(t => (
                        <button
                            key={t}
                            onClick={() => { setType(t); setPage(1); }}
                            className={`px-3 py-1.5 rounded-lg text-xs font-medium transition-colors ${
                                type === t
                                    ? 'bg-indigo-600 text-white'
                                    : 'bg-gray-100 text-gray-600 hover:bg-gray-200'
                            }`}
                        >
                            {t === '' ? 'All' : t.replace('_', ' ')}
                        </button>
                    ))}
                </div>
            </div>

            {/* Table */}
            <div className="bg-white rounded-xl border border-gray-100 shadow-sm overflow-hidden">
                <table className="w-full text-sm">
                    <thead>
                        <tr className="border-b border-gray-100 bg-gray-50">
                            <th className="text-left px-4 py-3 font-semibold text-gray-600">Author</th>
                            <th className="text-left px-4 py-3 font-semibold text-gray-600">Type</th>
                            <th className="text-left px-4 py-3 font-semibold text-gray-600 w-72">Content</th>
                            <th className="text-left px-4 py-3 font-semibold text-gray-600">Status</th>
                            <th className="text-left px-4 py-3 font-semibold text-gray-600">Date</th>
                            <th className="text-right px-4 py-3 font-semibold text-gray-600">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        {loading ? (
                            [...Array(6)].map((_, i) => (
                                <tr key={i} className="border-b border-gray-50 animate-pulse">
                                    {[...Array(6)].map((__, j) => (
                                        <td key={j} className="px-4 py-3">
                                            <div className="h-4 bg-gray-200 rounded w-full" />
                                        </td>
                                    ))}
                                </tr>
                            ))
                        ) : data?.data.length === 0 ? (
                            <tr>
                                <td colSpan={6} className="px-4 py-10 text-center text-gray-400">No posts found</td>
                            </tr>
                        ) : data?.data.map(post => (
                            <tr key={post.id} className="border-b border-gray-50 hover:bg-gray-50 transition-colors">
                                <td className="px-4 py-3 font-medium text-gray-700">
                                    {post.author?.name ?? <span className="text-gray-400 italic">Anonymous</span>}
                                </td>
                                <td className="px-4 py-3">
                                    <span className={`text-xs font-semibold px-2 py-0.5 rounded-full ${TYPE_COLORS[post.type] ?? 'bg-gray-100 text-gray-600'}`}>
                                        {post.type.replace('_', ' ')}
                                    </span>
                                </td>
                                <td className="px-4 py-3 text-gray-500 max-w-xs">
                                    <span className="line-clamp-2 block">
                                        {post.body?.slice(0, 100) ?? <em className="text-gray-400">no text</em>}
                                    </span>
                                </td>
                                <td className="px-4 py-3">
                                    <span className={`text-xs font-medium px-2 py-0.5 rounded-full ${STATUS_COLORS[post.status] ?? 'bg-gray-100 text-gray-600'}`}>
                                        {post.status}
                                    </span>
                                </td>
                                <td className="px-4 py-3 text-gray-400 text-xs whitespace-nowrap">
                                    {new Date(post.created_at).toLocaleDateString()}
                                </td>
                                <td className="px-4 py-3 text-right space-x-2">
                                    {post.is_approved === null && (
                                        <button
                                            onClick={() => handleApprove(post.id)}
                                            disabled={busy === post.id}
                                            className="text-xs text-emerald-600 hover:text-emerald-800 hover:underline disabled:opacity-40"
                                        >
                                            Approve
                                        </button>
                                    )}
                                    <button
                                        onClick={() => handleDelete(post.id)}
                                        disabled={busy === post.id}
                                        className="text-xs text-red-500 hover:text-red-700 hover:underline disabled:opacity-40"
                                    >
                                        {busy === post.id ? '…' : 'Delete'}
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
