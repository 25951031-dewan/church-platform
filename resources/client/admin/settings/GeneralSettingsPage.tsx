import { useState, useEffect } from 'react';
import { useMutation, useQuery, useQueryClient } from '@tanstack/react-query';
import { apiClient } from '@app/common/http/api-client';
import { CheckCircle } from 'lucide-react';

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

export function GeneralSettingsPage() {
  const qc = useQueryClient();
  const [form, setForm] = useState<Settings>({});
  const [saved, setSaved] = useState(false);

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

  const set = (key: string) => (val: string) => setForm(f => ({ ...f, [key]: val }));
  const v = (key: string) => form[key] ?? '';

  if (isLoading) return <div className="text-gray-400 text-sm">Loading…</div>;

  return (
    <div className="max-w-2xl">
      <div className="flex items-center justify-between mb-6">
        <h2 className="text-lg font-semibold text-white">General</h2>
        <div className="flex items-center gap-3">
          {saved && <span className="flex items-center gap-1 text-xs text-green-400"><CheckCircle size={13} /> Saved</span>}
          <button type="button" onClick={() => mutation.mutate(form)} disabled={mutation.isPending}
            className="px-4 py-2 bg-white text-gray-900 text-sm font-semibold rounded-lg hover:bg-gray-100 disabled:opacity-50 transition-colors">
            {mutation.isPending ? 'Saving…' : 'Save changes'}
          </button>
        </div>
      </div>
      {mutation.isError && (
        <div className="mb-4 p-3 bg-red-500/10 border border-red-500/20 rounded-lg text-red-400 text-sm">Failed to save. Please try again.</div>
      )}
      <div className="bg-[#161920] border border-white/5 rounded-xl p-5 mb-4">
        <p className="text-xs font-semibold text-gray-500 uppercase tracking-wider mb-4">Church Info</p>
        <Field label="Church Name"   name="church_name"   value={v('church_name')}   onChange={set('church_name')} />
        <Field label="Tagline"       name="tagline"       value={v('tagline')}        onChange={set('tagline')} />
        <Field label="Description"   name="description"   value={v('description')}    onChange={set('description')} rows={3} />
        <Field label="Pastor Name"   name="pastor_name"   value={v('pastor_name')}    onChange={set('pastor_name')} />
        <Field label="Service Times" name="service_times" value={v('service_times')}  onChange={set('service_times')} placeholder="e.g. Sunday 10am, Wednesday 7pm" />
      </div>
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
