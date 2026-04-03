import React, { useState, useEffect } from 'react';
import { Modal, DataTable, Pagination, FormField, Alert } from '../shared/CrudPanel';
import { get, post, put, del } from '../shared/api';

export default function EventsManager() {
    const [items, setItems] = useState([]);
    const [meta, setMeta] = useState(null);
    const [showModal, setShowModal] = useState(false);
    const [editing, setEditing] = useState(null);
    const [form, setForm] = useState({
        title: '', description: '', start_date: '', end_date: '',
        start_time: '', end_time: '', location: '', max_attendees: '',
        registration_required: false, is_active: false,
    });
    const [alert, setAlert] = useState(null);
    const [loading, setLoading] = useState(false);

    const fetchItems = async (page = 1) => {
        setLoading(true);
        try {
            const data = await get(`/api/events?page=${page}`);
            setItems(data.data || []);
            setMeta(data);
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
        setForm({
            title: '', description: '', start_date: '', end_date: '',
            start_time: '', end_time: '', location: '', max_attendees: '',
            registration_required: false, is_active: false,
        });
        setShowModal(true);
    };

    const openEdit = (item) => {
        setEditing(item);
        setForm({
            title: item.title || '',
            description: item.description || '',
            start_date: item.start_date || '',
            end_date: item.end_date || '',
            start_time: item.start_time || '',
            end_time: item.end_time || '',
            location: item.location || '',
            max_attendees: item.max_attendees || '',
            registration_required: !!item.registration_required,
            is_active: !!item.is_active,
        });
        setShowModal(true);
    };

    const handleSubmit = async (e) => {
        e.preventDefault();
        try {
            if (editing) {
                await put(`/api/events/${editing.id}`, form);
                setAlert({ type: 'success', message: 'Event updated.' });
            } else {
                await post('/api/events', form);
                setAlert({ type: 'success', message: 'Event created.' });
            }
            setShowModal(false);
            fetchItems();
        } catch (e) { setAlert({ type: 'error', message: e.message }); }
    };

    const handleDelete = async (item) => {
        if (!confirm('Delete this event?')) return;
        try {
            await del(`/api/events/${item.id}`);
            setAlert({ type: 'success', message: 'Event deleted.' });
            fetchItems();
        } catch (e) { setAlert({ type: 'error', message: e.message }); }
    };

    const columns = [
        { key: 'title', label: 'Title' },
        { key: 'start_date', label: 'Start Date', render: (r) => r.start_date ? new Date(r.start_date).toLocaleDateString() : '' },
        { key: 'location', label: 'Location' },
        {
            key: 'is_active', label: 'Active', render: (r) => (
                <span className={`inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium ${r.is_active ? 'bg-green-100 text-green-800' : 'bg-gray-100 text-gray-800'}`}>
                    {r.is_active ? 'Active' : 'Inactive'}
                </span>
            ),
        },
        { key: 'registrations_count', label: 'Registrations', render: (r) => r.registrations_count ?? 0 },
    ];

    return (
        <div>
            <div className="flex items-center justify-between mb-6">
                <h2 className="text-2xl font-bold text-gray-800">Events</h2>
                <button onClick={openCreate} className="px-4 py-2 bg-indigo-600 text-white rounded-lg text-sm font-medium hover:bg-indigo-700">+ Add Event</button>
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
            <Modal isOpen={showModal} onClose={() => setShowModal(false)} title={editing ? 'Edit Event' : 'Add Event'}>
                <form onSubmit={handleSubmit} className="space-y-4">
                    <FormField label="Title" name="title" value={form.title} onChange={handleChange} required placeholder="Event title" />
                    <FormField label="Description" name="description" type="richtext" value={form.description} onChange={handleChange} placeholder="Event description..." />
                    <div className="grid grid-cols-2 gap-4">
                        <FormField label="Start Date" name="start_date" type="date" value={form.start_date} onChange={handleChange} required />
                        <FormField label="End Date" name="end_date" type="date" value={form.end_date} onChange={handleChange} />
                    </div>
                    <div className="grid grid-cols-2 gap-4">
                        <FormField label="Start Time" name="start_time" type="time" value={form.start_time} onChange={handleChange} />
                        <FormField label="End Time" name="end_time" type="time" value={form.end_time} onChange={handleChange} />
                    </div>
                    <FormField label="Location" name="location" value={form.location} onChange={handleChange} placeholder="Event location" />
                    <FormField label="Max Attendees" name="max_attendees" type="number" value={form.max_attendees} onChange={handleChange} placeholder="Leave empty for unlimited" />
                    <FormField label="Registration Required" name="registration_required" type="checkbox" value={form.registration_required} onChange={handleChange} />
                    <FormField label="Active" name="is_active" type="checkbox" value={form.is_active} onChange={handleChange} />
                    <div className="flex justify-end gap-3 pt-2">
                        <button type="button" onClick={() => setShowModal(false)} className="px-4 py-2 border border-gray-300 text-gray-700 rounded-lg hover:bg-gray-50">Cancel</button>
                        <button type="submit" className="px-4 py-2 bg-indigo-600 text-white rounded-lg hover:bg-indigo-700">Save</button>
                    </div>
                </form>
            </Modal>
        </div>
    );
}
