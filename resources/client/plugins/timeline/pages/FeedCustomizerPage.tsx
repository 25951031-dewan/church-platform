import { useState } from 'react';
import { Layout, Settings, Eye } from 'lucide-react';
import { FeedLayoutManager } from '../components/FeedLayoutManager';
import { WidgetLibrary } from '../components/WidgetLibrary';
import { useActiveFeedLayout } from '../hooks/useFeedLayouts';

type Tab = 'layouts' | 'widgets';

export function FeedCustomizerPage() {
  const { data: activeLayout, isLoading } = useActiveFeedLayout();
  const [activeTab, setActiveTab] = useState<Tab>('layouts');

  return (
    <div className="space-y-6">
      {/* Header */}
      <div className="flex items-start justify-between">
        <div>
          <h1 className="text-xl font-bold text-white flex items-center gap-2">
            <Layout size={24} />
            Feed Customizer
          </h1>
          <p className="text-sm text-gray-400 mt-1">
            Design and customize your church community feed experience
          </p>
        </div>
        <div className="flex items-center gap-3">
          {isLoading ? (
            <span className="text-xs text-gray-500">Loading…</span>
          ) : activeLayout ? (
            <span className="flex items-center gap-1.5 text-xs bg-green-500/10 text-green-400 border border-green-500/20 px-2.5 py-1 rounded-full">
              <Eye size={11} />
              Active: {activeLayout.name}
            </span>
          ) : (
            <span className="text-xs text-gray-500">No active layout</span>
          )}
        </div>
      </div>

      {/* Tab bar */}
      <div className="flex gap-1 bg-[#161920] border border-white/5 rounded-lg p-1 w-fit">
        {([
          { key: 'layouts' as Tab, label: 'Layouts',        icon: Layout   },
          { key: 'widgets' as Tab, label: 'Widget Library', icon: Settings },
        ]).map(({ key, label, icon: Icon }) => (
          <button
            key={key}
            type="button"
            onClick={() => setActiveTab(key)}
            className={`flex items-center gap-2 px-4 py-2 rounded-md text-sm font-medium transition-colors ${
              activeTab === key
                ? 'bg-white/10 text-white'
                : 'text-gray-400 hover:text-white'
            }`}
          >
            <Icon size={15} />
            {label}
          </button>
        ))}
      </div>

      {/* Tab content */}
      {activeTab === 'layouts' && <FeedLayoutManager />}
      {activeTab === 'widgets' && <WidgetLibrary />}
    </div>
  );
}
