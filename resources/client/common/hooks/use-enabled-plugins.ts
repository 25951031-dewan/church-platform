import { useBootstrapStore } from '@app/common/core/bootstrap-data';
import { useMemo } from 'react';

/**
 * Returns the set of enabled plugin names from server-injected bootstrap data.
 *
 * window.__BOOTSTRAP_DATA__.plugins is populated by BootstrapDataService::get()
 * which calls PluginManager::getEnabled() → reads config/plugins.json.
 *
 * This is available synchronously at page load — no API call needed.
 * For admin plugin toggle to take effect, user must refresh (or we invalidate
 * the bootstrap store after toggle).
 */
export function useEnabledPlugins(): Set<string> {
  const plugins = useBootstrapStore((s) => s.plugins);
  return useMemo(() => new Set(Array.isArray(plugins) ? plugins : []), [plugins]);
}