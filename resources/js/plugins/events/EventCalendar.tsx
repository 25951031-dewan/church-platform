import React, { useState } from 'react';

interface CalEvent { id: number; title: string; start_at: string; category: string }
interface Props { events: CalEvent[]; onEventClick: (id: number) => void }

const DAYS = ['Sun', 'Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat'];
const CATEGORY_COLORS: Record<string, string> = {
    worship: '#7c3aed', youth: '#059669', outreach: '#d97706',
    study: '#2563eb', fellowship: '#db2777', other: '#64748b',
};

export default function EventCalendar({ events, onEventClick }: Props) {
    const [current, setCurrent] = useState(new Date());
    const year = current.getFullYear();
    const month = current.getMonth();

    const firstDay = new Date(year, month, 1).getDay();
    const daysInMonth = new Date(year, month + 1, 0).getDate();
    const cells = Array.from({ length: firstDay + daysInMonth }, (_, i) =>
        i < firstDay ? null : i - firstDay + 1
    );

    const byDate = events.reduce<Record<string, CalEvent[]>>((acc, e) => {
        const d = new Date(e.start_at).getDate();
        (acc[d] = acc[d] ?? []).push(e);
        return acc;
    }, {});

    return (
        <div>
            <div style={{ display: 'flex', justifyContent: 'space-between', alignItems: 'center', marginBottom: 8 }}>
                <button onClick={() => setCurrent(new Date(year, month - 1, 1))} style={{ background: 'none', border: 'none', cursor: 'pointer', fontSize: '1.2rem' }}>‹</button>
                <strong>{current.toLocaleString('default', { month: 'long', year: 'numeric' })}</strong>
                <button onClick={() => setCurrent(new Date(year, month + 1, 1))} style={{ background: 'none', border: 'none', cursor: 'pointer', fontSize: '1.2rem' }}>›</button>
            </div>
            <div style={{ display: 'grid', gridTemplateColumns: 'repeat(7, 1fr)', gap: 2, fontSize: '0.75rem' }}>
                {DAYS.map(d => <div key={d} style={{ textAlign: 'center', fontWeight: 600, color: '#94a3b8', padding: '4px 0' }}>{d}</div>)}
                {cells.map((day, i) => (
                    <div key={i} style={{ minHeight: 64, background: day ? '#fff' : 'transparent', borderRadius: 6, padding: 4, border: '1px solid #f1f5f9' }}>
                        {day && <>
                            <div style={{ color: '#64748b', marginBottom: 2 }}>{day}</div>
                            {(byDate[day] ?? []).slice(0, 2).map(e => (
                                <div key={e.id} onClick={() => onEventClick(e.id)}
                                    style={{ background: CATEGORY_COLORS[e.category] ?? '#64748b', color: '#fff', borderRadius: 3, padding: '1px 4px', marginBottom: 1, overflow: 'hidden', textOverflow: 'ellipsis', whiteSpace: 'nowrap', cursor: 'pointer', fontSize: '0.7rem' }}>
                                    {e.title}
                                </div>
                            ))}
                        </>}
                    </div>
                ))}
            </div>
        </div>
    );
}
