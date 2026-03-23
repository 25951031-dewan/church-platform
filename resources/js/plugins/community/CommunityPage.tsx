import React, { useEffect, useState } from 'react';
import { useNavigate } from 'react-router-dom';
import axios from 'axios';

interface Community {
    id: number; name: string; description?: string;
    members_count: number; privacy: string; privacy_closed: boolean; cover_image?: string;
    my_status?: 'approved' | 'pending' | null;
}

export default function CommunityPage() {
    const navigate    = useNavigate();
    const [items, setItems]   = useState<Community[]>([]);
    const [search, setSearch] = useState('');

    useEffect(() => {
        axios.get('/api/v1/communities', { params: { search } }).then(r => setItems(r.data.data ?? []));
    }, [search]);

    const handleJoin = async (c: Community) => {
        if (c.my_status === 'approved') {
            await axios.delete(`/api/v1/communities/${c.id}/leave`);
            setItems(cs => cs.map(x => x.id === c.id ? { ...x, my_status: null, members_count: x.members_count - 1 } : x));
        } else if (!c.my_status) {
            const res = await axios.post(`/api/v1/communities/${c.id}/join`);
            const newStatus = res.data.status as 'approved' | 'pending';
            setItems(cs => cs.map(x => x.id === c.id ? {
                ...x, my_status: newStatus,
                members_count: newStatus === 'approved' ? x.members_count + 1 : x.members_count,
            } : x));
        }
    };

    const joinLabel = (c: Community) => {
        if (c.my_status === 'approved') return 'Leave';
        if (c.my_status === 'pending')  return 'Pending…';
        return c.privacy_closed ? 'Request to Join' : 'Join';
    };

    const joinStyle = (c: Community): React.CSSProperties => ({
        width: '100%', border: 'none', borderRadius: 8, padding: '0.4rem',
        cursor: c.my_status === 'pending' ? 'default' : 'pointer',
        background: c.my_status === 'approved' ? '#f1f5f9' : c.my_status === 'pending' ? '#fef3c7' : '#2563eb',
        color: c.my_status === 'approved' ? '#475569' : c.my_status === 'pending' ? '#92400e' : '#fff',
    });

    return (
        <div style={{ maxWidth: 800, margin: '0 auto', padding: '1rem' }}>
            <h1 style={{ fontSize: '1.25rem', fontWeight: 700, marginBottom: '1rem' }}>Communities</h1>
            <input value={search} onChange={e => setSearch(e.target.value)} placeholder="Search…"
                style={{ width: '100%', padding: '0.6rem 1rem', border: '1px solid #e2e8f0', borderRadius: 8, marginBottom: '1rem', boxSizing: 'border-box' }} />
            <div style={{ display: 'grid', gridTemplateColumns: 'repeat(auto-fill,minmax(240px,1fr))', gap: '1rem' }}>
                {items.map(c => (
                    <div key={c.id} style={{ background: '#fff', borderRadius: 12, overflow: 'hidden', boxShadow: '0 1px 4px rgba(0,0,0,.08)' }}>
                        <div onClick={() => navigate(`/communities/${c.id}`)} style={{ cursor: 'pointer' }}>
                            <div style={{ height: 80, background: c.cover_image ? `url(${c.cover_image}) center/cover` : '#2563eb' }} />
                            <div style={{ padding: '0.75rem 0.75rem 0' }}>
                                <div style={{ fontWeight: 600 }}>{c.name}</div>
                                <div style={{ fontSize: '0.8rem', color: '#64748b', marginBottom: '0.4rem' }}>
                                    {c.members_count} members · {c.privacy_closed ? 'Closed' : c.privacy}
                                </div>
                                {c.description && <p style={{ fontSize: '0.8rem', color: '#475569', marginBottom: '0.5rem' }}>{c.description}</p>}
                            </div>
                        </div>
                        <div style={{ padding: '0 0.75rem 0.75rem' }}>
                            <button
                                onClick={() => c.my_status !== 'pending' && handleJoin(c)}
                                style={joinStyle(c)}
                                disabled={c.my_status === 'pending'}
                            >
                                {joinLabel(c)}
                            </button>
                        </div>
                    </div>
                ))}
            </div>
        </div>
    );
}
