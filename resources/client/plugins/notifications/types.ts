export type NotificationChannel = 'database' | 'mail' | 'email' | 'sms' | 'push';

export interface AppNotification {
  id: string;
  type: string;
  data: {
    type: string;
    title: string;
    body: string;
    url?: string | null;
  };
  read_at: string | null;
  created_at: string;
}

export interface NotificationPreferences {
  id: number;
  notification_type: string;
  push_enabled: boolean;
  email_enabled: boolean;
  sms_enabled: boolean;
  in_app_enabled: boolean;
}
