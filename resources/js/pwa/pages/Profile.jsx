import React, { useState } from 'react';
import { useNavigate } from 'react-router-dom';
import { post, put } from '../../components/shared/api';
import { useAuth } from '../hooks/useAuth';

function ErrorBanner({ message }) {
    return (
        <div className="my-2 p-3 bg-red-100 border border-red-300 text-red-700 rounded-lg text-sm">
            {message}
        </div>
    );
}

export default function Profile() {
    const { user, setUser } = useAuth();
    const navigate = useNavigate();

    const [editing, setEditing] = useState(false);
    const [form, setForm] = useState({
        name: user?.name || '',
        phone: user?.phone || '',
        bio: user?.bio || '',
    });
    const [saving, setSaving] = useState(false);
    const [saveError, setSaveError] = useState(null);
    const [saveSuccess, setSaveSuccess] = useState(false);

    const handleLogout = () => {
        localStorage.removeItem('pwa_token');
        localStorage.removeItem('pwa_user');
        setUser(null);
        navigate('/login', { replace: true });
    };

    const handleSave = async (e) => {
        e.preventDefault();
        setSaving(true);
        setSaveError(null);
        setSaveSuccess(false);
        try {
            const updated = await put('/api/profile', {
                name: form.name,
                phone: form.phone,
                bio: form.bio,
            });
            const newUser = updated?.user || updated || { ...user, ...form };
            localStorage.setItem('pwa_user', JSON.stringify(newUser));
            setUser(newUser);
            setSaveSuccess(true);
            setEditing(false);
        } catch (err) {
            setSaveError(err?.message || 'Failed to save profile.');
        } finally {
            setSaving(false);
        }
    };

    const initials = (user?.name || 'U')
        .split(' ')
        .map((n) => n[0])
        .join('')
        .toUpperCase()
        .slice(0, 2);

    return (
        <div className="px-4 py-6 space-y-6">
            {/* Avatar + name */}
            <div className="flex flex-col items-center text-center">
                {user?.avatar ? (
                    <img
                        src={user.avatar}
                        alt={user.name}
                        className="w-20 h-20 rounded-full object-cover border-4 border-indigo-100 mb-3"
                    />
                ) : (
                    <div className="w-20 h-20 rounded-full bg-indigo-600 flex items-center justify-center text-white text-2xl font-bold mb-3">
                        {initials}
                    </div>
                )}
                <h1 className="text-xl font-bold text-gray-900">{user?.name}</h1>
                <p className="text-gray-500 text-sm">{user?.email}</p>
            </div>

            {saveSuccess && (
                <div className="p-3 bg-green-50 border border-green-200 text-green-700 rounded-lg text-sm text-center">
                    Profile updated successfully.
                </div>
            )}

            {/* Profile info / edit */}
            <div className="bg-white rounded-xl shadow-sm border border-gray-100 p-5">
                {!editing ? (
                    <div className="space-y-4">
                        <div className="flex justify-between items-center">
                            <h2 className="font-bold text-gray-800">Profile Details</h2>
                            <button
                                onClick={() => {
                                    setEditing(true);
                                    setSaveSuccess(false);
                                    setForm({
                                        name: user?.name || '',
                                        phone: user?.phone || '',
                                        bio: user?.bio || '',
                                    });
                                }}
                                className="text-indigo-600 text-sm font-medium focus:outline-none hover:underline"
                            >
                                Edit
                            </button>
                        </div>

                        <div className="space-y-3 text-sm">
                            <div>
                                <p className="text-xs text-gray-400 font-medium uppercase tracking-wide">Name</p>
                                <p className="text-gray-800 mt-0.5">{user?.name || '—'}</p>
                            </div>
                            <div>
                                <p className="text-xs text-gray-400 font-medium uppercase tracking-wide">Email</p>
                                <p className="text-gray-800 mt-0.5">{user?.email || '—'}</p>
                            </div>
                            <div>
                                <p className="text-xs text-gray-400 font-medium uppercase tracking-wide">Phone</p>
                                <p className="text-gray-800 mt-0.5">{user?.phone || '—'}</p>
                            </div>
                            {user?.bio && (
                                <div>
                                    <p className="text-xs text-gray-400 font-medium uppercase tracking-wide">Bio</p>
                                    <p className="text-gray-800 mt-0.5 leading-relaxed">{user.bio}</p>
                                </div>
                            )}
                        </div>
                    </div>
                ) : (
                    <form onSubmit={handleSave} className="space-y-4">
                        <div className="flex justify-between items-center mb-1">
                            <h2 className="font-bold text-gray-800">Edit Profile</h2>
                        </div>

                        {saveError && <ErrorBanner message={saveError} />}

                        <div>
                            <label className="block text-xs font-medium text-gray-600 mb-1">Name</label>
                            <input
                                type="text"
                                value={form.name}
                                onChange={(e) => setForm((f) => ({ ...f, name: e.target.value }))}
                                className="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-indigo-400"
                            />
                        </div>

                        <div>
                            <label className="block text-xs font-medium text-gray-600 mb-1">Phone</label>
                            <input
                                type="tel"
                                value={form.phone}
                                onChange={(e) => setForm((f) => ({ ...f, phone: e.target.value }))}
                                placeholder="+1 (555) 000-0000"
                                className="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-indigo-400"
                            />
                        </div>

                        <div>
                            <label className="block text-xs font-medium text-gray-600 mb-1">Bio</label>
                            <textarea
                                value={form.bio}
                                onChange={(e) => setForm((f) => ({ ...f, bio: e.target.value }))}
                                rows={3}
                                placeholder="Tell us a little about yourself..."
                                className="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-indigo-400 resize-none"
                            />
                        </div>

                        <div className="flex gap-3">
                            <button
                                type="button"
                                onClick={() => setEditing(false)}
                                className="flex-1 py-2 border border-gray-300 text-gray-700 text-sm font-medium rounded-lg focus:outline-none hover:bg-gray-50"
                            >
                                Cancel
                            </button>
                            <button
                                type="submit"
                                disabled={saving}
                                className="flex-1 py-2 bg-indigo-600 text-white text-sm font-semibold rounded-lg hover:bg-indigo-700 disabled:opacity-60 focus:outline-none"
                            >
                                {saving ? 'Saving...' : 'Save Changes'}
                            </button>
                        </div>
                    </form>
                )}
            </div>

            {/* Logout */}
            <button
                onClick={handleLogout}
                className="w-full py-3 border border-red-300 text-red-600 text-sm font-semibold rounded-xl hover:bg-red-50 focus:outline-none transition-colors"
            >
                Sign Out
            </button>
        </div>
    );
}
