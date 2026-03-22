import React, { useEffect, useState } from 'react';
import axios from 'axios';
import PostCard from './PostCard';

export default function FeedPage() {
    const [posts, setPosts]     = useState<any[]>([]);
    const [page, setPage]       = useState(1);
    const [hasMore, setHasMore] = useState(true);
    const [loading, setLoading] = useState(false);

    const load = async (p = 1) => {
        setLoading(true);
        const { data } = await axios.get(`/api/v1/feed?page=${p}`);
        setPosts(prev => p === 1 ? data.data : [...prev, ...data.data]);
        setHasMore(!!data.next_page_url);
        setLoading(false);
    };

    useEffect(() => { load(1); }, []);

    const react = async (postId: number, emoji: string) => {
        await axios.post('/api/v1/reactions', { reactable_type: 'post', reactable_id: postId, emoji });
        setPosts(ps => ps.map(p => p.id === postId ? { ...p, reactions_count: p.reactions_count + 1 } : p));
    };

    return (
        <div style={{ maxWidth: 640, margin: '0 auto', padding: '1rem' }}>
            <h1 style={{ fontSize: '1.25rem', fontWeight: 700, marginBottom: '1rem' }}>Home Feed</h1>
            {posts.map(post => <PostCard key={post.id} post={post} onReact={react} />)}
            {hasMore && (
                <button onClick={() => { const next = page + 1; setPage(next); load(next); }}
                    disabled={loading}
                    style={{ width: '100%', padding: '0.75rem', background: '#f1f5f9', border: 'none', borderRadius: 8, cursor: 'pointer', marginTop: '0.5rem' }}>
                    {loading ? 'Loading…' : 'Load more'}
                </button>
            )}
        </div>
    );
}
