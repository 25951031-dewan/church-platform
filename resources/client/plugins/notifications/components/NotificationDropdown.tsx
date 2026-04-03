import {useMemo} from 'react';
import {useMarkAllAsRead, useMarkAsRead, useNotifications} from '../hooks';
import {NotificationItem} from './NotificationItem';
import {UnreadNotificationBadge} from './UnreadNotificationBadge';

export function NotificationDropdown() {
  const {data} = useNotifications(5);
  const markRead = useMarkAsRead();
  const markAll = useMarkAllAsRead();

  const notifications = useMemo(
    () => data?.pages.flatMap(page => page.data ?? []) ?? [],
    [data],
  );

  return (
    <div className="w-full max-w-md rounded-xl border border-gray-200 bg-white p-4 shadow dark:border-gray-700 dark:bg-gray-900">
      <div className="mb-3 flex items-center justify-between">
        <h3 className="font-semibold text-gray-900 dark:text-white">Notifications</h3>
        <div className="flex items-center gap-2">
          <UnreadNotificationBadge />
          <button className="text-xs text-primary-600" onClick={() => markAll.mutate()}>
            Mark all read
          </button>
        </div>
      </div>
      <div className="space-y-2">
        {notifications.length ? notifications.map(n => (
          <NotificationItem key={n.id} notification={n} onRead={id => markRead.mutate(id)} />
        )) : <p className="text-sm text-gray-500">No notifications yet.</p>}
      </div>
    </div>
  );
}
