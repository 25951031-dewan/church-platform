import { useQuery, useMutation, useQueryClient } from '@tanstack/react-query';
import { apiClient } from '../../../common/http/api-client';

export interface FeedLayout {
  id: number;
  name: string;
  description?: string;
  is_active: boolean;
  sort_order: number;
  layout_data: any;
  left_sidebar_config: any;
  right_sidebar_config: any;
  mobile_config: any;
  responsive_settings: any;
  created_at: string;
  updated_at: string;
}

export interface FeedWidget {
  id: number;
  widget_key: string;
  display_name: string;
  description?: string;
  category: string;
  icon?: string;
  component_path: string;
  config_schema?: any;
  default_config?: any;
  permissions_required?: string[];
  is_system: boolean;
  is_customizable: boolean;
  preview_image?: string;
}

export function useFeedLayouts() {
  return useQuery({
    queryKey: ['feed-layouts'],
    queryFn: async () => {
      const { data } = await apiClient.get('/feed-layouts');
      return data.layouts as FeedLayout[];
    },
  });
}

export function useActiveFeedLayout() {
  return useQuery({
    queryKey: ['feed-layout', 'active'],
    queryFn: async () => {
      try {
        const { data } = await apiClient.get('/feed-layouts/active');
        return data.layout;
      } catch (error) {
        // Return a default layout structure if API fails
        return {
          id: 0,
          name: 'Default Feed',
          layout_data: {},
          mobile_config: {},
          responsive_settings: {},
          left_sidebar_config: { widgets: [] },
          right_sidebar_config: { widgets: [] },
          panes: {
            left: { config: {}, widgets: [] },
            center: { config: {}, widgets: [] },
            right: { config: {}, widgets: [] },
          },
        };
      }
    },
    retry: false, // Don't retry if feed layout fails
  });
}

export function useFeedWidgets() {
  return useQuery({
    queryKey: ['feed-widgets'],
    queryFn: async () => {
      const { data } = await apiClient.get('/feed-widgets');
      return data.widgets as FeedWidget[];
    },
  });
}

export function useFeedWidgetCategories() {
  return useQuery({
    queryKey: ['feed-widgets', 'categories'],
    queryFn: async () => {
      const { data } = await apiClient.get('/feed-widgets/categories');
      return data.categories;
    },
  });
}

export function useCreateFeedLayout() {
  const queryClient = useQueryClient();
  
  return useMutation({
    mutationFn: async (layoutData: Partial<FeedLayout>) => {
      const { data } = await apiClient.post('/feed-layouts', layoutData);
      return data.layout;
    },
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: ['feed-layouts'] });
      queryClient.invalidateQueries({ queryKey: ['feed-layout', 'active'] });
    },
  });
}

export function useUpdateFeedLayout() {
  const queryClient = useQueryClient();
  
  return useMutation({
    mutationFn: async ({ id, ...layoutData }: Partial<FeedLayout> & { id: number }) => {
      const { data } = await apiClient.put(`/feed-layouts/${id}`, layoutData);
      return data.layout;
    },
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: ['feed-layouts'] });
      queryClient.invalidateQueries({ queryKey: ['feed-layout', 'active'] });
    },
  });
}

export function useDeleteFeedLayout() {
  const queryClient = useQueryClient();
  
  return useMutation({
    mutationFn: async (id: number) => {
      await apiClient.delete(`/feed-layouts/${id}`);
    },
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: ['feed-layouts'] });
      queryClient.invalidateQueries({ queryKey: ['feed-layout', 'active'] });
    },
  });
}