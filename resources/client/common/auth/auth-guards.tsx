import { Navigate, Outlet } from 'react-router';
import { useAuth } from './use-auth';
import { useUserPermissions } from './use-permissions';

export function RequireAuth() {
  const { isAuthenticated } = useAuth();
  if (!isAuthenticated) return <Navigate to="/login" replace />;
  return <Outlet />;
}

export function RequirePermission({ permission }: { permission: string }) {
  const { hasPermission } = useUserPermissions();
  if (!hasPermission(permission)) return <Navigate to="/" replace />;
  return <Outlet />;
}
