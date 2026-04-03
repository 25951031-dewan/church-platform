import {apiClient} from '@app/common/http/api-client';
import {
  useInfiniteQuery,
  useMutation,
  useQuery,
  useQueryClient,
} from '@tanstack/react-query';

// --- Types ---

export interface ArticleCategory {
  id: number;
  name: string;
  slug: string;
  description: string | null;
  image: string | null;
  sort_order: number;
  is_active: boolean;
}

export interface Tag {
  id: number;
  name: string;
  slug: string;
}

export interface Article {
  id: number;
  title: string;
  slug: string;
  content: string | null;
  excerpt: string | null;
  cover_image: string | null;
  author_id: number;
  author: {id: number; name: string; avatar: string | null} | null;
  category_id: number | null;
  category: {id: number; name: string; slug: string} | null;
  church_id: number | null;
  status: 'draft' | 'published' | 'scheduled';
  published_at: string | null;
  view_count: number;
  is_featured: boolean;
  is_active: boolean;
  meta_title: string | null;
  meta_description: string | null;
  tags: Tag[];
  reactions_count: number;
  created_at: string;
  updated_at: string;
}

// --- Article Queries ---

export function useArticles(params: Record<string, string | number | boolean> = {}) {
  return useInfiniteQuery({
    queryKey: ['articles', params],
    queryFn: ({pageParam = 1}) =>
      apiClient
        .get('articles', {params: {...params, page: pageParam}})
        .then(r => r.data),
    initialPageParam: 1,
    getNextPageParam: (last: any) =>
      last.pagination?.current_page < last.pagination?.last_page
        ? last.pagination.current_page + 1
        : undefined,
  });
}

export function useArticle(slug: string) {
  return useQuery({
    queryKey: ['articles', slug],
    queryFn: () => apiClient.get(`articles/${slug}`).then(r => r.data.article as Article),
    enabled: !!slug,
  });
}

// --- Article Mutations ---

export function useCreateArticle() {
  const queryClient = useQueryClient();
  return useMutation({
    mutationFn: (data: Partial<Article> & {tag_ids?: number[]}) =>
      apiClient.post('articles', data).then(r => r.data.article as Article),
    onSuccess: () => {
      queryClient.invalidateQueries({queryKey: ['articles']});
    },
  });
}

export function useUpdateArticle(slug: string) {
  const queryClient = useQueryClient();
  return useMutation({
    mutationFn: (data: Partial<Article> & {tag_ids?: number[]}) =>
      apiClient.put(`articles/${slug}`, data).then(r => r.data.article as Article),
    onSuccess: (article) => {
      queryClient.invalidateQueries({queryKey: ['articles', slug]});
      queryClient.invalidateQueries({queryKey: ['articles', article.slug]});
      queryClient.invalidateQueries({queryKey: ['articles']});
    },
  });
}

export function useDeleteArticle() {
  const queryClient = useQueryClient();
  return useMutation({
    mutationFn: (slug: string) => apiClient.delete(`articles/${slug}`),
    onSuccess: () => {
      queryClient.invalidateQueries({queryKey: ['articles']});
    },
  });
}

// --- Category Queries ---

export function useArticleCategories() {
  return useQuery({
    queryKey: ['article-categories'],
    queryFn: () =>
      apiClient.get('article-categories').then(r => r.data.categories as ArticleCategory[]),
  });
}

export function useCreateArticleCategory() {
  const queryClient = useQueryClient();
  return useMutation({
    mutationFn: (data: Partial<ArticleCategory>) =>
      apiClient.post('article-categories', data).then(r => r.data.category as ArticleCategory),
    onSuccess: () => {
      queryClient.invalidateQueries({queryKey: ['article-categories']});
    },
  });
}

export function useUpdateArticleCategory(categoryId: number | string) {
  const queryClient = useQueryClient();
  return useMutation({
    mutationFn: (data: Partial<ArticleCategory>) =>
      apiClient
        .put(`article-categories/${categoryId}`, data)
        .then(r => r.data.category as ArticleCategory),
    onSuccess: () => {
      queryClient.invalidateQueries({queryKey: ['article-categories']});
    },
  });
}

export function useDeleteArticleCategory() {
  const queryClient = useQueryClient();
  return useMutation({
    mutationFn: (categoryId: number) => apiClient.delete(`article-categories/${categoryId}`),
    onSuccess: () => {
      queryClient.invalidateQueries({queryKey: ['article-categories']});
      queryClient.invalidateQueries({queryKey: ['articles']});
    },
  });
}

// --- Tag Queries ---

export function useTags(search?: string) {
  return useQuery({
    queryKey: ['tags', search],
    queryFn: () =>
      apiClient
        .get('tags', {params: search ? {search} : {}})
        .then(r => r.data.tags as Tag[]),
  });
}

export function useCreateTag() {
  const queryClient = useQueryClient();
  return useMutation({
    mutationFn: (data: {name: string}) =>
      apiClient.post('tags', data).then(r => r.data.tag as Tag),
    onSuccess: () => {
      queryClient.invalidateQueries({queryKey: ['tags']});
    },
  });
}

export function useDeleteTag() {
  const queryClient = useQueryClient();
  return useMutation({
    mutationFn: (tagId: number) => apiClient.delete(`tags/${tagId}`),
    onSuccess: () => {
      queryClient.invalidateQueries({queryKey: ['tags']});
    },
  });
}
