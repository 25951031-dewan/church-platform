import React from 'react';
import { useLocation, useNavigate } from 'react-router-dom';

const baseNavItems = [
    { label: 'Home', icon: '🏠', path: '/' },
    { label: 'Sermons', icon: '🎵', path: '/sermons' },
    { label: 'Events', icon: '📅', path: '/events' },
    { label: 'Prayers', icon: '🙏', path: '/prayers' },
    { label: 'Community', icon: '👥', path: '/community', moduleKey: 'community' },
];

export default function BottomNav() {
    const location = useLocation();
    const navigate = useNavigate();

    const navItems = baseNavItems.filter((item) => {
        if (!item.moduleKey) return true;
        return window.MODULES?.[item.moduleKey] !== false;
    });

    const isActive = (path) => {
        if (path === '/') {
            return location.pathname === '/';
        }
        return location.pathname.startsWith(path);
    };

    return (
        <nav className="fixed bottom-0 left-0 right-0 z-50 bg-white border-t border-gray-200 shadow-lg">
            <div className="flex items-stretch justify-around">
                {navItems.map((item) => {
                    const active = isActive(item.path);
                    return (
                        <button
                            key={item.path}
                            onClick={() => navigate(item.path)}
                            className={`flex flex-col items-center justify-center flex-1 py-2 px-1 text-xs font-medium transition-colors duration-150 focus:outline-none ${
                                active
                                    ? 'text-indigo-600 border-t-2 border-indigo-600'
                                    : 'text-gray-500 hover:text-indigo-400 border-t-2 border-transparent'
                            }`}
                            aria-label={item.label}
                        >
                            <span className="text-xl leading-none mb-0.5">{item.icon}</span>
                            <span>{item.label}</span>
                        </button>
                    );
                })}
            </div>
        </nav>
    );
}
