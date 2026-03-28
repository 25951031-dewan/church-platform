import { NavLink, Outlet } from 'react-router';
import { useUserPermissions } from '@app/common/auth/use-permissions';

const sidebarItems = [
  { label: 'Feed', path: '/feed', icon: 'Newspaper', permission: 'posts.view' },
  { label: 'Groups', path: '/groups', icon: 'Users2', permission: 'groups.view' },
  { label: 'Events', path: '/events', icon: 'Calendar', permission: 'events.view' },
  { label: 'Sermons', path: '/sermons', icon: 'Mic', permission: 'sermons.view' },
  { label: 'Dashboard', path: '/admin', icon: 'LayoutDashboard', permission: 'admin.access' },
  { label: 'Users', path: '/admin/users', icon: 'Users', permission: 'users.view' },
  { label: 'Roles', path: '/admin/roles', icon: 'Shield', permission: 'roles.view' },
  { label: 'Settings', path: '/admin/settings', icon: 'Settings', permission: 'settings.view' },
];

export function AdminLayout() {
  const { hasPermission } = useUserPermissions();

  return (
    <div className="flex h-screen bg-gray-100 dark:bg-gray-900">
      {/* Sidebar */}
      <aside className="w-64 bg-white dark:bg-gray-800 border-r border-gray-200 dark:border-gray-700">
        <div className="p-4 border-b border-gray-200 dark:border-gray-700">
          <h1 className="text-lg font-bold text-gray-900 dark:text-white">Admin Panel</h1>
        </div>
        <nav className="p-2 space-y-1">
          {sidebarItems
            .filter((item) => hasPermission(item.permission))
            .map((item) => (
              <NavLink
                key={item.path}
                to={item.path}
                end={item.path === '/admin'}
                className={({ isActive }) =>
                  `block px-3 py-2 rounded-md text-sm ${
                    isActive
                      ? 'bg-primary-50 text-primary-700 dark:bg-primary-900/20 dark:text-primary-400'
                      : 'text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700/50'
                  }`
                }
              >
                {item.label}
              </NavLink>
            ))}
        </nav>
      </aside>

      {/* Content */}
      <main className="flex-1 overflow-auto p-6">
        <Outlet />
      </main>
    </div>
  );
}
