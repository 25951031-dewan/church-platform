import { useMutation, useQuery, useQueryClient } from '@tanstack/react-query';
import axios from 'axios';
import { useState, useEffect } from 'react';

const SOCIAL_PROVIDERS = ['google', 'facebook', 'twitter', 'github'];

export default function AuthSettings() {
    const queryClient = useQueryClient();
    const { data = {} } = useQuery({
        queryKey: ['admin-settings-auth'],
        queryFn:  () => axios.get('/api/v1/admin/settings/auth').then(r => r.data),
    });

    const [enabledProviders, setEnabledProviders] = useState([]);
    const [captchaEnabled,   setCaptchaEnabled]   = useState(false);
    const [saved,            setSaved]            = useState(false);

    useEffect(() => {
        setEnabledProviders(data.social_login_providers ?? []);
        setCaptchaEnabled(data.captcha_enabled ?? false);
    }, [data]);

    function toggleProvider(provider) {
        setEnabledProviders(prev =>
            prev.includes(provider) ? prev.filter(p => p !== provider) : [...prev, provider]
        );
    }

    const save = useMutation({
        mutationFn: (d) => axios.patch('/api/v1/admin/settings/auth', d),
        onSuccess:  () => { queryClient.invalidateQueries({ queryKey: ['admin-settings-auth'] }); setSaved(true); setTimeout(() => setSaved(false), 2500); },
    });

    return (
        <form onSubmit={e => { e.preventDefault(); save.mutate({ social_login_providers: enabledProviders, captcha_enabled: captchaEnabled }); }} className="max-w-lg space-y-6">
            <h2 className="text-lg font-semibold text-gray-900">Auth & Social Login</h2>
            {saved && <p className="text-sm text-green-600">Saved.</p>}

            <div>
                <p className="mb-2 text-sm font-medium text-gray-700">Social Login Providers</p>
                <div className="space-y-2">
                    {SOCIAL_PROVIDERS.map(p => (
                        <label key={p} className="flex items-center gap-2 text-sm capitalize text-gray-700 cursor-pointer">
                            <input type="checkbox" checked={enabledProviders.includes(p)} onChange={() => toggleProvider(p)} />
                            {p}
                        </label>
                    ))}
                </div>
            </div>

            <div className="flex items-center justify-between rounded-lg border border-gray-200 p-4">
                <div>
                    <p className="font-medium text-gray-900 text-sm">Captcha on login &amp; register</p>
                    <p className="text-xs text-gray-500">Configure Turnstile keys in the API Settings tab.</p>
                </div>
                <input type="checkbox" checked={captchaEnabled} onChange={e => setCaptchaEnabled(e.target.checked)} className="h-4 w-4" />
            </div>

            <button type="submit" disabled={save.isPending} className="rounded bg-blue-600 px-5 py-2 text-sm font-medium text-white hover:bg-blue-700 disabled:opacity-50">
                {save.isPending ? 'Saving…' : 'Save'}
            </button>
        </form>
    );
}
