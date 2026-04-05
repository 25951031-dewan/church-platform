/**
 * RequirePlugin — React Router v7 Outlet guard for plugin-gated routes.
 *
 * Usage inside <Routes>:
 *   <Route element={<RequirePlugin plugin="timeline" />}>
 *     <Route path="/feed" element={<NewsfeedPage />} />
 *   </Route>
 *
 * NOTE: PluginRoute (wrapping <Route>) is invalid in React Router v7 —
 * only actual <Route> elements are processed inside <Routes>. Custom
 * components wrapping <Route> are silently ignored, dropping all children.
 * This Outlet pattern matches how RequireAuth works in auth-guards.tsx.
 */
import { Navigate, Outlet } from 'react-router';
import { useEnabledPlugins } from '@app/common/hooks/use-enabled-plugins';

interface RequirePluginProps {
  plugin: string;
  /** Where to redirect if plugin is disabled. Defaults to "/" */
  redirectTo?: string;
}

export function RequirePlugin({ plugin, redirectTo = '/' }: RequirePluginProps) {
  const enabledPlugins = useEnabledPlugins();
  if (!enabledPlugins.has(plugin)) {
    return <Navigate to={redirectTo} replace />;
  }
  return <Outlet />;
}

/** @deprecated Use RequirePlugin instead — PluginRoute breaks React Router v7 */
export function PluginRoute() {
  return null;
}
