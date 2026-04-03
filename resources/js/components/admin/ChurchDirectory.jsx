import React, { useState, useEffect } from 'react';
import { get, del, upload, patch, extractPaginatedData } from '../shared/api';
import { Modal, DataTable, Pagination, FormField, Alert } from '../shared/CrudPanel';

export default function ChurchDirectory() {
    const [churches, setChurches] = useState([]);
    const [meta, setMeta] = useState(null);
    const [loading, setLoading] = useState(true);
    const [alert, setAlert] = useState(null);
    const [search, setSearch] = useState('');
    const [statusFilter, setStatusFilter] = useState('');
    const [showModal, setShowModal] = useState(false);
    const [editing, setEditing] = useState(null);
    const [users, setUsers] = useState([]);
    const [form, setForm] = useState({
        name: '', email: '', phone: '', website: '',
        address: '', city: '', state: '', zip_code: '', country: '',
        denomination: '', year_founded: '',
        short_description: '', admin_user_id: '', status: 'pending', is_featured: false,
    });

    useEffect(() => { fetchChurches(); }, []);

    async function fetchChurches(page = 1) {
        setLoading(true);
        try {
            let url = `/api/churches/admin?page=${page}&per_page=15`;
            if (search) url += `&search=${encodeURIComponent(search)}`;
            if (statusFilter) url += `&status=${statusFilter}`;
            const res = await get(url);
            const { items, meta: m } = extractPaginatedData(res);
            setChurches(items);
            setMeta(m);
        } catch (e) {
            setAlert({ type: 'error', message: e.message });
        }
        setLoading(false);
    }

    async function fetchUsers() {
        try {
            const res = await get('/api/churches/admin/available-admins');
            setUsers(res.data || []);
        } catch (e) {}
    }

    function openCreate() {
        setEditing(null);
        setForm({
            name: '', email: '', phone: '', website: '',
            address: '', city: '', state: '', zip_code: '', country: '',
            denomination: '', year_founded: '',
            short_description: '', admin_user_id: '', status: 'pending', is_featured: false,
        });
        fetchUsers();
        setShowModal(true);
    }

    function openEdit(church) {
        setEditing(church);
        setForm({
            name: church.name || '', email: church.email || '', phone: church.phone || '',
            website: church.website || '', address: church.address || '', city: church.city || '',
            state: church.state || '', zip_code: church.zip_code || '', country: church.country || '',
            denomination: church.denomination || '', year_founded: church.year_founded || '',
            short_description: church.short_description || '',
            admin_user_id: church.admin_user_id || '', status: church.status || 'pending',
            is_featured: church.is_featured || false,
        });
        fetchUsers();
        setShowModal(true);
    }

    async function handleSubmit(e) {
        e.preventDefault();
        try {
            const fd = new FormData();
            Object.keys(form).forEach(key => {
                if (form[key] !== '' && form[key] !== null && form[key] !== undefined) {
                    if (key === 'is_featured') {
                        fd.append(key, form[key] ? '1' : '0');
                    } else {
                        fd.append(key, form[key]);
                    }
                }
            });

            if (editing) {
                await upload(`/api/churches/admin/${editing.id}`, fd, 'POST');
                setAlert({ type: 'success', message: 'Church updated successfully.' });
            } else {
                await upload('/api/churches/admin', fd, 'POST');
                setAlert({ type: 'success', message: 'Church created successfully.' });
            }
            setShowModal(false);
            fetchChurches();
        } catch (e) {
            setAlert({ type: 'error', message: e.message });
        }
    }

    async function handleDelete(church) {
        if (!confirm(`Delete "${church.name}"? This action cannot be undone.`)) return;
        try {
            await del(`/api/churches/admin/${church.id}`);
            setAlert({ type: 'success', message: 'Church deleted.' });
            fetchChurches();
        } catch (e) {
            setAlert({ type: 'error', message: e.message });
        }
    }

    async function handleStatusChange(church, status) {
        try {
            await patch(`/api/churches/admin/${church.id}/status`, { status });
            setAlert({ type: 'success', message: `Church ${status}.` });
            fetchChurches();
        } catch (e) {
            setAlert({ type: 'error', message: e.message });
        }
    }

    async function handleToggleFeatured(church) {
        try {
            await patch(`/api/churches/admin/${church.id}/featured`);
            fetchChurches();
        } catch (e) {
            setAlert({ type: 'error', message: e.message });
        }
    }

    function handleChange(e) {
        const { name, value, type, checked } = e.target;
        setForm(prev => ({ ...prev, [name]: type === 'checkbox' ? checked : value }));
    }

    const statusBadge = (status) => {
        const colors = { approved: 'bg-green-100 text-green-800', pending: 'bg-yellow-100 text-yellow-800', rejected: 'bg-red-100 text-red-800' };
        return <span className={`px-2 py-1 rounded-full text-xs font-medium ${colors[status] || 'bg-gray-100 text-gray-800'}`}>{status}</span>;
    };

    const columns = [
        { label: 'Church', render: (row) => (
            <div className="flex items-center gap-3">
                {row.logo_url ? (
                    <img src={row.logo_url} alt="" className="w-10 h-10 rounded-lg object-cover" />
                ) : (
                    <div className="w-10 h-10 bg-indigo-100 rounded-lg flex items-center justify-center">
                        <i className="fas fa-church text-indigo-500"></i>
                    </div>
                )}
                <div>
                    <div className="font-medium text-gray-900">{row.name}</div>
                    <div className="text-xs text-gray-500">{row.city}{row.state ? `, ${row.state}` : ''}</div>
                </div>
            </div>
        )},
        { label: 'Admin', render: (row) => row.admin ? (
            <span className="text-sm">{row.admin.name}</span>
        ) : <span className="text-gray-400 text-sm">Not assigned</span> },
        { label: 'Status', render: (row) => statusBadge(row.status) },
        { label: 'Views', key: 'view_count' },
        { label: 'Featured', render: (row) => (
            <button onClick={() => handleToggleFeatured(row)} className={`text-lg ${row.is_featured ? 'text-yellow-500' : 'text-gray-300'}`}>
                <i className="fas fa-star"></i>
            </button>
        )},
    ];

    return (
        <div>
            <div className="flex items-center justify-between mb-6">
                <div>
                    <h1 className="text-2xl font-bold text-gray-900">Church Directory</h1>
                    <p className="text-sm text-gray-500 mt-1">Manage all registered churches</p>
                </div>
                <button onClick={openCreate}
                    className="bg-indigo-600 text-white px-4 py-2 rounded-lg hover:bg-indigo-700 flex items-center gap-2">
                    <i className="fas fa-plus"></i> Add Church
                </button>
            </div>

            <Alert type={alert?.type} message={alert?.message} onClose={() => setAlert(null)} />

            <div className="bg-white rounded-xl shadow-sm border">
                <div className="p-4 border-b flex flex-wrap gap-3">
                    <div className="flex-1 min-w-[200px]">
                        <input type="text" placeholder="Search churches..." value={search}
                            onChange={(e) => setSearch(e.target.value)}
                            onKeyDown={(e) => e.key === 'Enter' && fetchChurches()}
                            className="w-full rounded-lg border border-gray-300 px-4 py-2 text-sm focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 outline-none" />
                    </div>
                    <select value={statusFilter} onChange={(e) => { setStatusFilter(e.target.value); setTimeout(() => fetchChurches(), 0); }}
                        className="rounded-lg border border-gray-300 px-3 py-2 text-sm focus:ring-2 focus:ring-indigo-500 outline-none">
                        <option value="">All Status</option>
                        <option value="pending">Pending</option>
                        <option value="approved">Approved</option>
                        <option value="rejected">Rejected</option>
                    </select>
                    <button onClick={() => fetchChurches()} className="bg-gray-100 px-4 py-2 rounded-lg text-sm hover:bg-gray-200">
                        <i className="fas fa-search mr-1"></i> Search
                    </button>
                </div>

                {loading ? (
                    <div className="text-center py-12 text-gray-500">Loading...</div>
                ) : (
                    <>
                        <DataTable columns={columns} data={churches}
                            actions={(row) => (
                                <div className="flex items-center gap-2">
                                    {row.status === 'pending' && (
                                        <button onClick={() => handleStatusChange(row, 'approved')}
                                            className="text-green-600 hover:text-green-800 text-sm" title="Approve">
                                            <i className="fas fa-check"></i>
                                        </button>
                                    )}
                                    {row.status === 'pending' && (
                                        <button onClick={() => handleStatusChange(row, 'rejected')}
                                            className="text-red-500 hover:text-red-700 text-sm" title="Reject">
                                            <i className="fas fa-ban"></i>
                                        </button>
                                    )}
                                    <a href={`/admin/manage/church-builder?id=${row.id}`}
                                        className="text-indigo-600 hover:text-indigo-800 text-sm" title="Edit in Builder">
                                        <i className="fas fa-edit"></i>
                                    </a>
                                    <button onClick={() => openEdit(row)}
                                        className="text-blue-600 hover:text-blue-800 text-sm" title="Quick Edit">
                                        <i className="fas fa-pen"></i>
                                    </button>
                                    {row.status === 'approved' && (
                                        <a href={`/church/${row.slug}`} target="_blank"
                                            className="text-gray-500 hover:text-gray-700 text-sm" title="View Public Page">
                                            <i className="fas fa-external-link-alt"></i>
                                        </a>
                                    )}
                                    <button onClick={() => handleDelete(row)}
                                        className="text-red-600 hover:text-red-800 text-sm" title="Delete">
                                        <i className="fas fa-trash"></i>
                                    </button>
                                </div>
                            )}
                        />
                        <Pagination meta={meta} onPageChange={fetchChurches} />
                    </>
                )}
            </div>

            <Modal isOpen={showModal} onClose={() => setShowModal(false)} title={editing ? 'Edit Church' : 'Add Church'}>
                <form onSubmit={handleSubmit}>
                    <FormField label="Church Name" name="name" value={form.name} onChange={handleChange} required />
                    <div className="grid grid-cols-2 gap-4">
                        <FormField label="Email" name="email" type="email" value={form.email} onChange={handleChange} />
                        <FormField label="Phone" name="phone" value={form.phone} onChange={handleChange} />
                    </div>
                    <FormField label="Website" name="website" value={form.website} onChange={handleChange} placeholder="https://..." />
                    <FormField label="Address" name="address" value={form.address} onChange={handleChange} />
                    <div className="grid grid-cols-2 gap-4">
                        <FormField label="City" name="city" value={form.city} onChange={handleChange} />
                        <FormField label="State" name="state" value={form.state} onChange={handleChange} />
                    </div>
                    <div className="grid grid-cols-2 gap-4">
                        <FormField label="Zip Code" name="zip_code" value={form.zip_code} onChange={handleChange} />
                        <FormField label="Country" name="country" value={form.country} onChange={handleChange} />
                    </div>
                    <div className="grid grid-cols-2 gap-4">
                        <FormField label="Denomination" name="denomination" value={form.denomination} onChange={handleChange} />
                        <FormField label="Year Founded" name="year_founded" type="number" value={form.year_founded} onChange={handleChange} />
                    </div>
                    <FormField label="Short Description" name="short_description" type="textarea" value={form.short_description} onChange={handleChange} rows={3} />
                    <FormField label="Assign Admin User" name="admin_user_id" type="select" value={form.admin_user_id} onChange={handleChange}
                        options={users.map(u => ({ value: u.id, label: `${u.name} (${u.email})` }))} />
                    <FormField label="Status" name="status" type="select" value={form.status} onChange={handleChange}
                        options={[{ value: 'pending', label: 'Pending' }, { value: 'approved', label: 'Approved' }, { value: 'rejected', label: 'Rejected' }]} />
                    <FormField label="Featured" name="is_featured" type="checkbox" value={form.is_featured} onChange={handleChange} />

                    <div className="flex justify-end gap-3 mt-6">
                        <button type="button" onClick={() => setShowModal(false)}
                            className="px-4 py-2 border border-gray-300 rounded-lg text-gray-700 hover:bg-gray-50">Cancel</button>
                        <button type="submit"
                            className="px-4 py-2 bg-indigo-600 text-white rounded-lg hover:bg-indigo-700">
                            {editing ? 'Update' : 'Create'} Church
                        </button>
                    </div>
                </form>
            </Modal>
        </div>
    );
}
