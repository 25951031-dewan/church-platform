import React, { useState, useEffect } from 'react';
import { Modal, DataTable, Pagination, FormField, Alert } from '../shared/CrudPanel';
import { get, post, put, del, upload, extractPaginatedData } from '../shared/api';

export default function BooksManager() {
    const [items, setItems] = useState([]);
    const [meta, setMeta] = useState(null);
    const [showModal, setShowModal] = useState(false);
    const [editing, setEditing] = useState(null);
    const [form, setForm] = useState({
        title: '', author: '', description: '', category: '', isbn: '',
        publisher: '', publish_year: '', pages: '', is_featured: false, is_free: false,
        cover_image: null, pdf_file: null,
    });
    const [alert, setAlert] = useState(null);

    const fetchItems = async (page = 1) => {
        try {
            const data = await get(`/api/books?page=${page}`);
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
        setForm({
            title: '', author: '', description: '', category: '', isbn: '',
            publisher: '', publish_year: '', pages: '', is_featured: false, is_free: false,
            cover_image: null, pdf_file: null,
        });
        setShowModal(true);
    };

    const openEdit = (item) => {
        setEditing(item);
        setForm({
            title: item.title || '',
            author: item.author || '',
            description: item.description || '',
            category: item.category || '',
            isbn: item.isbn || '',
            publisher: item.publisher || '',
            publish_year: item.publish_year || '',
            pages: item.pages || '',
            is_featured: !!item.is_featured,
            is_free: !!item.is_free,
            cover_image: null,
            pdf_file: null,
        });
        setShowModal(true);
    };

    const handleSubmit = async (e) => {
        e.preventDefault();
        try {
            const formData = new FormData();
            formData.append('title', form.title);
            formData.append('author', form.author);
            formData.append('description', form.description);
            formData.append('category', form.category);
            formData.append('isbn', form.isbn);
            formData.append('publisher', form.publisher);
            formData.append('publish_year', form.publish_year);
            formData.append('pages', form.pages);
            formData.append('is_featured', form.is_featured ? '1' : '0');
            formData.append('is_free', form.is_free ? '1' : '0');
            if (form.cover_image) formData.append('cover_image', form.cover_image);
            if (form.pdf_file) formData.append('pdf_file', form.pdf_file);

            if (editing) {
                formData.append('_method', 'PUT');
                await upload(`/api/books/${editing.id}`, formData, 'POST');
                setAlert({ type: 'success', message: 'Book updated successfully.' });
            } else {
                await upload('/api/books', formData);
                setAlert({ type: 'success', message: 'Book created successfully.' });
            }
            setShowModal(false);
            fetchItems();
        } catch (e) {
            setAlert({ type: 'error', message: e.message });
        }
    };

    const handleDelete = async (item) => {
        if (!confirm('Are you sure you want to delete this book?')) return;
        try {
            await del(`/api/books/${item.id}`);
            setAlert({ type: 'success', message: 'Book deleted successfully.' });
            fetchItems();
        } catch (e) {
            setAlert({ type: 'error', message: e.message });
        }
    };

    const columns = [
        { key: 'title', label: 'Title' },
        { key: 'author', label: 'Author' },
        { key: 'category', label: 'Category' },
        {
            key: 'is_featured', label: 'Featured',
            render: (row) => (
                <span className={`inline-flex px-2 py-0.5 text-xs font-semibold rounded-full ${row.is_featured ? 'bg-green-100 text-green-800' : 'bg-gray-100 text-gray-600'}`}>
                    {row.is_featured ? 'Yes' : 'No'}
                </span>
            ),
        },
        {
            key: 'is_free', label: 'Free',
            render: (row) => (
                <span className={`inline-flex px-2 py-0.5 text-xs font-semibold rounded-full ${row.is_free ? 'bg-blue-100 text-blue-800' : 'bg-gray-100 text-gray-600'}`}>
                    {row.is_free ? 'Free' : 'Paid'}
                </span>
            ),
        },
    ];

    return (
        <div>
            <div className="flex items-center justify-between mb-6">
                <h2 className="text-2xl font-bold text-gray-800">Books</h2>
                <button onClick={openCreate} className="px-4 py-2 bg-indigo-600 text-white rounded-lg text-sm font-medium hover:bg-indigo-700">+ Add Book</button>
            </div>
            <Alert {...alert} onClose={() => setAlert(null)} />
            <div className="bg-white rounded-xl shadow-sm border">
                <DataTable columns={columns} data={items} actions={(row) => (
                    <div className="flex gap-2">
                        {row.slug && (
                            <a href={`/library/${row.slug}`} target="_blank" rel="noopener noreferrer" className="text-green-600 hover:text-green-800 text-sm font-medium">View</a>
                        )}
                        <button onClick={() => openEdit(row)} className="text-indigo-600 hover:text-indigo-800 text-sm font-medium">Edit</button>
                        <button onClick={() => handleDelete(row)} className="text-red-600 hover:text-red-800 text-sm font-medium">Delete</button>
                    </div>
                )} />
                <Pagination meta={meta} onPageChange={fetchItems} />
            </div>
            <Modal isOpen={showModal} onClose={() => setShowModal(false)} title={editing ? 'Edit Book' : 'Add Book'}>
                <form onSubmit={handleSubmit} className="space-y-4">
                    <FormField label="Title" name="title" value={form.title} onChange={handleChange} required />
                    <FormField label="Author" name="author" value={form.author} onChange={handleChange} required />
                    <FormField label="Description" name="description" type="richtext" value={form.description} onChange={handleChange} placeholder="Describe the book..." />
                    <FormField label="Category" name="category" value={form.category} onChange={handleChange} />
                    <FormField label="ISBN" name="isbn" value={form.isbn} onChange={handleChange} />
                    <FormField label="Publisher" name="publisher" value={form.publisher} onChange={handleChange} />
                    <div className="grid grid-cols-2 gap-4">
                        <FormField label="Publish Year" name="publish_year" type="number" value={form.publish_year} onChange={handleChange} />
                        <FormField label="Pages" name="pages" type="number" value={form.pages} onChange={handleChange} />
                    </div>
                    <div className="grid grid-cols-2 gap-4">
                        <FormField label="Featured" name="is_featured" type="checkbox" value={form.is_featured} onChange={handleChange} />
                        <FormField label="Free" name="is_free" type="checkbox" value={form.is_free} onChange={handleChange} />
                    </div>
                    <FormField label="Cover Image" name="cover_image" type="file" onChange={handleChange} />
                    <FormField label="PDF File" name="pdf_file" type="file" onChange={handleChange} />
                    <div className="flex justify-end gap-3 pt-2">
                        <button type="button" onClick={() => setShowModal(false)} className="px-4 py-2 border border-gray-300 text-gray-700 rounded-lg hover:bg-gray-50">Cancel</button>
                        <button type="submit" className="px-4 py-2 bg-indigo-600 text-white rounded-lg hover:bg-indigo-700">Save</button>
                    </div>
                </form>
            </Modal>
        </div>
    );
}
