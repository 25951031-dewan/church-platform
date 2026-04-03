import {useQuery} from '@tanstack/react-query';
import {apiClient} from '@app/common/http/api-client';

export function NotificationLogsPage() {
  const {data, isLoading} = useQuery({
    queryKey: ['admin', 'notification-logs'],
    queryFn: () => apiClient.get('admin/notification-logs').then(r => r.data),
  });

  const logs = data?.data ?? [];

  return (
    <div>
      <h1 className="mb-4 text-2xl font-bold">Notification Logs</h1>
      {isLoading ? <p>Loading...</p> : (
        <div className="space-y-2">
          {logs.map((log: any) => (
            <div key={log.id} className="rounded border p-3 text-sm">
              <p><strong>User:</strong> {log.user?.name ?? log.user_id}</p>
              <p><strong>Channel:</strong> {log.channel}</p>
              <p><strong>Status:</strong> {log.status}</p>
            </div>
          ))}
        </div>
      )}
    </div>
  );
}
