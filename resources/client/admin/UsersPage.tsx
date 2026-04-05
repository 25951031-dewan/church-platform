import { useState } from 'react';
import { useQuery, useMutation, useQueryClient } from '@tanstack/react-query';
import { apiClient } from '@app/common/http/api-client';
import { Search, AlertCircle, Shield, Edit, UserPlus, X } from 'lucide-react';
import { useNotificationStore } from '@app/common/stores';

interface Role {
  id: number;
  name: string;
  slug: string;
}

interface User {
  id: number;
  name: string | null;
  email: string;
  avatar: string | null;
  roles: Role[];
  created_at: string;
}

interface PaginatedUsers {
  data: User[];
  total: number;
  current_page: number;
  last_page: number;
}

interface UserRoleModalProps {
  user: User;
  onClose: () => void;
}

function UserRoleModal({ user, onClose }: UserRoleModalProps) {
  const [selectedRoles, setSelectedRoles] = useState<number[]>(
    user.roles.map(r => r.id)
  );
  const queryClient = useQueryClient();
  const { success, error } = useNotificationStore();

  const { data: allRoles } = useQuery({
    queryKey: ['admin-roles'],
    queryFn: () => apiClient.get<{ roles: Role[] }>('roles').then(r => r.data.roles || []),
  });

  const updateRolesMutation = useMutation({
    mutationFn: (roleIds: number[]) =>
      fetch(`/api/admin/users/${user.id}/roles`, {
        method: 'PUT',
        headers: {
          'Accept': 'application/json',
          'Content-Type': 'application/json',
          'Authorization': `Bearer ${localStorage.getItem('auth_token')}`,
        },
        body: JSON.stringify({ roles: roleIds }),
      }).then(async (response) => {
        if (!response.ok) throw new Error('Failed to update roles');
        return response.json();
      }),
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: ['admin-users'] });
      success('User roles updated successfully');
      onClose();
    },
    onError: () => error('Failed to update user roles'),
  });

  return (
    <div className="fixed inset-0 bg-black/50 flex items-center justify-center z-50">
      <div className="bg-[#161920] border border-white/10 rounded-xl w-full max-w-md">
        <div className="flex items-center justify-between p-4 border-b border-white/10">
          <h3 className="text-lg font-semibold text-white">Edit User Roles</h3>
          <button onClick={onClose} className="text-gray-400 hover:text-white">
            <X size={20} />
          </button>
        </div>

        <div className="p-4">
          <div className="flex items-center gap-3 mb-4">
            {user.avatar ? (
              <img src={user.avatar} className="w-10 h-10 rounded-full object-cover" alt="" />
            ) : (
              <div className="w-10 h-10 rounded-full bg-indigo-600 flex items-center justify-center font-bold text-white">
                {(user.name ?? 'U').charAt(0).toUpperCase()}
              </div>
            )}
            <div>
              <div className="font-medium text-white">{user.name ?? '(no name)'}</div>
              <div className="text-sm text-gray-400">{user.email}</div>
            </div>
          </div>

          <div className="space-y-3 mb-6">
            <label className="block text-sm font-medium text-gray-300">Roles</label>
            {allRoles?.map((role) => (
              <label key={role.id} className="flex items-center gap-3">
                <input
                  type="checkbox"
                  checked={selectedRoles.includes(role.id)}
                  onChange={(e) => {
                    if (e.target.checked) {
                      setSelectedRoles([...selectedRoles, role.id]);
                    } else {
                      setSelectedRoles(selectedRoles.filter(id => id !== role.id));
                    }
                  }}
                  className="rounded border-white/20 bg-[#0C0E12]"
                />
                <div>
                  <span className="text-sm text-white">{role.name}</span>
                  <code className="text-xs text-gray-500 ml-2">{role.slug}</code>
                </div>
              </label>
            ))}
          </div>

          <div className="flex items-center justify-end gap-3">
            <button
              type="button"
              onClick={onClose}
              className="px-4 py-2 text-gray-400 hover:text-white transition-colors"
            >
              Cancel
            </button>
            <button
              type="button"
              onClick={() => updateRolesMutation.mutate(selectedRoles)}
              disabled={updateRolesMutation.isPending}
              className="flex items-center gap-2 px-4 py-2 bg-indigo-600 text-white rounded-lg hover:bg-indigo-700 disabled:opacity-50 transition-colors"
            >
              <Shield size={16} />
              Update Roles
            </button>
          </div>
        </div>
      </div>
    </div>
  );
}

export function UsersPage() {
  const [search, setSearch] = useState('');
  const [page, setPage] = useState(1);
  const [editingUser, setEditingUser] = useState<User | undefined>();

  const { data, isLoading, isError } = useQuery({
    queryKey: ['admin-users', search, page],
    queryFn: async () => {
      const response = await fetch(`/api/admin/users?${new URLSearchParams({
        search: search || '',
        page: String(page),
        per_page: '15',
      })}`, {
        headers: {
          'Accept': 'application/json',
          'Authorization': `Bearer ${localStorage.getItem('auth_token')}`,
        },
      });
      
      if (!response.ok) {
        throw new Error('Failed to fetch users');
      }
      
      const data = await response.json();
      return {
        data: data.pagination?.data || data.data || [],
        total: data.pagination?.total || data.total || 0,
        current_page: data.pagination?.current_page || data.current_page || 1,
        last_page: data.pagination?.last_page || data.last_page || 1,
      };
    },
    placeholderData: (prev) => prev,
  });

  return (
    <div>
      <div className="flex items-center justify-between mb-6">
        <h1 className="text-xl font-bold text-white flex items-center gap-2">
          <UserPlus size={24} />
          Users
        </h1>
        <div className="flex items-center gap-4">
          <span className="text-sm text-gray-400">
            {data?.total || 0} total users
          </span>
          <div className="relative">
            <Search size={14} className="absolute left-3 top-1/2 -translate-y-1/2 text-gray-500" aria-hidden="true" />
            <input
              value={search}
              onChange={e => {
                setSearch(e.target.value);
                setPage(1);
              }}
              placeholder="Search users…"
              aria-label="Search users"
              className="pl-8 pr-4 py-1.5 bg-[#161920] border border-white/10 rounded-lg text-sm text-white placeholder-gray-500 focus:outline-none focus:border-indigo-500 w-56"
            />
          </div>
        </div>
      </div>

      {isError && (
        <div className="flex items-center gap-2 bg-red-500/10 border border-red-500/20 rounded-xl p-4 mb-4 text-red-400 text-sm">
          <AlertCircle size={16} aria-hidden="true" />
          Failed to load users. Check your permissions.
        </div>
      )}

      <div className="bg-[#161920] border border-white/5 rounded-xl overflow-hidden">
        <table className="w-full text-sm">
          <thead>
            <tr className="border-b border-white/5">
              <th className="text-left px-4 py-3 text-gray-400 font-medium">User</th>
              <th className="text-left px-4 py-3 text-gray-400 font-medium hidden sm:table-cell">Email</th>
              <th className="text-left px-4 py-3 text-gray-400 font-medium hidden md:table-cell">Roles</th>
              <th className="text-left px-4 py-3 text-gray-400 font-medium hidden lg:table-cell">Joined</th>
              <th className="text-right px-4 py-3 text-gray-400 font-medium">Actions</th>
            </tr>
          </thead>
          <tbody>
            {isLoading ? (
              <tr>
                <td colSpan={5} className="px-4 py-8 text-center text-gray-500">
                  Loading…
                </td>
              </tr>
            ) : !data?.data?.length ? (
              <tr>
                <td colSpan={5} className="px-4 py-8 text-center text-gray-500">
                  {search ? `No users found for "${search}"` : 'No users found.'}
                </td>
              </tr>
            ) : (
              data.data.map(user => (
                <tr key={user.id} className="border-b border-white/5 hover:bg-white/[0.03] transition-colors">
                  <td className="px-4 py-3">
                    <div className="flex items-center gap-2">
                      {user.avatar ? (
                        <img src={user.avatar} className="w-7 h-7 rounded-full object-cover flex-shrink-0" alt="" />
                      ) : (
                        <div className="w-7 h-7 rounded-full bg-indigo-600 flex items-center justify-center text-xs font-bold text-white flex-shrink-0">
                          {(user.name ?? 'U').charAt(0).toUpperCase()}
                        </div>
                      )}
                      <span className="text-white font-medium truncate">{user.name ?? '(no name)'}</span>
                    </div>
                  </td>
                  <td className="px-4 py-3 text-gray-400 hidden sm:table-cell">{user.email}</td>
                  <td className="px-4 py-3 hidden md:table-cell">
                    <div className="flex flex-wrap gap-1">
                      {user.roles?.length > 0 ? (
                        user.roles.map(role => (
                          <span
                            key={role.id}
                            className="px-1.5 py-0.5 bg-indigo-600/20 text-indigo-400 text-xs rounded"
                          >
                            {role.name}
                          </span>
                        ))
                      ) : (
                        <span className="text-gray-500 text-xs">No roles</span>
                      )}
                    </div>
                  </td>
                  <td className="px-4 py-3 text-gray-500 text-xs hidden lg:table-cell">
                    {new Date(user.created_at).toLocaleDateString()}
                  </td>
                  <td className="px-4 py-3">
                    <div className="flex items-center justify-end gap-2">
                      <button
                        onClick={() => setEditingUser(user)}
                        className="p-2 text-gray-400 hover:text-indigo-400 transition-colors"
                        title="Edit roles"
                      >
                        <Shield size={16} />
                      </button>
                    </div>
                  </td>
                </tr>
              ))
            )}
          </tbody>
        </table>

        {data && data.last_page > 1 && (
          <div className="flex items-center justify-between px-4 py-3 border-t border-white/5">
            <span className="text-xs text-gray-500">
              Showing {((page - 1) * 15) + 1} to {Math.min(page * 15, data.total)} of {data.total} users
            </span>
            <div className="flex gap-2">
              <button
                type="button"
                disabled={page <= 1}
                onClick={() => setPage(p => p - 1)}
                className="px-3 py-1 text-xs bg-white/5 hover:bg-white/10 rounded disabled:opacity-30 text-white transition-colors"
              >
                Prev
              </button>
              <span className="px-3 py-1 text-xs text-gray-400">
                {page} / {data.last_page}
              </span>
              <button
                type="button"
                disabled={page >= data.last_page}
                onClick={() => setPage(p => p + 1)}
                className="px-3 py-1 text-xs bg-white/5 hover:bg-white/10 rounded disabled:opacity-30 text-white transition-colors"
              >
                Next
              </button>
            </div>
          </div>
        )}
      </div>

      {editingUser && (
        <UserRoleModal
          user={editingUser}
          onClose={() => setEditingUser(undefined)}
        />
      )}
    </div>
  );
}
