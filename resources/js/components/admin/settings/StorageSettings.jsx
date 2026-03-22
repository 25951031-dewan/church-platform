import { useMutation, useQuery, useQueryClient } from '@tanstack/react-query';
import axios from 'axios';
import { useState, useEffect } from 'react';

export default function StorageSettings() {
    const queryClient = useQueryClient();
    const { data = {} } = useQuery({
        queryKey: ['admin-settings-storage'],
        queryFn:  () => axios.get('/api/v1/admin/settings/storage').then(r => r.data),
    });

    const [driver,      setDriver]      = useState('local');
    const [s3Bucket,    setS3Bucket]    = useState('');
    const [s3Region,    setS3Region]    = useState('');
    const [s3Key,       setS3Key]       = useState('');
    const [s3Secret,    setS3Secret]    = useState('');
    const [maxUploadMb, setMaxUploadMb] = useState('10');
    const [saved,       setSaved]       = useState(false);

    useEffect(() => {
        setDriver(data.storage_driver ?? 'local');
        setS3Bucket(data.s3_bucket ?? '');
        setS3Region(data.s3_region ?? '');
        setS3Key(data.s3_key ?? '');
        setMaxUploadMb(String(data.max_upload_mb ?? 10));
    }, [data]);

    const save = useMutation({
        mutationFn: (d) => axios.patch('/api/v1/admin/settings/storage', d),
        onSuccess:  () => { queryClient.invalidateQueries({ queryKey: ['admin-settings-storage'] }); setSaved(true); setTimeout(() => setSaved(false), 2500); },
    });

    return (
        <form onSubmit={e => { e.preventDefault(); save.mutate({ storage_driver: driver, s3_bucket: s3Bucket, s3_region: s3Region, s3_key: s3Key, s3_secret: s3Secret || undefined, max_upload_mb: Number(maxUploadMb) }); }} className="max-w-lg space-y-4">
            <h2 className="text-lg font-semibold text-gray-900">Storage</h2>
            {saved && <p className="text-sm text-green-600">Saved.</p>}
            <div>
                <label className="block text-sm font-medium text-gray-700 mb-1">Storage Driver</label>
                <select className="block w-full rounded border border-gray-300 px-3 py-2 text-sm focus:border-blue-500 focus:outline-none" value={driver} onChange={e => setDriver(e.target.value)}>
                    <option value="local">Local</option>
                    <option value="s3">Amazon S3</option>
                </select>
            </div>
            {driver === 's3' && (
                <>
                    {[
                        ['S3 Bucket',    s3Bucket, setS3Bucket, 'text'],
                        ['S3 Region',    s3Region, setS3Region, 'text'],
                        ['Access Key',   s3Key,    setS3Key,    'text'],
                        ['Secret Key',   s3Secret, setS3Secret, 'password'],
                    ].map(([label, val, setter, type]) => (
                        <div key={label}>
                            <label className="block text-sm font-medium text-gray-700 mb-1">{label}</label>
                            <input type={type} className="block w-full rounded border border-gray-300 px-3 py-2 text-sm focus:border-blue-500 focus:outline-none" value={val} onChange={e => setter(e.target.value)} />
                        </div>
                    ))}
                </>
            )}
            <div>
                <label className="block text-sm font-medium text-gray-700 mb-1">Max Upload Size (MB)</label>
                <input type="number" min="1" max="200" className="block w-32 rounded border border-gray-300 px-3 py-2 text-sm focus:border-blue-500 focus:outline-none" value={maxUploadMb} onChange={e => setMaxUploadMb(e.target.value)} />
            </div>
            <button type="submit" disabled={save.isPending} className="rounded bg-blue-600 px-5 py-2 text-sm font-medium text-white hover:bg-blue-700 disabled:opacity-50">
                {save.isPending ? 'Saving…' : 'Save'}
            </button>
        </form>
    );
}
