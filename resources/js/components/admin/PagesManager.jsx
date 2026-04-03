import React, { useState, useEffect } from 'react';
import { Modal, DataTable, Pagination, FormField, Alert } from '../shared/CrudPanel';
import { get, post, put, del, upload } from '../shared/api';

export default function PagesManager() {
    const [items, setItems] = useState([]);
    const [meta, setMeta] = useState({});
    const [modal, setModal] = useState(false);
    const [editing, setEditing] = useState(null);
    const [form, setForm] = useState({ title: '', content: '', excerpt: '', parent_id: '', status: 'draft', meta_title: '', meta_description: '', meta_keywords: '', sort_order: 0 });
    const [files, setFiles] = useState({ featured_image: null });
    const [alert, setAlert] = useState(null);
    const [allPages, setAllPages] = useState([]);

    useEffect(() => { fetchItems(); fetchAllPages(); }, []);

    const fetchItems = async (page = 1) => {
        try {
            const data = await get(`/api/pages?page=${page}`);
            setItems(data.data || []);
            setMeta(data);
        } catch (e) { setAlert({ type: 'error', message: e.message }); }
    };

    const fetchAllPages = async () => {
        try {
            const data = await get('/api/pages?per_page=100');
            setAllPages(data.data || []);
        } catch (e) { /* ignore */ }
    };

    const openCreate = () => {
        setEditing(null);
        setForm({ title: '', content: '', excerpt: '', parent_id: '', status: 'draft', meta_title: '', meta_description: '', meta_keywords: '', sort_order: 0 });
        setFiles({ featured_image: null });
        setModal(true);
    };

    const openEdit = (item) => {
        setEditing(item);
        setForm({
            title: item.title || '',
            content: item.content || '',
            excerpt: item.excerpt || '',
            parent_id: item.parent_id || '',
            status: item.status || 'draft',
            meta_title: item.meta_title || '',
            meta_description: item.meta_description || '',
            meta_keywords: item.meta_keywords || '',
            sort_order: item.sort_order || 0,
        });
        setFiles({ featured_image: null });
        setModal(true);
    };

    const handleSubmit = async () => {
        try {
            const formData = new FormData();
            Object.entries(form).forEach(([key, val]) => {
                if (val !== null && val !== undefined && val !== '') formData.append(key, val);
            });
            if (files.featured_image) formData.append('featured_image', files.featured_image);

            if (editing) {
                formData.append('_method', 'PUT');
                await upload(`/api/pages/${editing.id}`, formData, 'POST');
                setAlert({ type: 'success', message: 'Page updated.' });
            } else {
                await upload('/api/pages', formData, 'POST');
                setAlert({ type: 'success', message: 'Page created.' });
            }
            setModal(false);
            fetchItems(meta.current_page || 1);
            fetchAllPages();
        } catch (e) { setAlert({ type: 'error', message: e.message }); }
    };

    const handleDelete = async (item) => {
        if (!confirm('Delete this page?')) return;
        try {
            await del(`/api/pages/${item.id}`);
            setAlert({ type: 'success', message: 'Page deleted.' });
            fetchItems(meta.current_page || 1);
            fetchAllPages();
        } catch (e) { setAlert({ type: 'error', message: e.message }); }
    };

    const handleChange = (e) => {
        const { name, value } = e.target;
        setForm(prev => ({ ...prev, [name]: value }));
    };

    const handleFileChange = (e) => {
        const { name, files: f } = e.target;
        if (f && f[0]) setFiles(prev => ({ ...prev, [name]: f[0] }));
    };

    const columns = [
        { key: 'title', label: 'Title' },
        { key: 'slug', label: 'Slug', render: (item) => <span className="text-gray-500 text-xs">/page/{item.slug}</span> },
        { key: 'status', label: 'Status', render: (item) => <span className={`px-2 py-0.5 rounded-full text-xs font-medium ${item.status === 'published' ? 'bg-green-100 text-green-700' : 'bg-yellow-100 text-yellow-700'}`}>{item.status}</span> },
        { key: 'parent', label: 'Parent', render: (item) => item.parent ? item.parent.title : '-' },
        { key: 'sort_order', label: 'Order' },
    ];

    return (
        <div>
            <div className="flex items-center justify-between mb-6">
                <h2 className="text-2xl font-bold text-gray-800">Pages</h2>
                <button onClick={openCreate} className="px-4 py-2 bg-indigo-600 text-white rounded-lg text-sm font-medium hover:bg-indigo-700">+ Add Page</button>
            </div>
            <Alert {...alert} onClose={() => setAlert(null)} />
            <DataTable columns={columns} data={items} actions={(item) => (
                <div className="flex gap-2">
                    {item.status === 'published' && (
                        <a href={`/page/${item.slug}`} target="_blank" rel="noopener noreferrer" className="text-green-600 hover:text-green-800 text-sm font-medium">View</a>
                    )}
                    <button onClick={() => openEdit(item)} className="text-indigo-600 hover:text-indigo-800 text-sm font-medium">Edit</button>
                    <button onClick={() => handleDelete(item)} className="text-red-600 hover:text-red-800 text-sm font-medium">Delete</button>
                </div>
            )} />
            <Pagination meta={meta} onPageChange={fetchItems} />
            {modal && (
                <Modal isOpen={true} title={editing ? 'Edit Page' : 'New Page'} onClose={() => setModal(false)}>
                    <FormField label="Title" name="title" value={form.title} onChange={handleChange} required />
                    <FormField label="Content" name="content" type="richtext" value={form.content} onChange={handleChange} placeholder="Write page content..." />
                    <FormField label="Excerpt" name="excerpt" type="textarea" value={form.excerpt} onChange={handleChange} rows={2} />
                    <div className="grid grid-cols-2 gap-4">
                        <FormField label="Status" name="status" type="select" value={form.status} onChange={handleChange} options={[{ value: 'draft', label: 'Draft' }, { value: 'published', label: 'Published' }]} />
                        <FormField label="Parent Page" name="parent_id" type="select" value={form.parent_id} onChange={handleChange} options={[{ value: '', label: 'None (Top Level)' }, ...allPages.filter(p => !editing || p.id !== editing.id).map(p => ({ value: p.id, label: p.title }))]} />
                    </div>
                    <FormField label="Featured Image" name="featured_image" type="file" onChange={handleFileChange} />
                    <FormField label="Sort Order" name="sort_order" type="number" value={form.sort_order} onChange={handleChange} />
                    <div className="mt-4 p-4 bg-gray-50 rounded-lg">
                        <h4 className="text-sm font-semibold text-gray-700 mb-3">SEO Settings</h4>
                        <FormField label="Meta Title" name="meta_title" value={form.meta_title} onChange={handleChange} placeholder="SEO title" />
                        <FormField label="Meta Description" name="meta_description" type="textarea" value={form.meta_description} onChange={handleChange} rows={2} placeholder="SEO description" />
                        <FormField label="Meta Keywords" name="meta_keywords" value={form.meta_keywords} onChange={handleChange} placeholder="keyword1, keyword2" />
                    </div>
                    <div className="flex justify-end gap-3 mt-4">
                        <button onClick={() => setModal(false)} className="px-4 py-2 border border-gray-300 rounded-lg text-sm text-gray-700 hover:bg-gray-50">Cancel</button>
                        <button onClick={handleSubmit} className="px-4 py-2 bg-indigo-600 text-white rounded-lg text-sm font-medium hover:bg-indigo-700">{editing ? 'Update' : 'Create'}</button>
                    </div>
                </Modal>
            )}
        </div>
    );
}
