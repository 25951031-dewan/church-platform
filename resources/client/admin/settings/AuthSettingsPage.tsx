import { useState, useEffect } from 'react';
import { useMutation, useQuery, useQueryClient } from '@tanstack/react-query';
import { apiClient } from '@app/common/http/api-client';
import { KeyRound } from 'lucide-react';

interface AuthSettings {
  auth_google_enabled?: boolean;
  auth_google_client_id?: string;
  auth_google_client_secret?: string;
  auth_facebook_enabled?: boolean;
  auth_facebook_client_id?: string;
  auth_facebook_client_secret?: string;
}

function Toggle({ label, checked, onChange }: { label: string; checked: boolean; onChange: (v: boolean) => void }) {
  return (
    <label className="flex items-center justify-between py-2 cursor-pointer">
      <span className="text-sm text-gray-300">{label}</span>
      <button
        type="button"
        onClick={() => onChange(!checked)}
        className={`relative w-11 h-6 rounded-full transition-colors ${checked ? 'bg-indigo-600' : 'bg-gray-700'}`}
      >
        <span className={`absolute top-0.5 left-0.5 w-5 h-5 bg-white rounded-full transition-transform ${checked ? 'translate-x-5' : 'translate-x-0'}`} />
      </button>
    </label>
  );
}

export function AuthSettingsPage() {
  const queryClient = useQueryClient();

  const { data, isLoading } = useQuery({
    queryKey: ['settings', 'auth'],
    queryFn: () => apiClient.get<{ data: AuthSettings }>('settings').then((r) => r.data.data),
  });

  const [form, setForm] = useState<AuthSettings>({
    auth_google_enabled: false,
    auth_facebook_enabled: false,
  });

  useEffect(() => {
    if (data) {
      setForm(data);
    }
  }, [data]);

  const save = useMutation({
    mutationFn: (settings: AuthSettings) => apiClient.put('settings', settings),
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: ['settings', 'auth'] });
    },
  });

  if (isLoading) return <div className="text-gray-400 text-sm">Loading...</div>;

  return (
    <div className="max-w-3xl">
      <div className="mb-6">
        <h2 className="text-xl font-bold text-white mb-1">Authentication Settings</h2>
        <p className="text-sm text-gray-400">Configure social login providers for your church platform.</p>
      </div>

      {/* Google OAuth */}
      <div className="bg-[#161920] border border-white/5 rounded-xl p-5 mb-4">
        <h3 className="text-sm font-semibold text-white mb-4 flex items-center gap-2">
          <KeyRound size={16} />
          Google OAuth
        </h3>
        <Toggle
          label="Enable Google Login"
          checked={form.auth_google_enabled ?? false}
          onChange={(v) => setForm({ ...form, auth_google_enabled: v })}
        />
        {form.auth_google_enabled && (
          <div className="mt-4 space-y-3">
            <div>
              <label className="block text-sm font-medium text-gray-300 mb-1.5">Client ID</label>
              <input
                type="text"
                value={form.auth_google_client_id ?? ''}
                onChange={(e) => setForm({ ...form, auth_google_client_id: e.target.value })}
                placeholder="123456789-xxxxx.apps.googleusercontent.com"
                className="w-full px-3 py-2 bg-[#0C0E12] border border-white/10 rounded-lg text-white text-sm placeholder-gray-600 focus:outline-none focus:border-indigo-500"
              />
            </div>
            <div>
              <label className="block text-sm font-medium text-gray-300 mb-1.5">Client Secret</label>
              <input
                type="password"
                value={form.auth_google_client_secret ?? ''}
                onChange={(e) => setForm({ ...form, auth_google_client_secret: e.target.value })}
                placeholder="GOCSPX-xxxxx"
                className="w-full px-3 py-2 bg-[#0C0E12] border border-white/10 rounded-lg text-white text-sm placeholder-gray-600 focus:outline-none focus:border-indigo-500"
              />
            </div>
            <p className="text-xs text-gray-500">Get credentials from <a href="https://console.cloud.google.com" target="_blank" rel="noopener noreferrer" className="text-indigo-400 hover:underline">Google Cloud Console</a></p>
          </div>
        )}
      </div>

      {/* Facebook OAuth */}
      <div className="bg-[#161920] border border-white/5 rounded-xl p-5 mb-4">
        <h3 className="text-sm font-semibold text-white mb-4 flex items-center gap-2">
          <KeyRound size={16} />
          Facebook OAuth
        </h3>
        <Toggle
          label="Enable Facebook Login"
          checked={form.auth_facebook_enabled ?? false}
          onChange={(v) => setForm({ ...form, auth_facebook_enabled: v })}
        />
        {form.auth_facebook_enabled && (
          <div className="mt-4 space-y-3">
            <div>
              <label className="block text-sm font-medium text-gray-300 mb-1.5">App ID</label>
              <input
                type="text"
                value={form.auth_facebook_client_id ?? ''}
                onChange={(e) => setForm({ ...form, auth_facebook_client_id: e.target.value })}
                placeholder="1234567890123456"
                className="w-full px-3 py-2 bg-[#0C0E12] border border-white/10 rounded-lg text-white text-sm placeholder-gray-600 focus:outline-none focus:border-indigo-500"
              />
            </div>
            <div>
              <label className="block text-sm font-medium text-gray-300 mb-1.5">App Secret</label>
              <input
                type="password"
                value={form.auth_facebook_client_secret ?? ''}
                onChange={(e) => setForm({ ...form, auth_facebook_client_secret: e.target.value })}
                placeholder="xxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx"
                className="w-full px-3 py-2 bg-[#0C0E12] border border-white/10 rounded-lg text-white text-sm placeholder-gray-600 focus:outline-none focus:border-indigo-500"
              />
            </div>
            <p className="text-xs text-gray-500">Get credentials from <a href="https://developers.facebook.com" target="_blank" rel="noopener noreferrer" className="text-indigo-400 hover:underline">Facebook Developers</a></p>
          </div>
        )}
      </div>

      {/* Save Button */}
      <div className="flex items-center justify-between">
        <div className="text-xs text-gray-500">
          {save.isSuccess && <span className="text-green-400">✓ Settings saved successfully</span>}
          {save.isError && <span className="text-red-400">✗ Failed to save settings</span>}
        </div>
        <button
          type="button"
          onClick={() => save.mutate(form)}
          disabled={save.isPending}
          className="px-5 py-2.5 bg-indigo-600 hover:bg-indigo-700 text-white text-sm font-medium rounded-lg transition-colors disabled:opacity-50"
        >
          {save.isPending ? 'Saving...' : 'Save Settings'}
        </button>
      </div>
    </div>
  );
}
