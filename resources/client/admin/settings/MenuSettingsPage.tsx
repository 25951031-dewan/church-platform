import { useState, useEffect } from 'react';
import { useMutation, useQuery, useQueryClient } from '@tanstack/react-query';
import { apiClient } from '@app/common/http/api-client';
import { CheckCircle, Plus, GripVertical, Trash2, ChevronDown, ChevronUp, Edit, Link, Menu as MenuIcon } from 'lucide-react';

interface Menu {
  id: string;
  name: string;
  positions: string[];
  items: MenuItem[];
}

interface MenuItem {
  id: string;
  label: string;
  action: string;
  type: 'route' | 'link';
  icon?: string;
  target?: '_blank' | '_self';
}

interface MenuEditorConfig {
  positions: Array<{ name: string; label: string; route: string }>;
  available_routes: Array<{ label: string; route: string; type: string }>;
}

function MenuItemForm({ item, onUpdate, onCancel }: {
  item: Partial<MenuItem>;
  onUpdate: (item: MenuItem) => void;
  onCancel: () => void;
}) {
  const [form, setForm] = useState({
    id: item.id || crypto.randomUUID(),
    label: item.label || '',
    action: item.action || '',
    type: (item.type || 'route') as 'route' | 'link',
    icon: item.icon || '',
    target: (item.target || '_self') as '_blank' | '_self',
  });

  const { data: config } = useQuery<MenuEditorConfig>({
    queryKey: ['menu-editor-config'],
    queryFn: () => apiClient.get('menu-editor-config').then(r => r.data),
  });

  const handleSubmit = () => {
    if (!form.label.trim() || !form.action.trim()) return;
    onUpdate(form as MenuItem);
  };

  return (
    <div className="fixed inset-0 bg-black/60 backdrop-blur-sm flex items-center justify-center z-50">
      <div className="bg-[#161920] border border-white/10 rounded-xl p-6 w-full max-w-md">
        <h3 className="text-lg font-semibold text-white mb-4">
          {item.id ? 'Edit Menu Item' : 'Add Menu Item'}
        </h3>
        
        <div className="space-y-4">
          <div>
            <label className="block text-sm font-medium text-gray-300 mb-2">Label</label>
            <input
              type="text"
              value={form.label}
              onChange={e => setForm(f => ({ ...f, label: e.target.value }))}
              placeholder="Home"
              className="w-full px-3 py-2 bg-[#0C0E12] border border-white/10 rounded-lg text-white text-sm focus:outline-none focus:border-indigo-500"
            />
          </div>
          
          <div>
            <label className="block text-sm font-medium text-gray-300 mb-2">Type</label>
            <select
              value={form.type}
              onChange={e => setForm(f => ({ ...f, type: e.target.value as 'route' | 'link' }))}
              className="w-full px-3 py-2 bg-[#0C0E12] border border-white/10 rounded-lg text-white text-sm focus:outline-none focus:border-indigo-500"
            >
              <option value="route">Route (internal)</option>
              <option value="link">External Link</option>
            </select>
          </div>
          
          <div>
            <label className="block text-sm font-medium text-gray-300 mb-2">
              {form.type === 'route' ? 'Route' : 'URL'}
            </label>
            {form.type === 'route' ? (
              <select
                value={form.action}
                onChange={e => setForm(f => ({ ...f, action: e.target.value }))}
                className="w-full px-3 py-2 bg-[#0C0E12] border border-white/10 rounded-lg text-white text-sm focus:outline-none focus:border-indigo-500"
              >
                <option value="">Select a route...</option>
                {config?.available_routes.map(route => (
                  <option key={route.route} value={route.route}>
                    {route.label} ({route.route})
                  </option>
                ))}
              </select>
            ) : (
              <input
                type="url"
                value={form.action}
                onChange={e => setForm(f => ({ ...f, action: e.target.value }))}
                placeholder="https://example.com"
                className="w-full px-3 py-2 bg-[#0C0E12] border border-white/10 rounded-lg text-white text-sm focus:outline-none focus:border-indigo-500"
              />
            )}
          </div>
          
          {form.type === 'link' && (
            <div>
              <label className="block text-sm font-medium text-gray-300 mb-2">Target</label>
              <select
                value={form.target}
                onChange={e => setForm(f => ({ ...f, target: e.target.value as '_blank' | '_self' }))}
                className="w-full px-3 py-2 bg-[#0C0E12] border border-white/10 rounded-lg text-white text-sm focus:outline-none focus:border-indigo-500"
              >
                <option value="_self">Same window</option>
                <option value="_blank">New window</option>
              </select>
            </div>
          )}
          
          <div>
            <label className="block text-sm font-medium text-gray-300 mb-2">Icon (optional)</label>
            <input
              type="text"
              value={form.icon}
              onChange={e => setForm(f => ({ ...f, icon: e.target.value }))}
              placeholder="home"
              className="w-full px-3 py-2 bg-[#0C0E12] border border-white/10 rounded-lg text-white text-sm focus:outline-none focus:border-indigo-500"
            />
            <p className="text-xs text-gray-500 mt-1">Lucide icon name</p>
          </div>
        </div>
        
        <div className="flex items-center gap-3 mt-6">
          <button
            type="button"
            onClick={handleSubmit}
            disabled={!form.label.trim() || !form.action.trim()}
            className="px-4 py-2 bg-indigo-600 text-white text-sm font-semibold rounded-lg hover:bg-indigo-700 disabled:opacity-50 transition-colors"
          >
            {item.id ? 'Update' : 'Add'} Item
          </button>
          <button
            type="button"
            onClick={onCancel}
            className="px-4 py-2 bg-[#0C0E12] border border-white/10 text-gray-300 text-sm font-semibold rounded-lg hover:border-white/20 transition-colors"
          >
            Cancel
          </button>
        </div>
      </div>
    </div>
  );
}

function MenuAccordion({ menu, onUpdate, onDelete }: {
  menu: Menu;
  onUpdate: (data: Partial<Menu>) => void;
  onDelete: () => void;
}) {
  const [isOpen, setIsOpen] = useState(false);
  const [editingItem, setEditingItem] = useState<MenuItem | null>(null);
  const [showItemForm, setShowItemForm] = useState(false);

  const { data: config } = useQuery<MenuEditorConfig>({
    queryKey: ['menu-editor-config'],
    queryFn: () => apiClient.get('menu-editor-config').then(r => r.data),
  });

  const updateItem = (updatedItem: MenuItem) => {
    const items = editingItem 
      ? menu.items.map(item => item.id === editingItem.id ? updatedItem : item)
      : [...menu.items, updatedItem];
    onUpdate({ items });
    setEditingItem(null);
    setShowItemForm(false);
  };

  const deleteItem = (itemId: string) => {
    onUpdate({ items: menu.items.filter(item => item.id !== itemId) });
  };

  const moveItem = (itemIndex: number, direction: 'up' | 'down') => {
    const newIndex = direction === 'up' ? itemIndex - 1 : itemIndex + 1;
    if (newIndex < 0 || newIndex >= menu.items.length) return;

    const items = [...menu.items];
    [items[itemIndex], items[newIndex]] = [items[newIndex], items[itemIndex]];
    onUpdate({ items });
  };

  const togglePosition = (position: string) => {
    const positions = menu.positions.includes(position)
      ? menu.positions.filter(p => p !== position)
      : [...menu.positions, position];
    onUpdate({ positions });
  };

  return (
    <div className="bg-[#161920] border border-white/5 rounded-xl mb-4">
      <div className="flex items-center gap-3 p-4">
        <button type="button" className="text-gray-500 cursor-move hover:text-white">
          <GripVertical size={16} />
        </button>
        <button
          type="button"
          onClick={() => setIsOpen(!isOpen)}
          className="flex-1 flex items-center justify-between text-left"
        >
          <div>
            <span className="text-sm font-medium text-white">{menu.name}</span>
            <p className="text-xs text-gray-400 mt-0.5">
              {menu.positions.length ? `Active in: ${menu.positions.join(', ')}` : 'No positions assigned'}
            </p>
          </div>
          {isOpen ? <ChevronUp size={16} className="text-gray-400" /> : <ChevronDown size={16} className="text-gray-400" />}
        </button>
        <button
          type="button"
          onClick={onDelete}
          className="text-red-400 hover:text-red-300"
        >
          <Trash2 size={14} />
        </button>
      </div>

      {isOpen && (
        <div className="border-t border-white/5 p-4 space-y-4">
          <div>
            <label className="block text-sm font-medium text-gray-300 mb-2">Menu Name</label>
            <input
              type="text"
              value={menu.name}
              onChange={e => onUpdate({ name: e.target.value })}
              className="w-full px-3 py-2 bg-[#0C0E12] border border-white/10 rounded-lg text-white text-sm focus:outline-none focus:border-indigo-500"
            />
          </div>

          <div>
            <label className="block text-sm font-medium text-gray-300 mb-2">Positions</label>
            <div className="grid grid-cols-2 gap-2">
              {config?.positions.map(position => (
                <label key={position.name} className="flex items-center gap-2 text-sm">
                  <input
                    type="checkbox"
                    checked={menu.positions.includes(position.name)}
                    onChange={() => togglePosition(position.name)}
                    className="w-4 h-4 rounded bg-[#0C0E12] border-white/10"
                  />
                  <span className="text-gray-300">{position.label}</span>
                </label>
              ))}
            </div>
          </div>

          <div>
            <div className="flex items-center justify-between mb-3">
              <label className="text-sm font-medium text-gray-300">Menu Items ({menu.items.length})</label>
              <button
                type="button"
                onClick={() => setShowItemForm(true)}
                className="flex items-center gap-1 px-3 py-1 bg-[#0C0E12] border border-white/10 rounded-lg text-sm text-gray-300 hover:border-indigo-500 transition-colors"
              >
                <Plus size={12} />
                Add Item
              </button>
            </div>
            
            {menu.items.length === 0 ? (
              <div className="text-center py-4 text-gray-500 text-sm">No menu items yet</div>
            ) : (
              <div className="space-y-2">
                {menu.items.map((item, index) => (
                  <div key={item.id} className="flex items-center gap-3 p-3 bg-[#0C0E12] border border-white/10 rounded-lg">
                    <button type="button" className="text-gray-500 cursor-move hover:text-white">
                      <GripVertical size={14} />
                    </button>
                    <div className="flex-1">
                      <div className="flex items-center gap-2">
                        {item.type === 'link' ? <Link size={12} /> : <MenuIcon size={12} />}
                        <span className="text-sm text-white">{item.label}</span>
                      </div>
                      <p className="text-xs text-gray-400 mt-0.5">{item.action}</p>
                    </div>
                    <div className="flex items-center gap-1">
                      <button
                        type="button"
                        onClick={() => moveItem(index, 'up')}
                        disabled={index === 0}
                        className="text-xs text-gray-400 hover:text-white disabled:opacity-30 px-1"
                      >
                        ↑
                      </button>
                      <button
                        type="button"
                        onClick={() => moveItem(index, 'down')}
                        disabled={index === menu.items.length - 1}
                        className="text-xs text-gray-400 hover:text-white disabled:opacity-30 px-1"
                      >
                        ↓
                      </button>
                      <button
                        type="button"
                        onClick={() => setEditingItem(item)}
                        className="text-gray-400 hover:text-white"
                      >
                        <Edit size={12} />
                      </button>
                      <button
                        type="button"
                        onClick={() => deleteItem(item.id)}
                        className="text-red-400 hover:text-red-300"
                      >
                        <Trash2 size={12} />
                      </button>
                    </div>
                  </div>
                ))}
              </div>
            )}
          </div>
        </div>
      )}

      {(showItemForm || editingItem) && (
        <MenuItemForm
          item={editingItem || {}}
          onUpdate={updateItem}
          onCancel={() => {
            setShowItemForm(false);
            setEditingItem(null);
          }}
        />
      )}
    </div>
  );
}

export function MenuSettingsPage() {
  const qc = useQueryClient();
  const [menus, setMenus] = useState<Menu[]>([]);
  const [saved, setSaved] = useState(false);

  // Load existing menus from client.menus setting
  const { data, isLoading } = useQuery({
    queryKey: ['settings'],
    queryFn: () => apiClient.get<{ settings: Record<string, string> }>('settings').then(r => {
      const menusJson = r.data.settings['client.menus'] || '[]';
      try {
        return JSON.parse(menusJson) as Menu[];
      } catch {
        return [];
      }
    }),
  });

  useEffect(() => {
    if (data) setMenus(data);
  }, [data]);

  const mutation = useMutation({
    mutationFn: (values: Menu[]) => {
      const settings = { 'client.menus': JSON.stringify(values) };
      return apiClient.put('settings', { settings });
    },
    onSuccess: () => {
      qc.invalidateQueries({ queryKey: ['settings'] });
      setSaved(true);
      setTimeout(() => setSaved(false), 3000);
    },
  });

  const addMenu = () => {
    const newMenu: Menu = {
      id: crypto.randomUUID(),
      name: 'New Menu',
      positions: [],
      items: [],
    };
    setMenus([...menus, newMenu]);
  };

  const updateMenu = (index: number, data: Partial<Menu>) => {
    const updated = [...menus];
    updated[index] = { ...updated[index], ...data };
    setMenus(updated);
  };

  const deleteMenu = (index: number) => {
    setMenus(menus.filter((_, i) => i !== index));
  };

  const handleSave = () => {
    mutation.mutate(menus);
  };

  if (isLoading) return <div className="text-gray-400 text-sm">Loading…</div>;

  return (
    <div className="max-w-4xl">
      <div className="flex items-center justify-between mb-6">
        <h2 className="text-lg font-semibold text-white">Menu Customizer</h2>
        <div className="flex items-center gap-3">
          {saved && <span className="flex items-center gap-1 text-xs text-green-400"><CheckCircle size={13} /> Saved</span>}
          <button
            type="button"
            onClick={handleSave}
            disabled={mutation.isPending}
            className="px-4 py-2 bg-indigo-600 text-white text-sm font-semibold rounded-lg hover:bg-indigo-700 disabled:opacity-50 transition-colors"
          >
            {mutation.isPending ? 'Saving…' : 'Save changes'}
          </button>
        </div>
      </div>

      {mutation.isError && (
        <div className="mb-4 p-3 bg-red-500/10 border border-red-500/20 rounded-lg text-red-400 text-sm">
          Failed to save menus. Please try again.
        </div>
      )}

      <div className="mb-4">
        <button
          type="button"
          onClick={addMenu}
          className="flex items-center gap-2 px-4 py-2 bg-[#161920] border border-white/10 rounded-lg text-sm text-white hover:border-indigo-500 transition-colors"
        >
          <Plus size={14} />
          Add Menu
        </button>
      </div>

      {menus.length === 0 ? (
        <div className="bg-[#161920] border border-white/5 rounded-xl p-8 text-center">
          <p className="text-gray-400 text-sm mb-2">No menus configured</p>
          <p className="text-gray-500 text-xs">Click "Add Menu" to create your first menu</p>
        </div>
      ) : (
        <div>
          {menus.map((menu, index) => (
            <MenuAccordion
              key={menu.id}
              menu={menu}
              onUpdate={data => updateMenu(index, data)}
              onDelete={() => deleteMenu(index)}
            />
          ))}
        </div>
      )}
    </div>
  );
}