import React, { useState } from 'react';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@ui/card';
import { Button } from '@ui/button';
import { Badge } from '@ui/badge';
import { Tabs, TabsContent, TabsList, TabsTrigger } from '@ui/tabs';
import { 
  Layout, 
  Palette, 
  Smartphone,
  Monitor,
  Settings,
  Save,
  Eye
} from 'lucide-react';
import { FeedLayoutManager } from '../components/FeedLayoutManager';
import { WidgetLibrary } from '../components/WidgetLibrary';
import { useActiveFeedLayout } from '../hooks/useFeedLayouts';

export function FeedCustomizerPage() {
  const { data: activeLayout, isLoading } = useActiveFeedLayout();
  const [activeTab, setActiveTab] = useState('layouts');

  if (isLoading) return (
    <div className="flex items-center justify-center p-8">
      <div className="w-6 h-6 border-2 border-gray-300 dark:border-gray-700 border-t-blue-600 rounded-full animate-spin"></div>
    </div>
  );

  return (
    <div className="min-h-screen bg-gray-50 dark:bg-[#0C0E12]">
      {/* Header */}
      <div className="bg-white dark:bg-[#161920] border-b border-gray-200 dark:border-gray-700 px-6 py-4">
        <div className="max-w-7xl mx-auto">
          <div className="flex items-center justify-between">
            <div>
              <h1 className="text-2xl font-bold text-gray-900 dark:text-white">
                Feed Customizer
              </h1>
              <p className="text-gray-600 dark:text-gray-400 mt-1">
                Design and customize your church community feed experience
              </p>
            </div>
            
            <div className="flex items-center gap-4">
              {activeLayout && (
                <div className="text-sm">
                  <span className="text-gray-600 dark:text-gray-400">Active Layout:</span>
                  <Badge variant="default" className="ml-2 bg-blue-600">
                    {activeLayout.name}
                  </Badge>
                </div>
              )}
              
              <div className="flex gap-2">
                <Button variant="outline">
                  <Eye className="w-4 h-4 mr-2" />
                  Preview
                </Button>
                <Button className="bg-blue-600 hover:bg-blue-700">
                  <Save className="w-4 h-4 mr-2" />
                  Save Changes
                </Button>
              </div>
            </div>
          </div>
        </div>
      </div>

      {/* Content */}
      <div className="max-w-7xl mx-auto px-6 py-6">
        <Tabs value={activeTab} onValueChange={setActiveTab}>
          <TabsList className="grid w-full grid-cols-3 lg:w-auto lg:grid-cols-none lg:inline-flex">
            <TabsTrigger value="layouts" className="flex items-center gap-2">
              <Layout className="w-4 h-4" />
              Layouts
            </TabsTrigger>
            <TabsTrigger value="widgets" className="flex items-center gap-2">
              <Settings className="w-4 h-4" />
              Widgets
            </TabsTrigger>
            <TabsTrigger value="customize" className="flex items-center gap-2">
              <Palette className="w-4 h-4" />
              Customize
            </TabsTrigger>
          </TabsList>

          {/* Layouts Tab */}
          <TabsContent value="layouts" className="mt-6">
            <FeedLayoutManager />
          </TabsContent>

          {/* Widgets Tab */}
          <TabsContent value="widgets" className="mt-6">
            <WidgetLibrary />
          </TabsContent>

          {/* Customize Tab */}
          <TabsContent value="customize" className="mt-6">
            <div className="grid grid-cols-1 lg:grid-cols-2 gap-6">
              {/* Layout Preview */}
              <Card>
                <CardHeader>
                  <CardTitle className="flex items-center gap-2">
                    <Monitor className="w-5 h-5" />
                    Layout Preview
                  </CardTitle>
                  <CardDescription>
                    See how your feed will look on different devices
                  </CardDescription>
                </CardHeader>
                <CardContent>
                  <div className="space-y-4">
                    {/* Desktop Preview */}
                    <div>
                      <Label className="text-sm font-medium mb-2 block">Desktop View</Label>
                      <div className="border rounded-lg p-4 bg-gray-50 dark:bg-[#0C0E12]">
                        <div className="flex gap-3 h-48">
                          {/* Left Sidebar */}
                          <div className="w-1/4 bg-white dark:bg-[#161920] border rounded shadow-sm p-3 space-y-2">
                            <div className="h-4 bg-blue-100 dark:bg-blue-900 rounded" />
                            <div className="h-16 bg-gray-100 dark:bg-gray-800 rounded" />
                            <div className="h-12 bg-gray-100 dark:bg-gray-800 rounded" />
                          </div>
                          
                          {/* Center Content */}
                          <div className="flex-1 bg-white dark:bg-[#161920] border rounded shadow-sm p-3 space-y-2">
                            <div className="h-6 bg-gray-100 dark:bg-gray-800 rounded" />
                            <div className="h-20 bg-gray-100 dark:bg-gray-800 rounded" />
                            <div className="h-20 bg-gray-100 dark:bg-gray-800 rounded" />
                            <div className="h-20 bg-gray-100 dark:bg-gray-800 rounded" />
                          </div>
                          
                          {/* Right Sidebar */}
                          <div className="w-1/4 bg-white dark:bg-[#161920] border rounded shadow-sm p-3 space-y-2">
                            <div className="h-4 bg-green-100 dark:bg-green-900 rounded" />
                            <div className="h-12 bg-gray-100 dark:bg-gray-800 rounded" />
                            <div className="h-16 bg-gray-100 dark:bg-gray-800 rounded" />
                          </div>
                        </div>
                      </div>
                    </div>

                    {/* Mobile Preview */}
                    <div>
                      <Label className="text-sm font-medium mb-2 block">Mobile View</Label>
                      <div className="border rounded-lg p-4 bg-gray-50 dark:bg-[#0C0E12] flex justify-center">
                        <div className="w-64 h-48 bg-white dark:bg-[#161920] border rounded shadow-sm p-3 space-y-2">
                          <div className="h-6 bg-gray-100 dark:bg-gray-800 rounded" />
                          <div className="h-20 bg-gray-100 dark:bg-gray-800 rounded" />
                          <div className="h-20 bg-gray-100 dark:bg-gray-800 rounded" />
                          <div className="h-16 bg-gray-100 dark:bg-gray-800 rounded" />
                        </div>
                      </div>
                    </div>
                  </div>
                </CardContent>
              </Card>

              {/* Configuration Panel */}
              <Card>
                <CardHeader>
                  <CardTitle className="flex items-center gap-2">
                    <Settings className="w-5 h-5" />
                    Layout Configuration
                  </CardTitle>
                  <CardDescription>
                    Customize the behavior and appearance of your layout
                  </CardDescription>
                </CardHeader>
                <CardContent className="space-y-4">
                  <div className="text-center py-8 text-gray-500 dark:text-gray-400">
                    <Settings className="w-12 h-12 mx-auto mb-4 opacity-50" />
                    <p>Select a layout to customize its settings</p>
                  </div>
                </CardContent>
              </Card>
            </div>
          </TabsContent>
        </Tabs>
      </div>
    </div>
  );
}