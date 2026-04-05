import { useState, useEffect } from 'react';
import { useMutation, useQuery, useQueryClient } from '@tanstack/react-query';
import { apiClient } from '@app/common/http/api-client';
import { CheckCircle } from 'lucide-react';

interface Settings { [key: string]: string }

function Field({
  label, name, value, onChange, placeholder,
}: {
  label: string; name: string; value: string; onChange: (v: string) => void;
  placeholder?: string;
}) {
  return (
    <div className="mb-4">
      <label htmlFor={name} className="block text-sm font-medium text-gray-300 mb-1.5">{label}</label>
      <input id={name} value={value} onChange={e => onChange(e.target.value)}
        placeholder={placeholder}
        className="w-full px-3 py-2 bg-[#0C0E12] border border-white/10 rounded-lg text-white text-sm placeholder-gray-600 focus:outline-none focus:border-indigo-500" />
    </div>
  );
}

function Select({
  label, name, value, onChange, options,
}: {
  label: string; name: string; value: string; onChange: (v: string) => void;
  options: Array<{ value: string; label: string }>;
}) {
  return (
    <div className="mb-4">
      <label htmlFor={name} className="block text-sm font-medium text-gray-300 mb-1.5">{label}</label>
      <select id={name} value={value} onChange={e => onChange(e.target.value)}
        className="w-full px-3 py-2 bg-[#0C0E12] border border-white/10 rounded-lg text-white text-sm focus:outline-none focus:border-indigo-500">
        {options.map(opt => (
          <option key={opt.value} value={opt.value}>{opt.label}</option>
        ))}
      </select>
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

export function LocalizationSettingsPage() {
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
  const setToggle = (key: string) => (val: boolean) => setForm(f => ({ ...f, [key]: val ? '1' : '0' }));
  const v = (key: string) => form[key] ?? '';
  const bool = (key: string) => form[key] === '1' || form[key] === 'true';

  if (isLoading) return <div className="text-gray-400 text-sm">Loading…</div>;

  const languageOptions = [
    { value: 'en', label: 'English' },
    { value: 'ne', label: 'Nepali' },
    { value: 'hi', label: 'Hindi' },
    { value: 'es', label: 'Spanish' },
    { value: 'fr', label: 'French' },
    { value: 'de', label: 'German' },
    { value: 'ko', label: 'Korean' },
    { value: 'zh', label: 'Chinese' },
  ];

  const dateFormatOptions = [
    { value: 'M d, Y', label: 'M d, Y (Jan 1, 2024)' },
    { value: 'd/m/Y', label: 'd/m/Y (01/01/2024)' },
    { value: 'Y-m-d', label: 'Y-m-d (2024-01-01)' },
  ];

  const timeFormatOptions = [
    { value: '12h', label: '12 hour (2:30 PM)' },
    { value: '24h', label: '24 hour (14:30)' },
  ];

  return (
    <div className="max-w-2xl">
      <div className="flex items-center justify-between mb-6">
        <h2 className="text-lg font-semibold text-white">Localization</h2>
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
      <div className="bg-[#161920] border border-white/5 rounded-xl p-5 mb-4">
        <p className="text-xs font-semibold text-gray-500 uppercase tracking-wider mb-4">Language & Region</p>
        <Select label="Default Language" name="default_language" value={v('default_language') || 'en'} onChange={set('default_language')} options={languageOptions} />
        <Field label="Default Timezone" name="default_timezone" value={v('default_timezone')} onChange={set('default_timezone')} placeholder="Asia/Kathmandu" />
        <Field label="Default Currency" name="default_currency" value={v('default_currency')} onChange={set('default_currency')} placeholder="USD" />
      </div>
      <div className="bg-[#161920] border border-white/5 rounded-xl p-5 mb-4">
        <p className="text-xs font-semibold text-gray-500 uppercase tracking-wider mb-4">Formatting</p>
        <Select label="Date Format" name="date_format" value={v('date_format') || 'M d, Y'} onChange={set('date_format')} options={dateFormatOptions} />
        <Select label="Time Format" name="time_format" value={v('time_format') || '12h'} onChange={set('time_format')} options={timeFormatOptions} />
      </div>
      <div className="bg-[#161920] border border-white/5 rounded-xl p-5">
        <p className="text-xs font-semibold text-gray-500 uppercase tracking-wider mb-4">Layout Options</p>
        <Toggle label="Enable RTL layout" checked={bool('enable_rtl')} onChange={setToggle('enable_rtl')} />
      </div>
    </div>
  );
}