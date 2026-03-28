import { useState } from 'react';
import { useCreatePost } from '../queries';

interface PostComposerProps {
  groupId?: number | string;
}

export function PostComposer({groupId}: PostComposerProps = {}) {
  const [content, setContent] = useState('');
  const createPost = useCreatePost();

  const handleSubmit = (e: React.FormEvent) => {
    e.preventDefault();
    if (!content.trim()) return;

    createPost.mutate(
      { content, type: 'text', visibility: 'public', ...(groupId !== undefined && {group_id: groupId}) },
      { onSuccess: () => setContent('') }
    );
  };

  return (
    <form onSubmit={handleSubmit} className="bg-white dark:bg-gray-800 rounded-lg shadow p-4">
      <textarea
        value={content}
        onChange={(e) => setContent(e.target.value)}
        placeholder="Share something with your community..."
        className="w-full border-0 resize-none focus:ring-0 text-gray-900 dark:text-white dark:bg-gray-800 placeholder-gray-400"
        rows={3}
      />
      <div className="flex justify-end mt-2">
        <button
          type="submit"
          disabled={createPost.isPending || !content.trim()}
          className="px-4 py-2 bg-primary-600 text-white rounded-md text-sm hover:bg-primary-700 disabled:opacity-50"
        >
          {createPost.isPending ? 'Posting...' : 'Post'}
        </button>
      </div>
    </form>
  );
}
