import React, { useState } from 'react';

interface BibleStudyMeta { scripture: string; passage: string; study_guide?: string }
interface Props { body: string | null; meta: BibleStudyMeta }

export default function BibleStudyCard({ body, meta }: Props) {
    const [expanded, setExpanded] = useState(false);

    return (
        <div>
            <div style={{ marginBottom: 8 }}>
                <span style={{ background: '#dbeafe', color: '#1d4ed8', borderRadius: 4, padding: '2px 8px', fontSize: '0.75rem', fontWeight: 600 }}>📖 Bible Study</span>
                <span style={{ marginLeft: 8, fontWeight: 700, fontSize: '0.875rem', color: '#1e40af' }}>{meta.scripture}</span>
            </div>
            <blockquote style={{ borderLeft: '3px solid #bfdbfe', paddingLeft: '0.75rem', margin: '0 0 12px', color: '#374151', fontStyle: 'italic', fontSize: '0.9rem', lineHeight: 1.6 }}>
                {meta.passage}
            </blockquote>
            {body && <p style={{ margin: '0 0 8px', fontSize: '0.95rem', lineHeight: 1.6 }}>{body}</p>}
            {meta.study_guide && (
                <>
                    <button onClick={() => setExpanded(e => !e)}
                        style={{ fontSize: '0.8rem', background: 'none', border: 'none', color: '#2563eb', cursor: 'pointer', padding: 0 }}>
                        {expanded ? '▲ Hide study guide' : '▼ Show study guide'}
                    </button>
                    {expanded && (
                        <div style={{ marginTop: 8, background: '#f8fafc', borderRadius: 8, padding: '0.75rem', fontSize: '0.875rem', lineHeight: 1.7, color: '#374151' }}>
                            {meta.study_guide}
                        </div>
                    )}
                </>
            )}
        </div>
    );
}
