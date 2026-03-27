import React, { useCallback, useEffect, useState } from 'react';
import axios from 'axios';

interface Community {
    id: number;
    name: string;
    slug: string;
    privacy: string;
    community_type: string | null;
    status: string;
    members_count: number;
    posts_count: number;
    requires_approval: boolean;
    created_at: string;
}
interface Paginated { data: Community[]; current_page: number; last_page: number; total: number }

const PRIVACY_COLORS: Record<string, string> = {
    public:  'bg-green-100 text-green-700',
    private: 'bg-red-100 text-red-700',
    secret:  'bg-gray-100 text-gray-600',
};

export default function AdminCommunities() {
    const [data, setData]         = useState<Paginated | null>(null);
    const [loading, setLoading]   = useState(true);
    const [search, setSearch]     = useState('');
    const [page, setPage]         = useState(1);
    const [deleting, setDeleting] = useState<number | null>(null);

    const load = useCallback(() => {
        setLoading(true);
        axios.get('/api/v1/admin/communities', { params: { search, page } })
            .then(r => setData(r.data))
            .catch(() => {})
            .finally(() => setLoading(false));
    }, [search, page]);

    useEffect(() => { load(); }, [load]);

    useEffect(() => {
        const t = setTimeout(() => { setPage(1); load(); }, 400);
        return () => clearTimeout(t);
    }, [search]); // eslint-disable-line

    const handleDelete = async (id: number) => {
        if (!confirm('Delete this community and all its posts?')) return;
        setDeleting(id);
        await axios.delete(`/api/v1/admin/communities/${id}`).catch(() => {});
        setDeleting(null);
        load();
    };

    return (
        <div className="space-y-4">
            <div>
                <h1 className="text-2xl font-bold text-gray-800">Communities</h1>
                {data && <p className="text-sm text-gray-500">{data.total} total communities</p>}
            </div>

            {/* Search */}
            <div className="bg-white rounded-xl border border-gray-100 shadow-sm p-4">
                <input
                    type="text"
                    placeholder="Search communities…"
                    value={search}
                    onChange={e => setSearch(e.target.value)}
                    className="w-full max-w-sm border border-gray-200 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-300"
                />
            </div>

            {/* Table */}
            <div className="bg-white rounded-xl border border-gray-100 shadow-sm overflow-hidden">
                <table className="w-full text-sm">
                    <thead>
                        <tr className="border-b border-gray-100 bg-gray-50">
                            <th className="text-left px-4 py-3 font-semibold text-gray-600">Name</th>
                            <th className="text-left px-4 py-3 font-semibold text-gray-600">Privacy</th>
                            <th className="text-left px-4 py-3 font-semibold text-gray-600">Type</th>
                            <th className="text-left px-4 py-3 font-semibold text-gray-600">Members</th>
                            <th className="text-left px-4 py-3 font-semibold text-gray-600">Posts</th>
                            <th className="text-left px-4 py-3 font-semibold text-gray-600">Approval</th>
                            <th className="text-left px-4 py-3 font-semibold text-gray-600">Created</th>
                            <th className="text-right px-4 py-3 font-semibold text-gray-600">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        {loading ? (
                            [...Array(5)].map((_, i) => (
                                <tr key={i} className="border-b border-gray-50 animate-pulse">
                                    {[...Array(8)].map((__, j) => (
                                        <td key={j} className="px-4 py-3"><div className="h-4 bg-gray-200 rounded" /></td>
                                    ))}
                                </tr>
                            ))
                        ) : data?.data.length === 0 ? (
                            <tr>
                                <td colSpan={8} className="px-4 py-10 text-center text-gray-400">No communities found</td>
                            </tr>
                        ) : data?.data.map(c => (
                            <tr key={c.id} className="border-b border-gray-50 hover:bg-gray-50 transition-colors">
                                <td className="px-4 py-3">
                                    <div className="font-medium text-gray-800">{c.name}</div>
                                    <div className="text-xs text-gray-400">{c.slug}</div>
                                </td>
                                <td className="px-4 py-3">
                                    <span className={`text-xs font-semibold px-2 py-0.5 rounded-full ${PRIVACY_COLORS[c.privacy] ?? 'bg-gray-100 text-gray-600'}`}>
                                        {c.privacy}
                                    </span>
                                </td>
                                <td className="px-4 py-3 text-gray-500 text-xs capitalize">
                                    {c.community_type ?? '—'}
                                </td>
                                <td className="px-4 py-3">
                                    <span className="font-semibold text-gray-700">{c.members_count.toLocaleString()}</span>
                                </td>
                                <td className="px-4 py-3 text-gray-500">{c.posts_count.toLocaleString()}</td>
                                <td className="px-4 py-3">
                                    {c.requires_approval
                                        ? <span className="text-xs bg-amber-100 text-amber-700 px-2 py-0.5 rounded-full">Required</span>
                                        : <span className="text-xs text-gray-400">Open</span>}
                                </td>
                                <td className="px-4 py-3 text-gray-400 text-xs whitespace-nowrap">
                                    {new Date(c.created_at).toLocaleDateString()}
                                </td>
                                <td className="px-4 py-3 text-right">
                                    <button
                                        onClick={() => handleDelete(c.id)}
                                        disabled={deleting === c.id}
                                        className="text-xs text-red-500 hover:text-red-700 hover:underline disabled:opacity-40"
                                    >
                                        {deleting === c.id ? '…' : 'Delete'}
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
