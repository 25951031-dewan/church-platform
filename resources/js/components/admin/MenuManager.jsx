import React, { useState, useEffect } from 'react';
import { Modal, FormField, Alert } from '../shared/CrudPanel';
import { get, post, put, del } from '../shared/api';

function MenuItem({ item, index, onUpdate, onRemove, onMoveUp, onMoveDown, isFirst, isLast }) {
    return (
        <div className="border border-gray-200 rounded-lg p-3 bg-white">
            <div className="flex items-center gap-3">
                <div className="flex flex-col gap-1">
                    <button type="button" onClick={() => onMoveUp(index)} disabled={isFirst} className="text-gray-400 hover:text-gray-600 disabled:opacity-30 text-xs leading-none">&#9650;</button>
                    <button type="button" onClick={() => onMoveDown(index)} disabled={isLast} className="text-gray-400 hover:text-gray-600 disabled:opacity-30 text-xs leading-none">&#9660;</button>
                </div>
                <div className="flex-1 grid grid-cols-3 gap-2">
                    <input className="w-full rounded border border-gray-300 px-2 py-1.5 text-sm focus:ring-1 focus:ring-indigo-500 outline-none" placeholder="Label" value={item.label || ''} onChange={(e) => onUpdate(index, 'label', e.target.value)} />
                    <select className="w-full rounded border border-gray-300 px-2 py-1.5 text-sm focus:ring-1 focus:ring-indigo-500 outline-none" value={item.type || 'link'} onChange={(e) => onUpdate(index, 'type', e.target.value)}>
                        <option value="link">External Link</option>
                        <option value="page">Page</option>
                        <option value="category">Category</option>
                        <option value="post">Post</option>
                    </select>
                    <input className="w-full rounded border border-gray-300 px-2 py-1.5 text-sm focus:ring-1 focus:ring-indigo-500 outline-none" placeholder={item.type === 'link' ? 'https://...' : 'slug or ID'} value={item.target || ''} onChange={(e) => onUpdate(index, 'target', e.target.value)} />
                </div>
                <button type="button" onClick={() => onRemove(index)} className="text-red-500 hover:text-red-700 text-lg leading-none">&times;</button>
            </div>
            {item.children && item.children.length > 0 && (
                <div className="ml-8 mt-2 space-y-2">
                    {item.children.map((child, ci) => (
                        <div key={ci} className="border border-gray-100 rounded p-2 bg-gray-50 flex items-center gap-2">
                            <span className="text-gray-400 text-xs">&#8627;</span>
                            <input className="flex-1 rounded border border-gray-300 px-2 py-1 text-sm" placeholder="Sub-label" value={child.label || ''} onChange={(e) => {
                                const nc = [...(item.children || [])];
                                nc[ci] = { ...nc[ci], label: e.target.value };
                                onUpdate(index, 'children', nc);
                            }} />
                            <select className="rounded border border-gray-300 px-2 py-1 text-sm" value={child.type || 'link'} onChange={(e) => {
                                const nc = [...(item.children || [])];
                                nc[ci] = { ...nc[ci], type: e.target.value };
                                onUpdate(index, 'children', nc);
                            }}>
                                <option value="link">Link</option>
                                <option value="page">Page</option>
                                <option value="category">Category</option>
                                <option value="post">Post</option>
                            </select>
                            <input className="flex-1 rounded border border-gray-300 px-2 py-1 text-sm" placeholder="Target" value={child.target || ''} onChange={(e) => {
                                const nc = [...(item.children || [])];
                                nc[ci] = { ...nc[ci], target: e.target.value };
                                onUpdate(index, 'children', nc);
                            }} />
                            <button type="button" className="text-red-400 hover:text-red-600 text-sm" onClick={() => {
                                onUpdate(index, 'children', (item.children || []).filter((_, i) => i !== ci));
                            }}>&times;</button>
                        </div>
                    ))}
                </div>
            )}
            <button type="button" className="mt-2 ml-8 text-xs text-indigo-600 hover:text-indigo-800" onClick={() => {
                onUpdate(index, 'children', [...(item.children || []), { label: '', type: 'link', target: '' }]);
            }}>+ Add sub-menu item</button>
        </div>
    );
}

export default function MenuManager() {
    const [menus, setMenus] = useState([]);
    const [modal, setModal] = useState(false);
    const [editing, setEditing] = useState(null);
    const [form, setForm] = useState({ name: '', location: 'header', items: [], is_active: true });
    const [alert, setAlert] = useState(null);

    useEffect(() => { fetchMenus(); }, []);

    const fetchMenus = async () => {
        try {
            const data = await get('/api/menus');
            setMenus(data.data || []);
        } catch (e) { setAlert({ type: 'error', message: e.message }); }
    };

    const openCreate = () => {
        setEditing(null);
        setForm({ name: '', location: 'header', items: [], is_active: true });
        setModal(true);
    };

    const openEdit = (menu) => {
        setEditing(menu);
        setForm({ name: menu.name || '', location: menu.location || 'header', items: menu.items || [], is_active: menu.is_active ?? true });
        setModal(true);
    };

    const addItem = () => {
        setForm(prev => ({ ...prev, items: [...prev.items, { label: '', type: 'link', target: '', children: [] }] }));
    };

    const updateItem = (index, field, value) => {
        setForm(prev => {
            const newItems = [...prev.items];
            newItems[index] = { ...newItems[index], [field]: value };
            return { ...prev, items: newItems };
        });
    };

    const removeItem = (index) => {
        setForm(prev => ({ ...prev, items: prev.items.filter((_, i) => i !== index) }));
    };

    const moveItem = (index, direction) => {
        setForm(prev => {
            const newItems = [...prev.items];
            const target = index + direction;
            if (target < 0 || target >= newItems.length) return prev;
            [newItems[index], newItems[target]] = [newItems[target], newItems[index]];
            return { ...prev, items: newItems };
        });
    };

    const handleSubmit = async () => {
        try {
            if (editing) {
                await put(`/api/menus/${editing.id}`, form);
                setAlert({ type: 'success', message: 'Menu updated.' });
            } else {
                await post('/api/menus', form);
                setAlert({ type: 'success', message: 'Menu created.' });
            }
            setModal(false);
            fetchMenus();
        } catch (e) { setAlert({ type: 'error', message: e.message }); }
    };

    const handleDelete = async (menu) => {
        if (!confirm(`Delete menu "${menu.name}"?`)) return;
        try {
            await del(`/api/menus/${menu.id}`);
            setAlert({ type: 'success', message: 'Menu deleted.' });
            fetchMenus();
        } catch (e) { setAlert({ type: 'error', message: e.message }); }
    };

    return (
        <div>
            <div className="flex items-center justify-between mb-6">
                <h2 className="text-2xl font-bold text-gray-800">Menu Manager</h2>
                <button onClick={openCreate} className="px-4 py-2 bg-indigo-600 text-white rounded-lg text-sm font-medium hover:bg-indigo-700">+ Add Menu</button>
            </div>
            <Alert {...alert} onClose={() => setAlert(null)} />
            <div className="space-y-4">
                {menus.length === 0 && <p className="text-gray-500 text-center py-8">No menus created yet.</p>}
                {menus.map(menu => (
                    <div key={menu.id} className="bg-white rounded-xl shadow-sm border p-5">
                        <div className="flex items-center justify-between mb-3">
                            <div>
                                <h3 className="text-lg font-semibold text-gray-900">{menu.name}</h3>
                                <div className="flex gap-2 mt-1">
                                    <span className="px-2 py-0.5 rounded-full text-xs font-medium bg-indigo-100 text-indigo-700">{menu.location}</span>
                                    <span className={`px-2 py-0.5 rounded-full text-xs font-medium ${menu.is_active ? 'bg-green-100 text-green-700' : 'bg-red-100 text-red-700'}`}>{menu.is_active ? 'Active' : 'Inactive'}</span>
                                    <span className="text-xs text-gray-400">{(menu.items || []).length} items</span>
                                </div>
                            </div>
                            <div className="flex gap-2">
                                <button onClick={() => openEdit(menu)} className="px-3 py-1.5 bg-indigo-50 text-indigo-600 rounded-lg text-sm font-medium hover:bg-indigo-100">Edit</button>
                                <button onClick={() => handleDelete(menu)} className="px-3 py-1.5 bg-red-50 text-red-600 rounded-lg text-sm font-medium hover:bg-red-100">Delete</button>
                            </div>
                        </div>
                        {(menu.items || []).length > 0 && (
                            <div className="bg-gray-50 rounded-lg p-3">
                                <div className="text-xs font-medium text-gray-500 mb-2">Menu Items:</div>
                                <ul className="space-y-1">
                                    {menu.items.map((item, i) => (
                                        <li key={i} className="text-sm text-gray-700">
                                            <span className="font-medium">{item.label}</span>
                                            <span className="text-gray-400 ml-2">({item.type}: {item.target || '-'})</span>
                                            {item.children && item.children.length > 0 && (
                                                <ul className="ml-4 mt-1">
                                                    {item.children.map((child, ci) => (
                                                        <li key={ci} className="text-xs text-gray-500">&#8627; {child.label} ({child.target || '-'})</li>
                                                    ))}
                                                </ul>
                                            )}
                                        </li>
                                    ))}
                                </ul>
                            </div>
                        )}
                    </div>
                ))}
            </div>
            <Modal isOpen={modal} onClose={() => setModal(false)} title={editing ? 'Edit Menu' : 'New Menu'}>
                <div className="space-y-4">
                    <FormField label="Menu Name" name="name" value={form.name} onChange={(e) => setForm(prev => ({ ...prev, name: e.target.value }))} required />
                    <FormField label="Location" name="location" type="select" value={form.location} onChange={(e) => setForm(prev => ({ ...prev, location: e.target.value }))} options={[{ value: 'header', label: 'Header (Top)' }, { value: 'footer', label: 'Footer' }, { value: 'sidebar', label: 'Sidebar' }]} />
                    <FormField label="Active" name="is_active" type="checkbox" value={form.is_active} onChange={(e) => setForm(prev => ({ ...prev, is_active: e.target.checked }))} />
                    <div className="mt-4">
                        <div className="flex items-center justify-between mb-3">
                            <label className="block text-sm font-medium text-gray-700">Menu Items (drag to reorder)</label>
                            <button type="button" onClick={addItem} className="text-sm text-indigo-600 hover:text-indigo-800 font-medium">+ Add Item</button>
                        </div>
                        <div className="space-y-2">
                            {form.items.length === 0 && <p className="text-gray-400 text-sm text-center py-4">No items. Click "+ Add Item" to start.</p>}
                            {form.items.map((item, index) => (
                                <MenuItem key={index} item={item} index={index} onUpdate={updateItem} onRemove={removeItem} onMoveUp={() => moveItem(index, -1)} onMoveDown={() => moveItem(index, 1)} isFirst={index === 0} isLast={index === form.items.length - 1} />
                            ))}
                        </div>
                    </div>
                    <div className="flex justify-end gap-3 pt-2">
                        <button type="button" onClick={() => setModal(false)} className="px-4 py-2 border border-gray-300 text-gray-700 rounded-lg hover:bg-gray-50">Cancel</button>
                        <button type="button" onClick={handleSubmit} className="px-4 py-2 bg-indigo-600 text-white rounded-lg hover:bg-indigo-700">{editing ? 'Update' : 'Create'}</button>
                    </div>
                </div>
            </Modal>
        </div>
    );
}
