import React, { useState, useEffect, useCallback } from 'react';
import { get, put } from '../shared/api';

const STATUS_COLORS = {
    open: 'bg-yellow-900 text-yellow-400',
    in_progress: 'bg-blue-900 text-blue-400',
    resolved: 'bg-green-900 text-green-400',
    closed: 'bg-gray-700 text-gray-400',
};

const PRIORITY_COLORS = {
    low: 'text-gray-400',
    normal: 'text-blue-400',
    high: 'text-yellow-400',
    urgent: 'text-red-400',
};

export default function CounselingManager() {
    const [threads, setThreads] = useState([]);
    const [loading, setLoading] = useState(false);
    const [statusFilter, setStatusFilter] = useState('');
    const [selectedThread, setSelectedThread] = useState(null);
    const [counselors, setCounselors] = useState([]);
    const [assignCounselorId, setAssignCounselorId] = useState('');

    const loadThreads = useCallback(async () => {
        setLoading(true);
        try {
            const params = new URLSearchParams();
            if (statusFilter) params.set('status', statusFilter);
            const data = await get(`/api/counseling/admin/threads?${params}`);
            setThreads(data?.data || []);
        } catch (e) {
            console.error('Failed to load threads', e);
        }
        setLoading(false);
    }, [statusFilter]);

    const loadCounselors = useCallback(async () => {
        try {
            const data = await get('/api/users?role=counsellor');
            setCounselors(data?.data || data || []);
        } catch (e) {
            console.error('Failed to load counselors', e);
        }
    }, []);

    useEffect(() => { loadThreads(); }, [loadThreads]);
    useEffect(() => { loadCounselors(); }, [loadCounselors]);

    const viewThread = async (thread) => {
        try {
            const data = await get(`/api/counseling/threads/${thread.id}`);
            setSelectedThread(data);
        } catch (e) {
            alert('Failed to load thread details');
        }
    };

    const assignCounselor = async (threadId) => {
        if (!assignCounselorId) return;
        try {
            await put(`/api/counseling/threads/${threadId}/assign`, { counselor_id: parseInt(assignCounselorId) });
            setAssignCounselorId('');
            loadThreads();
            if (selectedThread?.id === threadId) {
                viewThread({ id: threadId });
            }
        } catch (e) {
            alert('Assignment failed: ' + (e.message || 'Unknown error'));
        }
    };

    const updateStatus = async (threadId, status) => {
        try {
            await put(`/api/counseling/threads/${threadId}/status`, { status });
            loadThreads();
            if (selectedThread?.id === threadId) {
                setSelectedThread(prev => ({ ...prev, status }));
            }
        } catch (e) {
            alert('Status update failed');
        }
    };

    // Stats
    const stats = {
        open: threads.filter(t => t.status === 'open').length,
        in_progress: threads.filter(t => t.status === 'in_progress').length,
        resolved: threads.filter(t => t.status === 'resolved').length,
        total: threads.length,
    };

    return (
        <div>
            <h2 className="text-xl font-bold mb-4">Counseling Management</h2>

            {/* Stats */}
            <div className="grid grid-cols-4 gap-3 mb-6">
                {[
                    { label: 'Open', value: stats.open, color: 'bg-yellow-900' },
                    { label: 'In Progress', value: stats.in_progress, color: 'bg-blue-900' },
                    { label: 'Resolved', value: stats.resolved, color: 'bg-green-900' },
                    { label: 'Total', value: stats.total, color: 'bg-gray-700' },
                ].map(s => (
                    <div key={s.label} className={`${s.color} rounded-lg p-3 text-center`}>
                        <div className="text-2xl font-bold">{s.value}</div>
                        <div className="text-xs text-gray-300">{s.label}</div>
                    </div>
                ))}
            </div>

            {/* Status filter */}
            <div className="flex gap-2 mb-4 flex-wrap">
                {['', 'open', 'in_progress', 'resolved', 'closed'].map(s => (
                    <button
                        key={s}
                        onClick={() => setStatusFilter(s)}
                        className={`px-3 py-1 rounded-full text-xs ${
                            statusFilter === s ? 'bg-indigo-600 text-white' : 'bg-gray-700 text-gray-400'
                        }`}
                    >
                        {s ? s.replace('_', ' ').replace(/\b\w/g, c => c.toUpperCase()) : 'All'}
                    </button>
                ))}
            </div>

            <div className="flex gap-4">
                {/* Thread list */}
                <div className="flex-1">
                    {loading && <p className="text-gray-400">Loading...</p>}
                    <div className="space-y-3">
                        {threads.map(t => (
                            <button
                                key={t.id}
                                onClick={() => viewThread(t)}
                                className={`w-full text-left bg-gray-800 rounded-lg p-4 border-2 ${
                                    selectedThread?.id === t.id ? 'border-indigo-500' : 'border-transparent'
                                }`}
                            >
                                <div className="flex justify-between items-start mb-2">
                                    <h3 className="font-semibold text-sm">{t.subject}</h3>
                                    <span className={`text-xs px-2 py-0.5 rounded ${STATUS_COLORS[t.status]}`}>
                                        {t.status.replace('_', ' ')}
                                    </span>
                                </div>
                                <div className="flex justify-between text-xs text-gray-400">
                                    <span>
                                        {t.is_anonymous ? 'Anonymous' : (t.requester?.name || 'Unknown')}
                                        {t.counselor ? ` → ${t.counselor.name}` : ' (Unassigned)'}
                                    </span>
                                    <span className={PRIORITY_COLORS[t.priority]}>{t.priority}</span>
                                </div>
                                {t.latest_message && (
                                    <p className="text-xs text-gray-500 mt-2 truncate">{t.latest_message.body}</p>
                                )}
                            </button>
                        ))}
                        {!loading && threads.length === 0 && <p className="text-gray-400">No threads found.</p>}
                    </div>
                </div>

                {/* Thread detail */}
                {selectedThread && (
                    <div className="w-96 bg-gray-800 rounded-lg p-4">
                        <div className="flex justify-between items-start mb-4">
                            <h3 className="font-bold">{selectedThread.subject}</h3>
                            <button onClick={() => setSelectedThread(null)} className="text-gray-400 text-lg">×</button>
                        </div>

                        <div className="space-y-2 mb-4 text-sm">
                            <div className="flex justify-between">
                                <span className="text-gray-400">Status:</span>
                                <span className={`px-2 py-0.5 rounded text-xs ${STATUS_COLORS[selectedThread.status]}`}>
                                    {selectedThread.status.replace('_', ' ')}
                                </span>
                            </div>
                            <div className="flex justify-between">
                                <span className="text-gray-400">Priority:</span>
                                <span className={PRIORITY_COLORS[selectedThread.priority]}>{selectedThread.priority}</span>
                            </div>
                            <div className="flex justify-between">
                                <span className="text-gray-400">Requester:</span>
                                <span>{selectedThread.is_anonymous ? 'Anonymous' : (selectedThread.requester?.name || '-')}</span>
                            </div>
                            <div className="flex justify-between">
                                <span className="text-gray-400">Counselor:</span>
                                <span>{selectedThread.counselor?.name || 'Unassigned'}</span>
                            </div>
                        </div>

                        {/* Assign counselor */}
                        <div className="mb-4">
                            <label className="text-xs text-gray-400 block mb-1">Assign Counselor</label>
                            <div className="flex gap-2">
                                <select
                                    value={assignCounselorId}
                                    onChange={e => setAssignCounselorId(e.target.value)}
                                    className="flex-1 bg-gray-700 text-white rounded px-2 py-1 text-sm"
                                >
                                    <option value="">Select...</option>
                                    {counselors.map(c => (
                                        <option key={c.id} value={c.id}>{c.name}</option>
                                    ))}
                                </select>
                                <button
                                    onClick={() => assignCounselor(selectedThread.id)}
                                    className="px-3 py-1 bg-indigo-600 text-white rounded text-sm"
                                >
                                    Assign
                                </button>
                            </div>
                        </div>

                        {/* Status actions */}
                        <div className="mb-4">
                            <label className="text-xs text-gray-400 block mb-1">Change Status</label>
                            <div className="flex gap-2 flex-wrap">
                                {['open', 'in_progress', 'resolved', 'closed'].filter(s => s !== selectedThread.status).map(s => (
                                    <button
                                        key={s}
                                        onClick={() => updateStatus(selectedThread.id, s)}
                                        className="px-3 py-1 bg-gray-700 text-white rounded text-xs hover:bg-gray-600"
                                    >
                                        {s.replace('_', ' ')}
                                    </button>
                                ))}
                            </div>
                        </div>

                        {/* Messages */}
                        {selectedThread.messages && (
                            <div>
                                <label className="text-xs text-gray-400 block mb-2">Messages ({selectedThread.messages.length})</label>
                                <div className="space-y-2 max-h-64 overflow-y-auto">
                                    {selectedThread.messages.map(m => (
                                        <div key={m.id} className="bg-gray-700 rounded p-2">
                                            <div className="flex justify-between text-xs text-gray-400 mb-1">
                                                <span>{m.sender?.name || 'Unknown'}</span>
                                                <span>{new Date(m.created_at).toLocaleString()}</span>
                                            </div>
                                            <p className="text-sm">{m.body}</p>
                                        </div>
                                    ))}
                                </div>
                            </div>
                        )}
                    </div>
                )}
            </div>
        </div>
    );
}
