import { useState, useEffect } from 'react';
import { useMutation, useQuery, useQueryClient } from '@tanstack/react-query';
import { apiClient } from '@app/common/http/api-client';
import { Church, MapPin, User, Globe } from 'lucide-react';

interface GeneralSettings {
  church_name?: string;
  tagline?: string;
  description?: string;
  email?: string;
  phone?: string;
  address?: string;
  city?: string;
  state?: string;
  zip_code?: string;
  country?: string;
  website_url?: string;
  facebook_url?: string;
  twitter_url?: string;
  instagram_url?: string;
  youtube_url?: string;
  tiktok_url?: string;
  service_times?: string;
  pastor_name?: string;
  pastor_title?: string;
  about_text?: string;
  mission_statement?: string;
  vision_statement?: string;
  google_maps_embed?: string;
  footer_text?: string;
  meta_title?: string;
  meta_description?: string;
}

function Field({ label, name, value, onChange, type = 'text', placeholder, rows }: {
  label: string; name: string; value: string | undefined; 
  onChange: (v: string) => void; type?: string; placeholder?: string; rows?: number;
}) {
  const Tag = rows ? 'textarea' : 'input';
  return (
    <div className="mb-4">
      <label className="block text-sm font-medium text-gray-300 mb-1.5">{label}</label>
      <Tag
        type={rows ? undefined : type}
        name={name}
        value={value ?? ''}
        onChange={(e) => onChange((e.target as HTMLInputElement | HTMLTextAreaElement).value)}
        placeholder={placeholder}
        rows={rows}
        className="w-full px-3 py-2 bg-[#0C0E12] border border-white/10 rounded-lg text-white text-sm placeholder-gray-600 focus:outline-none focus:border-indigo-500 transition-colors resize-none"
      />
    </div>
  );
}

export function GeneralSettingsPage() {
  const queryClient = useQueryClient();

  const { data, isLoading } = useQuery({
    queryKey: ['settings', 'general'],
    queryFn: () => apiClient.get<{ data: GeneralSettings }>('settings').then((r) => r.data.data),
  });

  const [form, setForm] = useState<GeneralSettings>({});

  useEffect(() => {
    if (data) {
      setForm(data);
    }
  }, [data]);

  const save = useMutation({
    mutationFn: (settings: GeneralSettings) => apiClient.put('settings', settings),
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: ['settings', 'general'] });
    },
  });

  const handleSave = () => {
    save.mutate(form);
  };

  if (isLoading) {
    return <div className="text-gray-400 text-sm">Loading general settings...</div>;
  }

  return (
    <div className="max-w-3xl">
      <div className="mb-6">
        <h2 className="text-xl font-bold text-white mb-1">General Settings</h2>
        <p className="text-sm text-gray-400">Basic church information and contact details.</p>
      </div>

      {/* Church Information */}
      <div className="bg-[#161920] border border-white/5 rounded-xl p-5 mb-4">
        <h3 className="text-sm font-semibold text-white mb-4 flex items-center gap-2">
          <Church size={16} />
          Church Information
        </h3>
        <Field
          label="Church Name *"
          name="church_name"
          value={form.church_name}
          onChange={(v) => setForm({ ...form, church_name: v })}
          placeholder="Grace Community Church"
        />
        <Field
          label="Tagline"
          name="tagline"
          value={form.tagline}
          onChange={(v) => setForm({ ...form, tagline: v })}
          placeholder="Where Faith Meets Community"
        />
        <Field
          label="Description"
          name="description"
          value={form.description}
          onChange={(v) => setForm({ ...form, description: v })}
          placeholder="A brief description of your church..."
          rows={3}
        />
      </div>

      {/* Contact Information */}
      <div className="bg-[#161920] border border-white/5 rounded-xl p-5 mb-4">
        <h3 className="text-sm font-semibold text-white mb-4 flex items-center gap-2">
          <MapPin size={16} />
          Contact Information
        </h3>
        <div className="grid grid-cols-2 gap-4">
          <Field
            label="Email"
            name="email"
            type="email"
            value={form.email}
            onChange={(v) => setForm({ ...form, email: v })}
            placeholder="info@yourchurch.com"
          />
          <Field
            label="Phone"
            name="phone"
            value={form.phone}
            onChange={(v) => setForm({ ...form, phone: v })}
            placeholder="+1 (555) 123-4567"
          />
        </div>
        <Field
          label="Address"
          name="address"
          value={form.address}
          onChange={(v) => setForm({ ...form, address: v })}
          placeholder="123 Main Street"
        />
        <div className="grid grid-cols-3 gap-4">
          <Field
            label="City"
            name="city"
            value={form.city}
            onChange={(v) => setForm({ ...form, city: v })}
            placeholder="New York"
          />
          <Field
            label="State"
            name="state"
            value={form.state}
            onChange={(v) => setForm({ ...form, state: v })}
            placeholder="NY"
          />
          <Field
            label="ZIP Code"
            name="zip_code"
            value={form.zip_code}
            onChange={(v) => setForm({ ...form, zip_code: v })}
            placeholder="10001"
          />
        </div>
        <Field
          label="Country"
          name="country"
          value={form.country}
          onChange={(v) => setForm({ ...form, country: v })}
          placeholder="United States"
        />
      </div>

      {/* Social Media */}
      <div className="bg-[#161920] border border-white/5 rounded-xl p-5 mb-4">
        <h3 className="text-sm font-semibold text-white mb-4 flex items-center gap-2">
          <Globe size={16} />
          Website & Social Media
        </h3>
        <Field
          label="Website URL"
          name="website_url"
          type="url"
          value={form.website_url}
          onChange={(v) => setForm({ ...form, website_url: v })}
          placeholder="https://yourchurch.com"
        />
        <div className="grid grid-cols-2 gap-4">
          <Field
            label="Facebook URL"
            name="facebook_url"
            type="url"
            value={form.facebook_url}
            onChange={(v) => setForm({ ...form, facebook_url: v })}
            placeholder="https://facebook.com/yourchurch"
          />
          <Field
            label="Instagram URL"
            name="instagram_url"
            type="url"
            value={form.instagram_url}
            onChange={(v) => setForm({ ...form, instagram_url: v })}
            placeholder="https://instagram.com/yourchurch"
          />
          <Field
            label="YouTube URL"
            name="youtube_url"
            type="url"
            value={form.youtube_url}
            onChange={(v) => setForm({ ...form, youtube_url: v })}
            placeholder="https://youtube.com/@yourchurch"
          />
          <Field
            label="Twitter URL"
            name="twitter_url"
            type="url"
            value={form.twitter_url}
            onChange={(v) => setForm({ ...form, twitter_url: v })}
            placeholder="https://twitter.com/yourchurch"
          />
        </div>
        <Field
          label="TikTok URL"
          name="tiktok_url"
          type="url"
          value={form.tiktok_url}
          onChange={(v) => setForm({ ...form, tiktok_url: v })}
          placeholder="https://tiktok.com/@yourchurch"
        />
      </div>

      {/* Leadership & Service Times */}
      <div className="bg-[#161920] border border-white/5 rounded-xl p-5 mb-4">
        <h3 className="text-sm font-semibold text-white mb-4 flex items-center gap-2">
          <User size={16} />
          Leadership & Services
        </h3>
        <div className="grid grid-cols-2 gap-4">
          <Field
            label="Pastor Name"
            name="pastor_name"
            value={form.pastor_name}
            onChange={(v) => setForm({ ...form, pastor_name: v })}
            placeholder="John Smith"
          />
          <Field
            label="Pastor Title"
            name="pastor_title"
            value={form.pastor_title}
            onChange={(v) => setForm({ ...form, pastor_title: v })}
            placeholder="Lead Pastor"
          />
        </div>
        <Field
          label="Service Times"
          name="service_times"
          value={form.service_times}
          onChange={(v) => setForm({ ...form, service_times: v })}
          placeholder="Sunday: 9:00 AM, 11:00 AM | Wednesday: 7:00 PM"
          rows={2}
        />
      </div>

      {/* About & Mission */}
      <div className="bg-[#161920] border border-white/5 rounded-xl p-5 mb-4">
        <h3 className="text-sm font-semibold text-white mb-4">About, Mission & Vision</h3>
        <Field
          label="About Text"
          name="about_text"
          value={form.about_text}
          onChange={(v) => setForm({ ...form, about_text: v })}
          placeholder="Tell your church's story..."
          rows={4}
        />
        <Field
          label="Mission Statement"
          name="mission_statement"
          value={form.mission_statement}
          onChange={(v) => setForm({ ...form, mission_statement: v })}
          placeholder="Our mission is to..."
          rows={3}
        />
        <Field
          label="Vision Statement"
          name="vision_statement"
          value={form.vision_statement}
          onChange={(v) => setForm({ ...form, vision_statement: v })}
          placeholder="Our vision is to..."
          rows={3}
        />
      </div>

      {/* Footer & SEO */}
      <div className="bg-[#161920] border border-white/5 rounded-xl p-5 mb-4">
        <h3 className="text-sm font-semibold text-white mb-4">Footer & SEO</h3>
        <Field
          label="Footer Text"
          name="footer_text"
          value={form.footer_text}
          onChange={(v) => setForm({ ...form, footer_text: v })}
          placeholder="© 2026 Your Church. All rights reserved."
        />
        <Field
          label="Meta Title"
          name="meta_title"
          value={form.meta_title}
          onChange={(v) => setForm({ ...form, meta_title: v })}
          placeholder="Your Church - Welcome"
        />
        <Field
          label="Meta Description"
          name="meta_description"
          value={form.meta_description}
          onChange={(v) => setForm({ ...form, meta_description: v })}
          placeholder="Join us for worship and community..."
          rows={2}
        />
      </div>

      {/* Save Button */}
      <div className="flex items-center justify-between">
        <div className="text-xs text-gray-500">
          {save.isSuccess && <span className="text-green-400">✓ Settings saved successfully</span>}
          {save.isError && <span className="text-red-400">✗ Failed to save settings</span>}
        </div>
        <button
          type="button"
          onClick={handleSave}
          disabled={save.isPending}
          className="px-5 py-2.5 bg-indigo-600 hover:bg-indigo-700 text-white text-sm font-medium rounded-lg transition-colors disabled:opacity-50 disabled:cursor-not-allowed"
        >
          {save.isPending ? 'Saving...' : 'Save Settings'}
        </button>
      </div>
    </div>
  );
}
