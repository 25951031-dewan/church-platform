import { ComponentType } from 'react';
import { PostFeedWidget } from './PostFeedWidget';
import { DailyVerseWidget } from './DailyVerseWidget';
import { AnnouncementsWidget } from './AnnouncementsWidget';
import { EventsWidget } from './EventsWidget';
import { PrayerRequestsWidget } from './PrayerRequestsWidget';
import { SermonsWidget } from './SermonsWidget';

export interface WidgetProps {
  config?: Record<string, any>;
  [key: string]: any;
}

export const widgetRegistry: Record<string, ComponentType<WidgetProps>> = {
  'post_feed': PostFeedWidget,
  'daily_verse': DailyVerseWidget,
  'announcements': AnnouncementsWidget,
  'events': EventsWidget,
  'prayer_requests': PrayerRequestsWidget,
  'sermons': SermonsWidget,
};

export function getWidget(widgetKey: string): ComponentType<WidgetProps> | null {
  return widgetRegistry[widgetKey] || null;
}

export function isValidWidget(widgetKey: string): boolean {
  return widgetKey in widgetRegistry;
}

export function getAvailableWidgets(): string[] {
  return Object.keys(widgetRegistry);
}