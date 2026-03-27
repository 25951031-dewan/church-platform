import React, { useEffect, useState, useCallback } from 'react';
import axios from 'axios';

interface Role { name: string }
interface User {
    id: number;
    name: string;
    email: string;
    created_at: string;
    roles: Role[];
}
interface Paginated {
    data: User[];
    current_page: number;
    last_page: number;
    total: number;
}

const ROLE_COLORS: Record<string, string> = {
    admin: 'bg-red-100 text-red-700',
    church_leader: 'bg-purple-100 text-purple-700',
    member: 'bg-blue-100 text-blue-700',
};

function initials(name: string) {
    return name.split(' ').map(w => w[0]).join('').toUpperCase().slice(0, 2);
}

export default function AdminUsers() {
    const [data, setData] = useState<Paginated | null>(null);
    const [loading, setLoading] = useState(true);
    const [search, setSearch] = useState('');
    const [role, setRole] = useState('');
    const [page, setPage] = useState(1);
    const [deleting, setDeleting] = useState<number | null>(null);
    const [roleEditing, setRoleEditing] = useState<number | null>(null);

    const load = useCallback(() => {
        setLoading(true);
        axios.get('/api/v1/admin/users', { params: { search, role, page } })
            .then(r => setData(r.data))
            .catch(() => {})
            .finally(() => setLoading(false));
    }, [search, role, page]);

    useEffect(() => { load(); }, [load]);

    // debounce search
    useEffect(() => {
        const t = setTimeout(() => load(), 400);
        return () => clearTimeout(t);
    }, [search]); // eslint-disable-line

    const handleDelete = async (id: number) => {
        if (!confirm('Delete this user? This cannot be undone.')) return;
        setDeleting(id);
        try {
            await axios.delete(`/api/v1/admin/users/${id}`);
            load();
        } finally {
            setDeleting(null);
        }
    };

    const handleRoleChange = async (id: number, newRole: string) => {
        setRoleEditing(id);
        try {
            await axios.patch(`/api/v1/admin/users/${id}/role`, { role: newRole });
            load();
        } finally {
            setRoleEditing(null);
        }
    };

    return (
        <div className="space-y-4">
            {/* Header */}
            <div className="flex items-center justify-between">
                <div>
                    <h1 className="text-2xl font-bold text-gray-800">Users</h1>
                    {data && <p className="text-sm text-gray-500">{data.total} total users</p>}
                </div>
            </div>

            {/* Filters */}
            <div className="bg-white rounded-xl border border-gray-100 shadow-sm p-4 flex flex-wrap gap-3">
                <input
                    type="text"
                    placeholder="Search name or email…"
                    value={search}
                    onChange={e => { setSearch(e.target.value); setPage(1); }}
                    className="flex-1 min-w-48 border border-gray-200 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-300"
                />
                <select
                    value={role}
                    onChange={e => { setRole(e.target.value); setPage(1); }}
                    className="border border-gray-200 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-300"
                >
                    <option value="">All Roles</option>
                    <option value="admin">Admin</option>
                    <option value="church_leader">Church Leader</option>
                    <option value="member">Member</option>
                </select>
            </div>

            {/* Table */}
            <div className="bg-white rounded-xl border border-gray-100 shadow-sm overflow-hidden">
                <table className="w-full text-sm">
                    <thead>
                        <tr className="border-b border-gray-100 bg-gray-50">
                            <th className="text-left px-4 py-3 font-semibold text-gray-600">User</th>
                            <th className="text-left px-4 py-3 font-semibold text-gray-600">Email</th>
                            <th className="text-left px-4 py-3 font-semibold text-gray-600">Role</th>
                            <th className="text-left px-4 py-3 font-semibold text-gray-600">Joined</th>
                            <th className="text-right px-4 py-3 font-semibold text-gray-600">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        {loading ? (
                            [...Array(5)].map((_, i) => (
                                <tr key={i} className="border-b border-gray-50 animate-pulse">
                                    <td className="px-4 py-3"><div className="h-4 bg-gray-200 rounded w-32" /></td>
                                    <td className="px-4 py-3"><div className="h-4 bg-gray-200 rounded w-40" /></td>
                                    <td className="px-4 py-3"><div className="h-4 bg-gray-200 rounded w-20" /></td>
                                    <td className="px-4 py-3"><div className="h-4 bg-gray-200 rounded w-24" /></td>
                                    <td className="px-4 py-3" />
                                </tr>
                            ))
                        ) : data?.data.length === 0 ? (
                            <tr>
                                <td colSpan={5} className="px-4 py-8 text-center text-gray-400">No users found</td>
                            </tr>
                        ) : data?.data.map(user => (
                            <tr key={user.id} className="border-b border-gray-50 hover:bg-gray-50 transition-colors">
                                <td className="px-4 py-3">
                                    <div className="flex items-center gap-3">
                                        <div className="w-8 h-8 rounded-full bg-indigo-100 text-indigo-700 flex items-center justify-center text-xs font-bold flex-shrink-0">
                                            {initials(user.name)}
                                        </div>
                                        <span className="font-medium text-gray-800">{user.name}</span>
                                    </div>
                                </td>
                                <td className="px-4 py-3 text-gray-500">{user.email}</td>
                                <td className="px-4 py-3">
                                    <select
                                        value={user.roles[0]?.name ?? 'member'}
                                        onChange={e => handleRoleChange(user.id, e.target.value)}
                                        disabled={roleEditing === user.id}
                                        className={`text-xs font-medium px-2 py-1 rounded-full border-0 cursor-pointer focus:ring-2 focus:ring-indigo-300 ${ROLE_COLORS[user.roles[0]?.name ?? 'member'] ?? 'bg-gray-100 text-gray-600'}`}
                                    >
                                        <option value="admin">admin</option>
                                        <option value="church_leader">church_leader</option>
                                        <option value="member">member</option>
                                    </select>
                                </td>
                                <td className="px-4 py-3 text-gray-400 text-xs">
                                    {new Date(user.created_at).toLocaleDateString()}
                                </td>
                                <td className="px-4 py-3 text-right">
                                    <button
                                        onClick={() => handleDelete(user.id)}
                                        disabled={deleting === user.id}
                                        className="text-xs text-red-500 hover:text-red-700 hover:underline disabled:opacity-40"
                                    >
                                        {deleting === user.id ? 'Deleting…' : 'Delete'}
                                    </button>
                                </td>
                            </tr>
                        ))}
                    </tbody>
                </table>

                {/* Pagination */}
                {data && data.last_page > 1 && (
                    <div className="flex items-center justify-between px-4 py-3 border-t border-gray-100 bg-gray-50">
                        <span className="text-xs text-gray-500">
                            Page {data.current_page} of {data.last_page}
                        </span>
                        <div className="flex gap-2">
                            <button
                                onClick={() => setPage(p => Math.max(1, p - 1))}
                                disabled={page === 1}
                                className="px-3 py-1 text-xs rounded border border-gray-200 disabled:opacity-40 hover:bg-gray-100"
                            >
                                ← Prev
                            </button>
                            <button
                                onClick={() => setPage(p => Math.min(data.last_page, p + 1))}
                                disabled={page === data.last_page}
                                className="px-3 py-1 text-xs rounded border border-gray-200 disabled:opacity-40 hover:bg-gray-100"
                            >
                                Next →
                            </button>
                        </div>
                    </div>
                )}
            </div>
        </div>
    );
}
