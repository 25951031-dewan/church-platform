import React, { useState, useEffect } from 'react';
import { Modal, DataTable, Pagination, FormField, Alert } from '../shared/CrudPanel';
import { get, post, put, del, upload } from '../shared/api';

export default function UsersManager() {
    const [items, setItems] = useState([]);
    const [meta, setMeta] = useState(null);
    const [showModal, setShowModal] = useState(false);
    const [editing, setEditing] = useState(null);
    const [form, setForm] = useState({ name: '', email: '', is_admin: false, password: '', password_confirmation: '', phone: '', church_name: '', social_id: '', spiritual_background: '', custom_fields: {} });
    const [alert, setAlert] = useState(null);
    const [loading, setLoading] = useState(false);
    const [resetModal, setResetModal] = useState(null);
    const [resetPassword, setResetPassword] = useState('');
    const [profileFields, setProfileFields] = useState([]);
    const [viewUser, setViewUser] = useState(null);

    const fetchItems = async (page = 1) => {
        setLoading(true);
        try {
            const data = await get(`/api/users?page=${page}`);
            setItems(data.data || []);
            setMeta(data);
        } catch (e) {
            setItems([]);
            setMeta(null);
        }
        setLoading(false);
    };

    const fetchProfileFields = async () => {
        try {
            const data = await get('/api/settings/profile-fields');
            setProfileFields((data.data || []).filter(f => f.enabled));
        } catch (e) { setProfileFields([]); }
    };

    useEffect(() => { fetchItems(); fetchProfileFields(); }, []);

    const handleChange = (e) => {
        const { name, value, type, checked } = e.target;
        if (name.startsWith('custom_fields.')) {
            const key = name.replace('custom_fields.', '');
            setForm(prev => ({ ...prev, custom_fields: { ...prev.custom_fields, [key]: value } }));
        } else {
            setForm({ ...form, [name]: type === 'checkbox' ? checked : value });
        }
    };

    const openCreate = () => {
        setEditing(null);
        setForm({ name: '', email: '', is_admin: false, password: '', password_confirmation: '', phone: '', church_name: '', social_id: '', spiritual_background: '', custom_fields: {} });
        setShowModal(true);
    };

    const openEdit = (item) => {
        setEditing(item);
        setForm({
            name: item.name, email: item.email, is_admin: !!item.is_admin,
            password: '', password_confirmation: '',
            phone: item.phone || '', church_name: item.church_name || '',
            social_id: item.social_id || '', spiritual_background: item.spiritual_background || '',
            custom_fields: item.custom_fields || {},
        });
        setShowModal(true);
    };

    const handleSubmit = async (e) => {
        e.preventDefault();
        try {
            const payload = {
                name: form.name, email: form.email, is_admin: form.is_admin,
                phone: form.phone || null, church_name: form.church_name || null,
                social_id: form.social_id || null, spiritual_background: form.spiritual_background || null,
                custom_fields: Object.keys(form.custom_fields).length > 0 ? form.custom_fields : null,
            };
            if (form.password) {
                payload.password = form.password;
                payload.password_confirmation = form.password_confirmation;
            }
            if (editing) {
                await put(`/api/users/${editing.id}`, payload);
                setAlert({ type: 'success', message: 'User updated.' });
            } else {
                await post('/api/users', payload);
                setAlert({ type: 'success', message: 'User created.' });
            }
            setShowModal(false);
            fetchItems();
        } catch (e) {
            setAlert({ type: 'error', message: e.message });
        }
    };

    const handleResetPassword = async () => {
        if (!resetPassword || resetPassword.length < 8) {
            setAlert({ type: 'error', message: 'Password must be at least 8 characters.' });
            return;
        }
        try {
            await post(`/api/users/${resetModal.id}/reset-password`, { password: resetPassword });
            setAlert({ type: 'success', message: `Password reset for ${resetModal.name}.` });
            setResetModal(null);
            setResetPassword('');
        } catch (e) {
            setAlert({ type: 'error', message: e.message });
        }
    };

    const handleDelete = async (item) => {
        if (!confirm('Delete this user?')) return;
        try {
            await del(`/api/users/${item.id}`);
            setAlert({ type: 'success', message: 'User deleted.' });
            fetchItems();
        } catch (e) {
            setAlert({ type: 'error', message: e.message });
        }
    };

    const columns = [
        { key: 'name', label: 'Name' },
        { key: 'email', label: 'Email' },
        {
            key: 'is_admin',
            label: 'Role',
            render: (row) => (
                <span className={`inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium ${
                    row.is_admin
                        ? 'bg-purple-100 text-purple-800'
                        : 'bg-gray-100 text-gray-800'
                }`}>
                    {row.is_admin ? 'Admin' : 'Member'}
                </span>
            ),
        },
        {
            key: 'profile_completed',
            label: 'Profile',
            render: (row) => (
                <span className={`inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium ${
                    row.profile_completed ? 'bg-green-100 text-green-700' : 'bg-yellow-100 text-yellow-700'
                }`}>
                    {row.profile_completed ? 'Complete' : 'Incomplete'}
                </span>
            ),
        },
        {
            key: 'created_at',
            label: 'Created',
            render: (row) => row.created_at ? new Date(row.created_at).toLocaleDateString() : '-',
        },
    ];

    const builtInFieldLabels = { phone: 'Phone', church_name: 'Church Name', social_id: 'Social ID', spiritual_background: 'Spiritual Background' };

    return (
        <div>
            <div className="flex items-center justify-between mb-6">
                <h2 className="text-2xl font-bold text-gray-800">User Management</h2>
                <button onClick={openCreate} className="px-4 py-2 bg-indigo-600 text-white rounded-lg text-sm font-medium hover:bg-indigo-700">
                    + Add User
                </button>
            </div>

            <Alert {...alert} onClose={() => setAlert(null)} />

            <div className="bg-white rounded-xl shadow-sm border">
                {loading ? (
                    <div className="flex items-center justify-center py-16">
                        <svg className="animate-spin h-8 w-8 text-indigo-600" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                            <circle className="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" strokeWidth="4"></circle>
                            <path className="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
                        </svg>
                    </div>
                ) : items.length > 0 ? (
                    <>
                        <DataTable columns={columns} data={items} actions={(row) => (
                            <div className="flex gap-2">
                                <button onClick={() => setViewUser(row)} className="text-teal-600 hover:text-teal-800 text-sm font-medium">View</button>
                                <button onClick={() => openEdit(row)} className="text-indigo-600 hover:text-indigo-800 text-sm font-medium">Edit</button>
                                <button onClick={() => { setResetModal(row); setResetPassword(''); }} className="text-amber-600 hover:text-amber-800 text-sm font-medium">Reset PW</button>
                                <button onClick={() => handleDelete(row)} className="text-red-600 hover:text-red-800 text-sm font-medium">Delete</button>
                            </div>
                        )} />
                        <Pagination meta={meta} onPageChange={fetchItems} />
                    </>
                ) : (
                    <div className="text-center py-16 px-4">
                        <div className="mx-auto w-16 h-16 bg-gray-100 rounded-full flex items-center justify-center mb-4">
                            <svg className="w-8 h-8 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={1.5}
                                    d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z" />
                            </svg>
                        </div>
                        <h3 className="text-lg font-semibold text-gray-900 mb-1">No users found</h3>
                        <p className="text-sm text-gray-500 max-w-sm mx-auto">
                            Users will appear here once the user management API is available. Click the button above to add a new user.
                        </p>
                    </div>
                )}
            </div>

            {/* View User Detail Modal */}
            <Modal isOpen={!!viewUser} onClose={() => setViewUser(null)} title={`User Details: ${viewUser?.name || ''}`}>
                {viewUser && (
                    <div className="space-y-3">
                        <div className="grid grid-cols-2 gap-3">
                            <div><span className="text-xs text-gray-500 block">Name</span><span className="text-sm font-medium">{viewUser.name}</span></div>
                            <div><span className="text-xs text-gray-500 block">Email</span><span className="text-sm font-medium">{viewUser.email}</span></div>
                            <div><span className="text-xs text-gray-500 block">Role</span><span className="text-sm font-medium">{viewUser.is_admin ? 'Admin' : 'Member'}</span></div>
                            <div><span className="text-xs text-gray-500 block">Joined</span><span className="text-sm font-medium">{viewUser.created_at ? new Date(viewUser.created_at).toLocaleDateString() : '-'}</span></div>
                        </div>
                        {(viewUser.phone || viewUser.church_name || viewUser.social_id || viewUser.spiritual_background) && (
                            <div className="border-t pt-3 mt-3">
                                <h4 className="text-sm font-semibold text-gray-700 mb-2">Profile Information</h4>
                                <div className="grid grid-cols-2 gap-3">
                                    {viewUser.phone && <div><span className="text-xs text-gray-500 block">Phone</span><span className="text-sm">{viewUser.phone}</span></div>}
                                    {viewUser.church_name && <div><span className="text-xs text-gray-500 block">Church Name</span><span className="text-sm">{viewUser.church_name}</span></div>}
                                    {viewUser.social_id && <div><span className="text-xs text-gray-500 block">Social ID</span><span className="text-sm">{viewUser.social_id}</span></div>}
                                </div>
                                {viewUser.spiritual_background && (
                                    <div className="mt-2"><span className="text-xs text-gray-500 block">Spiritual Background</span><span className="text-sm">{viewUser.spiritual_background}</span></div>
                                )}
                            </div>
                        )}
                        {viewUser.custom_fields && Object.keys(viewUser.custom_fields).length > 0 && (
                            <div className="border-t pt-3 mt-3">
                                <h4 className="text-sm font-semibold text-gray-700 mb-2">Custom Fields</h4>
                                <div className="grid grid-cols-2 gap-3">
                                    {Object.entries(viewUser.custom_fields).map(([key, val]) => {
                                        const fieldDef = profileFields.find(f => f.key === key);
                                        return (
                                            <div key={key}>
                                                <span className="text-xs text-gray-500 block">{fieldDef?.label || key}</span>
                                                <span className="text-sm">{val || '-'}</span>
                                            </div>
                                        );
                                    })}
                                </div>
                            </div>
                        )}
                        <div className="flex justify-end pt-2">
                            <button onClick={() => setViewUser(null)} className="px-4 py-2 border border-gray-300 text-gray-700 rounded-lg hover:bg-gray-50">Close</button>
                        </div>
                    </div>
                )}
            </Modal>

            {/* Create / Edit modal */}
            <Modal isOpen={showModal} onClose={() => setShowModal(false)} title={editing ? 'Edit User' : 'Add User'}>
                <form onSubmit={handleSubmit} className="space-y-4">
                    <FormField label="Name" name="name" value={form.name} onChange={handleChange} required placeholder="Full name" />
                    <FormField label="Email" name="email" type="email" value={form.email} onChange={handleChange} required placeholder="email@example.com" />
                    <FormField label="Administrator" name="is_admin" type="checkbox" value={form.is_admin} onChange={handleChange} />

                    {profileFields.length > 0 && (
                        <div className="border-t pt-4 mt-4">
                            <p className="text-xs text-gray-500 mb-3 font-medium">Profile Fields</p>
                            {profileFields.map(field => {
                                const isBuiltIn = ['phone', 'church_name', 'social_id', 'spiritual_background'].includes(field.key);
                                const fieldName = isBuiltIn ? field.key : `custom_fields.${field.key}`;
                                const fieldValue = isBuiltIn ? (form[field.key] || '') : (form.custom_fields[field.key] || '');

                                if (field.type === 'textarea') {
                                    return (
                                        <div key={field.key} className="mb-3">
                                            <label className="block text-sm font-medium text-gray-700 mb-1">
                                                {field.label} {field.required && <span className="text-red-500">*</span>}
                                            </label>
                                            <textarea name={fieldName} value={fieldValue} onChange={handleChange}
                                                placeholder={field.placeholder || ''} required={field.required}
                                                className="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm" rows="3" />
                                        </div>
                                    );
                                }
                                if (field.type === 'select') {
                                    const options = (field.options || '').split(',').map(o => o.trim()).filter(Boolean);
                                    return (
                                        <div key={field.key} className="mb-3">
                                            <label className="block text-sm font-medium text-gray-700 mb-1">
                                                {field.label} {field.required && <span className="text-red-500">*</span>}
                                            </label>
                                            <select name={fieldName} value={fieldValue} onChange={handleChange} required={field.required}
                                                className="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm">
                                                <option value="">Select...</option>
                                                {options.map(opt => <option key={opt} value={opt}>{opt}</option>)}
                                            </select>
                                        </div>
                                    );
                                }
                                return (
                                    <FormField key={field.key} label={<>{field.label} {field.required && <span className="text-red-500">*</span>}</>}
                                        name={fieldName} type={field.type || 'text'} value={fieldValue}
                                        onChange={handleChange} required={field.required} placeholder={field.placeholder || ''} />
                                );
                            })}
                        </div>
                    )}

                    <div className="border-t pt-4 mt-4">
                        <p className="text-xs text-gray-500 mb-3">{editing ? 'Leave blank to keep current password.' : 'Set a password for the new user.'}</p>
                        <FormField label="Password" name="password" type="password" value={form.password} onChange={handleChange} placeholder="Min 8 characters" required={!editing} />
                        <FormField label="Confirm Password" name="password_confirmation" type="password" value={form.password_confirmation} onChange={handleChange} placeholder="Confirm password" required={!editing} />
                    </div>
                    <div className="flex justify-end gap-3 pt-2">
                        <button type="button" onClick={() => setShowModal(false)} className="px-4 py-2 border border-gray-300 text-gray-700 rounded-lg hover:bg-gray-50">Cancel</button>
                        <button type="submit" className="px-4 py-2 bg-indigo-600 text-white rounded-lg hover:bg-indigo-700">Save</button>
                    </div>
                </form>
            </Modal>

            {/* Reset Password Modal */}
            <Modal isOpen={!!resetModal} onClose={() => setResetModal(null)} title={`Reset Password: ${resetModal?.name || ''}`}>
                <div className="space-y-4">
                    <p className="text-sm text-gray-600">Set a new password for this user.</p>
                    <FormField label="New Password" name="resetPassword" type="password" value={resetPassword} onChange={(e) => setResetPassword(e.target.value)} placeholder="Min 8 characters" required />
                    <div className="flex justify-end gap-3 pt-2">
                        <button type="button" onClick={() => setResetModal(null)} className="px-4 py-2 border border-gray-300 text-gray-700 rounded-lg hover:bg-gray-50">Cancel</button>
                        <button type="button" onClick={handleResetPassword} className="px-4 py-2 bg-amber-600 text-white rounded-lg hover:bg-amber-700">Reset Password</button>
                    </div>
                </div>
            </Modal>
        </div>
    );
}
