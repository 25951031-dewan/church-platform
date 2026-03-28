import { Routes, Route } from 'react-router';
import { lazy, Suspense } from 'react';
import { RequireAuth, RequirePermission } from './common/auth/auth-guards';

const AdminLayout = lazy(() => import('./admin/AdminLayout').then((m) => ({ default: m.AdminLayout })));
const DashboardPage = lazy(() => import('./admin/DashboardPage').then((m) => ({ default: m.DashboardPage })));
const LoginPage = lazy(() => import('./auth/LoginPage').then((m) => ({ default: m.LoginPage })));
const NewsfeedPage = lazy(() =>
  import('./plugins/timeline/pages/NewsfeedPage').then((m) => ({ default: m.NewsfeedPage }))
);
const GroupBrowserPage = lazy(() =>
  import('./plugins/groups/pages/GroupBrowserPage').then(m => ({default: m.GroupBrowserPage}))
);
const GroupDetailPage = lazy(() =>
  import('./plugins/groups/pages/GroupDetailPage').then(m => ({default: m.GroupDetailPage}))
);
const EventsPage = lazy(() =>
  import('./plugins/events/pages/EventsPage').then(m => ({default: m.EventsPage}))
);
const EventDetailPage = lazy(() =>
  import('./plugins/events/pages/EventDetailPage').then(m => ({default: m.EventDetailPage}))
);
const SermonsPage = lazy(() =>
  import('./plugins/sermons/pages/SermonsPage').then(m => ({default: m.SermonsPage}))
);
const SermonDetailPage = lazy(() =>
  import('./plugins/sermons/pages/SermonDetailPage').then(m => ({default: m.SermonDetailPage}))
);
const SermonSeriesPage = lazy(() =>
  import('./plugins/sermons/pages/SermonSeriesPage').then(m => ({default: m.SermonSeriesPage}))
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
          <Route path="/groups" element={<GroupBrowserPage />} />
          <Route path="/groups/:groupId" element={<GroupDetailPage />} />
          <Route path="/events" element={<EventsPage />} />
          <Route path="/events/:eventId" element={<EventDetailPage />} />
          <Route path="/sermons" element={<SermonsPage />} />
          <Route path="/sermons/:sermonId" element={<SermonDetailPage />} />
          <Route path="/sermon-series/:seriesId" element={<SermonSeriesPage />} />

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
