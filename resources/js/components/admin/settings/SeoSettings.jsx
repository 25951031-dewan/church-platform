import { useMutation, useQuery, useQueryClient } from '@tanstack/react-query';
import axios from 'axios';
import { useState, useEffect } from 'react';

export default function SeoSettings() {
    const queryClient = useQueryClient();
    const { data = {} } = useQuery({
        queryKey: ['admin-settings-seo'],
        queryFn:  () => axios.get('/api/v1/admin/settings/seo').then(r => r.data),
    });

    const [metaTitle,       setMetaTitle]       = useState('');
    const [metaDescription, setMetaDescription] = useState('');
    const [analyticsId,     setAnalyticsId]     = useState('');
    const [sitemapEnabled,  setSitemapEnabled]  = useState(true);
    const [saved,           setSaved]           = useState(false);

    useEffect(() => {
        setMetaTitle(data.meta_title ?? '');
        setMetaDescription(data.meta_description ?? '');
        setAnalyticsId(data.analytics_id ?? '');
        setSitemapEnabled(data.sitemap_enabled ?? true);
    }, [data]);

    const save = useMutation({
        mutationFn: (d) => axios.patch('/api/v1/admin/settings/seo', d),
        onSuccess:  () => { queryClient.invalidateQueries({ queryKey: ['admin-settings-seo'] }); setSaved(true); setTimeout(() => setSaved(false), 2500); },
    });

    return (
        <form onSubmit={e => { e.preventDefault(); save.mutate({ meta_title: metaTitle, meta_description: metaDescription, analytics_id: analyticsId, sitemap_enabled: sitemapEnabled }); }} className="max-w-lg space-y-4">
            <h2 className="text-lg font-semibold text-gray-900">SEO</h2>
            {saved && <p className="text-sm text-green-600">Saved.</p>}
            <div>
                <label className="block text-sm font-medium text-gray-700 mb-1">Default Meta Title</label>
                <input className="block w-full rounded border border-gray-300 px-3 py-2 text-sm focus:border-blue-500 focus:outline-none" value={metaTitle} onChange={e => setMetaTitle(e.target.value)} />
            </div>
            <div>
                <label className="block text-sm font-medium text-gray-700 mb-1">Default Meta Description</label>
                <textarea rows={3} className="block w-full rounded border border-gray-300 px-3 py-2 text-sm focus:border-blue-500 focus:outline-none" value={metaDescription} onChange={e => setMetaDescription(e.target.value)} />
            </div>
            <div>
                <label className="block text-sm font-medium text-gray-700 mb-1">Analytics ID (GA4 / GTM)</label>
                <input className="block w-full rounded border border-gray-300 px-3 py-2 text-sm font-mono focus:border-blue-500 focus:outline-none" placeholder="G-XXXXXXXXXX" value={analyticsId} onChange={e => setAnalyticsId(e.target.value)} />
            </div>
            <label className="flex items-center gap-2 text-sm text-gray-700">
                <input type="checkbox" checked={sitemapEnabled} onChange={e => setSitemapEnabled(e.target.checked)} />
                Generate XML sitemap
            </label>
            <button type="submit" disabled={save.isPending} className="rounded bg-blue-600 px-5 py-2 text-sm font-medium text-white hover:bg-blue-700 disabled:opacity-50">
                {save.isPending ? 'Saving…' : 'Save'}
            </button>
        </form>
    );
}
