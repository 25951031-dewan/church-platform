import React, { useState, Suspense, lazy } from 'react';
import SafeHtml from '../../components/shared/SafeHtml';

const CommentThread = lazy(() => import('./CommentThread'));

const EMOJIS = ['👍', '❤️', '🙏', '😂', '😮', '😢'];

interface Author { id: number; name: string; avatar?: string }
interface Post {
    id: number; body: string; author: Author;
    church?: { name: string }; reactions_count: number;
    comments_count: number; created_at: string;
}

export default function PostCard({ post, onReact }: { post: Post; onReact: (id: number, emoji: string) => void }) {
    const [showComments, setShowComments] = useState(false);

    return (
        <div style={{ background: '#fff', borderRadius: 12, padding: '1rem', marginBottom: '1rem', boxShadow: '0 1px 4px rgba(0,0,0,.08)' }}>
            <div style={{ display: 'flex', gap: '0.75rem', marginBottom: '0.75rem', alignItems: 'center' }}>
                <img src={post.author.avatar ?? `https://ui-avatars.com/api/?name=${encodeURIComponent(post.author.name)}`}
                    style={{ width: 40, height: 40, borderRadius: '50%', objectFit: 'cover' }} alt="" />
                <div>
                    <div style={{ fontWeight: 600, fontSize: '0.9rem' }}>{post.author.name}</div>
                    <div style={{ fontSize: '0.75rem', color: '#64748b' }}>
                        {new Date(post.created_at).toLocaleDateString()}{post.church && ` · ${post.church.name}`}
                    </div>
                </div>
            </div>

            <SafeHtml html={post.body} style={{ fontSize: '0.95rem', lineHeight: 1.6, marginBottom: '0.75rem' }} />

            <div style={{ display: 'flex', gap: '0.5rem', alignItems: 'center', flexWrap: 'wrap' }}>
                {EMOJIS.map(e => (
                    <button key={e} onClick={() => onReact(post.id, e)}
                        style={{ background: 'none', border: '1px solid #e2e8f0', borderRadius: 20, padding: '0.2rem 0.6rem', cursor: 'pointer' }}>
                        {e}
                    </button>
                ))}
                <span style={{ marginLeft: 'auto', fontSize: '0.8rem', color: '#64748b' }}>{post.reactions_count} reactions</span>
                <button onClick={() => setShowComments(v => !v)}
                    style={{ fontSize: '0.8rem', color: '#2563eb', background: 'none', border: 'none', cursor: 'pointer' }}>
                    {post.comments_count} comments
                </button>
            </div>

            {showComments && (
                <div style={{ marginTop: '0.75rem', borderTop: '1px solid #f1f5f9', paddingTop: '0.75rem' }}>
                    <Suspense fallback={<span style={{ fontSize: '0.8rem', color: '#94a3b8' }}>Loading…</span>}>
                        <CommentThread postId={post.id} />
                    </Suspense>
                </div>
            )}
        </div>
    );
}
