import { Routes, Route } from 'react-router';
import { lazy, Suspense } from 'react';
import { RequireAuth, RequirePermission } from './common/auth/auth-guards';

const AdminLayout = lazy(() => import('./admin/AdminLayout').then((m) => ({ default: m.AdminLayout })));
const DashboardPage = lazy(() => import('./admin/DashboardPage').then((m) => ({ default: m.DashboardPage })));
const LoginPage = lazy(() => import('./auth/LoginPage').then((m) => ({ default: m.LoginPage })));
const NewsfeedPage = lazy(() =>
  import('./plugins/timeline/pages/NewsfeedPage').then((m) => ({ default: m.NewsfeedPage }))
);

function Loading() {
  return <div className="flex items-center justify-center h-screen">Loading...</div>;
}

export function AppRouter() {
  return (
    <Suspense fallback={<Loading />}>
      <Routes>
        {/* Public */}
        <Route path="/" element={<div className="p-8 text-2xl">Church Platform v5</div>} />
        <Route path="/login" element={<LoginPage />} />

        {/* Protected routes */}
        <Route element={<RequireAuth />}>
          {/* Member-accessible routes */}
          <Route path="/feed" element={<NewsfeedPage />} />

          {/* Admin routes */}
          <Route element={<RequirePermission permission="admin.access" />}>
            <Route path="/admin" element={<AdminLayout />}>
              <Route index element={<DashboardPage />} />
              {/* More admin routes added in subsequent tasks */}
            </Route>
          </Route>
        </Route>
      </Routes>
    </Suspense>
  );
}
