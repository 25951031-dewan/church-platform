import React, { useEffect, useState } from 'react';
import axios from 'axios';
import PostCard from './PostCard';
import CreatePostModal from './CreatePostModal';

export default function FeedPage() {
    const [posts, setPosts]     = useState<any[]>([]);
    const [page, setPage]       = useState(1);
    const [hasMore, setHasMore] = useState(true);
    const [loading, setLoading] = useState(false);
    const [showCompose, setShowCompose] = useState(false);
    const params = new URLSearchParams(window.location.search);
    const [typeFilter, setTypeFilter] = useState(params.get('type') ?? '');

    const load = async (p: number, filter?: string) => {
        if (loading) return;
        setLoading(true);
        const currentFilter = filter !== undefined ? filter : typeFilter;
        try {
            const url = currentFilter ? `/api/v1/feed?page=${p}&type=${currentFilter}` : `/api/v1/feed?page=${p}`;
            const { data } = await axios.get(url);
            setPosts(prev => p === 1 ? data.data : [...prev, ...data.data]);
            setHasMore(!!data.links?.next);
            setPage(p);
        } finally {
            setLoading(false);
        }
    };

    function changeFilter(value: string) {
        const url = new URL(window.location.href);
        if (value) url.searchParams.set('type', value);
        else url.searchParams.delete('type');
        window.history.pushState({}, '', url.toString());
        setTypeFilter(value);
        setPage(1);
        load(1, value);
    }

    useEffect(() => { load(1); }, []);

    const react = async (postId: number, emoji: string) => {
        const { data } = await axios.post('/api/v1/reactions', { reactable_type: 'post', reactable_id: postId, emoji });
        const delta = data.reacted ? 1 : -1;
        setPosts(ps => ps.map(p => p.id === postId ? { ...p, reactions_count: Math.max(0, p.reactions_count + delta) } : p));
    };

    return (
        <div style={{ maxWidth: 640, margin: '0 auto', padding: '1rem' }}>
            <h1 style={{ fontSize: '1.25rem', fontWeight: 700, marginBottom: '1rem' }}>Home Feed</h1>

            {/* Create Post button */}
            <button onClick={() => setShowCompose(true)}
                style={{ width: '100%', padding: '0.75rem', borderRadius: 12, border: 'none', background: '#2563eb', color: '#fff', fontSize: '0.95rem', fontWeight: 600, cursor: 'pointer', marginBottom: '1rem' }}>
                + Create Post
            </button>

            {/* Filter tabs */}
            <div style={{ display: 'flex', gap: 8, overflowX: 'auto', marginBottom: '1rem' }}>
                {[
                    { label: 'All', value: '' },
                    { label: '🙏 Prayer', value: 'prayer' },
                    { label: '✨ Blessings', value: 'blessing' },
                    { label: '📊 Polls', value: 'poll' },
                    { label: '📖 Bible Study', value: 'bible_study' },
                ].map(f => (
                    <button key={f.value} onClick={() => changeFilter(f.value)}
                        style={{ padding: '6px 16px', borderRadius: 20, border: 'none', cursor: 'pointer', whiteSpace: 'nowrap', fontSize: '0.875rem', background: typeFilter === f.value ? '#2563eb' : '#f1f5f9', color: typeFilter === f.value ? '#fff' : '#475569' }}>
                        {f.label}
                    </button>
                ))}
            </div>

            {posts.map(post => <PostCard key={post.id} post={post} onReact={react} />)}
            {hasMore && (
                <button onClick={() => load(page + 1)}
                    disabled={loading}
                    style={{ width: '100%', padding: '0.75rem', background: '#f1f5f9', border: 'none', borderRadius: 8, cursor: 'pointer', marginTop: '0.5rem' }}>
                    {loading ? 'Loading…' : 'Load more'}
                </button>
            )}

            {showCompose && <CreatePostModal onClose={() => setShowCompose(false)} onCreated={() => { setShowCompose(false); load(1); }} />}
        </div>
    );
}
