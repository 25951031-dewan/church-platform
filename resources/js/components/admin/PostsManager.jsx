import React, { useState, useEffect } from 'react';
import { Modal, DataTable, Pagination, FormField, Alert } from '../shared/CrudPanel';
import { get, del, upload } from '../shared/api';

export default function PostsManager() {
    const [items, setItems] = useState([]);
    const [meta, setMeta] = useState(null);
    const [showModal, setShowModal] = useState(false);
    const [editing, setEditing] = useState(null);
    const [form, setForm] = useState({
        title: '', content: '', excerpt: '', category: '', status: 'draft',
        is_featured: false, author_name: '', published_at: '', meta_title: '',
        meta_description: '', tags: '', featured_image: null,
    });
    const [alert, setAlert] = useState(null);
    const [loading, setLoading] = useState(false);

    const fetchItems = async (page = 1) => {
        setLoading(true);
        try {
            const data = await get(`/api/posts?page=${page}`);
            setItems(data.data || []);
            setMeta(data);
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
            title: '', content: '', excerpt: '', category: '', status: 'draft',
            is_featured: false, author_name: '', published_at: '', meta_title: '',
            meta_description: '', tags: '', featured_image: null,
        });
        setShowModal(true);
    };

    const openEdit = (item) => {
        setEditing(item);
        setForm({
            title: item.title || '',
            content: item.content || '',
            excerpt: item.excerpt || '',
            category: item.category || '',
            status: item.status || 'draft',
            is_featured: !!item.is_featured,
            author_name: item.author_name || '',
            published_at: item.published_at || '',
            meta_title: item.meta_title || '',
            meta_description: item.meta_description || '',
            tags: item.tags || '',
            featured_image: null,
        });
        setShowModal(true);
    };

    const handleSubmit = async (e) => {
        e.preventDefault();
        try {
            const formData = new FormData();
            formData.append('title', form.title);
            formData.append('content', form.content);
            formData.append('excerpt', form.excerpt);
            formData.append('category', form.category);
            formData.append('status', form.status);
            formData.append('is_featured', form.is_featured ? '1' : '0');
            formData.append('author_name', form.author_name);
            formData.append('published_at', form.published_at);
            formData.append('meta_title', form.meta_title);
            formData.append('meta_description', form.meta_description);
            formData.append('tags', form.tags);
            if (form.featured_image) {
                formData.append('featured_image', form.featured_image);
            }

            if (editing) {
                formData.append('_method', 'PUT');
                await upload(`/api/posts/${editing.id}`, formData, 'POST');
                setAlert({ type: 'success', message: 'Post updated.' });
            } else {
                await upload('/api/posts', formData, 'POST');
                setAlert({ type: 'success', message: 'Post created.' });
            }
            setShowModal(false);
            fetchItems();
        } catch (e) { setAlert({ type: 'error', message: e.message }); }
    };

    const handleDelete = async (item) => {
        if (!confirm('Delete this post?')) return;
        try {
            await del(`/api/posts/${item.id}`);
            setAlert({ type: 'success', message: 'Post deleted.' });
            fetchItems();
        } catch (e) { setAlert({ type: 'error', message: e.message }); }
    };

    const statusBadge = (status) => {
        const colors = {
            draft: 'bg-gray-100 text-gray-800',
            published: 'bg-green-100 text-green-800',
            scheduled: 'bg-yellow-100 text-yellow-800',
        };
        return (
            <span className={`inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium ${colors[status] || 'bg-gray-100 text-gray-800'}`}>
                {status}
            </span>
        );
    };

    const getPermalink = (post) => {
        return window.location.origin + '/blog/' + (post.slug || '');
    };

    const copyLink = (post) => {
        const url = getPermalink(post);
        navigator.clipboard?.writeText(url).then(() => {
            setAlert({ type: 'success', message: 'Permalink copied to clipboard!' });
        }).catch(() => {
            const ta = document.createElement('textarea');
            ta.value = url; document.body.appendChild(ta); ta.select(); document.execCommand('copy');
            document.body.removeChild(ta);
            setAlert({ type: 'success', message: 'Permalink copied to clipboard!' });
        });
    };

    const columns = [
        {
            key: 'title', label: 'Title', render: (r) => (
                <div>
                    <div className="font-medium text-gray-900">{r.title}</div>
                    <div className="text-xs text-gray-400 mt-0.5">/{r.slug}</div>
                </div>
            ),
        },
        { key: 'category', label: 'Category' },
        { key: 'status', label: 'Status', render: (r) => statusBadge(r.status) },
        {
            key: 'is_featured', label: 'Featured', render: (r) => (
                <span className={`inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium ${r.is_featured ? 'bg-indigo-100 text-indigo-800' : 'bg-gray-100 text-gray-800'}`}>
                    {r.is_featured ? 'Featured' : 'No'}
                </span>
            ),
        },
        { key: 'view_count', label: 'Views', render: (r) => (
            <span className="text-sm text-gray-600">{r.view_count || 0}</span>
        )},
        { key: 'published_at', label: 'Published', render: (r) => r.published_at ? new Date(r.published_at).toLocaleDateString() : 'Not published' },
    ];

    const statusOptions = [
        { value: 'draft', label: 'Draft' },
        { value: 'published', label: 'Published' },
        { value: 'scheduled', label: 'Scheduled' },
    ];

    return (
        <div>
            <div className="flex items-center justify-between mb-6">
                <h2 className="text-2xl font-bold text-gray-800">Posts</h2>
                <button onClick={openCreate} className="px-4 py-2 bg-indigo-600 text-white rounded-lg text-sm font-medium hover:bg-indigo-700">+ Add Post</button>
            </div>
            <Alert {...alert} onClose={() => setAlert(null)} />
            <div className="bg-white rounded-xl shadow-sm border">
                <DataTable columns={columns} data={items} actions={(row) => (
                    <div className="flex gap-2">
                        {row.status === 'published' && (
                            <a href={getPermalink(row)} target="_blank" rel="noopener noreferrer" className="text-green-600 hover:text-green-800 text-sm font-medium">View</a>
                        )}
                        <button onClick={() => copyLink(row)} className="text-gray-500 hover:text-gray-700 text-sm font-medium">Copy Link</button>
                        <button onClick={() => openEdit(row)} className="text-indigo-600 hover:text-indigo-800 text-sm font-medium">Edit</button>
                        <button onClick={() => handleDelete(row)} className="text-red-600 hover:text-red-800 text-sm font-medium">Delete</button>
                    </div>
                )} />
                <Pagination meta={meta} onPageChange={fetchItems} />
            </div>
            <Modal isOpen={showModal} onClose={() => setShowModal(false)} title={editing ? 'Edit Post' : 'Add Post'}>
                <form onSubmit={handleSubmit} className="space-y-4">
                    <FormField label="Title" name="title" value={form.title} onChange={handleChange} required placeholder="Post title" />
                    {editing && editing.slug && (
                        <div className="flex items-center gap-2 px-3 py-2 bg-gray-50 border border-gray-200 rounded-lg">
                            <span className="text-xs text-gray-500 font-medium">Permalink:</span>
                            <code className="text-xs text-indigo-600 flex-1 truncate">{getPermalink(editing)}</code>
                            <button type="button" onClick={() => copyLink(editing)} className="text-xs text-indigo-600 hover:text-indigo-800 font-medium whitespace-nowrap">Copy</button>
                            <a href={getPermalink(editing)} target="_blank" rel="noopener noreferrer" className="text-xs text-green-600 hover:text-green-800 font-medium whitespace-nowrap">View</a>
                        </div>
                    )}
                    <FormField label="Content" name="content" type="richtext" value={form.content} onChange={handleChange} required placeholder="Write your post content..." />
                    <FormField label="Excerpt" name="excerpt" type="textarea" rows={2} value={form.excerpt} onChange={handleChange} placeholder="Brief summary..." />
                    <FormField label="Category" name="category" value={form.category} onChange={handleChange} placeholder="e.g. News, Devotional" />
                    <FormField label="Status" name="status" type="select" value={form.status} onChange={handleChange} options={statusOptions} />
                    <FormField label="Featured" name="is_featured" type="checkbox" value={form.is_featured} onChange={handleChange} />
                    <FormField label="Author Name" name="author_name" value={form.author_name} onChange={handleChange} placeholder="Author name" />
                    <FormField label="Publish Date" name="published_at" type="datetime-local" value={form.published_at} onChange={handleChange} />
                    <FormField label="Featured Image" name="featured_image" type="file" onChange={handleChange} />
                    {editing && editing.featured_image_url && (
                        <div className="mb-4">
                            <span className="block text-sm font-medium text-gray-700 mb-1">Current Image</span>
                            <img src={editing.featured_image_url} alt="Featured" className="h-20 w-auto rounded-lg border" />
                        </div>
                    )}
                    <FormField label="Meta Title" name="meta_title" value={form.meta_title} onChange={handleChange} placeholder="SEO title" />
                    <FormField label="Meta Description" name="meta_description" type="textarea" rows={2} value={form.meta_description} onChange={handleChange} placeholder="SEO description" />
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
