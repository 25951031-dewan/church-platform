import { NavLink, Outlet } from 'react-router';
import { useState } from 'react';
import {
  Settings, Mail, Bell, Palette, Lock, Video, Server, Upload, Globe, Search, BarChart2, Code2, ShieldCheck, Bot, Menu, X, Layers, List, Palette as PaletteIcon
} from 'lucide-react';
import type { ElementType } from 'react';

interface SettingNav {
  label: string;
  path: string;
  icon: ElementType;
}

const settingsNav: SettingNav[] = [
  { label: 'General',        path: '/admin/settings/general',       icon: Settings   },
  { label: 'Appearance',     path: '/admin/settings/appearance',    icon: Palette    },
  { label: 'Themes',         path: '/admin/settings/themes',        icon: PaletteIcon },
  { label: 'Authentication', path: '/admin/settings/auth',          icon: Lock       },
  { label: 'Email',          path: '/admin/settings/email',         icon: Mail       },
  { label: 'Notifications',  path: '/admin/settings/notifications', icon: Bell       },
  { label: 'Uploading',      path: '/admin/settings/uploading',     icon: Upload     },
  { label: 'Localization',   path: '/admin/settings/localization',  icon: Globe      },
  { label: 'SEO',            path: '/admin/settings/seo',           icon: Search     },
  { label: 'Analytics',      path: '/admin/settings/analytics',     icon: BarChart2  },
  { label: 'Custom Code',    path: '/admin/settings/custom-code',   icon: Code2      },
  { label: 'GDPR',           path: '/admin/settings/gdpr',          icon: ShieldCheck},
  { label: 'Captcha',        path: '/admin/settings/captcha',       icon: Bot        },
  { label: 'Landing Page',   path: '/admin/settings/landing-page',  icon: Layers     },
  { label: 'Menus',          path: '/admin/settings/menus',         icon: List       },
  { label: 'Live Meetings',  path: '/admin/settings/live-meetings', icon: Video      },
  { label: 'System',         path: '/admin/system',                 icon: Server     },
];

function SidebarContent({ onNavigate }: { onNavigate?: () => void }) {
  return (
    <>
      <h1 className="text-lg font-bold text-white px-3 mb-4">Settings</h1>
      <nav className="space-y-0.5">
        {settingsNav.map(item => (
          <NavLink
            key={item.path}
            to={item.path}
            onClick={onNavigate}
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
    </>
  );
}

export function SettingsLayout() {
  const [drawerOpen, setDrawerOpen] = useState(false);

  return (
    <div className="flex gap-0 h-full min-h-0">
      {/* Desktop sidebar (always visible ≥ lg) */}
      <aside className="hidden lg:block w-48 flex-shrink-0 border-r border-white/5 pr-2 mr-6">
        <SidebarContent />
      </aside>

      {/* Mobile slide-over drawer */}
      {drawerOpen && (
        <div className="lg:hidden fixed inset-0 z-50 flex">
          {/* Backdrop */}
          <div
            className="fixed inset-0 bg-black/60 backdrop-blur-sm"
            onClick={() => setDrawerOpen(false)}
            aria-hidden="true"
          />
          {/* Drawer */}
          <aside className="relative w-48 flex-shrink-0 bg-[#161920] p-4 border-r border-white/5 z-10">
            {/* Close button */}
            <button
              type="button"
              aria-label="Close menu"
              onClick={() => setDrawerOpen(false)}
              className="absolute top-3 right-3 p-1 rounded-lg hover:bg-white/10 text-gray-400 hover:text-white transition-colors"
            >
              <X size={16} />
            </button>
            <SidebarContent onNavigate={() => setDrawerOpen(false)} />
          </aside>
        </div>
      )}

      {/* Main content area */}
      <div className="flex-1 min-w-0 overflow-auto">
        {/* Mobile header with hamburger */}
        <div className="lg:hidden flex items-center gap-3 mb-4">
          <button
            type="button"
            aria-label="Open menu"
            onClick={() => setDrawerOpen(true)}
            className="p-1.5 rounded-lg hover:bg-white/10 text-gray-400 hover:text-white transition-colors"
          >
            <Menu size={20} />
          </button>
          <h1 className="text-lg font-bold text-white">Settings</h1>
        </div>
        
        <Outlet />
      </div>
    </div>
  );
}
