import { useState } from 'react';
import { ReactionBar } from '@app/common/components/ReactionBar';
import { CommentThread } from '@app/common/components/CommentThread';
import { useBootstrapStore } from '@app/common/core/bootstrap-data';
import { useDeletePost, POST_FEED_KEY } from '../queries';

interface PostCardProps {
  post: {
    id: number;
    content: string;
    type: string;
    is_pinned: boolean;
    user: { id: number; name: string; avatar: string | null };
    media: Array<{ id: number; file_path: string; type: string }>;
    reaction_counts: Record<string, number>;
    current_user_reaction: string | null;
    comments_count: number;
    created_at: string;
  };
}

export function PostCard({ post }: PostCardProps) {
  const [showComments, setShowComments] = useState(false);
  const currentUser = useBootstrapStore((s) => s.user);
  const deletePost = useDeletePost();

  const isOwner = currentUser?.id === post.user.id;

  return (
    <div className="bg-[#161920] border border-white/5 rounded-xl">
      {/* Header */}
      <div className="flex items-center justify-between p-4">
        <div className="flex items-center gap-3">
          <div className="w-10 h-10 rounded-full bg-indigo-600/20 flex items-center justify-center text-sm font-bold text-indigo-400">
            {post.user.name[0]}
          </div>
          <div>
            <p className="font-medium text-white">{post.user.name}</p>
            <p className="text-xs text-gray-400">
              {new Date(post.created_at).toLocaleDateString()}
              {post.is_pinned && <span className="ml-2 text-indigo-400">Pinned</span>}
              {post.type === 'announcement' && (
                <span className="ml-2 px-1.5 py-0.5 bg-yellow-500/20 text-yellow-400 rounded text-xs">
                  Announcement
                </span>
              )}
            </p>
          </div>
        </div>

        {isOwner && (
          <button
            onClick={() => {
              if (confirm('Delete this post?')) deletePost.mutate(post.id);
            }}
            className="text-gray-400 hover:text-red-500 text-sm"
          >
            Delete
          </button>
        )}
      </div>

      {/* Content */}
      <div className="px-4 pb-3">
        <p className="text-white whitespace-pre-wrap">{post.content}</p>
      </div>

      {/* Media */}
      {post.media.length > 0 && (
        <div className="px-4 pb-3">
          <div className={`grid gap-1 ${post.media.length > 1 ? 'grid-cols-2' : 'grid-cols-1'}`}>
            {post.media.map((m) => (
              <img key={m.id} src={m.file_path} alt="" className="rounded-lg w-full object-cover max-h-96" />
            ))}
          </div>
        </div>
      )}

      {/* Reactions + Comment toggle */}
      <div className="px-4 py-2 border-t border-white/10 flex items-center justify-between">
        <ReactionBar
          reactableId={post.id}
          reactableType="post"
          reactionCounts={post.reaction_counts}
          currentUserReaction={post.current_user_reaction}
          queryKey={POST_FEED_KEY}
        />

        <button
          onClick={() => setShowComments(!showComments)}
          className="text-sm text-gray-400 hover:text-gray-300"
        >
          {post.comments_count} comments
        </button>
      </div>

      {/* Comments */}
      {showComments && (
        <div className="px-4 pb-4 border-t border-white/10 pt-3">
          <CommentThread commentableId={post.id} commentableType="post" />
        </div>
      )}
    </div>
  );
}
