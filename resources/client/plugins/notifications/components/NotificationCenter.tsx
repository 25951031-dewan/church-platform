import {useMemo} from 'react';
import {useMarkAsRead, useNotifications} from '../hooks';
import {NotificationItem} from './NotificationItem';

export function NotificationCenter() {
  const {data, fetchNextPage, hasNextPage, isFetchingNextPage} = useNotifications();
  const markRead = useMarkAsRead();

  const notifications = useMemo(
    () => data?.pages.flatMap(page => page.data ?? []) ?? [],
    [data],
  );

  return (
    <div className="space-y-3">
      {notifications.map(notification => (
        <NotificationItem key={notification.id} notification={notification} onRead={id => markRead.mutate(id)} />
      ))}

      {hasNextPage && (
        <button className="rounded-md bg-primary-600 px-4 py-2 text-sm text-white" disabled={isFetchingNextPage} onClick={() => fetchNextPage()}>
          {isFetchingNextPage ? 'Loading...' : 'Load more'}
        </button>
      )}
    </div>
  );
}
