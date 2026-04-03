import React, { useState, useEffect } from 'react';
import { Modal, DataTable, Pagination, FormField, Alert } from '../shared/CrudPanel';
import { get, post, put, del } from '../shared/api';

function CategoryTree({ categories, level = 0, onEdit, onDelete, onAddChild }) {
    if (!categories || categories.length === 0) return null;
    return (
        <div className={level > 0 ? 'ml-6 border-l-2 border-gray-100 pl-3' : ''}>
            {categories.map(cat => (
                <div key={cat.id} className="mb-1">
                    <div className={`flex items-center gap-3 p-2.5 rounded-lg hover:bg-gray-50 group ${level === 0 ? 'bg-white border border-gray-100' : ''}`}>
                        {level > 0 && <span className="text-gray-300 text-xs">&#8627;</span>}
                        <div className="flex-1 min-w-0">
                            <div className="flex items-center gap-2">
                                <span className="font-medium text-gray-900 text-sm">{cat.name}</span>
                                <span className="px-2 py-0.5 rounded-full text-xs font-medium bg-indigo-50 text-indigo-600">{cat.type}</span>
                                {!cat.is_active && <span className="px-2 py-0.5 rounded-full text-xs font-medium bg-red-50 text-red-600">Inactive</span>}
                                {(cat.children || []).length > 0 && (
                                    <span className="px-2 py-0.5 rounded-full text-xs font-medium bg-gray-100 text-gray-600">{cat.children.length} sub</span>
                                )}
                            </div>
                            <div className="text-xs text-gray-400 mt-0.5">/{cat.slug} {cat.description ? `- ${cat.description.substring(0, 60)}` : ''}</div>
                        </div>
                        <div className="flex gap-1 opacity-0 group-hover:opacity-100 transition-opacity">
                            <button onClick={() => onAddChild(cat)} className="px-2 py-1 text-xs text-green-600 hover:bg-green-50 rounded font-medium">+ Sub</button>
                            <button onClick={() => onEdit(cat)} className="px-2 py-1 text-xs text-indigo-600 hover:bg-indigo-50 rounded font-medium">Edit</button>
                            <button onClick={() => onDelete(cat)} className="px-2 py-1 text-xs text-red-600 hover:bg-red-50 rounded font-medium">Delete</button>
                        </div>
                    </div>
                    {cat.children && cat.children.length > 0 && (
                        <CategoryTree categories={cat.children} level={level + 1} onEdit={onEdit} onDelete={onDelete} onAddChild={onAddChild} />
                    )}
                </div>
            ))}
        </div>
    );
}

export default function CategoriesManager() {
    const [items, setItems] = useState([]);
    const [allCategories, setAllCategories] = useState([]);
    const [meta, setMeta] = useState({});
    const [modal, setModal] = useState(false);
    const [editing, setEditing] = useState(null);
    const [form, setForm] = useState({ name: '', type: 'post', parent_id: '', description: '', sort_order: 0, is_active: true });
    const [alert, setAlert] = useState(null);
    const [typeFilter, setTypeFilter] = useState('');
    const [viewMode, setViewMode] = useState('tree');

    useEffect(() => { fetchItems(); fetchAllFlat(); }, [typeFilter]);

    const fetchItems = async (page = 1) => {
        try {
            let url = `/api/categories?tree=1`;
            if (typeFilter) url += `&type=${typeFilter}`;
            const data = await get(url);
            setItems(data.data || []);
        } catch (e) { setAlert({ type: 'error', message: e.message }); }
    };

    const fetchAllFlat = async () => {
        try {
            const data = await get('/api/categories');
            setAllCategories(data.data || []);
        } catch (e) { /* silent */ }
    };

    const openCreate = () => {
        setEditing(null);
        setForm({ name: '', type: typeFilter || 'post', parent_id: '', description: '', sort_order: 0, is_active: true });
        setModal(true);
    };

    const openCreateChild = (parent) => {
        setEditing(null);
        setForm({ name: '', type: parent.type, parent_id: String(parent.id), description: '', sort_order: 0, is_active: true });
        setModal(true);
    };

    const openEdit = (item) => {
        setEditing(item);
        setForm({
            name: item.name || '', type: item.type || 'post', parent_id: item.parent_id ? String(item.parent_id) : '',
            description: item.description || '', sort_order: item.sort_order || 0, is_active: item.is_active ?? true,
        });
        setModal(true);
    };

    const handleSubmit = async (e) => {
        if (e) e.preventDefault();
        try {
            const payload = { ...form };
            if (!payload.parent_id) delete payload.parent_id;
            if (editing) {
                await put(`/api/categories/${editing.id}`, payload);
                setAlert({ type: 'success', message: 'Category updated.' });
            } else {
                await post('/api/categories', payload);
                setAlert({ type: 'success', message: 'Category created.' });
            }
            setModal(false);
            fetchItems();
            fetchAllFlat();
        } catch (e) { setAlert({ type: 'error', message: e.message }); }
    };

    const handleDelete = async (item) => {
        const childCount = (item.children || []).length;
        const msg = childCount > 0
            ? `Delete category "${item.name}" and its ${childCount} subcategories?`
            : `Delete category "${item.name}"?`;
        if (!confirm(msg)) return;
        try {
            await del(`/api/categories/${item.id}`);
            setAlert({ type: 'success', message: 'Category deleted.' });
            fetchItems();
            fetchAllFlat();
        } catch (e) { setAlert({ type: 'error', message: e.message }); }
    };

    const handleChange = (e) => {
        const { name, value, type, checked } = e.target;
        setForm(prev => ({ ...prev, [name]: type === 'checkbox' ? checked : value }));
    };

    const typeOptions = [
        { value: 'post', label: 'Post' }, { value: 'page', label: 'Page' },
        { value: 'sermon', label: 'Sermon' }, { value: 'book', label: 'Book' },
        { value: 'bible-study', label: 'Bible Study' },
    ];

    // Build flat list of parent options (excluding current editing item and its children)
    const getParentOptions = () => {
        const opts = [{ value: '', label: 'None (Top Level)' }];
        const flatten = (cats, prefix = '') => {
            cats.forEach(c => {
                if (editing && c.id === editing.id) return;
                opts.push({ value: String(c.id), label: prefix + c.name });
                if (c.children) flatten(c.children, prefix + '-- ');
            });
        };
        flatten(items);
        return opts;
    };

    const typeCounts = {};
    const countAll = (cats) => {
        cats.forEach(c => {
            typeCounts[c.type] = (typeCounts[c.type] || 0) + 1;
            if (c.children) countAll(c.children);
        });
    };
    countAll(allCategories);

    return (
        <div>
            <div className="flex items-center justify-between mb-6">
                <div>
                    <h2 className="text-2xl font-bold text-gray-800">Categories</h2>
                    <p className="text-sm text-gray-500 mt-1">Organize content with hierarchical categories</p>
                </div>
                <div className="flex gap-2">
                    <div className="flex bg-gray-100 rounded-lg p-0.5">
                        <button onClick={() => setViewMode('tree')} className={`px-3 py-1.5 rounded-md text-xs font-medium transition-colors ${viewMode === 'tree' ? 'bg-white text-gray-900 shadow-sm' : 'text-gray-500'}`}>
                            <i className="fas fa-sitemap mr-1"></i>Tree
                        </button>
                        <button onClick={() => setViewMode('list')} className={`px-3 py-1.5 rounded-md text-xs font-medium transition-colors ${viewMode === 'list' ? 'bg-white text-gray-900 shadow-sm' : 'text-gray-500'}`}>
                            <i className="fas fa-list mr-1"></i>List
                        </button>
                    </div>
                    <button onClick={openCreate} className="px-4 py-2 bg-indigo-600 text-white rounded-lg text-sm font-medium hover:bg-indigo-700">+ Add Category</button>
                </div>
            </div>
            <Alert {...alert} onClose={() => setAlert(null)} />

            {/* Type Filter Tabs */}
            <div className="mb-4 flex gap-2 flex-wrap">
                <button onClick={() => setTypeFilter('')} className={`px-3 py-1.5 rounded-lg text-sm font-medium ${!typeFilter ? 'bg-indigo-600 text-white' : 'bg-gray-100 text-gray-700 hover:bg-gray-200'}`}>
                    All ({allCategories.length})
                </button>
                {typeOptions.map(opt => (
                    <button key={opt.value} onClick={() => setTypeFilter(opt.value)} className={`px-3 py-1.5 rounded-lg text-sm font-medium ${typeFilter === opt.value ? 'bg-indigo-600 text-white' : 'bg-gray-100 text-gray-700 hover:bg-gray-200'}`}>
                        {opt.label} ({typeCounts[opt.value] || 0})
                    </button>
                ))}
            </div>

            {/* Tree View */}
            {viewMode === 'tree' ? (
                <div className="bg-white rounded-xl shadow-sm border p-4 space-y-1">
                    {items.length === 0 ? (
                        <p className="text-gray-400 text-center py-8">No categories found. Create your first category to get started.</p>
                    ) : (
                        <CategoryTree categories={items} onEdit={openEdit} onDelete={handleDelete} onAddChild={openCreateChild} />
                    )}
                </div>
            ) : (
                <div className="bg-white rounded-xl shadow-sm border">
                    <DataTable columns={[
                        { key: 'name', label: 'Name', render: (r) => (
                            <div>
                                <div className="font-medium text-gray-900">{r.parent_id ? '-- ' : ''}{r.name}</div>
                                <div className="text-xs text-gray-400">/{r.slug}</div>
                            </div>
                        )},
                        { key: 'type', label: 'Type', render: (r) => <span className="px-2 py-0.5 rounded-full text-xs font-medium bg-indigo-100 text-indigo-700">{r.type}</span> },
                        { key: 'children', label: 'Sub-categories', render: (r) => (r.children || []).length },
                        { key: 'sort_order', label: 'Order' },
                        { key: 'is_active', label: 'Status', render: (r) => <span className={`px-2 py-0.5 rounded-full text-xs font-medium ${r.is_active ? 'bg-green-100 text-green-700' : 'bg-red-100 text-red-700'}`}>{r.is_active ? 'Active' : 'Inactive'}</span> },
                    ]} data={allCategories} actions={(r) => (
                        <div className="flex gap-2">
                            <button onClick={() => openCreateChild(r)} className="text-green-600 hover:text-green-800 text-sm font-medium">+ Sub</button>
                            <button onClick={() => openEdit(r)} className="text-indigo-600 hover:text-indigo-800 text-sm font-medium">Edit</button>
                            <button onClick={() => handleDelete(r)} className="text-red-600 hover:text-red-800 text-sm font-medium">Delete</button>
                        </div>
                    )} />
                </div>
            )}

            {/* Create/Edit Modal */}
            <Modal isOpen={modal} onClose={() => setModal(false)} title={editing ? 'Edit Category' : 'New Category'}>
                <form onSubmit={handleSubmit} className="space-y-4">
                    <FormField label="Name" name="name" value={form.name} onChange={handleChange} required />
                    <FormField label="Type" name="type" type="select" value={form.type} onChange={handleChange} options={typeOptions} />
                    <FormField label="Parent Category" name="parent_id" type="select" value={form.parent_id} onChange={handleChange} options={getParentOptions()} />
                    <FormField label="Description" name="description" type="textarea" value={form.description} onChange={handleChange} rows={2} />
                    <FormField label="Sort Order" name="sort_order" type="number" value={form.sort_order} onChange={handleChange} />
                    <FormField label="Active" name="is_active" type="checkbox" value={form.is_active} onChange={handleChange} />
                    <div className="flex justify-end gap-3 pt-2">
                        <button type="button" onClick={() => setModal(false)} className="px-4 py-2 border border-gray-300 text-gray-700 rounded-lg hover:bg-gray-50">Cancel</button>
                        <button type="submit" className="px-4 py-2 bg-indigo-600 text-white rounded-lg hover:bg-indigo-700">{editing ? 'Update' : 'Create'}</button>
                    </div>
                </form>
            </Modal>
        </div>
    );
}
