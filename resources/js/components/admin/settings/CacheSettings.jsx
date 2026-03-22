import { useMutation, useQuery, useQueryClient } from '@tanstack/react-query';
import axios from 'axios';
import { useState, useEffect } from 'react';

export default function CacheSettings() {
    const queryClient = useQueryClient();
    const { data = {} } = useQuery({
        queryKey: ['admin-settings-cache'],
        queryFn:  () => axios.get('/api/v1/admin/settings/cache').then(r => r.data),
    });

    const [driver,   setDriver]   = useState('redis');
    const [ttl,      setTtl]      = useState('3600');
    const [cdnUrl,   setCdnUrl]   = useState('');
    const [saved,    setSaved]    = useState(false);
    const [clearing, setClearing] = useState(false);

    useEffect(() => {
        setDriver(data.cache_driver ?? 'redis');
        setTtl(String(data.cache_ttl ?? 3600));
        setCdnUrl(data.cdn_url ?? '');
    }, [data]);

    const save = useMutation({
        mutationFn: (d) => axios.patch('/api/v1/admin/settings/cache', d),
        onSuccess:  () => { queryClient.invalidateQueries({ queryKey: ['admin-settings-cache'] }); setSaved(true); setTimeout(() => setSaved(false), 2500); },
    });

    async function clearCache() {
        setClearing(true);
        try { await axios.post('/api/v1/admin/cache/clear'); } finally { setClearing(false); }
    }

    return (
        <div className="max-w-lg space-y-6">
            <form onSubmit={e => { e.preventDefault(); save.mutate({ cache_driver: driver, cache_ttl: Number(ttl), cdn_url: cdnUrl }); }} className="space-y-4">
                <h2 className="text-lg font-semibold text-gray-900">Cache</h2>
                {saved && <p className="text-sm text-green-600">Saved.</p>}
                <div>
                    <label className="block text-sm font-medium text-gray-700 mb-1">Cache Driver</label>
                    <select className="block w-full rounded border border-gray-300 px-3 py-2 text-sm focus:border-blue-500 focus:outline-none" value={driver} onChange={e => setDriver(e.target.value)}>
                        <option value="redis">Redis</option>
                        <option value="file">File</option>
                        <option value="database">Database</option>
                        <option value="array">Array (dev only)</option>
                    </select>
                </div>
                <div>
                    <label className="block text-sm font-medium text-gray-700 mb-1">Default TTL (seconds)</label>
                    <input type="number" min="60" className="block w-full rounded border border-gray-300 px-3 py-2 text-sm focus:border-blue-500 focus:outline-none" value={ttl} onChange={e => setTtl(e.target.value)} />
                </div>
                <div>
                    <label className="block text-sm font-medium text-gray-700 mb-1">CDN URL (optional)</label>
                    <input type="url" className="block w-full rounded border border-gray-300 px-3 py-2 text-sm focus:border-blue-500 focus:outline-none" placeholder="https://cdn.example.com" value={cdnUrl} onChange={e => setCdnUrl(e.target.value)} />
                </div>
                <button type="submit" disabled={save.isPending} className="rounded bg-blue-600 px-5 py-2 text-sm font-medium text-white hover:bg-blue-700 disabled:opacity-50">
                    {save.isPending ? 'Saving…' : 'Save'}
                </button>
            </form>

            <div className="rounded-lg border border-red-100 bg-red-50 p-4">
                <p className="mb-2 text-sm font-medium text-red-800">Danger Zone</p>
                <p className="mb-3 text-sm text-red-600">Clears all cached data including settings, menus, and theme config.</p>
                <button type="button" onClick={clearCache} disabled={clearing} className="rounded bg-red-600 px-4 py-2 text-sm font-medium text-white hover:bg-red-700 disabled:opacity-50">
                    {clearing ? 'Clearing…' : 'Clear All Cache'}
                </button>
            </div>
        </div>
    );
}
