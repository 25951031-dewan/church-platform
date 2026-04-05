import { useQuery, useMutation, useQueryClient } from '@tanstack/react-query';
import { apiClient } from '@app/common/http/api-client';
import { Puzzle, Check, X, RefreshCw } from 'lucide-react';
import { useNotificationStore } from '@app/common/stores';

interface Plugin {
  name: string;
  version: string;
  description: string;
  is_enabled: boolean;
  dependencies: string[];
}

export function PluginsPage() {
  const queryClient = useQueryClient();
  const { success, error } = useNotificationStore();

  const { data: plugins, isLoading } = useQuery({
    queryKey: ['admin-plugins'],
    queryFn: async () => {
      // Use admin plugins endpoint which is outside v1 prefix
      const response = await fetch('/api/admin/plugins', {
        headers: {
          'Accept': 'application/json',
          'Authorization': `Bearer ${localStorage.getItem('auth_token')}`,
        },
      });
      
      if (!response.ok) {
        throw new Error('Failed to fetch plugins');
      }
      
      const data = await response.json();
      return data.data || [];
    },
  });

  const toggleMutation = useMutation({
    mutationFn: async ({ name, enable }: { name: string; enable: boolean }) => {
      const endpoint = enable ? '/api/admin/plugins/enable' : '/api/admin/plugins/disable';
      const response = await fetch(endpoint, {
        method: 'POST',
        headers: {
          'Accept': 'application/json',
          'Content-Type': 'application/json',
          'Authorization': `Bearer ${localStorage.getItem('auth_token')}`,
        },
        body: JSON.stringify({ name }),
      });
      
      if (!response.ok) {
        throw new Error('Failed to toggle plugin');
      }
      
      return response.json();
    },
    onSuccess: (_, { name, enable }) => {
      queryClient.invalidateQueries({ queryKey: ['admin-plugins'] });
      success(`${name} ${enable ? 'enabled' : 'disabled'}`);
    },
    onError: () => {
      error('Failed to update plugin status');
    },
  });

  if (isLoading) {
    return (
      <div className="flex items-center justify-center h-64 text-gray-400">
        <RefreshCw className="animate-spin mr-2" size={20} />
        Loading plugins...
      </div>
    );
  }

  return (
    <div className="space-y-6">
      <div className="flex items-center justify-between">
        <h1 className="text-xl font-bold text-white flex items-center gap-2">
          <Puzzle size={24} />
          Plugins
        </h1>
        <span className="text-sm text-gray-400">
          {plugins?.filter(p => p.is_enabled).length} of {plugins?.length} enabled
        </span>
      </div>

      <div className="grid gap-4">
        {plugins?.map((plugin) => (
          <div
            key={plugin.name}
            className="bg-[#161920] border border-white/5 rounded-xl p-5 hover:border-white/10 transition-colors"
          >
            <div className="flex items-start justify-between">
              <div className="flex-1">
                <div className="flex items-center gap-3">
                  <h3 className="text-lg font-semibold text-white">{plugin.name}</h3>
                  <span className="text-xs text-gray-500 bg-white/5 px-2 py-0.5 rounded">
                    v{plugin.version}
                  </span>
                  <span className={`text-xs px-2 py-0.5 rounded ${
                    plugin.is_enabled 
                      ? 'bg-green-500/10 text-green-400 border border-green-500/20' 
                      : 'bg-gray-500/10 text-gray-400 border border-gray-500/20'
                  }`}>
                    {plugin.is_enabled ? 'Enabled' : 'Disabled'}
                  </span>
                </div>
                <p className="text-sm text-gray-400 mt-2">{plugin.description}</p>
                {plugin.dependencies.length > 0 && (
                  <p className="text-xs text-gray-500 mt-2">
                    Dependencies: {plugin.dependencies.join(', ')}
                  </p>
                )}
              </div>
              <button
                onClick={() => toggleMutation.mutate({ 
                  name: plugin.name, 
                  enable: !plugin.is_enabled 
                })}
                disabled={toggleMutation.isPending}
                className={`p-2 rounded-lg transition-colors ${
                  plugin.is_enabled
                    ? 'bg-red-500/10 text-red-400 hover:bg-red-500/20'
                    : 'bg-green-500/10 text-green-400 hover:bg-green-500/20'
                }`}
              >
                {plugin.is_enabled ? <X size={20} /> : <Check size={20} />}
              </button>
            </div>
          </div>
        ))}
      </div>

      {(!plugins || plugins.length === 0) && (
        <div className="text-center text-gray-400 py-12">
          No plugins found. Create plugins in app/Plugins/ directory.
        </div>
      )}
    </div>
  );
}
