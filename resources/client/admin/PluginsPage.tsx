import { useQuery, useMutation, useQueryClient } from '@tanstack/react-query';
import { Puzzle, Check, X, RefreshCw, Settings, MessageSquare, Users, Calendar, Mic, Heart, MessageCircle, BookOpen, Building, FileText, Video, DollarSign, UserCheck, Target, Film, HeartHandshake, ShoppingBag } from 'lucide-react';
import { useNotificationStore } from '@app/common/stores';

interface Plugin {
  name: string;
  display_name: string;
  version: string;
  description: string;
  icon: string;
  is_enabled: boolean;
  has_settings: boolean;
}

// Icon mapping
const iconMap: Record<string, any> = {
  MessageSquare, Users, Calendar, Mic, Heart, MessageCircle, BookOpen, 
  Building, FileText, Video, DollarSign, UserCheck, Target, Film, 
  HeartHandshake, ShoppingBag, Puzzle, Settings
};

function PluginIcon({ icon, className }: { icon: string; className?: string }) {
  const IconComponent = iconMap[icon] || Puzzle;
  return <IconComponent className={className} size={20} />;
}

export function PluginsPage() {
  const queryClient = useQueryClient();
  const { success, error } = useNotificationStore();

  const { data: plugins, isLoading, isError } = useQuery({
    queryKey: ['admin-plugins'],
    queryFn: async () => {
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
      return (data.data || []) as Plugin[];
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

  if (isError) {
    return (
      <div className="bg-red-500/10 border border-red-500/20 rounded-xl p-6 text-center">
        <p className="text-red-400">Failed to load plugins. Check your permissions.</p>
      </div>
    );
  }

  const enabledPlugins = plugins?.filter(p => p.is_enabled) || [];
  const disabledPlugins = plugins?.filter(p => !p.is_enabled) || [];

  return (
    <div className="space-y-6">
      <div className="flex items-center justify-between">
        <h1 className="text-xl font-bold text-white flex items-center gap-2">
          <Puzzle size={24} />
          Plugins
        </h1>
        <span className="text-sm text-gray-400">
          {enabledPlugins.length} of {plugins?.length || 0} enabled
        </span>
      </div>

      {/* Enabled Plugins */}
      {enabledPlugins.length > 0 && (
        <div>
          <h2 className="text-sm font-semibold text-gray-400 uppercase tracking-wider mb-3">Enabled</h2>
          <div className="grid gap-3">
            {enabledPlugins.map((plugin) => (
              <div
                key={plugin.name}
                className="bg-[#161920] border border-green-500/20 rounded-xl p-4 hover:border-green-500/30 transition-colors"
              >
                <div className="flex items-center justify-between">
                  <div className="flex items-center gap-3">
                    <div className="w-10 h-10 bg-green-500/10 rounded-lg flex items-center justify-center">
                      <PluginIcon icon={plugin.icon} className="text-green-400" />
                    </div>
                    <div>
                      <div className="flex items-center gap-2">
                        <h3 className="font-semibold text-white">{plugin.display_name}</h3>
                        <span className="text-xs text-gray-500">v{plugin.version}</span>
                      </div>
                      <p className="text-sm text-gray-400">{plugin.description}</p>
                    </div>
                  </div>
                  <div className="flex items-center gap-2">
                    {plugin.has_settings && (
                      <button className="p-2 rounded-lg bg-white/5 text-gray-400 hover:bg-white/10 hover:text-white transition-colors">
                        <Settings size={18} />
                      </button>
                    )}
                    <button
                      onClick={() => toggleMutation.mutate({ name: plugin.name, enable: false })}
                      disabled={toggleMutation.isPending}
                      className="p-2 rounded-lg bg-red-500/10 text-red-400 hover:bg-red-500/20 transition-colors"
                      title="Disable plugin"
                    >
                      <X size={18} />
                    </button>
                  </div>
                </div>
              </div>
            ))}
          </div>
        </div>
      )}

      {/* Disabled Plugins */}
      {disabledPlugins.length > 0 && (
        <div>
          <h2 className="text-sm font-semibold text-gray-400 uppercase tracking-wider mb-3">Available</h2>
          <div className="grid gap-3">
            {disabledPlugins.map((plugin) => (
              <div
                key={plugin.name}
                className="bg-[#161920] border border-white/5 rounded-xl p-4 hover:border-white/10 transition-colors opacity-70 hover:opacity-100"
              >
                <div className="flex items-center justify-between">
                  <div className="flex items-center gap-3">
                    <div className="w-10 h-10 bg-white/5 rounded-lg flex items-center justify-center">
                      <PluginIcon icon={plugin.icon} className="text-gray-500" />
                    </div>
                    <div>
                      <div className="flex items-center gap-2">
                        <h3 className="font-semibold text-white">{plugin.display_name}</h3>
                        <span className="text-xs text-gray-500">v{plugin.version}</span>
                      </div>
                      <p className="text-sm text-gray-500">{plugin.description}</p>
                    </div>
                  </div>
                  <button
                    onClick={() => toggleMutation.mutate({ name: plugin.name, enable: true })}
                    disabled={toggleMutation.isPending}
                    className="p-2 rounded-lg bg-green-500/10 text-green-400 hover:bg-green-500/20 transition-colors"
                    title="Enable plugin"
                  >
                    <Check size={18} />
                  </button>
                </div>
              </div>
            ))}
          </div>
        </div>
      )}

      {(!plugins || plugins.length === 0) && (
        <div className="bg-[#161920] border border-white/5 rounded-xl p-12 text-center">
          <Puzzle size={48} className="mx-auto text-gray-600 mb-4" />
          <p className="text-gray-400">No plugins found.</p>
          <p className="text-sm text-gray-500 mt-1">Plugins are located in app/Plugins/ directory.</p>
        </div>
      )}
    </div>
  );
}
