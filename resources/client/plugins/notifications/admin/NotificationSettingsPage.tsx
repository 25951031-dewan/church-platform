import {useMutation, useQuery, useQueryClient} from '@tanstack/react-query';
import {apiClient} from '@app/common/http/api-client';

export function NotificationSettingsPage() {
  const queryClient = useQueryClient();
  const {data} = useQuery({
    queryKey: ['settings', 'notifications'],
    queryFn: () => apiClient.get('settings/notifications').then(r => r.data.settings),
  });

  const save = useMutation({
    mutationFn: (settings: Record<string, string>) => apiClient.put('settings', {settings}),
    onSuccess: () => queryClient.invalidateQueries({queryKey: ['settings', 'notifications']}),
  });

  const settings = data ?? {};

  return (
    <div className="space-y-4">
      <h1 className="text-2xl font-bold">Notifications Settings (Section 21)</h1>
      <div className="rounded border p-4 text-sm">
        <p>OneSignal App ID: {settings['notifications.onesignal_app_id'] || 'Not set'}</p>
        <p>Twilio SID: {settings['notifications.twilio_sid'] || 'Not set'}</p>
        <button
          className="mt-3 rounded bg-primary-600 px-3 py-2 text-white"
          onClick={() =>
            save.mutate({
              'notifications.section': '21',
              'notifications.enabled': '1',
            })
          }
        >
          Save defaults
        </button>
      </div>
    </div>
  );
}
