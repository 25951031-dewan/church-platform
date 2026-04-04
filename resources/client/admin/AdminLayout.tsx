import type { ElementType } from 'react';
import { NavLink, Outlet } from 'react-router';
import { useUserPermissions } from '@app/common/auth/use-permissions';
import { useBootstrapStore } from '@app/common/core/bootstrap-data';
import { useAuth } from '@app/common/auth/use-auth';
import {
  LayoutDashboard, Users, Shield, Settings, Server,
  Mic, Calendar, FileText, BookOpen,
  Users2, HandHeart, Church, Video, Bell, MessageCircle,
  List, LogOut,
} from 'lucide-react';

interface NavItem { label: string; path: string; icon: ElementType; permission: string; exact?: boolean; }

const navItems: NavItem[] = [
  { label: 'Dashboard',   path: '/admin',                        icon: LayoutDashboard, permission: 'admin.access', exact: true },
  { label: 'Users',       path: '/admin/users',                  icon: Users,           permission: 'users.view' },
  { label: 'Roles',       path: '/admin/roles',                  icon: Shield,          permission: 'roles.view' },
  { label: 'Sermons',     path: '/admin/sermons',                icon: Mic,             permission: 'sermons.view' },
  { label: 'Events',      path: '/admin/events',                 icon: Calendar,        permission: 'events.view' },
  { label: 'Blog',        path: '/admin/blog',                   icon: FileText,        permission: 'blog.view' },
  { label: 'Library',     path: '/admin/library',                icon: BookOpen,        permission: 'library.view' },
  { label: 'Groups',      path: '/admin/groups',                 icon: Users2,          permission: 'groups.view' },
  { label: 'Prayers',     path: '/admin/prayers',                icon: HandHeart,       permission: 'prayer.view' },
  { label: 'Churches',    path: '/admin/churches',               icon: Church,          permission: 'churches.view' },
  { label: 'Meetings',    path: '/admin/meetings',               icon: Video,           permission: 'admin.access' },
  { label: 'Chat',        path: '/admin/chat',                   icon: MessageCircle,   permission: 'chat.send' },
  { label: 'Notif Logs',  path: '/admin/notification-logs',      icon: List,            permission: 'admin.access' },
  { label: 'Templates',   path: '/admin/notification-templates', icon: Bell,            permission: 'admin.access' },
  { label: 'Settings',    path: '/admin/settings',               icon: Settings,        permission: 'settings.view' },
  { label: 'System',      path: '/admin/system',                 icon: Server,          permission: 'manage_settings' },
];

export function AdminLayout() {
  const { hasPermission } = useUserPermissions();
  const user = useBootstrapStore((s) => s.user);
  const { logout } = useAuth();

  return (
    <div className="flex h-screen bg-[#0C0E12] text-white overflow-hidden">
      <aside className="w-56 flex-shrink-0 bg-[#161920] flex flex-col border-r border-white/5">
        {/* Logo */}
        <div className="h-12 px-4 flex items-center border-b border-white/5">
          <span className="text-lg">⛪</span>
          <span className="ml-2 font-semibold text-sm truncate">Church Platform</span>
        </div>

        {/* Nav */}
        <nav className="flex-1 overflow-y-auto py-2 px-2 space-y-0.5">
          {navItems.filter(item => hasPermission(item.permission)).map(item => (
            <NavLink
              key={item.path}
              to={item.path}
              end={item.exact}
              className={({ isActive }) =>
                `flex items-center gap-3 px-3 py-2 rounded-lg text-sm transition-colors ${
                  isActive
                    ? 'bg-white/10 text-white'
                    : 'text-gray-400 hover:text-white hover:bg-white/5'
                }`
              }
            >
              {({ isActive }) => (
                <>
                  <item.icon size={16} className={isActive ? 'text-white' : 'text-gray-500'} />
                  {item.label}
                </>
              )}
            </NavLink>
          ))}
        </nav>

        {/* User footer */}
        <div className="border-t border-white/5 p-3">
          <button
            type="button"
            aria-label="Sign out"
            onClick={logout}
            className="w-full flex items-center gap-2 px-2 py-1.5 rounded-lg hover:bg-white/5 transition-colors text-left"
          >
            <div className="w-7 h-7 rounded-full bg-indigo-600 flex items-center justify-center text-xs font-bold flex-shrink-0">
              {user?.name?.charAt(0)?.toUpperCase() ?? 'A'}
            </div>
            <div className="flex-1 min-w-0">
              <p className="text-xs font-medium text-white truncate">{user?.name}</p>
              <p className="text-[10px] text-gray-500 truncate">{user?.email}</p>
            </div>
            <LogOut size={12} className="text-gray-500 flex-shrink-0" aria-hidden="true" />
          </button>
        </div>
      </aside>

      {/* Content */}
      <main className="flex-1 overflow-auto">
        <div className="p-6 min-h-full">
          <Outlet />
        </div>
      </main>
    </div>
  );
}
