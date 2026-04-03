import React, { useState, useEffect } from 'react';
import { Modal, DataTable, Pagination, FormField, Alert } from '../shared/CrudPanel';
import { get, post, put, del, extractPaginatedData } from '../shared/api';

export default function NewsletterManager() {
    const [activeTab, setActiveTab] = useState('subscribers');

    // Subscribers state
    const [subscribers, setSubscribers] = useState([]);
    const [subMeta, setSubMeta] = useState(null);
    const [search, setSearch] = useState('');
    const [searchTimeout, setSearchTimeout] = useState(null);

    // Templates state
    const [templates, setTemplates] = useState([]);
    const [tplMeta, setTplMeta] = useState(null);
    const [showModal, setShowModal] = useState(false);
    const [editing, setEditing] = useState(null);
    const [form, setForm] = useState({ name: '', subject: '', body: '', type: 'general' });
    const [showPreview, setShowPreview] = useState(false);
    const [previewTemplate, setPreviewTemplate] = useState(null);

    const [alert, setAlert] = useState(null);
    const [loading, setLoading] = useState(false);

    // Fetch subscribers
    const fetchSubscribers = async (page = 1, query = search) => {
        setLoading(true);
        try {
            let url = `/api/newsletter/subscribers?page=${page}`;
            if (query) url += `&search=${encodeURIComponent(query)}`;
            const data = await get(url);
            const { items, meta } = extractPaginatedData(data);
            setSubscribers(items);
            setSubMeta(meta);
        } catch (e) {
            setAlert({ type: 'error', message: e.message });
        }
        setLoading(false);
    };

    // Fetch templates
    const fetchTemplates = async (page = 1) => {
        setLoading(true);
        try {
            const data = await get(`/api/newsletter/templates?page=${page}`);
            const { items, meta } = extractPaginatedData(data);
            setTemplates(items);
            setTplMeta(meta);
        } catch (e) {
            setAlert({ type: 'error', message: e.message });
        }
        setLoading(false);
    };

    useEffect(() => {
        fetchSubscribers();
        fetchTemplates();
    }, []);

    const handleSearch = (e) => {
        const value = e.target.value;
        setSearch(value);
        if (searchTimeout) clearTimeout(searchTimeout);
        const timeout = setTimeout(() => fetchSubscribers(1, value), 400);
        setSearchTimeout(timeout);
    };

    const handleChange = (e) => {
        setForm({ ...form, [e.target.name]: e.target.value });
    };

    // Template CRUD
    const openCreate = () => {
        setEditing(null);
        setForm({ name: '', subject: '', body: '', type: 'general' });
        setShowModal(true);
    };

    const openEdit = (item) => {
        setEditing(item);
        setForm({
            name: item.name || '',
            subject: item.subject || '',
            body: item.body || '',
            type: item.type || 'general',
        });
        setShowModal(true);
    };

    const handleSubmit = async (e) => {
        e.preventDefault();
        try {
            if (editing) {
                await put(`/api/newsletter/templates/${editing.id}`, form);
                setAlert({ type: 'success', message: 'Template updated successfully.' });
            } else {
                await post('/api/newsletter/templates', form);
                setAlert({ type: 'success', message: 'Template created successfully.' });
            }
            setShowModal(false);
            setEditing(null);
            fetchTemplates();
        } catch (e) {
            setAlert({ type: 'error', message: e.message });
        }
    };

    const handleDeleteTemplate = async (item) => {
        if (!confirm('Delete this template?')) return;
        try {
            await del(`/api/newsletter/templates/${item.id}`);
            setAlert({ type: 'success', message: 'Template deleted.' });
            fetchTemplates();
        } catch (e) {
            setAlert({ type: 'error', message: e.message });
        }
    };

    const handleSend = async (item) => {
        if (!confirm(`Send "${item.subject}" to all active subscribers? This action cannot be undone.`)) return;
        try {
            const res = await post(`/api/newsletter/templates/${item.id}/send`, {});
            setAlert({ type: 'success', message: res.message || 'Newsletter sent!' });
            fetchTemplates();
        } catch (e) {
            setAlert({ type: 'error', message: e.message });
        }
    };

    const handleExport = () => {
        window.open('/api/newsletter/subscribers/export', '_blank');
    };

    const openPreview = (item) => {
        setPreviewTemplate(item);
        setShowPreview(true);
    };

    const activeCount = subscribers.filter(i => i.is_active).length;
    const totalSubscribers = subMeta?.total || subscribers.length;

    // Subscriber columns
    const subscriberColumns = [
        { key: 'email', label: 'Email' },
        { key: 'name', label: 'Name', render: (r) => r.name || '-' },
        {
            key: 'is_active', label: 'Status',
            render: (r) => (
                <span className={`inline-flex px-2 py-0.5 rounded-full text-xs font-medium ${r.is_active ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800'}`}>
                    {r.is_active ? 'Active' : 'Unsubscribed'}
                </span>
            ),
        },
        {
            key: 'subscribed_at', label: 'Subscribed',
            render: (r) => r.subscribed_at ? new Date(r.subscribed_at).toLocaleDateString() : (r.created_at ? new Date(r.created_at).toLocaleDateString() : '-'),
        },
    ];

    // Template columns
    const templateColumns = [
        { key: 'name', label: 'Name' },
        { key: 'subject', label: 'Subject' },
        {
            key: 'type', label: 'Type',
            render: (r) => (
                <span className="inline-flex px-2 py-0.5 rounded-full text-xs font-medium bg-indigo-100 text-indigo-800 capitalize">
                    {r.type || 'general'}
                </span>
            ),
        },
        {
            key: 'sent_at', label: 'Sent',
            render: (r) => r.sent_at ? (
                <span className="text-green-600 text-xs">
                    {new Date(r.sent_at).toLocaleDateString()} ({r.recipients_count} recipients)
                </span>
            ) : <span className="text-gray-400 text-xs">Not sent</span>,
        },
    ];

    const templateTypes = [
        { value: 'general', label: 'General' },
        { value: 'announcement', label: 'Announcement' },
        { value: 'event', label: 'Event' },
        { value: 'devotional', label: 'Devotional' },
        { value: 'welcome', label: 'Welcome' },
    ];

    return (
        <div>
            <div className="flex items-center justify-between mb-6">
                <div>
                    <h2 className="text-2xl font-bold text-gray-800">Newsletter Manager</h2>
                    <p className="text-sm text-gray-500 mt-1">Manage subscribers, create templates, and send newsletters.</p>
                </div>
            </div>
            <Alert {...alert} onClose={() => setAlert(null)} />

            {/* Tabs */}
            <div className="flex gap-1 mb-6 bg-gray-100 rounded-lg p-1 w-fit">
                <button
                    onClick={() => setActiveTab('subscribers')}
                    className={`px-4 py-2 rounded-md text-sm font-medium transition-colors ${activeTab === 'subscribers' ? 'bg-white text-gray-900 shadow-sm' : 'text-gray-500 hover:text-gray-700'}`}
                >
                    Subscribers ({totalSubscribers})
                </button>
                <button
                    onClick={() => setActiveTab('templates')}
                    className={`px-4 py-2 rounded-md text-sm font-medium transition-colors ${activeTab === 'templates' ? 'bg-white text-gray-900 shadow-sm' : 'text-gray-500 hover:text-gray-700'}`}
                >
                    Templates ({templates.length})
                </button>
            </div>

            {/* SUBSCRIBERS TAB */}
            {activeTab === 'subscribers' && (
                <div>
                    {/* Stats Cards */}
                    <div className="grid grid-cols-1 sm:grid-cols-3 gap-4 mb-6">
                        <div className="bg-white rounded-xl shadow-sm border p-4">
                            <div className="text-sm font-medium text-gray-500">Total Subscribers</div>
                            <div className="text-2xl font-bold text-gray-900 mt-1">{totalSubscribers}</div>
                        </div>
                        <div className="bg-white rounded-xl shadow-sm border p-4">
                            <div className="text-sm font-medium text-gray-500">Active (this page)</div>
                            <div className="text-2xl font-bold text-green-600 mt-1">{activeCount}</div>
                        </div>
                        <div className="bg-white rounded-xl shadow-sm border p-4">
                            <div className="text-sm font-medium text-gray-500">Unsubscribed (this page)</div>
                            <div className="text-2xl font-bold text-red-600 mt-1">{subscribers.length - activeCount}</div>
                        </div>
                    </div>

                    <div className="bg-white rounded-xl shadow-sm border">
                        <div className="p-4 border-b flex items-center justify-between gap-4">
                            <div className="relative flex-1">
                                <svg className="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                                </svg>
                                <input
                                    type="text"
                                    value={search}
                                    onChange={handleSearch}
                                    placeholder="Search by name or email..."
                                    className="w-full pl-10 pr-4 py-2.5 rounded-lg border border-gray-300 focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 outline-none text-sm"
                                />
                            </div>
                            <button
                                onClick={handleExport}
                                className="px-4 py-2.5 bg-green-600 text-white rounded-lg hover:bg-green-700 text-sm font-medium whitespace-nowrap"
                            >
                                <i className="fas fa-download mr-2"></i>Export CSV
                            </button>
                        </div>
                        {loading ? (
                            <div className="text-center py-12 text-gray-500">Loading subscribers...</div>
                        ) : (
                            <DataTable columns={subscriberColumns} data={subscribers} />
                        )}
                        <Pagination meta={subMeta} onPageChange={fetchSubscribers} />
                    </div>
                </div>
            )}

            {/* TEMPLATES TAB */}
            {activeTab === 'templates' && (
                <div>
                    <div className="flex justify-end mb-4">
                        <button onClick={openCreate} className="px-4 py-2.5 bg-indigo-600 text-white rounded-lg hover:bg-indigo-700 text-sm font-medium">
                            + Create Template
                        </button>
                    </div>
                    <div className="bg-white rounded-xl shadow-sm border">
                        {loading ? (
                            <div className="text-center py-12 text-gray-500">Loading templates...</div>
                        ) : (
                            <DataTable columns={templateColumns} data={templates} actions={(row) => (
                                <div className="flex gap-2">
                                    <button onClick={() => openPreview(row)} className="text-gray-600 hover:text-gray-800 text-sm font-medium">Preview</button>
                                    <button onClick={() => openEdit(row)} className="text-indigo-600 hover:text-indigo-800 text-sm font-medium">Edit</button>
                                    <button onClick={() => handleSend(row)} className="text-green-600 hover:text-green-800 text-sm font-medium">Send</button>
                                    <button onClick={() => handleDeleteTemplate(row)} className="text-red-600 hover:text-red-800 text-sm font-medium">Delete</button>
                                </div>
                            )} />
                        )}
                        <Pagination meta={tplMeta} onPageChange={fetchTemplates} />
                    </div>
                </div>
            )}

            {/* Template Create/Edit Modal */}
            <Modal isOpen={showModal} onClose={() => { setShowModal(false); setEditing(null); }} title={editing ? 'Edit Template' : 'Create Template'}>
                <form onSubmit={handleSubmit} className="space-y-4">
                    <FormField label="Template Name" name="name" value={form.name} onChange={handleChange} required placeholder="e.g., Weekly Update" />
                    <FormField label="Email Subject" name="subject" value={form.subject} onChange={handleChange} required placeholder="e.g., This Week at Grace Church" />
                    <FormField label="Type" name="type" type="select" value={form.type} onChange={handleChange} options={templateTypes} />
                    <FormField label="Email Body" name="body" type="textarea" value={form.body} onChange={handleChange} required rows={12} placeholder="Write your newsletter content here...&#10;&#10;You can include announcements, devotionals, upcoming events, etc.&#10;&#10;An unsubscribe link will be automatically added." />
                    <div className="flex justify-end gap-3 pt-2">
                        <button type="button" onClick={() => { setShowModal(false); setEditing(null); }} className="px-4 py-2 border border-gray-300 text-gray-700 rounded-lg hover:bg-gray-50">Cancel</button>
                        <button type="submit" className="px-4 py-2 bg-indigo-600 text-white rounded-lg hover:bg-indigo-700">
                            {editing ? 'Update Template' : 'Create Template'}
                        </button>
                    </div>
                </form>
            </Modal>

            {/* Preview Modal */}
            <Modal isOpen={showPreview} onClose={() => { setShowPreview(false); setPreviewTemplate(null); }} title="Template Preview">
                {previewTemplate && (
                    <div className="space-y-4">
                        <div className="bg-gray-50 rounded-lg p-4">
                            <div className="text-sm mb-2">
                                <span className="font-medium text-gray-600">Subject:</span>
                                <span className="ml-2 text-gray-900 font-semibold">{previewTemplate.subject}</span>
                            </div>
                            <div className="text-sm mb-2">
                                <span className="font-medium text-gray-600">Type:</span>
                                <span className="ml-2 capitalize">{previewTemplate.type || 'general'}</span>
                            </div>
                            {previewTemplate.sent_at && (
                                <div className="text-sm">
                                    <span className="font-medium text-gray-600">Last Sent:</span>
                                    <span className="ml-2">{new Date(previewTemplate.sent_at).toLocaleString()} to {previewTemplate.recipients_count} subscribers</span>
                                </div>
                            )}
                        </div>
                        <div className="border rounded-lg p-6 bg-white">
                            <div className="prose max-w-none text-sm whitespace-pre-wrap">{previewTemplate.body}</div>
                        </div>
                        <div className="text-xs text-gray-400 italic text-center">
                            An unsubscribe link will be automatically appended when sent.
                        </div>
                        <div className="flex justify-end gap-3 pt-2">
                            <button onClick={() => { setShowPreview(false); openEdit(previewTemplate); }} className="px-4 py-2 border border-gray-300 text-gray-700 rounded-lg hover:bg-gray-50 text-sm">Edit</button>
                            <button onClick={() => { setShowPreview(false); handleSend(previewTemplate); }} className="px-4 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700 text-sm">Send to All Subscribers</button>
                        </div>
                    </div>
                )}
            </Modal>
        </div>
    );
}
