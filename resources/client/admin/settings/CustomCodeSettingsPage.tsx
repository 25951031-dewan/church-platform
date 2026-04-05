import { useState, useEffect } from 'react';
import { useMutation, useQuery, useQueryClient } from '@tanstack/react-query';
import { apiClient } from '@app/common/http/api-client';
import { CheckCircle } from 'lucide-react';

interface Settings { [key: string]: string }

function Textarea({
  label, name, value, onChange, placeholder, rows,
}: {
  label: string; name: string; value: string; onChange: (v: string) => void;
  placeholder?: string; rows: number;
}) {
  return (
    <div className="mb-4">
      <label htmlFor={name} className="block text-sm font-medium text-gray-300 mb-1.5">{label}</label>
      <textarea id={name} rows={rows} value={value} onChange={e => onChange(e.target.value)}
        placeholder={placeholder}
        className="w-full px-3 py-2 bg-[#0C0E12] border border-white/10 rounded-lg text-white text-sm placeholder-gray-600 focus:outline-none focus:border-indigo-500 resize-none" />
    </div>
  );
}

export function CustomCodeSettingsPage() {
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
        <h2 className="text-lg font-semibold text-white">Custom Code</h2>
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
      <div className="bg-yellow-500/10 border border-yellow-500/20 rounded-lg p-3 mb-4 text-yellow-400 text-sm">
        Invalid HTML or JS here can break your site.
      </div>
      <div className="bg-[#161920] border border-white/5 rounded-xl p-5 mb-4">
        <p className="text-xs font-semibold text-gray-500 uppercase tracking-wider mb-4">Header Code</p>
        <Textarea label="Custom code injected before </head>" name="header_code" value={v('header_code')} onChange={set('header_code')} rows={8} />
      </div>
      <div className="bg-[#161920] border border-white/5 rounded-xl p-5">
        <p className="text-xs font-semibold text-gray-500 uppercase tracking-wider mb-4">Footer Code</p>
        <Textarea label="Custom code injected before </body>" name="footer_code" value={v('footer_code')} onChange={set('footer_code')} rows={8} />
      </div>
    </div>
  );
}