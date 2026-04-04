// BeMusic-Style Three-Pane Feed Customizer
// Left: Widget Builder | Center: Live Preview | Right: Widget Library & Settings

import React, { useState, useCallback } from 'react';
import { DndProvider } from 'react-dnd';
import { HTML5Backend } from 'react-dnd-html5-backend';
import { TouchBackend } from 'react-dnd-touch-backend';
import { 
  Layout, Eye, Save, RotateCcw, Smartphone, 
  Tablet, Monitor, X, Layers
} from 'lucide-react';
import { useMediaQuery } from '@/hooks/useMediaQuery';
import { cn } from '@/lib/utils';
import { Button } from '@ui/button';
import { Tabs, TabsContent, TabsList, TabsTrigger } from '@ui/tabs';
import { Badge } from '@ui/badge';
import { toast } from 'react-hot-toast';

interface FeedCustomizerProps {
  churchId: string;
  onSave?: (layout: any) => void;
  onClose?: () => void;
}

type ViewportMode = 'desktop' | 'tablet' | 'mobile';
type CustomizerPane = 'builder' | 'preview' | 'library';

const FeedCustomizer: React.FC<FeedCustomizerProps> = ({ churchId, onSave, onClose }) => {
  const [viewportMode, setViewportMode] = useState<ViewportMode>('desktop');
  const [isPreviewMode, setIsPreviewMode] = useState(false);
  const [activePane, setActivePane] = useState<CustomizerPane>('builder');
  const [selectedWidget, setSelectedWidget] = useState<string | null>(null);
  const [isSaving, setIsSaving] = useState(false);
  
  // Responsive detection
  const isMobile = useMediaQuery('(max-width: 768px)');
  const isTablet = useMediaQuery('(max-width: 1024px)');

  // Layout state - BeMusic-style channel configuration
  const [layout, setLayout] = useState({
    left: [
      { id: 'nav-1', type: 'navigation_menu', config: { style: 'expanded' } },
      { id: 'profile-1', type: 'user_profile_card', config: { showActivity: true } },
      { id: 'shortcuts-1', type: 'quick_shortcuts', config: { maxItems: 6 } }
    ],
    center: [
      { id: 'create-1', type: 'create_post_widget', config: { allowMedia: true } },
      { id: 'verse-1', type: 'daily_verse', config: { showShare: true, style: 'banner' } },
      { id: 'timeline-1', type: 'timeline_posts', config: { allowReactions: true, maxItems: 10 } }
    ],
    right: [
      { id: 'events-1', type: 'upcoming_events', config: { maxItems: 4 } },
      { id: 'prayer-1', type: 'prayer_requests', config: { maxItems: 3 } },
      { id: 'trending-1', type: 'trending_topics', config: { maxItems: 5 } }
    ],
    settings: {
      responsive: true,
      theme: 'light',
      spacing: 'normal'
    }
  });

  const handleSave = async () => {
    setIsSaving(true);
    try {
      if (onSave) {
        await onSave(layout);
      }
      toast.success('Layout saved successfully!');
    } catch (error) {
      toast.error('Failed to save layout');
    } finally {
      setIsSaving(false);
    }
  };

  return (
    <DndProvider backend={isMobile || isTablet ? TouchBackend : HTML5Backend}>
      <div className="h-screen bg-gray-50 dark:bg-gray-900 flex flex-col">
        {/* Customizer Header */}
        <CustomizerHeader
          viewportMode={viewportMode}
          onViewportChange={setViewportMode}
          isPreviewMode={isPreviewMode}
          onPreviewToggle={() => setIsPreviewMode(!isPreviewMode)}
          onSave={handleSave}
          onReset={() => {
            setLayout({
              left: [],
              center: [],
              right: [],
              settings: { responsive: true, theme: 'light', spacing: 'normal' }
            });
            toast.success('Layout reset');
          }}
          onClose={onClose}
          isSaving={isSaving}
        />

        {/* Main Customizer Body */}
        <div className="flex-1 flex overflow-hidden">
          {(isMobile || isTablet) ? (
            <MobileCustomizerLayout
              activePane={activePane}
              onPaneChange={setActivePane}
              layout={layout}
              onLayoutChange={setLayout}
              viewportMode={viewportMode}
              isPreviewMode={isPreviewMode}
            />
          ) : (
            <DesktopCustomizerLayout
              layout={layout}
              onLayoutChange={setLayout}
              viewportMode={viewportMode}
              isPreviewMode={isPreviewMode}
            />
          )}
        </div>
      </div>
    </DndProvider>
  );
};

// Customizer Header
const CustomizerHeader: React.FC<{
  viewportMode: ViewportMode;
  onViewportChange: (mode: ViewportMode) => void;
  isPreviewMode: boolean;
  onPreviewToggle: () => void;
  onSave: () => void;
  onReset: () => void;
  onClose?: () => void;
  isSaving: boolean;
}> = ({ viewportMode, onViewportChange, isPreviewMode, onPreviewToggle, onSave, onReset, onClose, isSaving }) => {
  return (
    <header className="h-16 bg-white dark:bg-gray-800 border-b border-gray-200 dark:border-gray-700 flex items-center justify-between px-6">
      {/* Left */}
      <div className="flex items-center space-x-4">
        {onClose && (
          <Button variant="ghost" size="sm" onClick={onClose}>
            <X className="w-4 h-4 mr-2" />
            Close
          </Button>
        )}
        <div>
          <h1 className="text-xl font-semibold text-gray-900 dark:text-white">Feed Customizer</h1>
          <p className="text-xs text-gray-500 dark:text-gray-400">Design your church feed layout</p>
        </div>
      </div>

      {/* Center: Viewport Controls */}
      <div className="flex items-center space-x-1 bg-gray-100 dark:bg-gray-700 rounded-lg p-1">
        <Button
          variant={viewportMode === 'desktop' ? 'secondary' : 'ghost'}
          size="sm"
          onClick={() => onViewportChange('desktop')}
        >
          <Monitor className="w-4 h-4" />
        </Button>
        <Button
          variant={viewportMode === 'tablet' ? 'secondary' : 'ghost'}
          size="sm"
          onClick={() => onViewportChange('tablet')}
        >
          <Tablet className="w-4 h-4" />
        </Button>
        <Button
          variant={viewportMode === 'mobile' ? 'secondary' : 'ghost'}
          size="sm"
          onClick={() => onViewportChange('mobile')}
        >
          <Smartphone className="w-4 h-4" />
        </Button>
      </div>

      {/* Right: Actions */}
      <div className="flex items-center space-x-2">
        <Button
          variant={isPreviewMode ? 'default' : 'outline'}
          size="sm"
          onClick={onPreviewToggle}
        >
          {isPreviewMode ? <Layout className="w-4 h-4 mr-2" /> : <Eye className="w-4 h-4 mr-2" />}
          {isPreviewMode ? 'Edit Mode' : 'Preview'}
        </Button>
        
        <Button variant="outline" size="sm" onClick={onReset}>
          <RotateCcw className="w-4 h-4 mr-2" />
          Reset
        </Button>
        
        <Button size="sm" onClick={onSave} disabled={isSaving}>
          {isSaving ? (
            <div className="w-4 h-4 mr-2 border-2 border-white border-t-transparent rounded-full animate-spin" />
          ) : (
            <Save className="w-4 h-4 mr-2" />
          )}
          {isSaving ? 'Saving...' : 'Save'}
        </Button>
      </div>
    </header>
  );
};

// Desktop Layout
const DesktopCustomizerLayout: React.FC<{
  layout: any;
  onLayoutChange: (layout: any) => void;
  viewportMode: ViewportMode;
  isPreviewMode: boolean;
}> = ({ layout, onLayoutChange, viewportMode, isPreviewMode }) => {
  return (
    <>
      {/* Left Pane: Widget Builder */}
      <aside className="w-80 bg-white dark:bg-gray-800 border-r border-gray-200 dark:border-gray-700">
        <WidgetBuilder layout={layout} onLayoutChange={onLayoutChange} />
      </aside>

      {/* Center Pane: Live Preview */}
      <main className="flex-1 bg-gray-50 dark:bg-gray-900">
        <LivePreview layout={layout} viewportMode={viewportMode} isPreviewMode={isPreviewMode} />
      </main>

      {/* Right Pane: Widget Library */}
      <aside className="w-80 bg-white dark:bg-gray-800 border-l border-gray-200 dark:border-gray-700">
        <WidgetLibrary layout={layout} onLayoutChange={onLayoutChange} />
      </aside>
    </>
  );
};

// Mobile Layout
const MobileCustomizerLayout: React.FC<{
  activePane: CustomizerPane;
  onPaneChange: (pane: CustomizerPane) => void;
  layout: any;
  onLayoutChange: (layout: any) => void;
  viewportMode: ViewportMode;
  isPreviewMode: boolean;
}> = ({ activePane, onPaneChange, layout, onLayoutChange, viewportMode, isPreviewMode }) => {
  return (
    <div className="flex-1 flex flex-col">
      {/* Mobile Tabs */}
      <div className="h-12 bg-white dark:bg-gray-800 border-b border-gray-200 dark:border-gray-700 flex">
        {[
          { key: 'builder', icon: Layout, label: 'Builder' },
          { key: 'preview', icon: Eye, label: 'Preview' },
          { key: 'library', icon: Layers, label: 'Widgets' }
        ].map(({ key, icon: Icon, label }) => (
          <button
            key={key}
            className={cn(
              "flex-1 flex items-center justify-center space-x-2 text-sm font-medium transition-colors",
              activePane === key 
                ? "bg-blue-50 dark:bg-blue-900/20 text-blue-600 dark:text-blue-400 border-b-2 border-blue-600" 
                : "text-gray-500 dark:text-gray-400 hover:text-gray-700 dark:hover:text-gray-300"
            )}
            onClick={() => onPaneChange(key as CustomizerPane)}
          >
            <Icon className="w-4 h-4" />
            <span>{label}</span>
          </button>
        ))}
      </div>

      {/* Mobile Content */}
      <div className="flex-1 overflow-hidden">
        {activePane === 'builder' && <WidgetBuilder layout={layout} onLayoutChange={onLayoutChange} />}
        {activePane === 'preview' && <LivePreview layout={layout} viewportMode={viewportMode} isPreviewMode={isPreviewMode} />}
        {activePane === 'library' && <WidgetLibrary layout={layout} onLayoutChange={onLayoutChange} />}
      </div>
    </div>
  );
};

// Widget Builder Component
const WidgetBuilder: React.FC<{
  layout: any;
  onLayoutChange: (layout: any) => void;
}> = ({ layout, onLayoutChange }) => {
  return (
    <div className="h-full flex flex-col">
      <div className="p-4 border-b border-gray-200 dark:border-gray-700">
        <h3 className="font-semibold text-gray-900 dark:text-white mb-2">Layout Builder</h3>
        <p className="text-xs text-gray-500 dark:text-gray-400">Manage widgets in each pane</p>
      </div>

      <Tabs defaultValue="left" className="flex-1">
        <TabsList className="w-full grid grid-cols-3">
          <TabsTrigger value="left">Left</TabsTrigger>
          <TabsTrigger value="center">Center</TabsTrigger>
          <TabsTrigger value="right">Right</TabsTrigger>
        </TabsList>
        
        <TabsContent value="left" className="flex-1 p-4 space-y-3">
          <PaneWidgetList pane="left" widgets={layout.left} onLayoutChange={onLayoutChange} />
        </TabsContent>
        
        <TabsContent value="center" className="flex-1 p-4 space-y-3">
          <PaneWidgetList pane="center" widgets={layout.center} onLayoutChange={onLayoutChange} />
        </TabsContent>
        
        <TabsContent value="right" className="flex-1 p-4 space-y-3">
          <PaneWidgetList pane="right" widgets={layout.right} onLayoutChange={onLayoutChange} />
        </TabsContent>
      </Tabs>
    </div>
  );
};

// Live Preview Component
const LivePreview: React.FC<{
  layout: any;
  viewportMode: ViewportMode;
  isPreviewMode: boolean;
}> = ({ layout, viewportMode, isPreviewMode }) => {
  const getViewportClasses = () => {
    switch (viewportMode) {
      case 'mobile': return 'max-w-sm';
      case 'tablet': return 'max-w-2xl';
      default: return 'max-w-6xl';
    }
  };

  return (
    <div className="h-full p-6 overflow-auto">
      <div className={cn('mx-auto', getViewportClasses())}>
        <div className="mb-4 flex items-center justify-between">
          <h3 className="font-semibold text-gray-900 dark:text-white">
            Live Preview - {viewportMode.charAt(0).toUpperCase() + viewportMode.slice(1)}
          </h3>
          <Badge variant={isPreviewMode ? 'default' : 'secondary'}>
            {isPreviewMode ? 'Preview Mode' : 'Edit Mode'}
          </Badge>
        </div>

        {/* Three-Pane Preview */}
        <div className="bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700 overflow-hidden">
          <div className={cn('flex', viewportMode === 'mobile' ? 'flex-col' : 'flex-row')}>
            {/* Left Preview */}
            <div className={cn(
              'border-r border-gray-200 dark:border-gray-700',
              viewportMode === 'mobile' ? 'w-full border-r-0 border-b' : 'w-1/4'
            )}>
              <div className="p-3 bg-gray-50 dark:bg-gray-900 border-b">
                <h4 className="text-xs font-medium text-gray-600 dark:text-gray-400">Left Sidebar</h4>
              </div>
              <div className="p-4 space-y-3 min-h-32">
                {layout.left.map((widget: any, index: number) => (
                  <WidgetPreview key={`left-${index}`} widget={widget} isPreviewMode={isPreviewMode} />
                ))}
                {layout.left.length === 0 && (
                  <div className="text-center py-8 text-gray-400 text-sm">No widgets</div>
                )}
              </div>
            </div>

            {/* Center Preview */}
            <div className={cn(viewportMode === 'mobile' ? 'w-full' : 'flex-1')}>
              <div className="p-3 bg-gray-50 dark:bg-gray-900 border-b">
                <h4 className="text-xs font-medium text-gray-600 dark:text-gray-400">Main Feed</h4>
              </div>
              <div className="p-4 space-y-4 min-h-64">
                {layout.center.map((widget: any, index: number) => (
                  <WidgetPreview key={`center-${index}`} widget={widget} isPreviewMode={isPreviewMode} />
                ))}
                {layout.center.length === 0 && (
                  <div className="text-center py-12 text-gray-400 text-sm">Drop widgets here</div>
                )}
              </div>
            </div>

            {/* Right Preview */}
            <div className={cn(
              'border-l border-gray-200 dark:border-gray-700',
              viewportMode === 'mobile' ? 'w-full border-l-0 border-t' : 'w-1/4'
            )}>
              <div className="p-3 bg-gray-50 dark:bg-gray-900 border-b">
                <h4 className="text-xs font-medium text-gray-600 dark:text-gray-400">Right Sidebar</h4>
              </div>
              <div className="p-4 space-y-3 min-h-32">
                {layout.right.map((widget: any, index: number) => (
                  <WidgetPreview key={`right-${index}`} widget={widget} isPreviewMode={isPreviewMode} />
                ))}
                {layout.right.length === 0 && (
                  <div className="text-center py-8 text-gray-400 text-sm">No widgets</div>
                )}
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  );
};

// Widget Library Component
const WidgetLibrary: React.FC<{
  layout: any;
  onLayoutChange: (layout: any) => void;
}> = ({ layout, onLayoutChange }) => {
  const availableWidgets = [
    { type: 'daily_verse', name: 'Daily Verse', description: 'Scripture of the day', icon: '📖' },
    { type: 'announcements', name: 'Announcements', description: 'Church announcements', icon: '📢' },
    { type: 'upcoming_events', name: 'Events', description: 'Upcoming church events', icon: '📅' },
    { type: 'prayer_requests', name: 'Prayers', description: 'Prayer requests', icon: '🙏' },
    { type: 'trending_topics', name: 'Trending', description: 'Popular topics', icon: '🔥' },
    { type: 'ministry_spotlight', name: 'Ministry', description: 'Featured ministry', icon: '⭐' }
  ];

  const addWidget = (widgetType: string, pane: 'left' | 'center' | 'right') => {
    const newWidget = {
      id: `${widgetType}-${Date.now()}`,
      type: widgetType,
      config: {}
    };
    
    const newLayout = { ...layout };
    newLayout[pane] = [...newLayout[pane], newWidget];
    onLayoutChange(newLayout);
    toast.success(`Added ${widgetType} to ${pane} pane`);
  };

  return (
    <div className="h-full flex flex-col">
      <div className="p-4 border-b border-gray-200 dark:border-gray-700">
        <h3 className="font-semibold text-gray-900 dark:text-white mb-2">Widget Library</h3>
        <p className="text-xs text-gray-500 dark:text-gray-400">Drag widgets to customize layout</p>
      </div>

      <div className="flex-1 p-4 space-y-3">
        {availableWidgets.map((widget) => (
          <div key={widget.type} className="p-3 bg-gray-50 dark:bg-gray-800 rounded-lg border border-gray-200 dark:border-gray-700">
            <div className="flex items-center space-x-3 mb-2">
              <span className="text-lg">{widget.icon}</span>
              <div>
                <h4 className="font-medium text-gray-900 dark:text-white text-sm">{widget.name}</h4>
                <p className="text-xs text-gray-500 dark:text-gray-400">{widget.description}</p>
              </div>
            </div>
            <div className="flex space-x-1">
              <Button size="sm" variant="outline" onClick={() => addWidget(widget.type, 'left')} className="text-xs">
                Left
              </Button>
              <Button size="sm" variant="outline" onClick={() => addWidget(widget.type, 'center')} className="text-xs">
                Center
              </Button>
              <Button size="sm" variant="outline" onClick={() => addWidget(widget.type, 'right')} className="text-xs">
                Right
              </Button>
            </div>
          </div>
        ))}
      </div>
    </div>
  );
};

// Helper Components
const PaneWidgetList: React.FC<{ pane: string; widgets: any[]; onLayoutChange: any }> = ({ pane, widgets, onLayoutChange }) => {
  const removeWidget = (index: number) => {
    // Remove widget logic here
    console.log(`Remove widget ${index} from ${pane}`);
  };

  return (
    <div className="space-y-2">
      {widgets.map((widget, index) => (
        <div key={widget.id} className="p-2 bg-gray-50 dark:bg-gray-800 rounded border flex items-center justify-between">
          <span className="text-sm font-medium">{widget.type}</span>
          <Button size="sm" variant="ghost" onClick={() => removeWidget(index)}>
            <X className="w-3 h-3" />
          </Button>
        </div>
      ))}
      {widgets.length === 0 && (
        <div className="text-center py-4 text-gray-400 text-sm">No widgets in {pane} pane</div>
      )}
    </div>
  );
};

const WidgetPreview: React.FC<{ widget: any; isPreviewMode: boolean }> = ({ widget, isPreviewMode }) => {
  return (
    <div className={cn(
      "p-3 bg-gray-100 dark:bg-gray-700 rounded border-2 border-dashed transition-colors",
      isPreviewMode ? "border-transparent bg-white dark:bg-gray-800 shadow-sm" : "border-gray-300 dark:border-gray-600"
    )}>
      <div className="text-sm font-medium text-gray-700 dark:text-gray-300">{widget.type}</div>
      <div className="text-xs text-gray-500 dark:text-gray-400">Widget preview</div>
    </div>
  );
};

export default FeedCustomizer;