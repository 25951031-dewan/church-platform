import { useState, useEffect } from 'react';
import { useMutation, useQuery, useQueryClient } from '@tanstack/react-query';
import { apiClient } from '@app/common/http/api-client';
import { CheckCircle, Upload, AlertCircle } from 'lucide-react';

interface Settings { [key: string]: string }

function Field({
  label, name, value, onChange, type = 'text', placeholder, rows,
}: {
  label: string; name: string; value: string; onChange: (v: string) => void;
  type?: string; placeholder?: string; rows?: number;
}) {
  return (
    <div className="mb-4">
      <label htmlFor={name} className="block text-sm font-medium text-gray-300 mb-1.5">{label}</label>
      {rows ? (
        <textarea id={name} rows={rows} value={value} onChange={e => onChange(e.target.value)}
          placeholder={placeholder}
          className="w-full px-3 py-2 bg-[#0C0E12] border border-white/10 rounded-lg text-white text-sm placeholder-gray-600 focus:outline-none focus:border-indigo-500 resize-none" />
      ) : (
        <input id={name} type={type} value={value} onChange={e => onChange(e.target.value)}
          placeholder={placeholder}
          className="w-full px-3 py-2 bg-[#0C0E12] border border-white/10 rounded-lg text-white text-sm placeholder-gray-600 focus:outline-none focus:border-indigo-500" />
      )}
    </div>
  );
}

function ImageUploader({
  label, value, onChange, description,
}: {
  label: string; value: string; onChange: (url: string) => void; description?: string;
}) {
  const [uploading, setUploading] = useState(false);

  const handleFileChange = async (e: React.ChangeEvent<HTMLInputElement>) => {
    const file = e.target.files?.[0];
    if (!file) return;

    setUploading(true);
    const formData = new FormData();
    formData.append('file', file);

    try {
      const response = await apiClient.post<{ url: string }>('uploads', formData, {
        headers: { 'Content-Type': 'multipart/form-data' },
      });
      onChange(response.data.url);
    } catch (error) {
      console.error('Upload failed:', error);
    } finally {
      setUploading(false);
    }
  };

  return (
    <div className="mb-4">
      <label className="block text-sm font-medium text-gray-300 mb-1.5">{label}</label>
      {description && <p className="text-xs text-gray-500 mb-2">{description}</p>}
      <div className="flex items-center gap-3">
        {value && (
          <div className="w-20 h-20 bg-[#0C0E12] border border-white/10 rounded-lg flex items-center justify-center overflow-hidden">
            <img src={value} alt={label} className="max-w-full max-h-full object-contain" />
          </div>
        )}
        <label className="cursor-pointer">
          <input type="file" accept="image/*" onChange={handleFileChange} className="hidden" disabled={uploading} />
          <div className="px-4 py-2 bg-[#0C0E12] border border-white/10 rounded-lg text-sm text-gray-300 hover:border-indigo-500 transition-colors flex items-center gap-2">
            <Upload size={14} />
            {uploading ? 'Uploading...' : value ? 'Change' : 'Upload'}
          </div>
        </label>
        {value && (
          <button
            type="button"
            onClick={() => onChange('')}
            className="text-xs text-red-400 hover:text-red-300 transition-colors"
          >
            Remove
          </button>
        )}
      </div>
    </div>
  );
}

export function GeneralSettingsPage() {
  const qc = useQueryClient();
  const [form, setForm] = useState<Settings>({});
  const [saved, setSaved] = useState(false);
  const [generatingSitemap, setGeneratingSitemap] = useState(false);

  const { data, isLoading } = useQuery({
    queryKey: ['settings'],
    queryFn: () => apiClient.get<{ settings: Settings }>('settings').then(r => r.data.settings ?? {}),
  });

  useEffect(() => { if (data) setForm(data); }, [data]);

  const mutation = useMutation({
    mutationFn: (values: Settings) => apiClient.put('settings', { settings: values }),
    onSuccess: () => {
      qc.invalidateQueries({ queryKey: ['settings'] });
      setSaved(true);
      setTimeout(() => setSaved(false), 3000);
    },
  });

  const generateSitemap = async () => {
    setGeneratingSitemap(true);
    try {
      await apiClient.post('admin/sitemap/generate');
      alert('Sitemap generated successfully!');
    } catch (error) {
      alert('Failed to generate sitemap.');
    } finally {
      setGeneratingSitemap(false);
    }
  };

  const set = (key: string) => (val: string) => setForm(f => ({ ...f, [key]: val }));
  const v = (key: string) => form[key] ?? '';
  
  const currentUrl = typeof window !== 'undefined' ? window.location.origin : '';
  const configuredUrl = v('server.app_url') || currentUrl;
  const urlMismatch = configuredUrl !== currentUrl;

  if (isLoading) return <div className="text-gray-400 text-sm">Loading…</div>;

  return (
    <div className="max-w-2xl">
      <div className="flex items-center justify-between mb-6">
        <h2 className="text-lg font-semibold text-white">General</h2>
        <div className="flex items-center gap-3">
          {saved && <span className="flex items-center gap-1 text-xs text-green-400"><CheckCircle size={13} /> Saved</span>}
          <button type="button" onClick={() => mutation.mutate(form)} disabled={mutation.isPending}
            className="px-4 py-2 bg-indigo-600 text-white text-sm font-semibold rounded-lg hover:bg-indigo-700 disabled:opacity-50 transition-colors">
            {mutation.isPending ? 'Saving…' : 'Save changes'}
          </button>
        </div>
      </div>
      {mutation.isError && (
        <div className="mb-4 p-3 bg-red-500/10 border border-red-500/20 rounded-lg text-red-400 text-sm">Failed to save. Please try again.</div>
      )}

      {/* Site URL Section */}
      <div className="bg-[#161920] border border-white/5 rounded-xl p-5 mb-4">
        <h3 className="text-base font-semibold text-white mb-2">Site URL</h3>
        <p className="text-sm text-gray-400 mb-4">
          Base URL for your site. This is used for generating links, sitemaps, and emails.
        </p>
        <div className="p-3 bg-[#0C0E12] border border-white/10 rounded-lg">
          <p className="text-sm text-white font-mono">{currentUrl}</p>
        </div>
        {urlMismatch && (
          <div className="mt-3 p-3 bg-yellow-500/10 border border-yellow-500/20 rounded-lg flex items-start gap-2">
            <AlertCircle size={16} className="text-yellow-400 mt-0.5 flex-shrink-0" />
            <p className="text-xs text-yellow-400">
              Configured URL ({configuredUrl}) differs from current URL. Update your .env APP_URL if needed.
            </p>
          </div>
        )}
      </div>

      {/* Site Identity Section */}
      <div className="bg-[#161920] border border-white/5 rounded-xl p-5 mb-4">
        <p className="text-xs font-semibold text-gray-500 uppercase tracking-wider mb-4">Site Identity</p>
        <Field 
          label="Site Name" 
          name="server.app_name" 
          value={v('server.app_name')} 
          onChange={set('server.app_name')} 
          placeholder="My Church Platform"
        />
        <Field 
          label="Site Description" 
          name="client.branding.site_description" 
          value={v('client.branding.site_description')} 
          onChange={set('client.branding.site_description')} 
          rows={2}
          placeholder="A brief description for SEO and social media"
        />
        <div className="mb-4">
          <label className="block text-sm font-medium text-gray-300 mb-1.5">Homepage Type</label>
          <select
            value={v('client.homepage.type') || 'feedPage'}
            onChange={e => set('client.homepage.type')(e.target.value)}
            className="w-full px-3 py-2 bg-[#0C0E12] border border-white/10 rounded-lg text-white text-sm focus:outline-none focus:border-indigo-500"
          >
            <option value="feedPage">Feed / Newsfeed</option>
            <option value="landingPage">Landing Page</option>
            <option value="loginPage">Login Page</option>
          </select>
        </div>
      </div>

      {/* Branding Section */}
      <div className="bg-[#161920] border border-white/5 rounded-xl p-5 mb-4">
        <p className="text-xs font-semibold text-gray-500 uppercase tracking-wider mb-4">Branding</p>
        <ImageUploader
          label="Favicon"
          value={v('client.branding.favicon')}
          onChange={set('client.branding.favicon')}
          description="Icon shown in browser tabs (recommended: 32x32px PNG/ICO)"
        />
        <ImageUploader
          label="Logo (Light Mode)"
          value={v('client.branding.logo_light')}
          onChange={set('client.branding.logo_light')}
          description="Main logo for light backgrounds"
        />
        <ImageUploader
          label="Logo (Dark Mode)"
          value={v('client.branding.logo_dark')}
          onChange={set('client.branding.logo_dark')}
          description="Main logo for dark backgrounds (current theme)"
        />
        <div className="grid grid-cols-2 gap-4">
          <ImageUploader
            label="Compact Logo Light"
            value={v('client.branding.logo_light_mobile')}
            onChange={set('client.branding.logo_light_mobile')}
            description="For mobile/small spaces"
          />
          <ImageUploader
            label="Compact Logo Dark"
            value={v('client.branding.logo_dark_mobile')}
            onChange={set('client.branding.logo_dark_mobile')}
            description="For mobile/small spaces"
          />
        </div>
      </div>

      {/* Sitemap Section */}
      <div className="bg-[#161920] border border-white/5 rounded-xl p-5 mb-4">
        <h3 className="text-base font-semibold text-white mb-2">Sitemap</h3>
        <p className="text-sm text-gray-400 mb-4">
          Generate an XML sitemap for search engines.
        </p>
        <div className="flex items-center gap-3">
          <button
            type="button"
            onClick={generateSitemap}
            disabled={generatingSitemap}
            className="px-4 py-2 bg-[#0C0E12] border border-white/10 rounded-lg text-sm text-gray-300 hover:border-indigo-500 disabled:opacity-50 transition-colors"
          >
            {generatingSitemap ? 'Generating...' : 'Generate Sitemap'}
          </button>
          <a
            href={`${currentUrl}/sitemap.xml`}
            target="_blank"
            rel="noopener noreferrer"
            className="text-xs text-indigo-400 hover:text-indigo-300 underline"
          >
            View sitemap.xml
          </a>
        </div>
      </div>

      {/* Church Info Section */}
      <div className="bg-[#161920] border border-white/5 rounded-xl p-5 mb-4">
        <p className="text-xs font-semibold text-gray-500 uppercase tracking-wider mb-4">Church Info</p>
        <Field label="Church Name"   name="church_name"   value={v('church_name')}   onChange={set('church_name')} />
        <Field label="Tagline"       name="tagline"       value={v('tagline')}        onChange={set('tagline')} />
        <Field label="Description"   name="description"   value={v('description')}    onChange={set('description')} rows={3} />
        <Field label="Pastor Name"   name="pastor_name"   value={v('pastor_name')}    onChange={set('pastor_name')} />
        <Field label="Service Times" name="service_times" value={v('service_times')}  onChange={set('service_times')} placeholder="e.g. Sunday 10am, Wednesday 7pm" />
      </div>

      {/* Contact Section */}
      <div className="bg-[#161920] border border-white/5 rounded-xl p-5 mb-4">
        <p className="text-xs font-semibold text-gray-500 uppercase tracking-wider mb-4">Contact</p>
        <Field label="Contact Email" name="email"   value={v('email')}   onChange={set('email')}   type="email" />
        <Field label="Phone"         name="phone"   value={v('phone')}   onChange={set('phone')} />
        <Field label="Address"       name="address" value={v('address')} onChange={set('address')} />
        <div className="grid grid-cols-2 gap-4">
          <Field label="City"    name="city"    value={v('city')}    onChange={set('city')} />
          <Field label="Country" name="country" value={v('country')} onChange={set('country')} />
        </div>
      </div>

      {/* Social Links Section */}
      <div className="bg-[#161920] border border-white/5 rounded-xl p-5">
        <p className="text-xs font-semibold text-gray-500 uppercase tracking-wider mb-4">Social Links</p>
        <Field label="Facebook URL"  name="facebook_url"  value={v('facebook_url')}  onChange={set('facebook_url')}  type="url" placeholder="https://facebook.com/..." />
        <Field label="Instagram URL" name="instagram_url" value={v('instagram_url')} onChange={set('instagram_url')} type="url" placeholder="https://instagram.com/..." />
        <Field label="YouTube URL"   name="youtube_url"   value={v('youtube_url')}   onChange={set('youtube_url')}   type="url" placeholder="https://youtube.com/..." />
        <Field label="Website URL"   name="website_url"   value={v('website_url')}   onChange={set('website_url')}   type="url" placeholder="https://..." />
      </div>
    </div>
  );
}
