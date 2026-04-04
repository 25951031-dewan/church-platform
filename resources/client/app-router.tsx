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
const PrayerWallPage = lazy(() =>
  import('./plugins/prayer/pages/PrayerWallPage').then(m => ({default: m.PrayerWallPage}))
);
const PrayerDetailPage = lazy(() =>
  import('./plugins/prayer/pages/PrayerDetailPage').then(m => ({default: m.PrayerDetailPage}))
);
const PrayerSubmitPage = lazy(() =>
  import('./plugins/prayer/pages/PrayerSubmitPage').then(m => ({default: m.PrayerSubmitPage}))
);
const ChurchDirectoryPage = lazy(() => import('./plugins/church-builder/pages/ChurchDirectoryPage').then(m => ({default: m.ChurchDirectoryPage})));
const ChurchProfilePage = lazy(() => import('./plugins/church-builder/pages/ChurchProfilePage').then(m => ({default: m.ChurchProfilePage})));
const LibraryCatalogPage = lazy(() => import('./plugins/library/pages/LibraryCatalogPage').then(m => ({default: m.LibraryCatalogPage})));
const BookDetailPage = lazy(() => import('./plugins/library/pages/BookDetailPage').then(m => ({default: m.BookDetailPage})));
const BlogListPage = lazy(() => import('./plugins/blog/pages/BlogListPage').then(m => ({default: m.BlogListPage})));
const ArticleDetailPage = lazy(() => import('./plugins/blog/pages/ArticleDetailPage').then(m => ({default: m.ArticleDetailPage})));
const ArticleEditorPage = lazy(() => import('./plugins/blog/pages/ArticleEditorPage').then(m => ({default: m.ArticleEditorPage})));
const MeetingsPage = lazy(() =>
  import('./plugins/live-meetings/pages/MeetingsPage').then(m => ({default: m.MeetingsPage}))
);
const MeetingDetailPage = lazy(() =>
  import('./plugins/live-meetings/pages/MeetingDetailPage').then(m => ({default: m.MeetingDetailPage}))
);
const ChatPage = lazy(() => import('./plugins/chat/pages/ChatPage').then(m => ({default: m.ChatPage})));
const NotificationsPage = lazy(() =>
  import('./plugins/notifications/pages/NotificationsPage').then(m => ({default: m.NotificationsPage}))
);
const NotificationLogsPage = lazy(() =>
  import('./plugins/notifications/admin/NotificationLogsPage').then(m => ({default: m.NotificationLogsPage}))
);
const NotificationTemplatesPage = lazy(() =>
  import('./plugins/notifications/admin/NotificationTemplatesPage').then(m => ({default: m.NotificationTemplatesPage}))
);
const MeetingManagerPage = lazy(() =>
  import('./plugins/live-meetings/admin/MeetingManagerPage').then(m => ({default: m.MeetingManagerPage}))
);
const NotificationSettingsPage = lazy(() =>
  import('./plugins/notifications/admin/NotificationSettingsPage').then(m => ({default: m.NotificationSettingsPage}))
);
const LiveMeetingSettingsPage = lazy(() =>
  import('./plugins/live-meetings/admin/LiveMeetingSettingsPage').then(m => ({default: m.LiveMeetingSettingsPage}))
);
const SystemPage = lazy(() => import('./admin/SystemPage').then(m => ({ default: m.SystemPage })));
const SettingsPage = lazy(() => import('./admin/SettingsPage').then(m => ({ default: m.SettingsPage })));
const UsersPage = lazy(() => import('./admin/UsersPage').then(m => ({ default: m.UsersPage })));
const RolesPage = lazy(() => import('./admin/RolesPage').then(m => ({ default: m.RolesPage })));
const HomePage = lazy(() => import('./pages/HomePage').then(m => ({ default: m.HomePage })));

function Loading() {
  return <div className="flex items-center justify-center h-screen">Loading...</div>;
}

export function AppRouter() {
  return (
    <Suspense fallback={<Loading />}>
      <Routes>
        {/* Public */}
        <Route path="/" element={<HomePage />} />
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
          <Route path="/prayers" element={<PrayerWallPage />} />
          <Route path="/prayers/submit" element={<PrayerSubmitPage />} />
          <Route path="/prayers/:prayerId" element={<PrayerDetailPage />} />
          <Route path="/churches" element={<ChurchDirectoryPage />} />
          <Route path="/churches/:churchId" element={<ChurchProfilePage />} />
          <Route path="/library" element={<LibraryCatalogPage />} />
          <Route path="/library/:bookId" element={<BookDetailPage />} />
          <Route path="/blog" element={<BlogListPage />} />
          <Route path="/blog/new" element={<ArticleEditorPage />} />
          <Route path="/blog/:slug" element={<ArticleDetailPage />} />
          <Route path="/blog/:slug/edit" element={<ArticleEditorPage />} />
          <Route path="/meetings" element={<MeetingsPage />} />
          <Route path="/meetings/:meetingId" element={<MeetingDetailPage />} />
          <Route path="/chat" element={<ChatPage />} />
          <Route path="/notifications" element={<NotificationsPage />} />

          {/* Admin routes */}
          <Route element={<RequirePermission permission="admin.access" />}>
            <Route path="/admin" element={<AdminLayout />}>
              <Route index element={<DashboardPage />} />
              <Route path="notification-logs" element={<NotificationLogsPage />} />
              <Route path="notification-templates" element={<NotificationTemplatesPage />} />
              <Route path="meetings" element={<MeetingManagerPage />} />
              <Route path="settings" element={<SettingsPage />} />
              <Route path="settings/notifications" element={<NotificationSettingsPage />} />
              <Route path="settings/live-meetings" element={<LiveMeetingSettingsPage />} />
              <Route path="system" element={<SystemPage />} />
              <Route path="users" element={<UsersPage />} />
              <Route path="roles" element={<RolesPage />} />
            </Route>
          </Route>
        </Route>
      </Routes>
    </Suspense>
  );
}
