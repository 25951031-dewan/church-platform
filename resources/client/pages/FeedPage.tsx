// Example Feed Page using Three-Pane Layout
// This demonstrates how to implement the complete layout system

import React, { Suspense } from 'react';
import { useAuth } from '@/hooks/useAuth';
import { useChurch } from '@/hooks/useChurch';
import { ThreePaneFeedLayout } from '@/components/layout/ThreePaneFeedLayout';
import { LeftSidebarContent } from '@/components/layout/LeftSidebarContent';
import { RightSidebarContent } from '@/components/layout/RightSidebarContent';
import { FeedSearchBar } from '@/components/search/FeedSearchBar';
import { FeedTimelinePost } from '@/components/feed/FeedTimelinePost';
import { CreatePostWidget } from '@/components/feed/CreatePostWidget';
import { DailyVerseWidget } from '@/components/feed/DailyVerseWidget';
import { AnnouncementWidget } from '@/components/feed/AnnouncementWidget';

const FeedPage: React.FC = () => {
  const { user } = useAuth();
  const { church } = useChurch();

  // Custom search component with feed-specific functionality
  const searchComponent = (
    <FeedSearchBar
      placeholder="Search posts, people, events..."
      onSearch={(query) => {
        // Handle search
        console.log('Searching for:', query);
      }}
      filters={['posts', 'people', 'events', 'prayers']}
    />
  );

  // Left sidebar content
  const leftSidebarContent = (
    <LeftSidebarContent
      user={user}
      church={church}
      navigationItems={[
        // Custom navigation for this church
        { label: 'Feed', href: '/feed', icon: Home },
        { label: 'Community', href: '/community', icon: Users, count: 12 },
        { label: 'Live Stream', href: '/live', icon: Video, count: 1 },
        // ... other nav items
      ]}
    />
  );

  // Center feed content
  const centerFeedContent = (
    <CenterFeedContent />
  );

  // Right sidebar content with custom widgets
  const rightSidebarContent = (
    <RightSidebarContent
      widgets={[
        { type: 'daily_verse', title: 'Daily Verse', isEnabled: true, config: {} },
        { type: 'upcoming_events', title: 'Upcoming Events', isEnabled: true, config: { maxItems: 4 } },
        { type: 'prayer_requests', title: 'Prayer Requests', isEnabled: true, config: { maxItems: 3 } },
        { type: 'suggested_connections', title: 'Connect', isEnabled: true, config: { maxItems: 3 } },
        { type: 'trending_topics', title: 'Trending', isEnabled: true, config: { maxItems: 4 } },
      ]}
    />
  );

  return (
    <ThreePaneFeedLayout
      leftSidebar={leftSidebarContent}
      centerFeed={centerFeedContent}
      rightSidebar={rightSidebarContent}
      searchComponent={searchComponent}
    />
  );
};

// Center Feed Content Component
const CenterFeedContent: React.FC = () => {
  const { user } = useAuth();
  
  return (
    <div className="space-y-6">
      {/* Create Post Widget */}
      <CreatePostWidget
        user={user}
        onPostCreated={(post) => {
          // Handle new post
          console.log('New post created:', post);
        }}
      />

      {/* Daily Verse Widget */}
      <Suspense fallback={<div className="h-32 bg-gray-100 animate-pulse rounded-lg" />}>
        <DailyVerseWidget
          config={{
            showAuthor: true,
            showShare: true,
            style: 'card'
          }}
        />
      </Suspense>

      {/* Announcements */}
      <Suspense fallback={<div className="h-48 bg-gray-100 animate-pulse rounded-lg" />}>
        <AnnouncementWidget
          config={{
            maxItems: 2,
            showImages: true,
            layout: 'card'
          }}
        />
      </Suspense>

      {/* Timeline Posts */}
      <Suspense fallback={<FeedTimelineSkeleton />}>
        <FeedTimeline />
      </Suspense>
    </div>
  );
};

// Feed Timeline Component
const FeedTimeline: React.FC = () => {
  // Mock data - replace with actual API call
  const posts = [
    {
      id: 1,
      user: {
        name: 'Pastor John Smith',
        avatar: null,
        role: 'Senior Pastor'
      },
      content: 'Excited to announce our upcoming community outreach event! Join us this Saturday as we serve our neighbors and share God\'s love.',
      timestamp: '2 hours ago',
      reactions: {
        bless: 23,
        love: 8,
        pray: 15,
        amen: 12
      },
      comments: 5,
      shares: 3,
      image: null,
      tags: ['#CommunityOutreach', '#ServiceDay']
    },
    {
      id: 2,
      user: {
        name: 'Sarah Johnson',
        avatar: null,
        role: 'Member'
      },
      content: 'Please pray for my grandmother who is having surgery tomorrow. We trust in God\'s healing power.',
      timestamp: '4 hours ago',
      reactions: {
        bless: 45,
        love: 12,
        pray: 67,
        amen: 23
      },
      comments: 12,
      shares: 1,
      type: 'prayer_request'
    },
    {
      id: 3,
      user: {
        name: 'Youth Ministry',
        avatar: null,
        role: 'Ministry'
      },
      content: 'What an amazing time we had at youth group last night! Check out these photos from our game night and worship session.',
      timestamp: '1 day ago',
      reactions: {
        bless: 18,
        love: 25,
        pray: 3,
        amen: 8
      },
      comments: 7,
      shares: 5,
      images: ['image1.jpg', 'image2.jpg', 'image3.jpg']
    }
  ];

  return (
    <div className="space-y-6">
      {posts.map((post) => (
        <FeedTimelinePost
          key={post.id}
          post={post}
          onReaction={(postId, reactionType) => {
            console.log(`Reaction: ${reactionType} on post ${postId}`);
          }}
          onComment={(postId, comment) => {
            console.log(`Comment on post ${postId}:`, comment);
          }}
          onShare={(postId) => {
            console.log(`Shared post ${postId}`);
          }}
        />
      ))}
      
      {/* Load More Button */}
      <div className="text-center py-8">
        <button className="px-6 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors">
          Load More Posts
        </button>
      </div>
    </div>
  );
};

// Feed Timeline Skeleton
const FeedTimelineSkeleton: React.FC = () => {
  return (
    <div className="space-y-6">
      {Array.from({ length: 3 }).map((_, index) => (
        <div key={index} className="bg-white dark:bg-gray-800 rounded-lg p-6 border border-gray-200 dark:border-gray-700">
          <div className="flex items-start space-x-3">
            <div className="w-12 h-12 bg-gray-200 dark:bg-gray-700 rounded-full animate-pulse" />
            <div className="flex-1 space-y-2">
              <div className="h-4 bg-gray-200 dark:bg-gray-700 rounded animate-pulse w-1/4" />
              <div className="h-3 bg-gray-200 dark:bg-gray-700 rounded animate-pulse w-1/6" />
            </div>
          </div>
          <div className="mt-4 space-y-2">
            <div className="h-4 bg-gray-200 dark:bg-gray-700 rounded animate-pulse" />
            <div className="h-4 bg-gray-200 dark:bg-gray-700 rounded animate-pulse w-3/4" />
          </div>
          <div className="mt-4 flex items-center space-x-6">
            <div className="h-8 w-20 bg-gray-200 dark:bg-gray-700 rounded animate-pulse" />
            <div className="h-8 w-20 bg-gray-200 dark:bg-gray-700 rounded animate-pulse" />
            <div className="h-8 w-20 bg-gray-200 dark:bg-gray-700 rounded animate-pulse" />
          </div>
        </div>
      ))}
    </div>
  );
};

export default FeedPage;