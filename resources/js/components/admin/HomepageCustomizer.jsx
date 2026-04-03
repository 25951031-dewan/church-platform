import React, { useState, useEffect, useRef, useCallback } from 'react';
import { Alert } from '../shared/CrudPanel';
import { get, put } from '../shared/api';

const PHONE_FRAME_PAGES = {
    verse: { title: 'Verse of the Day', color: '#7c3aed', preview: '"For God so loved the world..." — John 3:16' },
    blessing: { title: "Today's Blessing", color: '#d97706', preview: 'May the Lord bless you and keep you...' },
    announcements: { title: 'Announcements', color: '#dc2626', preview: '📢 Sunday service at 10 AM • Wednesday Bible Study 7 PM' },
    posts: { title: 'Latest Blog Posts', color: '#2563eb', preview: '📰 Blog posts with grid/list/featured layouts' },
    prayers: { title: 'Prayer Requests', color: '#059669', preview: '🙏 Community prayer requests' },
    events: { title: 'Upcoming Events', color: '#7c3aed', preview: '📅 Next 3 upcoming events' },
    sermon: { title: 'Latest Sermon', color: '#be185d', preview: '🎙️ Most recent sermon' },
    testimonies: { title: 'Testimonies', color: '#b45309', preview: '✝ Shared testimonies of faith' },
    reviews: { title: 'Reviews', color: '#eab308', preview: '⭐ Church reviews & ratings' },
    ministries: { title: 'Ministries', color: '#0d9488', preview: '🤝 Active ministry groups' },
    galleries: { title: 'Photo Gallery', color: '#6366f1', preview: '📸 Recent photos' },
    newsletter: { title: 'Newsletter Signup', color: '#e11d48', preview: '💌 Subscribe to our newsletter' },
    contact: { title: 'Quick Contact', color: '#4f46e5', preview: '✉️ Contact form & info' },
};

function DragItem({ widget, index, onDragStart, onDragOver, onDrop, onToggle, onSettingChange, isDragging, dragOverIndex }) {
    const isOver = dragOverIndex === index;

    return (
        <div
            draggable
            onDragStart={(e) => onDragStart(e, index)}
            onDragOver={(e) => onDragOver(e, index)}
            onDrop={(e) => onDrop(e, index)}
            className={`group flex items-center gap-3 px-4 py-3 rounded-xl border-2 transition-all cursor-grab active:cursor-grabbing
                ${widget.enabled ? 'bg-white border-gray-200 hover:border-indigo-300 hover:shadow-md' : 'bg-gray-50 border-dashed border-gray-300 opacity-60'}
                ${isDragging ? 'opacity-40 scale-95' : ''}
                ${isOver ? 'border-indigo-500 bg-indigo-50 shadow-lg scale-[1.02]' : ''}
            `}
        >
            {/* Drag Handle */}
            <div className="flex-shrink-0 text-gray-400 group-hover:text-indigo-500 transition-colors">
                <svg className="w-5 h-5" fill="currentColor" viewBox="0 0 20 20">
                    <path d="M7 2a2 2 0 1 0 0 4 2 2 0 0 0 0-4zM13 2a2 2 0 1 0 0 4 2 2 0 0 0 0-4zM7 8a2 2 0 1 0 0 4 2 2 0 0 0 0-4zM13 8a2 2 0 1 0 0 4 2 2 0 0 0 0-4zM7 14a2 2 0 1 0 0 4 2 2 0 0 0 0-4zM13 14a2 2 0 1 0 0 4 2 2 0 0 0 0-4z"/>
                </svg>
            </div>

            {/* Icon */}
            <div className={`flex-shrink-0 w-9 h-9 rounded-lg flex items-center justify-center ${widget.enabled ? 'bg-indigo-100' : 'bg-gray-200'}`}>
                <i className={`fas ${widget.icon} ${widget.enabled ? 'text-indigo-600' : 'text-gray-400'}`}></i>
            </div>

            {/* Label + Settings */}
            <div className="flex-1 min-w-0">
                <div className="flex items-center gap-2">
                    <span className={`text-sm font-medium truncate ${widget.enabled ? 'text-gray-900' : 'text-gray-500'}`}>
                        {widget.label}
                    </span>
                    <span className="text-xs text-gray-400">#{index + 1}</span>
                </div>
                {widget.enabled && widget.settings && widget.settings.count !== undefined && (
                    <div className="flex items-center gap-2 mt-1 flex-wrap">
                        <label className="text-xs text-gray-500">Show:</label>
                        <select
                            value={widget.settings.count || 3}
                            onChange={(e) => onSettingChange(index, 'count', parseInt(e.target.value))}
                            className="text-xs border border-gray-200 rounded-md px-2 py-0.5 focus:ring-1 focus:ring-indigo-500 focus:border-indigo-500 outline-none"
                            onClick={(e) => e.stopPropagation()}
                        >
                            {[1,2,3,4,5,6,8,10,12].map(n => (
                                <option key={n} value={n}>{n} items</option>
                            ))}
                        </select>
                        {widget.id === 'posts' && (
                            <>
                                <label className="text-xs text-gray-500 ml-2">Layout:</label>
                                <select
                                    value={widget.settings.layout || 'grid'}
                                    onChange={(e) => onSettingChange(index, 'layout', e.target.value)}
                                    className="text-xs border border-gray-200 rounded-md px-2 py-0.5 focus:ring-1 focus:ring-indigo-500 focus:border-indigo-500 outline-none"
                                    onClick={(e) => e.stopPropagation()}
                                >
                                    <option value="grid">Grid</option>
                                    <option value="list">List</option>
                                    <option value="featured">Featured</option>
                                </select>
                            </>
                        )}
                    </div>
                )}
            </div>

            {/* Toggle */}
            <button
                onClick={() => onToggle(index)}
                className={`flex-shrink-0 relative inline-flex h-6 w-11 items-center rounded-full transition-colors focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2
                    ${widget.enabled ? 'bg-indigo-600' : 'bg-gray-300'}`}
            >
                <span className={`inline-block h-4 w-4 transform rounded-full bg-white transition-transform shadow-sm ${widget.enabled ? 'translate-x-6' : 'translate-x-1'}`} />
            </button>
        </div>
    );
}

function PhonePreview({ widgets }) {
    const enabled = widgets.filter(w => w.enabled);
    return (
        <div className="sticky top-8">
            <h3 className="text-sm font-semibold text-gray-700 mb-3 text-center">Live Preview</h3>
            <div className="mx-auto w-[280px] bg-gray-900 rounded-[2.5rem] p-3 shadow-2xl">
                {/* Phone notch */}
                <div className="bg-black w-24 h-5 rounded-full mx-auto mb-1"></div>
                {/* Screen */}
                <div className="bg-[#0C0E12] rounded-[1.5rem] overflow-hidden" style={{ height: '520px' }}>
                    {/* Nav bar */}
                    <div className="bg-[#14171E] px-4 py-2 flex items-center justify-between border-b border-gray-800">
                        <div className="flex items-center gap-2">
                            <div className="w-5 h-5 bg-indigo-600 rounded flex items-center justify-center text-white text-[8px]">✝</div>
                            <span className="text-white text-[10px] font-semibold">Church Name</span>
                        </div>
                        <div className="flex gap-2">
                            <div className="w-4 h-4 bg-gray-700 rounded-full"></div>
                        </div>
                    </div>
                    {/* Content */}
                    <div className="overflow-y-auto px-3 py-2 space-y-2" style={{ height: '470px' }}>
                        {enabled.length === 0 && (
                            <div className="text-center text-gray-500 text-[10px] py-12">
                                No widgets enabled.<br/>Toggle widgets on the left.
                            </div>
                        )}
                        {enabled.map((w) => {
                            const info = PHONE_FRAME_PAGES[w.id] || {};
                            return (
                                <div key={w.id} className="rounded-lg p-2.5 border border-gray-800"
                                     style={{ backgroundColor: 'rgba(26,30,40,0.8)' }}>
                                    <div className="flex items-center gap-1.5 mb-1">
                                        <div className="w-3 h-3 rounded flex items-center justify-center"
                                             style={{ backgroundColor: (info.color || '#6366f1') + '30' }}>
                                            <i className={`fas ${w.icon}`} style={{ fontSize: '6px', color: info.color || '#6366f1' }}></i>
                                        </div>
                                        <span className="text-[9px] font-semibold" style={{ color: info.color || '#a5b4fc' }}>
                                            {info.title || w.label}
                                        </span>
                                    </div>
                                    <p className="text-[8px] text-gray-400 leading-relaxed">
                                        {info.preview || w.label}
                                    </p>
                                </div>
                            );
                        })}
                    </div>
                </div>
            </div>
        </div>
    );
}

export default function HomepageCustomizer() {
    const [widgets, setWidgets] = useState([]);
    const [loading, setLoading] = useState(true);
    const [saving, setSaving] = useState(false);
    const [alert, setAlert] = useState(null);
    const [dragIndex, setDragIndex] = useState(null);
    const [dragOverIndex, setDragOverIndex] = useState(null);
    const [hasChanges, setHasChanges] = useState(false);

    useEffect(() => {
        fetchConfig();
    }, []);

    const fetchConfig = async () => {
        setLoading(true);
        try {
            const data = await get('/api/settings/widgets');
            setWidgets(data.data?.widgets || []);
        } catch (e) {
            setAlert({ type: 'error', message: 'Failed to load widget config: ' + e.message });
        }
        setLoading(false);
    };

    const handleSave = async () => {
        setSaving(true);
        setAlert(null);
        try {
            await put('/api/settings/widgets', { widgets });
            setAlert({ type: 'success', message: 'Homepage layout saved! Changes are live on your website.' });
            setHasChanges(false);
        } catch (e) {
            setAlert({ type: 'error', message: 'Failed to save: ' + e.message });
        }
        setSaving(false);
    };

    const handleReset = async () => {
        if (!confirm('Reset to default layout? This will undo all customizations.')) return;
        setLoading(true);
        try {
            const data = await get('/api/settings/widgets');
            const defaults = data.data?.available_widgets || [];
            setWidgets(defaults);
            setHasChanges(true);
        } catch (e) {
            setAlert({ type: 'error', message: 'Failed to load defaults.' });
        }
        setLoading(false);
    };

    const updateWidgets = (newWidgets) => {
        setWidgets(newWidgets);
        setHasChanges(true);
    };

    // Drag handlers
    const onDragStart = useCallback((e, index) => {
        setDragIndex(index);
        e.dataTransfer.effectAllowed = 'move';
        e.dataTransfer.setData('text/plain', index);
    }, []);

    const onDragOver = useCallback((e, index) => {
        e.preventDefault();
        e.dataTransfer.dropEffect = 'move';
        setDragOverIndex(index);
    }, []);

    const onDrop = useCallback((e, dropIndex) => {
        e.preventDefault();
        const fromIndex = dragIndex;
        if (fromIndex === null || fromIndex === dropIndex) {
            setDragIndex(null);
            setDragOverIndex(null);
            return;
        }
        const updated = [...widgets];
        const [moved] = updated.splice(fromIndex, 1);
        updated.splice(dropIndex, 0, moved);
        updateWidgets(updated);
        setDragIndex(null);
        setDragOverIndex(null);
    }, [dragIndex, widgets]);

    const onDragEnd = useCallback(() => {
        setDragIndex(null);
        setDragOverIndex(null);
    }, []);

    const handleToggle = (index) => {
        const updated = [...widgets];
        updated[index] = { ...updated[index], enabled: !updated[index].enabled };
        updateWidgets(updated);
    };

    const handleSettingChange = (index, key, value) => {
        const updated = [...widgets];
        updated[index] = {
            ...updated[index],
            settings: { ...updated[index].settings, [key]: value },
        };
        updateWidgets(updated);
    };

    const moveWidget = (index, direction) => {
        const newIndex = index + direction;
        if (newIndex < 0 || newIndex >= widgets.length) return;
        const updated = [...widgets];
        [updated[index], updated[newIndex]] = [updated[newIndex], updated[index]];
        updateWidgets(updated);
    };

    const enabledCount = widgets.filter(w => w.enabled).length;

    if (loading) {
        return (
            <div className="flex items-center justify-center py-20">
                <svg className="animate-spin h-8 w-8 text-indigo-600" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                    <circle className="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" strokeWidth="4"></circle>
                    <path className="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
                </svg>
                <span className="ml-3 text-gray-500 text-sm">Loading homepage customizer...</span>
            </div>
        );
    }

    return (
        <div>
            {/* Header */}
            <div className="flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4 mb-6">
                <div>
                    <h2 className="text-2xl font-bold text-gray-800">Homepage Customizer</h2>
                    <p className="text-sm text-gray-500 mt-1">
                        Drag and drop to reorder sections. Toggle widgets on/off. {enabledCount} of {widgets.length} active.
                    </p>
                </div>
                <div className="flex items-center gap-3">
                    <button
                        onClick={handleReset}
                        className="px-4 py-2 border border-gray-300 text-gray-700 rounded-lg text-sm font-medium hover:bg-gray-50"
                    >
                        Reset Default
                    </button>
                    <button
                        onClick={handleSave}
                        disabled={saving || !hasChanges}
                        className="px-5 py-2 bg-indigo-600 text-white rounded-lg text-sm font-medium hover:bg-indigo-700 disabled:opacity-50 disabled:cursor-not-allowed flex items-center gap-2"
                    >
                        {saving && (
                            <svg className="animate-spin h-4 w-4 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                <circle className="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" strokeWidth="4"></circle>
                                <path className="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
                            </svg>
                        )}
                        {saving ? 'Publishing...' : hasChanges ? 'Publish Changes' : 'Published'}
                    </button>
                </div>
            </div>

            {hasChanges && (
                <div className="mb-4 px-4 py-2.5 bg-amber-50 border border-amber-200 rounded-lg flex items-center gap-2 text-sm text-amber-800">
                    <i className="fas fa-exclamation-triangle text-amber-500"></i>
                    You have unsaved changes. Click "Publish Changes" to make them live.
                </div>
            )}

            <Alert {...alert} onClose={() => setAlert(null)} />

            {/* Main Layout: Widget List + Phone Preview */}
            <div className="grid grid-cols-1 lg:grid-cols-[1fr_320px] gap-8">
                {/* Widget List */}
                <div>
                    <div className="flex items-center justify-between mb-4">
                        <h3 className="text-sm font-semibold text-gray-700 uppercase tracking-wider">Homepage Sections</h3>
                        <span className="text-xs text-gray-500">{enabledCount} active</span>
                    </div>
                    <div className="space-y-2" onDragEnd={onDragEnd}>
                        {widgets.map((widget, index) => (
                            <DragItem
                                key={widget.id}
                                widget={widget}
                                index={index}
                                onDragStart={onDragStart}
                                onDragOver={onDragOver}
                                onDrop={onDrop}
                                onToggle={handleToggle}
                                onSettingChange={handleSettingChange}
                                isDragging={dragIndex === index}
                                dragOverIndex={dragOverIndex}
                            />
                        ))}
                    </div>

                    {/* Quick Actions */}
                    <div className="mt-6 grid grid-cols-2 gap-3">
                        <button
                            onClick={() => {
                                const updated = widgets.map(w => ({ ...w, enabled: true }));
                                updateWidgets(updated);
                            }}
                            className="px-4 py-2.5 bg-green-50 border border-green-200 text-green-700 rounded-lg text-sm font-medium hover:bg-green-100 transition-colors"
                        >
                            <i className="fas fa-toggle-on mr-2"></i> Enable All
                        </button>
                        <button
                            onClick={() => {
                                const updated = widgets.map(w => ({ ...w, enabled: false }));
                                updateWidgets(updated);
                            }}
                            className="px-4 py-2.5 bg-red-50 border border-red-200 text-red-700 rounded-lg text-sm font-medium hover:bg-red-100 transition-colors"
                        >
                            <i className="fas fa-toggle-off mr-2"></i> Disable All
                        </button>
                    </div>

                    {/* Help Text */}
                    <div className="mt-6 p-4 bg-blue-50 border border-blue-200 rounded-xl">
                        <h4 className="text-sm font-semibold text-blue-900 mb-2"><i className="fas fa-info-circle mr-1"></i> How it works</h4>
                        <ul className="text-xs text-blue-800 space-y-1">
                            <li><strong>Drag &amp; Drop</strong> — Grab the dots icon to reorder sections on your homepage.</li>
                            <li><strong>Toggle</strong> — Use the switch to show/hide sections. Disabled sections are hidden from visitors.</li>
                            <li><strong>Items Count</strong> — Some sections let you choose how many items to display.</li>
                            <li><strong>Publish</strong> — Click "Publish Changes" to make your layout live.</li>
                        </ul>
                    </div>
                </div>

                {/* Phone Preview */}
                <div className="hidden lg:block">
                    <PhonePreview widgets={widgets} />
                </div>
            </div>
        </div>
    );
}
