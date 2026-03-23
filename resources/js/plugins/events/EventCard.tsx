import React from 'react';

interface EventItem {
    id: number; title: string; start_at: string; end_at: string; is_multi_day: boolean;
    location?: string; is_online: boolean; category: string;
    going_count: number; cover_image?: string;
    user_rsvp?: 'going' | 'maybe' | 'not_going' | null;
}

interface Props { event: EventItem; onRsvp?: (id: number, status: string) => void }

export default function EventCard({ event, onRsvp }: Props) {
    const start = new Date(event.start_at);
    const dateStr = start.toLocaleDateString('en-US', { weekday: 'short', month: 'short', day: 'numeric' });
    const timeStr = start.toLocaleTimeString('en-US', { hour: 'numeric', minute: '2-digit' });

    return (
        <div style={{ background: '#fff', borderRadius: 12, overflow: 'hidden', boxShadow: '0 1px 4px rgba(0,0,0,.08)', marginBottom: '1rem' }}>
            {event.cover_image && (
                <img src={event.cover_image} alt={event.title}
                    style={{ width: '100%', height: 140, objectFit: 'cover' }} />
            )}
            <div style={{ padding: '0.75rem 1rem' }}>
                <div style={{ fontSize: '0.75rem', color: '#64748b', marginBottom: 4 }}>
                    {dateStr} · {timeStr}
                    {event.is_online && <span style={{ marginLeft: 8, background: '#dbeafe', color: '#1d4ed8', borderRadius: 4, padding: '1px 6px' }}>Online</span>}
                </div>
                <div style={{ fontWeight: 700, fontSize: '1rem', marginBottom: 4 }}>{event.title}</div>
                {event.location && <div style={{ fontSize: '0.8rem', color: '#94a3b8' }}>📍 {event.location}</div>}
                <div style={{ marginTop: 8, display: 'flex', gap: 8 }}>
                    {(['going', 'maybe'] as const).map(s => (
                        <button key={s} onClick={() => onRsvp?.(event.id, s)}
                            style={{
                                fontSize: '0.8rem', borderRadius: 20, padding: '4px 14px', cursor: 'pointer', border: 'none',
                                background: event.user_rsvp === s ? '#2563eb' : '#f1f5f9',
                                color: event.user_rsvp === s ? '#fff' : '#475569',
                            }}>
                            {s === 'going' ? `✓ Going (${event.going_count})` : 'Maybe'}
                        </button>
                    ))}
                </div>
            </div>
        </div>
    );
}
