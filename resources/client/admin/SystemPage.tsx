import { useState, useRef, useEffect } from 'react';
import { useQuery, useMutation } from '@tanstack/react-query';
import { apiClient } from '@app/common/http/api-client';

type Tab = 'status' | 'git' | 'upload';

interface SystemStatus {
  app_name: string;
  app_version: string;
  release_date: string;
  laravel_version: string;
  php_version: string;
  environment: string;
  git?: {
    available: boolean;
    branch?: string;
    commit?: string;
    last_message?: string;
    last_date?: string;
    behind?: number;
  };
  server: {
    os: string;
    disk_free: string;
    disk_total: string;
    memory_limit: string;
    upload_max_filesize: string;
  };
  database: { connected: boolean; driver?: string };
}

function StatusBadge({ ok, label }: { ok: boolean; label: string }) {
  return (
    <span
      className={`inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-xs font-medium ${
        ok
          ? 'bg-green-100 text-green-700 dark:bg-green-900/30 dark:text-green-400'
          : 'bg-red-100 text-red-700 dark:bg-red-900/30 dark:text-red-400'
      }`}
    >
      <span className={`w-1.5 h-1.5 rounded-full ${ok ? 'bg-green-500' : 'bg-red-500'}`} />
      {label}
    </span>
  );
}

function OutputBox({ output }: { output: string }) {
  if (!output) return null;
  return (
    <pre className="mt-3 p-3 bg-gray-900 text-green-400 text-xs rounded-lg overflow-x-auto whitespace-pre-wrap max-h-64">
      {output}
    </pre>
  );
}

function ActionButton({
  onClick,
  loading,
  label,
  loadingLabel,
  variant = 'primary',
}: {
  onClick: () => void;
  loading: boolean;
  label: string;
  loadingLabel: string;
  variant?: 'primary' | 'danger' | 'secondary';
}) {
  const colors = {
    primary: 'bg-blue-600 hover:bg-blue-700 text-white',
    danger: 'bg-red-600 hover:bg-red-700 text-white',
    secondary: 'bg-gray-200 hover:bg-gray-300 text-gray-800 dark:bg-gray-700 dark:hover:bg-gray-600 dark:text-gray-200',
  };
  return (
    <button
      onClick={onClick}
      disabled={loading}
      className={`px-4 py-2 rounded-lg text-sm font-medium transition-colors disabled:opacity-50 ${colors[variant]}`}
    >
      {loading ? loadingLabel : label}
    </button>
  );
}

// ── Status Tab ──────────────────────────────────────────────────────────────

function StatusTab() {
  const { data, isLoading, refetch } = useQuery({
    queryKey: ['system-status'],
    queryFn: () => apiClient.get<SystemStatus>('/api/v1/system/status').then((r) => r.data),
  });

  if (isLoading) return <p className="text-gray-500 text-sm">Loading system info…</p>;
  if (!data) return null;

  const s = data;

  return (
    <div className="space-y-4">
      <div className="flex items-center justify-between">
        <h2 className="text-lg font-semibold text-gray-900 dark:text-white">System Status</h2>
        <button onClick={() => refetch()} className="text-xs text-blue-600 hover:underline">
          Refresh
        </button>
      </div>

      <div className="grid grid-cols-1 sm:grid-cols-2 gap-4">
        <Card label="App Version" value={`v${s.app_version}`} sub={s.release_date} />
        <Card label="PHP" value={s.php_version} />
        <Card label="Laravel" value={s.laravel_version} />
        <Card label="Environment" value={s.environment} />
        <Card label="Memory Limit" value={s.server.memory_limit} sub={`Upload max: ${s.server.upload_max_filesize}`} />
        <Card label="Disk Free" value={s.server.disk_free} sub={`Total: ${s.server.disk_total}`} />
      </div>

      <div className="flex flex-wrap gap-2">
        <StatusBadge ok={s.database.connected} label={`DB: ${s.database.connected ? s.database.driver : 'disconnected'}`} />
        {s.git?.available && (
          <>
            <StatusBadge ok={true} label={`Branch: ${s.git.branch}`} />
            <StatusBadge ok={(s.git.behind ?? 0) === 0} label={s.git.behind === 0 ? 'Up to date' : `${s.git.behind} behind`} />
          </>
        )}
      </div>

      {s.git?.available && (
        <div className="text-xs text-gray-500 dark:text-gray-400 space-y-0.5">
          <p>Commit: <code className="font-mono">{s.git.commit}</code> — {s.git.last_message}</p>
          <p>{s.git.last_date}</p>
        </div>
      )}
    </div>
  );
}

function Card({ label, value, sub }: { label: string; value: string; sub?: string }) {
  return (
    <div className="bg-gray-50 dark:bg-gray-700/50 rounded-lg p-4">
      <p className="text-xs text-gray-500 dark:text-gray-400 uppercase tracking-wide">{label}</p>
      <p className="text-base font-semibold text-gray-900 dark:text-white mt-0.5">{value}</p>
      {sub && <p className="text-xs text-gray-400 dark:text-gray-500 mt-0.5">{sub}</p>}
    </div>
  );
}

// ── Git Deploy Tab ──────────────────────────────────────────────────────────

function GitDeployTab() {
  const [output, setOutput] = useState('');

  const run = (endpoint: string, label: string) =>
    useMutation({
      mutationFn: () => apiClient.post(`/api/v1/system/${endpoint}`).then((r) => r.data),
      onSuccess: (data: any) => setOutput(`✅ ${label}\n\n${data.data?.output ?? data.data?.pull_output ?? JSON.stringify(data.data, null, 2)}`),
      onError: (e: any) => setOutput(`❌ ${label} failed\n\n${e.response?.data?.message ?? e.message}`),
    });

  // eslint-disable-next-line react-hooks/rules-of-hooks
  const pull = useMutation({
    mutationFn: () => apiClient.post('/api/v1/system/git-pull').then((r) => r.data),
    onSuccess: (data: any) => setOutput(`✅ Git Pull\n\n${data.data?.pull_output ?? data.message}`),
    onError: (e: any) => setOutput(`❌ Git Pull failed\n\n${e.response?.data?.message ?? e.message}`),
  });

  const migrate = useMutation({
    mutationFn: () => apiClient.post('/api/v1/system/migrate').then((r) => r.data),
    onSuccess: (data: any) => setOutput(`✅ Migrations\n\n${data.data?.output}`),
    onError: (e: any) => setOutput(`❌ Migration failed\n\n${e.response?.data?.message ?? e.message}`),
  });

  const clearCache = useMutation({
    mutationFn: () => apiClient.post('/api/v1/system/clear-cache').then((r) => r.data),
    onSuccess: () => setOutput('✅ All caches cleared.'),
    onError: (e: any) => setOutput(`❌ ${e.response?.data?.message ?? e.message}`),
  });

  const optimize = useMutation({
    mutationFn: () => apiClient.post('/api/v1/system/optimize').then((r) => r.data),
    onSuccess: () => setOutput('✅ Application optimized.'),
    onError: (e: any) => setOutput(`❌ ${e.response?.data?.message ?? e.message}`),
  });

  const deploy = useMutation({
    mutationFn: () => apiClient.post('/api/v1/system/deploy').then((r) => r.data),
    onSuccess: (data: any) => {
      const steps = (data.data?.steps ?? [])
        .map((s: any) => `${s.success ? '✅' : '❌'} ${s.step}: ${s.message}`)
        .join('\n');
      setOutput(`${data.success ? '✅ Deploy complete' : '❌ Deploy had errors'}\n\n${steps}`);
    },
    onError: (e: any) => setOutput(`❌ Deploy failed\n\n${e.response?.data?.message ?? e.message}`),
  });

  const anyLoading = pull.isPending || migrate.isPending || clearCache.isPending || optimize.isPending || deploy.isPending;

  return (
    <div className="space-y-6">
      <div>
        <h2 className="text-lg font-semibold text-gray-900 dark:text-white mb-1">Git Deploy</h2>
        <p className="text-sm text-gray-500 dark:text-gray-400">For VPS or any server with git access.</p>
      </div>

      {/* One-shot deploy */}
      <div className="bg-blue-50 dark:bg-blue-900/20 border border-blue-200 dark:border-blue-800 rounded-lg p-4">
        <h3 className="text-sm font-semibold text-blue-800 dark:text-blue-300 mb-1">Full Deploy Pipeline</h3>
        <p className="text-xs text-blue-600 dark:text-blue-400 mb-3">Pull → Migrate → Build → Clear cache → Optimize</p>
        <ActionButton
          onClick={() => deploy.mutate()}
          loading={deploy.isPending}
          label="Run Full Deploy"
          loadingLabel="Deploying…"
        />
      </div>

      {/* Step by step */}
      <div>
        <h3 className="text-sm font-semibold text-gray-700 dark:text-gray-300 mb-3">Individual Steps</h3>
        <div className="flex flex-wrap gap-2">
          <ActionButton onClick={() => pull.mutate()} loading={pull.isPending} label="Git Pull" loadingLabel="Pulling…" variant="secondary" />
          <ActionButton onClick={() => migrate.mutate()} loading={migrate.isPending} label="Run Migrations" loadingLabel="Migrating…" variant="secondary" />
          <ActionButton onClick={() => clearCache.mutate()} loading={clearCache.isPending} label="Clear Cache" loadingLabel="Clearing…" variant="secondary" />
          <ActionButton onClick={() => optimize.mutate()} loading={optimize.isPending} label="Optimize" loadingLabel="Optimizing…" variant="secondary" />
        </div>
      </div>

      <OutputBox output={output} />
    </div>
  );
}

// ── Upload Update Tab ───────────────────────────────────────────────────────

function UploadUpdateTab() {
  const [output, setOutput] = useState('');
  const [step, setStep] = useState<'idle' | 'uploaded' | 'done'>('idle');
  const fileRef = useRef<HTMLInputElement>(null);

  const upload = useMutation({
    mutationFn: (file: File) => {
      const fd = new FormData();
      fd.append('update_package', file);
      return apiClient.post('/api/v1/update/upload', fd, {
        headers: { 'Content-Type': 'multipart/form-data' },
      }).then((r) => r.data);
    },
    onSuccess: (data: any) => {
      setOutput(`✅ ${data.message}`);
      setStep('uploaded');
    },
    onError: (e: any) => setOutput(`❌ Upload failed\n\n${e.response?.data?.message ?? e.message}`),
  });

  const migrate = useMutation({
    mutationFn: () => apiClient.post('/api/v1/update/migrate').then((r) => r.data),
    onSuccess: (data: any) => setOutput((prev) => prev + `\n\n✅ Migrations\n${data.data?.output ?? ''}`),
    onError: (e: any) => setOutput((prev) => prev + `\n\n❌ Migration failed\n${e.response?.data?.message ?? e.message}`),
  });

  const clearCaches = useMutation({
    mutationFn: () => apiClient.post('/api/v1/update/clear-caches').then((r) => r.data),
    onSuccess: () => {
      setOutput((prev) => prev + '\n\n✅ Caches cleared. Update complete!');
      setStep('done');
    },
    onError: (e: any) => setOutput((prev) => prev + `\n\n❌ ${e.response?.data?.message ?? e.message}`),
  });

  function handleFileChange(e: React.ChangeEvent<HTMLInputElement>) {
    const file = e.target.files?.[0];
    if (file) upload.mutate(file);
  }

  return (
    <div className="space-y-6">
      <div>
        <h2 className="text-lg font-semibold text-gray-900 dark:text-white mb-1">Upload Update</h2>
        <p className="text-sm text-gray-500 dark:text-gray-400">For shared hosting — upload a .zip package, then run migrations.</p>
      </div>

      {/* Step 1: Upload */}
      <div className="border-2 border-dashed border-gray-300 dark:border-gray-600 rounded-lg p-6 text-center">
        <input ref={fileRef} type="file" accept=".zip" onChange={handleFileChange} className="hidden" />
        <p className="text-sm text-gray-600 dark:text-gray-400 mb-3">
          {upload.isPending ? 'Uploading and extracting…' : 'Select a .zip update package'}
        </p>
        <ActionButton
          onClick={() => fileRef.current?.click()}
          loading={upload.isPending}
          label="Choose .zip file"
          loadingLabel="Extracting…"
          variant="secondary"
        />
      </div>

      {/* Step 2: Migrate + Clear caches (shown after upload) */}
      {step !== 'idle' && (
        <div className="bg-gray-50 dark:bg-gray-700/50 rounded-lg p-4 space-y-3">
          <h3 className="text-sm font-semibold text-gray-700 dark:text-gray-300">Complete the update</h3>
          <div className="flex gap-2">
            <ActionButton
              onClick={() => migrate.mutate()}
              loading={migrate.isPending}
              label="Run Migrations"
              loadingLabel="Migrating…"
            />
            <ActionButton
              onClick={() => clearCaches.mutate()}
              loading={clearCaches.isPending}
              label="Clear Caches"
              loadingLabel="Clearing…"
              variant="secondary"
            />
          </div>
        </div>
      )}

      <OutputBox output={output} />

      {step === 'done' && (
        <p className="text-sm font-medium text-green-600 dark:text-green-400">
          Update applied successfully. Refresh the page to see the new version.
        </p>
      )}
    </div>
  );
}

// ── Main Page ───────────────────────────────────────────────────────────────

const TABS: { id: Tab; label: string }[] = [
  { id: 'status', label: 'Status' },
  { id: 'git', label: 'Git Deploy' },
  { id: 'upload', label: 'Upload Update' },
];

export function SystemPage() {
  const [tab, setTab] = useState<Tab>('status');

  return (
    <div className="max-w-3xl mx-auto">
      <h1 className="text-2xl font-bold text-gray-900 dark:text-white mb-6">System</h1>

      {/* Tab bar */}
      <div className="flex border-b border-gray-200 dark:border-gray-700 mb-6">
        {TABS.map((t) => (
          <button
            key={t.id}
            onClick={() => setTab(t.id)}
            className={`px-4 py-2 text-sm font-medium border-b-2 transition-colors ${
              tab === t.id
                ? 'border-blue-600 text-blue-600 dark:border-blue-400 dark:text-blue-400'
                : 'border-transparent text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-200'
            }`}
          >
            {t.label}
          </button>
        ))}
      </div>

      <div className="bg-white dark:bg-gray-800 rounded-lg shadow p-6">
        {tab === 'status' && <StatusTab />}
        {tab === 'git' && <GitDeployTab />}
        {tab === 'upload' && <UploadUpdateTab />}
      </div>
    </div>
  );
}
