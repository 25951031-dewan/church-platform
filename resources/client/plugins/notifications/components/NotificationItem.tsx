import type {AppNotification} from '../types';

interface Props {
  notification: AppNotification;
  onRead?: (id: string) => void;
}

export function NotificationItem({notification, onRead}: Props) {
  return (
    <div className={`p-3 border rounded-lg ${notification.read_at ? 'bg-white dark:bg-gray-800' : 'bg-blue-50 dark:bg-blue-900/20'}`}>
      <div className="flex items-start justify-between gap-3">
        <div>
          <p className="font-semibold text-sm text-gray-900 dark:text-white">{notification.data.title}</p>
          <p className="text-sm text-gray-600 dark:text-gray-300">{notification.data.body}</p>
          {notification.data.url && (
            <a href={notification.data.url} className="text-xs text-primary-600 hover:underline" onClick={() => onRead?.(notification.id)}>
              Open
            </a>
          )}
        </div>
        {!notification.read_at && (
          <button className="text-xs text-primary-600" onClick={() => onRead?.(notification.id)}>
            Mark read
          </button>
        )}
      </div>
    </div>
  );
}
