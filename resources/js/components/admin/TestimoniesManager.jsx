import React, { useState, useEffect } from 'react';
import { Modal, DataTable, Pagination, FormField, Alert } from '../shared/CrudPanel';
import { get, put, del, patch, upload, extractPaginatedData } from '../shared/api';

export default function TestimoniesManager() {
    const [items, setItems] = useState([]);
    const [meta, setMeta] = useState(null);
    const [alert, setAlert] = useState(null);
    const [editModal, setEditModal] = useState(false);
    const [editItem, setEditItem] = useState(null);
    const [viewModal, setViewModal] = useState(false);
    const [viewItem, setViewItem] = useState(null);

    const fetchItems = async (page = 1) => {
        try {
            const data = await get(`/api/testimonies?page=${page}`);
            const { items, meta } = extractPaginatedData(data);
            setItems(items);
            setMeta(meta);
        } catch (e) {
            setAlert({ type: 'error', message: e.message });
        }
    };

    useEffect(() => { fetchItems(); }, []);

    const handleStatusChange = async (item, status) => {
        try {
            await patch(`/api/testimonies/${item.id}/status`, { status });
            setAlert({ type: 'success', message: `Testimony ${status} successfully.` });
            fetchItems();
        } catch (e) {
            setAlert({ type: 'error', message: e.message });
        }
    };

    const handleEdit = (item) => {
        setEditItem({ ...item });
        setEditModal(true);
    };

    const handleView = (item) => {
        setViewItem(item);
        setViewModal(true);
    };

    const handleSaveEdit = async () => {
        try {
            const formData = new FormData();
            if (editItem.name) formData.append('name', editItem.name);
            if (editItem.born_again_date) formData.append('born_again_date', editItem.born_again_date);
            if (editItem.baptism_date) formData.append('baptism_date', editItem.baptism_date);
            if (editItem.testimony) formData.append('testimony', editItem.testimony);
            if (editItem.excerpt) formData.append('excerpt', editItem.excerpt);
            if (editItem.status) formData.append('status', editItem.status);
            if (editItem.meta_title) formData.append('meta_title', editItem.meta_title);
            if (editItem.meta_description) formData.append('meta_description', editItem.meta_description);
            if (editItem.meta_keywords) formData.append('meta_keywords', editItem.meta_keywords);
            if (editItem._featured_image instanceof File) formData.append('featured_image', editItem._featured_image);
            formData.append('_method', 'PUT');

            await upload(`/api/testimonies/${editItem.id}`, formData, 'POST');
            setAlert({ type: 'success', message: 'Testimony updated successfully.' });
            setEditModal(false);
            fetchItems();
        } catch (e) {
            setAlert({ type: 'error', message: e.message });
        }
    };

    const handleDelete = async (item) => {
        if (!confirm('Are you sure you want to delete this testimony?')) return;
        try {
            await del(`/api/testimonies/${item.id}`);
            setAlert({ type: 'success', message: 'Testimony deleted successfully.' });
            fetchItems();
        } catch (e) {
            setAlert({ type: 'error', message: e.message });
        }
    };

    const fmtDate = (d) => {
        if (!d) return '—';
        return new Date(d).toLocaleDateString('en-US', { year: 'numeric', month: 'short', day: 'numeric' });
    };

    const statusColors = {
        pending: 'bg-yellow-100 text-yellow-800',
        approved: 'bg-green-100 text-green-800',
        featured: 'bg-indigo-100 text-indigo-800',
        rejected: 'bg-red-100 text-red-800',
    };

    const columns = [
        { key: 'name', label: 'Name' },
        {
            key: 'testimony', label: 'Testimony',
            render: (row) => (row.excerpt || row.testimony || '').substring(0, 60) + '...',
        },
        {
            key: 'born_again_date', label: 'Born Again',
            render: (row) => fmtDate(row.born_again_date),
        },
        {
            key: 'baptism_date', label: 'Baptism',
            render: (row) => fmtDate(row.baptism_date),
        },
        {
            key: 'status', label: 'Status',
            render: (row) => (
                <span className={`inline-flex px-2 py-0.5 text-xs font-semibold rounded-full ${statusColors[row.status] || 'bg-gray-100 text-gray-600'}`}>
                    {row.status.charAt(0).toUpperCase() + row.status.slice(1)}
                </span>
            ),
        },
        {
            key: 'view_count', label: 'Views',
            render: (row) => row.view_count || 0,
        },
        {
            key: 'created_at', label: 'Submitted',
            render: (row) => fmtDate(row.created_at),
        },
    ];

    return (
        <div>
            <div className="flex items-center justify-between mb-6">
                <h2 className="text-2xl font-bold text-gray-800">Testimonies</h2>
                <div className="text-sm text-gray-500">
                    {items.filter(i => i.status === 'pending').length} pending approval
                </div>
            </div>
            <Alert {...alert} onClose={() => setAlert(null)} />
            <div className="bg-white rounded-xl shadow-sm border">
                <DataTable columns={columns} data={items} actions={(row) => (
                    <div className="flex gap-2 flex-wrap">
                        <button onClick={() => handleView(row)} className="text-indigo-600 hover:text-indigo-800 text-sm font-medium">View</button>
                        <button onClick={() => handleEdit(row)} className="text-blue-600 hover:text-blue-800 text-sm font-medium">Edit</button>
                        {row.status === 'pending' && (
                            <button onClick={() => handleStatusChange(row, 'approved')} className="text-green-600 hover:text-green-800 text-sm font-medium">Approve</button>
                        )}
                        {row.status !== 'featured' && row.status !== 'pending' && (
                            <button onClick={() => handleStatusChange(row, 'featured')} className="text-purple-600 hover:text-purple-800 text-sm font-medium">Feature</button>
                        )}
                        {(row.status === 'approved' || row.status === 'featured') && (
                            <button onClick={() => handleStatusChange(row, 'pending')} className="text-orange-600 hover:text-orange-800 text-sm font-medium">Unpublish</button>
                        )}
                        {row.status !== 'rejected' && (
                            <button onClick={() => handleStatusChange(row, 'rejected')} className="text-red-600 hover:text-red-800 text-sm font-medium">Reject</button>
                        )}
                        <button onClick={() => handleDelete(row)} className="text-red-600 hover:text-red-800 text-sm font-medium">Delete</button>
                    </div>
                )} />
                <Pagination meta={meta} onPageChange={fetchItems} />
            </div>

            {/* View Modal */}
            <Modal isOpen={viewModal} onClose={() => setViewModal(false)} title="Testimony Details">
                {viewItem && (
                    <div className="space-y-4">
                        <div className="grid grid-cols-2 gap-4">
                            <div>
                                <p className="text-xs font-semibold text-gray-500 uppercase">Name</p>
                                <p className="text-gray-800 font-medium">{viewItem.name}</p>
                            </div>
                            <div>
                                <p className="text-xs font-semibold text-gray-500 uppercase">Status</p>
                                <span className={`inline-flex px-2 py-0.5 text-xs font-semibold rounded-full ${statusColors[viewItem.status]}`}>
                                    {viewItem.status.charAt(0).toUpperCase() + viewItem.status.slice(1)}
                                </span>
                            </div>
                            <div>
                                <p className="text-xs font-semibold text-gray-500 uppercase">Born Again Date</p>
                                <p className="text-gray-800">{fmtDate(viewItem.born_again_date)}</p>
                            </div>
                            <div>
                                <p className="text-xs font-semibold text-gray-500 uppercase">Baptism Date</p>
                                <p className="text-gray-800">{fmtDate(viewItem.baptism_date)}</p>
                            </div>
                        </div>
                        {viewItem.featured_image && (
                            <div>
                                <p className="text-xs font-semibold text-gray-500 uppercase mb-1">Photo</p>
                                <img src={`/storage/${viewItem.featured_image}`} alt={viewItem.name} className="rounded-lg max-h-48 object-cover" />
                            </div>
                        )}
                        <div>
                            <p className="text-xs font-semibold text-gray-500 uppercase mb-1">Testimony</p>
                            <div className="text-gray-700 leading-relaxed whitespace-pre-wrap bg-gray-50 p-4 rounded-lg">{viewItem.testimony}</div>
                        </div>
                        <div className="grid grid-cols-3 gap-4 pt-2 border-t text-center">
                            <div>
                                <p className="text-xs text-gray-500">Views</p>
                                <p className="text-lg font-bold text-gray-800">{viewItem.view_count || 0}</p>
                            </div>
                            <div>
                                <p className="text-xs text-gray-500">Slug</p>
                                <p className="text-sm text-gray-600 truncate">{viewItem.slug}</p>
                            </div>
                            <div>
                                <p className="text-xs text-gray-500">Submitted</p>
                                <p className="text-sm text-gray-600">{fmtDate(viewItem.created_at)}</p>
                            </div>
                        </div>
                        {viewItem.meta_title && (
                            <div className="pt-2 border-t">
                                <p className="text-xs font-semibold text-gray-500 uppercase mb-1">SEO</p>
                                <p className="text-sm text-gray-600"><strong>Title:</strong> {viewItem.meta_title}</p>
                                <p className="text-sm text-gray-600"><strong>Description:</strong> {viewItem.meta_description}</p>
                                <p className="text-sm text-gray-600"><strong>Keywords:</strong> {viewItem.meta_keywords}</p>
                            </div>
                        )}
                    </div>
                )}
            </Modal>

            {/* Edit Modal */}
            <Modal isOpen={editModal} onClose={() => setEditModal(false)} title="Edit Testimony">
                {editItem && (
                    <div>
                        <FormField label="Name" name="name" value={editItem.name}
                            onChange={e => setEditItem({ ...editItem, name: e.target.value })} required />
                        <FormField label="Born Again Date" name="born_again_date" type="date"
                            value={editItem.born_again_date ? editItem.born_again_date.substring(0, 10) : ''}
                            onChange={e => setEditItem({ ...editItem, born_again_date: e.target.value })} />
                        <FormField label="Baptism Date" name="baptism_date" type="date"
                            value={editItem.baptism_date ? editItem.baptism_date.substring(0, 10) : ''}
                            onChange={e => setEditItem({ ...editItem, baptism_date: e.target.value })} />
                        <FormField label="Testimony" name="testimony" type="textarea" rows={6}
                            value={editItem.testimony}
                            onChange={e => setEditItem({ ...editItem, testimony: e.target.value })} required />
                        <FormField label="Excerpt" name="excerpt" type="textarea" rows={2}
                            value={editItem.excerpt}
                            onChange={e => setEditItem({ ...editItem, excerpt: e.target.value })}
                            placeholder="Auto-generated if left blank" />
                        <FormField label="Status" name="status" type="select"
                            value={editItem.status}
                            onChange={e => setEditItem({ ...editItem, status: e.target.value })}
                            options={[
                                { value: 'pending', label: 'Pending' },
                                { value: 'approved', label: 'Approved' },
                                { value: 'featured', label: 'Featured' },
                                { value: 'rejected', label: 'Rejected' },
                            ]} />
                        <FormField label="Featured Image" name="featured_image" type="file"
                            onChange={e => setEditItem({ ...editItem, _featured_image: e.target.files[0] })} />

                        <div className="border-t pt-4 mt-4">
                            <p className="text-sm font-semibold text-gray-600 mb-3">SEO Settings</p>
                            <FormField label="Meta Title (max 70 chars)" name="meta_title"
                                value={editItem.meta_title}
                                onChange={e => setEditItem({ ...editItem, meta_title: e.target.value })}
                                placeholder="Auto-generated from name" />
                            <FormField label="Meta Description (max 160 chars)" name="meta_description" type="textarea" rows={2}
                                value={editItem.meta_description}
                                onChange={e => setEditItem({ ...editItem, meta_description: e.target.value })}
                                placeholder="Auto-generated from testimony excerpt" />
                            <FormField label="Meta Keywords" name="meta_keywords"
                                value={editItem.meta_keywords}
                                onChange={e => setEditItem({ ...editItem, meta_keywords: e.target.value })}
                                placeholder="comma, separated, keywords" />
                        </div>

                        <div className="flex gap-3 mt-6">
                            <button onClick={handleSaveEdit}
                                className="flex-1 bg-indigo-600 text-white py-2.5 px-4 rounded-lg font-semibold hover:bg-indigo-700 transition-colors">
                                Save Changes
                            </button>
                            <button onClick={() => setEditModal(false)}
                                className="px-6 py-2.5 rounded-lg border border-gray-300 text-gray-700 font-medium hover:bg-gray-50 transition-colors">
                                Cancel
                            </button>
                        </div>
                    </div>
                )}
            </Modal>
        </div>
    );
}
