import { useBootstrapStore } from '@app/common/core/bootstrap-data';

export function useEnabledPlugins(): Set<string> {
  const { plugins } = useBootstrapStore();
  return new Set(plugins || []);
}