import { useState } from 'react';
import { ConversationList } from '../components/ConversationList';
import { MessageThread } from '../components/MessageThread';
import { NewConversationModal } from '../components/NewConversationModal';
import { useConversations } from '../hooks/useConversations';

/**
 * Main chat page with conversation list and message thread.
 */
export function ChatPage() {
  const [activeConversationId, setActiveConversationId] = useState<number>();
  const [isNewChatModalOpen, setIsNewChatModalOpen] = useState(false);
  const { data: conversations } = useConversations();

  const activeConversation = conversations?.find(
    (c) => c.id === activeConversationId
  );

  const handleConversationCreated = (conversationId: number) => {
    setActiveConversationId(conversationId);
  };

  return (
    <div className="h-screen flex bg-gray-100">
      {/* Conversation List */}
      <ConversationList
        activeConversationId={activeConversationId}
        onSelectConversation={setActiveConversationId}
        onNewChat={() => setIsNewChatModalOpen(true)}
      />

      {/* Message Thread or Empty State */}
      {activeConversation ? (
        <MessageThread conversation={activeConversation} />
      ) : (
        <div className="flex-1 flex flex-col items-center justify-center bg-gray-50">
          <svg
            xmlns="http://www.w3.org/2000/svg"
            className="h-24 w-24 text-gray-300"
            fill="none"
            viewBox="0 0 24 24"
            stroke="currentColor"
          >
            <path
              strokeLinecap="round"
              strokeLinejoin="round"
              strokeWidth={1}
              d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z"
            />
          </svg>
          <h2 className="text-2xl font-semibold text-gray-500 mt-4">
            Your Messages
          </h2>
          <p className="text-gray-400 mt-2">
            Select a conversation to start chatting
          </p>
          <button
            onClick={() => setIsNewChatModalOpen(true)}
            className="mt-4 px-4 py-2 bg-blue-500 text-white rounded-lg hover:bg-blue-600 transition-colors"
          >
            Start a New Conversation
          </button>
        </div>
      )}

      {/* New Conversation Modal */}
      <NewConversationModal
        isOpen={isNewChatModalOpen}
        onClose={() => setIsNewChatModalOpen(false)}
        onConversationCreated={handleConversationCreated}
      />
    </div>
  );
}

export default ChatPage;
