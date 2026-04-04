import type { Conversation } from '../types';
import { UnreadBadge } from './UnreadBadge';

interface Props {
  conversation: Conversation;
  isActive: boolean;
  onClick: () => void;
}

/**
 * Single conversation item in the list.
 */
export function ConversationCard({ conversation, isActive, onClick }: Props) {
  const formatTime = (dateString: string) => {
    const date = new Date(dateString);
    const now = new Date();
    const diffDays = Math.floor((now.getTime() - date.getTime()) / (1000 * 60 * 60 * 24));

    if (diffDays === 0) {
      return date.toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' });
    } else if (diffDays === 1) {
      return 'Yesterday';
    } else if (diffDays < 7) {
      return date.toLocaleDateString([], { weekday: 'short' });
    }
    return date.toLocaleDateString([], { month: 'short', day: 'numeric' });
  };

  const getPreviewText = () => {
    if (!conversation.latest_message) return 'No messages yet';
    
    const msg = conversation.latest_message;
    if (msg.type !== 'text') {
      const icons: Record<string, string> = {
        image: '📷 Image',
        file: '📎 File',
        audio: '🎵 Audio',
      };
      return icons[msg.type] || 'Attachment';
    }
    return msg.body || '';
  };

  // Get avatar from first user (for direct) or use group icon
  const avatarUrl = conversation.users[0]?.avatar || null;
  const initials = conversation.display_name
    .split(' ')
    .map((n) => n[0])
    .join('')
    .toUpperCase()
    .slice(0, 2);

  return (
    <button
      onClick={onClick}
      className={`w-full p-4 flex items-start gap-3 hover:bg-white/5 transition-colors border-b border-white/10 ${
        isActive ? 'bg-indigo-500/20 border-l-4 border-l-indigo-500' : ''
      }`}
    >
      {/* Avatar */}
      <div className="relative flex-shrink-0">
        {avatarUrl ? (
          <img
            src={avatarUrl}
            alt={conversation.display_name}
            className="w-12 h-12 rounded-full object-cover"
          />
        ) : (
          <div className="w-12 h-12 rounded-full bg-white/10 flex items-center justify-center text-gray-400 font-semibold">
            {initials}
          </div>
        )}
        {conversation.type === 'group' && (
          <span className="absolute -bottom-1 -right-1 bg-gray-500 text-white text-xs rounded-full w-5 h-5 flex items-center justify-center">
            {conversation.users.length}
          </span>
        )}
      </div>

      {/* Content */}
      <div className="flex-1 min-w-0 text-left">
        <div className="flex items-center justify-between gap-2">
          <h3 className="font-semibold text-white truncate">
            {conversation.display_name}
          </h3>
          <div className="flex items-center gap-2 flex-shrink-0">
            {conversation.unread_count > 0 && (
              <UnreadBadge count={conversation.unread_count} />
            )}
            <span className="text-xs text-gray-400">
              {formatTime(conversation.updated_at)}
            </span>
          </div>
        </div>
        <p className="text-sm text-gray-500 truncate mt-0.5">
          {conversation.latest_message?.user.name && (
            <span className="font-medium">
              {conversation.latest_message.user.name}:{' '}
            </span>
          )}
          {getPreviewText()}
        </p>
      </div>
    </button>
  );
}
