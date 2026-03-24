import React, { useEffect, useState } from 'react';
import axios from 'axios';
import EventCard from './EventCard';
import EventCalendar from './EventCalendar';

const CATEGORIES = ['All', 'Worship', 'Youth', 'Outreach', 'Study', 'Fellowship'];

interface EventItem { id: number; title: string; start_at: string; end_at: string; is_multi_day: boolean; location?: string; is_online: boolean; category: string; going_count: number; cover_image?: string; user_rsvp?: string | null }

export default function EventsPage() {
    const [events, setEvents] = useState<EventItem[]>([]);
    const [view, setView] = useState<'list' | 'calendar'>('list');
    const [category, setCategory] = useState('All');
    const [loading, setLoading] = useState(true);

    useEffect(() => {
        const params = new URLSearchParams({ scope: 'upcoming' });
        if (category !== 'All') params.set('category', category.toLowerCase());
        axios.get(`/api/v1/events?${params}`)
            .then(r => { setEvents(r.data.data ?? r.data); setLoading(false); })
            .catch(() => setLoading(false));
    }, [category]);

    async function rsvp(eventId: number, status: string) {
        await axios.post(`/api/v1/events/${eventId}/rsvp`, { status });
        setEvents(prev => prev.map(e => e.id === eventId ? { ...e, user_rsvp: status, going_count: status === 'going' ? e.going_count + 1 : e.going_count } : e));
    }

    return (
        <div style={{ maxWidth: 680, margin: '0 auto', padding: '1rem' }}>
            <div style={{ display: 'flex', justifyContent: 'space-between', alignItems: 'center', marginBottom: '1rem' }}>
                <h1 style={{ fontSize: '1.25rem', fontWeight: 700, margin: 0 }}>Events</h1>
                <div style={{ display: 'flex', gap: 8 }}>
                    {(['list', 'calendar'] as const).map(v => (
                        <button key={v} onClick={() => setView(v)}
                            style={{ fontSize: '0.8rem', padding: '4px 12px', borderRadius: 20, border: 'none', cursor: 'pointer', background: view === v ? '#2563eb' : '#f1f5f9', color: view === v ? '#fff' : '#475569' }}>
                            {v === 'list' ? '☰ List' : '◫ Calendar'}
                        </button>
                    ))}
                </div>
            </div>
            <div style={{ display: 'flex', gap: 8, overflowX: 'auto', marginBottom: '1rem', paddingBottom: 4 }}>
                {CATEGORIES.map(c => (
                    <button key={c} onClick={() => setCategory(c)}
                        style={{ fontSize: '0.8rem', padding: '4px 14px', borderRadius: 20, whiteSpace: 'nowrap', border: 'none', cursor: 'pointer', background: category === c ? '#2563eb' : '#f1f5f9', color: category === c ? '#fff' : '#475569' }}>
                        {c}
                    </button>
                ))}
            </div>
            {loading ? <div style={{ color: '#94a3b8' }}>Loading events…</div> : (
                view === 'calendar'
                    ? <EventCalendar events={events} onEventClick={id => window.location.href = `/events/${id}`} />
                    : events.map(e => <EventCard key={e.id} event={e} onRsvp={rsvp} />)
            )}
        </div>
    );
}
