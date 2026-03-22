import { useMutation, useQuery, useQueryClient } from '@tanstack/react-query';
import axios from 'axios';
import { useState, useEffect } from 'react';

export default function GeneralSettings() {
    const queryClient = useQueryClient();
    const { data = {} } = useQuery({
        queryKey: ['admin-settings-general'],
        queryFn:  () => axios.get('/api/v1/admin/settings/general').then(r => r.data),
    });

    const [churchName, setChurchName] = useState('');
    const [tagline,    setTagline]    = useState('');
    const [contact,    setContact]    = useState('');
    const [saved,      setSaved]      = useState(false);

    useEffect(() => {
        setChurchName(data.church_name ?? '');
        setTagline(data.tagline ?? '');
        setContact(data.contact_email ?? '');
    }, [data]);

    const save = useMutation({
        mutationFn: (d) => axios.patch('/api/v1/admin/settings/general', d),
        onSuccess:  () => { queryClient.invalidateQueries({ queryKey: ['admin-settings-general'] }); setSaved(true); setTimeout(() => setSaved(false), 2500); },
    });

    return (
        <form onSubmit={e => { e.preventDefault(); save.mutate({ church_name: churchName, tagline, contact_email: contact }); }} className="max-w-lg space-y-4">
            <h2 className="text-lg font-semibold text-gray-900">General</h2>
            {saved && <p className="text-sm text-green-600">Saved.</p>}
            <div>
                <label className="block text-sm font-medium text-gray-700 mb-1">Church Name</label>
                <input className="block w-full rounded border border-gray-300 px-3 py-2 text-sm focus:border-blue-500 focus:outline-none" value={churchName} onChange={e => setChurchName(e.target.value)} />
            </div>
            <div>
                <label className="block text-sm font-medium text-gray-700 mb-1">Tagline</label>
                <input className="block w-full rounded border border-gray-300 px-3 py-2 text-sm focus:border-blue-500 focus:outline-none" value={tagline} onChange={e => setTagline(e.target.value)} />
            </div>
            <div>
                <label className="block text-sm font-medium text-gray-700 mb-1">Contact Email</label>
                <input type="email" className="block w-full rounded border border-gray-300 px-3 py-2 text-sm focus:border-blue-500 focus:outline-none" value={contact} onChange={e => setContact(e.target.value)} />
            </div>
            <button type="submit" disabled={save.isPending} className="rounded bg-blue-600 px-5 py-2 text-sm font-medium text-white hover:bg-blue-700 disabled:opacity-50">
                {save.isPending ? 'Saving…' : 'Save'}
            </button>
        </form>
    );
}
