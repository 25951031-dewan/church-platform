import React, { createContext, useState, useEffect } from 'react';
import { HashRouter, Routes, Route, Navigate, useLocation } from 'react-router-dom';
import { get } from '../components/shared/api';
import BottomNav from './components/BottomNav';
import Home from './pages/Home';
import Sermons from './pages/Sermons';
import Events from './pages/Events';
import Prayers from './pages/Prayers';
import Community from './pages/Community';
import CommunityFeed from './pages/CommunityFeed';
import GroupDetail from './pages/GroupDetail';
import CounselingPortal from './pages/CounselingPortal';
import ChurchDirectory from './pages/ChurchDirectory';
import Login from './pages/Login';
import Profile from './pages/Profile';

export const PwaContext = createContext(null);

function AppShell() {
    const location = useLocation();
    const isLoginPage = location.pathname === '/login';
    const { user } = React.useContext(PwaContext);

    return (
        <div className="flex flex-col min-h-screen bg-gray-100">
            {/* Top header bar */}
            <header
                className="fixed top-0 left-0 right-0 z-50 flex items-center justify-between px-4 py-3 shadow-md"
                style={{ backgroundColor: '#0C0E12' }}
            >
                <span className="text-white font-bold text-lg tracking-wide">
                    {window.CHURCH_NAME || 'Church Platform'}
                </span>
                <button
                    className="text-white text-xl focus:outline-none"
                    aria-label="Notifications"
                >
                    🔔
                </button>
            </header>

            {/* Main content area */}
            <main className={`flex-1 overflow-y-auto pt-14 ${!isLoginPage ? 'pb-20' : ''}`}>
                <Routes>
                    <Route path="/" element={<Home />} />
                    <Route path="/sermons" element={<Sermons />} />
                    <Route path="/events" element={<Events />} />
                    <Route path="/prayers" element={<Prayers />} />
                    <Route path="/community" element={<CommunityFeed />} />
                    <Route path="/community/groups/:id" element={<GroupDetail />} />
                    <Route path="/counseling" element={user ? <CounselingPortal /> : <Navigate to="/login" replace />} />
                    <Route path="/churches" element={<ChurchDirectory />} />
                    <Route path="/profile" element={user ? <Profile /> : <Navigate to="/login" replace />} />
                    <Route path="/login" element={<Login />} />
                    <Route path="*" element={<Navigate to="/" replace />} />
                </Routes>
            </main>

            {/* Bottom nav — hidden on login */}
            {!isLoginPage && <BottomNav />}
        </div>
    );
}

export default function PwaApp() {
    const storedUser = (() => {
        try {
            const raw = localStorage.getItem('pwa_user');
            return raw ? JSON.parse(raw) : null;
        } catch {
            return null;
        }
    })();

    const [user, setUserState] = useState(storedUser);
    const churchMode = window.CHURCH_MODE || 'single';

    const setUser = (userData) => {
        if (userData) {
            localStorage.setItem('pwa_user', JSON.stringify(userData));
        } else {
            localStorage.removeItem('pwa_user');
        }
        setUserState(userData);
    };

    // Validate token on mount
    useEffect(() => {
        const token = localStorage.getItem('pwa_token');
        if (!token) {
            setUser(null);
            return;
        }

        get('/api/profile')
            .then((data) => {
                setUser(data);
            })
            .catch((err) => {
                if (err?.status === 401 || err?.message?.includes('401')) {
                    localStorage.removeItem('pwa_token');
                    setUser(null);
                }
            });
    }, []);

    return (
        <PwaContext.Provider value={{ user, setUser, churchMode }}>
            <HashRouter>
                <AppShell />
            </HashRouter>
        </PwaContext.Provider>
    );
}
