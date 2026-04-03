import {useUnreadCount} from '../hooks';

export function UnreadNotificationBadge() {
  const {data: count = 0} = useUnreadCount();

  if (!count) return null;

  return (
    <span className="inline-flex h-5 min-w-[20px] items-center justify-center rounded-full bg-red-600 px-1.5 text-xs text-white">
      {count > 99 ? '99+' : count}
    </span>
  );
}
