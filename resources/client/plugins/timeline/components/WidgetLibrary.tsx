import React, { useState } from 'react';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@ui/card';
import { Button } from '@ui/button';
import { Badge } from '@ui/badge';
import { Input } from '@ui/input';
import { Label } from '@ui/label';
import { Switch } from '@ui/switch';
import { 
  Calendar, 
  MessageSquare, 
  Bell, 
  Users, 
  Heart, 
  Settings, 
  Eye,
  Plus,
  Grip
} from 'lucide-react';
import { useFeedWidgets, useFeedWidgetCategories, FeedWidget } from '../hooks/useFeedLayouts';

const WidgetIcons = {
  daily_verse: Calendar,
  post_feed: MessageSquare,
  announcements: Bell,
  events: Calendar,
  prayer_requests: Heart,
  community_stats: Users,
  default: Settings,
};

export function WidgetLibrary() {
  const { data: widgets = [], isLoading } = useFeedWidgets();
  const { data: categories = [] } = useFeedWidgetCategories();
  const [selectedCategory, setSelectedCategory] = useState<string>('all');
  const [selectedWidget, setSelectedWidget] = useState<FeedWidget | null>(null);

  if (isLoading) return (
    <div className="flex items-center justify-center p-8">
      <div className="w-6 h-6 border-2 border-gray-300 dark:border-gray-700 border-t-blue-600 rounded-full animate-spin"></div>
    </div>
  );

  const filteredWidgets = selectedCategory === 'all' 
    ? widgets 
    : widgets.filter(widget => widget.category === selectedCategory);

  const getWidgetIcon = (widget: FeedWidget) => {
    const IconComponent = WidgetIcons[widget.widget_key as keyof typeof WidgetIcons] || WidgetIcons.default;
    return <IconComponent className="w-5 h-5" />;
  };

  return (
    <div className="space-y-6">
      {/* Header */}
      <div>
        <h2 className="text-2xl font-bold text-gray-900 dark:text-white mb-2">
          Widget Library
        </h2>
        <p className="text-gray-600 dark:text-gray-400">
          Choose from available widgets to customize your feed layout
        </p>
      </div>

      {/* Category Filter */}
      <div className="flex flex-wrap gap-2">
        <Button
          variant={selectedCategory === 'all' ? 'default' : 'outline'}
          size="sm"
          onClick={() => setSelectedCategory('all')}
        >
          All Widgets
        </Button>
        {categories.map((category: any) => (
          <Button
            key={category.name}
            variant={selectedCategory === category.name ? 'default' : 'outline'}
            size="sm"
            onClick={() => setSelectedCategory(category.name)}
          >
            {category.display_name}
          </Button>
        ))}
      </div>

      {/* Widget Grid */}
      <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-4">
        {filteredWidgets.map((widget) => (
          <Card 
            key={widget.id}
            className="relative transition-all hover:shadow-lg cursor-pointer group bg-white dark:bg-[#161920] border border-gray-200 dark:border-gray-700 hover:border-blue-300"
            onClick={() => setSelectedWidget(widget)}
          >
            <CardHeader className="pb-2">
              <div className="flex items-start justify-between">
                <div className="flex items-center gap-2">
                  <div className="p-2 rounded-lg bg-blue-100 dark:bg-blue-900 text-blue-600 dark:text-blue-400">
                    {getWidgetIcon(widget)}
                  </div>
                  <div>
                    <CardTitle className="text-sm text-gray-900 dark:text-white">
                      {widget.display_name}
                    </CardTitle>
                    <Badge 
                      variant="secondary" 
                      className="text-xs mt-1 bg-gray-100 dark:bg-gray-800"
                    >
                      {widget.category}
                    </Badge>
                  </div>
                </div>
                
                <div className="opacity-0 group-hover:opacity-100 transition-opacity flex gap-1">
                  <Button variant="ghost" size="sm">
                    <Eye className="w-3 h-3" />
                  </Button>
                  <Button variant="ghost" size="sm">
                    <Plus className="w-3 h-3" />
                  </Button>
                </div>
              </div>
            </CardHeader>

            <CardContent className="pt-0">
              {widget.description && (
                <CardDescription className="text-xs text-gray-600 dark:text-gray-400 mb-3">
                  {widget.description}
                </CardDescription>
              )}

              {/* Widget Preview */}
              <div className="bg-gray-50 dark:bg-[#0C0E12] rounded-lg p-3 mb-3">
                {widget.widget_key === 'daily_verse' && (
                  <div className="text-center space-y-2">
                    <div className="w-full h-2 bg-gray-200 dark:bg-gray-700 rounded" />
                    <div className="w-3/4 h-1 bg-gray-200 dark:bg-gray-700 rounded mx-auto" />
                    <div className="text-xs text-gray-500 dark:text-gray-400">Daily Verse</div>
                  </div>
                )}
                
                {widget.widget_key === 'post_feed' && (
                  <div className="space-y-1">
                    {[1, 2].map(i => (
                      <div key={i} className="flex gap-2">
                        <div className="w-6 h-6 bg-gray-200 dark:bg-gray-700 rounded-full" />
                        <div className="flex-1 space-y-1">
                          <div className="w-full h-1 bg-gray-200 dark:bg-gray-700 rounded" />
                          <div className="w-2/3 h-1 bg-gray-200 dark:bg-gray-700 rounded" />
                        </div>
                      </div>
                    ))}
                    <div className="text-xs text-gray-500 dark:text-gray-400 text-center">Posts</div>
                  </div>
                )}

                {widget.widget_key === 'announcements' && (
                  <div className="space-y-2">
                    <div className="flex items-center gap-2">
                      <Bell className="w-3 h-3 text-blue-500" />
                      <div className="w-full h-1 bg-gray-200 dark:bg-gray-700 rounded" />
                    </div>
                    <div className="w-3/4 h-1 bg-gray-200 dark:bg-gray-700 rounded" />
                    <div className="text-xs text-gray-500 dark:text-gray-400">Announcements</div>
                  </div>
                )}

                {widget.widget_key === 'events' && (
                  <div className="space-y-1">
                    {[1, 2].map(i => (
                      <div key={i} className="flex gap-2 items-center">
                        <Calendar className="w-3 h-3 text-green-500" />
                        <div className="w-full h-1 bg-gray-200 dark:bg-gray-700 rounded" />
                      </div>
                    ))}
                    <div className="text-xs text-gray-500 dark:text-gray-400 text-center">Events</div>
                  </div>
                )}

                {widget.widget_key === 'prayer_requests' && (
                  <div className="space-y-1">
                    {[1, 2].map(i => (
                      <div key={i} className="flex gap-2 items-center">
                        <Heart className="w-3 h-3 text-red-500" />
                        <div className="w-full h-1 bg-gray-200 dark:bg-gray-700 rounded" />
                      </div>
                    ))}
                    <div className="text-xs text-gray-500 dark:text-gray-400 text-center">Prayers</div>
                  </div>
                )}
              </div>

              {/* Widget Features */}
              <div className="flex flex-wrap gap-1">
                {widget.is_customizable && (
                  <Badge variant="outline" className="text-xs">
                    Customizable
                  </Badge>
                )}
                {widget.is_system && (
                  <Badge variant="outline" className="text-xs">
                    System
                  </Badge>
                )}
              </div>
            </CardContent>
          </Card>
        ))}
      </div>

      {/* Widget Detail Modal */}
      {selectedWidget && (
        <div className="fixed inset-0 bg-black/50 z-50 flex items-center justify-center p-4">
          <Card className="w-full max-w-2xl max-h-[80vh] overflow-y-auto">
            <CardHeader>
              <div className="flex items-center justify-between">
                <div className="flex items-center gap-3">
                  <div className="p-3 rounded-lg bg-blue-100 dark:bg-blue-900 text-blue-600 dark:text-blue-400">
                    {getWidgetIcon(selectedWidget)}
                  </div>
                  <div>
                    <CardTitle className="text-xl">{selectedWidget.display_name}</CardTitle>
                    <CardDescription>{selectedWidget.description}</CardDescription>
                  </div>
                </div>
                <Button 
                  variant="ghost"
                  onClick={() => setSelectedWidget(null)}
                >
                  ×
                </Button>
              </div>
            </CardHeader>
            
            <CardContent className="space-y-4">
              <div>
                <Label className="text-sm font-medium">Category</Label>
                <Badge variant="secondary" className="ml-2">
                  {selectedWidget.category}
                </Badge>
              </div>

              <div>
                <Label className="text-sm font-medium">Component Path</Label>
                <p className="text-sm text-gray-600 dark:text-gray-400 font-mono">
                  {selectedWidget.component_path}
                </p>
              </div>

              {selectedWidget.permissions_required && selectedWidget.permissions_required.length > 0 && (
                <div>
                  <Label className="text-sm font-medium">Required Permissions</Label>
                  <div className="flex flex-wrap gap-1 mt-1">
                    {selectedWidget.permissions_required.map((permission) => (
                      <Badge key={permission} variant="outline" className="text-xs">
                        {permission}
                      </Badge>
                    ))}
                  </div>
                </div>
              )}

              <div className="flex gap-2 pt-4">
                <Button className="flex-1 bg-blue-600 hover:bg-blue-700">
                  <Plus className="w-4 h-4 mr-2" />
                  Add to Layout
                </Button>
                <Button variant="outline">
                  <Eye className="w-4 h-4 mr-2" />
                  Preview
                </Button>
              </div>
            </CardContent>
          </Card>
        </div>
      )}
    </div>
  );
}