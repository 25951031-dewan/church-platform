import { useQuery } from '@tanstack/react-query';
import { apiClient } from '@app/common/http/api-client';
import { Shield } from 'lucide-react';

interface Role {
  id: number;
  name: string;
  slug: string;
  permissions?: { id: number }[];
}

export function RolesPage() {
  const { data, isLoading } = useQuery({
    queryKey: ['admin-roles'],
    queryFn: () =>
      apiClient.get<{ roles: Role[] }>('roles').then(r => r.data.roles || []),
  });

  return (
    <div>
      <h1 className="text-xl font-bold text-white mb-6">Roles</h1>

      <div className="bg-[#161920] border border-white/5 rounded-xl overflow-hidden">
        <table className="w-full text-sm">
          <thead>
            <tr className="border-b border-white/5">
              <th className="text-left px-4 py-3 text-gray-400 font-medium">Role</th>
              <th className="text-left px-4 py-3 text-gray-400 font-medium">Slug</th>
              <th className="text-left px-4 py-3 text-gray-400 font-medium">Permissions</th>
            </tr>
          </thead>
          <tbody>
            {isLoading ? (
              <tr>
                <td colSpan={3} className="px-4 py-8 text-center text-gray-500">
                  Loading…
                </td>
              </tr>
            ) : (
              data?.map(role => (
                <tr key={role.id} className="border-b border-white/5 hover:bg-white/[0.03] transition-colors">
                  <td className="px-4 py-3">
                    <div className="flex items-center gap-2">
                      <Shield size={14} className="text-indigo-400" aria-hidden="true" />
                      <span className="text-white font-medium">{role.name}</span>
                    </div>
                  </td>
                  <td className="px-4 py-3">
                    <code className="text-xs bg-white/5 px-2 py-0.5 rounded text-gray-300">
                      {role.slug}
                    </code>
                  </td>
                  <td className="px-4 py-3">
                    <span className="text-sm text-gray-400">
                      {role.permissions?.length ?? 0} permissions
                    </span>
                  </td>
                </tr>
              ))
            )}
          </tbody>
        </table>
      </div>
    </div>
  );
}
