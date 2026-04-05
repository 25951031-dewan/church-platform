import { useState, useEffect } from 'react';
import { useMutation, useQuery, useQueryClient } from '@tanstack/react-query';
import { apiClient } from '@app/common/http/api-client';
import { CheckCircle } from 'lucide-react';

interface Settings { [key: string]: string }

function Field({
  label, name, value, onChange, type = 'text', placeholder,
}: {
  label: string; name: string; value: string; onChange: (v: string) => void;
  type?: string; placeholder?: string;
}) {
  return (
    <div className="mb-4">
      <label htmlFor={name} className="block text-sm font-medium text-gray-300 mb-1.5">{label}</label>
      <input id={name} type={type} value={value} onChange={e => onChange(e.target.value)}
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

export function UploadingSettingsPage() {
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

  const storageDriverOptions = [
    { value: 'local', label: 'Local Storage' },
    { value: 's3', label: 'Amazon S3' },
    { value: 'cloudinary', label: 'Cloudinary' },
  ];

  return (
    <div className="max-w-2xl">
      <div className="flex items-center justify-between mb-6">
        <h2 className="text-lg font-semibold text-white">Uploading</h2>
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
        <p className="text-xs font-semibold text-gray-500 uppercase tracking-wider mb-4">File Limits</p>
        <Field label="Max File Size (MB)" name="max_file_size_mb" value={v('max_file_size_mb')} onChange={set('max_file_size_mb')} type="number" />
        <Field label="Allowed Image Types" name="allowed_image_types" value={v('allowed_image_types')} onChange={set('allowed_image_types')} placeholder="jpg,png,webp,gif" />
        <Field label="Allowed Document Types" name="allowed_document_types" value={v('allowed_document_types')} onChange={set('allowed_document_types')} placeholder="pdf,doc,docx" />
        <Field label="Allowed Video Types" name="allowed_video_types" value={v('allowed_video_types')} onChange={set('allowed_video_types')} placeholder="mp4,mov" />
      </div>
      <div className="bg-[#161920] border border-white/5 rounded-xl p-5 mb-4">
        <p className="text-xs font-semibold text-gray-500 uppercase tracking-wider mb-4">Storage</p>
        <Select label="Storage Driver" name="storage_driver" value={v('storage_driver') || 'local'} onChange={set('storage_driver')} options={storageDriverOptions} />
        {v('storage_driver') === 's3' && (
          <div className="space-y-0">
            <Field label="S3 Bucket" name="s3_bucket" value={v('s3_bucket')} onChange={set('s3_bucket')} />
            <Field label="S3 Region" name="s3_region" value={v('s3_region')} onChange={set('s3_region')} />
            <Field label="S3 Access Key" name="s3_key" value={v('s3_key')} onChange={set('s3_key')} />
            <Field label="S3 Secret Key" name="s3_secret" value={v('s3_secret')} onChange={set('s3_secret')} />
          </div>
        )}
      </div>
    </div>
  );
}