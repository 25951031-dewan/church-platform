import React, { useState } from 'react';
import { useNavigate } from 'react-router-dom';
import { post } from '../../components/shared/api';
import { useAuth } from '../hooks/useAuth';

export default function Login() {
    const { setUser } = useAuth();
    const navigate = useNavigate();
    const [form, setForm] = useState({ email: '', password: '' });
    const [loading, setLoading] = useState(false);
    const [error, setError] = useState(null);

    const handleSubmit = async (e) => {
        e.preventDefault();
        setLoading(true);
        setError(null);

        try {
            const data = await post('/api/login', {
                email: form.email,
                password: form.password,
            });

            const token = data?.token || data?.access_token;
            if (token) {
                localStorage.setItem('pwa_token', token);
            }

            const user = data?.user || data;
            if (user) {
                localStorage.setItem('pwa_user', JSON.stringify(user));
                setUser(user);
            }

            navigate('/', { replace: true });
        } catch (err) {
            setError(
                err?.message ||
                err?.data?.message ||
                'Invalid credentials. Please try again.'
            );
        } finally {
            setLoading(false);
        }
    };

    return (
        <div className="min-h-screen flex flex-col justify-center px-6 py-12 bg-gray-50">
            {/* Logo / App name */}
            <div className="text-center mb-8">
                <div
                    className="inline-flex items-center justify-center w-16 h-16 rounded-2xl text-white text-3xl mb-4 shadow-md"
                    style={{ backgroundColor: '#0C0E12' }}
                >
                    ⛪
                </div>
                <h1 className="text-2xl font-bold text-gray-900">
                    {window.APP_NAME || window.CHURCH_NAME || 'Church Platform'}
                </h1>
                <p className="text-gray-500 text-sm mt-1">Sign in to your account</p>
            </div>

            {/* Form card */}
            <div className="bg-white rounded-2xl shadow-sm border border-gray-100 p-6 w-full max-w-md mx-auto">
                {error && (
                    <div className="mb-4 p-3 bg-red-50 border border-red-200 text-red-700 rounded-lg text-sm">
                        {error}
                    </div>
                )}

                <form onSubmit={handleSubmit} className="space-y-4">
                    <div>
                        <label
                            htmlFor="email"
                            className="block text-sm font-medium text-gray-700 mb-1"
                        >
                            Email address
                        </label>
                        <input
                            id="email"
                            type="email"
                            autoComplete="email"
                            required
                            value={form.email}
                            onChange={(e) => setForm((f) => ({ ...f, email: e.target.value }))}
                            placeholder="you@example.com"
                            className="w-full px-4 py-2.5 rounded-lg border border-gray-300 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-400"
                        />
                    </div>

                    <div>
                        <label
                            htmlFor="password"
                            className="block text-sm font-medium text-gray-700 mb-1"
                        >
                            Password
                        </label>
                        <input
                            id="password"
                            type="password"
                            autoComplete="current-password"
                            required
                            value={form.password}
                            onChange={(e) => setForm((f) => ({ ...f, password: e.target.value }))}
                            placeholder="••••••••"
                            className="w-full px-4 py-2.5 rounded-lg border border-gray-300 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-400"
                        />
                    </div>

                    <button
                        type="submit"
                        disabled={loading}
                        className="w-full py-2.5 bg-indigo-600 text-white font-semibold text-sm rounded-lg hover:bg-indigo-700 disabled:opacity-60 focus:outline-none transition-colors"
                    >
                        {loading ? 'Signing in...' : 'Sign In'}
                    </button>
                </form>

                <div className="mt-5 text-center">
                    <p className="text-sm text-gray-500">
                        Don&apos;t have an account?{' '}
                        <button
                            className="text-indigo-600 font-medium hover:underline focus:outline-none"
                            onClick={() => alert('Registration coming soon!')}
                        >
                            Register
                        </button>
                    </p>
                </div>
            </div>

            {/* Continue as guest */}
            <div className="mt-4 text-center">
                <button
                    onClick={() => navigate('/')}
                    className="text-sm text-gray-500 hover:text-indigo-600 focus:outline-none underline"
                >
                    Continue as guest
                </button>
            </div>
        </div>
    );
}
