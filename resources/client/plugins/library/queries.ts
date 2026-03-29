import {apiClient} from '@app/common/http/api-client';
import {
  useInfiniteQuery,
  useMutation,
  useQuery,
  useQueryClient,
} from '@tanstack/react-query';

// --- Types ---

export interface BookCategory {
  id: number;
  name: string;
  slug: string;
  description: string | null;
  image: string | null;
  parent_id: number | null;
  sort_order: number;
  is_active: boolean;
  books_count: number;
}

export interface Book {
  id: number;
  title: string;
  slug: string;
  author: string;
  description: string | null;
  content: string | null;
  cover: string | null;
  pdf_path: string | null;
  isbn: string | null;
  publisher: string | null;
  pages_count: number | null;
  published_year: number | null;
  category_id: number | null;
  category: {id: number; name: string; slug: string} | null;
  church_id: number | null;
  uploaded_by: number | null;
  uploader: {id: number; name: string; avatar: string | null} | null;
  view_count: number;
  download_count: number;
  is_featured: boolean;
  is_active: boolean;
  has_pdf: boolean;
  can_download?: boolean;
  reactions_count: number;
  created_at: string;
}

// --- Book Queries ---

export function useBooks(params: Record<string, string | number | boolean> = {}) {
  return useInfiniteQuery({
    queryKey: ['books', params],
    queryFn: ({pageParam = 1}) =>
      apiClient
        .get('books', {params: {...params, page: pageParam}})
        .then(r => r.data),
    initialPageParam: 1,
    getNextPageParam: (last: any) =>
      last.current_page < last.last_page ? last.current_page + 1 : undefined,
  });
}

export function useBook(bookId: number | string) {
  return useQuery({
    queryKey: ['books', bookId],
    queryFn: () => apiClient.get(`books/${bookId}`).then(r => r.data.book),
  });
}

// --- Book Mutations ---

export function useCreateBook() {
  const queryClient = useQueryClient();
  return useMutation({
    mutationFn: (data: Partial<Book>) =>
      apiClient.post('books', data).then(r => r.data.book),
    onSuccess: () => {
      queryClient.invalidateQueries({queryKey: ['books']});
    },
  });
}

export function useUpdateBook(bookId: number | string) {
  const queryClient = useQueryClient();
  return useMutation({
    mutationFn: (data: Partial<Book>) =>
      apiClient.put(`books/${bookId}`, data).then(r => r.data.book),
    onSuccess: () => {
      queryClient.invalidateQueries({queryKey: ['books', bookId]});
      queryClient.invalidateQueries({queryKey: ['books']});
    },
  });
}

export function useDeleteBook() {
  const queryClient = useQueryClient();
  return useMutation({
    mutationFn: (bookId: number) => apiClient.delete(`books/${bookId}`),
    onSuccess: () => {
      queryClient.invalidateQueries({queryKey: ['books']});
    },
  });
}

export function useTrackDownload(bookId: number | string) {
  const queryClient = useQueryClient();
  return useMutation({
    mutationFn: () =>
      apiClient.post(`books/${bookId}/download`).then(r => r.data),
    onSuccess: () => {
      queryClient.invalidateQueries({queryKey: ['books', bookId]});
    },
  });
}

// --- Category Queries ---

export function useBookCategories() {
  return useQuery({
    queryKey: ['book-categories'],
    queryFn: () =>
      apiClient.get('book-categories').then(r => r.data.categories as BookCategory[]),
  });
}

export function useCreateBookCategory() {
  const queryClient = useQueryClient();
  return useMutation({
    mutationFn: (data: Partial<BookCategory>) =>
      apiClient.post('book-categories', data).then(r => r.data.category),
    onSuccess: () => {
      queryClient.invalidateQueries({queryKey: ['book-categories']});
    },
  });
}

export function useUpdateBookCategory(categoryId: number | string) {
  const queryClient = useQueryClient();
  return useMutation({
    mutationFn: (data: Partial<BookCategory>) =>
      apiClient.put(`book-categories/${categoryId}`, data).then(r => r.data.category),
    onSuccess: () => {
      queryClient.invalidateQueries({queryKey: ['book-categories']});
    },
  });
}

export function useDeleteBookCategory() {
  const queryClient = useQueryClient();
  return useMutation({
    mutationFn: (categoryId: number) => apiClient.delete(`book-categories/${categoryId}`),
    onSuccess: () => {
      queryClient.invalidateQueries({queryKey: ['book-categories']});
      queryClient.invalidateQueries({queryKey: ['books']});
    },
  });
}
