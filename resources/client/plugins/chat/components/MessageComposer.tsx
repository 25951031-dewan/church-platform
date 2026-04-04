import { useState, useRef, useEffect } from 'react';
import { useSendMessage } from '../hooks/useSendMessage';

interface Props {
  conversationId: number;
}

/**
 * Message input composer with send button.
 */
export function MessageComposer({ conversationId }: Props) {
  const [body, setBody] = useState('');
  const inputRef = useRef<HTMLInputElement>(null);
  const sendMessage = useSendMessage();

  // Focus input when conversation changes
  useEffect(() => {
    inputRef.current?.focus();
  }, [conversationId]);

  const handleSend = () => {
    const trimmedBody = body.trim();
    if (!trimmedBody || sendMessage.isPending) return;

    sendMessage.mutate(
      {
        conversation_id: conversationId,
        body: trimmedBody,
        type: 'text',
      },
      {
        onSuccess: () => {
          setBody('');
          inputRef.current?.focus();
        },
      }
    );
  };

  const handleKeyPress = (e: React.KeyboardEvent) => {
    if (e.key === 'Enter' && !e.shiftKey) {
      e.preventDefault();
      handleSend();
    }
  };

  return (
    <div className="border-t border-white/10 p-4 bg-[#161920]">
      <div className="flex items-center gap-2">
        {/* Attachment button */}
        <button
          type="button"
          className="p-2 text-gray-400 hover:text-gray-300 rounded-full hover:bg-white/10 transition-colors"
          title="Attach file"
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
              d="M15.172 7l-6.586 6.586a2 2 0 102.828 2.828l6.414-6.586a4 4 0 00-5.656-5.656l-6.415 6.585a6 6 0 108.486 8.486L20.5 13"
            />
          </svg>
        </button>

        {/* Input field */}
        <input
          ref={inputRef}
          type="text"
          value={body}
          onChange={(e) => setBody(e.target.value)}
          onKeyPress={handleKeyPress}
          placeholder="Type a message..."
          className="flex-1 px-4 py-2 bg-[#0C0E12] border border-white/10 rounded-full text-white placeholder:text-gray-400 focus:outline-none focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500"
          disabled={sendMessage.isPending}
        />

        {/* Send button */}
        <button
          onClick={handleSend}
          disabled={!body.trim() || sendMessage.isPending}
          className="p-2 bg-blue-500 text-white rounded-full hover:bg-blue-600 transition-colors disabled:opacity-50 disabled:cursor-not-allowed"
          title="Send message"
        >
          {sendMessage.isPending ? (
            <svg
              className="animate-spin h-6 w-6"
              xmlns="http://www.w3.org/2000/svg"
              fill="none"
              viewBox="0 0 24 24"
            >
              <circle
                className="opacity-25"
                cx="12"
                cy="12"
                r="10"
                stroke="currentColor"
                strokeWidth="4"
              />
              <path
                className="opacity-75"
                fill="currentColor"
                d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"
              />
            </svg>
          ) : (
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
                d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8"
              />
            </svg>
          )}
        </button>
      </div>
    </div>
  );
}
