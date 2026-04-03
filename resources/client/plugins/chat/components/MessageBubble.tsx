import type { Message } from '../types';
import { useAuth } from '@app/common/auth/use-auth';

interface Props {
  message: Message;
}

/**
 * Single message bubble (sent or received).
 */
export function MessageBubble({ message }: Props) {
  const { user } = useAuth();
  const isSent = message.user_id === user?.id;

  const formatTime = (dateString: string) => {
    return new Date(dateString).toLocaleTimeString([], {
      hour: '2-digit',
      minute: '2-digit',
    });
  };

  return (
    <div className={`flex ${isSent ? 'justify-end' : 'justify-start'} mb-4`}>
      <div className="flex items-end gap-2 max-w-[70%]">
        {/* Avatar for received messages */}
        {!isSent && (
          <div className="flex-shrink-0">
            {message.user.avatar ? (
              <img
                src={message.user.avatar}
                alt={message.user.name}
                className="w-8 h-8 rounded-full"
              />
            ) : (
              <div className="w-8 h-8 rounded-full bg-gray-300 flex items-center justify-center text-xs font-semibold text-gray-600">
                {message.user.name[0].toUpperCase()}
              </div>
            )}
          </div>
        )}

        {/* Message content */}
        <div
          className={`rounded-2xl px-4 py-2 ${
            isSent
              ? 'bg-blue-500 text-white rounded-br-md'
              : 'bg-gray-100 text-gray-900 rounded-bl-md'
          }`}
        >
          {/* Sender name for received messages */}
          {!isSent && (
            <p className="text-xs font-semibold text-gray-600 mb-1">
              {message.user.name}
            </p>
          )}

          {/* Message body */}
          {message.type === 'text' && message.body && (
            <p className="whitespace-pre-wrap break-words">{message.body}</p>
          )}

          {/* Image attachment */}
          {message.type === 'image' && (
            <div className="mt-1">
              <p className="text-sm opacity-75">📷 Image</p>
              {/* TODO: Render actual image when file system is connected */}
            </div>
          )}

          {/* File attachment */}
          {message.type === 'file' && (
            <div className="mt-1">
              <p className="text-sm opacity-75">📎 File attachment</p>
            </div>
          )}

          {/* Audio attachment */}
          {message.type === 'audio' && (
            <div className="mt-1">
              <p className="text-sm opacity-75">🎵 Audio message</p>
            </div>
          )}

          {/* Timestamp */}
          <span
            className={`text-xs ${
              isSent ? 'text-blue-100' : 'text-gray-400'
            } block mt-1`}
          >
            {formatTime(message.created_at)}
          </span>
        </div>
      </div>
    </div>
  );
}
