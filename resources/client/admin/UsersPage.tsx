import { useState } from 'react';
import { useQuery } from '@tanstack/react-query';
import { apiClient } from '@app/common/http/api-client';
import { Search } from 'lucide-react';

interface User {
  id: number;
  name: string;
  email: string;
  avatar: string | null;
  roles: string[];
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

  const { data, isLoading } = useQuery({
    queryKey: ['admin-users', search, page],
    queryFn: () =>
      apiClient
        .get<PaginatedUsers>('users', { params: { search, page, per_page: 15 } })
        .then(r => r.data),
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

      <div className="bg-[#161920] border border-white/5 rounded-xl overflow-hidden">
        <table className="w-full text-sm">
          <thead>
            <tr className="border-b border-white/5">
              <th className="text-left px-4 py-3 text-gray-400 font-medium">User</th>
              <th className="text-left px-4 py-3 text-gray-400 font-medium">Email</th>
              <th className="text-left px-4 py-3 text-gray-400 font-medium">Roles</th>
              <th className="text-left px-4 py-3 text-gray-400 font-medium">Joined</th>
            </tr>
          </thead>
          <tbody>
            {isLoading ? (
              <tr>
                <td colSpan={4} className="px-4 py-8 text-center text-gray-500">
                  Loading…
                </td>
              </tr>
            ) : (
              data?.data.map(user => (
                <tr key={user.id} className="border-b border-white/5 hover:bg-white/[0.03] transition-colors">
                  <td className="px-4 py-3">
                    <div className="flex items-center gap-2">
                      {user.avatar ? (
                        <img src={user.avatar} className="w-7 h-7 rounded-full object-cover" alt="" />
                      ) : (
                        <div className="w-7 h-7 rounded-full bg-indigo-600 flex items-center justify-center text-xs font-bold text-white">
                          {user.name.charAt(0).toUpperCase()}
                        </div>
                      )}
                      <span className="text-white font-medium">{user.name}</span>
                    </div>
                  </td>
                  <td className="px-4 py-3 text-gray-400">{user.email}</td>
                  <td className="px-4 py-3">
                    <div className="flex flex-wrap gap-1">
                      {user.roles?.map(r => (
                        <span
                          key={r}
                          className="px-1.5 py-0.5 bg-indigo-600/20 text-indigo-400 text-xs rounded"
                        >
                          {r}
                        </span>
                      ))}
                    </div>
                  </td>
                  <td className="px-4 py-3 text-gray-500 text-xs">
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
