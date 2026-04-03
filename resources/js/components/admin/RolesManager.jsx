import React, { useState, useEffect } from 'react';
import { Modal, DataTable, FormField, Alert } from '../shared/CrudPanel';
import { get, post, put, del } from '../shared/api';

const PERMISSION_GROUPS = [
    {
        label: 'Content',
        permissions: [
            'manage_posts', 'manage_pages', 'manage_sermons', 'manage_books',
            'manage_bible_studies', 'manage_testimonies', 'manage_announcements',
            'manage_galleries', 'manage_ministries',
        ],
    },
    {
        label: 'Events',
        permissions: ['manage_events', 'manage_event_registrations'],
    },
    {
        label: 'Prayer',
        permissions: ['manage_prayers'],
    },
    {
        label: 'Community',
        permissions: ['manage_community_posts', 'manage_groups', 'moderate_community'],
    },
    {
        label: 'Counseling',
        permissions: ['request_counseling', 'view_counseling_assigned', 'manage_counseling'],
    },
    {
        label: 'Churches',
        permissions: ['manage_churches', 'approve_churches', 'view_church_directory'],
    },
    {
        label: 'Users & Roles',
        permissions: ['manage_users', 'manage_roles', 'assign_roles'],
    },
    {
        label: 'Finance',
        permissions: ['manage_donations'],
    },
    {
        label: 'Settings',
        permissions: ['manage_settings', 'manage_appearance', 'manage_localizations'],
    },
    {
        label: 'Newsletter',
        permissions: ['manage_newsletter', 'send_newsletter'],
    },
    {
        label: 'System',
        permissions: ['manage_menus', 'manage_categories', 'manage_contacts', 'view_analytics'],
    },
];

const ALL_PERMISSIONS = PERMISSION_GROUPS.flatMap(g => g.permissions);

export default function RolesManager() {
    const [roles, setRoles] = useState([]);
    const [users, setUsers] = useState([]);
    const [modal, setModal] = useState(false);
    const [assignModal, setAssignModal] = useState(false);
    const [editing, setEditing] = useState(null);
    const [form, setForm] = useState({ name: '', description: '', permissions: [] });
    const [assignForm, setAssignForm] = useState({ user_id: '', role_id: '' });
    const [alert, setAlert] = useState(null);

    useEffect(() => { fetchRoles(); fetchUsers(); }, []);

    const fetchRoles = async () => {
        try {
            const data = await get('/api/roles');
            setRoles(data.data || []);
        } catch (e) { setAlert({ type: 'error', message: e.message }); }
    };

    const fetchUsers = async () => {
        try {
            const data = await get('/api/users');
            const result = data.data || data;
            setUsers(Array.isArray(result) ? result : result.data || []);
        } catch (e) { /* ignore */ }
    };

    const openCreate = () => {
        setEditing(null);
        setForm({ name: '', description: '', permissions: [] });
        setModal(true);
    };

    const openEdit = (role) => {
        setEditing(role);
        setForm({ name: role.name || '', description: role.description || '', permissions: role.permissions || [] });
        setModal(true);
    };

    const handleSubmit = async () => {
        try {
            if (editing) {
                await put(`/api/roles/${editing.id}`, form);
                setAlert({ type: 'success', message: 'Role updated.' });
            } else {
                await post('/api/roles', form);
                setAlert({ type: 'success', message: 'Role created.' });
            }
            setModal(false);
            fetchRoles();
        } catch (e) { setAlert({ type: 'error', message: e.message }); }
    };

    const handleDelete = async (role) => {
        if (!confirm(`Delete role "${role.name}"?`)) return;
        try {
            await del(`/api/roles/${role.id}`);
            setAlert({ type: 'success', message: 'Role deleted.' });
            fetchRoles();
        } catch (e) { setAlert({ type: 'error', message: e.message }); }
    };

    const handleAssign = async () => {
        try {
            await post('/api/roles/assign', assignForm);
            setAlert({ type: 'success', message: 'Role assigned.' });
            setAssignModal(false);
            fetchUsers();
        } catch (e) { setAlert({ type: 'error', message: e.message }); }
    };

    const togglePerm = (perm) => {
        setForm(prev => ({
            ...prev,
            permissions: prev.permissions.includes(perm) ? prev.permissions.filter(p => p !== perm) : [...prev.permissions, perm],
        }));
    };

    const columns = [
        {
            key: 'name', label: 'Role Name', render: (r) => (
                <div className="flex items-center gap-2">
                    <span className="font-medium">{r.name}</span>
                    {r.is_system && <span className="px-1.5 py-0.5 bg-indigo-100 text-indigo-700 text-xs rounded">system</span>}
                </div>
            )
        },
        { key: 'slug', label: 'Slug' },
        { key: 'level', label: 'Level', render: (r) => <span className="text-sm text-gray-500">L{r.level ?? 10}</span> },
        { key: 'permissions', label: 'Permissions', render: (r) => <span className="text-sm">{(r.permissions || []).length} perms</span> },
        { key: 'users_count', label: 'Users', render: (r) => r.users_count || 0 },
    ];

    return (
        <div>
            <div className="flex items-center justify-between mb-6">
                <h2 className="text-2xl font-bold text-gray-800">Roles & Permissions</h2>
                <div className="flex gap-2">
                    <button onClick={() => setAssignModal(true)} className="px-4 py-2 bg-green-600 text-white rounded-lg text-sm font-medium hover:bg-green-700">Assign Role</button>
                    <button onClick={openCreate} className="px-4 py-2 bg-indigo-600 text-white rounded-lg text-sm font-medium hover:bg-indigo-700">+ Add Role</button>
                </div>
            </div>
            <Alert {...alert} onClose={() => setAlert(null)} />
            <div className="bg-white rounded-xl shadow-sm border">
                <DataTable columns={columns} data={roles} actions={(r) => (
                    <div className="flex gap-2">
                        <button onClick={() => openEdit(r)} className="text-indigo-600 hover:text-indigo-800 text-sm font-medium">Edit</button>
                        {!r.is_system && (
                            <button onClick={() => handleDelete(r)} className="text-red-600 hover:text-red-800 text-sm font-medium">Delete</button>
                        )}
                    </div>
                )} />
            </div>

            <Modal isOpen={modal} onClose={() => setModal(false)} title={editing ? 'Edit Role' : 'New Role'}>
                <div className="space-y-4">
                    <FormField label="Role Name" name="name" value={form.name} onChange={(e) => setForm(prev => ({ ...prev, name: e.target.value }))} required />
                    <FormField label="Description" name="description" type="textarea" value={form.description} onChange={(e) => setForm(prev => ({ ...prev, description: e.target.value }))} rows={2} />
                    <div>
                        <div className="flex items-center justify-between mb-2">
                            <label className="block text-sm font-medium text-gray-700">Permissions</label>
                            <div className="flex gap-2 text-xs">
                                <button type="button" onClick={() => setForm(prev => ({ ...prev, permissions: [...ALL_PERMISSIONS] }))} className="text-indigo-600 hover:underline">Select all</button>
                                <span className="text-gray-400">|</span>
                                <button type="button" onClick={() => setForm(prev => ({ ...prev, permissions: [] }))} className="text-gray-500 hover:underline">Clear</button>
                            </div>
                        </div>
                        <div className="p-3 bg-gray-50 rounded-lg max-h-72 overflow-y-auto space-y-3">
                            {PERMISSION_GROUPS.map(group => (
                                <div key={group.label}>
                                    <p className="text-xs font-semibold text-gray-500 uppercase tracking-wide mb-1">{group.label}</p>
                                    <div className="grid grid-cols-2 gap-1">
                                        {group.permissions.map(perm => (
                                            <label key={perm} className="flex items-center gap-2 text-sm cursor-pointer">
                                                <input type="checkbox" checked={form.permissions.includes(perm)} onChange={() => togglePerm(perm)} className="rounded border-gray-300 text-indigo-600 focus:ring-indigo-500" />
                                                <span className="text-gray-700">{perm.replace(/_/g, ' ').replace(/\b\w/g, l => l.toUpperCase())}</span>
                                            </label>
                                        ))}
                                    </div>
                                </div>
                            ))}
                        </div>
                    </div>
                    <div className="flex justify-end gap-3 pt-2">
                        <button onClick={() => setModal(false)} className="px-4 py-2 border border-gray-300 text-gray-700 rounded-lg hover:bg-gray-50">Cancel</button>
                        <button onClick={handleSubmit} className="px-4 py-2 bg-indigo-600 text-white rounded-lg hover:bg-indigo-700">{editing ? 'Update' : 'Create'}</button>
                    </div>
                </div>
            </Modal>

            <Modal isOpen={assignModal} onClose={() => setAssignModal(false)} title="Assign Role to User">
                <div className="space-y-4">
                    <FormField label="User" name="user_id" type="select" value={assignForm.user_id} onChange={(e) => setAssignForm(prev => ({ ...prev, user_id: e.target.value }))} options={[{ value: '', label: 'Select user...' }, ...users.map(u => ({ value: String(u.id), label: `${u.name} (${u.email})` }))]} />
                    <FormField label="Role" name="role_id" type="select" value={assignForm.role_id} onChange={(e) => setAssignForm(prev => ({ ...prev, role_id: e.target.value }))} options={[{ value: '', label: 'No role' }, ...roles.map(r => ({ value: String(r.id), label: r.name }))]} />
                    <div className="flex justify-end gap-3 pt-2">
                        <button onClick={() => setAssignModal(false)} className="px-4 py-2 border border-gray-300 text-gray-700 rounded-lg hover:bg-gray-50">Cancel</button>
                        <button onClick={handleAssign} className="px-4 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700">Assign</button>
                    </div>
                </div>
            </Modal>
        </div>
    );
}
