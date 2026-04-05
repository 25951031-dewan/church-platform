import { useQuery } from '@tanstack/react-query';
import { apiClient } from '@app/common/http/api-client';

interface Plugin {
  name: string;
  enabled: boolean;
  version: string;
}

interface PluginsResponse {
  data: Plugin[];
  stats: {
    total: number;
    enabled: number;
    disabled: number;
  };
}

export function useEnabledPlugins(): Set<string> {
  const { data } = useQuery({
    queryKey: ['enabled-plugins'],
    queryFn: async () => {
      const response = await apiClient.get<PluginsResponse>('admin/plugins');
      const enabledPlugins = response.data.data
        .filter(plugin => plugin.enabled)
        .map(plugin => plugin.name);
      return new Set(enabledPlugins);
    },
    staleTime: 30000, // Cache for 30 seconds
    refetchOnWindowFocus: false,
  });

  return data || new Set();
}