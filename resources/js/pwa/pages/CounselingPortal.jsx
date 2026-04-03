import React, { useState, useEffect, useCallback, useRef } from 'react';
import { useAuth } from '../hooks/useAuth';
import { useApi } from '../hooks/useApi';

const STATUS_COLORS = {
    open: '#f59e0b',
    in_progress: '#3b82f6',
    resolved: '#10b981',
    closed: '#6b7280',
};

const PRIORITY_LABELS = {
    low: { label: 'Low', color: '#6b7280' },
    normal: { label: 'Normal', color: '#3b82f6' },
    high: { label: 'High', color: '#f59e0b' },
    urgent: { label: 'Urgent', color: '#ef4444' },
};

export default function CounselingPortal() {
    const { user } = useAuth();
    const { loading, request } = useApi();
    const [view, setView] = useState('threads'); // threads | chat | new
    const [threads, setThreads] = useState([]);
    const [activeThread, setActiveThread] = useState(null);
    const [messages, setMessages] = useState([]);
    const [messageText, setMessageText] = useState('');
    const [newRequest, setNewRequest] = useState({ subject: '', body: '', priority: 'normal', is_anonymous: false });
    const messagesEndRef = useRef(null);

    const loadThreads = useCallback(async () => {
        const data = await request('/api/counseling/my-threads');
        if (data?.data) setThreads(data.data);
    }, [request]);

    useEffect(() => { if (user) loadThreads(); }, [user, loadThreads]);

    useEffect(() => {
        messagesEndRef.current?.scrollIntoView({ behavior: 'smooth' });
    }, [messages]);

    const openThread = async (thread) => {
        setActiveThread(thread);
        setView('chat');
        const data = await request(`/api/counseling/threads/${thread.id}`);
        if (data?.messages) setMessages(data.messages);
    };

    const sendMessage = async () => {
        if (!messageText.trim() || !activeThread) return;
        const data = await request(`/api/counseling/threads/${activeThread.id}/messages`, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ body: messageText }),
        });
        if (data?.id) {
            setMessages(prev => [...prev, data]);
            setMessageText('');
        }
    };

    const submitRequest = async (e) => {
        e.preventDefault();
        if (!newRequest.subject.trim() || !newRequest.body.trim()) return;
        const data = await request('/api/counseling/request', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(newRequest),
        });
        if (data?.id) {
            setNewRequest({ subject: '', body: '', priority: 'normal', is_anonymous: false });
            setView('threads');
            loadThreads();
        }
    };

    if (!user) {
        return (
            <div style={{ padding: '16px', textAlign: 'center', color: '#9ca3af', marginTop: '40px' }}>
                <p style={{ fontSize: '18px', marginBottom: '8px' }}>🤝</p>
                <p>Please log in to access counseling services.</p>
            </div>
        );
    }

    // Chat view
    if (view === 'chat' && activeThread) {
        return (
            <div style={{ display: 'flex', flexDirection: 'column', height: 'calc(100vh - 120px)' }}>
                {/* Chat header */}
                <div style={{
                    padding: '12px 16px', background: '#1a1d24',
                    display: 'flex', alignItems: 'center', gap: '12px',
                    borderBottom: '1px solid #2a2d34',
                }}>
                    <button
                        onClick={() => { setView('threads'); setActiveThread(null); }}
                        style={{ background: 'none', border: 'none', color: '#fff', fontSize: '18px', cursor: 'pointer' }}
                    >
                        ←
                    </button>
                    <div style={{ flex: 1 }}>
                        <div style={{ fontSize: '15px', fontWeight: 600 }}>{activeThread.subject}</div>
                        <div style={{ fontSize: '12px', color: STATUS_COLORS[activeThread.status] }}>
                            {activeThread.status.replace('_', ' ')}
                            {activeThread.counselor && ` · ${activeThread.counselor.name}`}
                        </div>
                    </div>
                </div>

                {/* Messages */}
                <div style={{ flex: 1, overflowY: 'auto', padding: '16px' }}>
                    {messages.map(msg => {
                        const isMe = msg.sender_id === user.id;
                        return (
                            <div key={msg.id} style={{
                                display: 'flex', justifyContent: isMe ? 'flex-end' : 'flex-start',
                                marginBottom: '10px',
                            }}>
                                <div style={{
                                    maxWidth: '80%', padding: '10px 14px', borderRadius: '16px',
                                    background: isMe ? '#6366f1' : '#2a2d34',
                                    borderBottomRightRadius: isMe ? '4px' : '16px',
                                    borderBottomLeftRadius: isMe ? '16px' : '4px',
                                }}>
                                    {!isMe && (
                                        <div style={{ fontSize: '11px', color: '#9ca3af', marginBottom: '4px' }}>
                                            {msg.sender?.name || 'Counselor'}
                                        </div>
                                    )}
                                    <p style={{ margin: 0, fontSize: '14px', lineHeight: 1.5 }}>{msg.body}</p>
                                    <div style={{ fontSize: '10px', color: isMe ? 'rgba(255,255,255,0.6)' : '#6b7280', marginTop: '4px', textAlign: 'right' }}>
                                        {new Date(msg.created_at).toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' })}
                                    </div>
                                </div>
                            </div>
                        );
                    })}
                    <div ref={messagesEndRef} />
                </div>

                {/* Message input */}
                {['open', 'in_progress'].includes(activeThread.status) && (
                    <div style={{
                        padding: '12px 16px', background: '#1a1d24',
                        display: 'flex', gap: '8px', borderTop: '1px solid #2a2d34',
                    }}>
                        <input
                            placeholder="Type a message..."
                            value={messageText}
                            onChange={e => setMessageText(e.target.value)}
                            onKeyDown={e => e.key === 'Enter' && sendMessage()}
                            style={{
                                flex: 1, padding: '10px 14px', borderRadius: '20px',
                                background: '#2a2d34', color: '#fff', border: 'none', fontSize: '14px',
                            }}
                        />
                        <button
                            onClick={sendMessage}
                            disabled={loading}
                            style={{
                                background: '#6366f1', color: '#fff', border: 'none',
                                borderRadius: '50%', width: '40px', height: '40px',
                                cursor: 'pointer', fontSize: '16px',
                            }}
                        >
                            ↑
                        </button>
                    </div>
                )}
            </div>
        );
    }

    // New request form
    if (view === 'new') {
        return (
            <div style={{ padding: '16px', paddingBottom: '80px' }}>
                <div style={{ display: 'flex', alignItems: 'center', gap: '12px', marginBottom: '20px' }}>
                    <button
                        onClick={() => setView('threads')}
                        style={{ background: 'none', border: 'none', color: '#fff', fontSize: '18px', cursor: 'pointer' }}
                    >
                        ←
                    </button>
                    <h2 style={{ margin: 0, fontSize: '20px' }}>New Counseling Request</h2>
                </div>

                <form onSubmit={submitRequest} style={{
                    background: '#1a1d24', borderRadius: '12px', padding: '16px',
                }}>
                    <input
                        placeholder="Subject"
                        value={newRequest.subject}
                        onChange={e => setNewRequest(p => ({ ...p, subject: e.target.value }))}
                        required
                        style={{ width: '100%', padding: '10px', marginBottom: '12px', borderRadius: '8px', background: '#2a2d34', color: '#fff', border: 'none', boxSizing: 'border-box' }}
                    />

                    <textarea
                        placeholder="Describe what you need help with..."
                        value={newRequest.body}
                        onChange={e => setNewRequest(p => ({ ...p, body: e.target.value }))}
                        required
                        rows={6}
                        style={{ width: '100%', padding: '10px', marginBottom: '12px', borderRadius: '8px', background: '#2a2d34', color: '#fff', border: 'none', resize: 'vertical', boxSizing: 'border-box' }}
                    />

                    <div style={{ marginBottom: '12px' }}>
                        <label style={{ fontSize: '13px', color: '#9ca3af', display: 'block', marginBottom: '6px' }}>Priority</label>
                        <select
                            value={newRequest.priority}
                            onChange={e => setNewRequest(p => ({ ...p, priority: e.target.value }))}
                            style={{ width: '100%', padding: '10px', borderRadius: '8px', background: '#2a2d34', color: '#fff', border: 'none' }}
                        >
                            {Object.entries(PRIORITY_LABELS).map(([key, val]) => (
                                <option key={key} value={key}>{val.label}</option>
                            ))}
                        </select>
                    </div>

                    <label style={{ fontSize: '13px', color: '#9ca3af', display: 'flex', alignItems: 'center', gap: '8px', marginBottom: '16px' }}>
                        <input
                            type="checkbox"
                            checked={newRequest.is_anonymous}
                            onChange={e => setNewRequest(p => ({ ...p, is_anonymous: e.target.checked }))}
                        />
                        Submit anonymously
                    </label>

                    <button type="submit" disabled={loading} style={{
                        width: '100%', padding: '12px', background: '#6366f1', color: '#fff',
                        border: 'none', borderRadius: '8px', fontSize: '15px', cursor: 'pointer',
                    }}>
                        {loading ? 'Submitting...' : 'Submit Request'}
                    </button>
                </form>

                <p style={{ fontSize: '12px', color: '#6b7280', marginTop: '12px', lineHeight: 1.5 }}>
                    Your request will be reviewed and assigned to a counselor. All conversations are private and encrypted.
                </p>
            </div>
        );
    }

    // Thread list view
    return (
        <div style={{ padding: '16px', paddingBottom: '80px' }}>
            <div style={{ display: 'flex', justifyContent: 'space-between', alignItems: 'center', marginBottom: '16px' }}>
                <h2 style={{ margin: 0, fontSize: '20px' }}>Counseling</h2>
                <button
                    onClick={() => setView('new')}
                    style={{
                        background: '#6366f1', color: '#fff', border: 'none', borderRadius: '20px',
                        padding: '8px 16px', fontSize: '14px', cursor: 'pointer',
                    }}
                >
                    + New Request
                </button>
            </div>

            {loading && threads.length === 0 && <p style={{ color: '#9ca3af', textAlign: 'center' }}>Loading...</p>}
            {!loading && threads.length === 0 && (
                <div style={{ textAlign: 'center', marginTop: '40px', color: '#9ca3af' }}>
                    <p style={{ fontSize: '36px', marginBottom: '12px' }}>🤝</p>
                    <p style={{ fontSize: '15px' }}>No counseling threads yet.</p>
                    <p style={{ fontSize: '13px' }}>Your conversations are private and encrypted.</p>
                </div>
            )}

            {threads.map(thread => (
                <button
                    key={thread.id}
                    onClick={() => openThread(thread)}
                    style={{
                        width: '100%', textAlign: 'left', background: '#1a1d24',
                        borderRadius: '12px', padding: '14px', marginBottom: '10px',
                        border: 'none', color: '#fff', cursor: 'pointer',
                    }}
                >
                    <div style={{ display: 'flex', justifyContent: 'space-between', alignItems: 'flex-start', marginBottom: '6px' }}>
                        <div style={{ fontSize: '15px', fontWeight: 600, flex: 1 }}>{thread.subject}</div>
                        <span style={{
                            fontSize: '11px', padding: '2px 8px', borderRadius: '10px',
                            background: STATUS_COLORS[thread.status] + '20',
                            color: STATUS_COLORS[thread.status],
                            whiteSpace: 'nowrap', marginLeft: '8px',
                        }}>
                            {thread.status.replace('_', ' ')}
                        </span>
                    </div>
                    <div style={{ display: 'flex', justifyContent: 'space-between', alignItems: 'center' }}>
                        <span style={{ fontSize: '12px', color: '#9ca3af' }}>
                            {thread.counselor ? `Counselor: ${thread.counselor.name}` : 'Awaiting assignment'}
                        </span>
                        <span style={{ fontSize: '11px', color: PRIORITY_LABELS[thread.priority]?.color || '#6b7280' }}>
                            {PRIORITY_LABELS[thread.priority]?.label || thread.priority}
                        </span>
                    </div>
                    {thread.latest_message && (
                        <p style={{ margin: '6px 0 0', fontSize: '13px', color: '#6b7280', overflow: 'hidden', textOverflow: 'ellipsis', whiteSpace: 'nowrap' }}>
                            {thread.latest_message.body}
                        </p>
                    )}
                </button>
            ))}
        </div>
    );
}
