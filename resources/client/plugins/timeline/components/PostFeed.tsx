import { usePostFeed } from '../queries';
import { PostCard } from './PostCard';
import { useEffect, useRef } from 'react';

interface PostFeedProps {
  groupId?: number | string;
}

export function PostFeed({groupId}: PostFeedProps = {}) {
  const { data, fetchNextPage, hasNextPage, isFetchingNextPage, isLoading } = usePostFeed(
    groupId !== undefined ? {group_id: groupId} : undefined
  );
  const loadMoreRef = useRef<HTMLDivElement>(null);

  useEffect(() => {
    if (!loadMoreRef.current || !hasNextPage) return;

    const observer = new IntersectionObserver(
      (entries) => {
        if (entries[0].isIntersecting && hasNextPage && !isFetchingNextPage) {
          fetchNextPage();
        }
      },
      { threshold: 0.1 }
    );

    observer.observe(loadMoreRef.current);
    return () => observer.disconnect();
  }, [hasNextPage, isFetchingNextPage, fetchNextPage]);

  if (isLoading) {
    return (
      <div className="space-y-4">
        {[1, 2, 3].map((i) => (
          <div key={i} className="bg-white dark:bg-gray-800 rounded-lg shadow p-6 animate-pulse">
            <div className="h-4 bg-gray-200 dark:bg-gray-700 rounded w-1/4 mb-4" />
            <div className="h-3 bg-gray-200 dark:bg-gray-700 rounded w-3/4 mb-2" />
            <div className="h-3 bg-gray-200 dark:bg-gray-700 rounded w-1/2" />
          </div>
        ))}
      </div>
    );
  }

  const posts = data?.pages.flatMap((page) => page.data) ?? [];

  if (posts.length === 0) {
    return (
      <div className="text-center py-12 text-gray-500 dark:text-gray-400">
        No posts yet. Be the first to share something!
      </div>
    );
  }

  return (
    <div className="space-y-4">
      {posts.map((post: any) => (
        <PostCard key={post.id} post={post} />
      ))}

      <div ref={loadMoreRef} className="py-4 text-center">
        {isFetchingNextPage && <span className="text-sm text-gray-400">Loading more...</span>}
      </div>
    </div>
  );
}
