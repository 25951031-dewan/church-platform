import { Link } from 'react-router';

interface SettingCategory {
  title: string;
  description: string;
  path: string;
  icon: string;
}

const categories: SettingCategory[] = [
  {
    title: 'General',
    description: 'Site name, timezone, language and basic settings.',
    path: '/admin/settings/general',
    icon: '⚙️',
  },
  {
    title: 'Authentication',
    description: 'Social login, registration settings and email verification.',
    path: '/admin/settings/auth',
    icon: '🔐',
  },
  {
    title: 'Email',
    description: 'SMTP configuration, sender name and email templates.',
    path: '/admin/settings/email',
    icon: '📧',
  },
  {
    title: 'Notifications',
    description: 'OneSignal push notifications, Twilio SMS and default channels.',
    path: '/admin/settings/notifications',
    icon: '🔔',
  },
  {
    title: 'Live Meetings',
    description: 'Zoom, Google Meet integration and default meeting platform.',
    path: '/admin/settings/live-meetings',
    icon: '📹',
  },
  {
    title: 'Appearance',
    description: 'Logos, colors, PWA icons and theme configuration.',
    path: '/admin/settings/appearance',
    icon: '🎨',
  },
  {
    title: 'System',
    description: 'Server status, git deploy and update management.',
    path: '/admin/system',
    icon: '🖥️',
  },
];

export function SettingsPage() {
  return (
    <div className="max-w-4xl">
      <div className="mb-8">
        <h1 className="text-2xl font-bold text-gray-900 dark:text-white">Settings</h1>
        <p className="text-gray-500 dark:text-gray-400 mt-1">Manage your platform configuration.</p>
      </div>

      <div className="grid grid-cols-1 sm:grid-cols-2 gap-4">
        {categories.map((cat) => (
          <Link
            key={cat.path}
            to={cat.path}
            className="group flex items-start gap-4 p-5 bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 hover:border-primary-400 hover:shadow-md transition-all duration-150"
          >
            <span className="text-2xl mt-0.5">{cat.icon}</span>
            <div>
              <p className="font-semibold text-gray-900 dark:text-white group-hover:text-primary-600 dark:group-hover:text-primary-400">
                {cat.title}
              </p>
              <p className="text-sm text-gray-500 dark:text-gray-400 mt-0.5">{cat.description}</p>
            </div>
          </Link>
        ))}
      </div>
    </div>
  );
}
