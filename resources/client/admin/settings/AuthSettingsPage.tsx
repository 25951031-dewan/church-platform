import { useState, useEffect } from 'react';
import { useMutation, useQuery, useQueryClient } from '@tanstack/react-query';
import { apiClient } from '@app/common/http/api-client';
import { CheckCircle } from 'lucide-react';

interface Settings { [key: string]: string }

function Field({ label, name, value, onChange, placeholder }: {
  label: string; name: string; value: string; onChange: (v: string) => void; placeholder?: string;
}) {
  return (
    <div className="mb-4">
      <label htmlFor={name} className="block text-sm font-medium text-gray-300 mb-1.5">{label}</label>
      <input id={name} value={value} onChange={e => onChange(e.target.value)} placeholder={placeholder} autoComplete="off"
        className="w-full px-3 py-2 bg-[#0C0E12] border border-white/10 rounded-lg text-white text-sm placeholder-gray-600 focus:outline-none focus:border-indigo-500" />
    </div>
  );
}

function Toggle({ label, checked, onChange }: { label: string; checked: boolean; onChange: (v: boolean) => void }) {
  return (
    <label className="flex items-center justify-between py-2 cursor-pointer select-none">
      <span className="text-sm text-gray-300">{label}</span>
      <button type="button" role="switch" aria-checked={checked} onClick={() => onChange(!checked)}
        className={`relative w-11 h-6 rounded-full transition-colors ${checked ? 'bg-indigo-600' : 'bg-gray-700'}`}>
        <span className={`absolute top-0.5 left-0.5 w-5 h-5 bg-white rounded-full transition-transform ${checked ? 'translate-x-5' : 'translate-x-0'}`} />
      </button>
    </label>
  );
}

const AUTH_KEYS = [
  'auth_google_enabled', 'auth_google_client_id', 'auth_google_client_secret',
  'auth_facebook_enabled', 'auth_facebook_client_id', 'auth_facebook_client_secret',
];

export function AuthSettingsPage() {
  const qc = useQueryClient();
  const [form, setForm] = useState<Settings>({});
  const [saved, setSaved] = useState(false);

  const { data, isLoading } = useQuery({
    queryKey: ['settings'],
    queryFn: () => apiClient.get<{ settings: Settings }>('settings').then(r => r.data.settings ?? {}),
  });

  useEffect(() => {
    if (data) {
      const fields: Settings = {};
      AUTH_KEYS.forEach(k => { fields[k] = data[k] ?? ''; });
      setForm(fields);
    }
  }, [data]);

  const mutation = useMutation({
    mutationFn: (values: Settings) => apiClient.put('settings', { settings: values }),
    onSuccess: () => {
      qc.invalidateQueries({ queryKey: ['settings'] });
      setSaved(true);
      setTimeout(() => setSaved(false), 3000);
    },
  });

  const set = (key: string) => (val: string) => setForm(f => ({ ...f, [key]: val }));
  const setToggle = (key: string) => (val: boolean) => setForm(f => ({ ...f, [key]: val ? '1' : '0' }));
  const v = (key: string) => form[key] ?? '';
  const bool = (key: string) => form[key] === '1' || form[key] === 'true';

  if (isLoading) return <div className="text-gray-400 text-sm">Loading…</div>;

  return (
    <div className="max-w-2xl">
      <div className="flex items-center justify-between mb-6">
        <h2 className="text-lg font-semibold text-white">Authentication</h2>
        <div className="flex items-center gap-3">
          {saved && <span className="flex items-center gap-1 text-xs text-green-400"><CheckCircle size={13} /> Saved</span>}
          <button type="button" onClick={() => mutation.mutate(form)} disabled={mutation.isPending}
            className="px-4 py-2 bg-white text-gray-900 text-sm font-semibold rounded-lg hover:bg-gray-100 disabled:opacity-50 transition-colors">
            {mutation.isPending ? 'Saving…' : 'Save changes'}
          </button>
        </div>
      </div>
      {mutation.isError && (
        <div className="mb-4 p-3 bg-red-500/10 border border-red-500/20 rounded-lg text-red-400 text-sm">Failed to save settings.</div>
      )}
      {/* Google */}
      <div className="bg-[#161920] border border-white/5 rounded-xl p-5 mb-4">
        <Toggle label="Enable Google Login" checked={bool('auth_google_enabled')} onChange={setToggle('auth_google_enabled')} />
        {bool('auth_google_enabled') && (
          <div className="mt-4 pt-4 border-t border-white/5 space-y-0">
            <Field label="Google Client ID"     name="auth_google_client_id"     value={v('auth_google_client_id')}     onChange={set('auth_google_client_id')}     placeholder="xxxx.apps.googleusercontent.com" />
            <Field label="Google Client Secret" name="auth_google_client_secret" value={v('auth_google_client_secret')} onChange={set('auth_google_client_secret')} placeholder="GOCSPX-..." />
          </div>
        )}
      </div>
      {/* Facebook */}
      <div className="bg-[#161920] border border-white/5 rounded-xl p-5">
        <Toggle label="Enable Facebook Login" checked={bool('auth_facebook_enabled')} onChange={setToggle('auth_facebook_enabled')} />
        {bool('auth_facebook_enabled') && (
          <div className="mt-4 pt-4 border-t border-white/5 space-y-0">
            <Field label="Facebook App ID"     name="auth_facebook_client_id"     value={v('auth_facebook_client_id')}     onChange={set('auth_facebook_client_id')}     placeholder="App ID from Meta Developer Console" />
            <Field label="Facebook App Secret" name="auth_facebook_client_secret" value={v('auth_facebook_client_secret')} onChange={set('auth_facebook_client_secret')} placeholder="App Secret" />
          </div>
        )}
      </div>
    </div>
  );
}
