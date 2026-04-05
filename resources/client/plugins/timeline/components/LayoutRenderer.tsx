import { useActiveFeedLayout } from '../hooks/useFeedLayouts';
import { getWidget } from '../widgets/widgetRegistry';

interface WidgetInstance {
  widget_key: string;
  sort_order: number;
  is_visible: boolean;
  config?: Record<string, any>;
}

interface SidebarConfig {
  widgets: WidgetInstance[];
}

interface LayoutData {
  left_sidebar_config: SidebarConfig;
  right_sidebar_config: SidebarConfig;
  center_widgets?: WidgetInstance[];
}

function WidgetRenderer({ instance }: { instance: WidgetInstance }) {
  const WidgetComponent = getWidget(instance.widget_key);
  
  if (!WidgetComponent || !instance.is_visible) {
    return null;
  }
  
  return <WidgetComponent config={instance.config} />;
}

function SidebarPane({ widgets, className }: { widgets: WidgetInstance[]; className?: string }) {
  const sortedWidgets = [...widgets]
    .filter(w => w.is_visible)
    .sort((a, b) => a.sort_order - b.sort_order);
    
  if (sortedWidgets.length === 0) return null;
  
  return (
    <div className={`space-y-4 ${className || ''}`}>
      {sortedWidgets.map((widget, index) => (
        <WidgetRenderer key={`${widget.widget_key}-${index}`} instance={widget} />
      ))}
    </div>
  );
}

function LoadingSkeleton() {
  return (
    <div className="min-h-screen bg-[#0C0E12]">
      <div className="max-w-7xl mx-auto px-4 py-6">
        <div className="grid grid-cols-1 lg:grid-cols-12 gap-6">
          {/* Left sidebar skeleton */}
          <div className="lg:col-span-3 hidden lg:block space-y-4">
            {[...Array(3)].map((_, i) => (
              <div key={i} className="bg-[#161920] border border-white/5 rounded-xl p-4">
                <div className="h-4 bg-gray-700 rounded animate-pulse mb-3"></div>
                <div className="space-y-2">
                  <div className="h-3 bg-gray-700 rounded animate-pulse"></div>
                  <div className="h-3 bg-gray-700 rounded animate-pulse w-3/4"></div>
                </div>
              </div>
            ))}
          </div>
          
          {/* Center skeleton */}
          <div className="lg:col-span-6 space-y-4">
            {[...Array(3)].map((_, i) => (
              <div key={i} className="bg-[#161920] border border-white/5 rounded-xl p-4">
                <div className="h-4 bg-gray-700 rounded animate-pulse mb-3"></div>
                <div className="h-20 bg-gray-700 rounded animate-pulse mb-3"></div>
                <div className="h-3 bg-gray-700 rounded animate-pulse w-1/2"></div>
              </div>
            ))}
          </div>
          
          {/* Right sidebar skeleton */}
          <div className="lg:col-span-3 hidden lg:block space-y-4">
            {[...Array(2)].map((_, i) => (
              <div key={i} className="bg-[#161920] border border-white/5 rounded-xl p-4">
                <div className="h-4 bg-gray-700 rounded animate-pulse mb-3"></div>
                <div className="space-y-2">
                  <div className="h-3 bg-gray-700 rounded animate-pulse"></div>
                  <div className="h-3 bg-gray-700 rounded animate-pulse w-3/4"></div>
                </div>
              </div>
            ))}
          </div>
        </div>
      </div>
    </div>
  );
}

export function LayoutRenderer() {
  const { data: activeLayout, isLoading, error } = useActiveFeedLayout();
  
  if (isLoading) {
    return <LoadingSkeleton />;
  }
  
  if (error) {
    return (
      <div className="min-h-screen bg-[#0C0E12] flex items-center justify-center">
        <div className="bg-[#161920] border border-white/5 rounded-xl p-6 max-w-md">
          <h2 className="text-lg font-semibold text-white mb-2">Unable to load feed layout</h2>
          <p className="text-gray-400 text-sm">
            There was an error loading the feed configuration. Please try refreshing the page.
          </p>
        </div>
      </div>
    );
  }
  
  if (!activeLayout) {
    return (
      <div className="min-h-screen bg-[#0C0E12] flex items-center justify-center">
        <div className="bg-[#161920] border border-white/5 rounded-xl p-6 max-w-md text-center">
          <h2 className="text-lg font-semibold text-white mb-2">No active feed layout</h2>
          <p className="text-gray-400 text-sm">
            Please configure a feed layout in the admin panel to view your community feed.
          </p>
        </div>
      </div>
    );
  }
  
  const layoutData = activeLayout.layout_data as LayoutData || {};
  const leftWidgets = layoutData.left_sidebar_config?.widgets || [];
  const rightWidgets = layoutData.right_sidebar_config?.widgets || [];
  const centerWidgets = layoutData.center_widgets || [];
  
  return (
    <div className="min-h-screen bg-[#0C0E12]">
      <div className="max-w-7xl mx-auto px-4 py-6">
        <div className="grid grid-cols-1 lg:grid-cols-12 gap-6">
          {/* Left Sidebar - Hidden on mobile */}
          <div className="lg:col-span-3 hidden lg:block">
            <div className="sticky top-6">
              <SidebarPane widgets={leftWidgets} />
            </div>
          </div>
          
          {/* Center Content */}
          <div className="lg:col-span-6">
            <div className="space-y-4">
              {centerWidgets
                .filter(w => w.is_visible)
                .sort((a, b) => a.sort_order - b.sort_order)
                .map((widget, index) => (
                  <WidgetRenderer key={`center-${widget.widget_key}-${index}`} instance={widget} />
                ))
              }
              
              {/* Fallback: if no center widgets configured, show default post feed */}
              {centerWidgets.filter(w => w.is_visible).length === 0 && (
                <WidgetRenderer instance={{
                  widget_key: 'post_feed',
                  sort_order: 0,
                  is_visible: true,
                  config: { show_composer: true }
                }} />
              )}
            </div>
          </div>
          
          {/* Right Sidebar - Hidden on mobile */}
          <div className="lg:col-span-3 hidden lg:block">
            <div className="sticky top-6">
              <SidebarPane widgets={rightWidgets} />
            </div>
          </div>
        </div>
      </div>
    </div>
  );
}