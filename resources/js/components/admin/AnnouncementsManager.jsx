import React, { useState, useEffect } from 'react';
import { Modal, DataTable, Pagination, FormField, Alert } from '../shared/CrudPanel';
import { get, post, put, del } from '../shared/api';

export default function AnnouncementsManager() {
    const [items, setItems] = useState([]);
    const [meta, setMeta] = useState({});
    const [modal, setModal] = useState(false);
    const [editing, setEditing] = useState(null);
    const [form, setForm] = useState({ title: '', content: '', type: 'general', link: '', is_active: true, start_date: '', end_date: '', priority: 0 });
    const [alert, setAlert] = useState(null);

    useEffect(() => { fetchItems(); }, []);

    const fetchItems = async (page = 1) => {
        try {
            const data = await get(`/api/announcements?page=${page}`);
            setItems(data.data || []);
            setMeta(data);
        } catch (e) { setAlert({ type: 'error', message: e.message }); }
    };

    const openCreate = () => {
        setEditing(null);
        setForm({ title: '', content: '', type: 'general', link: '', is_active: true, start_date: '', end_date: '', priority: 0 });
        setModal(true);
    };

    const openEdit = (item) => {
        setEditing(item);
        setForm({
            title: item.title || '',
            content: item.content || '',
            type: item.type || 'general',
            link: item.link || '',
            is_active: item.is_active ?? true,
            start_date: item.start_date ? item.start_date.split('T')[0] : '',
            end_date: item.end_date ? item.end_date.split('T')[0] : '',
            priority: item.priority || 0,
        });
        setModal(true);
    };

    const handleSubmit = async () => {
        try {
            if (editing) {
                await put(`/api/announcements/${editing.id}`, form);
                setAlert({ type: 'success', message: 'Announcement updated.' });
            } else {
                await post('/api/announcements', form);
                setAlert({ type: 'success', message: 'Announcement created.' });
            }
            setModal(false);
            fetchItems(meta.current_page || 1);
        } catch (e) { setAlert({ type: 'error', message: e.message }); }
    };

    const handleDelete = async (item) => {
        if (!confirm('Delete this announcement?')) return;
        try {
            await del(`/api/announcements/${item.id}`);
            setAlert({ type: 'success', message: 'Announcement deleted.' });
            fetchItems(meta.current_page || 1);
        } catch (e) { setAlert({ type: 'error', message: e.message }); }
    };

    const handleChange = (e) => {
        const { name, value, type, checked } = e.target;
        setForm(prev => ({ ...prev, [name]: type === 'checkbox' ? checked : value }));
    };

    const columns = [
        { key: 'title', label: 'Title' },
        { key: 'type', label: 'Type', render: (item) => <span className={`px-2 py-0.5 rounded-full text-xs font-medium ${item.type === 'urgent' ? 'bg-red-100 text-red-700' : item.type === 'event' ? 'bg-blue-100 text-blue-700' : item.type === 'blog' ? 'bg-purple-100 text-purple-700' : 'bg-gray-100 text-gray-700'}`}>{item.type}</span> },
        { key: 'priority', label: 'Priority' },
        { key: 'is_active', label: 'Status', render: (item) => <span className={`px-2 py-0.5 rounded-full text-xs font-medium ${item.is_active ? 'bg-green-100 text-green-700' : 'bg-red-100 text-red-700'}`}>{item.is_active ? 'Active' : 'Inactive'}</span> },
        { key: 'start_date', label: 'Start', render: (item) => item.start_date ? new Date(item.start_date).toLocaleDateString() : '-' },
        { key: 'end_date', label: 'End', render: (item) => item.end_date ? new Date(item.end_date).toLocaleDateString() : '-' },
    ];

    return (
        <div>
            <div className="flex items-center justify-between mb-6">
                <h2 className="text-2xl font-bold text-gray-800">Announcements</h2>
                <button onClick={openCreate} className="px-4 py-2 bg-indigo-600 text-white rounded-lg text-sm font-medium hover:bg-indigo-700">+ Add Announcement</button>
            </div>
            <Alert {...alert} onClose={() => setAlert(null)} />
            <DataTable columns={columns} data={items} actions={(item) => (
                <div className="flex gap-2">
                    <button onClick={() => openEdit(item)} className="text-indigo-600 hover:text-indigo-800 text-sm font-medium">Edit</button>
                    <button onClick={() => handleDelete(item)} className="text-red-600 hover:text-red-800 text-sm font-medium">Delete</button>
                </div>
            )} />
            <Pagination meta={meta} onPageChange={fetchItems} />
            {modal && (
                <Modal isOpen={true} title={editing ? 'Edit Announcement' : 'New Announcement'} onClose={() => setModal(false)}>
                    <FormField label="Title" name="title" value={form.title} onChange={handleChange} required />
                    <FormField label="Content" name="content" type="textarea" value={form.content} onChange={handleChange} rows={3} />
                    <FormField label="Type" name="type" type="select" value={form.type} onChange={handleChange} options={[{ value: 'general', label: 'General' }, { value: 'urgent', label: 'Urgent' }, { value: 'event', label: 'Event' }, { value: 'blog', label: 'Blog' }]} />
                    <FormField label="Link (optional)" name="link" value={form.link} onChange={handleChange} placeholder="https://..." />
                    <div className="grid grid-cols-2 gap-4">
                        <FormField label="Start Date" name="start_date" type="date" value={form.start_date} onChange={handleChange} />
                        <FormField label="End Date" name="end_date" type="date" value={form.end_date} onChange={handleChange} />
                    </div>
                    <FormField label="Priority (0-100)" name="priority" type="number" value={form.priority} onChange={handleChange} />
                    <FormField label="Active" name="is_active" type="checkbox" checked={form.is_active} onChange={handleChange} />
                    <div className="flex justify-end gap-3 mt-4">
                        <button onClick={() => setModal(false)} className="px-4 py-2 border border-gray-300 rounded-lg text-sm text-gray-700 hover:bg-gray-50">Cancel</button>
                        <button onClick={handleSubmit} className="px-4 py-2 bg-indigo-600 text-white rounded-lg text-sm font-medium hover:bg-indigo-700">{editing ? 'Update' : 'Create'}</button>
                    </div>
                </Modal>
            )}
        </div>
    );
}
