import React, { useState, useEffect } from 'react';
import { Modal, DataTable, Pagination, FormField, Alert } from '../shared/CrudPanel';
import { get, post, put, del, upload, extractPaginatedData } from '../shared/api';

const categoryOptions = [
    { value: 'old-testament', label: 'Old Testament' },
    { value: 'new-testament', label: 'New Testament' },
    { value: 'topical', label: 'Topical' },
    { value: 'devotional', label: 'Devotional' },
];

const difficultyOptions = [
    { value: 'beginner', label: 'Beginner' },
    { value: 'intermediate', label: 'Intermediate' },
    { value: 'advanced', label: 'Advanced' },
];

const defaultForm = {
    title: '', description: '', content: '', scripture_reference: '',
    category: '', difficulty: '', duration_minutes: '', author: '',
    is_featured: false, is_published: false, tags: '',
    cover_image: null, attachment: null,
};

export default function BibleStudiesManager() {
    const [items, setItems] = useState([]);
    const [meta, setMeta] = useState(null);
    const [showModal, setShowModal] = useState(false);
    const [editing, setEditing] = useState(null);
    const [form, setForm] = useState({ ...defaultForm });
    const [alert, setAlert] = useState(null);

    const fetchItems = async (page = 1) => {
        try {
            const data = await get(`/api/bible-studies?page=${page}`);
            const { items, meta } = extractPaginatedData(data);
            setItems(items);
            setMeta(meta);
        } catch (e) {
            setAlert({ type: 'error', message: e.message });
        }
    };

    useEffect(() => { fetchItems(); }, []);

    const handleChange = (e) => {
        const { name, type, value, checked, files } = e.target;
        if (type === 'file') {
            setForm({ ...form, [name]: files[0] });
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
            content: item.content || '',
            scripture_reference: item.scripture_reference || '',
            category: item.category || '',
            difficulty: item.difficulty || '',
            duration_minutes: item.duration_minutes || '',
            author: item.author || '',
            is_featured: !!item.is_featured,
            is_published: !!item.is_published,
            tags: item.tags || '',
            cover_image: null,
            attachment: null,
        });
        setShowModal(true);
    };

    const handleSubmit = async (e) => {
        e.preventDefault();
        try {
            const formData = new FormData();
            formData.append('title', form.title);
            formData.append('description', form.description);
            formData.append('content', form.content);
            formData.append('scripture_reference', form.scripture_reference);
            formData.append('category', form.category);
            formData.append('difficulty', form.difficulty);
            formData.append('duration_minutes', form.duration_minutes);
            formData.append('author', form.author);
            formData.append('is_featured', form.is_featured ? '1' : '0');
            formData.append('is_published', form.is_published ? '1' : '0');
            formData.append('tags', form.tags);
            if (form.cover_image) formData.append('cover_image', form.cover_image);
            if (form.attachment) formData.append('attachment', form.attachment);

            if (editing) {
                formData.append('_method', 'PUT');
                await upload(`/api/bible-studies/${editing.id}`, formData, 'POST');
                setAlert({ type: 'success', message: 'Bible study updated successfully.' });
            } else {
                await upload('/api/bible-studies', formData);
                setAlert({ type: 'success', message: 'Bible study created successfully.' });
            }
            setShowModal(false);
            fetchItems();
        } catch (e) {
            setAlert({ type: 'error', message: e.message });
        }
    };

    const handleDelete = async (item) => {
        if (!confirm('Are you sure you want to delete this bible study?')) return;
        try {
            await del(`/api/bible-studies/${item.id}`);
            setAlert({ type: 'success', message: 'Bible study deleted successfully.' });
            fetchItems();
        } catch (e) {
            setAlert({ type: 'error', message: e.message });
        }
    };

    const categoryLabel = (value) => {
        const opt = categoryOptions.find(o => o.value === value);
        return opt ? opt.label : value;
    };

    const difficultyColors = {
        beginner: 'bg-green-100 text-green-800',
        intermediate: 'bg-yellow-100 text-yellow-800',
        advanced: 'bg-red-100 text-red-800',
    };

    const columns = [
        { key: 'title', label: 'Title' },
        {
            key: 'category', label: 'Category',
            render: (row) => (
                <span className="inline-flex px-2 py-0.5 text-xs font-semibold rounded-full bg-purple-100 text-purple-800">
                    {categoryLabel(row.category)}
                </span>
            ),
        },
        {
            key: 'difficulty', label: 'Difficulty',
            render: (row) => (
                <span className={`inline-flex px-2 py-0.5 text-xs font-semibold rounded-full ${difficultyColors[row.difficulty] || 'bg-gray-100 text-gray-600'}`}>
                    {row.difficulty ? row.difficulty.charAt(0).toUpperCase() + row.difficulty.slice(1) : '-'}
                </span>
            ),
        },
        { key: 'scripture_reference', label: 'Scripture' },
        {
            key: 'is_featured', label: 'Featured',
            render: (row) => (
                <span className={`inline-flex px-2 py-0.5 text-xs font-semibold rounded-full ${row.is_featured ? 'bg-green-100 text-green-800' : 'bg-gray-100 text-gray-600'}`}>
                    {row.is_featured ? 'Yes' : 'No'}
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
                <h2 className="text-2xl font-bold text-gray-800">Bible Studies</h2>
                <button onClick={openCreate} className="px-4 py-2 bg-indigo-600 text-white rounded-lg text-sm font-medium hover:bg-indigo-700">+ Add Bible Study</button>
            </div>
            <Alert {...alert} onClose={() => setAlert(null)} />
            <div className="bg-white rounded-xl shadow-sm border">
                <DataTable columns={columns} data={items} actions={(row) => (
                    <div className="flex gap-2">
                        {row.slug && row.is_published && (
                            <a href={`/bible-studies/${row.slug}`} target="_blank" rel="noopener noreferrer" className="text-green-600 hover:text-green-800 text-sm font-medium">View</a>
                        )}
                        <button onClick={() => openEdit(row)} className="text-indigo-600 hover:text-indigo-800 text-sm font-medium">Edit</button>
                        <button onClick={() => handleDelete(row)} className="text-red-600 hover:text-red-800 text-sm font-medium">Delete</button>
                    </div>
                )} />
                <Pagination meta={meta} onPageChange={fetchItems} />
            </div>
            <Modal isOpen={showModal} onClose={() => setShowModal(false)} title={editing ? 'Edit Bible Study' : 'Add Bible Study'}>
                <form onSubmit={handleSubmit} className="space-y-4">
                    <FormField label="Title" name="title" value={form.title} onChange={handleChange} required />
                    <FormField label="Description" name="description" type="textarea" value={form.description} onChange={handleChange} rows={3} />
                    <FormField label="Content" name="content" type="richtext" value={form.content} onChange={handleChange} placeholder="Write Bible study content..." />
                    <FormField label="Scripture Reference" name="scripture_reference" value={form.scripture_reference} onChange={handleChange} placeholder="e.g. Romans 8:28" />
                    <div className="grid grid-cols-2 gap-4">
                        <FormField label="Category" name="category" type="select" value={form.category} onChange={handleChange} options={categoryOptions} />
                        <FormField label="Difficulty" name="difficulty" type="select" value={form.difficulty} onChange={handleChange} options={difficultyOptions} />
                    </div>
                    <div className="grid grid-cols-2 gap-4">
                        <FormField label="Duration (minutes)" name="duration_minutes" type="number" value={form.duration_minutes} onChange={handleChange} />
                        <FormField label="Author" name="author" value={form.author} onChange={handleChange} />
                    </div>
                    <FormField label="Tags" name="tags" value={form.tags} onChange={handleChange} placeholder="Comma-separated tags" />
                    <div className="grid grid-cols-2 gap-4">
                        <FormField label="Featured" name="is_featured" type="checkbox" value={form.is_featured} onChange={handleChange} />
                        <FormField label="Published" name="is_published" type="checkbox" value={form.is_published} onChange={handleChange} />
                    </div>
                    <FormField label="Cover Image" name="cover_image" type="file" onChange={handleChange} />
                    <FormField label="Attachment" name="attachment" type="file" onChange={handleChange} />
                    <div className="flex justify-end gap-3 pt-2">
                        <button type="button" onClick={() => setShowModal(false)} className="px-4 py-2 border border-gray-300 text-gray-700 rounded-lg hover:bg-gray-50">Cancel</button>
                        <button type="submit" className="px-4 py-2 bg-indigo-600 text-white rounded-lg hover:bg-indigo-700">Save</button>
                    </div>
                </form>
            </Modal>
        </div>
    );
}
