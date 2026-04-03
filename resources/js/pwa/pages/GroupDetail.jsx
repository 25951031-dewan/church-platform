import React, { useState, useEffect, useCallback } from 'react';
import { useParams, useNavigate } from 'react-router-dom';
import { useAuth } from '../hooks/useAuth';
import { useApi } from '../hooks/useApi';

export default function GroupDetail() {
    const { id } = useParams();
    const navigate = useNavigate();
    const { user } = useAuth();
    const { loading, request } = useApi();
    const [group, setGroup] = useState(null);
    const [posts, setPosts] = useState([]);
    const [showMembers, setShowMembers] = useState(false);
    const [newPost, setNewPost] = useState('');

    const loadGroup = useCallback(async () => {
        const data = await request(`/api/community/groups/${id}`);
        if (data) setGroup(data);
    }, [id, request]);

    const loadPosts = useCallback(async () => {
        const data = await request(`/api/community/feed?group_id=${id}`);
        if (data?.data) setPosts(data.data);
    }, [id, request]);

    useEffect(() => {
        loadGroup();
        loadPosts();
    }, [loadGroup, loadPosts]);

    const joinGroup = async () => {
        await request(`/api/community/groups/${id}/join`, { method: 'POST' });
        loadGroup();
    };

    const leaveGroup = async () => {
        await request(`/api/community/groups/${id}/leave`, { method: 'POST' });
        loadGroup();
    };

    const submitPost = async () => {
        if (!newPost.trim()) return;
        const data = await request('/api/community/posts', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ type: 'discussion', body: newPost, group_id: parseInt(id) }),
        });
        if (data?.id) {
            setPosts(prev => [data, ...prev]);
            setNewPost('');
        }
    };

    if (loading && !group) {
        return <div style={{ padding: '16px', color: '#9ca3af', textAlign: 'center' }}>Loading...</div>;
    }

    if (!group) {
        return <div style={{ padding: '16px', color: '#9ca3af', textAlign: 'center' }}>Group not found</div>;
    }

    const isMember = group.members?.some(m => m.id === user?.id);

    return (
        <div style={{ paddingBottom: '80px' }}>
            {/* Header */}
            <div style={{
                background: group.cover_image ? `url(${group.cover_image}) center/cover` : 'linear-gradient(135deg, #6366f1, #8b5cf6)',
                height: '160px', position: 'relative',
            }}>
                <button
                    onClick={() => navigate(-1)}
                    style={{
                        position: 'absolute', top: '12px', left: '12px',
                        background: 'rgba(0,0,0,0.5)', color: '#fff', border: 'none',
                        borderRadius: '50%', width: '36px', height: '36px', cursor: 'pointer', fontSize: '18px',
                    }}
                >
                    ←
                </button>
            </div>

            {/* Group Info */}
            <div style={{ padding: '16px' }}>
                <div style={{ display: 'flex', justifyContent: 'space-between', alignItems: 'flex-start' }}>
                    <div>
                        <h2 style={{ margin: '0 0 4px', fontSize: '20px' }}>{group.name}</h2>
                        <div style={{ fontSize: '13px', color: '#9ca3af' }}>
                            {group.type === 'church_only' ? 'Church Only' : group.type === 'private' ? 'Private' : 'Public'}
                            {' · '}{group.members_count || 0} members
                        </div>
                    </div>
                    {user && (
                        isMember ? (
                            <button
                                onClick={leaveGroup}
                                style={{
                                    background: '#374151', color: '#fff', border: 'none',
                                    borderRadius: '8px', padding: '8px 16px', fontSize: '13px', cursor: 'pointer',
                                }}
                            >
                                Leave
                            </button>
                        ) : (
                            <button
                                onClick={joinGroup}
                                style={{
                                    background: '#6366f1', color: '#fff', border: 'none',
                                    borderRadius: '8px', padding: '8px 16px', fontSize: '13px', cursor: 'pointer',
                                }}
                            >
                                Join
                            </button>
                        )
                    )}
                </div>

                {group.description && (
                    <p style={{ margin: '12px 0', fontSize: '14px', color: '#d1d5db', lineHeight: 1.5 }}>
                        {group.description}
                    </p>
                )}

                {/* Members preview */}
                <button
                    onClick={() => setShowMembers(!showMembers)}
                    style={{
                        background: '#1a1d24', border: 'none', color: '#9ca3af',
                        borderRadius: '8px', padding: '10px', width: '100%', textAlign: 'left',
                        cursor: 'pointer', marginBottom: '16px', fontSize: '13px',
                    }}
                >
                    👥 {showMembers ? 'Hide' : 'View'} Members ({group.members_count || 0})
                </button>

                {showMembers && group.members && (
                    <div style={{
                        background: '#1a1d24', borderRadius: '8px', padding: '12px', marginBottom: '16px',
                    }}>
                        {group.members.map(m => (
                            <div key={m.id} style={{
                                display: 'flex', alignItems: 'center', gap: '10px',
                                padding: '6px 0', borderBottom: '1px solid #2a2d34',
                            }}>
                                <div style={{
                                    width: '28px', height: '28px', borderRadius: '50%', background: '#374151',
                                    display: 'flex', alignItems: 'center', justifyContent: 'center', fontSize: '12px',
                                }}>
                                    {m.name?.[0] || '?'}
                                </div>
                                <span style={{ fontSize: '13px' }}>{m.name}</span>
                                {m.pivot?.role !== 'member' && (
                                    <span style={{ fontSize: '11px', color: '#6366f1', marginLeft: 'auto' }}>
                                        {m.pivot.role}
                                    </span>
                                )}
                            </div>
                        ))}
                    </div>
                )}

                {/* Post composer */}
                {isMember && (
                    <div style={{ display: 'flex', gap: '8px', marginBottom: '16px' }}>
                        <input
                            placeholder="Share with this group..."
                            value={newPost}
                            onChange={e => setNewPost(e.target.value)}
                            onKeyDown={e => e.key === 'Enter' && submitPost()}
                            style={{
                                flex: 1, padding: '10px 14px', borderRadius: '20px',
                                background: '#1a1d24', color: '#fff', border: 'none', fontSize: '14px',
                            }}
                        />
                        <button
                            onClick={submitPost}
                            disabled={loading}
                            style={{
                                background: '#6366f1', color: '#fff', border: 'none',
                                borderRadius: '20px', padding: '10px 18px', cursor: 'pointer', fontSize: '14px',
                            }}
                        >
                            Post
                        </button>
                    </div>
                )}

                {/* Group Posts */}
                {posts.length === 0 && (
                    <p style={{ color: '#6b7280', textAlign: 'center', marginTop: '32px' }}>
                        No posts in this group yet.
                    </p>
                )}
                {posts.map(post => (
                    <div key={post.id} style={{
                        background: '#1a1d24', borderRadius: '12px', padding: '14px', marginBottom: '10px',
                    }}>
                        <div style={{ display: 'flex', alignItems: 'center', gap: '8px', marginBottom: '8px' }}>
                            <div style={{
                                width: '30px', height: '30px', borderRadius: '50%', background: '#374151',
                                display: 'flex', alignItems: 'center', justifyContent: 'center', fontSize: '13px',
                            }}>
                                {post.user?.name?.[0] || '?'}
                            </div>
                            <div>
                                <div style={{ fontSize: '13px', fontWeight: 600 }}>{post.user?.name || 'Unknown'}</div>
                                <div style={{ fontSize: '11px', color: '#9ca3af' }}>
                                    {new Date(post.created_at).toLocaleDateString()}
                                </div>
                            </div>
                        </div>
                        <p style={{ margin: 0, fontSize: '14px', color: '#d1d5db', lineHeight: 1.5 }}>{post.body}</p>
                        <div style={{ display: 'flex', gap: '16px', marginTop: '10px', fontSize: '13px', color: '#9ca3af' }}>
                            <span>❤️ {post.likes_count}</span>
                            <span>💬 {post.comments_count}</span>
                        </div>
                    </div>
                ))}
            </div>
        </div>
    );
}
