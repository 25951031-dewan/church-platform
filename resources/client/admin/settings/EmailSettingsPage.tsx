import { useState, useEffect } from 'react';
import { useMutation, useQuery, useQueryClient } from '@tanstack/react-query';
import { apiClient } from '@app/common/http/api-client';
import { CheckCircle } from 'lucide-react';

interface Settings { [key: string]: string }

function Field({ label, name, value, onChange, type = 'text', placeholder }: {
  label: string; name: string; value: string; onChange: (v: string) => void; type?: string; placeholder?: string;
}) {
  return (
    <div className="mb-4">
      <label htmlFor={name} className="block text-sm font-medium text-gray-300 mb-1.5">{label}</label>
      <input id={name} type={type} value={value} onChange={e => onChange(e.target.value)}
        placeholder={placeholder} autoComplete="off"
        className="w-full px-3 py-2 bg-[#0C0E12] border border-white/10 rounded-lg text-white text-sm placeholder-gray-600 focus:outline-none focus:border-indigo-500" />
    </div>
  );
}

const EMAIL_KEYS = [
  'smtp_host', 'smtp_port', 'smtp_username', 'smtp_password',
  'smtp_encryption', 'mail_from_address', 'mail_from_name',
];

export function EmailSettingsPage() {
  const qc = useQueryClient();
  const [form, setForm] = useState<Settings>({});
  const [saved, setSaved] = useState(false);

  // Load all settings, filter email-related ones
  const { data, isLoading } = useQuery({
    queryKey: ['settings'],
    queryFn: () => apiClient.get<{ settings: Settings }>('settings').then(r => r.data.settings ?? {}),
  });

  useEffect(() => {
    if (data) {
      const emailFields: Settings = {};
      EMAIL_KEYS.forEach(k => { emailFields[k] = data[k] ?? ''; });
      setForm(emailFields);
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
  const v = (key: string) => form[key] ?? '';

  if (isLoading) return <div className="text-gray-400 text-sm">Loading…</div>;

  return (
    <div className="max-w-2xl">
      <div className="flex items-center justify-between mb-6">
        <h2 className="text-lg font-semibold text-white">Email (SMTP)</h2>
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
      <div className="bg-[#161920] border border-white/5 rounded-xl p-5 mb-4">
        <p className="text-xs font-semibold text-gray-500 uppercase tracking-wider mb-4">SMTP Server</p>
        <div className="grid grid-cols-2 gap-4">
          <Field label="SMTP Host" name="smtp_host" value={v('smtp_host')} onChange={set('smtp_host')} placeholder="smtp.gmail.com" />
          <Field label="SMTP Port" name="smtp_port" value={v('smtp_port')} onChange={set('smtp_port')} placeholder="587" />
        </div>
        <Field label="Username / Email" name="smtp_username" value={v('smtp_username')} onChange={set('smtp_username')} placeholder="you@gmail.com" />
        <Field label="Password / App Password" name="smtp_password" value={v('smtp_password')} onChange={set('smtp_password')} type="password" placeholder="Leave blank to keep existing" />
        <div>
          <label htmlFor="smtp_encryption" className="block text-sm font-medium text-gray-300 mb-1.5">Encryption</label>
          <select id="smtp_encryption" value={v('smtp_encryption')} onChange={e => set('smtp_encryption')(e.target.value)}
            className="w-full px-3 py-2 bg-[#0C0E12] border border-white/10 rounded-lg text-white text-sm focus:outline-none focus:border-indigo-500">
            <option value="tls">TLS (recommended)</option>
            <option value="ssl">SSL</option>
            <option value="none">None</option>
          </select>
        </div>
      </div>
      <div className="bg-[#161920] border border-white/5 rounded-xl p-5">
        <p className="text-xs font-semibold text-gray-500 uppercase tracking-wider mb-4">Sender Identity</p>
        <Field label="From Address" name="mail_from_address" value={v('mail_from_address')} onChange={set('mail_from_address')} type="email" placeholder="noreply@mychurch.org" />
        <Field label="From Name"    name="mail_from_name"    value={v('mail_from_name')}    onChange={set('mail_from_name')}    placeholder="My Church" />
      </div>
    </div>
  );
}
