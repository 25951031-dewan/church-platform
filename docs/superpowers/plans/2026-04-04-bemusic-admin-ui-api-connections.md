# BeMusic-Style Admin UI + API Connections — Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Rebuild the church platform admin panel to match BeMusic's dark UI design and wire all frontend pages to their existing backend API endpoints.

**Architecture:** Dark-themed `AdminLayout` with lucide-react icons; each admin page is a self-contained TSX file that uses TanStack Query + `apiClient` to load and mutate data. Public pages get a mobile bottom navigation bar. Settings pages use a BeMusic-style two-panel layout (left sub-nav + right form) connected to `PUT /api/v1/settings`.

**Tech Stack:** React 19, TypeScript, TailwindCSS, React Router v6, TanStack Query v5, axios (`apiClient` from `@app/common/http/api-client`), lucide-react

---

## File Map

### Modified
- `resources/client/admin/AdminLayout.tsx` — BeMusic dark sidebar with lucide icons, grouped nav, user footer
- `resources/client/admin/DashboardPage.tsx` — real stats from `GET /api/v1/dashboard/stats`
- `resources/client/admin/SettingsPage.tsx` — two-panel layout (sub-nav + form); replaces card grid
- `resources/client/app-router.tsx` — add Users, Roles, Settings sub-routes, MobileLayout

### Created
- `resources/client/admin/UsersPage.tsx` — paginated table + search, role assignment (`GET/PUT /api/v1/users`)
- `resources/client/admin/RolesPage.tsx` — roles list with permission counts (`GET /api/v1/roles`)
- `resources/client/admin/settings/GeneralSettingsPanel.tsx` — church name, contact, social links form
- `resources/client/admin/settings/EmailSettingsPanel.tsx` — SMTP form (`GET/PUT /api/v1/settings/email`)
- `resources/client/admin/settings/NotificationsSettingsPanel.tsx` — OneSignal/Twilio keys
- `resources/client/admin/settings/AppearanceSettingsPanel.tsx` — logo, favicon, PWA icons
- `resources/client/layouts/MobileLayout.tsx` — bottom nav bar (Home, Search, Feed, Account) for public routes
- `resources/client/common/components/AdminTable.tsx` — reusable dark-themed table component
- `resources/client/common/components/FormField.tsx` — reusable label+input wrapper with error display

---

## Task 1: BeMusic-Style AdminLayout

**Files:**
- Modify: `resources/client/admin/AdminLayout.tsx`

**Design spec (from BeMusic screenshots):**
- Background: `bg-[#0C0E12]` (near-black)
- Sidebar: `w-56 bg-[#161920]` with no section headings — just icon + label rows
- Active item: `bg-white/10 text-white`, inactive: `text-gray-400 hover:text-white hover:bg-white/5`
- Logo area: 48px tall, logo image or church name
- User footer: avatar + name + chevron at bottom of sidebar
- Content area: `bg-[#0C0E12]` dark background

- [ ] **Step 1: Rewrite AdminLayout.tsx**

```tsx
import { NavLink, Outlet, useNavigate } from 'react-router';
import { useUserPermissions } from '@app/common/auth/use-permissions';
import { useBootstrapStore } from '@app/common/core/bootstrap-data';
import { useAuth } from '@app/common/auth/use-auth';
import {
  LayoutDashboard, Users, Shield, Settings, Server,
  Newspaper, Calendar, Mic, BookOpen, FileText,
  Users2, HandHeart, Church, Video, Bell, MessageCircle,
  List, BarChart2, ChevronDown,
} from 'lucide-react';

interface NavItem { label: string; path: string; icon: React.ElementType; permission: string; exact?: boolean; }

const navItems: NavItem[] = [
  { label: 'Dashboard',   path: '/admin',                        icon: LayoutDashboard, permission: 'admin.access', exact: true },
  { label: 'Users',       path: '/admin/users',                  icon: Users,           permission: 'manage_users' },
  { label: 'Roles',       path: '/admin/roles',                  icon: Shield,          permission: 'manage_roles' },
  { label: 'Sermons',     path: '/admin/sermons',                icon: Mic,             permission: 'sermons.view' },
  { label: 'Events',      path: '/admin/events',                 icon: Calendar,        permission: 'events.view' },
  { label: 'Blog',        path: '/admin/blog',                   icon: FileText,        permission: 'blog.view' },
  { label: 'Library',     path: '/admin/library',                icon: BookOpen,        permission: 'library.view' },
  { label: 'Groups',      path: '/admin/groups',                 icon: Users2,          permission: 'groups.view' },
  { label: 'Prayers',     path: '/admin/prayers',                icon: HandHeart,       permission: 'prayer.view' },
  { label: 'Churches',    path: '/admin/churches',               icon: Church,          permission: 'churches.view' },
  { label: 'Meetings',    path: '/admin/meetings',               icon: Video,           permission: 'admin.access' },
  { label: 'Chat',        path: '/admin/chat',                   icon: MessageCircle,   permission: 'chat.moderate' },
  { label: 'Notif Logs',  path: '/admin/notification-logs',      icon: List,            permission: 'admin.access' },
  { label: 'Templates',   path: '/admin/notification-templates', icon: Bell,            permission: 'admin.access' },
  { label: 'Settings',    path: '/admin/settings',               icon: Settings,        permission: 'settings.view' },
  { label: 'System',      path: '/admin/system',                 icon: Server,          permission: 'manage_settings' },
];

export function AdminLayout() {
  const { hasPermission } = useUserPermissions();
  const { user } = useBootstrapStore();
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
            <ChevronDown size={12} className="text-gray-500 flex-shrink-0" />
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
```

- [ ] **Step 2: Build and verify no TS errors**
```bash
export NVM_DIR="$HOME/.nvm" && . "$NVM_DIR/nvm.sh" && npm run build 2>&1 | grep -E "error|warning|✓"
```

- [ ] **Step 3: Commit**
```bash
git add resources/client/admin/AdminLayout.tsx
git commit -m "feat: BeMusic-style dark AdminLayout with lucide icons"
```

---

## Task 2: Dashboard with Real Stats

**Files:**
- Modify: `resources/client/admin/DashboardPage.tsx`

**API:** `GET /api/v1/dashboard/stats` → `{ data: { counts: { users, posts, events, sermons, prayer_requests, books, subscribers }, additional_stats: { pending_reviews, unread_contacts } } }`

- [ ] **Step 1: Rewrite DashboardPage.tsx**

```tsx
import { useQuery } from '@tanstack/react-query';
import { apiClient } from '@app/common/http/api-client';
import { Users, CalendarDays, Mic, BookOpen, HandHeart, Mail, FileText, Star } from 'lucide-react';

interface DashboardStats {
  counts: {
    users: number; posts: number; events: number; sermons: number;
    prayer_requests: number; books: number; subscribers: number;
  };
  additional_stats: { pending_reviews: number; unread_contacts: number; };
}

function StatCard({ label, value, icon: Icon, color }: { label: string; value: number | undefined; icon: React.ElementType; color: string }) {
  return (
    <div className="bg-[#161920] border border-white/5 rounded-xl p-5">
      <div className={`w-8 h-8 rounded-lg ${color} flex items-center justify-center mb-3`}>
        <Icon size={16} className="text-white" />
      </div>
      <p className="text-2xl font-bold text-white">{value ?? '—'}</p>
      <p className="text-sm text-gray-400 mt-0.5">{label}</p>
    </div>
  );
}

export function DashboardPage() {
  const { data, isLoading } = useQuery({
    queryKey: ['dashboard-stats'],
    queryFn: () => apiClient.get<{ data: DashboardStats }>('dashboard/stats').then(r => r.data.data),
  });

  const c = data?.counts;
  const a = data?.additional_stats;

  return (
    <div>
      <h1 className="text-xl font-bold text-white mb-6">Dashboard</h1>

      {isLoading ? (
        <div className="text-gray-400 text-sm">Loading stats…</div>
      ) : (
        <>
          <div className="grid grid-cols-2 md:grid-cols-4 gap-4 mb-6">
            <StatCard label="Members"       value={c?.users}           icon={Users}       color="bg-indigo-600" />
            <StatCard label="Sermons"        value={c?.sermons}         icon={Mic}         color="bg-purple-600" />
            <StatCard label="Events"         value={c?.events}          icon={CalendarDays} color="bg-blue-600" />
            <StatCard label="Prayer Requests" value={c?.prayer_requests} icon={HandHeart}  color="bg-rose-600" />
            <StatCard label="Blog Posts"     value={c?.posts}           icon={FileText}    color="bg-amber-600" />
            <StatCard label="Library Books"  value={c?.books}           icon={BookOpen}    color="bg-teal-600" />
            <StatCard label="Subscribers"    value={c?.subscribers}     icon={Mail}        color="bg-cyan-600" />
            <StatCard label="Pending Reviews" value={a?.pending_reviews} icon={Star}       color="bg-orange-600" />
          </div>

          {a && a.unread_contacts > 0 && (
            <div className="bg-amber-600/10 border border-amber-500/20 rounded-xl p-4 text-sm text-amber-400">
              📬 You have <strong>{a.unread_contacts}</strong> unread contact message{a.unread_contacts !== 1 ? 's' : ''}.
            </div>
          )}
        </>
      )}
    </div>
  );
}
```

- [ ] **Step 2: Build + commit**
```bash
git add resources/client/admin/DashboardPage.tsx
git commit -m "feat: dashboard with real stats from GET /api/v1/dashboard/stats"
```

---

## Task 3: Users Page

**Files:**
- Create: `resources/client/admin/UsersPage.tsx`
- Modify: `resources/client/app-router.tsx`

**API:**
- `GET /api/v1/users?search=&page=&per_page=15` → paginated user list
- `PUT /api/v1/users/{id}` → update user (roles, name, etc.)

- [ ] **Step 1: Create UsersPage.tsx**

```tsx
import { useState } from 'react';
import { useQuery } from '@tanstack/react-query';
import { apiClient } from '@app/common/http/api-client';
import { Search, UserCircle } from 'lucide-react';

interface User { id: number; name: string; email: string; avatar: string | null; roles: string[]; created_at: string; }
interface PaginatedUsers { data: User[]; total: number; current_page: number; last_page: number; }

export function UsersPage() {
  const [search, setSearch] = useState('');
  const [page, setPage] = useState(1);

  const { data, isLoading } = useQuery({
    queryKey: ['admin-users', search, page],
    queryFn: () =>
      apiClient.get<PaginatedUsers>('users', { params: { search, page, per_page: 15 } }).then(r => r.data),
    placeholderData: prev => prev,
  });

  return (
    <div>
      <div className="flex items-center justify-between mb-6">
        <h1 className="text-xl font-bold text-white">Users</h1>
        <div className="relative">
          <Search size={14} className="absolute left-3 top-1/2 -translate-y-1/2 text-gray-500" />
          <input
            value={search}
            onChange={e => { setSearch(e.target.value); setPage(1); }}
            placeholder="Search users…"
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
              <tr><td colSpan={4} className="px-4 py-8 text-center text-gray-500">Loading…</td></tr>
            ) : data?.data.map(user => (
              <tr key={user.id} className="border-b border-white/5 hover:bg-white/3 transition-colors">
                <td className="px-4 py-3">
                  <div className="flex items-center gap-2">
                    {user.avatar ? (
                      <img src={user.avatar} className="w-7 h-7 rounded-full object-cover" alt="" />
                    ) : (
                      <div className="w-7 h-7 rounded-full bg-indigo-600 flex items-center justify-center text-xs font-bold">
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
                      <span key={r} className="px-1.5 py-0.5 bg-indigo-600/20 text-indigo-400 text-xs rounded">
                        {r}
                      </span>
                    ))}
                  </div>
                </td>
                <td className="px-4 py-3 text-gray-500 text-xs">
                  {new Date(user.created_at).toLocaleDateString()}
                </td>
              </tr>
            ))}
          </tbody>
        </table>

        {/* Pagination */}
        {data && data.last_page > 1 && (
          <div className="flex items-center justify-between px-4 py-3 border-t border-white/5">
            <span className="text-xs text-gray-500">{data.total} total users</span>
            <div className="flex gap-2">
              <button
                disabled={page <= 1}
                onClick={() => setPage(p => p - 1)}
                className="px-3 py-1 text-xs bg-white/5 hover:bg-white/10 rounded disabled:opacity-30 text-white"
              >Prev</button>
              <span className="px-3 py-1 text-xs text-gray-400">{page} / {data.last_page}</span>
              <button
                disabled={page >= data.last_page}
                onClick={() => setPage(p => p + 1)}
                className="px-3 py-1 text-xs bg-white/5 hover:bg-white/10 rounded disabled:opacity-30 text-white"
              >Next</button>
            </div>
          </div>
        )}
      </div>
    </div>
  );
}
```

- [ ] **Step 2: Add route to app-router.tsx**

Add lazy import:
```tsx
const UsersPage = lazy(() => import('./admin/UsersPage').then(m => ({ default: m.UsersPage })));
```

Add under admin routes:
```tsx
<Route path="users" element={<UsersPage />} />
```

- [ ] **Step 3: Build + commit**
```bash
git add resources/client/admin/UsersPage.tsx resources/client/app-router.tsx
git commit -m "feat: Users admin page with search + pagination from GET /api/v1/users"
```

---

## Task 4: Roles Page

**Files:**
- Create: `resources/client/admin/RolesPage.tsx`
- Modify: `resources/client/app-router.tsx`

**API:** `GET /api/v1/roles` → `{ data: [{ id, name, slug, permissions_count }] }`

- [ ] **Step 1: Create RolesPage.tsx**

```tsx
import { useQuery } from '@tanstack/react-query';
import { apiClient } from '@app/common/http/api-client';
import { Shield } from 'lucide-react';

interface Role { id: number; name: string; slug: string; permissions?: { id: number }[]; }

export function RolesPage() {
  const { data, isLoading } = useQuery({
    queryKey: ['admin-roles'],
    queryFn: () => apiClient.get<{ data: Role[] }>('roles').then(r => r.data.data),
  });

  return (
    <div>
      <h1 className="text-xl font-bold text-white mb-6">Roles</h1>

      <div className="bg-[#161920] border border-white/5 rounded-xl overflow-hidden">
        <table className="w-full text-sm">
          <thead>
            <tr className="border-b border-white/5">
              <th className="text-left px-4 py-3 text-gray-400 font-medium">Role</th>
              <th className="text-left px-4 py-3 text-gray-400 font-medium">Slug</th>
              <th className="text-left px-4 py-3 text-gray-400 font-medium">Permissions</th>
            </tr>
          </thead>
          <tbody>
            {isLoading ? (
              <tr><td colSpan={3} className="px-4 py-8 text-center text-gray-500">Loading…</td></tr>
            ) : data?.map(role => (
              <tr key={role.id} className="border-b border-white/5 hover:bg-white/3 transition-colors">
                <td className="px-4 py-3">
                  <div className="flex items-center gap-2">
                    <Shield size={14} className="text-indigo-400" />
                    <span className="text-white font-medium">{role.name}</span>
                  </div>
                </td>
                <td className="px-4 py-3">
                  <code className="text-xs bg-white/5 px-2 py-0.5 rounded text-gray-300">{role.slug}</code>
                </td>
                <td className="px-4 py-3">
                  <span className="text-sm text-gray-400">{role.permissions?.length ?? 0} permissions</span>
                </td>
              </tr>
            ))}
          </tbody>
        </table>
      </div>
    </div>
  );
}
```

- [ ] **Step 2: Add route + lazy import to app-router.tsx**
- [ ] **Step 3: Build + commit**
```bash
git commit -m "feat: Roles admin page from GET /api/v1/roles"
```

---

## Task 5: BeMusic-Style Settings with Sub-Navigation

**Files:**
- Modify: `resources/client/admin/SettingsPage.tsx` — two-panel layout
- Create: `resources/client/admin/settings/GeneralSettingsPanel.tsx`
- Create: `resources/client/admin/settings/EmailSettingsPanel.tsx`
- Create: `resources/client/admin/settings/NotificationsSettingsPanel.tsx`
- Create: `resources/client/admin/settings/AppearanceSettingsPanel.tsx`

**API:** `GET /api/v1/settings` → wide-column setting row; `PUT /api/v1/settings` → update

- [ ] **Step 1: Rewrite SettingsPage.tsx as two-panel container**

```tsx
import { useState } from 'react';
import { GeneralSettingsPanel } from './settings/GeneralSettingsPanel';
import { EmailSettingsPanel } from './settings/EmailSettingsPanel';
import { NotificationsSettingsPanel } from './settings/NotificationsSettingsPanel';
import { AppearanceSettingsPanel } from './settings/AppearanceSettingsPanel';

type SettingTab = 'general' | 'email' | 'notifications' | 'appearance';

const TABS: { id: SettingTab; label: string }[] = [
  { id: 'general',       label: 'General' },
  { id: 'appearance',    label: 'Appearance' },
  { id: 'email',         label: 'Email' },
  { id: 'notifications', label: 'Notifications' },
];

export function SettingsPage() {
  const [tab, setTab] = useState<SettingTab>('general');

  return (
    <div className="flex gap-6 h-full">
      {/* Left sub-nav */}
      <aside className="w-48 flex-shrink-0">
        <h1 className="text-xl font-bold text-white mb-4">Settings</h1>
        <nav className="space-y-0.5">
          {TABS.map(t => (
            <button
              key={t.id}
              onClick={() => setTab(t.id)}
              className={`w-full text-left px-3 py-2 rounded-lg text-sm transition-colors ${
                tab === t.id
                  ? 'bg-white/10 text-white font-medium'
                  : 'text-gray-400 hover:text-white hover:bg-white/5'
              }`}
            >
              {t.label}
            </button>
          ))}
        </nav>
      </aside>

      {/* Right panel */}
      <div className="flex-1 min-w-0">
        {tab === 'general'       && <GeneralSettingsPanel />}
        {tab === 'email'         && <EmailSettingsPanel />}
        {tab === 'notifications' && <NotificationsSettingsPanel />}
        {tab === 'appearance'    && <AppearanceSettingsPanel />}
      </div>
    </div>
  );
}
```

- [ ] **Step 2: Create GeneralSettingsPanel.tsx**

```tsx
import { useState, useEffect } from 'react';
import { useQuery, useMutation, useQueryClient } from '@tanstack/react-query';
import { apiClient } from '@app/common/http/api-client';

interface ChurchSettings {
  church_name?: string; tagline?: string; email?: string; phone?: string;
  address?: string; city?: string; country?: string;
  facebook_url?: string; instagram_url?: string; youtube_url?: string;
  pastor_name?: string; service_times?: string;
}

function Field({ label, name, value, onChange, type = 'text' }: {
  label: string; name: string; value: string; onChange: (v: string) => void; type?: string;
}) {
  return (
    <div className="mb-5">
      <label className="block text-sm font-medium text-gray-300 mb-1.5">{label}</label>
      <input
        type={type}
        value={value}
        onChange={e => onChange(e.target.value)}
        className="w-full px-3 py-2.5 bg-[#0C0E12] border border-white/10 rounded-lg text-white text-sm placeholder-gray-600 focus:outline-none focus:border-indigo-500"
      />
    </div>
  );
}

export function GeneralSettingsPanel() {
  const qc = useQueryClient();
  const [form, setForm] = useState<ChurchSettings>({});
  const [saved, setSaved] = useState(false);

  const { data, isLoading } = useQuery({
    queryKey: ['settings'],
    queryFn: () => apiClient.get<{ data: ChurchSettings }>('settings').then(r => r.data.data ?? {}),
  });

  useEffect(() => { if (data) setForm(data); }, [data]);

  const mutation = useMutation({
    mutationFn: (values: ChurchSettings) => apiClient.put('settings', values),
    onSuccess: () => { qc.invalidateQueries({ queryKey: ['settings'] }); setSaved(true); setTimeout(() => setSaved(false), 2500); },
  });

  const set = (key: keyof ChurchSettings) => (val: string) => setForm(f => ({ ...f, [key]: val }));

  if (isLoading) return <div className="text-gray-400 text-sm">Loading…</div>;

  return (
    <div className="max-w-2xl">
      <div className="flex items-center justify-between mb-6">
        <h2 className="text-lg font-semibold text-white">General</h2>
        <button
          onClick={() => mutation.mutate(form)}
          disabled={mutation.isPending}
          className="px-4 py-2 bg-white text-gray-900 text-sm font-semibold rounded-lg hover:bg-gray-100 disabled:opacity-50 transition-colors"
        >
          {mutation.isPending ? 'Saving…' : saved ? '✓ Saved' : 'Save changes'}
        </button>
      </div>

      <div className="bg-[#161920] border border-white/5 rounded-xl p-6">
        <Field label="Church Name"  name="church_name"  value={form.church_name ?? ''}  onChange={set('church_name')} />
        <Field label="Tagline"      name="tagline"      value={form.tagline ?? ''}       onChange={set('tagline')} />
        <Field label="Email"        name="email"        value={form.email ?? ''}         onChange={set('email')} type="email" />
        <Field label="Phone"        name="phone"        value={form.phone ?? ''}         onChange={set('phone')} />
        <Field label="Pastor Name"  name="pastor_name"  value={form.pastor_name ?? ''}   onChange={set('pastor_name')} />
        <Field label="Address"      name="address"      value={form.address ?? ''}       onChange={set('address')} />
        <Field label="City"         name="city"         value={form.city ?? ''}          onChange={set('city')} />
        <Field label="Country"      name="country"      value={form.country ?? ''}       onChange={set('country')} />
        <Field label="Service Times" name="service_times" value={form.service_times ?? ''} onChange={set('service_times')} />
      </div>

      <div className="bg-[#161920] border border-white/5 rounded-xl p-6 mt-4">
        <h3 className="text-sm font-semibold text-gray-300 mb-4">Social Links</h3>
        <Field label="Facebook URL"  name="facebook_url"  value={form.facebook_url ?? ''}  onChange={set('facebook_url')} type="url" />
        <Field label="Instagram URL" name="instagram_url" value={form.instagram_url ?? ''} onChange={set('instagram_url')} type="url" />
        <Field label="YouTube URL"   name="youtube_url"   value={form.youtube_url ?? ''}   onChange={set('youtube_url')} type="url" />
      </div>
    </div>
  );
}
```

- [ ] **Step 3: Create EmailSettingsPanel.tsx**

```tsx
import { useState, useEffect } from 'react';
import { useQuery, useMutation } from '@tanstack/react-query';
import { apiClient } from '@app/common/http/api-client';

interface EmailSettings {
  mail_provider?: string; smtp_host?: string; smtp_port?: number;
  smtp_username?: string; smtp_encryption?: string;
  mail_from_address?: string; mail_from_name?: string;
}

export function EmailSettingsPanel() {
  const [form, setForm] = useState<EmailSettings>({});
  const [saved, setSaved] = useState(false);

  const { data, isLoading } = useQuery({
    queryKey: ['settings-email'],
    queryFn: () => apiClient.get<{ data: EmailSettings }>('settings/email').then(r => r.data.data ?? {}),
  });

  useEffect(() => { if (data) setForm(data); }, [data]);

  const mutation = useMutation({
    mutationFn: (values: EmailSettings) => apiClient.put('settings/email', values),
    onSuccess: () => { setSaved(true); setTimeout(() => setSaved(false), 2500); },
  });

  const set = (key: keyof EmailSettings) => (val: string) => setForm(f => ({ ...f, [key]: val }));

  if (isLoading) return <div className="text-gray-400 text-sm">Loading…</div>;

  return (
    <div className="max-w-2xl">
      <div className="flex items-center justify-between mb-6">
        <h2 className="text-lg font-semibold text-white">Email (SMTP)</h2>
        <button
          onClick={() => mutation.mutate(form)}
          disabled={mutation.isPending}
          className="px-4 py-2 bg-white text-gray-900 text-sm font-semibold rounded-lg hover:bg-gray-100 disabled:opacity-50"
        >
          {mutation.isPending ? 'Saving…' : saved ? '✓ Saved' : 'Save changes'}
        </button>
      </div>
      <div className="bg-[#161920] border border-white/5 rounded-xl p-6 space-y-4">
        {[
          { key: 'smtp_host',         label: 'SMTP Host' },
          { key: 'smtp_port',         label: 'SMTP Port' },
          { key: 'smtp_username',     label: 'SMTP Username' },
          { key: 'smtp_encryption',   label: 'Encryption (tls/ssl)' },
          { key: 'mail_from_address', label: 'From Address' },
          { key: 'mail_from_name',    label: 'From Name' },
        ].map(({ key, label }) => (
          <div key={key}>
            <label className="block text-sm font-medium text-gray-300 mb-1.5">{label}</label>
            <input
              value={(form as any)[key] ?? ''}
              onChange={e => set(key as keyof EmailSettings)(e.target.value)}
              className="w-full px-3 py-2.5 bg-[#0C0E12] border border-white/10 rounded-lg text-white text-sm focus:outline-none focus:border-indigo-500"
            />
          </div>
        ))}
      </div>
    </div>
  );
}
```

- [ ] **Step 4: Create NotificationsSettingsPanel.tsx** — loads `GET /api/v1/settings/notifications` (Foundation key-value), saves via `PUT /api/v1/v1/settings` with keys `notifications.onesignal_app_id`, `notifications.twilio_sid`, etc. (see SettingsSeeder for keys)

- [ ] **Step 5: Create AppearanceSettingsPanel.tsx** — shows church logo, favicon upload fields; `PUT /api/v1/settings` for logo URLs

- [ ] **Step 6: Build + commit**
```bash
git add resources/client/admin/SettingsPage.tsx resources/client/admin/settings/
git commit -m "feat: BeMusic-style settings with sub-nav and live API save"
```

---

## Task 6: Mobile Bottom Navigation

**Files:**
- Create: `resources/client/layouts/MobileLayout.tsx`
- Modify: `resources/client/app-router.tsx`

**Design:** Fixed bottom bar 56px tall, icons + labels, 4 items: Home, Search, Feed, Account. Only visible on `sm:hidden` screens. Wraps all public/member routes.

- [ ] **Step 1: Create MobileLayout.tsx**

```tsx
import { NavLink, Outlet } from 'react-router';
import { Home, Search, Rss, User } from 'lucide-react';

const mobileNav = [
  { label: 'Home',    path: '/',          icon: Home,   exact: true },
  { label: 'Search',  path: '/sermons',   icon: Search  },
  { label: 'Feed',    path: '/feed',      icon: Rss     },
  { label: 'Account', path: '/login',     icon: User    },
];

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
            end={item.exact}
            className={({ isActive }) =>
              `flex-1 flex flex-col items-center justify-center py-2 text-[10px] transition-colors ${
                isActive ? 'text-white' : 'text-gray-500'
              }`
            }
          >
            {({ isActive }) => (
              <>
                <item.icon size={20} className={isActive ? 'text-white' : 'text-gray-500'} />
                <span className="mt-1">{item.label}</span>
              </>
            )}
          </NavLink>
        ))}
      </nav>
    </div>
  );
}
```

- [ ] **Step 2: Wrap public+member routes in MobileLayout in app-router.tsx**

```tsx
const MobileLayout = lazy(() => import('./layouts/MobileLayout').then(m => ({ default: m.MobileLayout })));

// In routes:
<Route element={<MobileLayout />}>
  <Route path="/" element={<HomePage />} />
  <Route path="/login" element={<LoginPage />} />
  <Route element={<RequireAuth />}>
    <Route path="/feed" element={<NewsfeedPage />} />
    {/* ... all other public routes ... */}
  </Route>
</Route>
```

- [ ] **Step 3: Build + commit**
```bash
git add resources/client/layouts/MobileLayout.tsx resources/client/app-router.tsx
git commit -m "feat: mobile bottom nav bar for public and member routes"
```

---

## Task 7: Wire Remaining Admin Nav Links

**Files:**
- Modify: `resources/client/app-router.tsx`

The admin sidebar now shows Sermons, Events, Blog, Library, Groups, Prayers, Churches links pointing to `/admin/sermons` etc. These currently have no admin route — they should redirect to the existing public pages or to a future admin CRUD page.

- [ ] **Step 1: For now, redirect `/admin/X` → `/X` for content pages**

Add redirect routes:
```tsx
import { Navigate } from 'react-router';

// Inside admin Route group:
<Route path="sermons"  element={<Navigate to="/sermons" replace />} />
<Route path="events"   element={<Navigate to="/events" replace />} />
<Route path="blog"     element={<Navigate to="/blog" replace />} />
<Route path="library"  element={<Navigate to="/library" replace />} />
<Route path="groups"   element={<Navigate to="/groups" replace />} />
<Route path="prayers"  element={<Navigate to="/prayers" replace />} />
<Route path="churches" element={<Navigate to="/churches" replace />} />
<Route path="chat"     element={<Navigate to="/chat" replace />} />
```

- [ ] **Step 2: Build + commit**
```bash
git commit -m "feat: wire admin sidebar content links to existing public pages"
```

---

## Task 8: Final Build + Push

- [ ] **Step 1: Full build**
```bash
export NVM_DIR="$HOME/.nvm" && . "$NVM_DIR/nvm.sh" && npm run build 2>&1 | tail -5
```

- [ ] **Step 2: Commit + push**
```bash
git add -A
git commit -m "chore: final build for BeMusic admin UI plan"
git push origin v5-foundation
git push origin v5-foundation:main --force
```

- [ ] **Step 3: Update shared hosting**
```
cPanel Terminal: git fetch origin && git reset --hard origin/main
FTP: upload public/build/ to public_html/public/build/
cPanel Terminal: php artisan config:clear && php artisan cache:clear
```
