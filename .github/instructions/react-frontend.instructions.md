---
applyTo: "resources/client/**/*.tsx,resources/client/**/*.ts"
---

# React / TypeScript Rules — Church Platform

## Dark Palette (MANDATORY — html.dark is always active)
```
Page background : bg-[#0C0E12]
Card / panel    : bg-[#161920]
Borders         : border-white/5  or  border-white/10
Primary text    : text-white
Secondary text  : text-gray-400
Accent          : text-indigo-400
Inputs          : bg-[#161920] border border-white/10 text-white placeholder:text-gray-500
```
NEVER use: `bg-white`, `bg-gray-50`, `bg-gray-100`, `bg-gray-800`, `text-gray-900`

## Imports
```ts
import { apiClient } from '@app/common/http/api-client';    // ← Bearer token auto-attached
import { useAuth } from '@app/common/auth/use-auth';
import { useNotificationStore } from '@app/common/stores';  // NOT @common/stores
import { useBootstrapStore } from '@app/common/core/bootstrap-data';
```

## Data fetching
```tsx
const { data, isLoading } = useQuery({
  queryKey: ['resource', params],
  queryFn: () => apiClient.get('/resource').then(r => r.data),
});
```

## Route protection
```tsx
<Route element={<RequireAuth />}>         // member routes
<Route element={<RequirePermission permission="admin.access" />}>  // admin routes
```

## New admin pages
- File: `resources/client/admin/{FeatureName}Page.tsx`
- Add lazy import + route to `resources/client/app-router.tsx`
- Add nav item to `resources/client/admin/AdminLayout.tsx` navItems array
