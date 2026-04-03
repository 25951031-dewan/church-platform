import React, { useState, useEffect } from 'react';
import { Alert, FormField } from '../shared/CrudPanel';
import { get, put } from '../shared/api';

export default function ProfileManager() {
    const [form, setForm] = useState({ name: '', email: '', password: '', password_confirmation: '' });
    const [alert, setAlert] = useState(null);
    const [loading, setLoading] = useState(true);
    const [saving, setSaving] = useState(false);

    useEffect(() => {
        fetchProfile();
    }, []);

    const fetchProfile = async () => {
        setLoading(true);
        try {
            const data = await get('/api/profile');
            if (data && data.user) {
                setForm(f => ({ ...f, name: data.user.name || '', email: data.user.email || '' }));
            }
        } catch (e) {
            setAlert({ type: 'error', message: 'Failed to load profile.' });
        }
        setLoading(false);
    };

    const handleChange = (e) => {
        const { name, value } = e.target;
        setForm(f => ({ ...f, [name]: value }));
    };

    const handleSubmit = async (e) => {
        e.preventDefault();
        setAlert(null);

        if (!form.name || !form.email) {
            setAlert({ type: 'error', message: 'Name and email are required.' });
            return;
        }

        if (form.password && form.password.length < 8) {
            setAlert({ type: 'error', message: 'Password must be at least 8 characters.' });
            return;
        }

        if (form.password && form.password !== form.password_confirmation) {
            setAlert({ type: 'error', message: 'Passwords do not match.' });
            return;
        }

        setSaving(true);
        try {
            const payload = { name: form.name, email: form.email };
            if (form.password) {
                payload.password = form.password;
                payload.password_confirmation = form.password_confirmation;
            }
            await put('/api/profile', payload);
            setAlert({ type: 'success', message: 'Profile updated successfully.' });
            setForm(f => ({ ...f, password: '', password_confirmation: '' }));
        } catch (e) {
            setAlert({ type: 'error', message: e.message || 'Failed to update profile.' });
        }
        setSaving(false);
    };

    if (loading) {
        return (
            <div className="flex items-center justify-center py-16">
                <svg className="animate-spin h-8 w-8 text-indigo-600" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                    <circle className="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" strokeWidth="4"></circle>
                    <path className="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
                </svg>
            </div>
        );
    }

    return (
        <div className="max-w-2xl">
            <h2 className="text-2xl font-bold text-gray-800 mb-6">My Profile</h2>

            <Alert {...alert} onClose={() => setAlert(null)} />

            <div className="bg-white rounded-xl shadow-sm border p-6">
                <form onSubmit={handleSubmit} className="space-y-5">
                    <FormField label="Name" name="name" value={form.name} onChange={handleChange} required placeholder="Your full name" />
                    <FormField label="Email" name="email" type="email" value={form.email} onChange={handleChange} required placeholder="your@email.com" />

                    <div className="border-t pt-5 mt-5">
                        <h3 className="text-lg font-semibold text-gray-700 mb-1">Change Password</h3>
                        <p className="text-sm text-gray-500 mb-4">Leave blank to keep your current password.</p>
                        <div className="space-y-4">
                            <FormField label="New Password" name="password" type="password" value={form.password} onChange={handleChange} placeholder="Min 8 characters" />
                            <FormField label="Confirm New Password" name="password_confirmation" type="password" value={form.password_confirmation} onChange={handleChange} placeholder="Confirm new password" />
                        </div>
                    </div>

                    <div className="flex justify-end pt-2">
                        <button type="submit" disabled={saving} className="px-6 py-2.5 bg-indigo-600 text-white rounded-lg hover:bg-indigo-700 font-medium disabled:opacity-50">
                            {saving ? 'Saving...' : 'Save Changes'}
                        </button>
                    </div>
                </form>
            </div>
        </div>
    );
}
