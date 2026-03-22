import React from 'react';

interface BlessingMeta { scripture?: string }
interface Props { body: string | null; meta: BlessingMeta }

export default function BlessingCard({ body, meta }: Props) {
    return (
        <div>
            <div style={{ marginBottom: 8 }}>
                <span style={{ background: '#fef9c3', color: '#a16207', borderRadius: 4, padding: '2px 8px', fontSize: '0.75rem', fontWeight: 600 }}>✨ Blessing</span>
            </div>
            {body && <p style={{ margin: 0, lineHeight: 1.6, fontSize: '0.95rem' }}>{body}</p>}
            {meta.scripture && (
                <p style={{ marginTop: 8, fontStyle: 'italic', fontSize: '0.875rem', color: '#64748b' }}>
                    — {meta.scripture}
                </p>
            )}
        </div>
    );
}
