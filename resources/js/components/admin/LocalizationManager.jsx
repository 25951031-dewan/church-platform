import React, { useState, useEffect } from 'react';
import { Modal, FormField, Alert } from '../shared/CrudPanel';
import { get, post, put, del } from '../shared/api';

const languageOptions = [
    { value: 'en', label: 'English' },
    { value: 'es', label: 'Spanish (Espanol)' },
    { value: 'fr', label: 'French (Francais)' },
    { value: 'de', label: 'German (Deutsch)' },
    { value: 'pt', label: 'Portuguese (Portugues)' },
    { value: 'it', label: 'Italian (Italiano)' },
    { value: 'ko', label: 'Korean' },
    { value: 'zh', label: 'Chinese (Simplified)' },
    { value: 'ja', label: 'Japanese' },
    { value: 'ar', label: 'Arabic' },
    { value: 'hi', label: 'Hindi' },
    { value: 'sw', label: 'Swahili' },
    { value: 'tl', label: 'Tagalog (Filipino)' },
    { value: 'vi', label: 'Vietnamese' },
    { value: 'ru', label: 'Russian' },
    { value: 'pl', label: 'Polish' },
    { value: 'nl', label: 'Dutch (Nederlands)' },
];

export default function LocalizationManager() {
    const [localizations, setLocalizations] = useState([]);
    const [selectedId, setSelectedId] = useState(null);
    const [lines, setLines] = useState({});
    const [searchFilter, setSearchFilter] = useState('');
    const [showAddModal, setShowAddModal] = useState(false);
    const [newLang, setNewLang] = useState({ name: '', language: '' });
    const [loading, setLoading] = useState(true);
    const [saving, setSaving] = useState(false);
    const [alert, setAlert] = useState(null);

    useEffect(() => { fetchList(); }, []);

    const fetchList = async () => {
        setLoading(true);
        try {
            const data = await get('/api/localizations');
            setLocalizations(data.data || []);
        } catch (e) {
            setAlert({ type: 'error', message: 'Failed to load localizations.' });
        }
        setLoading(false);
    };

    const fetchLocalization = async (id) => {
        try {
            const data = await get(`/api/localizations/${id}`);
            const d = data.data || data;
            setLines(d.lines || {});
            setSelectedId(id);
        } catch (e) {
            setAlert({ type: 'error', message: 'Failed to load translation.' });
        }
    };

    const handleCreate = async () => {
        try {
            await post('/api/localizations', newLang);
            setAlert({ type: 'success', message: 'Language added!' });
            setShowAddModal(false);
            setNewLang({ name: '', language: '' });
            fetchList();
        } catch (e) {
            setAlert({ type: 'error', message: 'Failed: ' + e.message });
        }
    };

    const handleSave = async () => {
        if (!selectedId) return;
        setSaving(true);
        try {
            await put(`/api/localizations/${selectedId}`, { lines });
            setAlert({ type: 'success', message: 'Translations saved!' });
        } catch (e) {
            setAlert({ type: 'error', message: 'Failed: ' + e.message });
        }
        setSaving(false);
    };

    const handleDelete = async (id) => {
        if (!confirm('Delete this localization?')) return;
        try {
            await del(`/api/localizations/${id}`);
            if (selectedId === id) {
                setSelectedId(null);
                setLines({});
            }
            fetchList();
            setAlert({ type: 'success', message: 'Localization deleted.' });
        } catch (e) {
            setAlert({ type: 'error', message: 'Failed: ' + e.message });
        }
    };

    const filteredLines = Object.entries(lines).filter(([key, value]) =>
        key.toLowerCase().includes(searchFilter.toLowerCase()) ||
        value.toLowerCase().includes(searchFilter.toLowerCase())
    );

    // Group lines by prefix
    const groupedLines = {};
    filteredLines.forEach(([key, value]) => {
        const group = key.split('.')[0] || 'other';
        if (!groupedLines[group]) groupedLines[group] = [];
        groupedLines[group].push([key, value]);
    });

    const selectedLang = localizations.find(l => l.id === selectedId);

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
                    <i className="fas fa-language text-indigo-600 mr-2"></i>
                    Translations
                </h2>
                <button
                    onClick={() => setShowAddModal(true)}
                    className="px-4 py-2 bg-indigo-600 text-white rounded-lg text-sm font-medium hover:bg-indigo-700 flex items-center gap-2"
                >
                    <i className="fas fa-plus text-xs"></i> Add Language
                </button>
            </div>

            <Alert {...alert} onClose={() => setAlert(null)} />

            <div className="grid grid-cols-1 lg:grid-cols-4 gap-6">
                {/* Language list */}
                <div className="bg-white rounded-xl shadow-sm border p-4">
                    <h3 className="text-sm font-semibold text-gray-700 mb-3">Languages</h3>
                    {localizations.length === 0 ? (
                        <p className="text-sm text-gray-500 text-center py-4">No languages added yet.</p>
                    ) : (
                        <div className="space-y-1">
                            {localizations.map(loc => (
                                <div
                                    key={loc.id}
                                    className={`flex items-center justify-between p-2 rounded-lg cursor-pointer transition-colors ${
                                        selectedId === loc.id ? 'bg-indigo-50 border border-indigo-200' : 'hover:bg-gray-50'
                                    }`}
                                >
                                    <button onClick={() => fetchLocalization(loc.id)} className="flex items-center gap-2 flex-1 text-left">
                                        <span className="text-xs font-bold uppercase bg-gray-200 text-gray-700 px-1.5 py-0.5 rounded">{loc.language}</span>
                                        <span className="text-sm text-gray-800">{loc.name}</span>
                                    </button>
                                    {loc.language !== 'en' && (
                                        <button onClick={() => handleDelete(loc.id)} className="p-1 text-gray-400 hover:text-red-600">
                                            <i className="fas fa-trash text-xs"></i>
                                        </button>
                                    )}
                                </div>
                            ))}
                        </div>
                    )}
                </div>

                {/* Translation editor */}
                <div className="lg:col-span-3">
                    {!selectedId ? (
                        <div className="bg-white rounded-xl shadow-sm border p-12 text-center">
                            <i className="fas fa-language text-gray-300 text-5xl mb-4"></i>
                            <p className="text-gray-500">Select a language to edit translations</p>
                        </div>
                    ) : (
                        <div className="bg-white rounded-xl shadow-sm border">
                            <div className="p-4 border-b flex items-center justify-between">
                                <div>
                                    <h3 className="text-lg font-semibold text-gray-900">{selectedLang?.name} ({selectedLang?.language})</h3>
                                    <p className="text-sm text-gray-500">{Object.keys(lines).length} translation keys</p>
                                </div>
                                <button onClick={handleSave} disabled={saving} className="px-4 py-2 bg-indigo-600 text-white rounded-lg text-sm font-medium hover:bg-indigo-700 disabled:opacity-50">
                                    {saving ? 'Saving...' : 'Save Translations'}
                                </button>
                            </div>

                            <div className="p-4 border-b">
                                <input
                                    type="text"
                                    value={searchFilter}
                                    onChange={(e) => setSearchFilter(e.target.value)}
                                    placeholder="Search translations..."
                                    className="w-full border border-gray-300 rounded-lg px-4 py-2 text-sm focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 outline-none"
                                />
                            </div>

                            <div className="max-h-[600px] overflow-y-auto">
                                {Object.entries(groupedLines).map(([group, entries]) => (
                                    <div key={group} className="border-b last:border-b-0">
                                        <div className="px-4 py-2 bg-gray-50">
                                            <span className="text-xs font-semibold text-gray-500 uppercase">{group}</span>
                                        </div>
                                        {entries.map(([key, value]) => (
                                            <div key={key} className="flex items-center gap-4 px-4 py-2 hover:bg-gray-50">
                                                <span className="text-xs text-gray-500 font-mono w-48 flex-shrink-0 truncate" title={key}>{key}</span>
                                                <input
                                                    type="text"
                                                    value={value}
                                                    onChange={(e) => setLines(prev => ({ ...prev, [key]: e.target.value }))}
                                                    className="flex-1 border border-gray-200 rounded px-3 py-1.5 text-sm focus:ring-1 focus:ring-indigo-500 focus:border-indigo-500 outline-none"
                                                />
                                            </div>
                                        ))}
                                    </div>
                                ))}
                            </div>
                        </div>
                    )}
                </div>
            </div>

            {/* Add Language Modal */}
            {showAddModal && (
                <Modal title="Add Language" onClose={() => setShowAddModal(false)}>
                    <FormField label="Language Name" name="name" value={newLang.name} onChange={(e) => setNewLang(prev => ({ ...prev, name: e.target.value }))} placeholder="e.g., Spanish" />
                    <FormField label="Language Code" name="language" type="select" value={newLang.language} onChange={(e) => {
                        const code = e.target.value;
                        const opt = languageOptions.find(l => l.value === code);
                        setNewLang({ language: code, name: opt ? opt.label : newLang.name });
                    }} options={languageOptions} />
                    <div className="flex justify-end gap-3 mt-4">
                        <button onClick={() => setShowAddModal(false)} className="px-4 py-2 bg-gray-200 text-gray-700 rounded-lg text-sm font-medium hover:bg-gray-300">Cancel</button>
                        <button onClick={handleCreate} className="px-4 py-2 bg-indigo-600 text-white rounded-lg text-sm font-medium hover:bg-indigo-700">Add Language</button>
                    </div>
                </Modal>
            )}
        </div>
    );
}
