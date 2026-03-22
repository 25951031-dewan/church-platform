import React, { useEffect, useState } from 'react';
import axios from 'axios';

interface Community { id: number; name: string; description?: string; members_count: number; privacy: string; cover_image?: string }

export default function CommunityPage() {
    const [items, setItems]   = useState<Community[]>([]);
    const [search, setSearch] = useState('');

    useEffect(() => {
        axios.get('/api/v1/communities', { params: { search } }).then(r => setItems(r.data.data ?? []));
    }, [search]);

    const join = async (id: number) => {
        await axios.post(`/api/v1/communities/${id}/join`);
        setItems(cs => cs.map(c => c.id === id ? { ...c, members_count: c.members_count + 1 } : c));
    };

    return (
        <div style={{ maxWidth: 800, margin: '0 auto', padding: '1rem' }}>
            <h1 style={{ fontSize: '1.25rem', fontWeight: 700, marginBottom: '1rem' }}>Communities</h1>
            <input value={search} onChange={e => setSearch(e.target.value)} placeholder="Search…"
                style={{ width: '100%', padding: '0.6rem 1rem', border: '1px solid #e2e8f0', borderRadius: 8, marginBottom: '1rem', boxSizing: 'border-box' }} />
            <div style={{ display: 'grid', gridTemplateColumns: 'repeat(auto-fill,minmax(240px,1fr))', gap: '1rem' }}>
                {items.map(c => (
                    <div key={c.id} style={{ background: '#fff', borderRadius: 12, overflow: 'hidden', boxShadow: '0 1px 4px rgba(0,0,0,.08)' }}>
                        <div style={{ height: 80, background: c.cover_image ? `url(${c.cover_image}) center/cover` : '#2563eb' }} />
                        <div style={{ padding: '0.75rem' }}>
                            <div style={{ fontWeight: 600 }}>{c.name}</div>
                            <div style={{ fontSize: '0.8rem', color: '#64748b', marginBottom: '0.4rem' }}>{c.members_count} members · {c.privacy}</div>
                            {c.description && <p style={{ fontSize: '0.8rem', color: '#475569', marginBottom: '0.5rem' }}>{c.description}</p>}
                            <button onClick={() => join(c.id)}
                                style={{ width: '100%', background: '#2563eb', color: '#fff', border: 'none', borderRadius: 8, padding: '0.4rem', cursor: 'pointer' }}>
                                Join
                            </button>
                        </div>
                    </div>
                ))}
            </div>
        </div>
    );
}
