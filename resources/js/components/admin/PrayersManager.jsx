import React, { useState, useEffect } from 'react';
import { Modal, DataTable, Pagination, FormField, Alert } from '../shared/CrudPanel';
import { get, post, put, patch, del, extractPaginatedData } from '../shared/api';

export default function PrayersManager() {
    const [items, setItems] = useState([]);
    const [meta, setMeta] = useState(null);
    const [showModal, setShowModal] = useState(false);
    const [editing, setEditing] = useState(null);
    const [form, setForm] = useState({
        name: '', email: '', phone: '', subject: '', description: '',
        is_public: false, is_urgent: false,
    });
    const [alert, setAlert] = useState(null);
    const [loading, setLoading] = useState(false);

    const fetchItems = async (page = 1) => {
        setLoading(true);
        try {
            const data = await get(`/api/prayer-requests?page=${page}`);
            const { items, meta } = extractPaginatedData(data);
            setItems(items);
            setMeta(meta);
        } catch (e) { setAlert({ type: 'error', message: e.message }); }
        setLoading(false);
    };

    useEffect(() => { fetchItems(); }, []);

    const handleChange = (e) => {
        const { name, type, checked, value } = e.target;
        setForm({ ...form, [name]: type === 'checkbox' ? checked : value });
    };

    const openCreate = () => {
        setEditing(null);
        setForm({ name: '', email: '', phone: '', subject: '', description: '', is_public: false, is_urgent: false });
        setShowModal(true);
    };

    const openEdit = (item) => {
        setEditing(item);
        setForm({
            name: item.name || '',
            email: item.email || '',
            phone: item.phone || '',
            subject: item.subject || '',
            description: item.description || '',
            is_public: !!item.is_public,
            is_urgent: !!item.is_urgent,
        });
        setShowModal(true);
    };

    const handleSubmit = async (e) => {
        e.preventDefault();
        try {
            if (editing) {
                await put(`/api/prayer-requests/${editing.id}`, form);
                setAlert({ type: 'success', message: 'Prayer request updated.' });
            } else {
                await post('/api/prayer-requests', form);
                setAlert({ type: 'success', message: 'Prayer request created.' });
            }
            setShowModal(false);
            fetchItems();
        } catch (e) { setAlert({ type: 'error', message: e.message }); }
    };

    const handleDelete = async (item) => {
        if (!confirm('Delete this prayer request?')) return;
        try {
            await del(`/api/prayer-requests/${item.id}`);
            setAlert({ type: 'success', message: 'Prayer request deleted.' });
            fetchItems();
        } catch (e) { setAlert({ type: 'error', message: e.message }); }
    };

    const updateStatus = async (item, status) => {
        try {
            await patch(`/api/prayer-requests/${item.id}/status`, { status });
            setAlert({ type: 'success', message: `Prayer request ${status}.` });
            fetchItems();
        } catch (e) { setAlert({ type: 'error', message: e.message }); }
    };

    const statusBadge = (status) => {
        const colors = {
            pending: 'bg-yellow-100 text-yellow-800',
            approved: 'bg-green-100 text-green-800',
            rejected: 'bg-red-100 text-red-800',
        };
        return (
            <span className={`inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium ${colors[status] || 'bg-gray-100 text-gray-800'}`}>
                {status}
            </span>
        );
    };

    const columns = [
        { key: 'name', label: 'Name' },
        { key: 'subject', label: 'Subject' },
        { key: 'status', label: 'Status', render: (r) => statusBadge(r.status) },
        {
            key: 'is_public', label: 'Public', render: (r) => (
                <span className={r.is_public ? 'text-green-600 font-medium' : 'text-gray-400'}>
                    {r.is_public ? 'Yes' : 'No'}
                </span>
            ),
        },
        {
            key: 'is_urgent', label: 'Urgent', render: (r) => r.is_urgent ? (
                <span className="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-red-100 text-red-800">Urgent</span>
            ) : (
                <span className="text-gray-400 text-sm">No</span>
            ),
        },
        { key: 'created_at', label: 'Created', render: (r) => r.created_at ? new Date(r.created_at).toLocaleDateString() : '' },
    ];

    return (
        <div>
            <div className="flex items-center justify-between mb-6">
                <h2 className="text-2xl font-bold text-gray-800">Prayer Requests</h2>
                <button onClick={openCreate} className="px-4 py-2 bg-indigo-600 text-white rounded-lg text-sm font-medium hover:bg-indigo-700">+ Add Prayer Request</button>
            </div>
            <Alert {...alert} onClose={() => setAlert(null)} />
            <div className="bg-white rounded-xl shadow-sm border">
                <DataTable columns={columns} data={items} actions={(row) => (
                    <div className="flex items-center gap-2">
                        <select
                            value={row.status || 'pending'}
                            onChange={(e) => updateStatus(row, e.target.value)}
                            className="text-xs border border-gray-300 rounded-lg px-2 py-1 focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 outline-none"
                        >
                            <option value="pending">Pending</option>
                            <option value="approved">Approved</option>
                            <option value="rejected">Rejected</option>
                        </select>
                        <button onClick={() => openEdit(row)} className="text-indigo-600 hover:text-indigo-800 text-sm font-medium">Edit</button>
                        <button onClick={() => handleDelete(row)} className="text-red-600 hover:text-red-800 text-sm font-medium">Delete</button>
                    </div>
                )} />
                <Pagination meta={meta} onPageChange={fetchItems} />
            </div>
            <Modal isOpen={showModal} onClose={() => setShowModal(false)} title={editing ? 'Edit Prayer Request' : 'Add Prayer Request'}>
                <form onSubmit={handleSubmit} className="space-y-4">
                    <FormField label="Name" name="name" value={form.name} onChange={handleChange} required placeholder="Full name" />
                    <FormField label="Email" name="email" type="email" value={form.email} onChange={handleChange} placeholder="email@example.com" />
                    <FormField label="Phone" name="phone" value={form.phone} onChange={handleChange} placeholder="Phone number" />
                    <FormField label="Subject" name="subject" value={form.subject} onChange={handleChange} required placeholder="Prayer subject" />
                    <FormField label="Description" name="description" type="textarea" value={form.description} onChange={handleChange} required placeholder="Describe your prayer request..." />
                    <FormField label="Public" name="is_public" type="checkbox" value={form.is_public} onChange={handleChange} />
                    <FormField label="Urgent" name="is_urgent" type="checkbox" value={form.is_urgent} onChange={handleChange} />
                    <div className="flex justify-end gap-3 pt-2">
                        <button type="button" onClick={() => setShowModal(false)} className="px-4 py-2 border border-gray-300 text-gray-700 rounded-lg hover:bg-gray-50">Cancel</button>
                        <button type="submit" className="px-4 py-2 bg-indigo-600 text-white rounded-lg hover:bg-indigo-700">Save</button>
                    </div>
                </form>
            </Modal>
        </div>
    );
}
