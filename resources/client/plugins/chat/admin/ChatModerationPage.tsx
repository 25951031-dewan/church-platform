import { useState } from 'react';
import {
  useAdminConversations,
  useAdminMessages,
  useForceDeleteMessage,
  useRestoreMessage,
} from './queries';
import type { Conversation, Message } from '../types';

/**
 * Admin chat moderation page.
 */
export function ChatModerationPage() {
  const [selectedConversation, setSelectedConversation] = useState<Conversation | null>(null);
  const [page, setPage] = useState(1);

  const { data: conversationsData, isLoading: loadingConversations } = useAdminConversations(page);
  const { data: messagesData, isLoading: loadingMessages } = useAdminMessages(
    selectedConversation?.id ?? null
  );

  const forceDelete = useForceDeleteMessage();
  const restore = useRestoreMessage();

  const handleForceDelete = (message: Message) => {
    if (window.confirm('Permanently delete this message? This cannot be undone.')) {
      forceDelete.mutate(message.id);
    }
  };

  const handleRestore = (message: Message) => {
    restore.mutate(message.id);
  };

  return (
    <div className="p-6">
      <h1 className="text-2xl font-bold mb-6">Chat Moderation</h1>

      <div className="grid grid-cols-1 lg:grid-cols-2 gap-6">
        {/* Conversations List */}
        <div className="bg-[#161920] rounded-lg border border-white/10 p-4">
          <h2 className="text-lg font-semibold mb-4 text-white">All Conversations</h2>

          {loadingConversations && <div className="text-gray-400">Loading...</div>}

          <div className="space-y-2">
            {conversationsData?.data.map((conv) => (
              <button
                key={conv.id}
                onClick={() => setSelectedConversation(conv)}
                className={`w-full text-left p-3 rounded-lg border transition-colors ${
                  selectedConversation?.id === conv.id
                    ? 'bg-indigo-500/20 border-indigo-500'
                    : 'hover:bg-white/5 border-white/10'
                }`}
              >
                <div className="flex justify-between items-start">
                  <div>
                    <p className="font-medium text-white">{conv.display_name}</p>
                    <p className="text-sm text-gray-400">
                      {conv.type === 'group' ? `Group · ${conv.users.length} members` : 'Direct'}
                    </p>
                  </div>
                  <span className="text-xs text-gray-500">
                    ID: {conv.id}
                  </span>
                </div>
              </button>
            ))}
          </div>

          {/* Pagination */}
          {conversationsData && (
            <div className="mt-4 flex justify-between items-center">
              <button
                onClick={() => setPage((p) => Math.max(1, p - 1))}
                disabled={page === 1}
                className="px-3 py-1 text-sm bg-gray-100 rounded disabled:opacity-50"
              >
                Previous
              </button>
              <span className="text-sm text-gray-500">
                Page {conversationsData.current_page} of {conversationsData.last_page}
              </span>
              <button
                onClick={() => setPage((p) => p + 1)}
                disabled={page >= conversationsData.last_page}
                className="px-3 py-1 text-sm bg-gray-100 rounded disabled:opacity-50"
              >
                Next
              </button>
            </div>
          )}
        </div>

        {/* Messages View */}
        <div className="bg-[#161920] rounded-lg border border-white/10 p-4">
          <h2 className="text-lg font-semibold mb-4 text-white">
            Messages
            {selectedConversation && (
              <span className="font-normal text-gray-400">
                {' '}
                - {selectedConversation.display_name}
              </span>
            )}
          </h2>

          {!selectedConversation && (
            <div className="text-gray-500 text-center py-8">
              Select a conversation to view messages
            </div>
          )}

          {loadingMessages && <div className="text-gray-500">Loading messages...</div>}

          <div className="space-y-3 max-h-[500px] overflow-y-auto">
            {messagesData?.data.map((msg) => (
              <div
                key={msg.id}
                className={`p-3 rounded-lg border ${
                  msg.deleted_at ? 'bg-red-50 border-red-200' : 'border-gray-200'
                }`}
              >
                <div className="flex justify-between items-start mb-2">
                  <div>
                    <span className="font-medium">{msg.user.name}</span>
                    <span className="text-xs text-gray-400 ml-2">
                      {new Date(msg.created_at).toLocaleString()}
                    </span>
                  </div>
                  <div className="flex gap-2">
                    {msg.deleted_at ? (
                      <button
                        onClick={() => handleRestore(msg)}
                        disabled={restore.isPending}
                        className="text-xs text-green-600 hover:underline"
                      >
                        Restore
                      </button>
                    ) : (
                      <button
                        onClick={() => handleForceDelete(msg)}
                        disabled={forceDelete.isPending}
                        className="text-xs text-red-600 hover:underline"
                      >
                        Delete
                      </button>
                    )}
                  </div>
                </div>
                <p className={msg.deleted_at ? 'text-gray-400 italic' : ''}>
                  {msg.body || `[${msg.type} attachment]`}
                </p>
                {msg.deleted_at && (
                  <p className="text-xs text-red-500 mt-1">
                    Deleted at {new Date(msg.deleted_at).toLocaleString()}
                  </p>
                )}
              </div>
            ))}
          </div>
        </div>
      </div>
    </div>
  );
}

export default ChatModerationPage;
