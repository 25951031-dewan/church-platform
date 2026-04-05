import { useState } from 'react';
import {
  Calendar, MessageSquare, Bell, Heart, Settings, Eye, BookOpen,
} from 'lucide-react';
import { useFeedWidgets, useFeedWidgetCategories, FeedWidget } from '../hooks/useFeedLayouts';

const WidgetIcons: Record<string, React.ElementType> = {
  daily_verse:     Calendar,
  post_feed:       MessageSquare,
  announcements:   Bell,
  events:          Calendar,
  prayer_requests: Heart,
  sermons:         BookOpen,
};

function getIcon(key: string): React.ElementType {
  return WidgetIcons[key] ?? Settings;
}

export function WidgetLibrary() {
  const { data: widgets = [], isLoading } = useFeedWidgets();
  const { data: categories = [] } = useFeedWidgetCategories();
  const [selectedCategory, setSelectedCategory] = useState<string>('all');
  const [selectedWidget, setSelectedWidget] = useState<FeedWidget | null>(null);

  const filtered = selectedCategory === 'all'
    ? widgets
    : widgets.filter(w => w.category === selectedCategory);

  if (isLoading) return (
    <div className="flex items-center justify-center py-16">
      <div className="w-6 h-6 border-2 border-white/10 border-t-indigo-500 rounded-full animate-spin" />
    </div>
  );

  return (
    <div className="space-y-5">
      {/* Category filter */}
      <div className="flex flex-wrap gap-2">
        {(['all', ...categories.map((c: any) => c.name)] as string[]).map((cat) => (
          <button
            key={cat}
            type="button"
            onClick={() => setSelectedCategory(cat)}
            className={`px-3 py-1.5 rounded-lg text-xs font-medium transition-colors ${
              selectedCategory === cat
                ? 'bg-indigo-600 text-white'
                : 'bg-white/5 text-gray-400 hover:bg-white/10 hover:text-white'
            }`}
          >
            {cat === 'all' ? 'All Widgets' : cat}
          </button>
        ))}
      </div>

      {/* Widget grid */}
      {filtered.length === 0 ? (
        <div className="text-center py-12 text-gray-500 text-sm">No widgets in this category.</div>
      ) : (
        <div className="grid gap-3 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4">
          {filtered.map((widget) => {
            const Icon = getIcon(widget.widget_key);
            return (
              <button
                key={widget.id}
                type="button"
                onClick={() => setSelectedWidget(widget)}
                className="text-left bg-[#161920] border border-white/5 hover:border-indigo-500/30 rounded-xl p-4 transition-colors group"
              >
                <div className="flex items-center gap-3 mb-3">
                  <div className="p-2 rounded-lg bg-indigo-600/10 text-indigo-400">
                    <Icon size={16} />
                  </div>
                  <div className="min-w-0">
                    <p className="text-sm font-medium text-white truncate">{widget.display_name}</p>
                    <p className="text-xs text-gray-500">{widget.category}</p>
                  </div>
                </div>
                {widget.description && (
                  <p className="text-xs text-gray-500 line-clamp-2">{widget.description}</p>
                )}
                <div className="flex gap-1 mt-3">
                  {widget.is_customizable && (
                    <span className="text-xs bg-white/5 text-gray-500 px-1.5 py-0.5 rounded">Customizable</span>
                  )}
                </div>
              </button>
            );
          })}
        </div>
      )}

      {/* Widget detail modal */}
      {selectedWidget && (
        <div className="fixed inset-0 bg-black/60 backdrop-blur-sm z-50 flex items-center justify-center p-4">
          <div className="bg-[#161920] border border-white/10 rounded-xl w-full max-w-md">
            <div className="flex items-center justify-between p-5 border-b border-white/5">
              <div className="flex items-center gap-3">
                <div className="p-2 rounded-lg bg-indigo-600/10 text-indigo-400">
                  {(() => { const Icon = getIcon(selectedWidget.widget_key); return <Icon size={18} />; })()}
                </div>
                <div>
                  <h3 className="font-semibold text-white">{selectedWidget.display_name}</h3>
                  <p className="text-xs text-gray-500">{selectedWidget.category}</p>
                </div>
              </div>
              <button
                type="button"
                onClick={() => setSelectedWidget(null)}
                className="text-gray-400 hover:text-white transition-colors text-lg leading-none"
              >
                ✕
              </button>
            </div>
            <div className="p-5 space-y-4">
              {selectedWidget.description && (
                <p className="text-sm text-gray-400">{selectedWidget.description}</p>
              )}
              <div>
                <p className="text-xs text-gray-500 mb-1">Component path</p>
                <code className="text-xs text-indigo-400 bg-indigo-600/10 px-2 py-1 rounded">
                  {selectedWidget.component_path}
                </code>
              </div>
              {selectedWidget.permissions_required && selectedWidget.permissions_required.length > 0 && (
                <div>
                  <p className="text-xs text-gray-500 mb-1">Required permissions</p>
                  <div className="flex flex-wrap gap-1">
                    {selectedWidget.permissions_required.map((p) => (
                      <span key={p} className="text-xs bg-white/5 text-gray-400 px-1.5 py-0.5 rounded">{p}</span>
                    ))}
                  </div>
                </div>
              )}
              <div className="flex items-center gap-2 pt-2">
                <span className={`flex items-center gap-1 text-xs px-2 py-1 rounded ${
                  selectedWidget.is_customizable
                    ? 'bg-green-500/10 text-green-400'
                    : 'bg-white/5 text-gray-500'
                }`}>
                  <Eye size={11} />
                  {selectedWidget.is_customizable ? 'Customizable' : 'Fixed layout'}
                </span>
              </div>
            </div>
          </div>
        </div>
      )}
    </div>
  );
}
