import { useState } from 'react';
import { useCreateConversation } from '../hooks/useCreateConversation';

interface Props {
  isOpen: boolean;
  onClose: () => void;
  onConversationCreated: (conversationId: number) => void;
}

/**
 * Modal to create a new conversation.
 */
export function NewConversationModal({
  isOpen,
  onClose,
  onConversationCreated,
}: Props) {
  const [userIds, setUserIds] = useState<string>('');
  const [name, setName] = useState('');
  const [error, setError] = useState('');
  const createConversation = useCreateConversation();

  if (!isOpen) return null;

  const handleSubmit = (e: React.FormEvent) => {
    e.preventDefault();
    setError('');

    const ids = userIds
      .split(',')
      .map((id) => parseInt(id.trim(), 10))
      .filter((id) => !isNaN(id));

    if (ids.length === 0) {
      setError('Please enter at least one user ID');
      return;
    }

    createConversation.mutate(
      {
        user_ids: ids,
        name: ids.length > 1 ? name : undefined,
      },
      {
        onSuccess: (conversation) => {
          setUserIds('');
          setName('');
          onConversationCreated(conversation.id);
          onClose();
        },
        onError: () => {
          setError('Failed to create conversation');
        },
      }
    );
  };

  return (
    <div className="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50">
      <div className="bg-[#161920] rounded-lg border border-white/10 w-full max-w-md p-6">
        <div className="flex items-center justify-between mb-4">
          <h2 className="text-xl font-bold text-white">New Conversation</h2>
          <button
            onClick={onClose}
            className="text-gray-400 hover:text-gray-300"
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
                d="M6 18L18 6M6 6l12 12"
              />
            </svg>
          </button>
        </div>

        <form onSubmit={handleSubmit}>
          <div className="mb-4">
            <label
              htmlFor="userIds"
              className="block text-sm font-medium text-gray-700 mb-1"
            >
              User IDs (comma separated)
            </label>
            <input
              id="userIds"
              type="text"
              value={userIds}
              onChange={(e) => setUserIds(e.target.value)}
              placeholder="1, 2, 3"
              className="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500"
              required
            />
            <p className="text-xs text-gray-500 mt-1">
              Enter the IDs of users you want to chat with
            </p>
          </div>

          <div className="mb-4">
            <label
              htmlFor="name"
              className="block text-sm font-medium text-gray-700 mb-1"
            >
              Group Name (optional)
            </label>
            <input
              id="name"
              type="text"
              value={name}
              onChange={(e) => setName(e.target.value)}
              placeholder="My Group Chat"
              className="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500"
            />
            <p className="text-xs text-gray-500 mt-1">
              Only for group chats with multiple users
            </p>
          </div>

          {error && (
            <div className="mb-4 text-red-500 text-sm">{error}</div>
          )}

          <div className="flex justify-end gap-2">
            <button
              type="button"
              onClick={onClose}
              className="px-4 py-2 text-gray-600 hover:bg-gray-100 rounded-lg transition-colors"
            >
              Cancel
            </button>
            <button
              type="submit"
              disabled={createConversation.isPending}
              className="px-4 py-2 bg-blue-500 text-white rounded-lg hover:bg-blue-600 transition-colors disabled:opacity-50"
            >
              {createConversation.isPending ? 'Creating...' : 'Create'}
            </button>
          </div>
        </form>
      </div>
    </div>
  );
}
