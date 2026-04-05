import { useState } from 'react';
import { Trash2, Edit3, Settings, AlertCircle, Check } from 'lucide-react';
import { useFeedLayouts, useDeleteFeedLayout, useUpdateFeedLayout, FeedLayout } from '../hooks/useFeedLayouts';
import { useNotificationStore } from '@app/common/stores';

export function FeedLayoutManager() {
  const { data: layouts = [], isLoading, error } = useFeedLayouts();
  const deleteLayout = useDeleteFeedLayout();
  const updateLayout = useUpdateFeedLayout();
  const { success, error: notifyError } = useNotificationStore();
  const [selectedLayout, setSelectedLayout] = useState<FeedLayout | null>(null);

  const handleDelete = async (layout: FeedLayout) => {
    if (!confirm(`Delete layout "${layout.name}"? This cannot be undone.`)) return;
    try {
      await deleteLayout.mutateAsync(layout.id);
      success(`"${layout.name}" deleted.`);
    } catch {
      notifyError('Failed to delete layout.');
    }
  };

  const handleActivate = async (layout: FeedLayout) => {
    try {
      await updateLayout.mutateAsync({ id: layout.id, is_active: true });
      success(`"${layout.name}" is now the active layout.`);
    } catch {
      notifyError('Failed to activate layout.');
    }
  };

  if (isLoading) return (
    <div className="flex items-center justify-center py-16">
      <div className="w-6 h-6 border-2 border-white/10 border-t-indigo-500 rounded-full animate-spin" />
    </div>
  );

  if (error) return (
    <div className="flex items-center gap-2 bg-red-500/10 border border-red-500/20 rounded-xl p-4 text-red-400 text-sm">
      <AlertCircle size={16} />
      Failed to load feed layouts. Check that the Timeline plugin is enabled.
    </div>
  );

  if (layouts.length === 0) return (
    <div className="text-center py-16 bg-[#161920] border border-white/5 rounded-xl">
      <Settings size={40} className="mx-auto text-gray-600 mb-3" />
      <p className="text-gray-400 mb-1">No feed layouts yet</p>
      <p className="text-xs text-gray-600">Run the FeedWidgetSeeder to create default layouts</p>
    </div>
  );

  return (
    <div className="space-y-4">
      <div className="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
        {layouts.map((layout) => (
          <div
            key={layout.id}
            className={`relative bg-[#161920] border rounded-xl p-5 transition-colors ${
              layout.is_active
                ? 'border-indigo-500/40 ring-1 ring-indigo-500/20'
                : 'border-white/5 hover:border-white/10'
            }`}
          >
            {layout.is_active && (
              <span className="absolute -top-2.5 left-4 flex items-center gap-1 text-xs bg-indigo-600 text-white px-2 py-0.5 rounded-full">
                <Check size={10} /> Active
              </span>
            )}

            {/* 3-pane thumbnail */}
            <div className="flex gap-1.5 h-14 mb-4 bg-[#0C0E12] rounded-lg p-2">
              <div className="w-1/4 bg-white/5 rounded" />
              <div className="flex-1 bg-white/10 rounded" />
              <div className="w-1/4 bg-white/5 rounded" />
            </div>

            <h3 className="font-semibold text-white text-sm">{layout.name}</h3>
            {layout.description && (
              <p className="text-xs text-gray-500 mt-1 line-clamp-2">{layout.description}</p>
            )}
            <p className="text-xs text-gray-600 mt-1">
              {new Date(layout.created_at).toLocaleDateString()}
            </p>

            <div className="flex items-center gap-1.5 mt-4">
              <button
                type="button"
                onClick={() => setSelectedLayout(layout)}
                className="flex items-center gap-1 px-2.5 py-1.5 text-xs bg-white/5 hover:bg-white/10 text-gray-300 rounded-lg transition-colors"
              >
                <Edit3 size={12} /> Edit
              </button>
              {!layout.is_active && (
                <button
                  type="button"
                  onClick={() => handleActivate(layout)}
                  disabled={updateLayout.isPending}
                  className="flex items-center gap-1 px-2.5 py-1.5 text-xs bg-indigo-600/20 hover:bg-indigo-600/30 text-indigo-400 rounded-lg transition-colors disabled:opacity-50"
                >
                  <Check size={12} /> Activate
                </button>
              )}
              <button
                type="button"
                onClick={() => handleDelete(layout)}
                disabled={layout.is_active || deleteLayout.isPending}
                title={layout.is_active ? 'Cannot delete the active layout' : 'Delete layout'}
                className="ml-auto p-1.5 text-gray-600 hover:text-red-400 transition-colors disabled:opacity-30 disabled:cursor-not-allowed"
              >
                <Trash2 size={14} />
              </button>
            </div>
          </div>
        ))}
      </div>

      {/* Configure modal */}
      {selectedLayout && (
        <div className="fixed inset-0 bg-black/60 backdrop-blur-sm z-50 flex items-center justify-center p-4">
          <div className="bg-[#161920] border border-white/10 rounded-xl w-full max-w-lg">
            <div className="flex items-center justify-between p-5 border-b border-white/5">
              <h3 className="font-semibold text-white">Configure: {selectedLayout.name}</h3>
              <button
                type="button"
                onClick={() => setSelectedLayout(null)}
                className="text-gray-400 hover:text-white transition-colors text-lg leading-none"
              >
                ✕
              </button>
            </div>
            <div className="p-5 space-y-3">
              <div className="flex gap-3 text-sm">
                <div className="flex-1 bg-[#0C0E12] border border-white/5 rounded-lg p-3 text-center">
                  <p className="text-gray-500 text-xs mb-1">Left Sidebar</p>
                  <p className="text-white font-medium">
                    {(selectedLayout.left_sidebar_config as any)?.widgets?.length ?? 0} widgets
                  </p>
                </div>
                <div className="flex-1 bg-[#0C0E12] border border-white/5 rounded-lg p-3 text-center">
                  <p className="text-gray-500 text-xs mb-1">Center Feed</p>
                  <p className="text-white font-medium">Main</p>
                </div>
                <div className="flex-1 bg-[#0C0E12] border border-white/5 rounded-lg p-3 text-center">
                  <p className="text-gray-500 text-xs mb-1">Right Sidebar</p>
                  <p className="text-white font-medium">
                    {(selectedLayout.right_sidebar_config as any)?.widgets?.length ?? 0} widgets
                  </p>
                </div>
              </div>
              <p className="text-xs text-gray-600 text-center pt-2">
                Full drag-and-drop editor — coming in Plan 11b
              </p>
            </div>
          </div>
        </div>
      )}
    </div>
  );
}
