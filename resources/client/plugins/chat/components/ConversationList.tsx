import { useConversations } from '../hooks/useConversations';
import { ConversationCard } from './ConversationCard';

interface Props {
  activeConversationId?: number;
  onSelectConversation: (id: number) => void;
  onNewChat?: () => void;
}

/**
 * Sidebar list of all user conversations.
 */
export function ConversationList({
  activeConversationId,
  onSelectConversation,
  onNewChat,
}: Props) {
  const { data: conversations, isLoading, error } = useConversations();

  return (
    <div className="w-80 border-r h-full flex flex-col bg-white">
      {/* Header */}
      <div className="p-4 border-b flex items-center justify-between">
        <h2 className="text-xl font-bold text-gray-900">Messages</h2>
        {onNewChat && (
          <button
            onClick={onNewChat}
            className="p-2 text-blue-600 hover:bg-blue-50 rounded-full transition-colors"
            title="New conversation"
          >
            <svg
              xmlns="http://www.w3.org/2000/svg"
              className="h-6 w-6"
              fill="none"
              viewBox="0 0 24 24"
              stroke="currentColor"
            >
              <path
                strokeLinecap="round"
                strokeLinejoin="round"
                strokeWidth={2}
                d="M12 4v16m8-8H4"
              />
            </svg>
          </button>
        )}
      </div>

      {/* Conversation List */}
      <div className="flex-1 overflow-y-auto">
        {isLoading && (
          <div className="p-4 text-center text-gray-500">
            Loading conversations...
          </div>
        )}

        {error && (
          <div className="p-4 text-center text-red-500">
            Failed to load conversations
          </div>
        )}

        {conversations?.length === 0 && !isLoading && (
          <div className="p-4 text-center text-gray-500">
            <p>No conversations yet</p>
            {onNewChat && (
              <button
                onClick={onNewChat}
                className="mt-2 text-blue-600 hover:underline"
              >
                Start a new chat
              </button>
            )}
          </div>
        )}

        {conversations?.map((conv) => (
          <ConversationCard
            key={conv.id}
            conversation={conv}
            isActive={conv.id === activeConversationId}
            onClick={() => onSelectConversation(conv.id)}
          />
        ))}
      </div>
    </div>
  );
}
