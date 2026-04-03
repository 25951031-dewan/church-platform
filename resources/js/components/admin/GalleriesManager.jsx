import React, { useState, useEffect } from 'react';
import { Modal, DataTable, Pagination, FormField, Alert } from '../shared/CrudPanel';
import { get, post, put, del, upload } from '../shared/api';

const defaultForm = {
    title: '', description: '', category: '', event_date: '',
    is_published: false, cover_image: null, images: null,
};

export default function GalleriesManager() {
    const [items, setItems] = useState([]);
    const [meta, setMeta] = useState(null);
    const [showModal, setShowModal] = useState(false);
    const [editing, setEditing] = useState(null);
    const [form, setForm] = useState({ ...defaultForm });
    const [alert, setAlert] = useState(null);

    const fetchItems = async (page = 1) => {
        try {
            const data = await get(`/api/galleries?page=${page}`);
            setItems(data.data || []);
            setMeta(data);
        } catch (e) {
            setAlert({ type: 'error', message: e.message });
        }
    };

    useEffect(() => { fetchItems(); }, []);

    const handleChange = (e) => {
        const { name, type, value, checked, files } = e.target;
        if (type === 'file') {
            if (name === 'images') {
                setForm({ ...form, [name]: files });
            } else {
                setForm({ ...form, [name]: files[0] });
            }
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
            title: item.title || '',
            description: item.description || '',
            category: item.category || '',
            event_date: item.event_date || '',
            is_published: !!item.is_published,
            cover_image: null,
            images: null,
        });
        setShowModal(true);
    };

    const handleSubmit = async (e) => {
        e.preventDefault();
        try {
            const formData = new FormData();
            formData.append('title', form.title);
            formData.append('description', form.description);
            formData.append('category', form.category);
            formData.append('event_date', form.event_date);
            formData.append('is_published', form.is_published ? '1' : '0');
            if (form.cover_image) formData.append('cover_image', form.cover_image);
            if (form.images && form.images.length > 0) {
                for (let i = 0; i < form.images.length; i++) {
                    formData.append('images[]', form.images[i]);
                }
            }

            if (editing) {
                formData.append('_method', 'PUT');
                await upload(`/api/galleries/${editing.id}`, formData, 'POST');
                setAlert({ type: 'success', message: 'Gallery updated successfully.' });
            } else {
                await upload('/api/galleries', formData);
                setAlert({ type: 'success', message: 'Gallery created successfully.' });
            }
            setShowModal(false);
            fetchItems();
        } catch (e) {
            setAlert({ type: 'error', message: e.message });
        }
    };

    const handleDelete = async (item) => {
        if (!confirm('Are you sure you want to delete this gallery?')) return;
        try {
            await del(`/api/galleries/${item.id}`);
            setAlert({ type: 'success', message: 'Gallery deleted successfully.' });
            fetchItems();
        } catch (e) {
            setAlert({ type: 'error', message: e.message });
        }
    };

    const columns = [
        { key: 'title', label: 'Title' },
        { key: 'category', label: 'Category' },
        { key: 'event_date', label: 'Event Date' },
        {
            key: 'images_count', label: 'Images',
            render: (row) => (
                <span className="inline-flex px-2 py-0.5 text-xs font-semibold rounded-full bg-blue-100 text-blue-800">
                    {row.images_count || 0}
                </span>
            ),
        },
        {
            key: 'is_published', label: 'Published',
            render: (row) => (
                <span className={`inline-flex px-2 py-0.5 text-xs font-semibold rounded-full ${row.is_published ? 'bg-green-100 text-green-800' : 'bg-orange-100 text-orange-800'}`}>
                    {row.is_published ? 'Published' : 'Draft'}
                </span>
            ),
        },
    ];

    return (
        <div>
            <div className="flex items-center justify-between mb-6">
                <h2 className="text-2xl font-bold text-gray-800">Galleries</h2>
                <button onClick={openCreate} className="px-4 py-2 bg-indigo-600 text-white rounded-lg text-sm font-medium hover:bg-indigo-700">+ Add Gallery</button>
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
            <Modal isOpen={showModal} onClose={() => setShowModal(false)} title={editing ? 'Edit Gallery' : 'Add Gallery'}>
                <form onSubmit={handleSubmit} className="space-y-4">
                    <FormField label="Title" name="title" value={form.title} onChange={handleChange} required />
                    <FormField label="Description" name="description" type="textarea" value={form.description} onChange={handleChange} rows={4} />
                    <div className="grid grid-cols-2 gap-4">
                        <FormField label="Category" name="category" value={form.category} onChange={handleChange} />
                        <FormField label="Event Date" name="event_date" type="date" value={form.event_date} onChange={handleChange} />
                    </div>
                    <FormField label="Published" name="is_published" type="checkbox" value={form.is_published} onChange={handleChange} />
                    <FormField label="Cover Image" name="cover_image" type="file" onChange={handleChange} />
                    <div className="mb-4">
                        <label htmlFor="field-images" className="block text-sm font-medium text-gray-700 mb-1">Gallery Images</label>
                        <input
                            id="field-images"
                            type="file"
                            name="images"
                            multiple
                            onChange={handleChange}
                            className="w-full text-sm text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded-lg file:border-0 file:text-sm file:font-semibold file:bg-indigo-50 file:text-indigo-700 hover:file:bg-indigo-100"
                        />
                        <p className="mt-1 text-xs text-gray-500">You can select multiple images at once.</p>
                    </div>
                    <div className="flex justify-end gap-3 pt-2">
                        <button type="button" onClick={() => setShowModal(false)} className="px-4 py-2 border border-gray-300 text-gray-700 rounded-lg hover:bg-gray-50">Cancel</button>
                        <button type="submit" className="px-4 py-2 bg-indigo-600 text-white rounded-lg hover:bg-indigo-700">Save</button>
                    </div>
                </form>
            </Modal>
        </div>
    );
}
