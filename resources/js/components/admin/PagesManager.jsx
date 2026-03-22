import { useMutation, useQuery, useQueryClient } from '@tanstack/react-query';
import axios from 'axios';
import { useState } from 'react';
import { Link } from 'react-router-dom';

function StatusBadge({ status }) {
    return (
        <span className={[
            'rounded-full px-2 py-0.5 text-xs font-medium',
            status === 'published'
                ? 'bg-green-100 text-green-700'
                : 'bg-gray-100 text-gray-500',
        ].join(' ')}>
            {status}
        </span>
    );
}

function NewPageModal({ churchId, onClose, onCreated }) {
    const [title, setTitle] = useState('');
    const [status, setStatus] = useState('draft');

    const create = useMutation({
        mutationFn: (data) => axios.post('/api/v1/admin/pages', data),
        onSuccess: (res) => onCreated(res.data),
    });

    return (
        <div className="fixed inset-0 z-50 flex items-center justify-center bg-black/40">
            <div className="w-full max-w-md rounded-xl bg-white p-6 shadow-xl">
                <h2 className="mb-4 text-lg font-semibold text-gray-900">New Page</h2>
                <form onSubmit={e => { e.preventDefault(); create.mutate({ title, status, church_id: churchId ?? undefined }); }}>
                    <input
                        className="mb-3 block w-full rounded border border-gray-300 px-3 py-2 text-sm focus:border-blue-500 focus:outline-none"
                        placeholder="Page title *"
                        value={title}
                        onChange={e => setTitle(e.target.value)}
                        required
                        autoFocus
                    />
                    <select
                        className="mb-4 block w-full rounded border border-gray-300 px-3 py-2 text-sm focus:border-blue-500 focus:outline-none"
                        value={status}
                        onChange={e => setStatus(e.target.value)}
                    >
                        <option value="draft">Draft</option>
                        <option value="published">Published</option>
                    </select>
                    <div className="flex justify-end gap-2">
                        <button type="button" onClick={onClose} className="rounded border border-gray-300 px-4 py-2 text-sm text-gray-700 hover:bg-gray-50">
                            Cancel
                        </button>
                        <button type="submit" disabled={create.isPending} className="rounded bg-blue-600 px-4 py-2 text-sm font-medium text-white hover:bg-blue-700 disabled:opacity-50">
                            {create.isPending ? 'Creating…' : 'Create'}
                        </button>
                    </div>
                </form>
            </div>
        </div>
    );
}

export default function PagesManager({ churchId = null }) {
    const queryClient        = useQueryClient();
    const [showNew, setShowNew] = useState(false);

    const { data: pages = [], isLoading } = useQuery({
        queryKey: ['admin-pages', churchId],
        queryFn:  () => axios.get('/api/v1/admin/pages', {
            params: churchId ? { church_id: churchId } : {},
        }).then(r => r.data),
    });

    const toggleStatus = useMutation({
        mutationFn: ({ id, status }) => axios.patch(`/api/v1/admin/pages/${id}`, { status }),
        onSuccess:  () => queryClient.invalidateQueries({ queryKey: ['admin-pages', churchId] }),
    });

    const deletePage = useMutation({
        mutationFn: (id) => axios.delete(`/api/v1/admin/pages/${id}`),
        onSuccess:  () => queryClient.invalidateQueries({ queryKey: ['admin-pages', churchId] }),
    });

    function handleCreated(page) {
        setShowNew(false);
        queryClient.invalidateQueries({ queryKey: ['admin-pages', churchId] });
        // Navigate straight to builder after creation
        window.location.href = `/admin/pages/${page.id}/builder`;
    }

    if (isLoading) {
        return <div className="py-12 text-center text-sm text-gray-400">Loading pages…</div>;
    }

    return (
        <div className="mx-auto max-w-4xl px-4 py-8 sm:px-6">
            <div className="mb-6 flex items-center justify-between">
                <h1 className="text-xl font-bold text-gray-900">
                    {churchId ? 'Church Pages' : 'Pages'}
                </h1>
                <button
                    onClick={() => setShowNew(true)}
                    className="rounded bg-blue-600 px-4 py-2 text-sm font-medium text-white hover:bg-blue-700"
                >
                    + New Page
                </button>
            </div>

            {pages.length === 0 ? (
                <div className="rounded-lg border border-dashed border-gray-300 py-12 text-center text-sm text-gray-400">
                    No pages yet. Create one to get started.
                </div>
            ) : (
                <div className="divide-y divide-gray-100 rounded-lg border border-gray-200 bg-white">
                    {pages.map(page => (
                        <div key={page.id} className="flex items-center justify-between gap-4 px-4 py-3">
                            <div className="min-w-0">
                                <div className="flex items-center gap-2">
                                    <span className="truncate font-medium text-gray-900">{page.title}</span>
                                    <StatusBadge status={page.status} />
                                    {page.use_builder && (
                                        <span className="rounded-full bg-purple-100 px-2 py-0.5 text-xs font-medium text-purple-700">
                                            Builder
                                        </span>
                                    )}
                                </div>
                                <p className="mt-0.5 truncate text-xs text-gray-400">/{page.slug}</p>
                            </div>

                            <div className="flex shrink-0 items-center gap-3">
                                {/* Edit with Builder */}
                                <Link
                                    to={`/admin/pages/${page.id}/builder`}
                                    className="rounded border border-purple-200 bg-purple-50 px-3 py-1.5 text-xs font-medium text-purple-700 hover:bg-purple-100"
                                >
                                    Edit with Builder
                                </Link>

                                {/* Toggle publish */}
                                <button
                                    onClick={() => toggleStatus.mutate({
                                        id: page.id,
                                        status: page.status === 'published' ? 'draft' : 'published',
                                    })}
                                    className="text-xs text-blue-600 hover:underline"
                                >
                                    {page.status === 'published' ? 'Unpublish' : 'Publish'}
                                </button>

                                {/* Delete */}
                                <button
                                    onClick={() => window.confirm(`Delete "${page.title}"?`) && deletePage.mutate(page.id)}
                                    className="text-xs text-red-500 hover:underline"
                                >
                                    Delete
                                </button>
                            </div>
                        </div>
                    ))}
                </div>
            )}

            {showNew && (
                <NewPageModal
                    churchId={churchId}
                    onClose={() => setShowNew(false)}
                    onCreated={handleCreated}
                />
            )}
        </div>
    );
}
