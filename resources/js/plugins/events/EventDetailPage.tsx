import React, { useEffect, useState } from 'react';
import axios from 'axios';

interface EventDetail { id: number; title: string; description: string | null; start_at: string; end_at: string; is_online: boolean; meeting_url?: string | null; location?: string | null; cover_image?: string | null; going_count: number; maybe_count: number; user_rsvp?: string | null }

export default function EventDetailPage({ eventId }: { eventId: number }) {
    const [event, setEvent] = useState<EventDetail | null>(null);

    useEffect(() => {
        axios.get(`/api/v1/events/${eventId}`).then(r => setEvent(r.data)).catch(() => {});
    }, [eventId]);

    async function rsvp(status: string) {
        await axios.post(`/api/v1/events/${eventId}/rsvp`, { status });
        setEvent(prev => prev ? { ...prev, user_rsvp: status } : prev);
    }

    if (!event) return <div style={{ padding: '2rem', color: '#94a3b8' }}>Loading…</div>;

    return (
        <div style={{ maxWidth: 680, margin: '0 auto', padding: '1rem' }}>
            {event.cover_image && <img src={event.cover_image} alt={event.title} style={{ width: '100%', borderRadius: 12, marginBottom: '1rem', maxHeight: 240, objectFit: 'cover' }} />}
            <h1 style={{ fontSize: '1.4rem', fontWeight: 700, marginBottom: 4 }}>{event.title}</h1>
            <div style={{ color: '#64748b', fontSize: '0.875rem', marginBottom: 12 }}>
                {new Date(event.start_at).toLocaleString()} {event.is_online ? '· Online' : event.location ? `· ${event.location}` : ''}
            </div>

            <div style={{ display: 'flex', gap: 8, marginBottom: '1rem' }}>
                {(['going', 'maybe', 'not_going'] as const).map(s => (
                    <button key={s} onClick={() => rsvp(s)}
                        style={{ fontSize: '0.875rem', padding: '6px 16px', borderRadius: 20, border: 'none', cursor: 'pointer', background: event.user_rsvp === s ? '#2563eb' : '#f1f5f9', color: event.user_rsvp === s ? '#fff' : '#475569' }}>
                        {s === 'going' ? `Going (${event.going_count})` : s === 'maybe' ? `Maybe (${event.maybe_count})` : 'Not Going'}
                    </button>
                ))}
            </div>

            {event.meeting_url && (
                <a href={event.meeting_url} target="_blank" rel="noopener noreferrer"
                    style={{ display: 'inline-block', background: '#2563eb', color: '#fff', borderRadius: 8, padding: '8px 20px', textDecoration: 'none', marginBottom: '1rem', fontSize: '0.875rem' }}>
                    🎥 Join Meeting
                </a>
            )}

            {event.description && (
                <div style={{ marginBottom: '1.5rem', lineHeight: 1.7 }}>{event.description}</div>
            )}

            <h3 style={{ fontSize: '1rem', fontWeight: 600, marginBottom: 8 }}>Discussion</h3>
            <EventDiscussion eventId={eventId} />
        </div>
    );
}

function EventDiscussion({ eventId }: { eventId: number }) {
    const [posts, setPosts] = useState<any[]>([]);
    const [body, setBody] = useState('');

    useEffect(() => {
        axios.get(`/api/v1/events/${eventId}/posts`)
            .then(r => setPosts(r.data.data ?? []))
            .catch(() => {});
    }, [eventId]);

    async function post() {
        if (!body.trim()) return;
        try {
            const { data } = await axios.post(`/api/v1/events/${eventId}/posts`, { body });
            setPosts(prev => [data, ...prev]);
            setBody('');
        } catch {}
    }

    return (
        <div>
            <div style={{ display: 'flex', gap: 8, marginBottom: '1rem' }}>
                <input value={body} onChange={e => setBody(e.target.value)} placeholder="Add a comment…"
                    style={{ flex: 1, border: '1px solid #e2e8f0', borderRadius: 8, padding: '0.6rem', fontSize: '0.9rem' }} />
                <button onClick={post} style={{ padding: '0.6rem 1rem', borderRadius: 8, border: 'none', background: '#2563eb', color: '#fff', cursor: 'pointer' }}>Post</button>
            </div>
            {posts.map((p: any) => (
                <div key={p.id} style={{ borderBottom: '1px solid #f1f5f9', paddingBottom: 12, marginBottom: 12 }}>
                    <div style={{ fontWeight: 600, fontSize: '0.875rem' }}>{p.author?.name ?? 'Anonymous'}</div>
                    <div style={{ fontSize: '0.9rem', lineHeight: 1.6 }}>{p.body}</div>
                </div>
            ))}
        </div>
    );
}
