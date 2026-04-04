import { NavLink, Outlet } from 'react-router';
import { useUserPermissions } from '@app/common/auth/use-permissions';

interface NavItem {
  label: string;
  path: string;
  permission: string;
}

interface NavSection {
  heading: string;
  items: NavItem[];
}

const navSections: NavSection[] = [
  {
    heading: 'Content',
    items: [
      { label: 'Feed', path: '/feed', permission: 'posts.view' },
      { label: 'Sermons', path: '/sermons', permission: 'sermons.view' },
      { label: 'Events', path: '/events', permission: 'events.view' },
      { label: 'Blog', path: '/blog', permission: 'blog.view' },
      { label: 'Library', path: '/library', permission: 'library.view' },
    ],
  },
  {
    heading: 'Community',
    items: [
      { label: 'Groups', path: '/groups', permission: 'groups.view' },
      { label: 'Prayer Wall', path: '/prayers', permission: 'prayer.view' },
      { label: 'Churches', path: '/churches', permission: 'churches.view' },
      { label: 'Live Meetings', path: '/meetings', permission: 'live_meeting.view' },
      { label: 'Chat', path: '/chat', permission: 'chat.send' },
    ],
  },
  {
    heading: 'Admin',
    items: [
      { label: 'Dashboard', path: '/admin', permission: 'admin.access' },
      { label: 'Users', path: '/admin/users', permission: 'users.view' },
      { label: 'Roles', path: '/admin/roles', permission: 'roles.view' },
      { label: 'Meetings', path: '/admin/meetings', permission: 'admin.access' },
    ],
  },
  {
    heading: 'Notifications',
    items: [
      { label: 'Inbox', path: '/notifications', permission: 'admin.access' },
      { label: 'Templates', path: '/admin/notification-templates', permission: 'admin.access' },
      { label: 'Logs', path: '/admin/notification-logs', permission: 'admin.access' },
    ],
  },
  {
    heading: 'Configuration',
    items: [
      { label: 'Settings', path: '/admin/settings', permission: 'settings.view' },
      { label: 'System', path: '/admin/system', permission: 'manage_settings' },
    ],
  },
];

export function AdminLayout() {
  const { hasPermission } = useUserPermissions();

  return (
    <div className="flex h-screen bg-gray-100 dark:bg-gray-900">
      {/* Sidebar */}
      <aside className="w-60 bg-white dark:bg-gray-800 border-r border-gray-200 dark:border-gray-700 flex flex-col">
        {/* Logo */}
        <div className="h-14 px-4 flex items-center border-b border-gray-200 dark:border-gray-700 flex-shrink-0">
          <span className="text-xl mr-2">⛪</span>
          <span className="font-bold text-gray-900 dark:text-white">Church Platform</span>
        </div>

        {/* Nav */}
        <nav className="flex-1 overflow-y-auto py-3 px-2">
          {navSections.map((section) => {
            const visibleItems = section.items.filter((item) => hasPermission(item.permission));
            if (visibleItems.length === 0) return null;
            return (
              <div key={section.heading} className="mb-4">
                <p className="px-3 mb-1 text-[10px] font-semibold uppercase tracking-widest text-gray-400 dark:text-gray-500">
                  {section.heading}
                </p>
                {visibleItems.map((item) => (
                  <NavLink
                    key={item.path}
                    to={item.path}
                    end={item.path === '/admin'}
                    className={({ isActive }) =>
                      `flex items-center px-3 py-1.5 rounded-md text-sm font-medium mb-0.5 transition-colors ${
                        isActive
                          ? 'bg-indigo-50 text-indigo-700 dark:bg-indigo-900/30 dark:text-indigo-300'
                          : 'text-gray-600 dark:text-gray-400 hover:bg-gray-50 dark:hover:bg-gray-700/50 hover:text-gray-900 dark:hover:text-white'
                      }`
                    }
                  >
                    {item.label}
                  </NavLink>
                ))}
              </div>
            );
          })}
        </nav>

        {/* Footer */}
        <div className="px-4 py-3 border-t border-gray-200 dark:border-gray-700 flex-shrink-0">
          <a
            href="/"
            className="text-xs text-gray-400 dark:text-gray-500 hover:text-gray-600 dark:hover:text-gray-300 transition-colors"
          >
            ← View site
          </a>
        </div>
      </aside>

      {/* Main content */}
      <main className="flex-1 overflow-auto">
        <div className="p-6">
          <Outlet />
        </div>
      </main>
    </div>
  );
}
