import {NotificationCenter} from '../components/NotificationCenter';
import {NotificationPreferencesForm} from '../components/NotificationPreferencesForm';

export function NotificationsPage() {
  return (
    <div className="mx-auto max-w-4xl space-y-6 px-4 py-6">
      <h1 className="text-2xl font-bold text-gray-900 dark:text-white">Notifications</h1>

      <section className="rounded-xl border border-gray-200 bg-white p-4 dark:border-gray-700 dark:bg-gray-900">
        <h2 className="mb-3 text-lg font-semibold text-gray-900 dark:text-white">Notification Center</h2>
        <NotificationCenter />
      </section>

      <section className="rounded-xl border border-gray-200 bg-white p-4 dark:border-gray-700 dark:bg-gray-900">
        <h2 className="mb-3 text-lg font-semibold text-gray-900 dark:text-white">Preferences</h2>
        <NotificationPreferencesForm />
      </section>
    </div>
  );
}
