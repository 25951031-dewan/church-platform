import { useState, useEffect } from 'react';
import { useMutation, useQuery, useQueryClient } from '@tanstack/react-query';
import { apiClient } from '@app/common/http/api-client';
import { Mail, Send, CheckCircle, AlertCircle } from 'lucide-react';

interface EmailSettings {
  mail_provider: 'smtp' | 'mailchimp' | 'sendgrid' | 'mailgun';
  smtp_host?: string;
  smtp_port?: number;
  smtp_username?: string;
  smtp_password?: string;
  smtp_encryption?: 'tls' | 'ssl' | 'none';
  mail_from_address?: string;
  mail_from_name?: string;
  mailchimp_api_key?: string;
  mailchimp_list_id?: string;
  mailchimp_server_prefix?: string;
  sendgrid_api_key?: string;
  mailgun_domain?: string;
  mailgun_secret?: string;
  email_contact_notification?: boolean;
  email_newsletter_enabled?: boolean;
  email_welcome_enabled?: boolean;
  email_signature?: string;
}

function Field({ label, name, value, onChange, type = 'text', placeholder }: {
  label: string; name: string; value: string | number | undefined; 
  onChange: (v: string) => void; type?: string; placeholder?: string;
}) {
  return (
    <div className="mb-4">
      <label className="block text-sm font-medium text-gray-300 mb-1.5">{label}</label>
      <input
        type={type}
        name={name}
        value={value ?? ''}
        onChange={(e) => onChange(e.target.value)}
        placeholder={placeholder}
        className="w-full px-3 py-2 bg-[#0C0E12] border border-white/10 rounded-lg text-white text-sm placeholder-gray-600 focus:outline-none focus:border-indigo-500 transition-colors"
      />
    </div>
  );
}

function Toggle({ label, checked, onChange }: { label: string; checked: boolean; onChange: (v: boolean) => void }) {
  return (
    <label className="flex items-center justify-between py-2 cursor-pointer">
      <span className="text-sm text-gray-300">{label}</span>
      <button
        type="button"
        onClick={() => onChange(!checked)}
        className={`relative w-11 h-6 rounded-full transition-colors ${
          checked ? 'bg-indigo-600' : 'bg-gray-700'
        }`}
      >
        <span
          className={`absolute top-0.5 left-0.5 w-5 h-5 bg-white rounded-full transition-transform ${
            checked ? 'translate-x-5' : 'translate-x-0'
          }`}
        />
      </button>
    </label>
  );
}

export function EmailSettingsPage() {
  const queryClient = useQueryClient();

  const { data, isLoading } = useQuery({
    queryKey: ['settings', 'email'],
    queryFn: () => apiClient.get<{ data: EmailSettings }>('settings/email').then((r) => r.data.data),
  });

  const [form, setForm] = useState<EmailSettings>({
    mail_provider: 'smtp',
    smtp_encryption: 'tls',
    email_contact_notification: true,
    email_newsletter_enabled: true,
    email_welcome_enabled: false,
  });

  const [testEmail, setTestEmail] = useState('');
  const [testResult, setTestResult] = useState<{ success: boolean; message: string } | null>(null);

  useEffect(() => {
    if (data) {
      setForm(data);
    }
  }, [data]);

  const save = useMutation({
    mutationFn: (settings: EmailSettings) => apiClient.put('settings/email', settings),
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: ['settings', 'email'] });
    },
  });

  const sendTest = useMutation({
    mutationFn: (email: string) => apiClient.post('settings/email/test', { to_email: email }),
    onSuccess: (response) => {
      setTestResult({ success: true, message: response.data.message });
      setTimeout(() => setTestResult(null), 5000);
    },
    onError: (error: any) => {
      setTestResult({ success: false, message: error.response?.data?.message || 'Failed to send test email' });
      setTimeout(() => setTestResult(null), 5000);
    },
  });

  const handleSave = () => {
    save.mutate(form);
  };

  const handleTestEmail = () => {
    if (testEmail) {
      sendTest.mutate(testEmail);
    }
  };

  if (isLoading) {
    return <div className="text-gray-400 text-sm">Loading email settings...</div>;
  }

  return (
    <div className="max-w-3xl">
      <div className="mb-6">
        <h2 className="text-xl font-bold text-white mb-1">Email Settings</h2>
        <p className="text-sm text-gray-400">Configure SMTP, email providers, and notification preferences.</p>
      </div>

      {/* Provider Selection */}
      <div className="bg-[#161920] border border-white/5 rounded-xl p-5 mb-4">
        <label className="block text-sm font-medium text-gray-300 mb-2">Email Provider</label>
        <select
          value={form.mail_provider}
          onChange={(e) => setForm({ ...form, mail_provider: e.target.value as any })}
          className="w-full px-3 py-2 bg-[#0C0E12] border border-white/10 rounded-lg text-white text-sm focus:outline-none focus:border-indigo-500"
        >
          <option value="smtp">SMTP</option>
          <option value="mailchimp">Mailchimp</option>
          <option value="sendgrid">SendGrid</option>
          <option value="mailgun">Mailgun</option>
        </select>
      </div>

      {/* SMTP Settings */}
      {form.mail_provider === 'smtp' && (
        <div className="bg-[#161920] border border-white/5 rounded-xl p-5 mb-4">
          <h3 className="text-sm font-semibold text-white mb-4 flex items-center gap-2">
            <Mail size={16} />
            SMTP Configuration
          </h3>
          <div className="grid grid-cols-2 gap-4">
            <div className="col-span-2">
              <Field
                label="SMTP Host"
                name="smtp_host"
                value={form.smtp_host}
                onChange={(v) => setForm({ ...form, smtp_host: v })}
                placeholder="smtp.gmail.com"
              />
            </div>
            <Field
              label="SMTP Port"
              name="smtp_port"
              type="number"
              value={form.smtp_port}
              onChange={(v) => setForm({ ...form, smtp_port: parseInt(v) || 587 })}
              placeholder="587"
            />
            <div>
              <label className="block text-sm font-medium text-gray-300 mb-1.5">Encryption</label>
              <select
                value={form.smtp_encryption}
                onChange={(e) => setForm({ ...form, smtp_encryption: e.target.value as any })}
                className="w-full px-3 py-2 bg-[#0C0E12] border border-white/10 rounded-lg text-white text-sm focus:outline-none focus:border-indigo-500"
              >
                <option value="tls">TLS</option>
                <option value="ssl">SSL</option>
                <option value="none">None</option>
              </select>
            </div>
            <Field
              label="SMTP Username"
              name="smtp_username"
              value={form.smtp_username}
              onChange={(v) => setForm({ ...form, smtp_username: v })}
              placeholder="your-email@example.com"
            />
            <Field
              label="SMTP Password"
              name="smtp_password"
              type="password"
              value={form.smtp_password}
              onChange={(v) => setForm({ ...form, smtp_password: v })}
              placeholder="••••••••"
            />
          </div>
        </div>
      )}

      {/* Mailchimp Settings */}
      {form.mail_provider === 'mailchimp' && (
        <div className="bg-[#161920] border border-white/5 rounded-xl p-5 mb-4">
          <h3 className="text-sm font-semibold text-white mb-4">Mailchimp Configuration</h3>
          <Field
            label="API Key"
            name="mailchimp_api_key"
            type="password"
            value={form.mailchimp_api_key}
            onChange={(v) => setForm({ ...form, mailchimp_api_key: v })}
          />
          <Field
            label="List ID"
            name="mailchimp_list_id"
            value={form.mailchimp_list_id}
            onChange={(v) => setForm({ ...form, mailchimp_list_id: v })}
          />
          <Field
            label="Server Prefix"
            name="mailchimp_server_prefix"
            value={form.mailchimp_server_prefix}
            onChange={(v) => setForm({ ...form, mailchimp_server_prefix: v })}
            placeholder="us1"
          />
        </div>
      )}

      {/* SendGrid Settings */}
      {form.mail_provider === 'sendgrid' && (
        <div className="bg-[#161920] border border-white/5 rounded-xl p-5 mb-4">
          <h3 className="text-sm font-semibold text-white mb-4">SendGrid Configuration</h3>
          <Field
            label="API Key"
            name="sendgrid_api_key"
            type="password"
            value={form.sendgrid_api_key}
            onChange={(v) => setForm({ ...form, sendgrid_api_key: v })}
          />
        </div>
      )}

      {/* Mailgun Settings */}
      {form.mail_provider === 'mailgun' && (
        <div className="bg-[#161920] border border-white/5 rounded-xl p-5 mb-4">
          <h3 className="text-sm font-semibold text-white mb-4">Mailgun Configuration</h3>
          <Field
            label="Domain"
            name="mailgun_domain"
            value={form.mailgun_domain}
            onChange={(v) => setForm({ ...form, mailgun_domain: v })}
            placeholder="mg.yourdomain.com"
          />
          <Field
            label="Secret Key"
            name="mailgun_secret"
            type="password"
            value={form.mailgun_secret}
            onChange={(v) => setForm({ ...form, mailgun_secret: v })}
          />
        </div>
      )}

      {/* From Address */}
      <div className="bg-[#161920] border border-white/5 rounded-xl p-5 mb-4">
        <h3 className="text-sm font-semibold text-white mb-4">Sender Information</h3>
        <div className="grid grid-cols-2 gap-4">
          <Field
            label="From Name"
            name="mail_from_name"
            value={form.mail_from_name}
            onChange={(v) => setForm({ ...form, mail_from_name: v })}
            placeholder="Church Platform"
          />
          <Field
            label="From Email"
            name="mail_from_address"
            type="email"
            value={form.mail_from_address}
            onChange={(v) => setForm({ ...form, mail_from_address: v })}
            placeholder="noreply@yourchurch.com"
          />
        </div>
      </div>

      {/* Email Features */}
      <div className="bg-[#161920] border border-white/5 rounded-xl p-5 mb-4">
        <h3 className="text-sm font-semibold text-white mb-4">Email Features</h3>
        <div className="space-y-2">
          <Toggle
            label="Contact Form Notifications"
            checked={form.email_contact_notification ?? false}
            onChange={(v) => setForm({ ...form, email_contact_notification: v })}
          />
          <Toggle
            label="Newsletter Enabled"
            checked={form.email_newsletter_enabled ?? false}
            onChange={(v) => setForm({ ...form, email_newsletter_enabled: v })}
          />
          <Toggle
            label="Welcome Email on Registration"
            checked={form.email_welcome_enabled ?? false}
            onChange={(v) => setForm({ ...form, email_welcome_enabled: v })}
          />
        </div>
      </div>

      {/* Test Email */}
      <div className="bg-[#161920] border border-white/5 rounded-xl p-5 mb-4">
        <h3 className="text-sm font-semibold text-white mb-3 flex items-center gap-2">
          <Send size={16} />
          Test Email Configuration
        </h3>
        <div className="flex gap-2">
          <input
            type="email"
            value={testEmail}
            onChange={(e) => setTestEmail(e.target.value)}
            placeholder="test@example.com"
            className="flex-1 px-3 py-2 bg-[#0C0E12] border border-white/10 rounded-lg text-white text-sm placeholder-gray-600 focus:outline-none focus:border-indigo-500"
          />
          <button
            type="button"
            onClick={handleTestEmail}
            disabled={sendTest.isPending || !testEmail}
            className="px-4 py-2 bg-gray-700 hover:bg-gray-600 text-white text-sm rounded-lg transition-colors disabled:opacity-50 disabled:cursor-not-allowed"
          >
            {sendTest.isPending ? 'Sending...' : 'Send Test'}
          </button>
        </div>
        {testResult && (
          <div className={`mt-3 p-3 rounded-lg flex items-start gap-2 text-sm ${
            testResult.success ? 'bg-green-600/10 text-green-400 border border-green-500/20' : 'bg-red-600/10 text-red-400 border border-red-500/20'
          }`}>
            {testResult.success ? <CheckCircle size={16} className="mt-0.5 flex-shrink-0" /> : <AlertCircle size={16} className="mt-0.5 flex-shrink-0" />}
            <span>{testResult.message}</span>
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
