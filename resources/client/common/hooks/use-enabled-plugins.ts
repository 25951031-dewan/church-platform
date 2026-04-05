import { useBootstrapStore } from '@app/common/core/bootstrap-data';
import { useMemo } from 'react';

export function useEnabledPlugins(): Set<string> {
  const { plugins } = useBootstrapStore();
  
  // Memoize the Set to prevent unnecessary re-renders
  return useMemo(() => new Set(plugins || []), [plugins]);
}