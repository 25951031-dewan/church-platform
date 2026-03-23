import React, { useState, useEffect } from 'react';
import axios from 'axios';
import FeedPage from './plugins/feed/FeedPage';
import EventsPage from './plugins/events/EventsPage';
import CommunityPage from './plugins/community/CommunityPage';
import FaqPage from './plugins/faq/FaqPage';
import ProfilePage from './plugins/profile/ProfilePage';

type Page = 'feed' | 'events' | 'community' | 'faq' | 'profile';

interface User {
    id: number;
    name: string;
    email: string;
}

function LoginPage({ onLogin }: { onLogin: (user: User, token: string) => void }) {
    const [email, setEmail] = useState('');
    const [password, setPassword] = useState('');
    const [error, setError] = useState('');
    const [loading, setLoading] = useState(false);

    const handleSubmit = async (e: React.FormEvent) => {
        e.preventDefault();
        setError('');
        setLoading(true);
        try {
            const { data } = await axios.post('/api/v1/login', { email, password });
            onLogin(data.user, data.token);
        } catch (err: any) {
            setError(err.response?.data?.message ?? 'Invalid credentials. Please try again.');
        } finally {
            setLoading(false);
        }
    };

    return (
        <div className="min-h-screen bg-gradient-to-br from-blue-50 to-indigo-100 flex items-center justify-center p-4">
            <div className="bg-white rounded-2xl shadow-xl p-8 w-full max-w-md">
                <div className="text-center mb-8">
                    <div className="w-16 h-16 bg-indigo-600 rounded-full flex items-center justify-center mx-auto mb-4">
                        <svg className="w-8 h-8 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M12 6v6m0 0v6m0-6h6m-6 0H6" />
                        </svg>
                    </div>
                    <h1 className="text-2xl font-bold text-gray-900">Church Platform</h1>
                    <p className="text-gray-500 mt-1">Sign in to your account</p>
                </div>

                {error && (
                    <div className="mb-4 p-3 bg-red-50 border border-red-200 rounded-lg text-red-700 text-sm">
                        {error}
                    </div>
                )}

                <form onSubmit={handleSubmit} className="space-y-4">
                    <div>
                        <label className="block text-sm font-medium text-gray-700 mb-1">Email</label>
                        <input
                            type="email"
                            value={email}
                            onChange={e => setEmail(e.target.value)}
                            className="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-transparent outline-none"
                            placeholder="you@example.com"
                            required
                        />
                    </div>
                    <div>
                        <label className="block text-sm font-medium text-gray-700 mb-1">Password</label>
                        <input
                            type="password"
                            value={password}
                            onChange={e => setPassword(e.target.value)}
                            className="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-transparent outline-none"
                            placeholder="••••••••"
                            required
                        />
                    </div>
                    <button
                        type="submit"
                        disabled={loading}
                        className="w-full bg-indigo-600 hover:bg-indigo-700 disabled:opacity-60 text-white font-medium py-2.5 rounded-lg transition duration-200"
                    >
                        {loading ? 'Signing in…' : 'Sign In'}
                    </button>
                </form>
            </div>
        </div>
    );
}

export default function App() {
    const [user, setUser] = useState<User | null>(null);
    const [page, setPage] = useState<Page>('feed');
    const [booting, setBooting] = useState(true);

    useEffect(() => {
        const token = localStorage.getItem('auth_token');
        if (token) {
            axios.defaults.headers.common['Authorization'] = `Bearer ${token}`;
            axios.get('/api/v1/user')
                .then(r => setUser(r.data))
                .catch(() => {
                    localStorage.removeItem('auth_token');
                    delete axios.defaults.headers.common['Authorization'];
                })
                .finally(() => setBooting(false));
        } else {
            setBooting(false);
        }
    }, []);

    const handleLogin = (u: User, token: string) => {
        localStorage.setItem('auth_token', token);
        axios.defaults.headers.common['Authorization'] = `Bearer ${token}`;
        setUser(u);
    };

    const handleLogout = async () => {
        try { await axios.post('/api/v1/logout'); } catch {}
        localStorage.removeItem('auth_token');
        delete axios.defaults.headers.common['Authorization'];
        setUser(null);
    };

    if (booting) {
        return (
            <div className="min-h-screen flex items-center justify-center">
                <div className="w-8 h-8 border-4 border-indigo-600 border-t-transparent rounded-full animate-spin" />
            </div>
        );
    }

    if (!user) return <LoginPage onLogin={handleLogin} />;

    const navItems: { key: Page; label: string }[] = [
        { key: 'feed', label: 'Feed' },
        { key: 'events', label: 'Events' },
        { key: 'community', label: 'Community' },
        { key: 'faq', label: 'FAQ' },
        { key: 'profile', label: 'Profile' },
    ];

    return (
        <div className="min-h-screen bg-gray-50">
            <nav className="bg-white shadow-sm border-b border-gray-200 sticky top-0 z-10">
                <div className="max-w-4xl mx-auto px-4 flex items-center justify-between h-14">
                    <span className="font-bold text-indigo-600 text-lg">Church Platform</span>
                    <div className="flex items-center gap-1">
                        {navItems.map(item => (
                            <button
                                key={item.key}
                                onClick={() => setPage(item.key)}
                                className={`px-3 py-1.5 rounded-lg text-sm font-medium transition-colors ${
                                    page === item.key
                                        ? 'bg-indigo-100 text-indigo-700'
                                        : 'text-gray-600 hover:bg-gray-100'
                                }`}
                            >
                                {item.label}
                            </button>
                        ))}
                        <button
                            onClick={handleLogout}
                            className="ml-2 px-3 py-1.5 text-sm text-red-600 hover:bg-red-50 rounded-lg transition-colors"
                        >
                            Logout
                        </button>
                    </div>
                </div>
            </nav>

            <main className="max-w-4xl mx-auto px-4 py-6">
                {page === 'feed'      && <FeedPage />}
                {page === 'events'    && <EventsPage />}
                {page === 'community' && <CommunityPage />}
                {page === 'faq'       && <FaqPage />}
                {page === 'profile'   && <ProfilePage userId={user.id} />}
            </main>
        </div>
    );
}
