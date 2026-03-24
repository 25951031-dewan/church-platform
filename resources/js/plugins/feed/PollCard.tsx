import React, { useEffect, useState } from 'react';
import axios from 'axios';

interface PollOption { id: string; text: string; votes_count: number }
interface PollMeta { question: string; options: PollOption[]; ends_at: string | null; allow_multiple: boolean }
interface Props { postId: number; meta: PollMeta }

export default function PollCard({ postId, meta }: Props) {
    const [counts, setCounts] = useState<Record<string, number>>({});
    const [userVote, setUserVote] = useState<string | null>(null);
    const [total, setTotal] = useState(0);
    const expired = meta.ends_at ? new Date(meta.ends_at) < new Date() : false;

    useEffect(() => {
        axios.get(`/api/v1/posts/${postId}/votes`)
            .then(r => {
                setCounts(r.data.counts ?? {});
                setUserVote(r.data.user_vote ?? null);
                setTotal(Object.values(r.data.counts ?? {}).reduce((s: number, c: any) => s + Number(c), 0));
            })
            .catch(() => {});
    }, [postId]);

    async function vote(optionId: string) {
        if (expired) return;
        try {
            const { data } = await axios.post(`/api/v1/posts/${postId}/vote`, { option_id: optionId });
            setCounts(data.counts ?? {});
            setUserVote(data.user_vote ?? null);
            setTotal(Object.values(data.counts ?? {}).reduce((s: number, c: any) => s + Number(c), 0));
        } catch {}
    }

    return (
        <div>
            <div style={{ display: 'flex', gap: 8, alignItems: 'center', marginBottom: 10 }}>
                <span style={{ background: '#f0fdf4', color: '#15803d', borderRadius: 4, padding: '2px 8px', fontSize: '0.75rem', fontWeight: 600 }}>📊 Poll</span>
                {expired && <span style={{ background: '#fef2f2', color: '#dc2626', borderRadius: 4, padding: '2px 8px', fontSize: '0.75rem' }}>Ended</span>}
            </div>
            <p style={{ fontWeight: 700, fontSize: '0.95rem', marginBottom: 12 }}>{meta.question}</p>
            <div style={{ display: 'flex', flexDirection: 'column', gap: 8 }}>
                {meta.options.map(opt => {
                    const count  = Number(counts[opt.id] ?? 0);
                    const pct    = total > 0 ? Math.round((count / total) * 100) : 0;
                    const isVote = userVote === opt.id;

                    return (
                        <div key={opt.id} onClick={() => vote(opt.id)}
                            style={{ position: 'relative', borderRadius: 8, overflow: 'hidden', border: `2px solid ${isVote ? '#2563eb' : '#e2e8f0'}`, cursor: expired ? 'default' : 'pointer', background: '#fff' }}>
                            <div style={{ position: 'absolute', inset: 0, background: isVote ? '#dbeafe' : '#f8fafc', width: `${pct}%`, transition: 'width 0.4s ease' }} />
                            <div style={{ position: 'relative', display: 'flex', justifyContent: 'space-between', padding: '8px 12px', fontSize: '0.875rem' }}>
                                <span style={{ fontWeight: isVote ? 700 : 400 }}>{opt.text}</span>
                                <span style={{ color: '#64748b' }}>{pct}% ({count})</span>
                            </div>
                        </div>
                    );
                })}
            </div>
            <div style={{ marginTop: 8, fontSize: '0.75rem', color: '#94a3b8' }}>
                {total} vote{total !== 1 ? 's' : ''}
                {meta.ends_at && !expired && ` · Ends ${new Date(meta.ends_at).toLocaleDateString()}`}
            </div>
        </div>
    );
}
