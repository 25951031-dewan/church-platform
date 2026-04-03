import React, { useState, useEffect, useCallback } from 'react';
import { get, post, put, del } from '../shared/api';

const STATUS_OPTIONS = ['all', 'published', 'pending', 'flagged', 'removed'];

export default function CommunityManager() {
    const [posts, setPosts] = useState([]);
    const [groups, setGroups] = useState([]);
    const [loading, setLoading] = useState(false);
    const [tab, setTab] = useState('posts'); // posts | groups
    const [statusFilter, setStatusFilter] = useState('all');
    const [selectedPost, setSelectedPost] = useState(null);

    const loadPosts = useCallback(async () => {
        setLoading(true);
        try {
            const params = new URLSearchParams();
            if (statusFilter !== 'all') params.set('status', statusFilter);
            const data = await get(`/api/community/feed?${params}`);
            setPosts(data?.data || []);
        } catch (e) {
            console.error('Failed to load posts', e);
        }
        setLoading(false);
    }, [statusFilter]);

    const loadGroups = useCallback(async () => {
        setLoading(true);
        try {
            const data = await get('/api/community/groups');
            setGroups(data?.data || []);
        } catch (e) {
            console.error('Failed to load groups', e);
        }
        setLoading(false);
    }, []);

    useEffect(() => {
        if (tab === 'posts') loadPosts();
        else loadGroups();
    }, [tab, loadPosts, loadGroups]);

    const moderatePost = async (postId, action) => {
        const statusMap = { approve: 'published', flag: 'flagged', remove: 'removed' };
        try {
            await put(`/api/community/posts/${postId}`, { status: statusMap[action] });
            loadPosts();
        } catch (e) {
            alert('Action failed: ' + (e.message || 'Unknown error'));
        }
    };

    const deletePost = async (postId) => {
        if (!confirm('Delete this post permanently?')) return;
        try {
            await del(`/api/community/posts/${postId}`);
            setPosts(prev => prev.filter(p => p.id !== postId));
            setSelectedPost(null);
        } catch (e) {
            alert('Delete failed: ' + (e.message || 'Unknown error'));
        }
    };

    const deleteGroup = async (groupId) => {
        if (!confirm('Delete this group and all its posts?')) return;
        try {
            await del(`/api/community/groups/${groupId}`);
            setGroups(prev => prev.filter(g => g.id !== groupId));
        } catch (e) {
            alert('Delete failed: ' + (e.message || 'Unknown error'));
        }
    };

    return (
        <div>
            <h2 className="text-xl font-bold mb-4">Community Management</h2>

            {/* Tabs */}
            <div className="flex gap-2 mb-4">
                {['posts', 'groups'].map(t => (
                    <button
                        key={t}
                        onClick={() => setTab(t)}
                        className={`px-4 py-2 rounded-lg text-sm font-medium ${
                            tab === t ? 'bg-indigo-600 text-white' : 'bg-gray-700 text-gray-300'
                        }`}
                    >
                        {t === 'posts' ? 'Posts' : 'Groups'}
                    </button>
                ))}
            </div>

            {tab === 'posts' && (
                <>
                    {/* Status filter */}
                    <div className="flex gap-2 mb-4 flex-wrap">
                        {STATUS_OPTIONS.map(s => (
                            <button
                                key={s}
                                onClick={() => setStatusFilter(s)}
                                className={`px-3 py-1 rounded-full text-xs ${
                                    statusFilter === s ? 'bg-indigo-600 text-white' : 'bg-gray-700 text-gray-400'
                                }`}
                            >
                                {s.charAt(0).toUpperCase() + s.slice(1)}
                            </button>
                        ))}
                    </div>

                    {loading && <p className="text-gray-400">Loading posts...</p>}

                    <div className="space-y-3">
                        {posts.map(p => (
                            <div key={p.id} className="bg-gray-800 rounded-lg p-4">
                                <div className="flex justify-between items-start mb-2">
                                    <div>
                                        <span className="text-xs bg-gray-700 px-2 py-0.5 rounded mr-2">{p.type}</span>
                                        <span className="text-xs text-gray-400">
                                            {p.is_anonymous ? 'Anonymous' : (p.user?.name || 'Unknown')}
                                        </span>
                                    </div>
                                    <span className={`text-xs px-2 py-0.5 rounded ${
                                        p.status === 'published' ? 'bg-green-900 text-green-400' :
                                        p.status === 'flagged' ? 'bg-yellow-900 text-yellow-400' :
                                        p.status === 'removed' ? 'bg-red-900 text-red-400' :
                                        'bg-gray-700 text-gray-400'
                                    }`}>
                                        {p.status}
                                    </span>
                                </div>
                                {p.title && <h3 className="font-semibold text-sm mb-1">{p.title}</h3>}
                                <p className="text-sm text-gray-300 mb-3">{p.body.substring(0, 200)}{p.body.length > 200 ? '...' : ''}</p>
                                <div className="flex gap-3 text-xs text-gray-400 mb-3">
                                    <span>❤️ {p.likes_count}</span>
                                    <span>💬 {p.comments_count}</span>
                                    <span>🔗 {p.shares_count}</span>
                                    <span>{new Date(p.created_at).toLocaleDateString()}</span>
                                </div>
                                <div className="flex gap-2">
                                    {p.status !== 'published' && (
                                        <button onClick={() => moderatePost(p.id, 'approve')} className="px-3 py-1 bg-green-700 text-white rounded text-xs">Approve</button>
                                    )}
                                    {p.status !== 'flagged' && (
                                        <button onClick={() => moderatePost(p.id, 'flag')} className="px-3 py-1 bg-yellow-700 text-white rounded text-xs">Flag</button>
                                    )}
                                    {p.status !== 'removed' && (
                                        <button onClick={() => moderatePost(p.id, 'remove')} className="px-3 py-1 bg-red-700 text-white rounded text-xs">Remove</button>
                                    )}
                                    <button onClick={() => deletePost(p.id)} className="px-3 py-1 bg-gray-600 text-white rounded text-xs">Delete</button>
                                </div>
                            </div>
                        ))}
                    </div>
                </>
            )}

            {tab === 'groups' && (
                <>
                    {loading && <p className="text-gray-400">Loading groups...</p>}
                    <div className="space-y-3">
                        {groups.map(g => (
                            <div key={g.id} className="bg-gray-800 rounded-lg p-4">
                                <div className="flex justify-between items-start mb-2">
                                    <div>
                                        <h3 className="font-semibold">{g.name}</h3>
                                        <span className="text-xs text-gray-400">
                                            {g.type} · {g.members_count || 0} members · Created by {g.creator?.name || 'Unknown'}
                                        </span>
                                    </div>
                                    <button
                                        onClick={() => deleteGroup(g.id)}
                                        className="px-3 py-1 bg-red-700 text-white rounded text-xs"
                                    >
                                        Delete
                                    </button>
                                </div>
                                {g.description && <p className="text-sm text-gray-300 mt-2">{g.description}</p>}
                            </div>
                        ))}
                        {!loading && groups.length === 0 && <p className="text-gray-400">No groups yet.</p>}
                    </div>
                </>
            )}
        </div>
    );
}
