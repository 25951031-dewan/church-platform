import type { ElementType } from 'react';
import { useState } from 'react';
import { NavLink, Outlet } from 'react-router';
import { useUserPermissions } from '@app/common/auth/use-permissions';
import { useBootstrapStore } from '@app/common/core/bootstrap-data';
import { useAuth } from '@app/common/auth/use-auth';
import UserDropdown from '../auth/components/UserDropdown';
import {
  LayoutDashboard, Users, Shield, Settings, Server,
  Mic, Calendar, FileText, BookOpen,
  Users2, HandHeart, Church, Video, Bell, MessageCircle,
  List, LogOut, Menu, X, Layout, Puzzle,
} from 'lucide-react';

interface NavItem { label: string; path: string; icon: ElementType; permission: string; exact?: boolean; }

const navItems: NavItem[] = [
  { label: 'Dashboard',   path: '/admin',                        icon: LayoutDashboard, permission: 'admin.access', exact: true },
  { label: 'Users',       path: '/admin/users',                  icon: Users,           permission: 'users.view' },
  { label: 'Roles',       path: '/admin/roles',                  icon: Shield,          permission: 'roles.view' },
  { label: 'Plugins',     path: '/admin/plugins',                icon: Puzzle,          permission: 'admin.access' },
  { label: 'Feed Layout', path: '/admin/feed-customizer',        icon: Layout,          permission: 'admin.access' },
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

function SidebarContent({ onNavigate }: { onNavigate?: () => void }) {
  const { hasPermission } = useUserPermissions();
  const user = useBootstrapStore((s) => s.user);
  const [showUserDropdown, setShowUserDropdown] = useState(false);

  return (
    <>
      {/* Logo */}
      <div className="h-12 px-4 flex items-center border-b border-white/5 flex-shrink-0">
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
            onClick={onNavigate}
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
      <div className="border-t border-white/5 p-3 flex-shrink-0 relative">
        <button
          type="button"
          aria-label="User menu"
          onClick={() => setShowUserDropdown(!showUserDropdown)}
          className="w-full flex items-center gap-2 px-2 py-1.5 rounded-lg hover:bg-white/5 transition-colors text-left"
        >
          <div className="w-7 h-7 rounded-full bg-indigo-600 flex items-center justify-center text-xs font-bold flex-shrink-0">
            {(user?.name ?? 'A').charAt(0).toUpperCase()}
          </div>
          <div className="flex-1 min-w-0">
            <p className="text-xs font-medium text-white truncate">{user?.name}</p>
            <p className="text-[10px] text-gray-500 truncate">{user?.email}</p>
          </div>
        </button>
        <UserDropdown 
          isOpen={showUserDropdown} 
          onClose={() => setShowUserDropdown(false)} 
        />
      </div>
    </>
  );
}

export function AdminLayout() {
  const [sidebarOpen, setSidebarOpen] = useState(false);

  return (
    <div className="flex h-screen bg-[#0C0E12] text-white overflow-hidden">

      {/* ── Desktop sidebar (always visible ≥ lg) ── */}
      <aside className="hidden lg:flex w-56 flex-shrink-0 bg-[#161920] flex-col border-r border-white/5">
        <SidebarContent />
      </aside>

      {/* ── Mobile slide-over sidebar ── */}
      {sidebarOpen && (
        <div className="lg:hidden fixed inset-0 z-50 flex">
          {/* Backdrop */}
          <div
            className="fixed inset-0 bg-black/60 backdrop-blur-sm"
            onClick={() => setSidebarOpen(false)}
            aria-hidden="true"
          />
          {/* Drawer */}
          <aside className="relative w-56 flex-shrink-0 bg-[#161920] flex flex-col border-r border-white/5 z-10">
            {/* Close button */}
            <button
              type="button"
              aria-label="Close menu"
              onClick={() => setSidebarOpen(false)}
              className="absolute top-3 right-3 p-1 rounded-lg hover:bg-white/10 text-gray-400 hover:text-white transition-colors"
            >
              <X size={16} />
            </button>
            <SidebarContent onNavigate={() => setSidebarOpen(false)} />
          </aside>
        </div>
      )}

      {/* ── Main content ── */}
      <div className="flex-1 flex flex-col overflow-hidden">
        {/* Mobile top bar */}
        <header className="lg:hidden flex items-center gap-3 h-12 px-4 bg-[#161920] border-b border-white/5 flex-shrink-0">
          <button
            type="button"
            aria-label="Open menu"
            onClick={() => setSidebarOpen(true)}
            className="p-1.5 rounded-lg hover:bg-white/10 text-gray-400 hover:text-white transition-colors"
          >
            <Menu size={20} />
          </button>
          <span className="text-sm font-semibold truncate">Church Platform</span>
        </header>

        <main className="flex-1 overflow-auto">
          <div className="p-4 lg:p-6 min-h-full">
            <Outlet />
          </div>
        </main>
      </div>
    </div>
  );
}
