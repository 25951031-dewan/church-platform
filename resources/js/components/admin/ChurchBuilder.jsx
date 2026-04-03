import React, { useState, useEffect } from 'react';
import { get, upload, del, patch } from '../shared/api';
import { Alert } from '../shared/CrudPanel';

const TABS = [
    { id: 'general', label: 'General Settings', icon: 'fas fa-cog' },
    { id: 'about', label: 'About & History', icon: 'fas fa-book' },
    { id: 'appearance', label: 'Appearance', icon: 'fas fa-palette' },
    { id: 'seo', label: 'SEO & Social', icon: 'fas fa-search' },
];

export default function ChurchBuilder() {
    const [church, setChurch] = useState(null);
    const [loading, setLoading] = useState(true);
    const [saving, setSaving] = useState(false);
    const [activeTab, setActiveTab] = useState('general');
    const [alert, setAlert] = useState(null);
    const [form, setForm] = useState({});
    const [logoPreview, setLogoPreview] = useState(null);
    const [coverPreview, setCoverPreview] = useState(null);
    const [logoFile, setLogoFile] = useState(null);
    const [coverFile, setCoverFile] = useState(null);
    const [docFile, setDocFile] = useState(null);
    const [docName, setDocName] = useState('');

    useEffect(() => { loadChurch(); }, []);

    async function loadChurch() {
        setLoading(true);
        try {
            // Check for ?id= param (Super Admin editing specific church)
            const params = new URLSearchParams(window.location.search);
            const churchId = params.get('id');

            let res;
            if (churchId) {
                res = await get(`/api/churches/admin/${churchId}`);
            } else {
                res = await get('/api/churches/admin/my-church');
            }

            if (res.success && res.data) {
                setChurch(res.data);
                setForm({ ...res.data });
                if (res.data.logo_url) setLogoPreview(res.data.logo_url);
                if (res.data.cover_photo_url) setCoverPreview(res.data.cover_photo_url);
            }
        } catch (e) {
            setAlert({ type: 'error', message: 'No church found. Contact your administrator.' });
        }
        setLoading(false);
    }

    function handleChange(e) {
        const { name, value, type, checked } = e.target;
        setForm(prev => ({ ...prev, [name]: type === 'checkbox' ? checked : value }));
    }

    function handleLogoChange(e) {
        const file = e.target.files[0];
        if (file) {
            setLogoFile(file);
            setLogoPreview(URL.createObjectURL(file));
        }
    }

    function handleCoverChange(e) {
        const file = e.target.files[0];
        if (file) {
            setCoverFile(file);
            setCoverPreview(URL.createObjectURL(file));
        }
    }

    // Service hours management
    function addServiceHour() {
        const hours = form.service_hours ? [...form.service_hours] : [];
        hours.push({ day: 'Sunday', time: '10:00 AM', label: 'Morning Service' });
        setForm(prev => ({ ...prev, service_hours: hours }));
    }

    function updateServiceHour(index, field, value) {
        const hours = [...(form.service_hours || [])];
        hours[index] = { ...hours[index], [field]: value };
        setForm(prev => ({ ...prev, service_hours: hours }));
    }

    function removeServiceHour(index) {
        const hours = [...(form.service_hours || [])];
        hours.splice(index, 1);
        setForm(prev => ({ ...prev, service_hours: hours }));
    }

    async function handleSave() {
        if (!church) return;
        setSaving(true);
        setAlert(null);
        try {
            const fd = new FormData();

            // Tab 1 - General
            const generalFields = ['name', 'email', 'phone', 'website', 'address', 'city', 'state', 'zip_code', 'country', 'latitude', 'longitude', 'denomination', 'year_founded'];
            generalFields.forEach(key => {
                if (form[key] !== null && form[key] !== undefined) fd.append(key, form[key]);
            });
            if (form.service_hours) {
                fd.append('service_hours', JSON.stringify(form.service_hours));
            }

            // Tab 2 - About
            const aboutFields = ['short_description', 'history', 'mission_statement', 'vision_statement'];
            aboutFields.forEach(key => {
                if (form[key] !== null && form[key] !== undefined) fd.append(key, form[key]);
            });

            // Tab 3 - Appearance
            if (form.primary_color) fd.append('primary_color', form.primary_color);
            if (form.secondary_color) fd.append('secondary_color', form.secondary_color);
            if (logoFile) fd.append('logo', logoFile);
            if (coverFile) fd.append('cover_photo', coverFile);

            // Tab 4 - SEO
            const seoFields = ['meta_title', 'meta_description', 'facebook_url', 'instagram_url', 'youtube_url', 'twitter_url', 'tiktok_url'];
            seoFields.forEach(key => {
                if (form[key] !== null && form[key] !== undefined) fd.append(key, form[key]);
            });

            const res = await upload(`/api/churches/admin/${church.id}`, fd, 'POST');
            if (res.success) {
                setChurch(res.data);
                setForm({ ...res.data });
                setLogoFile(null);
                setCoverFile(null);
                if (res.data.logo_url) setLogoPreview(res.data.logo_url);
                if (res.data.cover_photo_url) setCoverPreview(res.data.cover_photo_url);
                setAlert({ type: 'success', message: 'Church profile saved successfully!' });
            }
        } catch (e) {
            setAlert({ type: 'error', message: e.message });
        }
        setSaving(false);
    }

    async function handleUploadDocument() {
        if (!docFile || !docName || !church) return;
        try {
            const fd = new FormData();
            fd.append('document', docFile);
            fd.append('document_name', docName);
            const res = await upload(`/api/churches/admin/${church.id}/documents`, fd);
            if (res.success) {
                setForm(prev => ({ ...prev, documents: res.data.documents }));
                setChurch(prev => ({ ...prev, documents: res.data.documents }));
                setDocFile(null);
                setDocName('');
                setAlert({ type: 'success', message: 'Document uploaded.' });
            }
        } catch (e) {
            setAlert({ type: 'error', message: e.message });
        }
    }

    async function handleDeleteDocument(index) {
        if (!confirm('Delete this document?')) return;
        try {
            const res = await del(`/api/churches/admin/${church.id}/documents?index=${index}`);
            if (res.success) {
                setForm(prev => ({ ...prev, documents: res.data.documents }));
                setChurch(prev => ({ ...prev, documents: res.data.documents }));
                setAlert({ type: 'success', message: 'Document deleted.' });
            }
        } catch (e) {
            setAlert({ type: 'error', message: e.message });
        }
    }

    if (loading) {
        return (
            <div className="flex items-center justify-center py-20">
                <div className="text-center">
                    <i className="fas fa-spinner fa-spin text-3xl text-indigo-500 mb-4"></i>
                    <p className="text-gray-500">Loading church profile...</p>
                </div>
            </div>
        );
    }

    if (!church) {
        return (
            <div className="text-center py-20">
                <i className="fas fa-church text-5xl text-gray-300 mb-4"></i>
                <h2 className="text-xl font-bold text-gray-700 mb-2">No Church Assigned</h2>
                <p className="text-gray-500">Contact your administrator to be assigned to a church.</p>
            </div>
        );
    }

    return (
        <div>
            {/* Header */}
            <div className="flex items-center justify-between mb-6">
                <div>
                    <h1 className="text-2xl font-bold text-gray-900">
                        <i className="fas fa-church text-indigo-600 mr-2"></i>
                        Website Builder
                    </h1>
                    <p className="text-sm text-gray-500 mt-1">
                        Editing: <strong>{church.name}</strong>
                        {church.status === 'approved' && (
                            <a href={`/church/${church.slug}`} target="_blank" className="ml-2 text-indigo-600 hover:underline">
                                <i className="fas fa-external-link-alt mr-1"></i>View Public Page
                            </a>
                        )}
                    </p>
                </div>
                <button onClick={handleSave} disabled={saving}
                    className="bg-indigo-600 text-white px-6 py-2.5 rounded-lg hover:bg-indigo-700 disabled:opacity-50 flex items-center gap-2">
                    {saving ? <i className="fas fa-spinner fa-spin"></i> : <i className="fas fa-save"></i>}
                    {saving ? 'Saving...' : 'Save Changes'}
                </button>
            </div>

            <Alert type={alert?.type} message={alert?.message} onClose={() => setAlert(null)} />

            {/* Tabs */}
            <div className="bg-white rounded-xl shadow-sm border">
                <div className="border-b flex overflow-x-auto">
                    {TABS.map(tab => (
                        <button key={tab.id} onClick={() => setActiveTab(tab.id)}
                            className={`flex items-center gap-2 px-6 py-4 text-sm font-medium border-b-2 transition-colors whitespace-nowrap ${
                                activeTab === tab.id
                                    ? 'border-indigo-600 text-indigo-600'
                                    : 'border-transparent text-gray-500 hover:text-gray-700'
                            }`}>
                            <i className={tab.icon}></i> {tab.label}
                        </button>
                    ))}
                </div>

                <div className="p-6">
                    {activeTab === 'general' && (
                        <GeneralTab form={form} onChange={handleChange}
                            serviceHours={form.service_hours || []}
                            addServiceHour={addServiceHour}
                            updateServiceHour={updateServiceHour}
                            removeServiceHour={removeServiceHour} />
                    )}
                    {activeTab === 'about' && (
                        <AboutTab form={form} onChange={handleChange}
                            documents={form.documents || []}
                            docFile={docFile} docName={docName}
                            onDocFileChange={setDocFile} onDocNameChange={setDocName}
                            onUploadDocument={handleUploadDocument}
                            onDeleteDocument={handleDeleteDocument} />
                    )}
                    {activeTab === 'appearance' && (
                        <AppearanceTab form={form} onChange={handleChange}
                            logoPreview={logoPreview} coverPreview={coverPreview}
                            onLogoChange={handleLogoChange} onCoverChange={handleCoverChange} />
                    )}
                    {activeTab === 'seo' && (
                        <SeoTab form={form} onChange={handleChange} />
                    )}
                </div>
            </div>
        </div>
    );
}

/* ====== TAB 1: General Settings ====== */
function GeneralTab({ form, onChange, serviceHours, addServiceHour, updateServiceHour, removeServiceHour }) {
    return (
        <div className="space-y-6">
            <div>
                <h3 className="text-lg font-semibold text-gray-800 mb-4">Contact Information</h3>
                <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label className="block text-sm font-medium text-gray-700 mb-1">Church Name</label>
                        <input type="text" name="name" value={form.name || ''} onChange={onChange}
                            className="w-full rounded-lg border border-gray-300 px-4 py-2.5 focus:ring-2 focus:ring-indigo-500 outline-none" />
                    </div>
                    <div>
                        <label className="block text-sm font-medium text-gray-700 mb-1">Email</label>
                        <input type="email" name="email" value={form.email || ''} onChange={onChange}
                            className="w-full rounded-lg border border-gray-300 px-4 py-2.5 focus:ring-2 focus:ring-indigo-500 outline-none" />
                    </div>
                    <div>
                        <label className="block text-sm font-medium text-gray-700 mb-1">Phone</label>
                        <input type="text" name="phone" value={form.phone || ''} onChange={onChange}
                            className="w-full rounded-lg border border-gray-300 px-4 py-2.5 focus:ring-2 focus:ring-indigo-500 outline-none" />
                    </div>
                    <div>
                        <label className="block text-sm font-medium text-gray-700 mb-1">Website</label>
                        <input type="url" name="website" value={form.website || ''} onChange={onChange} placeholder="https://..."
                            className="w-full rounded-lg border border-gray-300 px-4 py-2.5 focus:ring-2 focus:ring-indigo-500 outline-none" />
                    </div>
                </div>
            </div>

            <div>
                <h3 className="text-lg font-semibold text-gray-800 mb-4">Address</h3>
                <div className="grid grid-cols-1 gap-4">
                    <div>
                        <label className="block text-sm font-medium text-gray-700 mb-1">Street Address</label>
                        <input type="text" name="address" value={form.address || ''} onChange={onChange}
                            className="w-full rounded-lg border border-gray-300 px-4 py-2.5 focus:ring-2 focus:ring-indigo-500 outline-none" />
                    </div>
                    <div className="grid grid-cols-2 md:grid-cols-4 gap-4">
                        <div>
                            <label className="block text-sm font-medium text-gray-700 mb-1">City</label>
                            <input type="text" name="city" value={form.city || ''} onChange={onChange}
                                className="w-full rounded-lg border border-gray-300 px-4 py-2.5 focus:ring-2 focus:ring-indigo-500 outline-none" />
                        </div>
                        <div>
                            <label className="block text-sm font-medium text-gray-700 mb-1">State</label>
                            <input type="text" name="state" value={form.state || ''} onChange={onChange}
                                className="w-full rounded-lg border border-gray-300 px-4 py-2.5 focus:ring-2 focus:ring-indigo-500 outline-none" />
                        </div>
                        <div>
                            <label className="block text-sm font-medium text-gray-700 mb-1">Zip Code</label>
                            <input type="text" name="zip_code" value={form.zip_code || ''} onChange={onChange}
                                className="w-full rounded-lg border border-gray-300 px-4 py-2.5 focus:ring-2 focus:ring-indigo-500 outline-none" />
                        </div>
                        <div>
                            <label className="block text-sm font-medium text-gray-700 mb-1">Country</label>
                            <input type="text" name="country" value={form.country || ''} onChange={onChange}
                                className="w-full rounded-lg border border-gray-300 px-4 py-2.5 focus:ring-2 focus:ring-indigo-500 outline-none" />
                        </div>
                    </div>
                    <div className="grid grid-cols-2 gap-4">
                        <div>
                            <label className="block text-sm font-medium text-gray-700 mb-1">Latitude</label>
                            <input type="number" step="any" name="latitude" value={form.latitude || ''} onChange={onChange}
                                className="w-full rounded-lg border border-gray-300 px-4 py-2.5 focus:ring-2 focus:ring-indigo-500 outline-none" />
                        </div>
                        <div>
                            <label className="block text-sm font-medium text-gray-700 mb-1">Longitude</label>
                            <input type="number" step="any" name="longitude" value={form.longitude || ''} onChange={onChange}
                                className="w-full rounded-lg border border-gray-300 px-4 py-2.5 focus:ring-2 focus:ring-indigo-500 outline-none" />
                        </div>
                    </div>
                </div>
            </div>

            <div>
                <h3 className="text-lg font-semibold text-gray-800 mb-4">Church Details</h3>
                <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label className="block text-sm font-medium text-gray-700 mb-1">Denomination</label>
                        <input type="text" name="denomination" value={form.denomination || ''} onChange={onChange}
                            className="w-full rounded-lg border border-gray-300 px-4 py-2.5 focus:ring-2 focus:ring-indigo-500 outline-none" />
                    </div>
                    <div>
                        <label className="block text-sm font-medium text-gray-700 mb-1">Year Founded</label>
                        <input type="number" name="year_founded" value={form.year_founded || ''} onChange={onChange}
                            className="w-full rounded-lg border border-gray-300 px-4 py-2.5 focus:ring-2 focus:ring-indigo-500 outline-none" />
                    </div>
                </div>
            </div>

            <div>
                <div className="flex items-center justify-between mb-4">
                    <h3 className="text-lg font-semibold text-gray-800">Service Hours</h3>
                    <button type="button" onClick={addServiceHour}
                        className="text-sm bg-indigo-50 text-indigo-600 px-3 py-1.5 rounded-lg hover:bg-indigo-100">
                        <i className="fas fa-plus mr-1"></i> Add Service
                    </button>
                </div>
                {serviceHours.length === 0 ? (
                    <p className="text-gray-400 text-sm">No service hours added. Click "Add Service" to begin.</p>
                ) : (
                    <div className="space-y-3">
                        {serviceHours.map((sh, i) => (
                            <div key={i} className="flex items-center gap-3 bg-gray-50 rounded-lg p-3">
                                <select value={sh.day} onChange={(e) => updateServiceHour(i, 'day', e.target.value)}
                                    className="rounded-lg border border-gray-300 px-3 py-2 text-sm focus:ring-2 focus:ring-indigo-500 outline-none">
                                    {['Sunday','Monday','Tuesday','Wednesday','Thursday','Friday','Saturday'].map(d => (
                                        <option key={d} value={d}>{d}</option>
                                    ))}
                                </select>
                                <input type="text" value={sh.time} onChange={(e) => updateServiceHour(i, 'time', e.target.value)}
                                    placeholder="10:00 AM" className="flex-1 rounded-lg border border-gray-300 px-3 py-2 text-sm focus:ring-2 focus:ring-indigo-500 outline-none" />
                                <input type="text" value={sh.label} onChange={(e) => updateServiceHour(i, 'label', e.target.value)}
                                    placeholder="Service name" className="flex-1 rounded-lg border border-gray-300 px-3 py-2 text-sm focus:ring-2 focus:ring-indigo-500 outline-none" />
                                <button type="button" onClick={() => removeServiceHour(i)}
                                    className="text-red-500 hover:text-red-700 p-2"><i className="fas fa-trash"></i></button>
                            </div>
                        ))}
                    </div>
                )}
            </div>
        </div>
    );
}

/* ====== TAB 2: About & History ====== */
function AboutTab({ form, onChange, documents, docFile, docName, onDocFileChange, onDocNameChange, onUploadDocument, onDeleteDocument }) {
    return (
        <div className="space-y-6">
            <div>
                <label className="block text-sm font-medium text-gray-700 mb-1">Short Description</label>
                <textarea name="short_description" value={form.short_description || ''} onChange={onChange} rows={3}
                    placeholder="A brief description of your church..."
                    className="w-full rounded-lg border border-gray-300 px-4 py-2.5 focus:ring-2 focus:ring-indigo-500 outline-none" />
                <p className="text-xs text-gray-400 mt-1">Shown in the church directory listing. Max 500 characters.</p>
            </div>

            <div>
                <label className="block text-sm font-medium text-gray-700 mb-1">Church History</label>
                <textarea name="history" value={form.history || ''} onChange={onChange} rows={8}
                    placeholder="Tell the story of your church... You can use HTML for rich formatting."
                    className="w-full rounded-lg border border-gray-300 px-4 py-2.5 focus:ring-2 focus:ring-indigo-500 outline-none font-mono text-sm" />
                <p className="text-xs text-gray-400 mt-1">Supports HTML formatting. This appears on your church's public page.</p>
            </div>

            <div>
                <label className="block text-sm font-medium text-gray-700 mb-1">Mission Statement</label>
                <textarea name="mission_statement" value={form.mission_statement || ''} onChange={onChange} rows={3}
                    className="w-full rounded-lg border border-gray-300 px-4 py-2.5 focus:ring-2 focus:ring-indigo-500 outline-none" />
            </div>

            <div>
                <label className="block text-sm font-medium text-gray-700 mb-1">Vision Statement</label>
                <textarea name="vision_statement" value={form.vision_statement || ''} onChange={onChange} rows={3}
                    className="w-full rounded-lg border border-gray-300 px-4 py-2.5 focus:ring-2 focus:ring-indigo-500 outline-none" />
            </div>

            <div>
                <h3 className="text-lg font-semibold text-gray-800 mb-4">Documents</h3>
                {documents.length > 0 && (
                    <div className="space-y-2 mb-4">
                        {documents.map((doc, i) => (
                            <div key={i} className="flex items-center justify-between bg-gray-50 rounded-lg p-3">
                                <div className="flex items-center gap-3">
                                    <i className="fas fa-file-pdf text-red-500 text-lg"></i>
                                    <div>
                                        <div className="text-sm font-medium text-gray-800">{doc.name}</div>
                                        <div className="text-xs text-gray-400">{doc.uploaded_at}</div>
                                    </div>
                                </div>
                                <div className="flex items-center gap-2">
                                    <a href={`/storage/${doc.file_path}`} target="_blank"
                                        className="text-indigo-600 hover:text-indigo-800 text-sm">
                                        <i className="fas fa-download"></i>
                                    </a>
                                    <button onClick={() => onDeleteDocument(i)}
                                        className="text-red-500 hover:text-red-700 text-sm">
                                        <i className="fas fa-trash"></i>
                                    </button>
                                </div>
                            </div>
                        ))}
                    </div>
                )}
                <div className="flex items-end gap-3 bg-gray-50 rounded-lg p-4">
                    <div className="flex-1">
                        <label className="block text-sm font-medium text-gray-700 mb-1">Document Name</label>
                        <input type="text" value={docName} onChange={(e) => onDocNameChange(e.target.value)}
                            placeholder="e.g., Statement of Faith"
                            className="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:ring-2 focus:ring-indigo-500 outline-none" />
                    </div>
                    <div className="flex-1">
                        <label className="block text-sm font-medium text-gray-700 mb-1">File (PDF, DOC)</label>
                        <input type="file" accept=".pdf,.doc,.docx" onChange={(e) => onDocFileChange(e.target.files[0])}
                            className="w-full text-sm text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded-lg file:border-0 file:text-sm file:font-semibold file:bg-indigo-50 file:text-indigo-700" />
                    </div>
                    <button type="button" onClick={onUploadDocument} disabled={!docFile || !docName}
                        className="bg-indigo-600 text-white px-4 py-2 rounded-lg hover:bg-indigo-700 disabled:opacity-50 text-sm whitespace-nowrap">
                        <i className="fas fa-upload mr-1"></i> Upload
                    </button>
                </div>
            </div>
        </div>
    );
}

/* ====== TAB 3: Appearance ====== */
function AppearanceTab({ form, onChange, logoPreview, coverPreview, onLogoChange, onCoverChange }) {
    return (
        <div className="space-y-6">
            <div>
                <h3 className="text-lg font-semibold text-gray-800 mb-4">Logo</h3>
                <div className="flex items-start gap-6">
                    <div className="w-32 h-32 border-2 border-dashed border-gray-300 rounded-xl flex items-center justify-center overflow-hidden bg-gray-50">
                        {logoPreview ? (
                            <img src={logoPreview} alt="Logo" className="w-full h-full object-contain" />
                        ) : (
                            <div className="text-center text-gray-400">
                                <i className="fas fa-image text-2xl"></i>
                                <p className="text-xs mt-1">No logo</p>
                            </div>
                        )}
                    </div>
                    <div>
                        <input type="file" accept="image/*" onChange={onLogoChange}
                            className="text-sm text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded-lg file:border-0 file:text-sm file:font-semibold file:bg-indigo-50 file:text-indigo-700" />
                        <p className="text-xs text-gray-400 mt-2">Recommended: 200x200px, PNG or SVG with transparent background</p>
                    </div>
                </div>
            </div>

            <div>
                <h3 className="text-lg font-semibold text-gray-800 mb-4">Cover Photo</h3>
                <div className="w-full h-48 border-2 border-dashed border-gray-300 rounded-xl overflow-hidden bg-gray-50 mb-3">
                    {coverPreview ? (
                        <img src={coverPreview} alt="Cover" className="w-full h-full object-cover" />
                    ) : (
                        <div className="w-full h-full flex items-center justify-center text-gray-400">
                            <div className="text-center">
                                <i className="fas fa-panorama text-3xl"></i>
                                <p className="text-sm mt-2">No cover photo</p>
                            </div>
                        </div>
                    )}
                </div>
                <input type="file" accept="image/*" onChange={onCoverChange}
                    className="text-sm text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded-lg file:border-0 file:text-sm file:font-semibold file:bg-indigo-50 file:text-indigo-700" />
                <p className="text-xs text-gray-400 mt-2">Recommended: 1920x400px or similar wide banner image</p>
            </div>

            <div>
                <h3 className="text-lg font-semibold text-gray-800 mb-4">Brand Colors</h3>
                <div className="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div>
                        <label className="block text-sm font-medium text-gray-700 mb-2">Primary Color</label>
                        <div className="flex items-center gap-3">
                            <input type="color" name="primary_color" value={form.primary_color || '#4F46E5'}
                                onChange={onChange} className="w-12 h-12 rounded-lg border border-gray-300 cursor-pointer" />
                            <input type="text" name="primary_color" value={form.primary_color || '#4F46E5'}
                                onChange={onChange} placeholder="#4F46E5"
                                className="flex-1 rounded-lg border border-gray-300 px-4 py-2.5 focus:ring-2 focus:ring-indigo-500 outline-none font-mono text-sm" />
                        </div>
                        <p className="text-xs text-gray-400 mt-1">Applied to headings, buttons, and accents on your public page</p>
                    </div>
                    <div>
                        <label className="block text-sm font-medium text-gray-700 mb-2">Secondary Color</label>
                        <div className="flex items-center gap-3">
                            <input type="color" name="secondary_color" value={form.secondary_color || '#818CF8'}
                                onChange={onChange} className="w-12 h-12 rounded-lg border border-gray-300 cursor-pointer" />
                            <input type="text" name="secondary_color" value={form.secondary_color || '#818CF8'}
                                onChange={onChange} placeholder="#818CF8"
                                className="flex-1 rounded-lg border border-gray-300 px-4 py-2.5 focus:ring-2 focus:ring-indigo-500 outline-none font-mono text-sm" />
                        </div>
                    </div>
                </div>

                {/* Live Preview */}
                <div className="mt-6 p-4 rounded-xl border" style={{ borderColor: form.primary_color || '#4F46E5' }}>
                    <h4 className="font-bold mb-2" style={{ color: form.primary_color || '#4F46E5' }}>Color Preview</h4>
                    <p className="text-sm text-gray-600 mb-3">This is how your brand color will appear on your public page.</p>
                    <button className="px-4 py-2 rounded-lg text-white text-sm" style={{ backgroundColor: form.primary_color || '#4F46E5' }}>
                        Sample Button
                    </button>
                </div>
            </div>
        </div>
    );
}

/* ====== TAB 4: SEO & Social ====== */
function SeoTab({ form, onChange }) {
    return (
        <div className="space-y-6">
            <div>
                <h3 className="text-lg font-semibold text-gray-800 mb-4">Search Engine Optimization</h3>
                <div>
                    <label className="block text-sm font-medium text-gray-700 mb-1">Meta Title</label>
                    <input type="text" name="meta_title" value={form.meta_title || ''} onChange={onChange}
                        placeholder="Your Church Name - City, State"
                        className="w-full rounded-lg border border-gray-300 px-4 py-2.5 focus:ring-2 focus:ring-indigo-500 outline-none" />
                    <p className="text-xs text-gray-400 mt-1">{(form.meta_title || '').length}/70 characters</p>
                </div>
                <div className="mt-4">
                    <label className="block text-sm font-medium text-gray-700 mb-1">Meta Description</label>
                    <textarea name="meta_description" value={form.meta_description || ''} onChange={onChange} rows={3}
                        placeholder="A brief description of your church for search engine results..."
                        className="w-full rounded-lg border border-gray-300 px-4 py-2.5 focus:ring-2 focus:ring-indigo-500 outline-none" />
                    <p className="text-xs text-gray-400 mt-1">{(form.meta_description || '').length}/160 characters</p>
                </div>

                {/* SEO Preview */}
                <div className="mt-4 p-4 bg-gray-50 rounded-lg">
                    <p className="text-xs text-gray-400 mb-1">Search Preview</p>
                    <p className="text-blue-700 text-lg font-medium truncate">{form.meta_title || form.name || 'Church Name'}</p>
                    <p className="text-green-700 text-sm">yoursite.com/church/{form.slug || 'church-name'}</p>
                    <p className="text-sm text-gray-600 mt-1 line-clamp-2">{form.meta_description || form.short_description || 'Your church description will appear here...'}</p>
                </div>
            </div>

            <div>
                <h3 className="text-lg font-semibold text-gray-800 mb-4">Social Media Links</h3>
                <div className="space-y-4">
                    <div className="flex items-center gap-3">
                        <div className="w-10 h-10 bg-blue-100 rounded-lg flex items-center justify-center">
                            <i className="fab fa-facebook text-blue-600"></i>
                        </div>
                        <input type="url" name="facebook_url" value={form.facebook_url || ''} onChange={onChange}
                            placeholder="https://facebook.com/yourchurch"
                            className="flex-1 rounded-lg border border-gray-300 px-4 py-2.5 focus:ring-2 focus:ring-indigo-500 outline-none" />
                    </div>
                    <div className="flex items-center gap-3">
                        <div className="w-10 h-10 bg-pink-100 rounded-lg flex items-center justify-center">
                            <i className="fab fa-instagram text-pink-600"></i>
                        </div>
                        <input type="url" name="instagram_url" value={form.instagram_url || ''} onChange={onChange}
                            placeholder="https://instagram.com/yourchurch"
                            className="flex-1 rounded-lg border border-gray-300 px-4 py-2.5 focus:ring-2 focus:ring-indigo-500 outline-none" />
                    </div>
                    <div className="flex items-center gap-3">
                        <div className="w-10 h-10 bg-red-100 rounded-lg flex items-center justify-center">
                            <i className="fab fa-youtube text-red-600"></i>
                        </div>
                        <input type="url" name="youtube_url" value={form.youtube_url || ''} onChange={onChange}
                            placeholder="https://youtube.com/@yourchurch"
                            className="flex-1 rounded-lg border border-gray-300 px-4 py-2.5 focus:ring-2 focus:ring-indigo-500 outline-none" />
                    </div>
                    <div className="flex items-center gap-3">
                        <div className="w-10 h-10 bg-sky-100 rounded-lg flex items-center justify-center">
                            <i className="fab fa-twitter text-sky-500"></i>
                        </div>
                        <input type="url" name="twitter_url" value={form.twitter_url || ''} onChange={onChange}
                            placeholder="https://twitter.com/yourchurch"
                            className="flex-1 rounded-lg border border-gray-300 px-4 py-2.5 focus:ring-2 focus:ring-indigo-500 outline-none" />
                    </div>
                    <div className="flex items-center gap-3">
                        <div className="w-10 h-10 bg-gray-100 rounded-lg flex items-center justify-center">
                            <i className="fab fa-tiktok text-gray-800"></i>
                        </div>
                        <input type="url" name="tiktok_url" value={form.tiktok_url || ''} onChange={onChange}
                            placeholder="https://tiktok.com/@yourchurch"
                            className="flex-1 rounded-lg border border-gray-300 px-4 py-2.5 focus:ring-2 focus:ring-indigo-500 outline-none" />
                    </div>
                </div>
            </div>
        </div>
    );
}
