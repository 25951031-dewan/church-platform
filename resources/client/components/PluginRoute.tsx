import { Route, RouteProps } from 'react-router';
import { useEnabledPlugins } from '@app/common/hooks/use-enabled-plugins';
import { NotFoundPage } from '../pages/NotFoundPage';

interface PluginRouteProps extends RouteProps {
  plugin: string;
}

export function PluginRoute({ plugin, ...props }: PluginRouteProps) {
  const enabledPlugins = useEnabledPlugins();
  
  // If plugin is not enabled, show 404
  if (!enabledPlugins.has(plugin)) {
    return <Route {...props} element={<NotFoundPage />} />;
  }
  
  // Plugin is enabled, show the actual route
  return <Route {...props} />;
}