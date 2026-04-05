import { Link, useNavigate } from 'react-router';
import { useBootstrapStore } from '@app/common/core/bootstrap-data';
import { useEnabledPlugins } from '@app/common/hooks/use-enabled-plugins';
import { useEffect } from 'react';

export function HomePage() {
  const { user } = useBootstrapStore();
  const enabledPlugins = useEnabledPlugins();
  const navigate = useNavigate();

  useEffect(() => {
    // Redirect authenticated users to main feed if timeline plugin is enabled
    if (user && enabledPlugins.has('timeline')) {
      navigate('/feed', { replace: true });
    }
  }, [user, enabledPlugins, navigate]);

  return (
    <div className="min-h-screen bg-gradient-to-br from-indigo-50 via-white to-purple-50 dark:from-gray-900 dark:via-gray-900 dark:to-gray-800">
      {/* Nav */}
      <header className="border-b border-gray-200 dark:border-gray-800 bg-white/80 dark:bg-gray-900/80 backdrop-blur sticky top-0 z-10">
        <div className="max-w-6xl mx-auto px-4 h-16 flex items-center justify-between">
          <div className="flex items-center gap-2">
            <span className="text-2xl">⛪</span>
            <span className="font-bold text-gray-900 dark:text-white text-lg">Church Platform</span>
          </div>
          <div className="flex items-center gap-3">
            {user ? (
              <Link
                to="/admin"
                className="px-4 py-2 bg-indigo-600 hover:bg-indigo-700 text-white text-sm font-medium rounded-lg transition-colors"
              >
                Go to Admin
              </Link>
            ) : (
              <>
                <Link
                  to="/login"
                  className="px-4 py-2 text-gray-600 dark:text-gray-300 hover:text-gray-900 dark:hover:text-white text-sm font-medium transition-colors"
                >
                  Sign In
                </Link>
                <Link
                  to="/login"
                  className="px-4 py-2 bg-indigo-600 hover:bg-indigo-700 text-white text-sm font-medium rounded-lg transition-colors"
                >
                  Get Started
                </Link>
              </>
            )}
          </div>
        </div>
      </header>

      {/* Hero */}
      <section className="max-w-6xl mx-auto px-4 pt-24 pb-20 text-center">
        <div className="inline-flex items-center gap-2 bg-indigo-50 dark:bg-indigo-900/30 text-indigo-700 dark:text-indigo-300 text-sm font-medium px-4 py-1.5 rounded-full mb-6">
          <span className="w-2 h-2 bg-indigo-500 rounded-full"></span>
          Community Platform for Churches
        </div>
        <h1 className="text-4xl sm:text-5xl font-bold text-gray-900 dark:text-white mb-6 leading-tight">
          Connect. Grow. Worship Together.
        </h1>
        <p className="text-lg text-gray-500 dark:text-gray-400 max-w-2xl mx-auto mb-10">
          A complete church community platform — sermons, events, prayer, groups, live meetings and more, all in one place.
        </p>
        <div className="flex items-center justify-center gap-4 flex-wrap">
          <Link
            to="/login"
            className="px-6 py-3 bg-indigo-600 hover:bg-indigo-700 text-white font-semibold rounded-xl shadow-lg shadow-indigo-200 dark:shadow-indigo-900/30 transition-all hover:-translate-y-0.5"
          >
            Sign In to Your Community
          </Link>
          <Link
            to="/sermons"
            className="px-6 py-3 bg-white dark:bg-gray-800 text-gray-700 dark:text-gray-200 font-semibold rounded-xl border border-gray-200 dark:border-gray-700 hover:border-indigo-300 transition-all hover:-translate-y-0.5"
          >
            Browse Sermons
          </Link>
        </div>
      </section>

      {/* Features */}
      <section className="max-w-6xl mx-auto px-4 pb-24">
        <div className="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-6">
          {[
            { icon: '🎙️', title: 'Sermons', desc: 'Stream and download sermon messages from your pastor.' },
            { icon: '📅', title: 'Events', desc: 'Stay up to date with church events and register to attend.' },
            { icon: '🙏', title: 'Prayer Wall', desc: 'Share prayer requests and pray for one another.' },
            { icon: '👥', title: 'Groups', desc: 'Join small groups and connect with fellow members.' },
            { icon: '📹', title: 'Live Meetings', desc: 'Join Zoom, Google Meet or YouTube live services.' },
            { icon: '📚', title: 'Library', desc: 'Download devotionals, books and study materials.' },
          ].map((f) => (
            <div
              key={f.title}
              className="bg-white dark:bg-gray-800 rounded-2xl p-6 border border-gray-100 dark:border-gray-700 shadow-sm hover:shadow-md transition-shadow"
            >
              <div className="text-3xl mb-3">{f.icon}</div>
              <h3 className="font-semibold text-gray-900 dark:text-white mb-1">{f.title}</h3>
              <p className="text-sm text-gray-500 dark:text-gray-400">{f.desc}</p>
            </div>
          ))}
        </div>
      </section>

      {/* Footer */}
      <footer className="border-t border-gray-200 dark:border-gray-800 py-6 text-center text-sm text-gray-400">
        © {new Date().getFullYear()} Church Platform. All rights reserved.
      </footer>
    </div>
  );
}
