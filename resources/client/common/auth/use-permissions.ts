import { useBootstrapStore } from '../core/bootstrap-data';

export function useUserPermissions() {
  const user = useBootstrapStore((s) => s.user);
  const permissions = user?.permissions ?? {};

  return {
    hasPermission: (name: string): boolean => {
      return permissions[name] === true;
    },
    can: (action: string, resource: string): boolean => {
      return permissions[`${resource}.${action}`] === true;
    },
    canAny: (...perms: string[]): boolean => {
      return perms.some((p) => permissions[p] === true);
    },
    isAdmin: permissions['admin.access'] === true,
    roleLevel: user?.role_level ?? 0,
  };
}
