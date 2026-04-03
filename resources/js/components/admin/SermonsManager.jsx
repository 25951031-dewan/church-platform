import React, { useState, useEffect } from 'react';
import { Modal, DataTable, Pagination, FormField, Alert } from '../shared/CrudPanel';
import { get, del, upload, extractPaginatedData } from '../shared/api';

export default function SermonsManager() {
    const [items, setItems] = useState([]);
    const [meta, setMeta] = useState(null);
    const [showModal, setShowModal] = useState(false);
    const [editing, setEditing] = useState(null);
    const [form, setForm] = useState({
        title: '', description: '', speaker: '', scripture_reference: '',
        series: '', category: '', sermon_date: '', duration: '',
        video_url: '', audio_url: '', is_featured: false, is_published: false,
        tags: '', thumbnail: null,
    });
    const [alert, setAlert] = useState(null);
    const [loading, setLoading] = useState(false);

    const fetchItems = async (page = 1) => {
        setLoading(true);
        try {
            const data = await get(`/api/sermons?page=${page}`);
            const { items, meta } = extractPaginatedData(data);
            setItems(items);
            setMeta(meta);
        } catch (e) { setAlert({ type: 'error', message: e.message }); }
        setLoading(false);
    };

    useEffect(() => { fetchItems(); }, []);

    const handleChange = (e) => {
        const { name, type, checked, value, files } = e.target;
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
        setForm({
            title: '', description: '', speaker: '', scripture_reference: '',
            series: '', category: '', sermon_date: '', duration: '',
            video_url: '', audio_url: '', is_featured: false, is_published: false,
            tags: '', thumbnail: null,
        });
        setShowModal(true);
    };

    const openEdit = (item) => {
        setEditing(item);
        setForm({
            title: item.title || '',
            description: item.description || '',
            speaker: item.speaker || '',
            scripture_reference: item.scripture_reference || '',
            series: item.series || '',
            category: item.category || '',
            sermon_date: item.sermon_date || '',
            duration: item.duration || '',
            video_url: item.video_url || '',
            audio_url: item.audio_url || '',
            is_featured: !!item.is_featured,
            is_published: !!item.is_published,
            tags: item.tags || '',
            thumbnail: null,
        });
        setShowModal(true);
    };

    const handleSubmit = async (e) => {
        e.preventDefault();
        try {
            const formData = new FormData();
            formData.append('title', form.title);
            formData.append('description', form.description);
            formData.append('speaker', form.speaker);
            formData.append('scripture_reference', form.scripture_reference);
            formData.append('series', form.series);
            formData.append('category', form.category);
            formData.append('sermon_date', form.sermon_date);
            formData.append('duration', form.duration);
            formData.append('video_url', form.video_url);
            formData.append('audio_url', form.audio_url);
            formData.append('is_featured', form.is_featured ? '1' : '0');
            formData.append('is_published', form.is_published ? '1' : '0');
            formData.append('tags', form.tags);
            if (form.thumbnail) {
                formData.append('thumbnail', form.thumbnail);
            }

            if (editing) {
                formData.append('_method', 'PUT');
                await upload(`/api/sermons/${editing.id}`, formData, 'POST');
                setAlert({ type: 'success', message: 'Sermon updated.' });
            } else {
                await upload('/api/sermons', formData, 'POST');
                setAlert({ type: 'success', message: 'Sermon created.' });
            }
            setShowModal(false);
            fetchItems();
        } catch (e) { setAlert({ type: 'error', message: e.message }); }
    };

    const handleDelete = async (item) => {
        if (!confirm('Delete this sermon?')) return;
        try {
            await del(`/api/sermons/${item.id}`);
            setAlert({ type: 'success', message: 'Sermon deleted.' });
            fetchItems();
        } catch (e) { setAlert({ type: 'error', message: e.message }); }
    };

    const columns = [
        { key: 'title', label: 'Title' },
        { key: 'speaker', label: 'Speaker' },
        { key: 'sermon_date', label: 'Date', render: (r) => r.sermon_date ? new Date(r.sermon_date).toLocaleDateString() : '' },
        { key: 'series', label: 'Series' },
        {
            key: 'is_featured', label: 'Featured', render: (r) => (
                <span className={`inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium ${r.is_featured ? 'bg-indigo-100 text-indigo-800' : 'bg-gray-100 text-gray-800'}`}>
                    {r.is_featured ? 'Featured' : 'No'}
                </span>
            ),
        },
        {
            key: 'is_published', label: 'Published', render: (r) => (
                <span className={`inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium ${r.is_published ? 'bg-green-100 text-green-800' : 'bg-yellow-100 text-yellow-800'}`}>
                    {r.is_published ? 'Published' : 'Draft'}
                </span>
            ),
        },
    ];

    return (
        <div>
            <div className="flex items-center justify-between mb-6">
                <h2 className="text-2xl font-bold text-gray-800">Sermons</h2>
                <button onClick={openCreate} className="px-4 py-2 bg-indigo-600 text-white rounded-lg text-sm font-medium hover:bg-indigo-700">+ Add Sermon</button>
            </div>
            <Alert {...alert} onClose={() => setAlert(null)} />
            <div className="bg-white rounded-xl shadow-sm border">
                <DataTable columns={columns} data={items} actions={(row) => (
                    <div className="flex gap-2">
                        {row.slug && (
                            <a href={`/sermons/${row.slug}`} target="_blank" rel="noopener noreferrer" className="text-green-600 hover:text-green-800 text-sm font-medium">View</a>
                        )}
                        <button onClick={() => openEdit(row)} className="text-indigo-600 hover:text-indigo-800 text-sm font-medium">Edit</button>
                        <button onClick={() => handleDelete(row)} className="text-red-600 hover:text-red-800 text-sm font-medium">Delete</button>
                    </div>
                )} />
                <Pagination meta={meta} onPageChange={fetchItems} />
            </div>
            <Modal isOpen={showModal} onClose={() => setShowModal(false)} title={editing ? 'Edit Sermon' : 'Add Sermon'}>
                <form onSubmit={handleSubmit} className="space-y-4">
                    <FormField label="Title" name="title" value={form.title} onChange={handleChange} required placeholder="Sermon title" />
                    <FormField label="Description" name="description" type="richtext" value={form.description} onChange={handleChange} placeholder="Sermon description..." />
                    <div className="grid grid-cols-2 gap-4">
                        <FormField label="Speaker" name="speaker" value={form.speaker} onChange={handleChange} required placeholder="Speaker name" />
                        <FormField label="Scripture Reference" name="scripture_reference" value={form.scripture_reference} onChange={handleChange} placeholder="e.g. John 3:16-17" />
                    </div>
                    <div className="grid grid-cols-2 gap-4">
                        <FormField label="Series" name="series" value={form.series} onChange={handleChange} placeholder="Sermon series" />
                        <FormField label="Category" name="category" value={form.category} onChange={handleChange} placeholder="e.g. Sunday Service" />
                    </div>
                    <div className="grid grid-cols-2 gap-4">
                        <FormField label="Sermon Date" name="sermon_date" type="date" value={form.sermon_date} onChange={handleChange} required />
                        <FormField label="Duration" name="duration" value={form.duration} onChange={handleChange} placeholder="e.g. 45:00" />
                    </div>
                    <FormField label="Video URL" name="video_url" value={form.video_url} onChange={handleChange} placeholder="https://youtube.com/..." />
                    <FormField label="Audio URL" name="audio_url" value={form.audio_url} onChange={handleChange} placeholder="https://example.com/audio.mp3" />
                    <FormField label="Thumbnail" name="thumbnail" type="file" onChange={handleChange} />
                    {editing && editing.thumbnail_url && (
                        <div className="mb-4">
                            <span className="block text-sm font-medium text-gray-700 mb-1">Current Thumbnail</span>
                            <img src={editing.thumbnail_url} alt="Thumbnail" className="h-20 w-auto rounded-lg border" />
                        </div>
                    )}
                    <FormField label="Featured" name="is_featured" type="checkbox" value={form.is_featured} onChange={handleChange} />
                    <FormField label="Published" name="is_published" type="checkbox" value={form.is_published} onChange={handleChange} />
                    <FormField label="Tags" name="tags" value={form.tags} onChange={handleChange} placeholder="Comma-separated tags" />
                    <div className="flex justify-end gap-3 pt-2">
                        <button type="button" onClick={() => setShowModal(false)} className="px-4 py-2 border border-gray-300 text-gray-700 rounded-lg hover:bg-gray-50">Cancel</button>
                        <button type="submit" className="px-4 py-2 bg-indigo-600 text-white rounded-lg hover:bg-indigo-700">Save</button>
                    </div>
                </form>
            </Modal>
        </div>
    );
}
