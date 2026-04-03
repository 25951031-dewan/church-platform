import {useMemo} from 'react';
import {useNotificationPreferences, useUpdatePreferences} from '../hooks';

export function NotificationPreferencesForm() {
  const {data = []} = useNotificationPreferences();
  const update = useUpdatePreferences();

  const rows = useMemo(() => data, [data]);

  const toggle = (id: number, field: 'push_enabled' | 'email_enabled' | 'sms_enabled' | 'in_app_enabled', value: boolean) => {
    const row = rows.find(r => r.id === id);
    if (!row) return;
    update.mutate([{...row, [field]: value}]);
  };

  return (
    <div className="space-y-3">
      {rows.map(pref => (
        <div key={pref.id} className="rounded-lg border border-gray-200 p-3 dark:border-gray-700">
          <p className="mb-2 text-sm font-semibold capitalize text-gray-900 dark:text-white">{pref.notification_type}</p>
          <div className="grid grid-cols-2 gap-2 text-xs sm:grid-cols-4">
            {[
              ['Push', 'push_enabled'],
              ['Email', 'email_enabled'],
              ['SMS', 'sms_enabled'],
              ['In-app', 'in_app_enabled'],
            ].map(([label, key]) => (
              <label key={key} className="flex items-center gap-2">
                <input
                  type="checkbox"
                  checked={(pref as any)[key]}
                  onChange={e => toggle(pref.id, key as any, e.target.checked)}
                />
                {label}
              </label>
            ))}
          </div>
        </div>
      ))}
    </div>
  );
}
