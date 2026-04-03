import {useMutation, useQuery, useQueryClient} from '@tanstack/react-query';
import {apiClient} from '@app/common/http/api-client';

export function LiveMeetingSettingsPage() {
  const queryClient = useQueryClient();
  const {data} = useQuery({
    queryKey: ['settings', 'live_meetings'],
    queryFn: () => apiClient.get('settings/live_meetings').then(r => r.data.settings),
  });

  const save = useMutation({
    mutationFn: (settings: Record<string, string>) => apiClient.put('settings', {settings}),
    onSuccess: () => queryClient.invalidateQueries({queryKey: ['settings', 'live_meetings']}),
  });

  const settings = data ?? {};

  return (
    <div className="space-y-4">
      <h1 className="text-2xl font-bold">Live Meetings Settings (Section 22)</h1>
      <div className="rounded border p-4 text-sm">
        <p>Zoom Client ID: {settings['live_meetings.zoom_client_id'] || 'Not set'}</p>
        <p>Default Platform: {settings['live_meetings.default_platform'] || 'zoom'}</p>
        <button
          className="mt-3 rounded bg-primary-600 px-3 py-2 text-white"
          onClick={() =>
            save.mutate({
              'live_meetings.section': '22',
              'live_meetings.enabled': '1',
            })
          }
        >
          Save defaults
        </button>
      </div>
    </div>
  );
}
