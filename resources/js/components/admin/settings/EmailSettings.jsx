import { useMutation, useQuery, useQueryClient } from '@tanstack/react-query';
import axios from 'axios';
import { useState, useEffect } from 'react';

export default function EmailSettings() {
    const queryClient = useQueryClient();
    const { data = {} } = useQuery({
        queryKey: ['admin-settings-email'],
        queryFn:  () => axios.get('/api/v1/admin/settings/email').then(r => r.data),
    });

    const [host,       setHost]       = useState('');
    const [port,       setPort]       = useState('587');
    const [username,   setUsername]   = useState('');
    const [password,   setPassword]   = useState('');
    const [encryption, setEncryption] = useState('tls');
    const [fromAddr,   setFromAddr]   = useState('');
    const [fromName,   setFromName]   = useState('');
    const [saved,      setSaved]      = useState(false);

    useEffect(() => {
        setHost(data.smtp_host ?? '');
        setPort(String(data.smtp_port ?? '587'));
        setUsername(data.smtp_username ?? '');
        setEncryption(data.smtp_encryption ?? 'tls');
        setFromAddr(data.mail_from_address ?? '');
        setFromName(data.mail_from_name ?? '');
    }, [data]);

    const save = useMutation({
        mutationFn: (d) => axios.patch('/api/v1/admin/settings/email', d),
        onSuccess:  () => { queryClient.invalidateQueries({ queryKey: ['admin-settings-email'] }); setSaved(true); setTimeout(() => setSaved(false), 2500); },
    });

    return (
        <form
            onSubmit={e => { e.preventDefault(); save.mutate({ smtp_host: host, smtp_port: port, smtp_username: username, smtp_password: password || undefined, smtp_encryption: encryption, mail_from_address: fromAddr, mail_from_name: fromName }); }}
            className="max-w-lg space-y-4"
        >
            <h2 className="text-lg font-semibold text-gray-900">Email / SMTP</h2>
            {saved && <p className="text-sm text-green-600">Saved.</p>}
            {[
                ['SMTP Host',     host,       setHost,       'text',     'smtp.example.com'],
                ['SMTP Port',     port,       setPort,       'number',   '587'],
                ['SMTP Username', username,   setUsername,   'text',     'user@example.com'],
                ['SMTP Password', password,   setPassword,   'password', '••••••••'],
                ['From Address',  fromAddr,   setFromAddr,   'email',    'noreply@yourchurch.com'],
                ['From Name',     fromName,   setFromName,   'text',     'Your Church'],
            ].map(([label, val, setter, type, placeholder]) => (
                <div key={label}>
                    <label className="block text-sm font-medium text-gray-700 mb-1">{label}</label>
                    <input
                        type={type}
                        className="block w-full rounded border border-gray-300 px-3 py-2 text-sm focus:border-blue-500 focus:outline-none"
                        value={val}
                        onChange={e => setter(e.target.value)}
                        placeholder={placeholder}
                    />
                </div>
            ))}
            <div>
                <label className="block text-sm font-medium text-gray-700 mb-1">Encryption</label>
                <select className="block w-full rounded border border-gray-300 px-3 py-2 text-sm focus:border-blue-500 focus:outline-none" value={encryption} onChange={e => setEncryption(e.target.value)}>
                    <option value="tls">TLS</option>
                    <option value="ssl">SSL</option>
                    <option value="">None</option>
                </select>
            </div>
            <button type="submit" disabled={save.isPending} className="rounded bg-blue-600 px-5 py-2 text-sm font-medium text-white hover:bg-blue-700 disabled:opacity-50">
                {save.isPending ? 'Saving…' : 'Save'}
            </button>
        </form>
    );
}
