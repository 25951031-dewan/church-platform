import React, { useState } from 'react';

type PostType = 'post' | 'prayer' | 'blessing' | 'poll' | 'bible_study';
const TYPE_LABELS: Record<PostType, string> = { post: '💬 Post', prayer: '🙏 Prayer', blessing: '✨ Blessing', poll: '📊 Poll', bible_study: '📖 Bible Study' };
const BODY_PLACEHOLDERS: Record<PostType, string> = {
    post: "What's on your mind?", prayer: 'What would you like prayer for?',
    blessing: 'Share your testimony…', poll: 'Context (optional)',
    bible_study: 'Reflection (optional)',
};

interface Props { onClose: () => void; onCreated: () => void }

export default function CreatePostModal({ onClose, onCreated }: Props) {
    const [type, setType] = useState<PostType>('post');
    const [body, setBody] = useState('');
    const [scripture, setScripture] = useState('');
    const [passage, setPassage] = useState('');
    const [studyGuide, setStudyGuide] = useState('');
    const [question, setQuestion] = useState('');
    const [options, setOptions] = useState(['', '']);
    const [endsAt, setEndsAt] = useState('');
    const [allowMultiple, setAllowMultiple] = useState(false);
    const [submitting, setSubmitting] = useState(false);
    const [error, setError] = useState('');

    async function submit() {
        setSubmitting(true);
        setError('');
        let meta: Record<string, any> | undefined;
        if (type === 'blessing') meta = { scripture: scripture || undefined };
        if (type === 'poll') meta = { question, options: options.filter(Boolean).map(t => ({ text: t })), ends_at: endsAt || null, allow_multiple: allowMultiple };
        if (type === 'bible_study') meta = { scripture, passage, study_guide: studyGuide || undefined };

        try {
            const res = await fetch('/api/v1/posts', {
                method: 'POST', headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ type, body: body || null, meta }),
            });
            if (!res.ok) throw new Error((await res.json()).message ?? 'Failed');
            onCreated();
            onClose();
        } catch (e: any) {
            setError(e.message);
        } finally {
            setSubmitting(false);
        }
    }

    return (
        <div style={{ position: 'fixed', inset: 0, background: 'rgba(0,0,0,0.4)', zIndex: 1000, display: 'flex', alignItems: 'center', justifyContent: 'center' }}>
            <div style={{ background: '#fff', borderRadius: 16, padding: '1.5rem', width: '100%', maxWidth: 520, maxHeight: '90vh', overflowY: 'auto', boxShadow: '0 8px 32px rgba(0,0,0,.15)' }}>
                <div style={{ display: 'flex', justifyContent: 'space-between', alignItems: 'center', marginBottom: '1rem' }}>
                    <h2 style={{ margin: 0, fontSize: '1.1rem', fontWeight: 700 }}>Create Post</h2>
                    <button onClick={onClose} style={{ background: 'none', border: 'none', fontSize: '1.25rem', cursor: 'pointer', color: '#64748b' }}>✕</button>
                </div>

                {/* Type selector */}
                <div style={{ display: 'flex', gap: 6, flexWrap: 'wrap', marginBottom: '1rem' }}>
                    {(Object.keys(TYPE_LABELS) as PostType[]).map(t => (
                        <button key={t} onClick={() => setType(t)}
                            style={{ fontSize: '0.8rem', padding: '4px 12px', borderRadius: 20, border: 'none', cursor: 'pointer', background: type === t ? '#2563eb' : '#f1f5f9', color: type === t ? '#fff' : '#475569' }}>
                            {TYPE_LABELS[t]}
                        </button>
                    ))}
                </div>

                {/* Shared body */}
                <textarea rows={3} placeholder={BODY_PLACEHOLDERS[type]} value={body} onChange={e => setBody(e.target.value)}
                    style={{ width: '100%', border: '1px solid #e2e8f0', borderRadius: 8, padding: '0.6rem', fontSize: '0.9rem', resize: 'vertical', boxSizing: 'border-box' }} />

                {/* Type-specific fields */}
                {(type === 'blessing' || type === 'bible_study') && (
                    <input placeholder={type === 'bible_study' ? 'Scripture reference *' : 'Scripture reference (optional)'}
                        value={scripture} onChange={e => setScripture(e.target.value)}
                        style={{ marginTop: 8, width: '100%', border: '1px solid #e2e8f0', borderRadius: 8, padding: '0.6rem', boxSizing: 'border-box' }} />
                )}
                {type === 'bible_study' && (
                    <>
                        <textarea rows={3} placeholder="Passage text *" value={passage} onChange={e => setPassage(e.target.value)}
                            style={{ marginTop: 8, width: '100%', border: '1px solid #e2e8f0', borderRadius: 8, padding: '0.6rem', resize: 'vertical', boxSizing: 'border-box' }} />
                        <textarea rows={2} placeholder="Study guide (optional)" value={studyGuide} onChange={e => setStudyGuide(e.target.value)}
                            style={{ marginTop: 8, width: '100%', border: '1px solid #e2e8f0', borderRadius: 8, padding: '0.6rem', resize: 'vertical', boxSizing: 'border-box' }} />
                    </>
                )}
                {type === 'poll' && (
                    <div style={{ marginTop: 8 }}>
                        <input placeholder="Question *" value={question} onChange={e => setQuestion(e.target.value)}
                            style={{ width: '100%', border: '1px solid #e2e8f0', borderRadius: 8, padding: '0.6rem', boxSizing: 'border-box' }} />
                        {options.map((opt, i) => (
                            <div key={i} style={{ display: 'flex', gap: 6, marginTop: 6 }}>
                                <input placeholder={`Option ${i + 1} *`} value={opt} onChange={e => setOptions(o => o.map((v, j) => j === i ? e.target.value : v))}
                                    style={{ flex: 1, border: '1px solid #e2e8f0', borderRadius: 8, padding: '0.6rem' }} />
                                {options.length > 2 && (
                                    <button onClick={() => setOptions(o => o.filter((_, j) => j !== i))}
                                        style={{ border: 'none', background: '#fee2e2', color: '#dc2626', borderRadius: 6, padding: '0 10px', cursor: 'pointer' }}>✕</button>
                                )}
                            </div>
                        ))}
                        {options.length < 10 && (
                            <button onClick={() => setOptions(o => [...o, ''])}
                                style={{ marginTop: 6, fontSize: '0.8rem', background: 'none', border: '1px solid #e2e8f0', borderRadius: 8, padding: '4px 12px', cursor: 'pointer', color: '#2563eb' }}>
                                + Add option
                            </button>
                        )}
                        <div style={{ display: 'flex', gap: 12, marginTop: 8, alignItems: 'center', flexWrap: 'wrap' }}>
                            <input type="datetime-local" value={endsAt} onChange={e => setEndsAt(e.target.value)}
                                style={{ border: '1px solid #e2e8f0', borderRadius: 8, padding: '0.5rem', fontSize: '0.8rem' }} />
                            <label style={{ display: 'flex', gap: 6, alignItems: 'center', cursor: 'pointer', fontSize: '0.875rem' }}>
                                <input type="checkbox" checked={allowMultiple} onChange={e => setAllowMultiple(e.target.checked)} />
                                Allow multiple choices
                            </label>
                        </div>
                    </div>
                )}

                {error && <div style={{ marginTop: 8, color: '#dc2626', fontSize: '0.875rem' }}>{error}</div>}

                <button onClick={submit} disabled={submitting}
                    style={{ marginTop: '1rem', width: '100%', padding: '0.7rem', borderRadius: 10, border: 'none', background: '#2563eb', color: '#fff', fontSize: '0.95rem', fontWeight: 600, cursor: 'pointer' }}>
                    {submitting ? 'Posting…' : 'Post'}
                </button>
            </div>
        </div>
    );
}
