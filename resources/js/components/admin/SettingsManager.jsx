import React, { useState, useEffect } from 'react';
import { Modal, DataTable, Pagination, FormField, Alert } from '../shared/CrudPanel';
import { get, post, put, del, upload } from '../shared/api';

function SectionHeader({ icon, title, description }) {
    return (
        <div className="mb-6 pb-3 border-b border-gray-200">
            <div className="flex items-center gap-3">
                <div className="flex-shrink-0 w-9 h-9 bg-indigo-100 rounded-lg flex items-center justify-center">
                    {icon}
                </div>
                <div>
                    <h3 className="text-lg font-semibold text-gray-900">{title}</h3>
                    {description && <p className="text-sm text-gray-500">{description}</p>}
                </div>
            </div>
        </div>
    );
}

export default function SettingsManager() {
    const [activeTab, setActiveTab] = useState('general');

    // Custom profile fields
    const [profileFields, setProfileFields] = useState([]);
    const [profileFieldsSaving, setProfileFieldsSaving] = useState(false);
    const [profileFieldsAlert, setProfileFieldsAlert] = useState(null);

    const defaultFields = [
        { key: 'phone', label: 'Phone Number', type: 'tel', required: false, enabled: false, options: '', placeholder: 'Enter phone number' },
        { key: 'church_name', label: 'Church Name', type: 'text', required: false, enabled: false, options: '', placeholder: 'Enter church name' },
        { key: 'social_id', label: 'Social ID', type: 'text', required: false, enabled: false, options: '', placeholder: 'Enter social ID' },
        { key: 'spiritual_background', label: 'Spiritual Background', type: 'textarea', required: false, enabled: false, options: '', placeholder: 'Describe your spiritual background' },
    ];

    // Auth provider settings
    const [authForm, setAuthForm] = useState({
        auth_google_enabled: false,
        auth_google_client_id: '',
        auth_google_client_secret: '',
        auth_facebook_enabled: false,
        auth_facebook_client_id: '',
        auth_facebook_client_secret: '',
    });
    const [authSaving, setAuthSaving] = useState(false);

    // Storage settings
    const [storageForm, setStorageForm] = useState({
        storage_driver: 'local',
        storage_s3_key: '',
        storage_s3_secret: '',
        storage_s3_region: '',
        storage_s3_bucket: '',
        max_upload_size: 10,
        allowed_file_types: 'jpg,jpeg,png,gif,webp,svg,pdf,mp3,mp4',
    });
    const [storageSaving, setStorageSaving] = useState(false);

    // Cache/Performance settings
    const [cacheForm, setCacheForm] = useState({
        cache_driver: 'file',
        cache_ttl: 3600,
        enable_page_cache: false,
        enable_minification: false,
        cdn_url: '',
    });
    const [cacheSaving, setCacheSaving] = useState(false);

    // Logging settings
    const [loggingForm, setLoggingForm] = useState({
        log_channel: 'daily',
        queue_driver: 'sync',
    });
    const [loggingSaving, setLoggingSaving] = useState(false);

    // Email settings state
    const [emailForm, setEmailForm] = useState({
        mail_provider: 'smtp',
        smtp_host: '',
        smtp_port: 587,
        smtp_username: '',
        smtp_password: '',
        smtp_encryption: 'tls',
        mail_from_address: '',
        mail_from_name: '',
        mailchimp_api_key: '',
        mailchimp_list_id: '',
        mailchimp_server_prefix: '',
        sendgrid_api_key: '',
        mailgun_domain: '',
        mailgun_secret: '',
        email_contact_notification: true,
        email_contact_recipient: '',
        email_newsletter_enabled: true,
        email_welcome_enabled: false,
        email_welcome_template: '',
        email_signature: '',
    });
    const [emailSaving, setEmailSaving] = useState(false);
    const [emailAlert, setEmailAlert] = useState(null);
    const [testEmailAddress, setTestEmailAddress] = useState('');
    const [testingSend, setTestingSend] = useState(false);

    const [form, setForm] = useState({
        church_name: '',
        tagline: '',
        description: '',
        email: '',
        phone: '',
        address: '',
        city: '',
        state: '',
        zip_code: '',
        country: '',
        pastor_name: '',
        pastor_title: '',
        mission_statement: '',
        vision_statement: '',
        about_text: '',
        facebook_url: '',
        twitter_url: '',
        instagram_url: '',
        youtube_url: '',
        tiktok_url: '',
        primary_color: '#4f46e5',
        secondary_color: '#7c3aed',
        footer_text: '',
        meta_title: '',
        meta_description: '',
        meta_keywords: '',
        service_times: '',
    });
    const [files, setFiles] = useState({ logo: null, banner: null, favicon: null });
    const [previews, setPreviews] = useState({ logo: null, banner: null, favicon: null });
    const [alert, setAlert] = useState(null);
    const [loading, setLoading] = useState(true);
    const [saving, setSaving] = useState(false);

    useEffect(() => {
        fetchSettings();
        fetchEmailSettings();
        fetchExtendedSettings();
        fetchProfileFields();
    }, []);

    const fetchExtendedSettings = async () => {
        try {
            const data = await get('/api/settings');
            const s = data.data || data;
            if (s) {
                setAuthForm(prev => ({
                    ...prev,
                    auth_google_enabled: s.auth_google_enabled ?? false,
                    auth_google_client_id: s.auth_google_client_id || '',
                    auth_google_client_secret: s.auth_google_client_secret ? '********' : '',
                    auth_facebook_enabled: s.auth_facebook_enabled ?? false,
                    auth_facebook_client_id: s.auth_facebook_client_id || '',
                    auth_facebook_client_secret: s.auth_facebook_client_secret ? '********' : '',
                }));
                setStorageForm(prev => ({
                    ...prev,
                    storage_driver: s.storage_driver || 'local',
                    storage_s3_key: s.storage_s3_key || '',
                    storage_s3_secret: s.storage_s3_secret ? '********' : '',
                    storage_s3_region: s.storage_s3_region || '',
                    storage_s3_bucket: s.storage_s3_bucket || '',
                    max_upload_size: s.max_upload_size || 10,
                    allowed_file_types: s.allowed_file_types || 'jpg,jpeg,png,gif,webp,svg,pdf,mp3,mp4',
                }));
                setCacheForm(prev => ({
                    ...prev,
                    cache_driver: s.cache_driver || 'file',
                    cache_ttl: s.cache_ttl || 3600,
                    enable_page_cache: s.enable_page_cache ?? false,
                    enable_minification: s.enable_minification ?? false,
                    cdn_url: s.cdn_url || '',
                }));
                setLoggingForm(prev => ({
                    ...prev,
                    log_channel: s.log_channel || 'daily',
                    queue_driver: s.queue_driver || 'sync',
                }));
            }
        } catch (e) { /* silently fail */ }
    };

    const fetchProfileFields = async () => {
        try {
            const data = await get('/api/settings/profile-fields');
            const fields = data.data || [];
            if (fields.length > 0) {
                setProfileFields(fields);
            } else {
                setProfileFields(defaultFields);
            }
        } catch (e) {
            setProfileFields(defaultFields);
        }
    };

    const handleProfileFieldChange = (index, key, value) => {
        setProfileFields(prev => prev.map((f, i) => i === index ? { ...f, [key]: value } : f));
    };

    const addCustomField = () => {
        setProfileFields(prev => [...prev, {
            key: 'custom_' + Date.now(),
            label: '',
            type: 'text',
            required: false,
            enabled: false,
            options: '',
            placeholder: '',
        }]);
    };

    const removeCustomField = (index) => {
        const field = profileFields[index];
        if (['phone', 'church_name', 'social_id', 'spiritual_background'].includes(field.key)) return;
        setProfileFields(prev => prev.filter((_, i) => i !== index));
    };

    const handleSaveProfileFields = async () => {
        setProfileFieldsSaving(true);
        try {
            await put('/api/settings/profile-fields', { fields: profileFields });
            setProfileFieldsAlert({ type: 'success', message: 'Profile fields configuration saved.' });
        } catch (e) {
            setProfileFieldsAlert({ type: 'error', message: e.message || 'Failed to save profile fields.' });
        }
        setProfileFieldsSaving(false);
    };

    const handleSaveAuth = async () => {
        setAuthSaving(true);
        try {
            const payload = { ...authForm };
            if (payload.auth_google_client_secret === '********') delete payload.auth_google_client_secret;
            if (payload.auth_facebook_client_secret === '********') delete payload.auth_facebook_client_secret;
            const fd = new FormData();
            Object.entries(payload).forEach(([k, v]) => { if (v !== null && v !== undefined) fd.append(k, v); });
            fd.append('_method', 'PUT');
            await upload('/api/settings', fd, 'POST');
            setAlert({ type: 'success', message: 'Authentication settings saved.' });
        } catch (e) {
            setAlert({ type: 'error', message: 'Failed: ' + e.message });
        }
        setAuthSaving(false);
    };

    const handleSaveStorage = async () => {
        setStorageSaving(true);
        try {
            const payload = { ...storageForm };
            if (payload.storage_s3_secret === '********') delete payload.storage_s3_secret;
            const fd = new FormData();
            Object.entries(payload).forEach(([k, v]) => { if (v !== null && v !== undefined) fd.append(k, v); });
            fd.append('_method', 'PUT');
            await upload('/api/settings', fd, 'POST');
            setAlert({ type: 'success', message: 'Storage settings saved.' });
        } catch (e) {
            setAlert({ type: 'error', message: 'Failed: ' + e.message });
        }
        setStorageSaving(false);
    };

    const handleSaveCache = async () => {
        setCacheSaving(true);
        try {
            const fd = new FormData();
            Object.entries(cacheForm).forEach(([k, v]) => { if (v !== null && v !== undefined) fd.append(k, v); });
            fd.append('_method', 'PUT');
            await upload('/api/settings', fd, 'POST');
            setAlert({ type: 'success', message: 'Cache settings saved.' });
        } catch (e) {
            setAlert({ type: 'error', message: 'Failed: ' + e.message });
        }
        setCacheSaving(false);
    };

    const handleSaveLogging = async () => {
        setLoggingSaving(true);
        try {
            const fd = new FormData();
            Object.entries(loggingForm).forEach(([k, v]) => { if (v !== null && v !== undefined) fd.append(k, v); });
            fd.append('_method', 'PUT');
            await upload('/api/settings', fd, 'POST');
            setAlert({ type: 'success', message: 'Logging & Queue settings saved.' });
        } catch (e) {
            setAlert({ type: 'error', message: 'Failed: ' + e.message });
        }
        setLoggingSaving(false);
    };

    const fetchSettings = async () => {
        setLoading(true);
        try {
            const data = await get('/api/settings');
            const settings = data.data || data;
            const updated = { ...form };
            Object.keys(updated).forEach((key) => {
                if (settings[key] !== undefined && settings[key] !== null) {
                    updated[key] = settings[key];
                }
            });
            setForm(updated);

            // Set existing file previews from the server
            const newPreviews = { logo: null, banner: null, favicon: null };
            if (settings.logo_url) newPreviews.logo = settings.logo_url;
            if (settings.banner_url) newPreviews.banner = settings.banner_url;
            if (settings.favicon_url) newPreviews.favicon = settings.favicon_url;
            setPreviews(newPreviews);
        } catch (e) {
            setAlert({ type: 'error', message: 'Failed to load settings: ' + e.message });
        }
        setLoading(false);
    };

    const handleChange = (e) => {
        const { name, value } = e.target;
        setForm({ ...form, [name]: value });
    };

    const handleFileChange = (e) => {
        const { name, files: fileList } = e.target;
        if (fileList && fileList[0]) {
            setFiles((prev) => ({ ...prev, [name]: fileList[0] }));
            // Create local preview
            const reader = new FileReader();
            reader.onload = (ev) => {
                setPreviews((prev) => ({ ...prev, [name]: ev.target.result }));
            };
            reader.readAsDataURL(fileList[0]);
        }
    };

    const handleSubmit = async (e) => {
        e.preventDefault();
        setSaving(true);
        setAlert(null);
        try {
            const formData = new FormData();

            // Append all text fields
            Object.keys(form).forEach((key) => {
                if (form[key] !== null && form[key] !== undefined) {
                    formData.append(key, form[key]);
                }
            });

            // Append files
            if (files.logo) formData.append('logo', files.logo);
            if (files.banner) formData.append('banner', files.banner);
            if (files.favicon) formData.append('favicon', files.favicon);

            // Laravel PUT via POST with _method override for FormData
            formData.append('_method', 'PUT');
            await upload('/api/settings', formData, 'POST');

            setAlert({ type: 'success', message: 'Settings saved successfully.' });
            // Clear file inputs after successful save
            setFiles({ logo: null, banner: null, favicon: null });
        } catch (e) {
            setAlert({ type: 'error', message: 'Failed to save settings: ' + e.message });
        }
        setSaving(false);
    };

    const fetchEmailSettings = async () => {
        try {
            const data = await get('/api/settings/email');
            const settings = data.data || data;
            const updated = { ...emailForm };
            Object.keys(updated).forEach((key) => {
                if (settings[key] !== undefined && settings[key] !== null) {
                    updated[key] = settings[key];
                }
            });
            setEmailForm(updated);
        } catch (e) {
            // Silently fail - email settings may not be configured yet
        }
    };

    const handleEmailChange = (e) => {
        const { name, value, type, checked } = e.target;
        setEmailForm({ ...emailForm, [name]: type === 'checkbox' ? checked : value });
    };

    const handleEmailSubmit = async (e) => {
        if (e) e.preventDefault();
        setEmailSaving(true);
        setEmailAlert(null);
        try {
            await put('/api/settings/email', emailForm);
            setEmailAlert({ type: 'success', message: 'Email settings saved successfully.' });
            fetchEmailSettings();
        } catch (e) {
            setEmailAlert({ type: 'error', message: 'Failed to save email settings: ' + e.message });
        }
        setEmailSaving(false);
    };

    const handleTestEmail = async () => {
        if (!testEmailAddress) {
            setEmailAlert({ type: 'error', message: 'Please enter an email address to send a test to.' });
            return;
        }
        setTestingSend(true);
        setEmailAlert(null);
        try {
            const res = await post('/api/settings/email/test', { to_email: testEmailAddress });
            setEmailAlert({ type: 'success', message: res.message || 'Test email sent successfully!' });
        } catch (e) {
            setEmailAlert({ type: 'error', message: 'Test email failed: ' + e.message });
        }
        setTestingSend(false);
    };

    if (loading) {
        return (
            <div className="flex items-center justify-center py-20">
                <svg className="animate-spin h-8 w-8 text-indigo-600" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                    <circle className="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" strokeWidth="4"></circle>
                    <path className="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
                </svg>
                <span className="ml-3 text-gray-500 text-sm">Loading settings...</span>
            </div>
        );
    }

    return (
        <div>
            <div className="flex items-center justify-between mb-6">
                <h2 className="text-2xl font-bold text-gray-800">Site Settings</h2>
            </div>

            {/* Tabs */}
            <div className="flex gap-1 mb-6 bg-gray-100 rounded-lg p-1 w-fit flex-wrap">
                {[
                    { id: 'general', label: 'General', icon: 'fa-cog' },
                    { id: 'email', label: 'Mail', icon: 'fa-envelope' },
                    { id: 'authentication', label: 'Authentication', icon: 'fa-key' },
                    { id: 'storage', label: 'Uploading', icon: 'fa-cloud-upload-alt' },
                    { id: 'cache', label: 'Cache', icon: 'fa-bolt' },
                    { id: 'logging', label: 'Logging & Queue', icon: 'fa-stream' },
                    { id: 'userfields', label: 'User Fields', icon: 'fa-user-cog' },
                ].map(tab => (
                    <button
                        key={tab.id}
                        onClick={() => setActiveTab(tab.id)}
                        className={`px-4 py-2 rounded-md text-sm font-medium transition-colors flex items-center gap-2 ${activeTab === tab.id ? 'bg-white text-gray-900 shadow-sm' : 'text-gray-500 hover:text-gray-700'}`}
                    >
                        <i className={`fas ${tab.icon} text-xs`}></i>
                        {tab.label}
                    </button>
                ))}
            </div>

            {/* EMAIL SETTINGS TAB */}
            {activeTab === 'email' && (
                <div>
                    <Alert {...emailAlert} onClose={() => setEmailAlert(null)} />

                    {/* Mail Provider */}
                    <div className="bg-white rounded-xl shadow-sm border p-6 mb-6">
                        <SectionHeader
                            icon={<svg className="w-5 h-5 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" /></svg>}
                            title="Email Provider"
                            description="Choose how your church sends emails"
                        />
                        <FormField label="Mail Provider" name="mail_provider" type="select" value={emailForm.mail_provider} onChange={handleEmailChange} options={[
                            { value: 'smtp', label: 'SMTP (Custom Mail Server)' },
                            { value: 'mailchimp', label: 'Mailchimp (Mandrill)' },
                            { value: 'sendgrid', label: 'SendGrid' },
                            { value: 'mailgun', label: 'Mailgun' },
                        ]} />
                        <div className="grid grid-cols-1 md:grid-cols-2 gap-x-6">
                            <FormField label="From Email Address" name="mail_from_address" type="email" value={emailForm.mail_from_address} onChange={handleEmailChange} placeholder="noreply@yourchurch.com" />
                            <FormField label="From Name" name="mail_from_name" value={emailForm.mail_from_name} onChange={handleEmailChange} placeholder="Your Church Name" />
                        </div>
                    </div>

                    {/* SMTP Settings */}
                    {emailForm.mail_provider === 'smtp' && (
                        <div className="bg-white rounded-xl shadow-sm border p-6 mb-6">
                            <SectionHeader
                                icon={<svg className="w-5 h-5 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M5 12h14M5 12a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v4a2 2 0 01-2 2M5 12a2 2 0 00-2 2v4a2 2 0 002 2h14a2 2 0 002-2v-4a2 2 0 00-2-2" /></svg>}
                                title="SMTP Configuration"
                                description="Configure your SMTP mail server"
                            />
                            <div className="grid grid-cols-1 md:grid-cols-2 gap-x-6">
                                <FormField label="SMTP Host" name="smtp_host" value={emailForm.smtp_host} onChange={handleEmailChange} placeholder="smtp.gmail.com" />
                                <div className="grid grid-cols-2 gap-x-4">
                                    <FormField label="Port" name="smtp_port" type="number" value={emailForm.smtp_port} onChange={handleEmailChange} placeholder="587" />
                                    <FormField label="Encryption" name="smtp_encryption" type="select" value={emailForm.smtp_encryption} onChange={handleEmailChange} options={[
                                        { value: 'tls', label: 'TLS' },
                                        { value: 'ssl', label: 'SSL' },
                                        { value: 'none', label: 'None' },
                                    ]} />
                                </div>
                                <FormField label="SMTP Username" name="smtp_username" value={emailForm.smtp_username} onChange={handleEmailChange} placeholder="your@email.com" />
                                <FormField label="SMTP Password" name="smtp_password" type="password" value={emailForm.smtp_password} onChange={handleEmailChange} placeholder="Leave blank to keep current" />
                            </div>
                        </div>
                    )}

                    {/* Mailchimp Settings */}
                    {emailForm.mail_provider === 'mailchimp' && (
                        <div className="bg-white rounded-xl shadow-sm border p-6 mb-6">
                            <SectionHeader
                                icon={<svg className="w-5 h-5 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M13 10V3L4 14h7v7l9-11h-7z" /></svg>}
                                title="Mailchimp Configuration"
                                description="Configure Mailchimp for newsletters and transactional email (Mandrill)"
                            />
                            <FormField label="Mailchimp API Key" name="mailchimp_api_key" type="password" value={emailForm.mailchimp_api_key} onChange={handleEmailChange} placeholder="Leave blank to keep current" />
                            <div className="grid grid-cols-1 md:grid-cols-2 gap-x-6">
                                <FormField label="Audience/List ID" name="mailchimp_list_id" value={emailForm.mailchimp_list_id} onChange={handleEmailChange} placeholder="e.g., abc123def4" />
                                <FormField label="Server Prefix" name="mailchimp_server_prefix" value={emailForm.mailchimp_server_prefix} onChange={handleEmailChange} placeholder="e.g., us1, us2, us21" />
                            </div>
                            <p className="text-xs text-gray-500 mt-2">The server prefix is in your API key after the dash (e.g., key-<strong>us21</strong>). The List ID is in Audience &rarr; Settings.</p>
                        </div>
                    )}

                    {/* SendGrid Settings */}
                    {emailForm.mail_provider === 'sendgrid' && (
                        <div className="bg-white rounded-xl shadow-sm border p-6 mb-6">
                            <SectionHeader
                                icon={<svg className="w-5 h-5 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M13 10V3L4 14h7v7l9-11h-7z" /></svg>}
                                title="SendGrid Configuration"
                                description="Configure SendGrid for email delivery"
                            />
                            <FormField label="SendGrid API Key" name="sendgrid_api_key" type="password" value={emailForm.sendgrid_api_key} onChange={handleEmailChange} placeholder="Leave blank to keep current" />
                        </div>
                    )}

                    {/* Mailgun Settings */}
                    {emailForm.mail_provider === 'mailgun' && (
                        <div className="bg-white rounded-xl shadow-sm border p-6 mb-6">
                            <SectionHeader
                                icon={<svg className="w-5 h-5 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M13 10V3L4 14h7v7l9-11h-7z" /></svg>}
                                title="Mailgun Configuration"
                                description="Configure Mailgun for email delivery"
                            />
                            <div className="grid grid-cols-1 md:grid-cols-2 gap-x-6">
                                <FormField label="Mailgun Domain" name="mailgun_domain" value={emailForm.mailgun_domain} onChange={handleEmailChange} placeholder="mg.yourdomain.com" />
                                <FormField label="Mailgun Secret" name="mailgun_secret" type="password" value={emailForm.mailgun_secret} onChange={handleEmailChange} placeholder="Leave blank to keep current" />
                            </div>
                        </div>
                    )}

                    {/* Email Workflow Settings */}
                    <div className="bg-white rounded-xl shadow-sm border p-6 mb-6">
                        <SectionHeader
                            icon={<svg className="w-5 h-5 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.066 2.573c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.573 1.066c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.066-2.573c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z" /><path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" /></svg>}
                            title="Email Workflows"
                            description="Configure automated email notifications"
                        />
                        <div className="space-y-4">
                            <label className="flex items-center gap-3 cursor-pointer">
                                <input type="checkbox" name="email_contact_notification" checked={emailForm.email_contact_notification} onChange={handleEmailChange} className="w-4 h-4 text-indigo-600 rounded border-gray-300 focus:ring-indigo-500" />
                                <div>
                                    <span className="text-sm font-medium text-gray-700">Send contact form notifications</span>
                                    <p className="text-xs text-gray-500">Receive an email when someone submits the contact form</p>
                                </div>
                            </label>
                            {emailForm.email_contact_notification && (
                                <FormField label="Contact Notification Recipient" name="email_contact_recipient" type="email" value={emailForm.email_contact_recipient} onChange={handleEmailChange} placeholder="admin@yourchurch.com (defaults to church email)" />
                            )}
                            <label className="flex items-center gap-3 cursor-pointer">
                                <input type="checkbox" name="email_newsletter_enabled" checked={emailForm.email_newsletter_enabled} onChange={handleEmailChange} className="w-4 h-4 text-indigo-600 rounded border-gray-300 focus:ring-indigo-500" />
                                <div>
                                    <span className="text-sm font-medium text-gray-700">Enable newsletter sending</span>
                                    <p className="text-xs text-gray-500">Allow sending newsletters to subscribers</p>
                                </div>
                            </label>
                            <label className="flex items-center gap-3 cursor-pointer">
                                <input type="checkbox" name="email_welcome_enabled" checked={emailForm.email_welcome_enabled} onChange={handleEmailChange} className="w-4 h-4 text-indigo-600 rounded border-gray-300 focus:ring-indigo-500" />
                                <div>
                                    <span className="text-sm font-medium text-gray-700">Send welcome email to new subscribers</span>
                                    <p className="text-xs text-gray-500">Automatically email new newsletter subscribers</p>
                                </div>
                            </label>
                            {emailForm.email_welcome_enabled && (
                                <FormField label="Welcome Email Template" name="email_welcome_template" type="textarea" value={emailForm.email_welcome_template} onChange={handleEmailChange} rows={5} placeholder="Welcome to {church_name}!&#10;&#10;Thank you for subscribing, {name}. You'll receive updates about upcoming events, sermons, and more.&#10;&#10;Available variables: {name}, {church_name}, {email}" />
                            )}
                        </div>
                    </div>

                    {/* Email Signature */}
                    <div className="bg-white rounded-xl shadow-sm border p-6 mb-6">
                        <SectionHeader
                            icon={<svg className="w-5 h-5 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z" /></svg>}
                            title="Email Signature"
                            description="Appended to all outgoing emails"
                        />
                        <FormField label="Signature" name="email_signature" type="textarea" value={emailForm.email_signature} onChange={handleEmailChange} rows={4} placeholder="Blessings,&#10;Your Church Name&#10;123 Main St, City, State&#10;(555) 123-4567" />
                    </div>

                    {/* Test Email */}
                    <div className="bg-white rounded-xl shadow-sm border p-6 mb-6">
                        <SectionHeader
                            icon={<svg className="w-5 h-5 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>}
                            title="Test Email"
                            description="Send a test email to verify your configuration"
                        />
                        <div className="flex items-end gap-3">
                            <div className="flex-1">
                                <FormField label="Test Recipient Email" name="test_email" type="email" value={testEmailAddress} onChange={(e) => setTestEmailAddress(e.target.value)} placeholder="admin@yourchurch.com" />
                            </div>
                            <button
                                type="button"
                                onClick={handleTestEmail}
                                disabled={testingSend}
                                className="px-4 py-2.5 mb-4 bg-green-600 text-white rounded-lg text-sm font-medium hover:bg-green-700 disabled:opacity-50 whitespace-nowrap"
                            >
                                {testingSend ? 'Sending...' : 'Send Test Email'}
                            </button>
                        </div>
                    </div>

                    {/* Save Email Settings */}
                    <div className="flex justify-end pb-4">
                        <button
                            onClick={handleEmailSubmit}
                            disabled={emailSaving}
                            className="px-6 py-2.5 bg-indigo-600 text-white rounded-lg text-sm font-medium hover:bg-indigo-700 disabled:opacity-50 disabled:cursor-not-allowed flex items-center gap-2"
                        >
                            {emailSaving && (
                                <svg className="animate-spin h-4 w-4 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                    <circle className="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" strokeWidth="4"></circle>
                                    <path className="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
                                </svg>
                            )}
                            {emailSaving ? 'Saving...' : 'Save Email Settings'}
                        </button>
                    </div>
                </div>
            )}

            {/* GENERAL SETTINGS TAB */}
            {activeTab === 'general' && (<div>
            <div className="flex justify-end mb-4">
                <button
                    onClick={handleSubmit}
                    disabled={saving}
                    className="px-5 py-2 bg-indigo-600 text-white rounded-lg text-sm font-medium hover:bg-indigo-700 disabled:opacity-50 disabled:cursor-not-allowed flex items-center gap-2"
                >
                    {saving && (
                        <svg className="animate-spin h-4 w-4 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                            <circle className="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" strokeWidth="4"></circle>
                            <path className="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
                        </svg>
                    )}
                    {saving ? 'Saving...' : 'Save Settings'}
                </button>
            </div>
            <Alert {...alert} onClose={() => setAlert(null)} />

            <form onSubmit={handleSubmit} className="space-y-8">

                {/* Church Info Section */}
                <div className="bg-white rounded-xl shadow-sm border p-6">
                    <SectionHeader
                        icon={
                            <svg className="w-5 h-5 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" />
                            </svg>
                        }
                        title="Church Information"
                        description="Basic details about your church"
                    />
                    <div className="grid grid-cols-1 md:grid-cols-2 gap-x-6">
                        <FormField label="Church Name" name="church_name" value={form.church_name} onChange={handleChange} required placeholder="Enter church name" />
                        <FormField label="Tagline" name="tagline" value={form.tagline} onChange={handleChange} placeholder="A short tagline" />
                    </div>
                    <FormField label="Description" name="description" type="textarea" value={form.description} onChange={handleChange} placeholder="Brief description of your church" rows={3} />
                    <div className="grid grid-cols-1 md:grid-cols-2 gap-x-6">
                        <FormField label="Email" name="email" type="email" value={form.email} onChange={handleChange} placeholder="contact@church.com" />
                        <FormField label="Phone" name="phone" value={form.phone} onChange={handleChange} placeholder="(555) 123-4567" />
                    </div>
                    <FormField label="Address" name="address" value={form.address} onChange={handleChange} placeholder="Street address" />
                    <div className="grid grid-cols-2 md:grid-cols-4 gap-x-6">
                        <FormField label="City" name="city" value={form.city} onChange={handleChange} placeholder="City" />
                        <FormField label="State" name="state" value={form.state} onChange={handleChange} placeholder="State" />
                        <FormField label="Zip Code" name="zip_code" value={form.zip_code} onChange={handleChange} placeholder="Zip" />
                        <FormField label="Country" name="country" value={form.country} onChange={handleChange} placeholder="Country" />
                    </div>
                </div>

                {/* Leadership Section */}
                <div className="bg-white rounded-xl shadow-sm border p-6">
                    <SectionHeader
                        icon={
                            <svg className="w-5 h-5 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                            </svg>
                        }
                        title="Leadership"
                        description="Pastor and leadership information"
                    />
                    <div className="grid grid-cols-1 md:grid-cols-2 gap-x-6">
                        <FormField label="Pastor Name" name="pastor_name" value={form.pastor_name} onChange={handleChange} placeholder="Pastor's full name" />
                        <FormField label="Pastor Title" name="pastor_title" value={form.pastor_title} onChange={handleChange} placeholder="e.g. Senior Pastor" />
                    </div>
                </div>

                {/* Mission Section */}
                <div className="bg-white rounded-xl shadow-sm border p-6">
                    <SectionHeader
                        icon={
                            <svg className="w-5 h-5 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M11.049 2.927c.3-.921 1.603-.921 1.902 0l1.519 4.674a1 1 0 00.95.69h4.915c.969 0 1.371 1.24.588 1.81l-3.976 2.888a1 1 0 00-.363 1.118l1.518 4.674c.3.922-.755 1.688-1.538 1.118l-3.976-2.888a1 1 0 00-1.176 0l-3.976 2.888c-.783.57-1.838-.197-1.538-1.118l1.518-4.674a1 1 0 00-.363-1.118l-3.976-2.888c-.784-.57-.38-1.81.588-1.81h4.914a1 1 0 00.951-.69l1.519-4.674z" />
                            </svg>
                        }
                        title="Mission & Vision"
                        description="Your church's mission, vision, and about information"
                    />
                    <FormField label="Mission Statement" name="mission_statement" type="textarea" value={form.mission_statement} onChange={handleChange} placeholder="What is your church's mission?" rows={3} />
                    <FormField label="Vision Statement" name="vision_statement" type="textarea" value={form.vision_statement} onChange={handleChange} placeholder="What is your church's vision?" rows={3} />
                    <FormField label="About Text" name="about_text" type="textarea" value={form.about_text} onChange={handleChange} placeholder="Tell visitors about your church..." rows={4} />
                </div>

                {/* Social Media Section */}
                <div className="bg-white rounded-xl shadow-sm border p-6">
                    <SectionHeader
                        icon={
                            <svg className="w-5 h-5 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M13.828 10.172a4 4 0 00-5.656 0l-4 4a4 4 0 105.656 5.656l1.102-1.101m-.758-4.899a4 4 0 005.656 0l4-4a4 4 0 00-5.656-5.656l-1.1 1.1" />
                            </svg>
                        }
                        title="Social Media"
                        description="Links to your social media profiles"
                    />
                    <div className="grid grid-cols-1 md:grid-cols-2 gap-x-6">
                        <FormField label="Facebook URL" name="facebook_url" type="url" value={form.facebook_url} onChange={handleChange} placeholder="https://facebook.com/yourchurch" />
                        <FormField label="Twitter / X URL" name="twitter_url" type="url" value={form.twitter_url} onChange={handleChange} placeholder="https://twitter.com/yourchurch" />
                        <FormField label="Instagram URL" name="instagram_url" type="url" value={form.instagram_url} onChange={handleChange} placeholder="https://instagram.com/yourchurch" />
                        <FormField label="YouTube URL" name="youtube_url" type="url" value={form.youtube_url} onChange={handleChange} placeholder="https://youtube.com/@yourchurch" />
                        <FormField label="TikTok URL" name="tiktok_url" type="url" value={form.tiktok_url} onChange={handleChange} placeholder="https://tiktok.com/@yourchurch" />
                    </div>
                </div>

                {/* Appearance Section */}
                <div className="bg-white rounded-xl shadow-sm border p-6">
                    <SectionHeader
                        icon={
                            <svg className="w-5 h-5 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M7 21a4 4 0 01-4-4V5a2 2 0 012-2h4a2 2 0 012 2v12a4 4 0 01-4 4zm0 0h12a2 2 0 002-2v-4a2 2 0 00-2-2h-2.343M11 7.343l1.657-1.657a2 2 0 012.828 0l2.829 2.829a2 2 0 010 2.828l-8.486 8.485M7 17h.01" />
                            </svg>
                        }
                        title="Appearance"
                        description="Customize colors and branding"
                    />
                    <div className="grid grid-cols-1 md:grid-cols-2 gap-x-6">
                        <div className="mb-4">
                            <label htmlFor="field-primary_color" className="block text-sm font-medium text-gray-700 mb-1">Primary Color</label>
                            <div className="flex items-center gap-3">
                                <input
                                    id="field-primary_color"
                                    type="color"
                                    name="primary_color"
                                    value={form.primary_color || '#4f46e5'}
                                    onChange={handleChange}
                                    className="h-10 w-14 rounded-lg border border-gray-300 cursor-pointer p-0.5"
                                />
                                <input
                                    type="text"
                                    value={form.primary_color || '#4f46e5'}
                                    onChange={(e) => setForm({ ...form, primary_color: e.target.value })}
                                    className="w-full rounded-lg border border-gray-300 px-4 py-2.5 focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 outline-none text-sm"
                                    placeholder="#4f46e5"
                                />
                            </div>
                        </div>
                        <div className="mb-4">
                            <label htmlFor="field-secondary_color" className="block text-sm font-medium text-gray-700 mb-1">Secondary Color</label>
                            <div className="flex items-center gap-3">
                                <input
                                    id="field-secondary_color"
                                    type="color"
                                    name="secondary_color"
                                    value={form.secondary_color || '#7c3aed'}
                                    onChange={handleChange}
                                    className="h-10 w-14 rounded-lg border border-gray-300 cursor-pointer p-0.5"
                                />
                                <input
                                    type="text"
                                    value={form.secondary_color || '#7c3aed'}
                                    onChange={(e) => setForm({ ...form, secondary_color: e.target.value })}
                                    className="w-full rounded-lg border border-gray-300 px-4 py-2.5 focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 outline-none text-sm"
                                    placeholder="#7c3aed"
                                />
                            </div>
                        </div>
                    </div>
                    <FormField label="Footer Text" name="footer_text" value={form.footer_text} onChange={handleChange} placeholder="e.g. &copy; 2026 Your Church. All rights reserved." />
                </div>

                {/* SEO Section */}
                <div className="bg-white rounded-xl shadow-sm border p-6">
                    <SectionHeader
                        icon={
                            <svg className="w-5 h-5 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                            </svg>
                        }
                        title="SEO"
                        description="Search engine optimization settings"
                    />
                    <FormField label="Meta Title" name="meta_title" value={form.meta_title} onChange={handleChange} placeholder="Page title for search engines" />
                    <FormField label="Meta Description" name="meta_description" type="textarea" value={form.meta_description} onChange={handleChange} placeholder="Brief description for search results" rows={2} />
                    <FormField label="Meta Keywords" name="meta_keywords" value={form.meta_keywords} onChange={handleChange} placeholder="keyword1, keyword2, keyword3" />
                </div>

                {/* Service Times Section */}
                <div className="bg-white rounded-xl shadow-sm border p-6">
                    <SectionHeader
                        icon={
                            <svg className="w-5 h-5 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                        }
                        title="Service Times"
                        description="When your church holds services"
                    />
                    <FormField label="Service Times" name="service_times" type="textarea" value={form.service_times} onChange={handleChange} placeholder="Sunday: 9:00 AM, 11:00 AM&#10;Wednesday: 7:00 PM" rows={4} />
                </div>

                {/* File Uploads Section */}
                <div className="bg-white rounded-xl shadow-sm border p-6">
                    <SectionHeader
                        icon={
                            <svg className="w-5 h-5 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z" />
                            </svg>
                        }
                        title="Files & Media"
                        description="Upload your logo, banner, and favicon"
                    />
                    <div className="grid grid-cols-1 md:grid-cols-3 gap-6">
                        <div>
                            <FormField label="Logo" name="logo" type="file" onChange={handleFileChange} />
                            {previews.logo && (
                                <div className="mt-2 p-2 border border-gray-200 rounded-lg bg-gray-50">
                                    <img src={previews.logo} alt="Logo preview" className="h-16 object-contain mx-auto" />
                                </div>
                            )}
                        </div>
                        <div>
                            <FormField label="Banner" name="banner" type="file" onChange={handleFileChange} />
                            {previews.banner && (
                                <div className="mt-2 p-2 border border-gray-200 rounded-lg bg-gray-50">
                                    <img src={previews.banner} alt="Banner preview" className="h-16 object-contain mx-auto" />
                                </div>
                            )}
                        </div>
                        <div>
                            <FormField label="Favicon" name="favicon" type="file" onChange={handleFileChange} />
                            {previews.favicon && (
                                <div className="mt-2 p-2 border border-gray-200 rounded-lg bg-gray-50">
                                    <img src={previews.favicon} alt="Favicon preview" className="h-16 object-contain mx-auto" />
                                </div>
                            )}
                        </div>
                    </div>
                </div>

                {/* Submit */}
                <div className="flex justify-end pb-4">
                    <button
                        type="submit"
                        disabled={saving}
                        className="px-6 py-2.5 bg-indigo-600 text-white rounded-lg text-sm font-medium hover:bg-indigo-700 disabled:opacity-50 disabled:cursor-not-allowed flex items-center gap-2"
                    >
                        {saving && (
                            <svg className="animate-spin h-4 w-4 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                <circle className="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" strokeWidth="4"></circle>
                                <path className="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
                            </svg>
                        )}
                        {saving ? 'Saving...' : 'Save Settings'}
                    </button>
                </div>
            </form>
            </div>)}

            {/* AUTHENTICATION TAB */}
            {activeTab === 'authentication' && (
                <div>
                    <Alert {...alert} onClose={() => setAlert(null)} />
                    <div className="bg-white rounded-xl shadow-sm border p-6 mb-6">
                        <SectionHeader
                            icon={<svg className="w-5 h-5 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M15 7a2 2 0 012 2m4 0a6 6 0 01-7.743 5.743L11 17H9v2H7v2H4a1 1 0 01-1-1v-2.586a1 1 0 01.293-.707l5.964-5.964A6 6 0 1121 9z" /></svg>}
                            title="Google Login"
                            description="Allow users to sign in with their Google account"
                        />
                        <label className="flex items-center gap-3 mb-4 cursor-pointer">
                            <input type="checkbox" checked={authForm.auth_google_enabled} onChange={(e) => setAuthForm(prev => ({ ...prev, auth_google_enabled: e.target.checked }))} className="w-4 h-4 text-indigo-600 rounded border-gray-300" />
                            <span className="text-sm font-medium text-gray-700">Enable Google Login</span>
                        </label>
                        {authForm.auth_google_enabled && (
                            <div className="grid grid-cols-1 md:grid-cols-2 gap-x-6">
                                <FormField label="Google Client ID" name="auth_google_client_id" value={authForm.auth_google_client_id} onChange={(e) => setAuthForm(prev => ({ ...prev, auth_google_client_id: e.target.value }))} placeholder="Your Google OAuth Client ID" />
                                <FormField label="Google Client Secret" name="auth_google_client_secret" type="password" value={authForm.auth_google_client_secret} onChange={(e) => setAuthForm(prev => ({ ...prev, auth_google_client_secret: e.target.value }))} placeholder="Leave blank to keep current" />
                            </div>
                        )}
                    </div>

                    <div className="bg-white rounded-xl shadow-sm border p-6 mb-6">
                        <SectionHeader
                            icon={<svg className="w-5 h-5 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z" /></svg>}
                            title="Facebook Login"
                            description="Allow users to sign in with their Facebook account"
                        />
                        <label className="flex items-center gap-3 mb-4 cursor-pointer">
                            <input type="checkbox" checked={authForm.auth_facebook_enabled} onChange={(e) => setAuthForm(prev => ({ ...prev, auth_facebook_enabled: e.target.checked }))} className="w-4 h-4 text-indigo-600 rounded border-gray-300" />
                            <span className="text-sm font-medium text-gray-700">Enable Facebook Login</span>
                        </label>
                        {authForm.auth_facebook_enabled && (
                            <div className="grid grid-cols-1 md:grid-cols-2 gap-x-6">
                                <FormField label="Facebook App ID" name="auth_facebook_client_id" value={authForm.auth_facebook_client_id} onChange={(e) => setAuthForm(prev => ({ ...prev, auth_facebook_client_id: e.target.value }))} placeholder="Your Facebook App ID" />
                                <FormField label="Facebook App Secret" name="auth_facebook_client_secret" type="password" value={authForm.auth_facebook_client_secret} onChange={(e) => setAuthForm(prev => ({ ...prev, auth_facebook_client_secret: e.target.value }))} placeholder="Leave blank to keep current" />
                            </div>
                        )}
                    </div>

                    <div className="flex justify-end pb-4">
                        <button onClick={handleSaveAuth} disabled={authSaving} className="px-6 py-2.5 bg-indigo-600 text-white rounded-lg text-sm font-medium hover:bg-indigo-700 disabled:opacity-50">
                            {authSaving ? 'Saving...' : 'Save Auth Settings'}
                        </button>
                    </div>
                </div>
            )}

            {/* STORAGE / UPLOADING TAB */}
            {activeTab === 'storage' && (
                <div>
                    <Alert {...alert} onClose={() => setAlert(null)} />
                    <div className="bg-white rounded-xl shadow-sm border p-6 mb-6">
                        <SectionHeader
                            icon={<svg className="w-5 h-5 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12" /></svg>}
                            title="File Storage"
                            description="Configure where uploaded files are stored"
                        />
                        <FormField label="Storage Driver" name="storage_driver" type="select" value={storageForm.storage_driver} onChange={(e) => setStorageForm(prev => ({ ...prev, storage_driver: e.target.value }))} options={[
                            { value: 'local', label: 'Local Disk (Default)' },
                            { value: 's3', label: 'Amazon S3 / S3-Compatible' },
                        ]} />

                        {storageForm.storage_driver === 's3' && (
                            <div className="grid grid-cols-1 md:grid-cols-2 gap-x-6 mt-4">
                                <FormField label="S3 Access Key" name="storage_s3_key" value={storageForm.storage_s3_key} onChange={(e) => setStorageForm(prev => ({ ...prev, storage_s3_key: e.target.value }))} placeholder="AKIAIOSFODNN7EXAMPLE" />
                                <FormField label="S3 Secret Key" name="storage_s3_secret" type="password" value={storageForm.storage_s3_secret} onChange={(e) => setStorageForm(prev => ({ ...prev, storage_s3_secret: e.target.value }))} placeholder="Leave blank to keep current" />
                                <FormField label="S3 Region" name="storage_s3_region" value={storageForm.storage_s3_region} onChange={(e) => setStorageForm(prev => ({ ...prev, storage_s3_region: e.target.value }))} placeholder="us-east-1" />
                                <FormField label="S3 Bucket Name" name="storage_s3_bucket" value={storageForm.storage_s3_bucket} onChange={(e) => setStorageForm(prev => ({ ...prev, storage_s3_bucket: e.target.value }))} placeholder="my-church-uploads" />
                            </div>
                        )}
                    </div>

                    <div className="bg-white rounded-xl shadow-sm border p-6 mb-6">
                        <SectionHeader
                            icon={<svg className="w-5 h-5 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" /></svg>}
                            title="Upload Limits"
                            description="Control what files can be uploaded"
                        />
                        <div className="grid grid-cols-1 md:grid-cols-2 gap-x-6">
                            <FormField label="Max Upload Size (MB)" name="max_upload_size" type="number" value={storageForm.max_upload_size} onChange={(e) => setStorageForm(prev => ({ ...prev, max_upload_size: parseInt(e.target.value) || 10 }))} />
                            <FormField label="Allowed File Types" name="allowed_file_types" value={storageForm.allowed_file_types} onChange={(e) => setStorageForm(prev => ({ ...prev, allowed_file_types: e.target.value }))} placeholder="jpg,jpeg,png,gif,pdf,mp3" />
                        </div>
                    </div>

                    <div className="flex justify-end pb-4">
                        <button onClick={handleSaveStorage} disabled={storageSaving} className="px-6 py-2.5 bg-indigo-600 text-white rounded-lg text-sm font-medium hover:bg-indigo-700 disabled:opacity-50">
                            {storageSaving ? 'Saving...' : 'Save Storage Settings'}
                        </button>
                    </div>
                </div>
            )}

            {/* CACHE / PERFORMANCE TAB */}
            {activeTab === 'cache' && (
                <div>
                    <Alert {...alert} onClose={() => setAlert(null)} />
                    <div className="bg-white rounded-xl shadow-sm border p-6 mb-6">
                        <SectionHeader
                            icon={<svg className="w-5 h-5 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M13 10V3L4 14h7v7l9-11h-7z" /></svg>}
                            title="Cache Configuration"
                            description="Configure caching for better performance"
                        />
                        <FormField label="Cache Driver" name="cache_driver" type="select" value={cacheForm.cache_driver} onChange={(e) => setCacheForm(prev => ({ ...prev, cache_driver: e.target.value }))} options={[
                            { value: 'file', label: 'File (Default)' },
                            { value: 'database', label: 'Database' },
                            { value: 'redis', label: 'Redis' },
                            { value: 'memcached', label: 'Memcached' },
                        ]} />
                        <FormField label="Cache TTL (seconds)" name="cache_ttl" type="number" value={cacheForm.cache_ttl} onChange={(e) => setCacheForm(prev => ({ ...prev, cache_ttl: parseInt(e.target.value) || 3600 }))} />
                    </div>

                    <div className="bg-white rounded-xl shadow-sm border p-6 mb-6">
                        <SectionHeader
                            icon={<svg className="w-5 h-5 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M12 6V4m0 2a2 2 0 100 4m0-4a2 2 0 110 4m-6 8a2 2 0 100-4m0 4a2 2 0 110-4m0 4v2m0-6V4m6 6v10m6-2a2 2 0 100-4m0 4a2 2 0 110-4m0 4v2m0-6V4" /></svg>}
                            title="Performance Optimization"
                            description="Additional performance settings"
                        />
                        <div className="space-y-3">
                            <label className="flex items-center gap-3 cursor-pointer">
                                <input type="checkbox" checked={cacheForm.enable_page_cache} onChange={(e) => setCacheForm(prev => ({ ...prev, enable_page_cache: e.target.checked }))} className="w-4 h-4 text-indigo-600 rounded border-gray-300" />
                                <div>
                                    <span className="text-sm font-medium text-gray-700">Enable Page Cache</span>
                                    <p className="text-xs text-gray-500">Cache full HTML pages for faster load times</p>
                                </div>
                            </label>
                            <label className="flex items-center gap-3 cursor-pointer">
                                <input type="checkbox" checked={cacheForm.enable_minification} onChange={(e) => setCacheForm(prev => ({ ...prev, enable_minification: e.target.checked }))} className="w-4 h-4 text-indigo-600 rounded border-gray-300" />
                                <div>
                                    <span className="text-sm font-medium text-gray-700">Enable Asset Minification</span>
                                    <p className="text-xs text-gray-500">Minify CSS and JS for smaller file sizes</p>
                                </div>
                            </label>
                        </div>
                        <div className="mt-4">
                            <FormField label="CDN URL (optional)" name="cdn_url" value={cacheForm.cdn_url} onChange={(e) => setCacheForm(prev => ({ ...prev, cdn_url: e.target.value }))} placeholder="https://cdn.yourdomain.com" />
                        </div>
                    </div>

                    {/* Clear Cache Section */}
                    <div className="bg-white rounded-xl shadow-sm border p-6 mb-6">
                        <SectionHeader
                            icon={<svg className="w-5 h-5 text-red-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" /></svg>}
                            title="Clear Cache"
                            description="Clear all application caches to refresh content and settings"
                        />
                        <div className="space-y-3">
                            <p className="text-sm text-gray-600">
                                This will clear all cached data including page cache, configuration cache, route cache, and compiled views. Use this if you see stale content or after making configuration changes.
                            </p>
                            <div className="flex gap-3">
                                <button
                                    onClick={async () => {
                                        try {
                                            setAlert(null);
                                            const res = await post('/api/system/clear-cache', {});
                                            setAlert({ type: 'success', message: res.message || 'All caches cleared successfully!' });
                                        } catch (e) {
                                            setAlert({ type: 'error', message: e.message || 'Failed to clear cache.' });
                                        }
                                    }}
                                    className="px-5 py-2.5 bg-red-600 text-white rounded-lg text-sm font-medium hover:bg-red-700 flex items-center gap-2"
                                >
                                    <i className="fas fa-trash-alt text-xs"></i>
                                    Clear All Caches
                                </button>
                                <button
                                    onClick={async () => {
                                        try {
                                            setAlert(null);
                                            const res = await post('/api/system/optimize', {});
                                            setAlert({ type: 'success', message: res.message || 'Application optimized successfully!' });
                                        } catch (e) {
                                            setAlert({ type: 'error', message: e.message || 'Failed to optimize.' });
                                        }
                                    }}
                                    className="px-5 py-2.5 bg-green-600 text-white rounded-lg text-sm font-medium hover:bg-green-700 flex items-center gap-2"
                                >
                                    <i className="fas fa-rocket text-xs"></i>
                                    Optimize &amp; Rebuild Cache
                                </button>
                            </div>
                        </div>
                    </div>

                    <div className="flex justify-end pb-4">
                        <button onClick={handleSaveCache} disabled={cacheSaving} className="px-6 py-2.5 bg-indigo-600 text-white rounded-lg text-sm font-medium hover:bg-indigo-700 disabled:opacity-50">
                            {cacheSaving ? 'Saving...' : 'Save Cache Settings'}
                        </button>
                    </div>
                </div>
            )}

            {/* LOGGING & QUEUE TAB */}
            {activeTab === 'logging' && (
                <div>
                    <Alert {...alert} onClose={() => setAlert(null)} />
                    <div className="bg-white rounded-xl shadow-sm border p-6 mb-6">
                        <SectionHeader
                            icon={<svg className="w-5 h-5 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2" /></svg>}
                            title="Logging"
                            description="Configure application error logging"
                        />
                        <FormField label="Log Channel" name="log_channel" type="select" value={loggingForm.log_channel} onChange={(e) => setLoggingForm(prev => ({ ...prev, log_channel: e.target.value }))} options={[
                            { value: 'daily', label: 'Daily Files (Recommended)' },
                            { value: 'single', label: 'Single File' },
                            { value: 'stack', label: 'Stack (Multiple Channels)' },
                            { value: 'syslog', label: 'System Log' },
                            { value: 'errorlog', label: 'Error Log' },
                        ]} />
                    </div>

                    <div className="bg-white rounded-xl shadow-sm border p-6 mb-6">
                        <SectionHeader
                            icon={<svg className="w-5 h-5 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10" /></svg>}
                            title="Queue"
                            description="Configure background job processing"
                        />
                        <FormField label="Queue Driver" name="queue_driver" type="select" value={loggingForm.queue_driver} onChange={(e) => setLoggingForm(prev => ({ ...prev, queue_driver: e.target.value }))} options={[
                            { value: 'sync', label: 'Synchronous (Default - No Queue)' },
                            { value: 'database', label: 'Database' },
                            { value: 'redis', label: 'Redis' },
                        ]} />
                        <p className="text-xs text-gray-500 mt-2">
                            <i className="fas fa-info-circle mr-1"></i>
                            Queue processing is used for sending emails and newsletters in the background.
                            If using Database or Redis, make sure to run <code className="bg-gray-100 px-1 rounded">php artisan queue:work</code>.
                        </p>
                    </div>

                    <div className="flex justify-end pb-4">
                        <button onClick={handleSaveLogging} disabled={loggingSaving} className="px-6 py-2.5 bg-indigo-600 text-white rounded-lg text-sm font-medium hover:bg-indigo-700 disabled:opacity-50">
                            {loggingSaving ? 'Saving...' : 'Save Logging & Queue Settings'}
                        </button>
                    </div>
                </div>
            )}

            {/* USER FIELDS TAB */}
            {activeTab === 'userfields' && (
                <div>
                    <Alert {...profileFieldsAlert} onClose={() => setProfileFieldsAlert(null)} />

                    <div className="bg-white rounded-xl shadow-sm border p-6 mb-6">
                        <SectionHeader
                            icon={<svg className="w-5 h-5 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" /></svg>}
                            title="Custom User Profile Fields"
                            description="Configure which fields users must fill during registration and in their profile. Enable a field to show it, and mark it as required to make it compulsory."
                        />

                        <div className="space-y-4">
                            {profileFields.map((field, index) => {
                                const isBuiltIn = ['phone', 'church_name', 'social_id', 'spiritual_background'].includes(field.key);
                                return (
                                    <div key={field.key} className={`border rounded-lg p-4 ${field.enabled ? 'border-indigo-200 bg-indigo-50/30' : 'border-gray-200 bg-gray-50/30'}`}>
                                        <div className="flex items-center justify-between mb-3">
                                            <div className="flex items-center gap-3">
                                                <label className="relative inline-flex items-center cursor-pointer">
                                                    <input type="checkbox" checked={field.enabled} onChange={(e) => handleProfileFieldChange(index, 'enabled', e.target.checked)} className="sr-only peer" />
                                                    <div className="w-9 h-5 bg-gray-200 peer-focus:outline-none rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-4 after:w-4 after:transition-all peer-checked:bg-indigo-600"></div>
                                                </label>
                                                {isBuiltIn ? (
                                                    <span className="font-medium text-gray-900">{field.label}</span>
                                                ) : (
                                                    <input
                                                        type="text" value={field.label} placeholder="Field label"
                                                        onChange={(e) => handleProfileFieldChange(index, 'label', e.target.value)}
                                                        className="font-medium text-gray-900 border border-gray-300 rounded px-2 py-1 text-sm"
                                                    />
                                                )}
                                                {isBuiltIn && <span className="text-xs bg-gray-200 text-gray-600 px-2 py-0.5 rounded-full">Built-in</span>}
                                            </div>
                                            <div className="flex items-center gap-3">
                                                <label className="flex items-center gap-2 text-sm">
                                                    <input type="checkbox" checked={field.required} onChange={(e) => handleProfileFieldChange(index, 'required', e.target.checked)} className="rounded border-gray-300 text-indigo-600 focus:ring-indigo-500" />
                                                    <span className={field.required ? 'text-red-600 font-medium' : 'text-gray-500'}>Required</span>
                                                </label>
                                                {!isBuiltIn && (
                                                    <button onClick={() => removeCustomField(index)} className="text-red-400 hover:text-red-600 text-sm" title="Remove field">
                                                        <svg className="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" /></svg>
                                                    </button>
                                                )}
                                            </div>
                                        </div>

                                        {field.enabled && (
                                            <div className="grid grid-cols-1 md:grid-cols-3 gap-3 mt-3 pl-12">
                                                <div>
                                                    <label className="block text-xs text-gray-500 mb-1">Field Type</label>
                                                    <select value={field.type} onChange={(e) => handleProfileFieldChange(index, 'type', e.target.value)}
                                                        className="w-full border border-gray-300 rounded-md px-2 py-1.5 text-sm" disabled={isBuiltIn}>
                                                        <option value="text">Text</option>
                                                        <option value="textarea">Textarea</option>
                                                        <option value="email">Email</option>
                                                        <option value="tel">Phone</option>
                                                        <option value="url">URL</option>
                                                        <option value="select">Dropdown</option>
                                                    </select>
                                                </div>
                                                <div>
                                                    <label className="block text-xs text-gray-500 mb-1">Placeholder</label>
                                                    <input type="text" value={field.placeholder || ''} onChange={(e) => handleProfileFieldChange(index, 'placeholder', e.target.value)}
                                                        className="w-full border border-gray-300 rounded-md px-2 py-1.5 text-sm" placeholder="Placeholder text" />
                                                </div>
                                                {field.type === 'select' && (
                                                    <div>
                                                        <label className="block text-xs text-gray-500 mb-1">Options (comma-separated)</label>
                                                        <input type="text" value={field.options || ''} onChange={(e) => handleProfileFieldChange(index, 'options', e.target.value)}
                                                            className="w-full border border-gray-300 rounded-md px-2 py-1.5 text-sm" placeholder="Option 1, Option 2, Option 3" />
                                                    </div>
                                                )}
                                            </div>
                                        )}
                                    </div>
                                );
                            })}
                        </div>

                        <div className="mt-4 pt-4 border-t border-gray-200">
                            <button onClick={addCustomField} className="text-sm text-indigo-600 hover:text-indigo-800 font-medium flex items-center gap-1">
                                <svg className="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M12 6v6m0 0v6m0-6h6m-6 0H6" /></svg>
                                Add Custom Field
                            </button>
                        </div>
                    </div>

                    <div className="flex justify-end pb-4">
                        <button onClick={handleSaveProfileFields} disabled={profileFieldsSaving} className="px-6 py-2.5 bg-indigo-600 text-white rounded-lg text-sm font-medium hover:bg-indigo-700 disabled:opacity-50">
                            {profileFieldsSaving ? 'Saving...' : 'Save User Fields'}
                        </button>
                    </div>
                </div>
            )}
        </div>
    );
}
