import React, { useState, useEffect } from 'react';
import { Modal, DataTable, Pagination, FormField, Alert } from '../shared/CrudPanel';
import { get, post, put, del, upload, patch, extractPaginatedData } from '../shared/api';

export default function ReviewsManager() {
    const [items, setItems] = useState([]);
    const [meta, setMeta] = useState(null);
    const [alert, setAlert] = useState(null);

    const fetchItems = async (page = 1) => {
        try {
            const data = await get(`/api/reviews?page=${page}`);
            const { items, meta } = extractPaginatedData(data);
            setItems(items);
            setMeta(meta);
        } catch (e) {
            setAlert({ type: 'error', message: e.message });
        }
    };

    useEffect(() => { fetchItems(); }, []);

    const handleApprove = async (item) => {
        try {
            await patch(`/api/reviews/${item.id}/approve`, {});
            setAlert({ type: 'success', message: `Review ${item.is_approved ? 'unapproved' : 'approved'} successfully.` });
            fetchItems();
        } catch (e) {
            setAlert({ type: 'error', message: e.message });
        }
    };

    const handleToggleFeatured = async (item) => {
        try {
            await patch(`/api/reviews/${item.id}/toggle-featured`, {});
            setAlert({ type: 'success', message: `Review ${item.is_featured ? 'unfeatured' : 'featured'} successfully.` });
            fetchItems();
        } catch (e) {
            setAlert({ type: 'error', message: e.message });
        }
    };

    const handleDelete = async (item) => {
        if (!confirm('Are you sure you want to delete this review?')) return;
        try {
            await del(`/api/reviews/${item.id}`);
            setAlert({ type: 'success', message: 'Review deleted successfully.' });
            fetchItems();
        } catch (e) {
            setAlert({ type: 'error', message: e.message });
        }
    };

    const renderStars = (rating) => {
        const stars = [];
        for (let i = 1; i <= 5; i++) {
            stars.push(
                <span key={i} className={`text-lg ${i <= rating ? 'text-yellow-400' : 'text-gray-300'}`}>
                    &#9733;
                </span>
            );
        }
        return <div className="flex">{stars}</div>;
    };

    const columns = [
        { key: 'name', label: 'Name' },
        { key: 'email', label: 'Email' },
        {
            key: 'rating', label: 'Rating',
            render: (row) => renderStars(row.rating),
        },
        {
            key: 'title', label: 'Title',
            render: (row) => (row.title || '').substring(0, 40) + (row.title && row.title.length > 40 ? '...' : ''),
        },
        {
            key: 'is_approved', label: 'Approved',
            render: (row) => (
                <span className={`inline-flex px-2 py-0.5 text-xs font-semibold rounded-full ${row.is_approved ? 'bg-green-100 text-green-800' : 'bg-orange-100 text-orange-800'}`}>
                    {row.is_approved ? 'Approved' : 'Pending'}
                </span>
            ),
        },
        {
            key: 'is_featured', label: 'Featured',
            render: (row) => (
                <span className={`inline-flex px-2 py-0.5 text-xs font-semibold rounded-full ${row.is_featured ? 'bg-green-100 text-green-800' : 'bg-gray-100 text-gray-600'}`}>
                    {row.is_featured ? 'Yes' : 'No'}
                </span>
            ),
        },
    ];

    return (
        <div>
            <div className="flex items-center justify-between mb-6">
                <h2 className="text-2xl font-bold text-gray-800">Reviews</h2>
            </div>
            <Alert {...alert} onClose={() => setAlert(null)} />
            <div className="bg-white rounded-xl shadow-sm border">
                <DataTable columns={columns} data={items} actions={(row) => (
                    <div className="flex gap-2">
                        <button
                            onClick={() => handleApprove(row)}
                            className={`text-sm font-medium ${row.is_approved ? 'text-orange-600 hover:text-orange-800' : 'text-green-600 hover:text-green-800'}`}
                        >
                            {row.is_approved ? 'Unapprove' : 'Approve'}
                        </button>
                        <button
                            onClick={() => handleToggleFeatured(row)}
                            className={`text-sm font-medium ${row.is_featured ? 'text-gray-600 hover:text-gray-800' : 'text-indigo-600 hover:text-indigo-800'}`}
                        >
                            {row.is_featured ? 'Unfeature' : 'Feature'}
                        </button>
                        <button onClick={() => handleDelete(row)} className="text-red-600 hover:text-red-800 text-sm font-medium">Delete</button>
                    </div>
                )} />
                <Pagination meta={meta} onPageChange={fetchItems} />
            </div>
        </div>
    );
}
