import { useMutation, useQuery, useQueryClient } from '@tanstack/react-query';
import axios from 'axios';
import { useState } from 'react';

const FEATURE_LABELS = {
    announcement:  'Announcements',
    verse:         'Daily Verse',
    blessing:      'Blessings',
    prayer:        'Prayer Wall',
    blog:          'Blog / Posts',
    events:        'Events',
    library:       'Library',
    bible_studies: 'Bible Studies',
    testimony:     'Testimonies',
    galleries:     'Galleries',
    ministries:    'Ministries',
    sermons:       'Sermons',
    reviews:       'Reviews',
    hymns:         'Hymns',
    bible_reader:  'Bible Reader',
};

function Toggle({ enabled, onChange, disabled }) {
    return (
        <button
            type="button"
            role="switch"
            aria-checked={enabled}
            disabled={disabled}
            onClick={() => onChange(!enabled)}
            className={[
                'relative inline-flex h-6 w-11 shrink-0 cursor-pointer rounded-full border-2 border-transparent',
                'transition-colors duration-200 ease-in-out focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2',
                enabled ? 'bg-blue-600' : 'bg-gray-200',
                disabled ? 'opacity-50 cursor-not-allowed' : '',
            ].join(' ')}
        >
            <span
                className={[
                    'pointer-events-none inline-block h-5 w-5 transform rounded-full bg-white shadow ring-0',
                    'transition duration-200 ease-in-out',
                    enabled ? 'translate-x-5' : 'translate-x-0',
                ].join(' ')}
            />
        </button>
    );
}

function SectionHeading({ title, description }) {
    return (
        <div className="mb-6">
            <h2 className="text-lg font-semibold text-gray-900">{title}</h2>
            {description && <p className="mt-1 text-sm text-gray-500">{description}</p>}
        </div>
    );
}

export default function SettingsManager() {
    const queryClient = useQueryClient();
    const [saved, setSaved] = useState(false);

    const { data: settings, isLoading } = useQuery({
        queryKey: ['admin-settings'],
        queryFn:  () => axios.get('/api/v1/admin/settings').then(r => r.data),
    });

    const mutation = useMutation({
        mutationFn: (payload) => axios.patch('/api/v1/admin/settings', payload),
        onSuccess: () => {
            queryClient.invalidateQueries({ queryKey: ['admin-settings'] });
            setSaved(true);
            setTimeout(() => setSaved(false), 2500);
        },
    });

    function handlePlatformModeChange(mode) {
        mutation.mutate({ platform_mode: mode });
    }

    function handleDirectoryToggle(value) {
        mutation.mutate({ show_church_directory: value });
    }

    function handleDefaultChurchChange(e) {
        mutation.mutate({ default_church_id: e.target.value || null });
    }

    function handleFeatureToggle(feature, value) {
        mutation.mutate({
            feature_toggles: {
                ...(settings?.feature_toggles ?? {}),
                [feature]: value,
            },
        });
    }

    if (isLoading) {
        return (
            <div className="flex items-center justify-center p-12 text-gray-400">
                Loading settings…
            </div>
        );
    }

    const platformMode     = settings?.platform_mode ?? 'single';
    const showDirectory    = settings?.show_church_directory ?? false;
    const defaultChurchId  = settings?.default_church_id ?? '';
    const featureToggles   = settings?.feature_toggles ?? {};
    const isSaving         = mutation.isPending;

    return (
        <div className="mx-auto max-w-3xl space-y-10 py-8 px-4 sm:px-6">

            {/* ── Save feedback ─────────────────────────────────── */}
            {saved && (
                <div className="rounded-md bg-green-50 px-4 py-3 text-sm font-medium text-green-800 ring-1 ring-green-300">
                    Settings saved.
                </div>
            )}
            {mutation.isError && (
                <div className="rounded-md bg-red-50 px-4 py-3 text-sm font-medium text-red-800 ring-1 ring-red-300">
                    Failed to save. Please try again.
                </div>
            )}

            {/* ── Platform Mode ─────────────────────────────────── */}
            <section>
                <SectionHeading
                    title="Platform Mode"
                    description="Choose how this installation handles churches."
                />

                <div className="space-y-3">
                    {[
                        {
                            value: 'single',
                            label: 'Single Church',
                            hint:  'One church. The directory is hidden.',
                        },
                        {
                            value: 'multi',
                            label: 'Multi-Church',
                            hint:  'Multiple churches. A public directory is available.',
                        },
                    ].map(({ value, label, hint }) => (
                        <label
                            key={value}
                            className={[
                                'flex cursor-pointer items-start gap-4 rounded-lg border p-4 transition',
                                platformMode === value
                                    ? 'border-blue-600 bg-blue-50'
                                    : 'border-gray-200 hover:border-gray-300',
                            ].join(' ')}
                        >
                            <input
                                type="radio"
                                name="platform_mode"
                                value={value}
                                checked={platformMode === value}
                                onChange={() => handlePlatformModeChange(value)}
                                disabled={isSaving}
                                className="mt-0.5 h-4 w-4 text-blue-600 focus:ring-blue-500"
                            />
                            <div>
                                <div className="font-medium text-gray-900">{label}</div>
                                <div className="text-sm text-gray-500">{hint}</div>
                            </div>
                        </label>
                    ))}
                </div>

                {/* Church directory toggle — only relevant in multi mode */}
                {platformMode === 'multi' && (
                    <div className="mt-5 flex items-center justify-between rounded-lg border border-gray-200 p-4">
                        <div>
                            <div className="font-medium text-gray-900">Show Church Directory</div>
                            <div className="text-sm text-gray-500">
                                Display a public /churches browsing page.
                            </div>
                        </div>
                        <Toggle
                            enabled={showDirectory}
                            onChange={handleDirectoryToggle}
                            disabled={isSaving}
                        />
                    </div>
                )}

                {/* Default church selector — only relevant in single mode */}
                {platformMode === 'single' && (
                    <div className="mt-5 rounded-lg border border-gray-200 p-4">
                        <label className="block text-sm font-medium text-gray-900">
                            Default Church
                        </label>
                        <p className="mb-2 text-sm text-gray-500">
                            All content is scoped to this church.
                        </p>
                        <input
                            type="number"
                            min="1"
                            placeholder="Church ID"
                            value={defaultChurchId}
                            onChange={handleDefaultChurchChange}
                            disabled={isSaving}
                            className="block w-full rounded-md border border-gray-300 px-3 py-2 text-sm
                                       focus:border-blue-500 focus:outline-none focus:ring-1 focus:ring-blue-500"
                        />
                    </div>
                )}
            </section>

            {/* ── Feature Toggles ───────────────────────────────── */}
            <section>
                <SectionHeading
                    title="Feature Toggles"
                    description="Enable or disable content modules platform-wide."
                />

                <div className="grid gap-3 sm:grid-cols-2">
                    {Object.entries(FEATURE_LABELS).map(([feature, label]) => {
                        const enabled = featureToggles[feature] !== false;

                        return (
                            <div
                                key={feature}
                                className="flex items-center justify-between rounded-lg border border-gray-200 px-4 py-3"
                            >
                                <span className="text-sm font-medium text-gray-900">{label}</span>
                                <Toggle
                                    enabled={enabled}
                                    onChange={(val) => handleFeatureToggle(feature, val)}
                                    disabled={isSaving}
                                />
                            </div>
                        );
                    })}
                </div>
            </section>

        </div>
    );
}
