import React, { useState } from 'react';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@ui/card';
import { Button } from '@ui/button';
import { Badge } from '@ui/badge';
import { Input } from '@ui/input';
import { Label } from '@ui/label';
import { Switch } from '@ui/switch';
import { Trash2, Edit3, Eye, Settings, Plus } from 'lucide-react';
import { useFeedLayouts, useDeleteFeedLayout, FeedLayout } from '../hooks/useFeedLayouts';
import { toast } from 'react-hot-toast';

export function FeedLayoutManager() {
  const { data: layouts = [], isLoading, error } = useFeedLayouts();
  const deleteLayout = useDeleteFeedLayout();
  const [selectedLayout, setSelectedLayout] = useState<FeedLayout | null>(null);
  const [isCreating, setIsCreating] = useState(false);

  if (isLoading) return (
    <div className="flex items-center justify-center p-8">
      <div className="w-6 h-6 border-2 border-gray-300 dark:border-gray-700 border-t-blue-600 rounded-full animate-spin"></div>
    </div>
  );

  if (error) {
    return (
      <Card className="bg-red-50 border-red-200 dark:bg-red-950 dark:border-red-800">
        <CardContent className="p-6">
          <p className="text-red-600 dark:text-red-400">Failed to load feed layouts</p>
        </CardContent>
      </Card>
    );
  }

  const handleDelete = async (layout: FeedLayout) => {
    if (!confirm(`Are you sure you want to delete "${layout.name}"?`)) return;

    try {
      await deleteLayout.mutateAsync(layout.id);
      toast.success(`"${layout.name}" has been deleted successfully.`);
    } catch (error) {
      toast.error('Failed to delete the layout.');
    }
  };

  return (
    <div className="space-y-6">
      {/* Header */}
      <div className="flex items-center justify-between">
        <div>
          <h2 className="text-2xl font-bold text-gray-900 dark:text-white">
            Feed Layout Manager
          </h2>
          <p className="text-gray-600 dark:text-gray-400">
            Customize your church community feed with different layout configurations
          </p>
        </div>
        <Button 
          onClick={() => setIsCreating(true)}
          className="bg-blue-600 hover:bg-blue-700"
        >
          <Plus className="w-4 h-4 mr-2" />
          New Layout
        </Button>
      </div>

      {/* Layout Grid */}
      <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
        {layouts.map((layout) => (
          <Card 
            key={layout.id} 
            className={`relative transition-all hover:shadow-lg ${
              layout.is_active 
                ? 'ring-2 ring-blue-500 bg-blue-50 dark:bg-blue-950' 
                : 'bg-white dark:bg-[#161920]'
            }`}
          >
            {layout.is_active && (
              <Badge 
                variant="default" 
                className="absolute -top-2 -right-2 bg-blue-600 text-white"
              >
                Active
              </Badge>
            )}
            
            <CardHeader>
              <div className="flex items-start justify-between">
                <div>
                  <CardTitle className="text-lg text-gray-900 dark:text-white">
                    {layout.name}
                  </CardTitle>
                  {layout.description && (
                    <CardDescription className="text-gray-600 dark:text-gray-400 mt-1">
                      {layout.description}
                    </CardDescription>
                  )}
                </div>
                
                <div className="flex gap-1">
                  <Button 
                    variant="ghost" 
                    size="sm"
                    onClick={() => setSelectedLayout(layout)}
                  >
                    <Eye className="w-4 h-4" />
                  </Button>
                  <Button 
                    variant="ghost" 
                    size="sm"
                    onClick={() => setSelectedLayout(layout)}
                  >
                    <Edit3 className="w-4 h-4" />
                  </Button>
                  <Button 
                    variant="ghost" 
                    size="sm"
                    onClick={() => handleDelete(layout)}
                    disabled={layout.is_active}
                    className="hover:bg-red-50 hover:text-red-600"
                  >
                    <Trash2 className="w-4 h-4" />
                  </Button>
                </div>
              </div>
            </CardHeader>

            <CardContent className="pt-0">
              {/* Layout Preview */}
              <div className="bg-gray-100 dark:bg-[#0C0E12] rounded-lg p-4 mb-4">
                <div className="flex gap-2 h-16">
                  {/* Left Sidebar Preview */}
                  <div className="w-1/4 bg-gray-300 dark:bg-gray-700 rounded opacity-60" />
                  
                  {/* Center Content Preview */}
                  <div className="flex-1 bg-gray-300 dark:bg-gray-700 rounded" />
                  
                  {/* Right Sidebar Preview */}
                  <div className="w-1/4 bg-gray-300 dark:bg-gray-700 rounded opacity-60" />
                </div>
                
                <div className="mt-2 text-xs text-gray-500 dark:text-gray-400 text-center">
                  3-Pane Layout Preview
                </div>
              </div>

              {/* Layout Stats */}
              <div className="space-y-2 text-sm">
                <div className="flex justify-between">
                  <span className="text-gray-600 dark:text-gray-400">Sort Order:</span>
                  <span className="text-gray-900 dark:text-white">{layout.sort_order}</span>
                </div>
                <div className="flex justify-between">
                  <span className="text-gray-600 dark:text-gray-400">Created:</span>
                  <span className="text-gray-900 dark:text-white">
                    {new Date(layout.created_at).toLocaleDateString()}
                  </span>
                </div>
              </div>

              {/* Actions */}
              <div className="mt-4 flex gap-2">
                <Button 
                  variant="outline" 
                  size="sm" 
                  className="flex-1"
                  onClick={() => setSelectedLayout(layout)}
                >
                  <Settings className="w-4 h-4 mr-1" />
                  Configure
                </Button>
                
                {!layout.is_active && (
                  <Button 
                    variant="default" 
                    size="sm" 
                    className="bg-blue-600 hover:bg-blue-700"
                  >
                    Activate
                  </Button>
                )}
              </div>
            </CardContent>
          </Card>
        ))}
      </div>

      {layouts.length === 0 && (
        <Card className="text-center py-12">
          <CardContent>
            <div className="text-gray-500 dark:text-gray-400 mb-4">
              <Settings className="w-12 h-12 mx-auto mb-4 opacity-50" />
              <h3 className="text-lg font-medium mb-2">No Feed Layouts</h3>
              <p>Create your first feed layout to customize your community page</p>
            </div>
            <Button 
              onClick={() => setIsCreating(true)}
              className="bg-blue-600 hover:bg-blue-700"
            >
              <Plus className="w-4 h-4 mr-2" />
              Create Layout
            </Button>
          </CardContent>
        </Card>
      )}

      {/* Layout Detail Modal would go here */}
      {selectedLayout && (
        <div className="fixed inset-0 bg-black/50 z-50 flex items-center justify-center p-4">
          <Card className="w-full max-w-4xl max-h-[80vh] overflow-y-auto">
            <CardHeader>
              <div className="flex items-center justify-between">
                <CardTitle>Configure {selectedLayout.name}</CardTitle>
                <Button 
                  variant="ghost"
                  onClick={() => setSelectedLayout(null)}
                >
                  ×
                </Button>
              </div>
            </CardHeader>
            <CardContent>
              <p className="text-gray-600 dark:text-gray-400">
                Layout configuration interface coming soon...
              </p>
            </CardContent>
          </Card>
        </div>
      )}
    </div>
  );
}