import React, { useState, Suspense, lazy } from 'react';
import SafeHtml from '../../components/shared/SafeHtml';
import PrayerCard from './PrayerCard';
import BlessingCard from './BlessingCard';
import BibleStudyCard from './BibleStudyCard';
import PollCard from './PollCard';

const CommentThread = lazy(() => import('./CommentThread'));

const EMOJIS = ['👍', '❤️', '🙏', '✝️', '🕊️'];

interface Author { id: number; name: string; avatar?: string }
interface Post {
    id: number;
    body: string | null;
    type: 'post' | 'prayer' | 'blessing' | 'poll' | 'bible_study';
    meta?: Record<string, any>;
    author: Author | null;
    entity_actor?: { id: number; name: string; profile_image?: string } | null;
    posted_as: 'user' | 'entity';
    church?: { name: string };
    reactions_count: number;
    comments_count: number;
    created_at: string;
}

export default function PostCard({ post, onReact }: { post: Post; onReact: (id: number, emoji: string) => void }) {
    const [showComments, setShowComments] = useState(false);

    const displayName   = post.posted_as === 'entity' && post.entity_actor
        ? post.entity_actor.name
        : (post.author?.name ?? 'Anonymous');
    const displayAvatar = post.posted_as === 'entity' && post.entity_actor
        ? (post.entity_actor.profile_image ?? `https://ui-avatars.com/api/?name=${encodeURIComponent(post.entity_actor.name)}`)
        : (post.author?.avatar ?? `https://ui-avatars.com/api/?name=${encodeURIComponent(post.author?.name ?? 'Anonymous')}`);

    return (
        <div style={{ background: '#fff', borderRadius: 12, padding: '1rem', marginBottom: '1rem', boxShadow: '0 1px 4px rgba(0,0,0,.08)' }}>
            <div style={{ display: 'flex', gap: '0.75rem', marginBottom: '0.75rem', alignItems: 'center' }}>
                <img src={displayAvatar}
                    style={{ width: 40, height: 40, borderRadius: post.posted_as === 'entity' ? 8 : '50%', objectFit: 'cover' }} alt="" />
                <div>
                    <div style={{ fontWeight: 600, fontSize: '0.9rem' }}>
                        {displayName}
                        {post.posted_as === 'entity' && (
                            <span style={{ fontSize: '0.7rem', color: '#3b82f6', marginLeft: 6, fontWeight: 500 }}>Page</span>
                        )}
                    </div>
                    <div style={{ fontSize: '0.75rem', color: '#64748b' }}>
                        {new Date(post.created_at).toLocaleDateString()}{post.church && ` · ${post.church.name}`}
                    </div>
                </div>
            </div>

            {/* Type-specific body rendering */}
            {post.type === 'prayer' && post.meta && (
                <PrayerCard postId={post.id} body={post.body} meta={post.meta as any} isAuthor={false} />
            )}
            {post.type === 'blessing' && (
                <BlessingCard body={post.body} meta={(post.meta ?? {}) as any} />
            )}
            {post.type === 'bible_study' && post.meta && (
                <BibleStudyCard body={post.body} meta={post.meta as any} />
            )}
            {post.type === 'poll' && post.meta && (
                <PollCard postId={post.id} meta={post.meta as any} />
            )}
            {(!post.type || post.type === 'post') && (
                <SafeHtml html={post.body ?? ''} style={{ fontSize: '0.95rem', lineHeight: 1.6, marginBottom: '0.75rem' }} />
            )}

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
