import { useMutation, useQuery, useQueryClient } from '@tanstack/react-query';
import axios from 'axios';
import { useState, useEffect } from 'react';

export default function ApiSettings() {
    const queryClient = useQueryClient();
    const { data = {} } = useQuery({
        queryKey: ['admin-settings-api'],
        queryFn:  () => axios.get('/api/v1/admin/settings/api').then(r => r.data),
    });

    const [turnstileSiteKey,   setTurnstileSiteKey]   = useState('');
    const [turnstileSecretKey, setTurnstileSecretKey] = useState('');
    const [rateLimit,          setRateLimit]          = useState('60');
    const [saved,              setSaved]              = useState(false);

    useEffect(() => {
        setTurnstileSiteKey(data.turnstile_site_key ?? '');
        setRateLimit(String(data.api_rate_limit ?? 60));
    }, [data]);

    const save = useMutation({
        mutationFn: (d) => axios.patch('/api/v1/admin/settings/api', d),
        onSuccess:  () => { queryClient.invalidateQueries({ queryKey: ['admin-settings-api'] }); setSaved(true); setTimeout(() => setSaved(false), 2500); },
    });

    return (
        <form onSubmit={e => { e.preventDefault(); save.mutate({ turnstile_site_key: turnstileSiteKey, turnstile_secret_key: turnstileSecretKey || undefined, api_rate_limit: Number(rateLimit) }); }} className="max-w-lg space-y-4">
            <h2 className="text-lg font-semibold text-gray-900">API Keys</h2>
            {saved && <p className="text-sm text-green-600">Saved.</p>}

            <div className="rounded-lg border border-gray-200 p-4 space-y-3">
                <p className="text-sm font-medium text-gray-900">Cloudflare Turnstile</p>
                <div>
                    <label className="block text-sm font-medium text-gray-700 mb-1">Site Key</label>
                    <input className="block w-full rounded border border-gray-300 px-3 py-2 text-sm font-mono focus:border-blue-500 focus:outline-none" value={turnstileSiteKey} onChange={e => setTurnstileSiteKey(e.target.value)} placeholder="0x4AAAAAAA…" />
                </div>
                <div>
                    <label className="block text-sm font-medium text-gray-700 mb-1">Secret Key</label>
                    <input type="password" className="block w-full rounded border border-gray-300 px-3 py-2 text-sm font-mono focus:border-blue-500 focus:outline-none" value={turnstileSecretKey} onChange={e => setTurnstileSecretKey(e.target.value)} placeholder="Leave blank to keep current" />
                </div>
            </div>

            <div>
                <label className="block text-sm font-medium text-gray-700 mb-1">API Rate Limit (requests/min)</label>
                <input type="number" min="10" className="block w-32 rounded border border-gray-300 px-3 py-2 text-sm focus:border-blue-500 focus:outline-none" value={rateLimit} onChange={e => setRateLimit(e.target.value)} />
            </div>

            <button type="submit" disabled={save.isPending} className="rounded bg-blue-600 px-5 py-2 text-sm font-medium text-white hover:bg-blue-700 disabled:opacity-50">
                {save.isPending ? 'Saving…' : 'Save'}
            </button>
        </form>
    );
}
