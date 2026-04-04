// Right Sidebar Components for Church Feed

import React, { useState } from 'react';
import { 
  Calendar, Clock, MapPin, Users, Heart, MessageSquare,
  ChevronRight, Settings, MoreHorizontal, Star, Zap,
  TrendingUp, UserPlus, CheckCircle, AlertCircle
} from 'lucide-react';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Avatar, AvatarFallback, AvatarImage } from '@/components/ui/avatar';
import { Progress } from '@/components/ui/progress';
import { cn } from '@/lib/utils';

interface RightSidebarContentProps {
  widgets?: WidgetConfig[];
}

interface WidgetConfig {
  type: string;
  title: string;
  isEnabled: boolean;
  config: Record<string, any>;
}

const RightSidebarContent: React.FC<RightSidebarContentProps> = ({ widgets }) => {
  const defaultWidgets = [
    { type: 'upcoming_events', title: 'Upcoming Events', isEnabled: true, config: { maxItems: 5 } },
    { type: 'prayer_requests', title: 'Prayer Requests', isEnabled: true, config: { maxItems: 4 } },
    { type: 'suggested_connections', title: 'People You May Know', isEnabled: true, config: { maxItems: 3 } },
    { type: 'trending_topics', title: 'Trending', isEnabled: true, config: { maxItems: 5 } },
    { type: 'ministry_highlights', title: 'Ministry Spotlight', isEnabled: true, config: {} }
  ];

  const activeWidgets = widgets || defaultWidgets;

  return (
    <div className="h-full overflow-y-auto p-4 space-y-6">
      {activeWidgets.filter(w => w.isEnabled).map((widget) => (
        <RightSidebarWidget key={widget.type} widget={widget} />
      ))}
    </div>
  );
};

// Widget Renderer
const RightSidebarWidget: React.FC<{ widget: WidgetConfig }> = ({ widget }) => {
  const renderWidget = () => {
    switch (widget.type) {
      case 'upcoming_events':
        return <UpcomingEventsWidget config={widget.config} />;
      case 'prayer_requests':
        return <PrayerRequestsWidget config={widget.config} />;
      case 'suggested_connections':
        return <SuggestedConnectionsWidget config={widget.config} />;
      case 'trending_topics':
        return <TrendingTopicsWidget config={widget.config} />;
      case 'ministry_highlights':
        return <MinistryHighlightsWidget config={widget.config} />;
      case 'daily_verse':
        return <DailyVerseWidget config={widget.config} />;
      case 'church_stats':
        return <ChurchStatsWidget config={widget.config} />;
      default:
        return <DefaultWidget title={widget.title} />;
    }
  };

  return (
    <Card className="shadow-sm">
      <CardHeader className="pb-3">
        <div className="flex items-center justify-between">
          <CardTitle className="text-lg font-semibold">{widget.title}</CardTitle>
          <Button variant="ghost" size="sm">
            <MoreHorizontal className="w-4 h-4" />
          </Button>
        </div>
      </CardHeader>
      <CardContent className="pt-0">
        {renderWidget()}
      </CardContent>
    </Card>
  );
};

// Upcoming Events Widget
const UpcomingEventsWidget: React.FC<{ config: any }> = ({ config }) => {
  const events = [
    {
      id: 1,
      title: 'Sunday Worship Service',
      date: '2024-01-07',
      time: '10:00 AM',
      location: 'Main Sanctuary',
      attendees: 245
    },
    {
      id: 2,
      title: 'Youth Bible Study',
      date: '2024-01-09',
      time: '7:00 PM',
      location: 'Youth Room',
      attendees: 32
    },
    {
      id: 3,
      title: 'Prayer Meeting',
      date: '2024-01-10',
      time: '6:30 PM',
      location: 'Fellowship Hall',
      attendees: 18
    },
    {
      id: 4,
      title: 'Community Outreach',
      date: '2024-01-12',
      time: '9:00 AM',
      location: 'Downtown Park',
      attendees: 45
    }
  ];

  const maxItems = config.maxItems || 5;
  const displayEvents = events.slice(0, maxItems);

  return (
    <div className="space-y-3">
      {displayEvents.map((event) => (
        <div key={event.id} className="p-3 bg-gray-50 dark:bg-gray-800 rounded-lg hover:bg-gray-100 dark:hover:bg-gray-700 cursor-pointer transition-colors">
          <div className="flex items-start justify-between">
            <div className="flex-1">
              <h4 className="font-medium text-sm text-gray-900 dark:text-white mb-1">
                {event.title}
              </h4>
              <div className="space-y-1">
                <div className="flex items-center text-xs text-gray-500 dark:text-gray-400">
                  <Calendar className="w-3 h-3 mr-1" />
                  <span>{new Date(event.date).toLocaleDateString()}</span>
                  <Clock className="w-3 h-3 ml-2 mr-1" />
                  <span>{event.time}</span>
                </div>
                <div className="flex items-center text-xs text-gray-500 dark:text-gray-400">
                  <MapPin className="w-3 h-3 mr-1" />
                  <span>{event.location}</span>
                </div>
                <div className="flex items-center text-xs text-gray-500 dark:text-gray-400">
                  <Users className="w-3 h-3 mr-1" />
                  <span>{event.attendees} attending</span>
                </div>
              </div>
            </div>
            <ChevronRight className="w-4 h-4 text-gray-400 ml-2" />
          </div>
        </div>
      ))}
      
      <Button variant="ghost" className="w-full text-sm" size="sm">
        View All Events
        <ChevronRight className="w-4 h-4 ml-1" />
      </Button>
    </div>
  );
};

// Prayer Requests Widget
const PrayerRequestsWidget: React.FC<{ config: any }> = ({ config }) => {
  const prayerRequests = [
    {
      id: 1,
      user: 'Sarah Johnson',
      request: 'Please pray for my grandmother\'s recovery from surgery',
      prayerCount: 23,
      timeAgo: '2 hours ago',
      avatar: null
    },
    {
      id: 2,
      user: 'Michael Chen',
      request: 'Guidance in my career transition and job search',
      prayerCount: 18,
      timeAgo: '5 hours ago',
      avatar: null
    },
    {
      id: 3,
      user: 'Emily Rodriguez',
      request: 'For peace and comfort during this difficult time',
      prayerCount: 31,
      timeAgo: '1 day ago',
      avatar: null
    }
  ];

  const maxItems = config.maxItems || 4;
  const displayRequests = prayerRequests.slice(0, maxItems);

  return (
    <div className="space-y-4">
      {displayRequests.map((request) => (
        <div key={request.id} className="p-3 bg-gray-50 dark:bg-gray-800 rounded-lg">
          <div className="flex items-start space-x-3">
            <Avatar className="w-8 h-8">
              <AvatarImage src={request.avatar} />
              <AvatarFallback className="text-xs bg-blue-500 text-white">
                {request.user.charAt(0)}
              </AvatarFallback>
            </Avatar>
            <div className="flex-1 min-w-0">
              <h5 className="text-sm font-medium text-gray-900 dark:text-white">
                {request.user}
              </h5>
              <p className="text-xs text-gray-600 dark:text-gray-400 mt-1 line-clamp-2">
                {request.request}
              </p>
              <div className="flex items-center justify-between mt-2">
                <div className="flex items-center text-xs text-gray-500 dark:text-gray-400">
                  <Heart className="w-3 h-3 mr-1" />
                  <span>{request.prayerCount} prayers</span>
                </div>
                <span className="text-xs text-gray-500 dark:text-gray-400">
                  {request.timeAgo}
                </span>
              </div>
              <Button variant="outline" size="sm" className="w-full mt-2 text-xs">
                <Heart className="w-3 h-3 mr-1" />
                Pray for this
              </Button>
            </div>
          </div>
        </div>
      ))}
      
      <Button variant="ghost" className="w-full text-sm" size="sm">
        View All Prayer Requests
        <ChevronRight className="w-4 h-4 ml-1" />
      </Button>
    </div>
  );
};

// Suggested Connections Widget
const SuggestedConnectionsWidget: React.FC<{ config: any }> = ({ config }) => {
  const suggestions = [
    {
      id: 1,
      name: 'David Wilson',
      role: 'Youth Pastor',
      mutualConnections: 12,
      avatar: null
    },
    {
      id: 2,
      name: 'Jennifer Lee',
      role: 'Worship Leader',
      mutualConnections: 8,
      avatar: null
    },
    {
      id: 3,
      name: 'Robert Martinez',
      role: 'Ministry Leader',
      mutualConnections: 15,
      avatar: null
    }
  ];

  const maxItems = config.maxItems || 3;
  const displaySuggestions = suggestions.slice(0, maxItems);

  return (
    <div className="space-y-3">
      {displaySuggestions.map((person) => (
        <div key={person.id} className="flex items-center space-x-3">
          <Avatar className="w-10 h-10">
            <AvatarImage src={person.avatar} />
            <AvatarFallback className="bg-green-500 text-white">
              {person.name.charAt(0)}
            </AvatarFallback>
          </Avatar>
          <div className="flex-1 min-w-0">
            <h5 className="text-sm font-medium text-gray-900 dark:text-white">
              {person.name}
            </h5>
            <p className="text-xs text-gray-500 dark:text-gray-400">
              {person.role}
            </p>
            <p className="text-xs text-gray-500 dark:text-gray-400">
              {person.mutualConnections} mutual connections
            </p>
          </div>
          <Button variant="outline" size="sm">
            <UserPlus className="w-3 h-3 mr-1" />
            Connect
          </Button>
        </div>
      ))}
      
      <Button variant="ghost" className="w-full text-sm" size="sm">
        See All Suggestions
        <ChevronRight className="w-4 h-4 ml-1" />
      </Button>
    </div>
  );
};

// Trending Topics Widget
const TrendingTopicsWidget: React.FC<{ config: any }> = ({ config }) => {
  const topics = [
    { tag: '#SundayService', posts: 45, growth: '+12%' },
    { tag: '#PrayerRequest', posts: 23, growth: '+8%' },
    { tag: '#YouthMinistry', posts: 18, growth: '+15%' },
    { tag: '#BibleStudy', posts: 32, growth: '+5%' },
    { tag: '#Community', posts: 27, growth: '+20%' }
  ];

  const maxItems = config.maxItems || 5;
  const displayTopics = topics.slice(0, maxItems);

  return (
    <div className="space-y-2">
      {displayTopics.map((topic, index) => (
        <div key={topic.tag} className="flex items-center justify-between p-2 hover:bg-gray-50 dark:hover:bg-gray-800 rounded cursor-pointer transition-colors">
          <div className="flex items-center space-x-3">
            <div className="flex items-center justify-center w-6 h-6 text-xs font-bold text-blue-600 dark:text-blue-400">
              {index + 1}
            </div>
            <div>
              <h5 className="text-sm font-medium text-gray-900 dark:text-white">
                {topic.tag}
              </h5>
              <p className="text-xs text-gray-500 dark:text-gray-400">
                {topic.posts} posts
              </p>
            </div>
          </div>
          <div className="text-right">
            <span className="text-xs text-green-600 dark:text-green-400 font-medium">
              {topic.growth}
            </span>
            <TrendingUp className="w-3 h-3 text-green-500 ml-1 inline" />
          </div>
        </div>
      ))}
    </div>
  );
};

// Ministry Highlights Widget
const MinistryHighlightsWidget: React.FC<{ config: any }> = ({ config }) => {
  const highlight = {
    ministry: 'Youth Ministry',
    title: 'Summer Camp Registration Open',
    description: 'Join us for an amazing week of fun, fellowship, and spiritual growth.',
    image: null,
    deadline: '2024-02-15',
    spotsLeft: 15,
    totalSpots: 50
  };

  const progressPercentage = ((highlight.totalSpots - highlight.spotsLeft) / highlight.totalSpots) * 100;

  return (
    <div className="space-y-4">
      <div className="aspect-video bg-gradient-to-r from-blue-500 to-purple-600 rounded-lg flex items-center justify-center">
        <div className="text-center text-white p-4">
          <h3 className="font-bold text-lg mb-2">{highlight.ministry}</h3>
          <p className="text-sm opacity-90">{highlight.title}</p>
        </div>
      </div>
      
      <div>
        <p className="text-sm text-gray-600 dark:text-gray-400 mb-3">
          {highlight.description}
        </p>
        
        <div className="space-y-2">
          <div className="flex justify-between text-xs">
            <span className="text-gray-500">Registration Progress</span>
            <span className="text-gray-700 dark:text-gray-300">
              {highlight.totalSpots - highlight.spotsLeft}/{highlight.totalSpots}
            </span>
          </div>
          <Progress value={progressPercentage} className="h-2" />
          <p className="text-xs text-orange-600 dark:text-orange-400">
            Only {highlight.spotsLeft} spots left!
          </p>
        </div>
        
        <Button className="w-full mt-3" size="sm">
          Register Now
        </Button>
      </div>
    </div>
  );
};

// Daily Verse Widget (compact version for sidebar)
const DailyVerseWidget: React.FC<{ config: any }> = ({ config }) => {
  const verse = {
    content: "For I know the plans I have for you, declares the Lord, plans for welfare and not for evil, to give you a future and a hope.",
    reference: "Jeremiah 29:11"
  };

  return (
    <div className="text-center p-4 bg-gradient-to-r from-blue-50 to-indigo-50 dark:from-blue-900/20 dark:to-indigo-900/20 rounded-lg">
      <h4 className="text-xs font-semibold text-blue-600 dark:text-blue-400 uppercase tracking-wider mb-2">
        Daily Verse
      </h4>
      <blockquote className="text-sm italic text-gray-700 dark:text-gray-300 mb-2">
        "{verse.content}"
      </blockquote>
      <cite className="text-xs text-gray-500 dark:text-gray-400">
        — {verse.reference}
      </cite>
    </div>
  );
};

// Church Stats Widget
const ChurchStatsWidget: React.FC<{ config: any }> = ({ config }) => {
  const stats = [
    { label: 'Active Members', value: '1,247', icon: Users, color: 'text-blue-600' },
    { label: 'This Week', value: '89', icon: Calendar, color: 'text-green-600' },
    { label: 'Prayer Requests', value: '23', icon: Heart, color: 'text-red-600' },
    { label: 'Upcoming Events', value: '12', icon: Zap, color: 'text-purple-600' }
  ];

  return (
    <div className="grid grid-cols-2 gap-3">
      {stats.map((stat) => (
        <div key={stat.label} className="text-center p-3 bg-gray-50 dark:bg-gray-800 rounded-lg">
          <stat.icon className={`w-5 h-5 mx-auto mb-1 ${stat.color}`} />
          <div className="text-lg font-bold text-gray-900 dark:text-white">
            {stat.value}
          </div>
          <div className="text-xs text-gray-500 dark:text-gray-400">
            {stat.label}
          </div>
        </div>
      ))}
    </div>
  );
};

// Default Widget
const DefaultWidget: React.FC<{ title: string }> = ({ title }) => {
  return (
    <div className="text-center p-8 text-gray-500 dark:text-gray-400">
      <AlertCircle className="w-8 h-8 mx-auto mb-2" />
      <p className="text-sm">Widget not implemented: {title}</p>
    </div>
  );
};

export { RightSidebarContent };