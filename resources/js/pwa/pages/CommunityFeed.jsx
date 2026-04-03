import React, { useState, useEffect, useCallback } from 'react';
import { useAuth } from '../hooks/useAuth';
import { useApi } from '../hooks/useApi';

const POST_TYPES = [
    { value: '', label: 'All' },
    { value: 'discussion', label: 'Discussion' },
    { value: 'blessing', label: 'Blessing' },
    { value: 'verse', label: 'Verse' },
    { value: 'testimony', label: 'Testimony' },
    { value: 'question', label: 'Q&A' },
    { value: 'bible_study', label: 'Bible Study' },
];

const REACTION_TYPES = [
    { value: 'like', emoji: '❤️' },
    { value: 'pray', emoji: '🙏' },
    { value: 'amen', emoji: '🙌' },
];

export default function CommunityFeed() {
    const { user } = useAuth();
    const { loading, request } = useApi();
    const [posts, setPosts] = useState([]);
    const [filter, setFilter] = useState('');
    const [sort, setSort] = useState('latest');
    const [showComposer, setShowComposer] = useState(false);
    const [newPost, setNewPost] = useState({ type: 'discussion', title: '', body: '', is_anonymous: false });
    const [expandedPost, setExpandedPost] = useState(null);
    const [commentText, setCommentText] = useState('');

    const loadFeed = useCallback(async () => {
        const params = new URLSearchParams({ sort });
        if (filter) params.set('type', filter);
        const data = await request(`/api/community/feed?${params}`);
        if (data?.data) setPosts(data.data);
    }, [filter, sort, request]);

    useEffect(() => { loadFeed(); }, [loadFeed]);

    const submitPost = async (e) => {
        e.preventDefault();
        if (!newPost.body.trim()) return;
        const data = await request('/api/community/posts', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(newPost),
        });
        if (data?.id) {
            setPosts(prev => [data, ...prev]);
            setNewPost({ type: 'discussion', title: '', body: '', is_anonymous: false });
            setShowComposer(false);
        }
    };

    const toggleLike = async (postId, type = 'like') => {
        const data = await request(`/api/community/posts/${postId}/like`, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ type }),
        });
        if (data) {
            setPosts(prev => prev.map(p => p.id === postId
                ? { ...p, likes_count: data.liked ? p.likes_count + 1 : p.likes_count - 1 }
                : p
            ));
        }
    };

    const submitComment = async (postId) => {
        if (!commentText.trim()) return;
        const data = await request(`/api/community/posts/${postId}/comments`, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ body: commentText }),
        });
        if (data?.id) {
            setPosts(prev => prev.map(p => p.id === postId
                ? { ...p, comments_count: p.comments_count + 1 }
                : p
            ));
            setCommentText('');
        }
    };

    return (
        <div style={{ padding: '16px', paddingBottom: '80px' }}>
            <div style={{ display: 'flex', justifyContent: 'space-between', alignItems: 'center', marginBottom: '16px' }}>
                <h2 style={{ margin: 0, fontSize: '20px' }}>Community</h2>
                {user && (
                    <button
                        onClick={() => setShowComposer(!showComposer)}
                        style={{
                            background: '#6366f1', color: '#fff', border: 'none', borderRadius: '20px',
                            padding: '8px 16px', fontSize: '14px', cursor: 'pointer',
                        }}
                    >
                        + New Post
                    </button>
                )}
            </div>

            {/* Composer */}
            {showComposer && (
                <form onSubmit={submitPost} style={{
                    background: '#1a1d24', borderRadius: '12px', padding: '16px', marginBottom: '16px',
                }}>
                    <select
                        value={newPost.type}
                        onChange={e => setNewPost(p => ({ ...p, type: e.target.value }))}
                        style={{ width: '100%', padding: '8px', marginBottom: '8px', borderRadius: '8px', background: '#2a2d34', color: '#fff', border: 'none' }}
                    >
                        {POST_TYPES.filter(t => t.value).map(t => (
                            <option key={t.value} value={t.value}>{t.label}</option>
                        ))}
                    </select>
                    <input
                        placeholder="Title (optional)"
                        value={newPost.title}
                        onChange={e => setNewPost(p => ({ ...p, title: e.target.value }))}
                        style={{ width: '100%', padding: '8px', marginBottom: '8px', borderRadius: '8px', background: '#2a2d34', color: '#fff', border: 'none', boxSizing: 'border-box' }}
                    />
                    <textarea
                        placeholder="Share your thoughts..."
                        value={newPost.body}
                        onChange={e => setNewPost(p => ({ ...p, body: e.target.value }))}
                        rows={4}
                        style={{ width: '100%', padding: '8px', marginBottom: '8px', borderRadius: '8px', background: '#2a2d34', color: '#fff', border: 'none', resize: 'vertical', boxSizing: 'border-box' }}
                    />
                    <div style={{ display: 'flex', justifyContent: 'space-between', alignItems: 'center' }}>
                        <label style={{ fontSize: '13px', color: '#9ca3af' }}>
                            <input
                                type="checkbox"
                                checked={newPost.is_anonymous}
                                onChange={e => setNewPost(p => ({ ...p, is_anonymous: e.target.checked }))}
                            /> Post anonymously
                        </label>
                        <button type="submit" disabled={loading} style={{
                            background: '#6366f1', color: '#fff', border: 'none', borderRadius: '8px',
                            padding: '8px 20px', cursor: 'pointer',
                        }}>
                            {loading ? 'Posting...' : 'Post'}
                        </button>
                    </div>
                </form>
            )}

            {/* Filters */}
            <div style={{ display: 'flex', gap: '8px', overflowX: 'auto', marginBottom: '12px', paddingBottom: '4px' }}>
                {POST_TYPES.map(t => (
                    <button
                        key={t.value}
                        onClick={() => setFilter(t.value)}
                        style={{
                            background: filter === t.value ? '#6366f1' : '#2a2d34',
                            color: '#fff', border: 'none', borderRadius: '16px',
                            padding: '6px 14px', fontSize: '13px', whiteSpace: 'nowrap', cursor: 'pointer',
                        }}
                    >
                        {t.label}
                    </button>
                ))}
            </div>

            {/* Sort */}
            <div style={{ display: 'flex', gap: '8px', marginBottom: '16px' }}>
                {['latest', 'trending'].map(s => (
                    <button
                        key={s}
                        onClick={() => setSort(s)}
                        style={{
                            background: 'none', border: 'none', color: sort === s ? '#6366f1' : '#9ca3af',
                            fontSize: '13px', cursor: 'pointer', textTransform: 'capitalize',
                            borderBottom: sort === s ? '2px solid #6366f1' : '2px solid transparent',
                            paddingBottom: '4px',
                        }}
                    >
                        {s}
                    </button>
                ))}
            </div>

            {/* Posts */}
            {loading && posts.length === 0 && <p style={{ color: '#9ca3af', textAlign: 'center' }}>Loading...</p>}
            {!loading && posts.length === 0 && <p style={{ color: '#9ca3af', textAlign: 'center' }}>No posts yet. Be the first!</p>}

            {posts.map(post => (
                <div key={post.id} style={{
                    background: '#1a1d24', borderRadius: '12px', padding: '16px', marginBottom: '12px',
                }}>
                    {/* Header */}
                    <div style={{ display: 'flex', alignItems: 'center', gap: '10px', marginBottom: '8px' }}>
                        <div style={{
                            width: '36px', height: '36px', borderRadius: '50%', background: '#374151',
                            display: 'flex', alignItems: 'center', justifyContent: 'center', fontSize: '14px',
                        }}>
                            {post.is_anonymous ? '?' : (post.user?.name?.[0] || '?')}
                        </div>
                        <div>
                            <div style={{ fontSize: '14px', fontWeight: 600 }}>
                                {post.is_anonymous ? 'Anonymous' : (post.user?.name || 'Unknown')}
                            </div>
                            <div style={{ fontSize: '11px', color: '#9ca3af' }}>
                                {post.type} · {new Date(post.created_at).toLocaleDateString()}
                            </div>
                        </div>
                        {post.is_pinned && <span style={{ fontSize: '11px', background: '#6366f1', padding: '2px 6px', borderRadius: '4px', marginLeft: 'auto' }}>Pinned</span>}
                    </div>

                    {/* Content */}
                    {post.title && <h3 style={{ margin: '0 0 4px', fontSize: '16px' }}>{post.title}</h3>}
                    <p style={{ margin: '0 0 12px', fontSize: '14px', color: '#d1d5db', lineHeight: 1.5 }}>{post.body}</p>

                    {/* Reactions */}
                    <div style={{ display: 'flex', gap: '16px', alignItems: 'center', borderTop: '1px solid #2a2d34', paddingTop: '10px' }}>
                        {REACTION_TYPES.map(r => (
                            <button
                                key={r.value}
                                onClick={() => toggleLike(post.id, r.value)}
                                style={{ background: 'none', border: 'none', color: '#9ca3af', cursor: 'pointer', fontSize: '14px' }}
                            >
                                {r.emoji} {r.value === 'like' ? post.likes_count : ''}
                            </button>
                        ))}
                        <button
                            onClick={() => setExpandedPost(expandedPost === post.id ? null : post.id)}
                            style={{ background: 'none', border: 'none', color: '#9ca3af', cursor: 'pointer', fontSize: '14px' }}
                        >
                            💬 {post.comments_count}
                        </button>
                        <span style={{ fontSize: '13px', color: '#6b7280' }}>🔗 {post.shares_count}</span>
                    </div>

                    {/* Comments */}
                    {expandedPost === post.id && user && (
                        <div style={{ marginTop: '12px', borderTop: '1px solid #2a2d34', paddingTop: '10px' }}>
                            <div style={{ display: 'flex', gap: '8px' }}>
                                <input
                                    placeholder="Write a comment..."
                                    value={commentText}
                                    onChange={e => setCommentText(e.target.value)}
                                    onKeyDown={e => e.key === 'Enter' && submitComment(post.id)}
                                    style={{
                                        flex: 1, padding: '8px 12px', borderRadius: '20px',
                                        background: '#2a2d34', color: '#fff', border: 'none', fontSize: '13px',
                                    }}
                                />
                                <button
                                    onClick={() => submitComment(post.id)}
                                    style={{ background: '#6366f1', color: '#fff', border: 'none', borderRadius: '20px', padding: '8px 14px', fontSize: '13px', cursor: 'pointer' }}
                                >
                                    Send
                                </button>
                            </div>
                        </div>
                    )}
                </div>
            ))}
        </div>
    );
}
