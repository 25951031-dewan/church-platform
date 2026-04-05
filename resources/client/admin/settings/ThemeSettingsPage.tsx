import { useState, useEffect } from 'react';
import { useMutation, useQuery, useQueryClient } from '@tanstack/react-query';
import { apiClient } from '@app/common/http/api-client';
import { CheckCircle, Plus, Edit, Trash2, Eye, Palette as PaletteIcon, ChevronDown, ChevronUp } from 'lucide-react';

interface CssTheme {
  id: number;
  name: string;
  is_dark: boolean;
  default_dark: boolean;
  default_light: boolean;
  values: Record<string, string>;
  font?: { family: string; google: boolean };
}

const themeColorList = [
  { key: '--be-bg', label: 'Background', description: 'Main page background' },
  { key: '--be-bg-alt', label: 'Background Alt', description: 'Card and panel background' },
  { key: '--be-text', label: 'Text', description: 'Primary text color' },
  { key: '--be-text-muted', label: 'Text Muted', description: 'Secondary text color' },
  { key: '--be-primary', label: 'Primary', description: 'Accent color for buttons and links' },
  { key: '--be-primary-light', label: 'Primary Light', description: 'Light variant of primary color' },
  { key: '--be-danger', label: 'Danger', description: 'Error and warning color' },
];

const radiusOptions = [
  { label: 'None', value: '0' },
  { label: 'Small', value: '0.25rem' },
  { label: 'Medium', value: '0.5rem' },
  { label: 'Large', value: '0.75rem' },
  { label: 'Extra Large', value: '1rem' },
];

function ColorPicker({ color, onChange }: {
  color: string;
  onChange: (color: string) => void;
}) {
  const [isOpen, setIsOpen] = useState(false);
  const [inputValue, setInputValue] = useState(color);

  useEffect(() => {
    setInputValue(color);
  }, [color]);

  const handleChange = (newColor: string) => {
    setInputValue(newColor);
    onChange(newColor);
  };

  return (
    <div className="relative">
      <button
        type="button"
        onClick={() => setIsOpen(!isOpen)}
        className="flex items-center gap-2 px-3 py-2 bg-[#0C0E12] border border-white/10 rounded-lg hover:border-indigo-500 transition-colors"
      >
        <div
          className="w-4 h-4 rounded border border-white/20"
          style={{ backgroundColor: color }}
        />
        <span className="text-sm text-gray-300 font-mono">{color}</span>
      </button>
      
      {isOpen && (
        <div className="absolute top-full mt-1 left-0 bg-[#161920] border border-white/10 rounded-lg p-3 z-50 min-w-48">
          <input
            type="color"
            value={color}
            onChange={e => handleChange(e.target.value)}
            className="w-full h-8 rounded border border-white/10 mb-2 cursor-pointer"
          />
          <input
            type="text"
            value={inputValue}
            onChange={e => setInputValue(e.target.value)}
            onBlur={e => handleChange(e.target.value)}
            onKeyDown={e => e.key === 'Enter' && handleChange(inputValue)}
            placeholder="#000000"
            className="w-full px-2 py-1 bg-[#0C0E12] border border-white/10 rounded text-white text-sm font-mono"
          />
          <div className="flex justify-between mt-2">
            <button
              type="button"
              onClick={() => setIsOpen(false)}
              className="px-2 py-1 text-xs text-gray-400 hover:text-white"
            >
              Close
            </button>
          </div>
        </div>
      )}
    </div>
  );
}

function RadiusSelector({ label, value, onChange }: {
  label: string;
  value: string;
  onChange: (value: string) => void;
}) {
  return (
    <div>
      <label className="block text-sm font-medium text-gray-300 mb-2">{label}</label>
      <div className="grid grid-cols-5 gap-2">
        {radiusOptions.map(option => (
          <button
            key={option.value}
            type="button"
            onClick={() => onChange(option.value)}
            className={`px-3 py-2 text-xs rounded border-2 transition-colors ${
              value === option.value
                ? 'border-indigo-500 bg-indigo-500/10 text-indigo-400'
                : 'border-white/10 hover:border-white/20 text-gray-300'
            }`}
            style={{ borderRadius: option.value }}
          >
            {option.label}
          </button>
        ))}
      </div>
    </div>
  );
}

function ThemeForm({ theme, onSubmit, onCancel }: {
  theme?: CssTheme;
  onSubmit: (data: Partial<CssTheme>) => void;
  onCancel: () => void;
}) {
  const [form, setForm] = useState({
    name: theme?.name || '',
    is_dark: theme?.is_dark ?? true,
    values: {
      '--be-bg': '#0C0E12',
      '--be-bg-alt': '#161920',
      '--be-text': '#ffffff',
      '--be-text-muted': '#9ca3af',
      '--be-primary': '#6366f1',
      '--be-primary-light': '#818cf8',
      '--be-danger': '#ef4444',
      '--be-button-radius': '0.5rem',
      '--be-input-radius': '0.375rem',
      '--be-panel-radius': '0.75rem',
      ...theme?.values,
    },
  });

  const updateColor = (key: string, color: string) => {
    setForm(f => ({
      ...f,
      values: { ...f.values, [key]: color },
    }));
  };

  const updateRadius = (key: string, radius: string) => {
    setForm(f => ({
      ...f,
      values: { ...f.values, [key]: radius },
    }));
  };

  const handleSubmit = () => {
    if (!form.name.trim()) return;
    onSubmit({
      id: theme?.id,
      name: form.name,
      is_dark: form.is_dark,
      values: form.values,
    });
  };

  return (
    <div className="fixed inset-0 bg-black/60 backdrop-blur-sm flex items-center justify-center z-50 p-4">
      <div className="bg-[#161920] border border-white/10 rounded-xl p-6 w-full max-w-2xl max-h-[90vh] overflow-y-auto">
        <h3 className="text-lg font-semibold text-white mb-4">
          {theme ? 'Edit Theme' : 'Create Theme'}
        </h3>
        
        <div className="space-y-6">
          <div className="grid grid-cols-2 gap-4">
            <div>
              <label className="block text-sm font-medium text-gray-300 mb-2">Theme Name</label>
              <input
                type="text"
                value={form.name}
                onChange={e => setForm(f => ({ ...f, name: e.target.value }))}
                placeholder="Dark Theme"
                className="w-full px-3 py-2 bg-[#0C0E12] border border-white/10 rounded-lg text-white text-sm focus:outline-none focus:border-indigo-500"
              />
            </div>
            <div>
              <label className="block text-sm font-medium text-gray-300 mb-2">Type</label>
              <select
                value={form.is_dark ? 'dark' : 'light'}
                onChange={e => setForm(f => ({ ...f, is_dark: e.target.value === 'dark' }))}
                className="w-full px-3 py-2 bg-[#0C0E12] border border-white/10 rounded-lg text-white text-sm focus:outline-none focus:border-indigo-500"
              >
                <option value="dark">Dark Theme</option>
                <option value="light">Light Theme</option>
              </select>
            </div>
          </div>

          <div>
            <h4 className="text-sm font-semibold text-white mb-3">Colors</h4>
            <div className="grid grid-cols-1 gap-4">
              {themeColorList.map(color => (
                <div key={color.key} className="flex items-center gap-4">
                  <div className="w-32">
                    <p className="text-sm text-white">{color.label}</p>
                    <p className="text-xs text-gray-500">{color.description}</p>
                  </div>
                  <ColorPicker
                    color={form.values[color.key] || '#000000'}
                    onChange={color => updateColor(color.key, color)}
                  />
                </div>
              ))}
            </div>
          </div>

          <div>
            <h4 className="text-sm font-semibold text-white mb-3">Border Radius</h4>
            <div className="space-y-4">
              <RadiusSelector
                label="Button Radius"
                value={form.values['--be-button-radius'] || '0.5rem'}
                onChange={value => updateRadius('--be-button-radius', value)}
              />
              <RadiusSelector
                label="Input Radius"
                value={form.values['--be-input-radius'] || '0.375rem'}
                onChange={value => updateRadius('--be-input-radius', value)}
              />
              <RadiusSelector
                label="Panel Radius"
                value={form.values['--be-panel-radius'] || '0.75rem'}
                onChange={value => updateRadius('--be-panel-radius', value)}
              />
            </div>
          </div>
        </div>
        
        <div className="flex items-center gap-3 mt-6 pt-4 border-t border-white/10">
          <button
            type="button"
            onClick={handleSubmit}
            disabled={!form.name.trim()}
            className="px-4 py-2 bg-indigo-600 text-white text-sm font-semibold rounded-lg hover:bg-indigo-700 disabled:opacity-50 transition-colors"
          >
            {theme ? 'Update' : 'Create'} Theme
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

export function ThemeSettingsPage() {
  const qc = useQueryClient();
  const [saved, setSaved] = useState(false);
  const [showForm, setShowForm] = useState(false);
  const [editingTheme, setEditingTheme] = useState<CssTheme | null>(null);

  // Load settings
  const { data: settings, isLoading: settingsLoading } = useQuery({
    queryKey: ['settings'],
    queryFn: () => apiClient.get<{ settings: Record<string, string> }>('settings').then(r => r.data.settings),
  });

  // Load themes
  const { data: themes = [], isLoading: themesLoading } = useQuery({
    queryKey: ['themes'],
    queryFn: () => apiClient.get<{ data: CssTheme[] }>('themes').then(r => r.data.data),
  });

  // Update settings mutation
  const updateSettingsMutation = useMutation({
    mutationFn: (values: Record<string, string>) => {
      return apiClient.put('settings', { settings: values });
    },
    onSuccess: () => {
      qc.invalidateQueries({ queryKey: ['settings'] });
      setSaved(true);
      setTimeout(() => setSaved(false), 3000);
    },
  });

  // Create/Update theme mutation
  const saveThemeMutation = useMutation({
    mutationFn: (theme: Partial<CssTheme>) => {
      if (theme.id) {
        return apiClient.put(`themes/${theme.id}`, theme);
      } else {
        return apiClient.post('themes', theme);
      }
    },
    onSuccess: () => {
      qc.invalidateQueries({ queryKey: ['themes'] });
      setShowForm(false);
      setEditingTheme(null);
    },
  });

  // Delete theme mutation
  const deleteThemeMutation = useMutation({
    mutationFn: (themeId: number) => apiClient.delete(`themes/${themeId}`),
    onSuccess: () => {
      qc.invalidateQueries({ queryKey: ['themes'] });
    },
  });

  const handleThemeSubmit = (data: Partial<CssTheme>) => {
    saveThemeMutation.mutate(data);
  };

  const setDefault = (themeId: number, type: 'dark' | 'light') => {
    const key = type === 'dark' ? 'client.themes.default_dark_id' : 'client.themes.default_light_id';
    updateSettingsMutation.mutate({ [key]: themeId.toString() });
  };

  const toggleUserThemeChange = (enabled: boolean) => {
    updateSettingsMutation.mutate({ 'client.themes.user_change': enabled.toString() });
  };

  if (settingsLoading || themesLoading) {
    return <div className="text-gray-400 text-sm">Loading…</div>;
  }

  const userCanChangeThemes = settings?.['client.themes.user_change'] === 'true';
  const defaultDarkId = parseInt(settings?.['client.themes.default_dark_id'] || '0') || null;
  const defaultLightId = parseInt(settings?.['client.themes.default_light_id'] || '0') || null;

  return (
    <div className="max-w-4xl">
      <div className="flex items-center justify-between mb-6">
        <h2 className="text-lg font-semibold text-white">Theme Settings</h2>
        <div className="flex items-center gap-3">
          {saved && <span className="flex items-center gap-1 text-xs text-green-400"><CheckCircle size={13} /> Saved</span>}
          <button
            type="button"
            onClick={() => setShowForm(true)}
            className="px-4 py-2 bg-indigo-600 text-white text-sm font-semibold rounded-lg hover:bg-indigo-700 transition-colors"
          >
            Create Theme
          </button>
        </div>
      </div>

      {(updateSettingsMutation.isError || saveThemeMutation.isError || deleteThemeMutation.isError) && (
        <div className="mb-4 p-3 bg-red-500/10 border border-red-500/20 rounded-lg text-red-400 text-sm">
          Failed to save changes. Please try again.
        </div>
      )}

      {/* Global Theme Settings */}
      <div className="bg-[#161920] border border-white/5 rounded-xl p-5 mb-6">
        <h3 className="text-base font-semibold text-white mb-4">Global Settings</h3>
        <div className="space-y-4">
          <div className="flex items-center justify-between">
            <div>
              <p className="text-sm text-white">Allow users to change themes</p>
              <p className="text-xs text-gray-400 mt-0.5">Users can switch between light and dark themes</p>
            </div>
            <button
              type="button"
              onClick={() => toggleUserThemeChange(!userCanChangeThemes)}
              disabled={updateSettingsMutation.isPending}
              className={`relative inline-flex h-5 w-9 items-center rounded-full transition-colors ${
                userCanChangeThemes ? 'bg-indigo-600' : 'bg-gray-600'
              }`}
            >
              <span className={`inline-block h-3 w-3 transform rounded-full bg-white transition-transform ${
                userCanChangeThemes ? 'translate-x-5' : 'translate-x-1'
              }`} />
            </button>
          </div>
        </div>
      </div>

      {/* Theme List */}
      <div className="bg-[#161920] border border-white/5 rounded-xl p-5">
        <h3 className="text-base font-semibold text-white mb-4">Available Themes</h3>
        
        {themes.length === 0 ? (
          <div className="text-center py-8 text-gray-400 text-sm">
            <PaletteIcon size={32} className="mx-auto mb-2 opacity-50" />
            <p className="mb-1">No custom themes created yet</p>
            <p className="text-xs text-gray-500">Click "Create Theme" to get started</p>
          </div>
        ) : (
          <div className="space-y-3">
            {themes.map(theme => (
              <div key={theme.id} className="flex items-center gap-4 p-4 bg-[#0C0E12] border border-white/10 rounded-lg">
                <div className="flex items-center gap-3 flex-1">
                  <div
                    className="w-8 h-8 rounded-lg border border-white/20"
                    style={{ backgroundColor: theme.values['--be-primary'] || '#6366f1' }}
                  />
                  <div>
                    <p className="text-sm font-medium text-white">{theme.name}</p>
                    <div className="flex items-center gap-2 mt-0.5">
                      <span className={`px-2 py-0.5 text-xs rounded ${
                        theme.is_dark 
                          ? 'bg-gray-700 text-gray-300' 
                          : 'bg-yellow-500/20 text-yellow-400'
                      }`}>
                        {theme.is_dark ? 'Dark' : 'Light'}
                      </span>
                      {((theme.is_dark && defaultDarkId === theme.id) || (!theme.is_dark && defaultLightId === theme.id)) && (
                        <span className="px-2 py-0.5 text-xs bg-indigo-500/20 text-indigo-400 rounded">
                          Default
                        </span>
                      )}
                    </div>
                  </div>
                </div>
                
                <div className="flex items-center gap-2">
                  <button
                    type="button"
                    onClick={() => setDefault(theme.id, theme.is_dark ? 'dark' : 'light')}
                    disabled={updateSettingsMutation.isPending}
                    className="px-3 py-1 text-xs border border-white/10 text-gray-300 rounded hover:border-indigo-500 hover:text-indigo-400 transition-colors disabled:opacity-50"
                  >
                    Set as Default
                  </button>
                  <button
                    type="button"
                    onClick={() => setEditingTheme(theme)}
                    className="text-gray-400 hover:text-white"
                  >
                    <Edit size={14} />
                  </button>
                  <button
                    type="button"
                    onClick={() => deleteThemeMutation.mutate(theme.id)}
                    disabled={deleteThemeMutation.isPending || 
                      (theme.is_dark && defaultDarkId === theme.id) || 
                      (!theme.is_dark && defaultLightId === theme.id)}
                    className="text-red-400 hover:text-red-300 disabled:opacity-30 disabled:cursor-not-allowed"
                  >
                    <Trash2 size={14} />
                  </button>
                </div>
              </div>
            ))}
          </div>
        )}
      </div>

      {(showForm || editingTheme) && (
        <ThemeForm
          theme={editingTheme || undefined}
          onSubmit={handleThemeSubmit}
          onCancel={() => {
            setShowForm(false);
            setEditingTheme(null);
          }}
        />
      )}
    </div>
  );
}