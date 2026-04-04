// Left Sidebar Components for Church Feed

import React, { useState } from 'react';
import { 
  Home, Users, Calendar, Heart, BookOpen, Settings, 
  ChevronDown, ChevronRight, Plus, Bell, MessageCircle,
  Church, Bookmark, Compass, TrendingUp
} from 'lucide-react';
import { Link, useLocation } from 'react-router-dom';
import { Card, CardContent } from '@/components/ui/card';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Avatar, AvatarFallback, AvatarImage } from '@/components/ui/avatar';
import { cn } from '@/lib/utils';

interface LeftSidebarContentProps {
  user: any;
  church: any;
  navigationItems?: NavigationItem[];
}

interface NavigationItem {
  label: string;
  href: string;
  icon: React.ComponentType<{ className?: string }>;
  count?: number;
  children?: NavigationItem[];
}

const LeftSidebarContent: React.FC<LeftSidebarContentProps> = ({
  user,
  church,
  navigationItems
}) => {
  const location = useLocation();
  
  const defaultNavigation: NavigationItem[] = [
    { label: 'Feed', href: '/feed', icon: Home },
    { label: 'Community', href: '/community', icon: Users, count: 3 },
    { label: 'Events', href: '/events', icon: Calendar, count: 2 },
    { label: 'Prayer Requests', href: '/prayers', icon: Heart, count: 5 },
    { label: 'Bible Study', href: '/bible', icon: BookOpen },
    { label: 'Ministries', href: '/ministries', icon: Church, children: [
      { label: 'Youth Ministry', href: '/ministries/youth', icon: Users },
      { label: 'Music Ministry', href: '/ministries/music', icon: Users },
      { label: 'Outreach', href: '/ministries/outreach', icon: Users }
    ]},
    { label: 'Saved Posts', href: '/saved', icon: Bookmark },
    { label: 'Explore', href: '/explore', icon: Compass },
    { label: 'Settings', href: '/settings', icon: Settings }
  ];

  const navigation = navigationItems || defaultNavigation;

  return (
    <div className="h-full flex flex-col">
      {/* User Profile Section */}
      <UserProfileSection user={user} church={church} />
      
      {/* Navigation Menu */}
      <NavigationMenu navigation={navigation} currentPath={location.pathname} />
      
      {/* Quick Actions */}
      <QuickActionsSection />
      
      {/* Recent Activity */}
      <RecentActivitySection />
      
      {/* Shortcuts */}
      <ShortcutsSection />
    </div>
  );
};

// User Profile Section
const UserProfileSection: React.FC<{
  user: any;
  church: any;
}> = ({ user, church }) => {
  return (
    <div className="p-4 border-b border-gray-200 dark:border-gray-700">
      <div className="flex items-center space-x-3">
        <Avatar className="w-12 h-12">
          <AvatarImage src={user?.avatar} alt={user?.name} />
          <AvatarFallback className="bg-blue-500 text-white">
            {user?.name?.charAt(0) || 'U'}
          </AvatarFallback>
        </Avatar>
        <div className="flex-1 min-w-0">
          <h3 className="text-sm font-semibold text-gray-900 dark:text-white truncate">
            {user?.name || 'User'}
          </h3>
          <p className="text-xs text-gray-500 dark:text-gray-400 truncate">
            {church?.name || 'Church Member'}
          </p>
        </div>
      </div>
    </div>
  );
};

// Navigation Menu
const NavigationMenu: React.FC<{
  navigation: NavigationItem[];
  currentPath: string;
}> = ({ navigation, currentPath }) => {
  const [expandedItems, setExpandedItems] = useState<Set<string>>(new Set());

  const toggleExpanded = (label: string) => {
    setExpandedItems(prev => {
      const newSet = new Set(prev);
      if (newSet.has(label)) {
        newSet.delete(label);
      } else {
        newSet.add(label);
      }
      return newSet;
    });
  };

  return (
    <nav className="flex-1 p-4 space-y-1">
      {navigation.map((item) => (
        <NavigationItem
          key={item.label}
          item={item}
          currentPath={currentPath}
          isExpanded={expandedItems.has(item.label)}
          onToggleExpanded={() => toggleExpanded(item.label)}
        />
      ))}
    </nav>
  );
};

// Individual Navigation Item
const NavigationItem: React.FC<{
  item: NavigationItem;
  currentPath: string;
  isExpanded: boolean;
  onToggleExpanded: () => void;
  depth?: number;
}> = ({ item, currentPath, isExpanded, onToggleExpanded, depth = 0 }) => {
  const isActive = currentPath === item.href;
  const hasChildren = item.children && item.children.length > 0;

  return (
    <div>
      {/* Main Item */}
      <div className={cn(
        "flex items-center space-x-3 px-3 py-2 rounded-lg transition-colors cursor-pointer",
        isActive 
          ? "bg-blue-50 dark:bg-blue-900/20 text-blue-600 dark:text-blue-400" 
          : "text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700",
        depth > 0 && "ml-6"
      )}>
        {hasChildren ? (
          <button
            onClick={onToggleExpanded}
            className="flex items-center space-x-3 flex-1 text-left"
          >
            <item.icon className="w-5 h-5 flex-shrink-0" />
            <span className="text-sm font-medium truncate">{item.label}</span>
            {item.count && (
              <Badge variant="secondary" className="ml-auto">
                {item.count}
              </Badge>
            )}
            <div className="ml-auto">
              {isExpanded ? (
                <ChevronDown className="w-4 h-4" />
              ) : (
                <ChevronRight className="w-4 h-4" />
              )}
            </div>
          </button>
        ) : (
          <Link to={item.href} className="flex items-center space-x-3 flex-1">
            <item.icon className="w-5 h-5 flex-shrink-0" />
            <span className="text-sm font-medium truncate">{item.label}</span>
            {item.count && (
              <Badge variant="secondary" className="ml-auto">
                {item.count}
              </Badge>
            )}
          </Link>
        )}
      </div>

      {/* Children */}
      {hasChildren && isExpanded && (
        <div className="mt-1 space-y-1">
          {item.children!.map((child) => (
            <NavigationItem
              key={child.label}
              item={child}
              currentPath={currentPath}
              isExpanded={false}
              onToggleExpanded={() => {}}
              depth={depth + 1}
            />
          ))}
        </div>
      )}
    </div>
  );
};

// Quick Actions Section
const QuickActionsSection: React.FC = () => {
  return (
    <div className="p-4 border-t border-gray-200 dark:border-gray-700">
      <h4 className="text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider mb-3">
        Quick Actions
      </h4>
      <div className="space-y-2">
        <Button variant="outline" className="w-full justify-start" size="sm">
          <Plus className="w-4 h-4 mr-2" />
          Create Post
        </Button>
        <Button variant="outline" className="w-full justify-start" size="sm">
          <Heart className="w-4 h-4 mr-2" />
          Prayer Request
        </Button>
        <Button variant="outline" className="w-full justify-start" size="sm">
          <Calendar className="w-4 h-4 mr-2" />
          New Event
        </Button>
      </div>
    </div>
  );
};

// Recent Activity Section
const RecentActivitySection: React.FC = () => {
  const recentActivities = [
    {
      id: 1,
      type: 'like',
      user: 'Sarah Johnson',
      action: 'liked your post',
      time: '2 hours ago',
      avatar: null
    },
    {
      id: 2,
      type: 'comment',
      user: 'Michael Chen',
      action: 'commented on your prayer request',
      time: '4 hours ago',
      avatar: null
    },
    {
      id: 3,
      type: 'event',
      user: 'Youth Ministry',
      action: 'invited you to Bible Study',
      time: '1 day ago',
      avatar: null
    }
  ];

  return (
    <div className="p-4 border-t border-gray-200 dark:border-gray-700">
      <div className="flex items-center justify-between mb-3">
        <h4 className="text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">
          Recent Activity
        </h4>
        <Bell className="w-4 h-4 text-gray-400" />
      </div>
      
      <div className="space-y-3">
        {recentActivities.map((activity) => (
          <div key={activity.id} className="flex items-start space-x-2">
            <Avatar className="w-8 h-8">
              <AvatarImage src={activity.avatar} />
              <AvatarFallback className="text-xs">
                {activity.user.charAt(0)}
              </AvatarFallback>
            </Avatar>
            <div className="flex-1 min-w-0">
              <p className="text-xs text-gray-600 dark:text-gray-400">
                <span className="font-medium">{activity.user}</span>{' '}
                {activity.action}
              </p>
              <p className="text-xs text-gray-500 dark:text-gray-500">
                {activity.time}
              </p>
            </div>
          </div>
        ))}
      </div>
    </div>
  );
};

// Shortcuts Section
const ShortcutsSection: React.FC = () => {
  const shortcuts = [
    { name: 'Sunday Service Live', href: '/live', icon: '🎥' },
    { name: 'Weekly Bulletin', href: '/bulletin', icon: '📰' },
    { name: 'Church Directory', href: '/directory', icon: '📖' },
    { name: 'Giving Portal', href: '/giving', icon: '💝' }
  ];

  return (
    <div className="p-4 border-t border-gray-200 dark:border-gray-700">
      <h4 className="text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider mb-3">
        Shortcuts
      </h4>
      
      <div className="space-y-1">
        {shortcuts.map((shortcut) => (
          <Link
            key={shortcut.name}
            to={shortcut.href}
            className="flex items-center space-x-3 p-2 rounded-lg hover:bg-gray-100 dark:hover:bg-gray-700 transition-colors"
          >
            <span className="text-base">{shortcut.icon}</span>
            <span className="text-sm text-gray-700 dark:text-gray-300 truncate">
              {shortcut.name}
            </span>
          </Link>
        ))}
      </div>
    </div>
  );
};

export { LeftSidebarContent };