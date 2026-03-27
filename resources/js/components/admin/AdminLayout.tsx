import React, { useState } from 'react';
import { NavLink, Navigate, Route, Routes, useNavigate } from 'react-router-dom';

// Existing .jsx components
import FaqManager from './FaqManager';
import MediaManager from './MediaManager';
import PagesManager from './PagesManager';
import ChurchBuilder from './ChurchBuilder';
import SettingsManager from './SettingsManager';

// New datatable pages
import AdminDashboard from './pages/AdminDashboard';
import AdminUsers from './pages/AdminUsers';
import AdminPosts from './pages/AdminPosts';
import AdminEvents from './pages/AdminEvents';
import AdminCommunities from './pages/AdminCommunities';

interface User { id: number; name: string; email: string; roles?: string[] }
interface Props { user: User; onLogout: () => void }

// ── SVG icon components ────────────────────────────────────────────────────
const Icon = ({ d, ...props }: { d: string; className?: string }) => (
    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth={1.8}
        strokeLinecap="round" strokeLinejoin="round" {...props}>
        <path d={d} />
    </svg>
);

const ICONS = {
    dashboard:   'M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6',
    posts:       'M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z',
    events:      'M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z',
    communities: 'M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z',
    faq:         'M8.228 9c.549-1.165 2.03-2 3.772-2 2.21 0 4 1.343 4 3 0 1.4-1.278 2.575-3.006 2.907-.542.104-.994.54-.994 1.093m0 3h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z',
    media:       'M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z',
    pages:       'M7 21h10a2 2 0 002-2V9.414a1 1 0 00-.293-.707l-5.414-5.414A1 1 0 0012.586 3H7a2 2 0 00-2 2v14a2 2 0 002 2z',
    users:       'M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z',
    church:      'M3 21h18M3 10h18M3 7l9-4 9 4M4 10v11M20 10v11M8 10v4m4-4v4m4-4v4',
    settings:    'M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z M15 12a3 3 0 11-6 0 3 3 0 016 0z',
    back:        'M10 19l-7-7m0 0l7-7m-7 7h18',
    logout:      'M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1',
    menu:        'M4 6h16M4 12h16M4 18h16',
    chevronLeft: 'M15 19l-7-7 7-7',
};

const NAV_SECTIONS = [
    {
        title: 'Overview',
        items: [
            { path: '/admin', label: 'Dashboard', icon: ICONS.dashboard, exact: true },
        ],
    },
    {
        title: 'Content',
        items: [
            { path: '/admin/posts',       label: 'Posts',        icon: ICONS.posts },
            { path: '/admin/events',      label: 'Events',       icon: ICONS.events },
            { path: '/admin/communities', label: 'Communities',  icon: ICONS.communities },
            { path: '/admin/faq',         label: 'FAQ',          icon: ICONS.faq },
            { path: '/admin/media',       label: 'Media',        icon: ICONS.media },
            { path: '/admin/pages',       label: 'Pages',        icon: ICONS.pages },
        ],
    },
    {
        title: 'People',
        items: [
            { path: '/admin/users', label: 'Users', icon: ICONS.users },
        ],
    },
    {
        title: 'Church',
        items: [
            { path: '/admin/church', label: 'Church Builder', icon: ICONS.church },
        ],
    },
    {
        title: 'System',
        items: [
            { path: '/admin/settings', label: 'Settings', icon: ICONS.settings },
        ],
    },
];

export default function AdminLayout({ user, onLogout }: Props) {
    const [collapsed, setCollapsed] = useState(false);
    const navigate = useNavigate();

    return (
        <div className="flex h-screen bg-gray-100 overflow-hidden">

            {/* ── Sidebar ─────────────────────────────────────────────── */}
            <aside
                className={`${collapsed ? 'w-16' : 'w-56'} flex-shrink-0 bg-gray-900 text-white flex flex-col transition-all duration-200`}
            >
                {/* Logo row */}
                <div className="flex items-center justify-between h-14 px-3 border-b border-gray-700/60 flex-shrink-0">
                    {!collapsed && (
                        <span className="font-bold text-indigo-400 text-sm tracking-wide truncate">Church Admin</span>
                    )}
                    <button
                        onClick={() => setCollapsed(c => !c)}
                        className="w-8 h-8 flex items-center justify-center rounded-lg text-gray-400 hover:bg-gray-800 hover:text-white transition-colors ml-auto flex-shrink-0"
                        title={collapsed ? 'Expand sidebar' : 'Collapse sidebar'}
                    >
                        <Icon d={collapsed ? ICONS.menu : ICONS.chevronLeft} className="w-4 h-4" />
                    </button>
                </div>

                {/* Nav */}
                <nav className="flex-1 overflow-y-auto py-3 space-y-1">
                    {NAV_SECTIONS.map(section => (
                        <div key={section.title}>
                            {!collapsed && (
                                <p className="px-4 pt-3 pb-1 text-[10px] font-semibold uppercase tracking-widest text-gray-500">
                                    {section.title}
                                </p>
                            )}
                            {collapsed && <div className="my-2 border-t border-gray-700/40" />}
                            {section.items.map(item => (
                                <NavLink
                                    key={item.path}
                                    to={item.path}
                                    end={item.exact}
                                    title={collapsed ? item.label : undefined}
                                    className={({ isActive }) =>
                                        `flex items-center gap-3 px-3 py-2 mx-2 rounded-lg text-sm transition-colors ${
                                            isActive
                                                ? 'bg-indigo-600 text-white'
                                                : 'text-gray-400 hover:bg-gray-800 hover:text-white'
                                        }`
                                    }
                                >
                                    <Icon d={item.icon} className="w-4 h-4 flex-shrink-0" />
                                    {!collapsed && <span className="truncate">{item.label}</span>}
                                </NavLink>
                            ))}
                        </div>
                    ))}
                </nav>

                {/* User footer */}
                <div className="border-t border-gray-700/60 p-3 flex-shrink-0">
                    {collapsed ? (
                        <div className="flex flex-col items-center gap-2">
                            <button onClick={() => navigate('/')} title="Back to site"
                                className="w-8 h-8 flex items-center justify-center rounded-lg text-gray-400 hover:bg-gray-800 hover:text-white">
                                <Icon d={ICONS.back} className="w-4 h-4" />
                            </button>
                            <button onClick={onLogout} title="Logout"
                                className="w-8 h-8 flex items-center justify-center rounded-lg text-gray-400 hover:bg-red-800 hover:text-red-300">
                                <Icon d={ICONS.logout} className="w-4 h-4" />
                            </button>
                        </div>
                    ) : (
                        <div>
                            <div className="flex items-center gap-2 mb-2">
                                <div className="w-7 h-7 rounded-full bg-indigo-500 flex items-center justify-center text-xs font-bold flex-shrink-0">
                                    {user.name.charAt(0).toUpperCase()}
                                </div>
                                <div className="min-w-0">
                                    <div className="text-xs font-semibold text-white truncate">{user.name}</div>
                                    <div className="text-[10px] text-gray-500 truncate">{user.email}</div>
                                </div>
                            </div>
                            <div className="flex gap-1">
                                <button
                                    onClick={() => navigate('/')}
                                    className="flex-1 flex items-center justify-center gap-1 text-[11px] text-gray-400 hover:text-white py-1 rounded-md hover:bg-gray-800 transition-colors"
                                >
                                    <Icon d={ICONS.back} className="w-3 h-3" /> Site
                                </button>
                                <button
                                    onClick={onLogout}
                                    className="flex-1 flex items-center justify-center gap-1 text-[11px] text-red-400 hover:text-red-300 py-1 rounded-md hover:bg-gray-800 transition-colors"
                                >
                                    <Icon d={ICONS.logout} className="w-3 h-3" /> Logout
                                </button>
                            </div>
                        </div>
                    )}
                </div>
            </aside>

            {/* ── Main area ───────────────────────────────────────────── */}
            <div className="flex-1 flex flex-col overflow-hidden">

                {/* Top bar */}
                <header className="bg-white border-b border-gray-200 h-14 flex items-center px-6 flex-shrink-0">
                    <span className="text-sm font-semibold text-gray-500">
                        Logged in as <span className="text-gray-800">{user.name}</span>
                    </span>
                    <div className="ml-auto flex items-center gap-2">
                        <span className="text-xs bg-red-100 text-red-600 font-semibold px-2 py-0.5 rounded-full">Admin</span>
                    </div>
                </header>

                {/* Page content */}
                <main className="flex-1 overflow-y-auto p-6">
                    <Routes>
                        <Route index element={<AdminDashboard />} />
                        <Route path="posts"       element={<AdminPosts />} />
                        <Route path="events"      element={<AdminEvents />} />
                        <Route path="communities" element={<AdminCommunities />} />
                        <Route path="users"       element={<AdminUsers />} />
                        <Route path="faq"         element={<FaqManager />} />
                        <Route path="media"       element={<MediaManager />} />
                        <Route path="pages"       element={<PagesManager />} />
                        <Route path="pages/:id/builder" element={<PagesManager />} />
                        <Route path="church"      element={<ChurchBuilder churchId={1} />} />
                        <Route path="settings"    element={<SettingsManager />} />
                        <Route path="*"           element={<Navigate to="/admin" replace />} />
                    </Routes>
                </main>
            </div>
        </div>
    );
}
