import React, { useEffect, useState } from 'react';
import axios from 'axios';

interface Profile { id: number; name: string; avatar?: string; cover_image?: string; bio?: string; location?: string; website?: string }

export default function ProfilePage({ userId }: { userId: number }) {
    const [profile, setProfile] = useState<Profile | null>(null);

    useEffect(() => {
        axios.get(`/api/v1/users/${userId}`).then(r => setProfile(r.data));
    }, [userId]);

    if (!profile) return <p style={{ textAlign: 'center', padding: '2rem', color: '#94a3b8' }}>Loading…</p>;

    return (
        <div style={{ maxWidth: 640, margin: '0 auto' }}>
            <div style={{ height: 160, borderRadius: '0 0 12px 12px', background: profile.cover_image ? `url(${profile.cover_image}) center/cover` : '#2563eb' }} />
            <div style={{ padding: '0 1rem' }}>
                <img src={profile.avatar ?? `https://ui-avatars.com/api/?name=${encodeURIComponent(profile.name)}&size=80`}
                    style={{ width: 80, height: 80, borderRadius: '50%', border: '3px solid #fff', marginTop: -40, objectFit: 'cover' }} alt="" />
                <h1 style={{ fontSize: '1.25rem', fontWeight: 700, marginTop: '0.5rem' }}>{profile.name}</h1>
                {profile.bio      && <p style={{ color: '#475569', margin: '0.25rem 0' }}>{profile.bio}</p>}
                {profile.location && <p style={{ fontSize: '0.875rem', color: '#64748b' }}>📍 {profile.location}</p>}
                {profile.website  && <a href={profile.website} style={{ color: '#2563eb', fontSize: '0.875rem' }}>{profile.website}</a>}
            </div>
        </div>
    );
}
