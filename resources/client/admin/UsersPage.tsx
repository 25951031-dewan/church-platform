import { useState } from 'react';
import { useQuery } from '@tanstack/react-query';
import { apiClient } from '@app/common/http/api-client';
import { Search, AlertCircle } from 'lucide-react';

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

export function UsersPage() {
  const [search, setSearch] = useState('');
  const [page, setPage] = useState(1);

  const { data, isLoading, isError } = useQuery({
    queryKey: ['admin-users', search, page],
    queryFn: async () => {
      // Use admin users endpoint which is outside v1 prefix
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
      // Handle the pagination response format
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
        <h1 className="text-xl font-bold text-white">Users</h1>
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
            </tr>
          </thead>
          <tbody>
            {isLoading ? (
              <tr>
                <td colSpan={4} className="px-4 py-8 text-center text-gray-500">
                  Loading…
                </td>
              </tr>
            ) : !data?.data?.length ? (
              <tr>
                <td colSpan={4} className="px-4 py-8 text-center text-gray-500">
                  No users found.
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
                      {user.roles?.map(role => (
                        <span
                          key={role.id}
                          className="px-1.5 py-0.5 bg-indigo-600/20 text-indigo-400 text-xs rounded"
                        >
                          {role.name}
                        </span>
                      ))}
                    </div>
                  </td>
                  <td className="px-4 py-3 text-gray-500 text-xs hidden lg:table-cell">
                    {new Date(user.created_at).toLocaleDateString()}
                  </td>
                </tr>
              ))
            )}
          </tbody>
        </table>

        {data && data.last_page > 1 && (
          <div className="flex items-center justify-between px-4 py-3 border-t border-white/5">
            <span className="text-xs text-gray-500">{data.total} total users</span>
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
    </div>
  );
}
