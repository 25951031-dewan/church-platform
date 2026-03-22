import { useMutation, useQuery, useQueryClient } from '@tanstack/react-query';
import axios from 'axios';
import { useState } from 'react';

const MIME_ICONS = {
    'image/': '🖼',
    'video/': '🎬',
    'audio/': '🎵',
    'application/pdf': '📄',
};

function mimeIcon(mime = '') {
    for (const [prefix, icon] of Object.entries(MIME_ICONS)) {
        if (mime.startsWith(prefix)) return icon;
    }
    return '📎';
}

function formatBytes(bytes = 0) {
    if (bytes < 1024)       return `${bytes} B`;
    if (bytes < 1024 ** 2)  return `${(bytes / 1024).toFixed(1)} KB`;
    return `${(bytes / 1024 ** 2).toFixed(1)} MB`;
}

function StorageBar({ used, total }) {
    const pct = total > 0 ? Math.min((used / total) * 100, 100) : 0;
    return (
        <div>
            <div className="mb-1 flex justify-between text-xs text-gray-500">
                <span>{formatBytes(used)} used</span>
                <span>{formatBytes(total)} total</span>
            </div>
            <div className="h-2 w-full overflow-hidden rounded-full bg-gray-200">
                <div
                    className={`h-full rounded-full transition-all ${pct > 85 ? 'bg-red-500' : 'bg-blue-500'}`}
                    style={{ width: `${pct}%` }}
                />
            </div>
        </div>
    );
}

export default function MediaManager() {
    const queryClient     = useQueryClient();
    const [selected, setSelected] = useState(new Set());
    const [search,   setSearch]   = useState('');
    const [mimeFilter, setMimeFilter] = useState('all');

    const { data, isLoading } = useQuery({
        queryKey: ['admin-media', mimeFilter, search],
        queryFn:  () => axios.get('/api/v1/admin/media', {
            params: { mime_type: mimeFilter !== 'all' ? mimeFilter : undefined, search: search || undefined },
        }).then(r => r.data),
    });

    const deleteSelected = useMutation({
        mutationFn: (ids) => axios.post('/api/v1/admin/media/bulk-delete', { ids: [...ids] }),
        onSuccess:  () => {
            setSelected(new Set());
            queryClient.invalidateQueries({ queryKey: ['admin-media'] });
        },
    });

    const media  = data?.data ?? [];
    const stats  = data?.stats ?? { used: 0, total: 0, count: 0 };

    function toggleSelect(id) {
        setSelected(prev => {
            const next = new Set(prev);
            next.has(id) ? next.delete(id) : next.add(id);
            return next;
        });
    }

    function toggleAll() {
        setSelected(prev =>
            prev.size === media.length ? new Set() : new Set(media.map(m => m.id))
        );
    }

    return (
        <div className="mx-auto max-w-5xl px-4 py-8 sm:px-6">
            <div className="mb-6 flex items-center justify-between">
                <h1 className="text-xl font-bold text-gray-900">Media Manager</h1>
                <label className="cursor-pointer rounded bg-blue-600 px-4 py-2 text-sm font-medium text-white hover:bg-blue-700">
                    Upload
                    <input type="file" multiple className="sr-only"
                        onChange={async e => {
                            const files = Array.from(e.target.files ?? []);
                            const form = new FormData();
                            files.forEach(f => form.append('files[]', f));
                            await axios.post('/api/v1/admin/media', form, { headers: { 'Content-Type': 'multipart/form-data' } });
                            queryClient.invalidateQueries({ queryKey: ['admin-media'] });
                        }}
                    />
                </label>
            </div>

            {/* Storage stats */}
            <div className="mb-6 rounded-lg border border-gray-200 bg-white p-4">
                <div className="mb-3 flex items-center justify-between text-sm">
                    <span className="font-medium text-gray-900">Storage</span>
                    <span className="text-gray-500">{stats.count} files</span>
                </div>
                <StorageBar used={stats.used} total={stats.total} />
            </div>

            {/* Filters */}
            <div className="mb-4 flex flex-wrap gap-3">
                <input
                    type="search"
                    placeholder="Search files…"
                    className="flex-1 min-w-48 rounded border border-gray-300 px-3 py-1.5 text-sm focus:border-blue-500 focus:outline-none"
                    value={search}
                    onChange={e => setSearch(e.target.value)}
                />
                <select
                    className="rounded border border-gray-300 px-3 py-1.5 text-sm focus:border-blue-500 focus:outline-none"
                    value={mimeFilter}
                    onChange={e => setMimeFilter(e.target.value)}
                >
                    <option value="all">All types</option>
                    <option value="image/">Images</option>
                    <option value="video/">Videos</option>
                    <option value="audio/">Audio</option>
                    <option value="application/pdf">PDFs</option>
                </select>

                {selected.size > 0 && (
                    <button
                        onClick={() => window.confirm(`Delete ${selected.size} file(s)?`) && deleteSelected.mutate(selected)}
                        disabled={deleteSelected.isPending}
                        className="rounded bg-red-600 px-4 py-1.5 text-sm font-medium text-white hover:bg-red-700 disabled:opacity-50"
                    >
                        Delete {selected.size} selected
                    </button>
                )}
            </div>

            {/* File grid */}
            {isLoading ? (
                <div className="py-12 text-center text-sm text-gray-400">Loading…</div>
            ) : media.length === 0 ? (
                <div className="rounded-lg border border-dashed border-gray-300 py-12 text-center text-sm text-gray-400">
                    No media files found.
                </div>
            ) : (
                <div>
                    {/* Select all */}
                    <label className="mb-2 flex cursor-pointer items-center gap-2 text-sm text-gray-600">
                        <input type="checkbox" checked={selected.size === media.length && media.length > 0} onChange={toggleAll} />
                        Select all ({media.length})
                    </label>

                    <div className="grid grid-cols-2 gap-3 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-5">
                        {media.map(file => (
                            <div
                                key={file.id}
                                onClick={() => toggleSelect(file.id)}
                                className={[
                                    'relative cursor-pointer overflow-hidden rounded-lg border-2 transition',
                                    selected.has(file.id)
                                        ? 'border-blue-500 ring-1 ring-blue-500'
                                        : 'border-gray-200 hover:border-gray-300',
                                ].join(' ')}
                            >
                                {/* Preview */}
                                {file.mime_type?.startsWith('image/') ? (
                                    <img src={file.url} alt={file.name} className="h-28 w-full object-cover" loading="lazy" />
                                ) : (
                                    <div className="flex h-28 items-center justify-center bg-gray-50 text-4xl">
                                        {mimeIcon(file.mime_type)}
                                    </div>
                                )}

                                {/* Selection indicator */}
                                {selected.has(file.id) && (
                                    <div className="absolute right-1.5 top-1.5 flex h-5 w-5 items-center justify-center rounded-full bg-blue-600 text-white text-xs">✓</div>
                                )}

                                {/* File info */}
                                <div className="p-2">
                                    <p className="truncate text-xs font-medium text-gray-900">{file.name}</p>
                                    <p className="text-xs text-gray-400">{formatBytes(file.size)}</p>
                                </div>
                            </div>
                        ))}
                    </div>
                </div>
            )}
        </div>
    );
}
