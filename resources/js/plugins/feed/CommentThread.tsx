import React, { useEffect, useState } from 'react';
import axios from 'axios';
import SafeHtml from '../../components/shared/SafeHtml';

interface Comment {
    id: number; body: string;
    author: { name: string; avatar?: string };
    replies: Comment[]; replies_count: number; created_at: string;
}

export default function CommentThread({ postId }: { postId: number }) {
    const [comments, setComments] = useState<Comment[]>([]);
    const [body, setBody]         = useState('');
    const [replyTo, setReplyTo]   = useState<number | null>(null);
    const [loading, setLoading]   = useState(true);

    useEffect(() => {
        axios.get(`/api/v1/posts/${postId}/comments`).then(r => { setComments(r.data.data ?? []); setLoading(false); });
    }, [postId]);

    const submit = async (parentId?: number) => {
        if (!body.trim()) return;
        const { data } = await axios.post('/api/v1/comments', { post_id: postId, body, parent_id: parentId ?? null });
        if (parentId) {
            setComments(cs => cs.map(c => c.id === parentId ? { ...c, replies: [data, ...c.replies] } : c));
        } else {
            setComments(cs => [data, ...cs]);
        }
        setBody(''); setReplyTo(null);
    };

    const Item = ({ c, depth = 0 }: { c: Comment; depth?: number }) => (
        <div style={{ marginLeft: depth * 20, marginBottom: '0.75rem' }}>
            <div style={{ display: 'flex', gap: '0.5rem' }}>
                <img src={c.author.avatar ?? `https://ui-avatars.com/api/?name=${encodeURIComponent(c.author.name)}`}
                    style={{ width: 28, height: 28, borderRadius: '50%', flexShrink: 0 }} alt="" />
                <div style={{ background: '#f8fafc', borderRadius: 8, padding: '0.4rem 0.75rem', flex: 1 }}>
                    <strong style={{ fontSize: '0.8rem' }}>{c.author.name}</strong>
                    <SafeHtml html={c.body} style={{ fontSize: '0.875rem' }} />
                </div>
            </div>
            <button onClick={() => setReplyTo(c.id)}
                style={{ fontSize: '0.75rem', color: '#2563eb', background: 'none', border: 'none', cursor: 'pointer', marginLeft: 36 }}>
                Reply
            </button>
            {replyTo === c.id && (
                <div style={{ marginLeft: 36, marginTop: '0.4rem', display: 'flex', gap: '0.5rem' }}>
                    <input value={body} onChange={e => setBody(e.target.value)} placeholder="Write a reply…"
                        style={{ flex: 1, border: '1px solid #e2e8f0', borderRadius: 8, padding: '0.4rem 0.75rem', fontSize: '0.875rem' }} />
                    <button onClick={() => submit(c.id)}
                        style={{ background: '#2563eb', color: '#fff', border: 'none', borderRadius: 8, padding: '0.4rem 0.75rem', cursor: 'pointer' }}>
                        Post
                    </button>
                </div>
            )}
            {c.replies?.map(r => <Item key={r.id} c={r} depth={depth + 1} />)}
        </div>
    );

    return (
        <div>
            <div style={{ display: 'flex', gap: '0.5rem', marginBottom: '0.75rem' }}>
                <input value={body} onChange={e => setBody(e.target.value)} placeholder="Write a comment…"
                    style={{ flex: 1, border: '1px solid #e2e8f0', borderRadius: 8, padding: '0.5rem 0.75rem', fontSize: '0.875rem' }} />
                <button onClick={() => submit()}
                    style={{ background: '#2563eb', color: '#fff', border: 'none', borderRadius: 8, padding: '0.5rem 1rem', cursor: 'pointer' }}>
                    Post
                </button>
            </div>
            {loading ? <p style={{ color: '#94a3b8', fontSize: '0.875rem' }}>Loading…</p> : comments.map(c => <Item key={c.id} c={c} />)}
        </div>
    );
}
