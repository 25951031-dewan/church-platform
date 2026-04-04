import { NavLink, Outlet } from 'react-router';
import {
  Settings, Mail, Bell, Palette, Lock, Video, Server,
} from 'lucide-react';
import type { ElementType } from 'react';

interface SettingNav {
  label: string;
  path: string;
  icon: ElementType;
}

const settingsNav: SettingNav[] = [
  { label: 'General',       path: '/admin/settings/general',       icon: Settings },
  { label: 'Authentication',path: '/admin/settings/auth',          icon: Lock     },
  { label: 'Email',         path: '/admin/settings/email',         icon: Mail     },
  { label: 'Notifications', path: '/admin/settings/notifications', icon: Bell     },
  { label: 'Live Meetings', path: '/admin/settings/live-meetings', icon: Video    },
  { label: 'Appearance',    path: '/admin/settings/appearance',    icon: Palette  },
  { label: 'System',        path: '/admin/system',                 icon: Server   },
];

export function SettingsLayout() {
  return (
    <div className="flex gap-0 h-full min-h-0">
      {/* Left settings sub-nav */}
      <aside className="w-48 flex-shrink-0 border-r border-white/5 pr-2 mr-6">
        <h1 className="text-lg font-bold text-white px-3 mb-4">Settings</h1>
        <nav className="space-y-0.5">
          {settingsNav.map(item => (
            <NavLink
              key={item.path}
              to={item.path}
              className={({ isActive }) =>
                `flex items-center gap-2.5 px-3 py-2 rounded-lg text-sm transition-colors ${
                  isActive
                    ? 'bg-white/10 text-white font-medium'
                    : 'text-gray-400 hover:text-white hover:bg-white/5'
                }`
              }
            >
              {({ isActive }) => (
                <>
                  <item.icon size={15} className={isActive ? 'text-white' : 'text-gray-500'} aria-hidden="true" />
                  {item.label}
                </>
              )}
            </NavLink>
          ))}
        </nav>
      </aside>

      {/* Right content */}
      <div className="flex-1 min-w-0 overflow-auto">
        <Outlet />
      </div>
    </div>
  );
}
