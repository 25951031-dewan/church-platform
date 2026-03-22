import { lazy, Suspense } from 'react';
import { useSearchParams } from 'react-router-dom';

// Sub-components are lazy-loaded so the heavy GrapesJS bundle (etc.)
// doesn't inflate the settings initial load.
const TABS = [
    { key: 'general',    label: 'General',    Component: lazy(() => import('./settings/GeneralSettings'))    },
    { key: 'email',      label: 'Email',      Component: lazy(() => import('./settings/EmailSettings'))      },
    { key: 'appearance', label: 'Appearance', Component: lazy(() => import('./settings/AppearanceSettings')) },
    { key: 'seo',        label: 'SEO',        Component: lazy(() => import('./settings/SeoSettings'))        },
    { key: 'cache',      label: 'Cache',      Component: lazy(() => import('./settings/CacheSettings'))      },
    { key: 'auth',       label: 'Auth',       Component: lazy(() => import('./settings/AuthSettings'))       },
    { key: 'storage',    label: 'Storage',    Component: lazy(() => import('./settings/StorageSettings'))    },
    { key: 'api',        label: 'API Keys',   Component: lazy(() => import('./settings/ApiSettings'))        },
    { key: 'platform',   label: 'Platform',   Component: lazy(() => import('./settings/PlatformSettings'))   },
];

export default function SettingsManager() {
    const [searchParams, setSearchParams] = useSearchParams();
    const activeKey = searchParams.get('tab') ?? 'general';
    const activeTab = TABS.find(t => t.key === activeKey) ?? TABS[0];

    return (
        <div className="flex min-h-screen">
            {/* Sidebar nav */}
            <nav className="w-48 shrink-0 border-r border-gray-200 bg-gray-50 px-3 py-6">
                <p className="mb-3 px-2 text-xs font-semibold uppercase tracking-wider text-gray-400">
                    Settings
                </p>
                <ul className="space-y-0.5">
                    {TABS.map(tab => (
                        <li key={tab.key}>
                            <button
                                onClick={() => setSearchParams({ tab: tab.key })}
                                className={[
                                    'w-full rounded-lg px-3 py-2 text-left text-sm transition',
                                    tab.key === activeKey
                                        ? 'bg-white font-medium text-gray-900 shadow-sm'
                                        : 'text-gray-600 hover:bg-white hover:text-gray-900',
                                ].join(' ')}
                            >
                                {tab.label}
                            </button>
                        </li>
                    ))}
                </ul>
            </nav>

            {/* Content */}
            <main className="flex-1 px-8 py-8">
                <Suspense fallback={<div className="text-sm text-gray-400">Loading…</div>}>
                    <activeTab.Component />
                </Suspense>
            </main>
        </div>
    );
}
