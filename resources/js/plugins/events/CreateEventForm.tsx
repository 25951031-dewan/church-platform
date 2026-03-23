import React, { useState } from 'react';

interface FormData { title: string; description: string; category: string; start_at: string; end_at: string; is_recurring: boolean; is_online: boolean; meeting_url: string; location: string; max_attendees: string }
const EMPTY: FormData = { title: '', description: '', category: 'worship', start_at: '', end_at: '', is_recurring: false, is_online: false, meeting_url: '', location: '', max_attendees: '' };

export default function CreateEventForm({ onCreated }: { onCreated: () => void }) {
    const [step, setStep] = useState(1);
    const [form, setForm] = useState<FormData>(EMPTY);
    const [submitting, setSubmitting] = useState(false);
    const [error, setError] = useState('');

    const update = (k: keyof FormData, v: any) => setForm(f => ({ ...f, [k]: v }));

    async function submit() {
        setSubmitting(true);
        try {
            const res = await fetch('/api/v1/events', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ ...form, max_attendees: form.max_attendees ? Number(form.max_attendees) : null }),
            });
            if (!res.ok) throw new Error((await res.json()).message ?? 'Failed');
            onCreated();
        } catch (e: any) {
            setError(e.message);
        } finally {
            setSubmitting(false);
        }
    }

    const stepLabel = ['Details', 'Schedule', 'Location', 'Publish'];

    return (
        <div style={{ maxWidth: 500, margin: '0 auto', padding: '1rem' }}>
            <div style={{ display: 'flex', gap: 8, marginBottom: '1.5rem' }}>
                {stepLabel.map((l, i) => (
                    <div key={i} style={{ flex: 1, textAlign: 'center', fontSize: '0.75rem', fontWeight: step === i + 1 ? 700 : 400, color: step === i + 1 ? '#2563eb' : '#94a3b8', borderBottom: step === i + 1 ? '2px solid #2563eb' : '2px solid #e2e8f0', paddingBottom: 4 }}>
                        {l}
                    </div>
                ))}
            </div>

            {step === 1 && (
                <div style={{ display: 'flex', flexDirection: 'column', gap: 12 }}>
                    <input placeholder="Event title *" value={form.title} onChange={e => update('title', e.target.value)}
                        style={{ border: '1px solid #e2e8f0', borderRadius: 8, padding: '0.6rem', fontSize: '0.9rem' }} />
                    <textarea placeholder="Description" rows={4} value={form.description} onChange={e => update('description', e.target.value)}
                        style={{ border: '1px solid #e2e8f0', borderRadius: 8, padding: '0.6rem', fontSize: '0.9rem', resize: 'vertical' }} />
                    <select value={form.category} onChange={e => update('category', e.target.value)}
                        style={{ border: '1px solid #e2e8f0', borderRadius: 8, padding: '0.6rem', fontSize: '0.9rem' }}>
                        {['worship', 'youth', 'outreach', 'study', 'fellowship', 'other'].map(c => <option key={c} value={c}>{c.charAt(0).toUpperCase() + c.slice(1)}</option>)}
                    </select>
                </div>
            )}

            {step === 2 && (
                <div style={{ display: 'flex', flexDirection: 'column', gap: 12 }}>
                    <label style={{ fontSize: '0.875rem', color: '#64748b' }}>Start *</label>
                    <input type="datetime-local" value={form.start_at} onChange={e => update('start_at', e.target.value)}
                        style={{ border: '1px solid #e2e8f0', borderRadius: 8, padding: '0.6rem' }} />
                    <label style={{ fontSize: '0.875rem', color: '#64748b' }}>End *</label>
                    <input type="datetime-local" value={form.end_at} onChange={e => update('end_at', e.target.value)}
                        style={{ border: '1px solid #e2e8f0', borderRadius: 8, padding: '0.6rem' }} />
                    <label style={{ display: 'flex', alignItems: 'center', gap: 8, fontSize: '0.875rem', cursor: 'pointer' }}>
                        <input type="checkbox" checked={form.is_recurring} onChange={e => update('is_recurring', e.target.checked)} />
                        Recurring event
                    </label>
                </div>
            )}

            {step === 3 && (
                <div style={{ display: 'flex', flexDirection: 'column', gap: 12 }}>
                    <label style={{ display: 'flex', alignItems: 'center', gap: 8, cursor: 'pointer' }}>
                        <input type="checkbox" checked={form.is_online} onChange={e => update('is_online', e.target.checked)} />
                        Online event
                    </label>
                    {form.is_online
                        ? <input placeholder="Meeting URL (Zoom, Meet…)" value={form.meeting_url} onChange={e => update('meeting_url', e.target.value)}
                            style={{ border: '1px solid #e2e8f0', borderRadius: 8, padding: '0.6rem' }} />
                        : <input placeholder="Location address" value={form.location} onChange={e => update('location', e.target.value)}
                            style={{ border: '1px solid #e2e8f0', borderRadius: 8, padding: '0.6rem' }} />
                    }
                </div>
            )}

            {step === 4 && (
                <div style={{ display: 'flex', flexDirection: 'column', gap: 12 }}>
                    <input type="number" placeholder="Max attendees (optional)" value={form.max_attendees} onChange={e => update('max_attendees', e.target.value)}
                        style={{ border: '1px solid #e2e8f0', borderRadius: 8, padding: '0.6rem' }} />
                    <div style={{ background: '#f8fafc', borderRadius: 8, padding: '0.75rem', fontSize: '0.875rem' }}>
                        <strong>{form.title || '(no title)'}</strong><br />
                        <span style={{ color: '#64748b' }}>{form.start_at || 'No date set'} — {form.category}</span>
                    </div>
                    {error && <div style={{ color: '#dc2626', fontSize: '0.875rem' }}>{error}</div>}
                </div>
            )}

            <div style={{ display: 'flex', justifyContent: 'space-between', marginTop: '1.5rem' }}>
                {step > 1 && <button onClick={() => setStep(s => s - 1)} style={{ padding: '8px 20px', borderRadius: 8, border: '1px solid #e2e8f0', background: '#fff', cursor: 'pointer' }}>Back</button>}
                {step < 4
                    ? <button onClick={() => setStep(s => s + 1)} style={{ marginLeft: 'auto', padding: '8px 20px', borderRadius: 8, border: 'none', background: '#2563eb', color: '#fff', cursor: 'pointer' }}>Next</button>
                    : <button onClick={submit} disabled={submitting} style={{ marginLeft: 'auto', padding: '8px 20px', borderRadius: 8, border: 'none', background: '#2563eb', color: '#fff', cursor: 'pointer' }}>
                        {submitting ? 'Creating…' : 'Publish Event'}
                      </button>
                }
            </div>
        </div>
    );
}
