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

export function CaptchaSettingsPage() {
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

  const captchaDriverOptions = [
    { value: 'none', label: 'None' },
    { value: 'recaptcha_v2', label: 'reCAPTCHA v2' },
    { value: 'recaptcha_v3', label: 'reCAPTCHA v3' },
    { value: 'hcaptcha', label: 'hCaptcha' },
    { value: 'turnstile', label: 'Cloudflare Turnstile' },
  ];

  return (
    <div className="max-w-2xl">
      <div className="flex items-center justify-between mb-6">
        <div>
          <h2 className="text-lg font-semibold text-white">Captcha</h2>
          <p className="text-sm text-gray-400 mt-1">Enable captcha integration for your church platform</p>
        </div>
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
        <p className="text-xs font-semibold text-gray-500 uppercase tracking-wider mb-4">Provider & credentials</p>
        <p className="text-sm text-gray-400 mb-4">Select captcha provider and enter your API credentials.</p>
        <Select label="Captcha provider" name="captcha_driver" value={v('captcha_driver') || 'none'} onChange={set('captcha_driver')} options={captchaDriverOptions} />
        {v('captcha_driver') !== 'none' && (
          <div className="space-y-0">
            {v('captcha_driver') === 'turnstile' ? (
              <>
                <Field label="Turnstile site key" name="turnstile_site_key" value={v('turnstile_site_key')} onChange={set('turnstile_site_key')} />
                <Field label="Turnstile secret key" name="turnstile_secret_key" value={v('turnstile_secret_key')} onChange={set('turnstile_secret_key')} />
              </>
            ) : (
              <>
                <Field label="Site Key" name="recaptcha_site_key" value={v('recaptcha_site_key')} onChange={set('recaptcha_site_key')} />
                <Field label="Secret Key" name="recaptcha_secret_key" value={v('recaptcha_secret_key')} onChange={set('recaptcha_secret_key')} />
              </>
            )}
          </div>
        )}
      </div>
      <div className="bg-[#161920] border border-white/5 rounded-xl p-5">
        <p className="text-xs font-semibold text-gray-500 uppercase tracking-wider mb-2">Enable captcha</p>
        <p className="text-sm text-gray-400 mb-4">Select which pages should be protected by captcha.</p>
        <div className="space-y-1">
          <Toggle label='Enable captcha integration for "contact us" page.' checked={bool('captcha_on_contact')} onChange={setToggle('captcha_on_contact')} />
          <Toggle label="Enable captcha integration for registration page." checked={bool('captcha_on_register')} onChange={setToggle('captcha_on_register')} />
        </div>
      </div>
    </div>
  );
}