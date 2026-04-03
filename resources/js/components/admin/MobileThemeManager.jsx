import React, { useState, useEffect } from 'react';
import { FormField, Alert } from '../shared/CrudPanel';
import { get, put, upload } from '../shared/api';

function PhonePreview({ config, pwaConfig }) {
    const nav = (config?.bottom_nav || []).filter(n => n.enabled);
    const quickActions = (config?.quick_actions || []).filter(a => a.enabled);
    return (
        <div className="mx-auto" style={{ width: '280px' }}>
            <div className="bg-gray-900 rounded-[2rem] p-2 shadow-2xl">
                <div className="bg-white rounded-[1.5rem] overflow-hidden" style={{ height: '520px' }}>
                    {/* Status bar */}
                    <div className="flex items-center justify-between px-4 py-1 bg-gray-50">
                        <span className="text-[10px] font-semibold text-gray-800">9:41</span>
                        <div className="flex gap-1">
                            <i className="fas fa-signal text-[8px] text-gray-800"></i>
                            <i className="fas fa-wifi text-[8px] text-gray-800"></i>
                            <i className="fas fa-battery-full text-[8px] text-gray-800"></i>
                        </div>
                    </div>

                    {/* Header */}
                    <div className={`px-4 py-2 flex items-center justify-between bg-indigo-600 ${config?.header_style === 'large' ? 'py-4' : ''}`}>
                        <div className="flex items-center gap-2">
                            <div className="w-6 h-6 bg-white/20 rounded-lg flex items-center justify-center">
                                <i className="fas fa-church text-white text-[10px]"></i>
                            </div>
                            <span className="text-white text-xs font-bold">{pwaConfig?.pwa_short_name || 'Church'}</span>
                        </div>
                        <div className="flex gap-2">
                            <i className="fas fa-search text-white text-[10px]"></i>
                            <i className="fas fa-bell text-white text-[10px]"></i>
                        </div>
                    </div>

                    {/* Content */}
                    <div className="flex-1 overflow-hidden bg-gray-50 px-3 py-2" style={{ height: '380px' }}>
                        {/* Quick actions */}
                        {quickActions.length > 0 && (
                            <div className="flex gap-2 mb-3 overflow-x-auto">
                                {quickActions.map(action => (
                                    <div key={action.id} className="flex flex-col items-center min-w-[50px]">
                                        <div className="w-10 h-10 bg-indigo-100 rounded-xl flex items-center justify-center mb-1">
                                            <i className={`fas ${action.icon} text-indigo-600 text-xs`}></i>
                                        </div>
                                        <span className="text-[8px] text-gray-600">{action.label}</span>
                                    </div>
                                ))}
                            </div>
                        )}

                        {/* Cards */}
                        <div className={`bg-white ${config?.card_style === 'elevated' ? 'shadow-md' : 'shadow-sm'} ${config?.card_style === 'flat' ? 'rounded-none' : 'rounded-xl'} p-3 mb-2`}>
                            <div className="flex items-center gap-2 mb-2">
                                <i className="fas fa-book-bible text-indigo-500 text-[10px]"></i>
                                <span className="text-[10px] font-semibold text-gray-800">Verse of the Day</span>
                            </div>
                            <p className="text-[9px] text-gray-600 italic">"For God so loved the world..."</p>
                            <span className="text-[8px] text-indigo-500 mt-1 block">- John 3:16</span>
                        </div>

                        <div className={`bg-white ${config?.card_style === 'elevated' ? 'shadow-md' : 'shadow-sm'} ${config?.card_style === 'flat' ? 'rounded-none' : 'rounded-xl'} p-3 mb-2`}>
                            <div className="flex items-center gap-2 mb-2">
                                <i className="fas fa-microphone-alt text-purple-500 text-[10px]"></i>
                                <span className="text-[10px] font-semibold text-gray-800">Latest Sermon</span>
                            </div>
                            <div className="flex gap-2">
                                <div className="w-14 h-10 bg-gray-200 rounded"></div>
                                <div>
                                    <p className="text-[9px] font-medium text-gray-800">Walking in Faith</p>
                                    <p className="text-[8px] text-gray-500">Pastor John</p>
                                </div>
                            </div>
                        </div>

                        <div className={`bg-white ${config?.card_style === 'elevated' ? 'shadow-md' : 'shadow-sm'} ${config?.card_style === 'flat' ? 'rounded-none' : 'rounded-xl'} p-3 mb-2`}>
                            <div className="flex items-center gap-2 mb-2">
                                <i className="fas fa-calendar text-green-500 text-[10px]"></i>
                                <span className="text-[10px] font-semibold text-gray-800">Upcoming Events</span>
                            </div>
                            <div className="flex items-center gap-2">
                                <div className="bg-indigo-50 rounded-lg p-1.5 text-center min-w-[30px]">
                                    <span className="text-[10px] font-bold text-indigo-600 block">SUN</span>
                                    <span className="text-[12px] font-bold text-indigo-800 block">02</span>
                                </div>
                                <div>
                                    <p className="text-[9px] font-medium text-gray-800">Sunday Worship</p>
                                    <p className="text-[8px] text-gray-500">9:00 AM</p>
                                </div>
                            </div>
                        </div>
                    </div>

                    {/* Bottom navigation */}
                    <div className="flex items-center justify-around px-2 py-1.5 bg-white border-t border-gray-200">
                        {nav.slice(0, 5).map((item, i) => (
                            <div key={item.id} className="flex flex-col items-center">
                                <i className={`fas ${item.icon} text-[11px] ${i === 0 ? 'text-indigo-600' : 'text-gray-400'}`}></i>
                                <span className={`text-[7px] mt-0.5 ${i === 0 ? 'text-indigo-600 font-semibold' : 'text-gray-400'}`}>{item.label}</span>
                            </div>
                        ))}
                    </div>
                </div>
            </div>
        </div>
    );
}

export default function MobileThemeManager() {
    const [activeTab, setActiveTab] = useState('layout');
    const [loading, setLoading] = useState(true);
    const [saving, setSaving] = useState(false);
    const [alert, setAlert] = useState(null);
    const [enabled, setEnabled] = useState(true);
    const [config, setConfig] = useState(null);
    const [pwaConfig, setPwaConfig] = useState({});

    useEffect(() => { fetchData(); }, []);

    const fetchData = async () => {
        setLoading(true);
        try {
            const [mobileData, pwaData] = await Promise.all([
                get('/api/mobile-theme'),
                get('/api/pwa-config'),
            ]);
            const md = mobileData.data || mobileData;
            setEnabled(md.enabled);
            setConfig(md.config);
            setPwaConfig(pwaData.data || pwaData);
        } catch (e) {
            setAlert({ type: 'error', message: 'Failed to load: ' + e.message });
        }
        setLoading(false);
    };

    const updateConfig = (path, value) => {
        setConfig(prev => {
            const updated = { ...prev };
            const keys = path.split('.');
            let obj = updated;
            for (let i = 0; i < keys.length - 1; i++) {
                obj[keys[i]] = { ...obj[keys[i]] };
                obj = obj[keys[i]];
            }
            obj[keys[keys.length - 1]] = value;
            return updated;
        });
    };

    const updateNavItem = (index, field, value) => {
        setConfig(prev => {
            const nav = [...(prev.bottom_nav || [])];
            nav[index] = { ...nav[index], [field]: value };
            return { ...prev, bottom_nav: nav };
        });
    };

    const updateQuickAction = (index, field, value) => {
        setConfig(prev => {
            const actions = [...(prev.quick_actions || [])];
            actions[index] = { ...actions[index], [field]: value };
            return { ...prev, quick_actions: actions };
        });
    };

    const handleSaveMobile = async () => {
        setSaving(true);
        try {
            await put('/api/mobile-theme', { enabled, config });
            setAlert({ type: 'success', message: 'Mobile theme saved!' });
        } catch (e) {
            setAlert({ type: 'error', message: 'Failed: ' + e.message });
        }
        setSaving(false);
    };

    const handleSavePwa = async () => {
        setSaving(true);
        try {
            await put('/api/pwa-config', pwaConfig);
            setAlert({ type: 'success', message: 'PWA settings saved!' });
        } catch (e) {
            setAlert({ type: 'error', message: 'Failed: ' + e.message });
        }
        setSaving(false);
    };

    const tabs = [
        { id: 'layout', label: 'Layout', icon: 'fa-mobile-alt' },
        { id: 'navigation', label: 'Navigation', icon: 'fa-compass' },
        { id: 'actions', label: 'Quick Actions', icon: 'fa-bolt' },
        { id: 'pwa', label: 'PWA Settings', icon: 'fa-download' },
    ];

    if (loading || !config) {
        return (
            <div className="flex items-center justify-center py-20">
                <svg className="animate-spin h-8 w-8 text-indigo-600" fill="none" viewBox="0 0 24 24">
                    <circle className="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" strokeWidth="4"></circle>
                    <path className="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
                </svg>
            </div>
        );
    }

    return (
        <div>
            <div className="flex items-center justify-between mb-6">
                <h2 className="text-2xl font-bold text-gray-800">
                    <i className="fas fa-mobile-alt text-indigo-600 mr-2"></i>
                    Mobile Theme
                </h2>
                <label className="flex items-center gap-2 cursor-pointer">
                    <input type="checkbox" checked={enabled} onChange={(e) => setEnabled(e.target.checked)} className="w-4 h-4 text-indigo-600 rounded border-gray-300" />
                    <span className="text-sm text-gray-700 font-medium">Enable Mobile Theme</span>
                </label>
            </div>

            <Alert {...alert} onClose={() => setAlert(null)} />

            <div className="flex gap-1 mb-6 bg-gray-100 rounded-lg p-1 w-fit">
                {tabs.map(tab => (
                    <button
                        key={tab.id}
                        onClick={() => setActiveTab(tab.id)}
                        className={`px-4 py-2 rounded-md text-sm font-medium transition-colors flex items-center gap-2 ${
                            activeTab === tab.id ? 'bg-white text-gray-900 shadow-sm' : 'text-gray-500 hover:text-gray-700'
                        }`}
                    >
                        <i className={`fas ${tab.icon} text-xs`}></i>
                        {tab.label}
                    </button>
                ))}
            </div>

            <div className="grid grid-cols-1 lg:grid-cols-2 gap-6">
                {/* Settings panel */}
                <div>
                    {/* LAYOUT TAB */}
                    {activeTab === 'layout' && (
                        <div className="space-y-6">
                            <div className="bg-white rounded-xl shadow-sm border p-6">
                                <h3 className="text-lg font-semibold text-gray-900 mb-4">Header Style</h3>
                                <div className="grid grid-cols-3 gap-3">
                                    {['compact', 'standard', 'large'].map(style => (
                                        <button
                                            key={style}
                                            onClick={() => updateConfig('header_style', style)}
                                            className={`p-3 rounded-lg border-2 text-center transition-all ${
                                                config.header_style === style ? 'border-indigo-500 bg-indigo-50' : 'border-gray-200'
                                            }`}
                                        >
                                            <div className={`bg-indigo-500 rounded mb-1 mx-auto w-full ${style === 'large' ? 'h-6' : style === 'standard' ? 'h-4' : 'h-3'}`}></div>
                                            <span className="text-xs capitalize font-medium">{style}</span>
                                        </button>
                                    ))}
                                </div>
                            </div>

                            <div className="bg-white rounded-xl shadow-sm border p-6">
                                <h3 className="text-lg font-semibold text-gray-900 mb-4">Card Style</h3>
                                <div className="grid grid-cols-3 gap-3">
                                    {['rounded', 'flat', 'elevated'].map(style => (
                                        <button
                                            key={style}
                                            onClick={() => updateConfig('card_style', style)}
                                            className={`p-3 rounded-lg border-2 text-center transition-all ${
                                                config.card_style === style ? 'border-indigo-500 bg-indigo-50' : 'border-gray-200'
                                            }`}
                                        >
                                            <div className={`bg-gray-200 p-2 mb-1 ${style === 'rounded' ? 'rounded-xl' : style === 'elevated' ? 'rounded-xl shadow-md' : ''}`}>
                                                <div className="h-3 bg-gray-300 rounded w-full"></div>
                                            </div>
                                            <span className="text-xs capitalize font-medium">{style}</span>
                                        </button>
                                    ))}
                                </div>
                            </div>

                            <div className="bg-white rounded-xl shadow-sm border p-6">
                                <h3 className="text-lg font-semibold text-gray-900 mb-4">Font Size</h3>
                                <div className="grid grid-cols-3 gap-3">
                                    {['small', 'medium', 'large'].map(size => (
                                        <button
                                            key={size}
                                            onClick={() => updateConfig('font_size', size)}
                                            className={`p-3 rounded-lg border-2 text-center transition-all ${
                                                config.font_size === size ? 'border-indigo-500 bg-indigo-50' : 'border-gray-200'
                                            }`}
                                        >
                                            <span className={`block font-medium capitalize ${size === 'small' ? 'text-xs' : size === 'large' ? 'text-base' : 'text-sm'}`}>Aa</span>
                                            <span className="text-xs text-gray-500 capitalize">{size}</span>
                                        </button>
                                    ))}
                                </div>
                            </div>

                            <div className="bg-white rounded-xl shadow-sm border p-6">
                                <h3 className="text-lg font-semibold text-gray-900 mb-4">Gestures & Interactions</h3>
                                <div className="space-y-3">
                                    {[
                                        { key: 'enable_pull_refresh', label: 'Pull to Refresh', desc: 'Pull down to refresh content' },
                                        { key: 'enable_swipe_nav', label: 'Swipe Navigation', desc: 'Swipe left/right to navigate' },
                                        { key: 'enable_haptics', label: 'Haptic Feedback', desc: 'Vibration feedback on interactions' },
                                    ].map(item => (
                                        <label key={item.key} className="flex items-center gap-3 cursor-pointer">
                                            <input
                                                type="checkbox"
                                                checked={config[item.key] ?? true}
                                                onChange={(e) => updateConfig(item.key, e.target.checked)}
                                                className="w-4 h-4 text-indigo-600 rounded border-gray-300"
                                            />
                                            <div>
                                                <span className="text-sm font-medium text-gray-700">{item.label}</span>
                                                <p className="text-xs text-gray-500">{item.desc}</p>
                                            </div>
                                        </label>
                                    ))}
                                </div>
                            </div>

                            <div className="flex justify-end">
                                <button onClick={handleSaveMobile} disabled={saving} className="px-6 py-2.5 bg-indigo-600 text-white rounded-lg text-sm font-medium hover:bg-indigo-700 disabled:opacity-50">
                                    {saving ? 'Saving...' : 'Save Mobile Settings'}
                                </button>
                            </div>
                        </div>
                    )}

                    {/* NAVIGATION TAB */}
                    {activeTab === 'navigation' && (
                        <div className="space-y-6">
                            <div className="bg-white rounded-xl shadow-sm border p-6">
                                <h3 className="text-lg font-semibold text-gray-900 mb-4">Bottom Navigation Bar</h3>
                                <p className="text-sm text-gray-500 mb-4">Configure the bottom navigation bar items (max 5 visible)</p>
                                <div className="space-y-3">
                                    {(config.bottom_nav || []).map((item, i) => (
                                        <div key={item.id} className="flex items-center gap-3 p-3 bg-gray-50 rounded-lg">
                                            <input type="checkbox" checked={item.enabled} onChange={(e) => updateNavItem(i, 'enabled', e.target.checked)} className="w-4 h-4 text-indigo-600 rounded border-gray-300" />
                                            <div className="w-8 h-8 bg-indigo-100 rounded-lg flex items-center justify-center">
                                                <i className={`fas ${item.icon} text-indigo-600 text-sm`}></i>
                                            </div>
                                            <div className="flex-1 grid grid-cols-3 gap-2">
                                                <input
                                                    type="text"
                                                    value={item.label}
                                                    onChange={(e) => updateNavItem(i, 'label', e.target.value)}
                                                    className="border border-gray-200 rounded px-2 py-1 text-sm"
                                                    placeholder="Label"
                                                />
                                                <input
                                                    type="text"
                                                    value={item.icon}
                                                    onChange={(e) => updateNavItem(i, 'icon', e.target.value)}
                                                    className="border border-gray-200 rounded px-2 py-1 text-sm"
                                                    placeholder="fa-icon"
                                                />
                                                <input
                                                    type="text"
                                                    value={item.route}
                                                    onChange={(e) => updateNavItem(i, 'route', e.target.value)}
                                                    className="border border-gray-200 rounded px-2 py-1 text-sm"
                                                    placeholder="/route"
                                                />
                                            </div>
                                        </div>
                                    ))}
                                </div>
                            </div>
                            <div className="flex justify-end">
                                <button onClick={handleSaveMobile} disabled={saving} className="px-6 py-2.5 bg-indigo-600 text-white rounded-lg text-sm font-medium hover:bg-indigo-700 disabled:opacity-50">
                                    {saving ? 'Saving...' : 'Save Navigation'}
                                </button>
                            </div>
                        </div>
                    )}

                    {/* QUICK ACTIONS TAB */}
                    {activeTab === 'actions' && (
                        <div className="space-y-6">
                            <div className="bg-white rounded-xl shadow-sm border p-6">
                                <h3 className="text-lg font-semibold text-gray-900 mb-4">Quick Action Buttons</h3>
                                <p className="text-sm text-gray-500 mb-4">These appear at the top of the mobile home screen</p>
                                <div className="space-y-3">
                                    {(config.quick_actions || []).map((action, i) => (
                                        <div key={action.id} className="flex items-center gap-3 p-3 bg-gray-50 rounded-lg">
                                            <input type="checkbox" checked={action.enabled} onChange={(e) => updateQuickAction(i, 'enabled', e.target.checked)} className="w-4 h-4 text-indigo-600 rounded border-gray-300" />
                                            <div className="w-8 h-8 bg-indigo-100 rounded-lg flex items-center justify-center">
                                                <i className={`fas ${action.icon} text-indigo-600 text-sm`}></i>
                                            </div>
                                            <div className="flex-1 grid grid-cols-3 gap-2">
                                                <input type="text" value={action.label} onChange={(e) => updateQuickAction(i, 'label', e.target.value)} className="border border-gray-200 rounded px-2 py-1 text-sm" placeholder="Label" />
                                                <input type="text" value={action.icon} onChange={(e) => updateQuickAction(i, 'icon', e.target.value)} className="border border-gray-200 rounded px-2 py-1 text-sm" placeholder="fa-icon" />
                                                <input type="text" value={action.action} onChange={(e) => updateQuickAction(i, 'action', e.target.value)} className="border border-gray-200 rounded px-2 py-1 text-sm" placeholder="action" />
                                            </div>
                                        </div>
                                    ))}
                                </div>
                            </div>
                            <div className="flex justify-end">
                                <button onClick={handleSaveMobile} disabled={saving} className="px-6 py-2.5 bg-indigo-600 text-white rounded-lg text-sm font-medium hover:bg-indigo-700 disabled:opacity-50">
                                    {saving ? 'Saving...' : 'Save Quick Actions'}
                                </button>
                            </div>
                        </div>
                    )}

                    {/* PWA TAB */}
                    {activeTab === 'pwa' && (
                        <div className="space-y-6">
                            <div className="bg-white rounded-xl shadow-sm border p-6">
                                <h3 className="text-lg font-semibold text-gray-900 mb-4">Progressive Web App (PWA)</h3>
                                <p className="text-sm text-gray-500 mb-4">Configure how your app appears when installed on mobile devices</p>

                                <label className="flex items-center gap-3 mb-4 cursor-pointer">
                                    <input type="checkbox" checked={pwaConfig.pwa_enabled ?? true} onChange={(e) => setPwaConfig(prev => ({ ...prev, pwa_enabled: e.target.checked }))} className="w-4 h-4 text-indigo-600 rounded border-gray-300" />
                                    <span className="text-sm font-medium text-gray-700">Enable PWA (Add to Home Screen)</span>
                                </label>

                                <div className="grid grid-cols-1 md:grid-cols-2 gap-x-4">
                                    <FormField label="App Name" name="pwa_name" value={pwaConfig.pwa_name || ''} onChange={(e) => setPwaConfig(prev => ({ ...prev, pwa_name: e.target.value }))} placeholder="My Church App" />
                                    <FormField label="Short Name (max 12 chars)" name="pwa_short_name" value={pwaConfig.pwa_short_name || ''} onChange={(e) => setPwaConfig(prev => ({ ...prev, pwa_short_name: e.target.value }))} placeholder="Church" />
                                </div>
                                <FormField label="Description" name="pwa_description" type="textarea" rows={2} value={pwaConfig.pwa_description || ''} onChange={(e) => setPwaConfig(prev => ({ ...prev, pwa_description: e.target.value }))} placeholder="Your church community app" />

                                <div className="grid grid-cols-2 gap-4 mb-4">
                                    <div>
                                        <label className="block text-sm font-medium text-gray-700 mb-1">Theme Color</label>
                                        <div className="flex items-center gap-2">
                                            <input type="color" value={pwaConfig.pwa_theme_color || '#4F46E5'} onChange={(e) => setPwaConfig(prev => ({ ...prev, pwa_theme_color: e.target.value }))} className="h-8 w-10 rounded border cursor-pointer" />
                                            <input type="text" value={pwaConfig.pwa_theme_color || '#4F46E5'} onChange={(e) => setPwaConfig(prev => ({ ...prev, pwa_theme_color: e.target.value }))} className="flex-1 border border-gray-300 rounded px-2 py-1 text-sm" />
                                        </div>
                                    </div>
                                    <div>
                                        <label className="block text-sm font-medium text-gray-700 mb-1">Background Color</label>
                                        <div className="flex items-center gap-2">
                                            <input type="color" value={pwaConfig.pwa_background_color || '#ffffff'} onChange={(e) => setPwaConfig(prev => ({ ...prev, pwa_background_color: e.target.value }))} className="h-8 w-10 rounded border cursor-pointer" />
                                            <input type="text" value={pwaConfig.pwa_background_color || '#ffffff'} onChange={(e) => setPwaConfig(prev => ({ ...prev, pwa_background_color: e.target.value }))} className="flex-1 border border-gray-300 rounded px-2 py-1 text-sm" />
                                        </div>
                                    </div>
                                </div>

                                <div className="grid grid-cols-2 gap-4">
                                    <FormField label="Display Mode" name="pwa_display" type="select" value={pwaConfig.pwa_display || 'standalone'} onChange={(e) => setPwaConfig(prev => ({ ...prev, pwa_display: e.target.value }))} options={[
                                        { value: 'standalone', label: 'Standalone (App-like)' },
                                        { value: 'fullscreen', label: 'Fullscreen' },
                                        { value: 'minimal-ui', label: 'Minimal UI' },
                                        { value: 'browser', label: 'Browser' },
                                    ]} />
                                    <FormField label="Orientation" name="pwa_orientation" type="select" value={pwaConfig.pwa_orientation || 'any'} onChange={(e) => setPwaConfig(prev => ({ ...prev, pwa_orientation: e.target.value }))} options={[
                                        { value: 'any', label: 'Any' },
                                        { value: 'portrait', label: 'Portrait Only' },
                                        { value: 'landscape', label: 'Landscape Only' },
                                    ]} />
                                </div>
                            </div>

                            <div className="flex justify-end">
                                <button onClick={handleSavePwa} disabled={saving} className="px-6 py-2.5 bg-indigo-600 text-white rounded-lg text-sm font-medium hover:bg-indigo-700 disabled:opacity-50">
                                    {saving ? 'Saving...' : 'Save PWA Settings'}
                                </button>
                            </div>
                        </div>
                    )}
                </div>

                {/* Phone preview */}
                <div className="sticky top-4">
                    <div className="bg-white rounded-xl shadow-sm border p-6">
                        <h3 className="text-lg font-semibold text-gray-900 mb-4 text-center">
                            <i className="fas fa-eye text-indigo-600 mr-2"></i>
                            Mobile Preview
                        </h3>
                        <PhonePreview config={config} pwaConfig={pwaConfig} />
                    </div>
                </div>
            </div>
        </div>
    );
}
