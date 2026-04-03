import React, { useState, useEffect } from 'react';
import { Modal, FormField, Alert } from '../shared/CrudPanel';
import { get, post, put, del } from '../shared/api';

const defaultLightColors = {
    primary: '#4F46E5',
    primary_light: '#818CF8',
    on_primary: '#FFFFFF',
    accent: '#7C3AED',
    background: '#FFFFFF',
    background_alt: '#F9FAFB',
    text_primary: '#111827',
    text_secondary: '#6B7280',
    border: '#E5E7EB',
    header_bg: '#FFFFFF',
    header_text: '#111827',
    footer_bg: '#1F2937',
    footer_text: '#D1D5DB',
};

const defaultDarkColors = {
    primary: '#818CF8',
    primary_light: '#A5B4FC',
    on_primary: '#FFFFFF',
    accent: '#A78BFA',
    background: '#0F172A',
    background_alt: '#1E293B',
    text_primary: '#F1F5F9',
    text_secondary: '#94A3B8',
    border: '#334155',
    header_bg: '#1E293B',
    header_text: '#F1F5F9',
    footer_bg: '#0F172A',
    footer_text: '#94A3B8',
};

function ColorPicker({ label, name, value, onChange }) {
    return (
        <div className="flex items-center gap-3 mb-3">
            <input
                type="color"
                value={value || '#000000'}
                onChange={(e) => onChange(name, e.target.value)}
                className="h-8 w-10 rounded border border-gray-300 cursor-pointer p-0.5"
            />
            <div className="flex-1">
                <label className="block text-xs font-medium text-gray-600">{label}</label>
                <input
                    type="text"
                    value={value || ''}
                    onChange={(e) => onChange(name, e.target.value)}
                    className="w-full text-xs border border-gray-200 rounded px-2 py-1 mt-0.5"
                    placeholder="#000000"
                />
            </div>
        </div>
    );
}

function ThemePreview({ colors, isDark }) {
    const c = colors || (isDark ? defaultDarkColors : defaultLightColors);
    return (
        <div className="rounded-lg overflow-hidden border shadow-sm" style={{ background: c.background, minHeight: '200px' }}>
            {/* Header */}
            <div className="px-4 py-2 flex items-center justify-between" style={{ background: c.header_bg, borderBottom: `1px solid ${c.border}` }}>
                <div className="flex items-center gap-2">
                    <div className="w-6 h-6 rounded" style={{ background: c.primary }}></div>
                    <span className="text-xs font-bold" style={{ color: c.header_text }}>Church Name</span>
                </div>
                <div className="flex gap-2">
                    {['Home', 'About', 'Sermons'].map(item => (
                        <span key={item} className="text-[10px]" style={{ color: c.text_secondary }}>{item}</span>
                    ))}
                </div>
            </div>
            {/* Content */}
            <div className="p-4">
                <h3 className="text-sm font-bold mb-1" style={{ color: c.text_primary }}>Welcome to Our Church</h3>
                <p className="text-[10px] mb-3" style={{ color: c.text_secondary }}>Join us for worship every Sunday</p>
                <div className="grid grid-cols-2 gap-2">
                    <div className="p-2 rounded" style={{ background: c.background_alt, border: `1px solid ${c.border}` }}>
                        <div className="w-full h-8 rounded mb-1" style={{ background: c.primary, opacity: 0.2 }}></div>
                        <span className="text-[9px] font-medium" style={{ color: c.text_primary }}>Latest Sermon</span>
                    </div>
                    <div className="p-2 rounded" style={{ background: c.background_alt, border: `1px solid ${c.border}` }}>
                        <div className="w-full h-8 rounded mb-1" style={{ background: c.accent, opacity: 0.2 }}></div>
                        <span className="text-[9px] font-medium" style={{ color: c.text_primary }}>Upcoming Event</span>
                    </div>
                </div>
                <button className="mt-3 text-[10px] px-3 py-1 rounded-full font-medium" style={{ background: c.primary, color: c.on_primary }}>
                    Join Us
                </button>
            </div>
            {/* Footer */}
            <div className="px-4 py-2 mt-2" style={{ background: c.footer_bg }}>
                <span className="text-[9px]" style={{ color: c.footer_text }}>2026 Church Platform</span>
            </div>
        </div>
    );
}

export default function AppearanceManager() {
    const [activeTab, setActiveTab] = useState('themes');
    const [themes, setThemes] = useState([]);
    const [defaultTheme, setDefaultTheme] = useState('light');
    const [customCss, setCustomCss] = useState('');
    const [customJs, setCustomJs] = useState('');
    const [alert, setAlert] = useState(null);
    const [loading, setLoading] = useState(true);
    const [saving, setSaving] = useState(false);

    // Theme editor
    const [editingTheme, setEditingTheme] = useState(null);
    const [themeForm, setThemeForm] = useState({
        name: '',
        is_dark: false,
        is_default: false,
        colors: { ...defaultLightColors },
        custom_css: '',
    });

    useEffect(() => { fetchData(); }, []);

    const fetchData = async () => {
        setLoading(true);
        try {
            const data = await get('/api/appearance');
            const d = data.data || data;
            setThemes(d.themes || []);
            setDefaultTheme(d.default_theme || 'light');
            setCustomCss(d.custom_css || '');
            setCustomJs(d.custom_js || '');
        } catch (e) {
            setAlert({ type: 'error', message: 'Failed to load appearance settings.' });
        }
        setLoading(false);
    };

    const handleColorChange = (name, value) => {
        setThemeForm(prev => ({
            ...prev,
            colors: { ...prev.colors, [name]: value },
        }));
    };

    const handleSaveTheme = async () => {
        setSaving(true);
        try {
            const url = editingTheme ? `/api/appearance/themes/${editingTheme}` : '/api/appearance/themes';
            const method = editingTheme ? put : post;
            await method(url, themeForm);
            setAlert({ type: 'success', message: 'Theme saved!' });
            fetchData();
            setEditingTheme(null);
            resetThemeForm();
        } catch (e) {
            setAlert({ type: 'error', message: 'Failed to save theme: ' + e.message });
        }
        setSaving(false);
    };

    const handleDeleteTheme = async (id) => {
        if (!confirm('Delete this theme?')) return;
        try {
            await del(`/api/appearance/themes/${id}`);
            fetchData();
            setAlert({ type: 'success', message: 'Theme deleted.' });
        } catch (e) {
            setAlert({ type: 'error', message: 'Failed: ' + e.message });
        }
    };

    const handleEditTheme = (theme) => {
        setEditingTheme(theme.id);
        setThemeForm({
            name: theme.name,
            is_dark: theme.is_dark,
            is_default: theme.is_default,
            colors: theme.colors || defaultLightColors,
            custom_css: theme.custom_css || '',
        });
        setActiveTab('editor');
    };

    const resetThemeForm = () => {
        setThemeForm({
            name: '',
            is_dark: false,
            is_default: false,
            colors: { ...defaultLightColors },
            custom_css: '',
        });
    };

    const handleSaveGlobal = async () => {
        setSaving(true);
        try {
            await put('/api/appearance', {
                default_theme: defaultTheme,
                custom_css: customCss,
                custom_js: customJs,
            });
            setAlert({ type: 'success', message: 'Global appearance settings saved!' });
        } catch (e) {
            setAlert({ type: 'error', message: 'Failed: ' + e.message });
        }
        setSaving(false);
    };

    const tabs = [
        { id: 'themes', label: 'Themes', icon: 'fa-palette' },
        { id: 'editor', label: 'Theme Editor', icon: 'fa-paint-brush' },
        { id: 'custom_code', label: 'Custom CSS/JS', icon: 'fa-code' },
    ];

    if (loading) {
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
                    <i className="fas fa-palette text-indigo-600 mr-2"></i>
                    Appearance Editor
                </h2>
            </div>

            <Alert {...alert} onClose={() => setAlert(null)} />

            {/* Tabs */}
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

            {/* THEMES TAB */}
            {activeTab === 'themes' && (
                <div>
                    {/* Default theme selector */}
                    <div className="bg-white rounded-xl shadow-sm border p-6 mb-6">
                        <h3 className="text-lg font-semibold text-gray-900 mb-4">Default Theme</h3>
                        <div className="flex gap-4">
                            {['light', 'dark'].map(mode => (
                                <button
                                    key={mode}
                                    onClick={() => setDefaultTheme(mode)}
                                    className={`flex-1 p-4 rounded-lg border-2 transition-all ${
                                        defaultTheme === mode
                                            ? 'border-indigo-500 bg-indigo-50'
                                            : 'border-gray-200 hover:border-gray-300'
                                    }`}
                                >
                                    <div className={`w-full h-20 rounded-lg mb-2 ${mode === 'dark' ? 'bg-gray-800' : 'bg-gray-100'}`}>
                                        <div className="p-2">
                                            <div className={`h-2 w-12 rounded ${mode === 'dark' ? 'bg-indigo-400' : 'bg-indigo-600'} mb-1`}></div>
                                            <div className={`h-1.5 w-20 rounded ${mode === 'dark' ? 'bg-gray-600' : 'bg-gray-300'}`}></div>
                                        </div>
                                    </div>
                                    <span className="text-sm font-medium capitalize">{mode} Mode</span>
                                </button>
                            ))}
                        </div>
                        <div className="mt-4 flex justify-end">
                            <button onClick={handleSaveGlobal} disabled={saving} className="px-4 py-2 bg-indigo-600 text-white rounded-lg text-sm font-medium hover:bg-indigo-700 disabled:opacity-50">
                                {saving ? 'Saving...' : 'Save Default Theme'}
                            </button>
                        </div>
                    </div>

                    {/* Custom themes list */}
                    <div className="bg-white rounded-xl shadow-sm border p-6 mb-6">
                        <div className="flex items-center justify-between mb-4">
                            <h3 className="text-lg font-semibold text-gray-900">Custom Themes</h3>
                            <button
                                onClick={() => { resetThemeForm(); setEditingTheme(null); setActiveTab('editor'); }}
                                className="px-3 py-1.5 bg-indigo-600 text-white rounded-lg text-sm font-medium hover:bg-indigo-700 flex items-center gap-2"
                            >
                                <i className="fas fa-plus text-xs"></i> New Theme
                            </button>
                        </div>
                        {themes.length === 0 ? (
                            <p className="text-gray-500 text-sm text-center py-8">No custom themes yet. Create one to customize your site's look!</p>
                        ) : (
                            <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                                {themes.map(theme => (
                                    <div key={theme.id} className="border rounded-lg overflow-hidden">
                                        <ThemePreview colors={theme.colors} isDark={theme.is_dark} />
                                        <div className="p-3 bg-white border-t flex items-center justify-between">
                                            <div>
                                                <span className="text-sm font-medium text-gray-900">{theme.name}</span>
                                                <div className="flex gap-1 mt-1">
                                                    {theme.is_dark && <span className="text-[10px] bg-gray-800 text-white px-1.5 py-0.5 rounded">Dark</span>}
                                                    {theme.is_default && <span className="text-[10px] bg-indigo-100 text-indigo-700 px-1.5 py-0.5 rounded">Default</span>}
                                                </div>
                                            </div>
                                            <div className="flex gap-1">
                                                <button onClick={() => handleEditTheme(theme)} className="p-1.5 text-gray-400 hover:text-indigo-600">
                                                    <i className="fas fa-edit text-xs"></i>
                                                </button>
                                                <button onClick={() => handleDeleteTheme(theme.id)} className="p-1.5 text-gray-400 hover:text-red-600">
                                                    <i className="fas fa-trash text-xs"></i>
                                                </button>
                                            </div>
                                        </div>
                                    </div>
                                ))}
                            </div>
                        )}
                    </div>
                </div>
            )}

            {/* THEME EDITOR TAB */}
            {activeTab === 'editor' && (
                <div className="grid grid-cols-1 lg:grid-cols-2 gap-6">
                    {/* Editor panel */}
                    <div className="bg-white rounded-xl shadow-sm border p-6">
                        <h3 className="text-lg font-semibold text-gray-900 mb-4">
                            {editingTheme ? 'Edit Theme' : 'Create New Theme'}
                        </h3>

                        <FormField label="Theme Name" name="name" value={themeForm.name} onChange={(e) => setThemeForm(prev => ({ ...prev, name: e.target.value }))} placeholder="My Custom Theme" />

                        <div className="flex gap-4 mb-4">
                            <label className="flex items-center gap-2 cursor-pointer">
                                <input type="checkbox" checked={themeForm.is_dark} onChange={(e) => {
                                    const isDark = e.target.checked;
                                    setThemeForm(prev => ({
                                        ...prev,
                                        is_dark: isDark,
                                        colors: isDark ? { ...defaultDarkColors } : { ...defaultLightColors },
                                    }));
                                }} className="w-4 h-4 text-indigo-600 rounded border-gray-300" />
                                <span className="text-sm text-gray-700">Dark Theme</span>
                            </label>
                            <label className="flex items-center gap-2 cursor-pointer">
                                <input type="checkbox" checked={themeForm.is_default} onChange={(e) => setThemeForm(prev => ({ ...prev, is_default: e.target.checked }))} className="w-4 h-4 text-indigo-600 rounded border-gray-300" />
                                <span className="text-sm text-gray-700">Set as Default</span>
                            </label>
                        </div>

                        <h4 className="text-sm font-semibold text-gray-700 mb-3 mt-4">Brand Colors</h4>
                        <div className="grid grid-cols-2 gap-x-4">
                            <ColorPicker label="Primary" name="primary" value={themeForm.colors.primary} onChange={handleColorChange} />
                            <ColorPicker label="Primary Light" name="primary_light" value={themeForm.colors.primary_light} onChange={handleColorChange} />
                            <ColorPicker label="On Primary (text)" name="on_primary" value={themeForm.colors.on_primary} onChange={handleColorChange} />
                            <ColorPicker label="Accent" name="accent" value={themeForm.colors.accent} onChange={handleColorChange} />
                        </div>

                        <h4 className="text-sm font-semibold text-gray-700 mb-3 mt-4">Page Colors</h4>
                        <div className="grid grid-cols-2 gap-x-4">
                            <ColorPicker label="Background" name="background" value={themeForm.colors.background} onChange={handleColorChange} />
                            <ColorPicker label="Background Alt" name="background_alt" value={themeForm.colors.background_alt} onChange={handleColorChange} />
                            <ColorPicker label="Text Primary" name="text_primary" value={themeForm.colors.text_primary} onChange={handleColorChange} />
                            <ColorPicker label="Text Secondary" name="text_secondary" value={themeForm.colors.text_secondary} onChange={handleColorChange} />
                            <ColorPicker label="Border" name="border" value={themeForm.colors.border} onChange={handleColorChange} />
                        </div>

                        <h4 className="text-sm font-semibold text-gray-700 mb-3 mt-4">Header & Footer</h4>
                        <div className="grid grid-cols-2 gap-x-4">
                            <ColorPicker label="Header BG" name="header_bg" value={themeForm.colors.header_bg} onChange={handleColorChange} />
                            <ColorPicker label="Header Text" name="header_text" value={themeForm.colors.header_text} onChange={handleColorChange} />
                            <ColorPicker label="Footer BG" name="footer_bg" value={themeForm.colors.footer_bg} onChange={handleColorChange} />
                            <ColorPicker label="Footer Text" name="footer_text" value={themeForm.colors.footer_text} onChange={handleColorChange} />
                        </div>

                        <div className="mt-4">
                            <label className="block text-sm font-medium text-gray-700 mb-1">Theme Custom CSS</label>
                            <textarea
                                value={themeForm.custom_css}
                                onChange={(e) => setThemeForm(prev => ({ ...prev, custom_css: e.target.value }))}
                                className="w-full border border-gray-300 rounded-lg p-3 text-xs font-mono"
                                rows={4}
                                placeholder="/* Custom CSS for this theme */"
                            />
                        </div>

                        <div className="flex gap-3 mt-6">
                            <button onClick={handleSaveTheme} disabled={saving} className="px-5 py-2 bg-indigo-600 text-white rounded-lg text-sm font-medium hover:bg-indigo-700 disabled:opacity-50">
                                {saving ? 'Saving...' : (editingTheme ? 'Update Theme' : 'Create Theme')}
                            </button>
                            {editingTheme && (
                                <button onClick={() => { setEditingTheme(null); resetThemeForm(); }} className="px-5 py-2 bg-gray-200 text-gray-700 rounded-lg text-sm font-medium hover:bg-gray-300">
                                    Cancel
                                </button>
                            )}
                        </div>
                    </div>

                    {/* Live preview */}
                    <div>
                        <div className="bg-white rounded-xl shadow-sm border p-6 sticky top-4">
                            <h3 className="text-lg font-semibold text-gray-900 mb-4">
                                <i className="fas fa-eye text-indigo-600 mr-2"></i>
                                Live Preview
                            </h3>
                            <ThemePreview colors={themeForm.colors} isDark={themeForm.is_dark} />

                            {/* Color palette display */}
                            <div className="mt-4">
                                <h4 className="text-xs font-semibold text-gray-500 uppercase mb-2">Color Palette</h4>
                                <div className="flex gap-1">
                                    {Object.values(themeForm.colors).filter(Boolean).map((color, i) => (
                                        <div key={i} className="w-6 h-6 rounded-full border border-gray-200" style={{ background: color }} title={color}></div>
                                    ))}
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            )}

            {/* CUSTOM CSS/JS TAB */}
            {activeTab === 'custom_code' && (
                <div className="space-y-6">
                    <div className="bg-white rounded-xl shadow-sm border p-6">
                        <h3 className="text-lg font-semibold text-gray-900 mb-2">Custom CSS</h3>
                        <p className="text-sm text-gray-500 mb-4">Add custom CSS that will be injected into every page. Be careful with what you add here.</p>
                        <textarea
                            value={customCss}
                            onChange={(e) => setCustomCss(e.target.value)}
                            className="w-full border border-gray-300 rounded-lg p-4 font-mono text-sm"
                            rows={12}
                            placeholder="/* Your custom CSS */&#10;.my-class { color: red; }"
                        />
                    </div>

                    <div className="bg-white rounded-xl shadow-sm border p-6">
                        <h3 className="text-lg font-semibold text-gray-900 mb-2">Custom JavaScript</h3>
                        <p className="text-sm text-gray-500 mb-4">Add custom JS that will be injected into every page. Use with caution.</p>
                        <textarea
                            value={customJs}
                            onChange={(e) => setCustomJs(e.target.value)}
                            className="w-full border border-gray-300 rounded-lg p-4 font-mono text-sm"
                            rows={8}
                            placeholder="// Your custom JavaScript"
                        />
                    </div>

                    <div className="flex justify-end">
                        <button onClick={handleSaveGlobal} disabled={saving} className="px-6 py-2.5 bg-indigo-600 text-white rounded-lg text-sm font-medium hover:bg-indigo-700 disabled:opacity-50">
                            {saving ? 'Saving...' : 'Save Custom Code'}
                        </button>
                    </div>
                </div>
            )}
        </div>
    );
}
