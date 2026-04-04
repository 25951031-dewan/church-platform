// Integration Example: Feed Page with Customizer
// Shows how to combine the customizer with the three-pane layout

import React, { useState, Suspense } from 'react';
import { Settings } from 'lucide-react';
import { useAuth } from '@/hooks/useAuth';
import { useChurch } from '@/hooks/useChurch';
import { Button } from '@/components/ui/button';
import { ThreePaneFeedLayout } from '@/components/layout/ThreePaneFeedLayout';
import { LeftSidebarContent } from '@/components/layout/LeftSidebarContent';
import { RightSidebarContent } from '@/components/layout/RightSidebarContent';
import FeedCustomizer from '@/components/customizer/FeedCustomizer';

// Lazy load the customizer for performance
const LazyFeedCustomizer = React.lazy(() => import('@/components/customizer/FeedCustomizer'));

const FeedPageWithCustomizer: React.FC = () => {
  const [showCustomizer, setShowCustomizer] = useState(false);
  const [customLayout, setCustomLayout] = useState(null);
  const { user } = useAuth();
  const { church } = useChurch();

  // Save layout configuration
  const handleSaveLayout = async (layout: any) => {
    try {
      const response = await fetch('/api/admin/feed-layout', {
        method: 'PUT',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify(layout)
      });
      
      if (response.ok) {
        setCustomLayout(layout);
        setShowCustomizer(false);
      }
    } catch (error) {
      console.error('Failed to save layout:', error);
      throw error;
    }
  };

  // Show customizer modal
  if (showCustomizer) {
    return (
      <div className="fixed inset-0 bg-black bg-opacity-50 z-50 flex items-center justify-center p-4">
        <div className="w-full h-full max-w-7xl max-h-full bg-white dark:bg-gray-900 rounded-lg overflow-hidden">
          <Suspense fallback={<CustomizerSkeleton />}>
            <LazyFeedCustomizer
              churchId={church?.id || ''}
              onSave={handleSaveLayout}
              onClose={() => setShowCustomizer(false)}
            />
          </Suspense>
        </div>
      </div>
    );
  }

  // Render main feed with customization button
  return (
    <div className="relative">
      {/* Customization Button */}
      {user?.hasPermission('manage_feed_layout') && (
        <div className="fixed bottom-6 right-6 z-40">
          <Button
            onClick={() => setShowCustomizer(true)}
            className="shadow-lg hover:shadow-xl transition-shadow"
            size="lg"
          >
            <Settings className="w-5 h-5 mr-2" />
            Customize Feed
          </Button>
        </div>
      )}

      {/* Main Three-Pane Layout */}
      <ThreePaneFeedLayout
        leftSidebar={
          <LeftSidebarContent
            user={user}
            church={church}
            navigationItems={getNavigationFromLayout(customLayout)}
          />
        }
        centerFeed={<CenterFeedFromLayout layout={customLayout} />}
        rightSidebar={
          <RightSidebarContent
            widgets={getWidgetsFromLayout(customLayout, 'right')}
          />
        }
        searchComponent={<FeedSearchBar />}
      />
    </div>
  );
};

// Center Feed Component based on layout configuration
const CenterFeedFromLayout: React.FC<{ layout: any }> = ({ layout }) => {
  // If no custom layout, use default components
  if (!layout?.center) {
    return <DefaultCenterFeed />;
  }

  return (
    <div className="space-y-6">
      {layout.center.map((widget: any, index: number) => (
        <Suspense key={`${widget.id}-${index}`} fallback={<WidgetSkeleton />}>
          <DynamicWidget widget={widget} />
        </Suspense>
      ))}
    </div>
  );
};

// Dynamic Widget Renderer
const DynamicWidget: React.FC<{ widget: any }> = ({ widget }) => {
  switch (widget.type) {
    case 'create_post_widget':
      return <CreatePostWidget config={widget.config} />;
    case 'daily_verse':
      return <DailyVerseWidget config={widget.config} />;
    case 'announcements':
      return <AnnouncementWidget config={widget.config} />;
    case 'timeline_posts':
      return <TimelinePostsWidget config={widget.config} />;
    case 'upcoming_events':
      return <UpcomingEventsWidget config={widget.config} />;
    case 'prayer_requests':
      return <PrayerRequestsWidget config={widget.config} />;
    case 'ministry_spotlight':
      return <MinistrySpotlightWidget config={widget.config} />;
    case 'trending_topics':
      return <TrendingTopicsWidget config={widget.config} />;
    default:
      return (
        <div className="p-4 bg-gray-100 dark:bg-gray-800 rounded-lg text-center text-gray-500">
          Unknown widget: {widget.type}
        </div>
      );
  }
};

// Default Center Feed (fallback)
const DefaultCenterFeed: React.FC = () => {
  return (
    <div className="space-y-6">
      <CreatePostWidget config={{ allowMedia: true }} />
      <DailyVerseWidget config={{ showShare: true, style: 'card' }} />
      <TimelinePostsWidget config={{ allowReactions: true, maxItems: 10 }} />
    </div>
  );
};

// Helper functions
const getNavigationFromLayout = (layout: any) => {
  if (!layout?.left) return undefined;
  
  // Extract navigation config from left sidebar widgets
  const navWidget = layout.left.find((w: any) => w.type === 'navigation_menu');
  return navWidget?.config?.items;
};

const getWidgetsFromLayout = (layout: any, pane: 'left' | 'right') => {
  if (!layout?.[pane]) return undefined;
  
  return layout[pane].map((widget: any) => ({
    type: widget.type,
    title: getWidgetTitle(widget.type),
    isEnabled: true,
    config: widget.config
  }));
};

const getWidgetTitle = (type: string): string => {
  const titles: Record<string, string> = {
    'upcoming_events': 'Upcoming Events',
    'prayer_requests': 'Prayer Requests',
    'trending_topics': 'Trending',
    'ministry_spotlight': 'Ministry Spotlight',
    'daily_verse': 'Daily Verse',
    'announcements': 'Announcements',
    'church_stats': 'Church Stats',
    'suggested_connections': 'People You May Know'
  };
  return titles[type] || type.replace(/_/g, ' ').replace(/\b\w/g, l => l.toUpperCase());
};

// Skeleton Components
const CustomizerSkeleton: React.FC = () => (
  <div className="h-full bg-gray-50 dark:bg-gray-900 animate-pulse">
    <div className="h-16 bg-gray-200 dark:bg-gray-800" />
    <div className="flex h-full">
      <div className="w-80 bg-gray-100 dark:bg-gray-800" />
      <div className="flex-1 bg-gray-50 dark:bg-gray-900" />
      <div className="w-80 bg-gray-100 dark:bg-gray-800" />
    </div>
  </div>
);

const WidgetSkeleton: React.FC = () => (
  <div className="h-32 bg-gray-100 dark:bg-gray-800 rounded-lg animate-pulse" />
);

// Widget Components (placeholders - would be actual implementations)
const CreatePostWidget: React.FC<{ config: any }> = ({ config }) => (
  <div className="p-4 bg-white dark:bg-gray-800 rounded-lg border">Create Post Widget</div>
);

const DailyVerseWidget: React.FC<{ config: any }> = ({ config }) => (
  <div className="p-4 bg-white dark:bg-gray-800 rounded-lg border">Daily Verse Widget</div>
);

const AnnouncementWidget: React.FC<{ config: any }> = ({ config }) => (
  <div className="p-4 bg-white dark:bg-gray-800 rounded-lg border">Announcements Widget</div>
);

const TimelinePostsWidget: React.FC<{ config: any }> = ({ config }) => (
  <div className="p-4 bg-white dark:bg-gray-800 rounded-lg border">Timeline Posts Widget</div>
);

const UpcomingEventsWidget: React.FC<{ config: any }> = ({ config }) => (
  <div className="p-4 bg-white dark:bg-gray-800 rounded-lg border">Events Widget</div>
);

const PrayerRequestsWidget: React.FC<{ config: any }> = ({ config }) => (
  <div className="p-4 bg-white dark:bg-gray-800 rounded-lg border">Prayer Requests Widget</div>
);

const MinistrySpotlightWidget: React.FC<{ config: any }> = ({ config }) => (
  <div className="p-4 bg-white dark:bg-gray-800 rounded-lg border">Ministry Spotlight Widget</div>
);

const TrendingTopicsWidget: React.FC<{ config: any }> = ({ config }) => (
  <div className="p-4 bg-white dark:bg-gray-800 rounded-lg border">Trending Topics Widget</div>
);

const FeedSearchBar: React.FC = () => (
  <div className="relative">
    <input
      type="text"
      placeholder="Search posts, people, events..."
      className="w-full pl-10 pr-4 py-3 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent bg-white dark:bg-gray-700 text-gray-900 dark:text-white"
    />
  </div>
);

export default FeedPageWithCustomizer;