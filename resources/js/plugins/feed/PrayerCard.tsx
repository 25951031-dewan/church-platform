import React from 'react';

interface PrayerMeta { answered: boolean; answered_at: string | null }
interface Props { postId: number; body: string | null; meta: PrayerMeta; isAuthor: boolean; onAnswered?: () => void }

export default function PrayerCard({ postId, body, meta, isAuthor, onAnswered }: Props) {
    async function toggleAnswered() {
        await fetch(`/api/v1/posts/${postId}/answer-prayer`, { method: 'POST' });
        onAnswered?.();
    }

    return (
        <div style={{ borderLeft: '4px solid #7c3aed', paddingLeft: '0.75rem' }}>
            <div style={{ display: 'flex', gap: 6, alignItems: 'center', marginBottom: 8 }}>
                <span style={{ background: '#ede9fe', color: '#7c3aed', borderRadius: 4, padding: '2px 8px', fontSize: '0.75rem', fontWeight: 600 }}>🙏 Prayer Request</span>
                {meta.answered && (
                    <span style={{ background: '#dcfce7', color: '#15803d', borderRadius: 4, padding: '2px 8px', fontSize: '0.75rem', fontWeight: 600 }}>✓ Answered</span>
                )}
            </div>
            {body && <p style={{ margin: 0, lineHeight: 1.6, fontSize: '0.95rem' }}>{body}</p>}
            {isAuthor && (
                <button onClick={toggleAnswered}
                    style={{ marginTop: 8, fontSize: '0.8rem', background: 'none', border: '1px solid #7c3aed', color: '#7c3aed', borderRadius: 20, padding: '3px 12px', cursor: 'pointer' }}>
                    {meta.answered ? 'Unmark as Answered' : 'Mark as Answered'}
                </button>
            )}
        </div>
    );
}
