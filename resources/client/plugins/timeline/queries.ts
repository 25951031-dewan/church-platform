import { useInfiniteQuery, useMutation, useQueryClient } from '@tanstack/react-query';
import { apiClient } from '@app/common/http/api-client';

export const POST_FEED_KEY = ['posts', 'feed'];

export function usePostFeed(extraParams?: Record<string, any>) {
  return useInfiniteQuery({
    queryKey: extraParams ? [...POST_FEED_KEY, extraParams] : POST_FEED_KEY,
    queryFn: ({ pageParam = 1 }) =>
      apiClient.get('/posts', { params: { feed: 1, page: pageParam, ...extraParams } }).then((r) => r.data),
    getNextPageParam: (lastPage) =>
      lastPage.current_page < lastPage.last_page ? lastPage.current_page + 1 : undefined,
    initialPageParam: 1,
  });
}

export function useCreatePost() {
  const queryClient = useQueryClient();

  return useMutation({
    mutationFn: (data: { content: string; type?: string; visibility?: string; group_id?: number | string }) =>
      apiClient.post('/posts', data).then((r) => r.data),
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: POST_FEED_KEY });
    },
  });
}

export function useDeletePost() {
  const queryClient = useQueryClient();

  return useMutation({
    mutationFn: (postId: number) => apiClient.delete(`/posts/${postId}`),
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: POST_FEED_KEY });
    },
  });
}
