import React, { useState, useEffect } from 'react';
import { Modal, DataTable, Pagination, FormField, Alert } from '../shared/CrudPanel';
import { get, post, put, del } from '../shared/api';

export default function BlessingsManager() {
    const [items, setItems] = useState([]);
    const [meta, setMeta] = useState(null);
    const [showModal, setShowModal] = useState(false);
    const [editing, setEditing] = useState(null);
    const [form, setForm] = useState({ title: '', content: '', display_date: '', author: '' });
    const [alert, setAlert] = useState(null);

    const fetchItems = async (page = 1) => {
        try {
            const data = await get(`/api/blessings?page=${page}`);
            setItems(data.data || []); setMeta(data);
        } catch (e) { setAlert({ type: 'error', message: e.message }); }
    };

    useEffect(() => { fetchItems(); }, []);
    const handleChange = (e) => setForm({ ...form, [e.target.name]: e.target.value });
    const openCreate = () => { setEditing(null); setForm({ title: '', content: '', display_date: '', author: '' }); setShowModal(true); };
    const openEdit = (item) => { setEditing(item); setForm({ title: item.title, content: item.content, display_date: item.display_date, author: item.author || '' }); setShowModal(true); };

    const handleSubmit = async (e) => {
        e.preventDefault();
        try {
            if (editing) { await put(`/api/blessings/${editing.id}`, form); setAlert({ type: 'success', message: 'Blessing updated.' }); }
            else { await post('/api/blessings', form); setAlert({ type: 'success', message: 'Blessing created.' }); }
            setShowModal(false); fetchItems();
        } catch (e) { setAlert({ type: 'error', message: e.message }); }
    };

    const handleDelete = async (item) => {
        if (!confirm('Delete this blessing?')) return;
        try { await del(`/api/blessings/${item.id}`); setAlert({ type: 'success', message: 'Deleted.' }); fetchItems(); }
        catch (e) { setAlert({ type: 'error', message: e.message }); }
    };

    const columns = [
        { key: 'title', label: 'Title' },
        { key: 'content', label: 'Content', render: (r) => (r.content || '').substring(0, 60) + '...' },
        { key: 'display_date', label: 'Date' },
        { key: 'author', label: 'Author' },
    ];

    return (
        <div>
            <div className="flex items-center justify-between mb-6">
                <h2 className="text-2xl font-bold text-gray-800">Daily Blessings</h2>
                <button onClick={openCreate} className="px-4 py-2 bg-indigo-600 text-white rounded-lg text-sm font-medium hover:bg-indigo-700">+ Add Blessing</button>
            </div>
            <Alert {...alert} onClose={() => setAlert(null)} />
            <div className="bg-white rounded-xl shadow-sm border">
                <DataTable columns={columns} data={items} actions={(row) => (
                    <div className="flex gap-2">
                        <button onClick={() => openEdit(row)} className="text-indigo-600 hover:text-indigo-800 text-sm font-medium">Edit</button>
                        <button onClick={() => handleDelete(row)} className="text-red-600 hover:text-red-800 text-sm font-medium">Delete</button>
                    </div>
                )} />
                <Pagination meta={meta} onPageChange={fetchItems} />
            </div>
            <Modal isOpen={showModal} onClose={() => setShowModal(false)} title={editing ? 'Edit Blessing' : 'Add Blessing'}>
                <form onSubmit={handleSubmit} className="space-y-4">
                    <FormField label="Title" name="title" value={form.title} onChange={handleChange} required />
                    <FormField label="Content" name="content" type="textarea" value={form.content} onChange={handleChange} required />
                    <FormField label="Date" name="display_date" type="date" value={form.display_date} onChange={handleChange} required />
                    <FormField label="Author" name="author" value={form.author} onChange={handleChange} />
                    <div className="flex justify-end gap-3 pt-2">
                        <button type="button" onClick={() => setShowModal(false)} className="px-4 py-2 border border-gray-300 text-gray-700 rounded-lg hover:bg-gray-50">Cancel</button>
                        <button type="submit" className="px-4 py-2 bg-indigo-600 text-white rounded-lg hover:bg-indigo-700">Save</button>
                    </div>
                </form>
            </Modal>
        </div>
    );
}
