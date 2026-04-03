import React, { useState, useEffect } from 'react';
import { Modal, DataTable, Pagination, FormField, Alert } from '../shared/CrudPanel';
import { get, post, put, del, upload } from '../shared/api';

const defaultForm = {
    name: '',
    description: '',
    category: '',
    leader_name: '',
    leader_email: '',
    leader_phone: '',
    meeting_schedule: '',
    meeting_location: '',
    is_active: true,
    is_featured: false,
    sort_order: 0,
    image: null,
};

export default function MinistriesManager() {
    const [items, setItems] = useState([]);
    const [meta, setMeta] = useState(null);
    const [showModal, setShowModal] = useState(false);
    const [editing, setEditing] = useState(null);
    const [form, setForm] = useState({ ...defaultForm });
    const [alert, setAlert] = useState(null);
    const [loading, setLoading] = useState(false);

    const fetchItems = async (page = 1) => {
        setLoading(true);
        try {
            const data = await get(`/api/ministries?page=${page}`);
            setItems(data.data || []);
            setMeta(data);
        } catch (e) {
            setAlert({ type: 'error', message: e.message });
        }
        setLoading(false);
    };

    useEffect(() => { fetchItems(); }, []);

    const handleChange = (e) => {
        const { name, value, type, checked, files } = e.target;
        if (type === 'file') {
            setForm({ ...form, [name]: files[0] || null });
        } else if (type === 'checkbox') {
            setForm({ ...form, [name]: checked });
        } else {
            setForm({ ...form, [name]: value });
        }
    };

    const openCreate = () => {
        setEditing(null);
        setForm({ ...defaultForm });
        setShowModal(true);
    };

    const openEdit = (item) => {
        setEditing(item);
        setForm({
            name: item.name || '',
            description: item.description || '',
            category: item.category || '',
            leader_name: item.leader_name || '',
            leader_email: item.leader_email || '',
            leader_phone: item.leader_phone || '',
            meeting_schedule: item.meeting_schedule || '',
            meeting_location: item.meeting_location || '',
            is_active: !!item.is_active,
            is_featured: !!item.is_featured,
            sort_order: item.sort_order || 0,
            image: null,
        });
        setShowModal(true);
    };

    const handleSubmit = async (e) => {
        e.preventDefault();
        try {
            const formData = new FormData();
            formData.append('name', form.name);
            formData.append('description', form.description);
            formData.append('category', form.category);
            formData.append('leader_name', form.leader_name);
            formData.append('leader_email', form.leader_email);
            formData.append('leader_phone', form.leader_phone);
            formData.append('meeting_schedule', form.meeting_schedule);
            formData.append('meeting_location', form.meeting_location);
            formData.append('is_active', form.is_active ? '1' : '0');
            formData.append('is_featured', form.is_featured ? '1' : '0');
            formData.append('sort_order', form.sort_order);
            if (form.image) {
                formData.append('image', form.image);
            }

            if (editing) {
                formData.append('_method', 'PUT');
                await upload(`/api/ministries/${editing.id}`, formData, 'POST');
                setAlert({ type: 'success', message: 'Ministry updated successfully.' });
            } else {
                await upload('/api/ministries', formData);
                setAlert({ type: 'success', message: 'Ministry created successfully.' });
            }
            setShowModal(false);
            fetchItems();
        } catch (e) {
            setAlert({ type: 'error', message: e.message });
        }
    };

    const handleDelete = async (item) => {
        if (!confirm('Are you sure you want to delete this ministry?')) return;
        try {
            await del(`/api/ministries/${item.id}`);
            setAlert({ type: 'success', message: 'Ministry deleted successfully.' });
            fetchItems();
        } catch (e) {
            setAlert({ type: 'error', message: e.message });
        }
    };

    const columns = [
        { key: 'name', label: 'Name' },
        { key: 'category', label: 'Category' },
        { key: 'leader_name', label: 'Leader' },
        { key: 'meeting_schedule', label: 'Schedule' },
        {
            key: 'is_active', label: 'Active',
            render: (r) => (
                <span className={`inline-flex px-2 py-0.5 rounded-full text-xs font-medium ${r.is_active ? 'bg-green-100 text-green-800' : 'bg-gray-100 text-gray-600'}`}>
                    {r.is_active ? 'Active' : 'Inactive'}
                </span>
            ),
        },
        {
            key: 'is_featured', label: 'Featured',
            render: (r) => (
                <span className={`inline-flex px-2 py-0.5 rounded-full text-xs font-medium ${r.is_featured ? 'bg-amber-100 text-amber-800' : 'bg-gray-100 text-gray-600'}`}>
                    {r.is_featured ? 'Featured' : 'No'}
                </span>
            ),
        },
    ];

    return (
        <div>
            <div className="flex items-center justify-between mb-6">
                <h2 className="text-2xl font-bold text-gray-800">Ministries</h2>
                <button onClick={openCreate} className="px-4 py-2 bg-indigo-600 text-white rounded-lg text-sm font-medium hover:bg-indigo-700">
                    + Add Ministry
                </button>
            </div>
            <Alert {...alert} onClose={() => setAlert(null)} />
            <div className="bg-white rounded-xl shadow-sm border">
                <DataTable columns={columns} data={items} actions={(row) => (
                    <div className="flex gap-2">
                        {row.slug && row.is_active && (
                            <a href={`/ministries/${row.slug}`} target="_blank" rel="noopener noreferrer" className="text-green-600 hover:text-green-800 text-sm font-medium">View</a>
                        )}
                        <button onClick={() => openEdit(row)} className="text-indigo-600 hover:text-indigo-800 text-sm font-medium">Edit</button>
                        <button onClick={() => handleDelete(row)} className="text-red-600 hover:text-red-800 text-sm font-medium">Delete</button>
                    </div>
                )} />
                <Pagination meta={meta} onPageChange={fetchItems} />
            </div>
            <Modal isOpen={showModal} onClose={() => setShowModal(false)} title={editing ? 'Edit Ministry' : 'Add Ministry'}>
                <form onSubmit={handleSubmit} className="space-y-4">
                    <FormField label="Name" name="name" value={form.name} onChange={handleChange} required placeholder="Ministry name" />
                    <FormField label="Description" name="description" type="richtext" value={form.description} onChange={handleChange} placeholder="Describe the ministry..." />
                    <FormField label="Category" name="category" value={form.category} onChange={handleChange} placeholder="e.g. Worship, Youth, Outreach" />
                    <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <FormField label="Leader Name" name="leader_name" value={form.leader_name} onChange={handleChange} />
                        <FormField label="Leader Email" name="leader_email" type="email" value={form.leader_email} onChange={handleChange} />
                    </div>
                    <FormField label="Leader Phone" name="leader_phone" value={form.leader_phone} onChange={handleChange} />
                    <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <FormField label="Meeting Schedule" name="meeting_schedule" value={form.meeting_schedule} onChange={handleChange} placeholder="e.g. Sundays at 9 AM" />
                        <FormField label="Meeting Location" name="meeting_location" value={form.meeting_location} onChange={handleChange} placeholder="e.g. Room 201" />
                    </div>
                    <FormField label="Image" name="image" type="file" onChange={handleChange} />
                    {editing && editing.image_url && (
                        <div className="mb-4">
                            <span className="text-xs text-gray-500">Current image:</span>
                            <img src={editing.image_url} alt="Ministry" className="mt-1 h-20 w-20 object-cover rounded-lg" />
                        </div>
                    )}
                    <FormField label="Sort Order" name="sort_order" type="number" value={form.sort_order} onChange={handleChange} />
                    <div className="flex gap-6">
                        <FormField label="Active" name="is_active" type="checkbox" value={form.is_active} onChange={handleChange} />
                        <FormField label="Featured" name="is_featured" type="checkbox" value={form.is_featured} onChange={handleChange} />
                    </div>
                    <div className="flex justify-end gap-3 pt-2">
                        <button type="button" onClick={() => setShowModal(false)} className="px-4 py-2 border border-gray-300 text-gray-700 rounded-lg hover:bg-gray-50">Cancel</button>
                        <button type="submit" className="px-4 py-2 bg-indigo-600 text-white rounded-lg hover:bg-indigo-700">
                            {editing ? 'Update Ministry' : 'Create Ministry'}
                        </button>
                    </div>
                </form>
            </Modal>
        </div>
    );
}
