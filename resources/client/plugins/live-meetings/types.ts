export type MeetingPlatform = 'zoom' | 'google_meet' | 'youtube' | 'other';
export type RecurrenceRule = 'weekly' | 'biweekly' | 'monthly';

export interface Meeting {
  id: number;
  title: string;
  description: string | null;
  meeting_url: string;
  meeting_id?: string | null;
  meeting_password?: string | null;
  platform: MeetingPlatform;
  church_id: number | null;
  event_id: number | null;
  host_id: number;
  host: {id: number; name: string; avatar: string | null} | null;
  starts_at: string;
  ends_at: string;
  timezone: string;
  is_recurring: boolean;
  recurrence_rule: RecurrenceRule | null;
  cover_image: string | null;
  is_active: boolean;
  is_live: boolean;
  max_participants?: number | null;
  requires_registration?: boolean;
}
