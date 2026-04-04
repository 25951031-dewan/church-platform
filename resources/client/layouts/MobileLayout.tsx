import { NavLink, Outlet } from 'react-router';
import { Home, Search, Rss, User } from 'lucide-react';

const mobileNav = [
  { label: 'Home',    path: '/',        icon: Home,   exact: true },
  { label: 'Search',  path: '/sermons', icon: Search  },
  { label: 'Feed',    path: '/feed',    icon: Rss     },
  { label: 'Account', path: '/login',   icon: User    },
] as const;

export function MobileLayout() {
  return (
    <div className="flex flex-col min-h-screen">
      <main className="flex-1 pb-16 sm:pb-0">
        <Outlet />
      </main>

      {/* Bottom nav — mobile only */}
      <nav className="sm:hidden fixed bottom-0 left-0 right-0 bg-[#161920] border-t border-white/5 flex z-50">
        {mobileNav.map(item => (
          <NavLink
            key={item.path}
            to={item.path}
            end={'exact' in item ? item.exact : undefined}
            className={({ isActive }) =>
              `flex-1 flex flex-col items-center justify-center py-2 text-[10px] transition-colors ${
                isActive ? 'text-white' : 'text-gray-500'
              }`
            }
          >
            {({ isActive }) => (
              <>
                <item.icon size={20} className={isActive ? 'text-white' : 'text-gray-500'} aria-hidden="true" />
                <span className="mt-1">{item.label}</span>
              </>
            )}
          </NavLink>
        ))}
      </nav>
    </div>
  );
}
