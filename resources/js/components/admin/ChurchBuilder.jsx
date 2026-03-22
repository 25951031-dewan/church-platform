import { useQuery } from '@tanstack/react-query';
import axios from 'axios';
import { useState } from 'react';
import { useParams, useSearchParams } from 'react-router-dom';
import PagesManager from './PagesManager';

// ─── Tab definitions ──────────────────────────────────────────────────────────

const TABS = [
    { key: 'general',      label: 'General' },
    { key: 'appearance',   label: 'Appearance' },
    { key: 'social',       label: 'Social & Links' },
    { key: 'members',      label: 'Members' },
    { key: 'pages',        label: 'Custom Pages' },  // Phase 3.2 addition
];

// ─── Tab panels ───────────────────────────────────────────────────────────────

function GeneralTab({ church, onSave }) {
    const [name,        setName]        = useState(church?.name ?? '');
    const [description, setDescription] = useState(church?.description ?? '');
    const [email,       setEmail]       = useState(church?.email ?? '');
    const [phone,       setPhone]       = useState(church?.phone ?? '');
    const [website,     setWebsite]     = useState(church?.website ?? '');

    return (
        <form
            onSubmit={e => { e.preventDefault(); onSave({ name, description, email, phone, website }); }}
            className="space-y-4 max-w-lg"
        >
            <div>
                <label className="block text-sm font-medium text-gray-700 mb-1">Church Name *</label>
                <input
                    className="block w-full rounded border border-gray-300 px-3 py-2 text-sm focus:border-blue-500 focus:outline-none"
                    value={name}
                    onChange={e => setName(e.target.value)}
                    required
                />
            </div>
            <div>
                <label className="block text-sm font-medium text-gray-700 mb-1">Description</label>
                <textarea
                    rows={4}
                    className="block w-full rounded border border-gray-300 px-3 py-2 text-sm focus:border-blue-500 focus:outline-none"
                    value={description}
                    onChange={e => setDescription(e.target.value)}
                />
            </div>
            <div className="grid grid-cols-2 gap-4">
                <div>
                    <label className="block text-sm font-medium text-gray-700 mb-1">Email</label>
                    <input type="email" className="block w-full rounded border border-gray-300 px-3 py-2 text-sm focus:border-blue-500 focus:outline-none" value={email} onChange={e => setEmail(e.target.value)} />
                </div>
                <div>
                    <label className="block text-sm font-medium text-gray-700 mb-1">Phone</label>
                    <input type="tel" className="block w-full rounded border border-gray-300 px-3 py-2 text-sm focus:border-blue-500 focus:outline-none" value={phone} onChange={e => setPhone(e.target.value)} />
                </div>
            </div>
            <div>
                <label className="block text-sm font-medium text-gray-700 mb-1">Website</label>
                <input type="url" className="block w-full rounded border border-gray-300 px-3 py-2 text-sm focus:border-blue-500 focus:outline-none" value={website} onChange={e => setWebsite(e.target.value)} />
            </div>
            <button type="submit" className="rounded bg-blue-600 px-5 py-2 text-sm font-medium text-white hover:bg-blue-700">
                Save Changes
            </button>
        </form>
    );
}

function AppearanceTab({ church, onSave }) {
    return (
        <div className="max-w-lg space-y-4">
            <div>
                <label className="block text-sm font-medium text-gray-700 mb-1">Logo</label>
                <p className="text-sm text-gray-400">Current: {church?.logo ?? 'None'}</p>
                <input type="file" accept="image/*" className="mt-1 block text-sm text-gray-500" />
            </div>
            <div>
                <label className="block text-sm font-medium text-gray-700 mb-1">Cover Image</label>
                <p className="text-sm text-gray-400">Current: {church?.cover_image ?? 'None'}</p>
                <input type="file" accept="image/*" className="mt-1 block text-sm text-gray-500" />
            </div>
            <p className="text-xs text-gray-400">Media upload wired up via Spatie Media Library in the API layer.</p>
        </div>
    );
}

function SocialTab({ church, onSave }) {
    const links = church?.social_links ?? {};
    const [facebook,  setFacebook]  = useState(links.facebook ?? '');
    const [instagram, setInstagram] = useState(links.instagram ?? '');
    const [youtube,   setYoutube]   = useState(links.youtube ?? '');
    const [twitter,   setTwitter]   = useState(links.twitter ?? '');

    return (
        <form
            onSubmit={e => { e.preventDefault(); onSave({ social_links: { facebook, instagram, youtube, twitter } }); }}
            className="space-y-4 max-w-lg"
        >
            {[
                ['Facebook',  facebook,  setFacebook],
                ['Instagram', instagram, setInstagram],
                ['YouTube',   youtube,   setYoutube],
                ['X / Twitter', twitter, setTwitter],
            ].map(([label, val, setter]) => (
                <div key={label}>
                    <label className="block text-sm font-medium text-gray-700 mb-1">{label}</label>
                    <input type="url" className="block w-full rounded border border-gray-300 px-3 py-2 text-sm focus:border-blue-500 focus:outline-none" value={val} onChange={e => setter(e.target.value)} placeholder="https://" />
                </div>
            ))}
            <button type="submit" className="rounded bg-blue-600 px-5 py-2 text-sm font-medium text-white hover:bg-blue-700">
                Save Links
            </button>
        </form>
    );
}

function MembersTab({ churchId }) {
    const { data, isLoading } = useQuery({
        queryKey: ['church-members', churchId],
        queryFn:  () => axios.get(`/api/v1/churches/${churchId}/members`).then(r => r.data),
        enabled:  !!churchId,
    });

    if (isLoading) return <p className="text-sm text-gray-400">Loading members…</p>;

    const members = data?.data ?? [];

    return (
        <div>
            <p className="mb-4 text-sm text-gray-500">{members.length} members</p>
            <div className="divide-y divide-gray-100 rounded-lg border border-gray-200 bg-white">
                {members.map(m => (
                    <div key={m.id} className="flex items-center gap-3 px-4 py-3">
                        <div className="h-8 w-8 rounded-full bg-gray-200 text-center text-sm leading-8 text-gray-600">
                            {m.user?.name?.[0] ?? '?'}
                        </div>
                        <span className="text-sm text-gray-900">{m.user?.name}</span>
                        <span className="ml-auto rounded-full bg-gray-100 px-2 py-0.5 text-xs text-gray-500 capitalize">{m.role}</span>
                    </div>
                ))}
            </div>
        </div>
    );
}

// ─── ChurchBuilder ────────────────────────────────────────────────────────────

export default function ChurchBuilder() {
    const { churchId }             = useParams();
    const [searchParams, setSearchParams] = useSearchParams();
    const activeTab = searchParams.get('tab') ?? 'general';

    const { data: church, isLoading } = useQuery({
        queryKey: ['admin-church', churchId],
        queryFn:  () => axios.get(`/api/v1/churches/${churchId}`).then(r => r.data),
        enabled:  !!churchId,
    });

    const { data: saveResult, mutate: save } = {
        data: null,
        mutate: (data) => axios.patch(`/api/v1/admin/churches/${churchId}`, data),
    };

    function setTab(key) {
        setSearchParams({ tab: key });
    }

    if (isLoading) {
        return <div className="flex items-center justify-center py-24 text-gray-400">Loading…</div>;
    }

    return (
        <div className="mx-auto max-w-4xl px-4 py-8 sm:px-6">
            <div className="mb-6">
                <h1 className="text-xl font-bold text-gray-900">{church?.name ?? 'Church Builder'}</h1>
                <p className="text-sm text-gray-500">Manage your church page and content.</p>
            </div>

            {/* Tabs */}
            <div className="mb-8 flex gap-1 overflow-x-auto rounded-lg border border-gray-200 bg-gray-50 p-1 w-fit">
                {TABS.map(tab => (
                    <button
                        key={tab.key}
                        onClick={() => setTab(tab.key)}
                        className={[
                            'whitespace-nowrap rounded px-4 py-1.5 text-sm font-medium transition',
                            activeTab === tab.key
                                ? 'bg-white text-gray-900 shadow'
                                : 'text-gray-500 hover:text-gray-700',
                        ].join(' ')}
                    >
                        {tab.label}
                    </button>
                ))}
            </div>

            {/* Tab content */}
            {activeTab === 'general'    && <GeneralTab    church={church} onSave={save} />}
            {activeTab === 'appearance' && <AppearanceTab church={church} onSave={save} />}
            {activeTab === 'social'     && <SocialTab     church={church} onSave={save} />}
            {activeTab === 'members'    && <MembersTab    churchId={Number(churchId)} />}
            {activeTab === 'pages'      && <PagesManager  churchId={Number(churchId)} />}
        </div>
    );
}
