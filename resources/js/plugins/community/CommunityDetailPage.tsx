import React from 'react';
import { useParams, useNavigate } from 'react-router-dom';
import { useQuery, useMutation, useQueryClient } from '@tanstack/react-query';
import axios from 'axios';

interface Member {
    id: number; user_id: number; role: string; status: string;
    user: { id: number; name: string; avatar?: string };
}

const TYPE_BADGES: Record<string, { label: string; color: string }> = {
    small_group:   { label: 'Small Group',   color: '#6366f1' },
    prayer_circle: { label: 'Prayer Circle', color: '#8b5cf6' },
    bible_study:   { label: 'Bible Study',   color: '#0ea5e9' },
    ministry_team: { label: 'Ministry Team', color: '#10b981' },
    choir:         { label: 'Choir',         color: '#f59e0b' },
};

export default function CommunityDetailPage() {
    const { id }   = useParams<{ id: string }>();
    const navigate = useNavigate();
    const qc       = useQueryClient();

    const { data: community, isLoading, isError } = useQuery({
        queryKey: ['community', id],
        queryFn:  () => axios.get(`/api/v1/communities/${id}`).then(r => r.data),
    });

    const { data: members } = useQuery({
        queryKey: ['community-members', id],
        queryFn:  () => axios.get(`/api/v1/communities/${id}/members`).then(r => r.data),
        enabled: !!community,
        retry: false,
    });

    const approveMutation = useMutation({
        mutationFn: (userId: number) => axios.post(`/api/v1/communities/${id}/members/${userId}/approve`),
        onSuccess: () => qc.invalidateQueries({ queryKey: ['community-members', id] }),
    });

    const rejectMutation = useMutation({
        mutationFn: (userId: number) => axios.delete(`/api/v1/communities/${id}/members/${userId}/approve`),
        onSuccess: () => qc.invalidateQueries({ queryKey: ['community-members', id] }),
    });

    const banMutation = useMutation({
        mutationFn: (userId: number) => axios.post(`/api/v1/communities/${id}/members/${userId}/ban`),
        onSuccess: () => qc.invalidateQueries({ queryKey: ['community-members', id] }),
    });

    if (isLoading) {
        return (
            <div className="max-w-3xl mx-auto p-6">
                <div className="h-48 bg-gray-200 rounded-xl animate-pulse" />
            </div>
        );
    }

    if (isError || !community) {
        return (
            <div className="max-w-3xl mx-auto px-6 py-16 text-center">
                <p className="text-gray-500">Community not found.</p>
                <button onClick={() => navigate('/communities')} className="mt-4 text-blue-600 text-sm hover:underline">← Back</button>
            </div>
        );
    }

    const pending  = (members as Member[] | undefined)?.filter(m => m.status === 'pending')  ?? [];
    const approved = (members as Member[] | undefined)?.filter(m => m.status === 'approved') ?? [];

    return (
        <div className="max-w-3xl mx-auto">
            {/* Cover */}
            <div className="h-48 bg-gradient-to-br from-indigo-500 to-purple-600 rounded-b-xl overflow-hidden">
                {community.cover_image && <img src={community.cover_image} className="w-full h-full object-cover" alt="" />}
            </div>

            {/* Header */}
            <div className="px-6 py-4">
                <h1 className="text-2xl font-bold text-gray-900">{community.name}</h1>
                <p className="text-sm text-gray-500 mt-0.5">
                    {community.approved_members_count ?? community.members_count} members
                    {' · '}{community.privacy_closed ? 'Closed' : community.privacy}
                </p>
                {community.community_type && TYPE_BADGES[community.community_type] && (
                    <span className="inline-block text-xs font-semibold px-2 py-0.5 rounded-full mt-1" style={{
                        background: TYPE_BADGES[community.community_type].color + '1a',
                        color: TYPE_BADGES[community.community_type].color,
                    }}>
                        {TYPE_BADGES[community.community_type].label}
                    </span>
                )}
                {community.description && <p className="text-gray-700 mt-2 text-sm leading-relaxed">{community.description}</p>}
            </div>

            {/* Approval queue (admin only) */}
            {pending.length > 0 && (
                <div className="mx-6 mb-4 p-4 bg-amber-50 rounded-xl border border-amber-200">
                    <h2 className="text-sm font-semibold text-amber-800 mb-3">Join Requests ({pending.length})</h2>
                    {pending.map(m => (
                        <div key={m.user_id} className="flex items-center gap-3 mb-2">
                            <img
                                src={m.user.avatar ?? `https://ui-avatars.com/api/?name=${encodeURIComponent(m.user.name)}`}
                                className="w-8 h-8 rounded-full object-cover" alt=""
                            />
                            <span className="flex-1 text-sm font-medium text-gray-800">{m.user.name}</span>
                            <button
                                onClick={() => approveMutation.mutate(m.user_id)}
                                className="px-3 py-1 text-xs rounded-lg bg-green-500 text-white hover:bg-green-600"
                            >
                                Approve
                            </button>
                            <button
                                onClick={() => rejectMutation.mutate(m.user_id)}
                                className="px-3 py-1 text-xs rounded-lg bg-red-100 text-red-600 hover:bg-red-200"
                            >
                                Reject
                            </button>
                        </div>
                    ))}
                </div>
            )}

            {/* Members list */}
            {approved.length > 0 && (
                <div className="px-6 pb-6">
                    <h2 className="text-base font-semibold text-gray-900 mb-3">Members</h2>
                    {approved.map(m => (
                        <div key={m.user_id} className="flex items-center gap-3 mb-2">
                            <img
                                src={m.user.avatar ?? `https://ui-avatars.com/api/?name=${encodeURIComponent(m.user.name)}`}
                                className="w-8 h-8 rounded-full object-cover" alt=""
                            />
                            <span className="flex-1 text-sm font-medium text-gray-800">{m.user.name}</span>
                            <span className="text-xs text-gray-400 capitalize">{m.role}</span>
                            {m.role !== 'admin' && (
                                <button
                                    onClick={() => banMutation.mutate(m.user_id)}
                                    className="px-2 py-1 text-xs rounded bg-gray-100 text-gray-500 hover:bg-red-50 hover:text-red-500"
                                >
                                    Ban
                                </button>
                            )}
                        </div>
                    ))}
                </div>
            )}
        </div>
    );
}
