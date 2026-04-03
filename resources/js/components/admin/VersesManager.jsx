import React, { useState, useEffect, useRef } from 'react';
import { Modal, DataTable, Pagination, FormField, Alert } from '../shared/CrudPanel';
import { get, post, put, del, upload } from '../shared/api';

export default function VersesManager() {
    const [items, setItems] = useState([]);
    const [meta, setMeta] = useState(null);
    const [showModal, setShowModal] = useState(false);
    const [showImportModal, setShowImportModal] = useState(false);
    const [editing, setEditing] = useState(null);
    const [form, setForm] = useState({ reference: '', verse_text: '', display_date: '', translation: '' });
    const [alert, setAlert] = useState(null);
    const [loading, setLoading] = useState(false);
    const [importing, setImporting] = useState(false);
    const [importResult, setImportResult] = useState(null);
    const fileInputRef = useRef(null);

    const fetchItems = async (page = 1) => {
        setLoading(true);
        try {
            const data = await get(`/api/verses?page=${page}`);
            setItems(data.data || []);
            setMeta(data);
        } catch (e) { setAlert({ type: 'error', message: e.message }); }
        setLoading(false);
    };

    useEffect(() => { fetchItems(); }, []);

    const handleChange = (e) => setForm({ ...form, [e.target.name]: e.target.value });

    const openCreate = () => { setEditing(null); setForm({ reference: '', verse_text: '', display_date: '', translation: '' }); setShowModal(true); };
    const openEdit = (item) => { setEditing(item); setForm({ reference: item.reference, verse_text: item.verse_text, display_date: item.display_date, translation: item.translation || '' }); setShowModal(true); };

    const handleSubmit = async (e) => {
        e.preventDefault();
        try {
            if (editing) { await put(`/api/verses/${editing.id}`, form); setAlert({ type: 'success', message: 'Verse updated.' }); }
            else { await post('/api/verses', form); setAlert({ type: 'success', message: 'Verse created.' }); }
            setShowModal(false); fetchItems();
        } catch (e) { setAlert({ type: 'error', message: e.message }); }
    };

    const handleDelete = async (item) => {
        if (!confirm('Delete this verse?')) return;
        try { await del(`/api/verses/${item.id}`); setAlert({ type: 'success', message: 'Verse deleted.' }); fetchItems(); }
        catch (e) { setAlert({ type: 'error', message: e.message }); }
    };

    const handleImportCsv = async (e) => {
        const file = e.target.files[0];
        if (!file) return;

        setImporting(true);
        setImportResult(null);

        const formData = new FormData();
        formData.append('file', file);

        try {
            const result = await upload('/api/verses/import-csv', formData);
            setImportResult(result);
            setAlert({ type: 'success', message: result.message });
            fetchItems();
        } catch (err) {
            setAlert({ type: 'error', message: err.message });
        }

        setImporting(false);
        if (fileInputRef.current) fileInputRef.current.value = '';
    };

    const columns = [
        { key: 'reference', label: 'Reference' },
        { key: 'verse_text', label: 'Text', render: (r) => (r.verse_text || '').substring(0, 60) + '...' },
        { key: 'display_date', label: 'Date' },
        { key: 'translation', label: 'Translation' },
    ];

    return (
        <div>
            <div className="flex items-center justify-between mb-6">
                <h2 className="text-2xl font-bold text-gray-800">Verses of the Day</h2>
                <div className="flex gap-2">
                    <a
                        href="/api/verses/sample-csv"
                        download
                        className="px-4 py-2 border border-gray-300 text-gray-700 rounded-lg text-sm font-medium hover:bg-gray-50"
                    >
                        Sample CSV
                    </a>
                    <button
                        onClick={() => { setImportResult(null); setShowImportModal(true); }}
                        className="px-4 py-2 bg-green-600 text-white rounded-lg text-sm font-medium hover:bg-green-700"
                    >
                        Import CSV
                    </button>
                    <button onClick={openCreate} className="px-4 py-2 bg-indigo-600 text-white rounded-lg text-sm font-medium hover:bg-indigo-700">+ Add Verse</button>
                </div>
            </div>
            <Alert {...alert} onClose={() => setAlert(null)} />
            <div className="bg-white rounded-xl shadow-sm border">
                <DataTable columns={columns} data={items} actions={(row) => (
                    <div className="flex gap-2">
                        <button onClick={() => openEdit(row)} className="text-indigo-600 hover:text-indigo-800 text-sm font-medium">Edit</button>
                        <button onClick={() => handleDelete(row)} className="text-red-600 hover:text-red-800 text-sm font-medium">Delete</button>
                    </div>
                )} />
                <Pagination meta={meta} onPageChange={fetchItems} />
            </div>
            <Modal isOpen={showModal} onClose={() => setShowModal(false)} title={editing ? 'Edit Verse' : 'Add Verse'}>
                <form onSubmit={handleSubmit} className="space-y-4">
                    <FormField label="Reference" name="reference" value={form.reference} onChange={handleChange} required placeholder="John 3:16" />
                    <FormField label="Text" name="verse_text" type="textarea" value={form.verse_text} onChange={handleChange} required />
                    <FormField label="Date" name="display_date" type="date" value={form.display_date} onChange={handleChange} required />
                    <FormField label="Translation" name="translation" value={form.translation} onChange={handleChange} placeholder="NIV, KJV, etc." />
                    <div className="flex justify-end gap-3 pt-2">
                        <button type="button" onClick={() => setShowModal(false)} className="px-4 py-2 border border-gray-300 text-gray-700 rounded-lg hover:bg-gray-50">Cancel</button>
                        <button type="submit" className="px-4 py-2 bg-indigo-600 text-white rounded-lg hover:bg-indigo-700">Save</button>
                    </div>
                </form>
            </Modal>
            <Modal isOpen={showImportModal} onClose={() => setShowImportModal(false)} title="Import Verses from CSV">
                <div className="space-y-4">
                    <div className="bg-blue-50 border border-blue-200 rounded-lg p-4 text-sm text-blue-800">
                        <p className="font-medium mb-1">CSV Format</p>
                        <p>Your CSV file must include these columns: <strong>reference</strong>, <strong>verse_text</strong>, <strong>display_date</strong></p>
                        <p className="mt-1">Optional column: <strong>translation</strong> (defaults to KJV)</p>
                        <a href="/api/verses/sample-csv" download className="inline-block mt-2 text-blue-600 underline hover:text-blue-800">
                            Download sample CSV template
                        </a>
                    </div>
                    <div>
                        <label className="block text-sm font-medium text-gray-700 mb-2">Select CSV File</label>
                        <input
                            ref={fileInputRef}
                            type="file"
                            accept=".csv"
                            onChange={handleImportCsv}
                            disabled={importing}
                            className="block w-full text-sm text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded-lg file:border-0 file:text-sm file:font-medium file:bg-indigo-50 file:text-indigo-700 hover:file:bg-indigo-100"
                        />
                    </div>
                    {importing && (
                        <div className="flex items-center gap-2 text-sm text-gray-600">
                            <svg className="animate-spin h-4 w-4" viewBox="0 0 24 24"><circle className="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" strokeWidth="4" fill="none" /><path className="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z" /></svg>
                            Importing...
                        </div>
                    )}
                    {importResult && (
                        <div className="bg-gray-50 border rounded-lg p-4 text-sm">
                            <p className="font-medium text-gray-800">{importResult.message}</p>
                            {importResult.errors && importResult.errors.length > 0 && (
                                <div className="mt-2">
                                    <p className="font-medium text-red-600 mb-1">Issues:</p>
                                    <ul className="list-disc list-inside text-red-600 space-y-1">
                                        {importResult.errors.map((err, i) => (
                                            <li key={i}>{err}</li>
                                        ))}
                                    </ul>
                                </div>
                            )}
                        </div>
                    )}
                    <div className="flex justify-end pt-2">
                        <button type="button" onClick={() => setShowImportModal(false)} className="px-4 py-2 border border-gray-300 text-gray-700 rounded-lg hover:bg-gray-50">Close</button>
                    </div>
                </div>
            </Modal>
        </div>
    );
}
