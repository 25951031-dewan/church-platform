import { useState } from 'react';
import { useQuery, useMutation, useQueryClient } from '@tanstack/react-query';
import { apiClient } from '@app/common/http/api-client';

interface Comment {
  id: number;
  body: string;
  user: { id: number; name: string; avatar: string | null };
  replies: Comment[];
  replies_count: number;
  created_at: string;
}

interface CommentThreadProps {
  commentableId: number;
  commentableType: string;
}

export function CommentThread({ commentableId, commentableType }: CommentThreadProps) {
  const queryClient = useQueryClient();
  const queryKey = ['comments', commentableType, commentableId];

  const { data, isLoading } = useQuery({
    queryKey,
    queryFn: () =>
      apiClient
        .get('/comments', { params: { commentable_id: commentableId, commentable_type: commentableType } })
        .then((r) => r.data),
  });

  const [newComment, setNewComment] = useState('');

  const createMutation = useMutation({
    mutationFn: (body: string) =>
      apiClient.post('/comments', {
        commentable_id: commentableId,
        commentable_type: commentableType,
        body,
      }),
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey });
      setNewComment('');
    },
  });

  const handleSubmit = (e: React.FormEvent) => {
    e.preventDefault();
    if (newComment.trim()) {
      createMutation.mutate(newComment);
    }
  };

  if (isLoading) return <div className="text-sm text-gray-400">Loading comments...</div>;

  const comments: Comment[] = data?.data ?? [];

  return (
    <div className="space-y-3">
      <form onSubmit={handleSubmit} className="flex gap-2">
        <input
          type="text"
          value={newComment}
          onChange={(e) => setNewComment(e.target.value)}
          placeholder="Write a comment..."
          className="flex-1 px-3 py-2 text-sm border rounded-full dark:bg-gray-700 dark:border-gray-600 dark:text-white"
        />
        <button
          type="submit"
          disabled={createMutation.isPending || !newComment.trim()}
          className="px-4 py-2 text-sm bg-primary-600 text-white rounded-full hover:bg-primary-700 disabled:opacity-50"
        >
          Post
        </button>
      </form>

      {comments.map((comment) => (
        <CommentItem key={comment.id} comment={comment} />
      ))}
    </div>
  );
}

function CommentItem({ comment }: { comment: Comment }) {
  return (
    <div className="flex gap-2">
      <div className="w-8 h-8 rounded-full bg-gray-200 dark:bg-gray-600 flex-shrink-0 flex items-center justify-center text-xs font-bold">
        {comment.user.name[0]}
      </div>
      <div className="flex-1">
        <div className="bg-gray-50 dark:bg-gray-700/50 rounded-lg px-3 py-2">
          <p className="text-sm font-medium text-gray-900 dark:text-white">{comment.user.name}</p>
          <p className="text-sm text-gray-700 dark:text-gray-300">{comment.body}</p>
        </div>
        <div className="flex gap-3 mt-1 text-xs text-gray-500 dark:text-gray-400">
          <span>{new Date(comment.created_at).toLocaleDateString()}</span>
          {comment.replies_count > 0 && <span>{comment.replies_count} replies</span>}
        </div>

        {comment.replies?.length > 0 && (
          <div className="mt-2 ml-4 space-y-2">
            {comment.replies.map((reply) => (
              <CommentItem key={reply.id} comment={reply} />
            ))}
          </div>
        )}
      </div>
    </div>
  );
}
