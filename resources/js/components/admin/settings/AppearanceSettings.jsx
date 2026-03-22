import { useMutation, useQuery, useQueryClient } from '@tanstack/react-query';
import axios from 'axios';
import { useState, useEffect } from 'react';

export default function AppearanceSettings() {
    const queryClient = useQueryClient();
    const { data = {} } = useQuery({
        queryKey: ['admin-settings-appearance'],
        queryFn:  () => axios.get('/api/v1/admin/settings/appearance').then(r => r.data),
    });

    const [primaryColor, setPrimaryColor] = useState('#2563eb');
    const [customCss,    setCustomCss]    = useState('');
    const [customJs,     setCustomJs]     = useState('');
    const [saved,        setSaved]        = useState(false);

    useEffect(() => {
        setPrimaryColor(data.primary_color ?? '#2563eb');
        setCustomCss(data.custom_css ?? '');
        setCustomJs(data.custom_js ?? '');
    }, [data]);

    const save = useMutation({
        mutationFn: (d) => axios.patch('/api/v1/admin/settings/appearance', d),
        onSuccess:  () => { queryClient.invalidateQueries({ queryKey: ['admin-settings-appearance'] }); setSaved(true); setTimeout(() => setSaved(false), 2500); },
    });

    return (
        <form onSubmit={e => { e.preventDefault(); save.mutate({ primary_color: primaryColor, custom_css: customCss, custom_js: customJs }); }} className="max-w-xl space-y-4">
            <h2 className="text-lg font-semibold text-gray-900">Appearance</h2>
            {saved && <p className="text-sm text-green-600">Saved.</p>}

            <div>
                <label className="block text-sm font-medium text-gray-700 mb-1">Logo</label>
                <input type="file" accept="image/*" className="block text-sm text-gray-500" />
            </div>
            <div>
                <label className="block text-sm font-medium text-gray-700 mb-1">Favicon</label>
                <input type="file" accept="image/x-icon,image/png" className="block text-sm text-gray-500" />
            </div>
            <div>
                <label className="block text-sm font-medium text-gray-700 mb-1">Primary Color</label>
                <div className="flex items-center gap-3">
                    <input type="color" className="h-9 w-16 rounded border border-gray-300 p-0.5 cursor-pointer" value={primaryColor} onChange={e => setPrimaryColor(e.target.value)} />
                    <input type="text" className="w-28 rounded border border-gray-300 px-3 py-2 text-sm font-mono focus:border-blue-500 focus:outline-none" value={primaryColor} onChange={e => setPrimaryColor(e.target.value)} />
                </div>
            </div>
            <div>
                <label className="block text-sm font-medium text-gray-700 mb-1">Custom CSS</label>
                <textarea rows={6} className="block w-full rounded border border-gray-300 px-3 py-2 font-mono text-sm focus:border-blue-500 focus:outline-none" value={customCss} onChange={e => setCustomCss(e.target.value)} placeholder="/* Custom styles */" />
            </div>
            <div>
                <label className="block text-sm font-medium text-gray-700 mb-1">Custom JS (head)</label>
                <textarea rows={4} className="block w-full rounded border border-gray-300 px-3 py-2 font-mono text-sm focus:border-blue-500 focus:outline-none" value={customJs} onChange={e => setCustomJs(e.target.value)} placeholder="// Custom scripts" />
            </div>
            <button type="submit" disabled={save.isPending} className="rounded bg-blue-600 px-5 py-2 text-sm font-medium text-white hover:bg-blue-700 disabled:opacity-50">
                {save.isPending ? 'Saving…' : 'Save'}
            </button>
        </form>
    );
}
