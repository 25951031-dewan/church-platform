import { useEffect, useRef } from 'react';
import { useMessages } from '../hooks/useMessages';
import { useMarkAsRead } from '../hooks/useMarkAsRead';
import { MessageBubble } from './MessageBubble';
import { MessageComposer } from './MessageComposer';
import type { Conversation } from '../types';

interface Props {
  conversation: Conversation;
}

/**
 * Main message thread area showing messages and composer.
 */
export function MessageThread({ conversation }: Props) {
  const { data: messages, isLoading, error } = useMessages(conversation.id);
  const markAsRead = useMarkAsRead();
  const scrollRef = useRef<HTMLDivElement>(null);

  // Auto-scroll to bottom when new messages arrive
  useEffect(() => {
    if (scrollRef.current) {
      scrollRef.current.scrollIntoView({ behavior: 'smooth' });
    }
  }, [messages?.length]);

  // Mark conversation as read when opened
  useEffect(() => {
    if (conversation.unread_count > 0) {
      markAsRead.mutate(conversation.id);
    }
  }, [conversation.id, conversation.unread_count, markAsRead]);

  return (
    <div className="flex-1 flex flex-col bg-white">
      {/* Header */}
      <div className="p-4 border-b flex items-center gap-3">
        <div className="w-10 h-10 rounded-full bg-gray-300 flex items-center justify-center text-gray-600 font-semibold">
          {conversation.display_name[0].toUpperCase()}
        </div>
        <div>
          <h3 className="font-semibold text-gray-900">
            {conversation.display_name}
          </h3>
          {conversation.type === 'group' && (
            <p className="text-sm text-gray-500">
              {conversation.users.length} members
            </p>
          )}
        </div>
      </div>

      {/* Messages area */}
      <div className="flex-1 overflow-y-auto p-4 bg-gray-50">
        {isLoading && (
          <div className="text-center text-gray-500 py-8">
            Loading messages...
          </div>
        )}

        {error && (
          <div className="text-center text-red-500 py-8">
            Failed to load messages
          </div>
        )}

        {messages?.length === 0 && !isLoading && (
          <div className="text-center text-gray-500 py-8">
            <p>No messages yet</p>
            <p className="text-sm mt-1">
              Send a message to start the conversation
            </p>
          </div>
        )}

        {messages?.map((msg) => (
          <MessageBubble key={msg.id} message={msg} />
        ))}

        {/* Scroll anchor */}
        <div ref={scrollRef} />
      </div>

      {/* Composer */}
      <MessageComposer conversationId={conversation.id} />
    </div>
  );
}
