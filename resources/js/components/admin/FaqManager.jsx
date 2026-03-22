import { useMutation, useQuery, useQueryClient } from '@tanstack/react-query';
import axios from 'axios';
import { useState } from 'react';

function CategoryForm({ initial = {}, onSave, onCancel }) {
    const [name, setName]               = useState(initial.name ?? '');
    const [description, setDescription] = useState(initial.description ?? '');
    const [isActive, setIsActive]       = useState(initial.is_active ?? true);

    function handleSubmit(e) {
        e.preventDefault();
        onSave({ name, description, is_active: isActive });
    }

    return (
        <form onSubmit={handleSubmit} className="space-y-3">
            <input
                className="block w-full rounded border border-gray-300 px-3 py-2 text-sm focus:border-blue-500 focus:outline-none"
                placeholder="Category name *"
                value={name}
                onChange={e => setName(e.target.value)}
                required
            />
            <textarea
                className="block w-full rounded border border-gray-300 px-3 py-2 text-sm focus:border-blue-500 focus:outline-none"
                placeholder="Description (optional)"
                rows={2}
                value={description}
                onChange={e => setDescription(e.target.value)}
            />
            <label className="flex items-center gap-2 text-sm text-gray-700">
                <input type="checkbox" checked={isActive} onChange={e => setIsActive(e.target.checked)} />
                Active
            </label>
            <div className="flex gap-2">
                <button type="submit" className="rounded bg-blue-600 px-4 py-2 text-sm font-medium text-white hover:bg-blue-700">
                    Save
                </button>
                <button type="button" onClick={onCancel} className="rounded border border-gray-300 px-4 py-2 text-sm text-gray-700 hover:bg-gray-50">
                    Cancel
                </button>
            </div>
        </form>
    );
}

function FaqForm({ categories, initial = {}, onSave, onCancel }) {
    const [categoryId,   setCategoryId]   = useState(initial.faq_category_id ?? '');
    const [question,     setQuestion]     = useState(initial.question ?? '');
    const [answer,       setAnswer]       = useState(initial.answer ?? '');
    const [isPublished,  setIsPublished]  = useState(initial.is_published ?? true);

    function handleSubmit(e) {
        e.preventDefault();
        onSave({ faq_category_id: categoryId, question, answer, is_published: isPublished });
    }

    return (
        <form onSubmit={handleSubmit} className="space-y-3">
            <select
                className="block w-full rounded border border-gray-300 px-3 py-2 text-sm focus:border-blue-500 focus:outline-none"
                value={categoryId}
                onChange={e => setCategoryId(e.target.value)}
                required
            >
                <option value="">Select category *</option>
                {categories.map(c => <option key={c.id} value={c.id}>{c.name}</option>)}
            </select>
            <input
                className="block w-full rounded border border-gray-300 px-3 py-2 text-sm focus:border-blue-500 focus:outline-none"
                placeholder="Question *"
                value={question}
                onChange={e => setQuestion(e.target.value)}
                required
            />
            <textarea
                className="block w-full rounded border border-gray-300 px-3 py-2 text-sm focus:border-blue-500 focus:outline-none"
                placeholder="Answer (HTML supported) *"
                rows={5}
                value={answer}
                onChange={e => setAnswer(e.target.value)}
                required
            />
            <label className="flex items-center gap-2 text-sm text-gray-700">
                <input type="checkbox" checked={isPublished} onChange={e => setIsPublished(e.target.checked)} />
                Published
            </label>
            <div className="flex gap-2">
                <button type="submit" className="rounded bg-blue-600 px-4 py-2 text-sm font-medium text-white hover:bg-blue-700">
                    Save
                </button>
                <button type="button" onClick={onCancel} className="rounded border border-gray-300 px-4 py-2 text-sm text-gray-700 hover:bg-gray-50">
                    Cancel
                </button>
            </div>
        </form>
    );
}

export default function FaqManager() {
    const queryClient   = useQueryClient();
    const [tab, setTab] = useState('faqs');
    const [editing, setEditing] = useState(null);
    const [creating, setCreating] = useState(false);

    const { data: categories = [] } = useQuery({
        queryKey: ['admin-faq-categories'],
        queryFn:  () => axios.get('/api/v1/admin/faq/categories').then(r => r.data),
    });

    const { data: faqs } = useQuery({
        queryKey: ['admin-faq-faqs'],
        queryFn:  () => axios.get('/api/v1/admin/faq/faqs').then(r => r.data),
    });

    function invalidate() {
        queryClient.invalidateQueries({ queryKey: ['admin-faq-categories'] });
        queryClient.invalidateQueries({ queryKey: ['admin-faq-faqs'] });
        setEditing(null);
        setCreating(false);
    }

    const saveCategory = useMutation({
        mutationFn: (data) => editing
            ? axios.patch(`/api/v1/admin/faq/categories/${editing.id}`, data)
            : axios.post('/api/v1/admin/faq/categories', data),
        onSuccess: invalidate,
    });

    const deleteCategory = useMutation({
        mutationFn: (id) => axios.delete(`/api/v1/admin/faq/categories/${id}`),
        onSuccess: invalidate,
    });

    const saveFaq = useMutation({
        mutationFn: (data) => editing
            ? axios.patch(`/api/v1/admin/faq/faqs/${editing.id}`, data)
            : axios.post('/api/v1/admin/faq/faqs', data),
        onSuccess: invalidate,
    });

    const deleteFaq = useMutation({
        mutationFn: (id) => axios.delete(`/api/v1/admin/faq/faqs/${id}`),
        onSuccess: invalidate,
    });

    return (
        <div className="mx-auto max-w-4xl px-4 py-8 sm:px-6">
            <div className="mb-6 flex items-center justify-between">
                <h1 className="text-xl font-bold text-gray-900">FAQ Manager</h1>
                <button
                    onClick={() => { setCreating(true); setEditing(null); }}
                    className="rounded bg-blue-600 px-4 py-2 text-sm font-medium text-white hover:bg-blue-700"
                >
                    + New {tab === 'categories' ? 'Category' : 'FAQ'}
                </button>
            </div>

            {/* Tabs */}
            <div className="mb-6 flex gap-1 rounded-lg border border-gray-200 bg-gray-50 p-1 w-fit">
                {['faqs', 'categories'].map(t => (
                    <button
                        key={t}
                        onClick={() => { setTab(t); setEditing(null); setCreating(false); }}
                        className={`rounded px-4 py-1.5 text-sm font-medium capitalize transition ${
                            tab === t ? 'bg-white text-gray-900 shadow' : 'text-gray-500 hover:text-gray-700'
                        }`}
                    >
                        {t}
                    </button>
                ))}
            </div>

            {/* Create form */}
            {creating && tab === 'categories' && (
                <div className="mb-6 rounded-lg border border-blue-200 bg-blue-50 p-4">
                    <CategoryForm onSave={d => saveCategory.mutate(d)} onCancel={() => setCreating(false)} />
                </div>
            )}
            {creating && tab === 'faqs' && (
                <div className="mb-6 rounded-lg border border-blue-200 bg-blue-50 p-4">
                    <FaqForm categories={categories} onSave={d => saveFaq.mutate(d)} onCancel={() => setCreating(false)} />
                </div>
            )}

            {/* Categories list */}
            {tab === 'categories' && (
                <div className="space-y-2">
                    {categories.map(cat => (
                        <div key={cat.id} className="rounded-lg border border-gray-200 bg-white p-4">
                            {editing?.id === cat.id ? (
                                <CategoryForm
                                    initial={cat}
                                    onSave={d => saveCategory.mutate(d)}
                                    onCancel={() => setEditing(null)}
                                />
                            ) : (
                                <div className="flex items-center justify-between">
                                    <div>
                                        <span className="font-medium text-gray-900">{cat.name}</span>
                                        {!cat.is_active && (
                                            <span className="ml-2 rounded bg-gray-100 px-2 py-0.5 text-xs text-gray-500">Inactive</span>
                                        )}
                                        {cat.description && (
                                            <p className="mt-0.5 text-sm text-gray-500">{cat.description}</p>
                                        )}
                                    </div>
                                    <div className="flex gap-2">
                                        <button onClick={() => setEditing(cat)} className="text-sm text-blue-600 hover:underline">Edit</button>
                                        <button
                                            onClick={() => window.confirm('Delete category?') && deleteCategory.mutate(cat.id)}
                                            className="text-sm text-red-500 hover:underline"
                                        >
                                            Delete
                                        </button>
                                    </div>
                                </div>
                            )}
                        </div>
                    ))}
                </div>
            )}

            {/* FAQs list */}
            {tab === 'faqs' && (
                <div className="space-y-2">
                    {faqs?.data?.map(faq => (
                        <div key={faq.id} className="rounded-lg border border-gray-200 bg-white p-4">
                            {editing?.id === faq.id ? (
                                <FaqForm
                                    categories={categories}
                                    initial={faq}
                                    onSave={d => saveFaq.mutate(d)}
                                    onCancel={() => setEditing(null)}
                                />
                            ) : (
                                <div className="flex items-start justify-between gap-4">
                                    <div>
                                        <p className="font-medium text-gray-900">{faq.question}</p>
                                        <p className="mt-0.5 text-xs text-gray-400">
                                            {faq.category?.name} · {faq.is_published ? 'Published' : 'Draft'}
                                        </p>
                                    </div>
                                    <div className="flex shrink-0 gap-2">
                                        <button onClick={() => setEditing(faq)} className="text-sm text-blue-600 hover:underline">Edit</button>
                                        <button
                                            onClick={() => window.confirm('Delete FAQ?') && deleteFaq.mutate(faq.id)}
                                            className="text-sm text-red-500 hover:underline"
                                        >
                                            Delete
                                        </button>
                                    </div>
                                </div>
                            )}
                        </div>
                    ))}
                </div>
            )}
        </div>
    );
}
