import { useState, useEffect } from 'react';
import { useMutation, useQuery, useQueryClient } from '@tanstack/react-query';
import { apiClient } from '@app/common/http/api-client';
import { Palette, Upload } from 'lucide-react';

interface AppearanceData {
  logo?: string;
  banner?: string;
  favicon?: string;
  primary_color?: string;
  secondary_color?: string;
}

export function AppearanceSettingsPage() {
  const queryClient = useQueryClient();
  const [logoFile, setLogoFile] = useState<File | null>(null);
  const [bannerFile, setBannerFile] = useState<File | null>(null);
  const [faviconFile, setFaviconFile] = useState<File | null>(null);

  const { data, isLoading } = useQuery({
    queryKey: ['settings', 'appearance'],
    queryFn: () => apiClient.get<{ data: AppearanceData }>('settings').then((r) => r.data.data),
  });

  const [primaryColor, setPrimaryColor] = useState('#6366f1');
  const [secondaryColor, setSecondaryColor] = useState('#8b5cf6');

  useEffect(() => {
    if (data) {
      setPrimaryColor(data.primary_color || '#6366f1');
      setSecondaryColor(data.secondary_color || '#8b5cf6');
    }
  }, [data]);

  const save = useMutation({
    mutationFn: async (formData: FormData) => apiClient.put('settings', formData, {
      headers: { 'Content-Type': 'multipart/form-data' },
    }),
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: ['settings', 'appearance'] });
      setLogoFile(null);
      setBannerFile(null);
      setFaviconFile(null);
    },
  });

  const handleSave = () => {
    const formData = new FormData();
    if (logoFile) formData.append('logo', logoFile);
    if (bannerFile) formData.append('banner', bannerFile);
    if (faviconFile) formData.append('favicon', faviconFile);
    formData.append('primary_color', primaryColor);
    formData.append('secondary_color', secondaryColor);
    save.mutate(formData);
  };

  if (isLoading) return <div className="text-gray-400 text-sm">Loading...</div>;

  return (
    <div className="max-w-3xl">
      <div className="mb-6">
        <h2 className="text-xl font-bold text-white mb-1">Appearance Settings</h2>
        <p className="text-sm text-gray-400">Customize your church's branding and theme colors.</p>
      </div>

      {/* Logos & Images */}
      <div className="bg-[#161920] border border-white/5 rounded-xl p-5 mb-4">
        <h3 className="text-sm font-semibold text-white mb-4 flex items-center gap-2">
          <Upload size={16} />
          Logos & Images
        </h3>
        
        <div className="space-y-4">
          <div>
            <label className="block text-sm font-medium text-gray-300 mb-2">Church Logo</label>
            {data?.logo && <img src={data.logo} alt="Logo" className="w-32 h-32 object-contain mb-2 bg-white/5 rounded p-2" />}
            <input
              type="file"
              accept="image/jpeg,image/png,image/jpg,image/gif,image/svg+xml,image/webp"
              onChange={(e) => setLogoFile(e.target.files?.[0] || null)}
              className="block w-full text-sm text-gray-400 file:mr-4 file:py-2 file:px-4 file:rounded-lg file:border-0 file:text-sm file:bg-indigo-600 file:text-white hover:file:bg-indigo-700 file:cursor-pointer"
            />
            <p className="text-xs text-gray-500 mt-1">Max 5MB. Formats: JPEG, PNG, GIF, SVG, WebP</p>
          </div>

          <div>
            <label className="block text-sm font-medium text-gray-300 mb-2">Banner Image</label>
            {data?.banner && <img src={data.banner} alt="Banner" className="w-full h-32 object-cover mb-2 rounded" />}
            <input
              type="file"
              accept="image/jpeg,image/png,image/jpg,image/gif,image/webp"
              onChange={(e) => setBannerFile(e.target.files?.[0] || null)}
              className="block w-full text-sm text-gray-400 file:mr-4 file:py-2 file:px-4 file:rounded-lg file:border-0 file:text-sm file:bg-indigo-600 file:text-white hover:file:bg-indigo-700 file:cursor-pointer"
            />
            <p className="text-xs text-gray-500 mt-1">Max 10MB. Formats: JPEG, PNG, GIF, WebP</p>
          </div>

          <div>
            <label className="block text-sm font-medium text-gray-300 mb-2">Favicon</label>
            {data?.favicon && <img src={data.favicon} alt="Favicon" className="w-8 h-8 mb-2" />}
            <input
              type="file"
              accept="image/x-icon,image/png"
              onChange={(e) => setFaviconFile(e.target.files?.[0] || null)}
              className="block w-full text-sm text-gray-400 file:mr-4 file:py-2 file:px-4 file:rounded-lg file:border-0 file:text-sm file:bg-indigo-600 file:text-white hover:file:bg-indigo-700 file:cursor-pointer"
            />
            <p className="text-xs text-gray-500 mt-1">Max 1MB. Formats: ICO, PNG</p>
          </div>
        </div>
      </div>

      {/* Theme Colors */}
      <div className="bg-[#161920] border border-white/5 rounded-xl p-5 mb-4">
        <h3 className="text-sm font-semibold text-white mb-4 flex items-center gap-2">
          <Palette size={16} />
          Theme Colors
        </h3>
        <div className="grid grid-cols-2 gap-4">
          <div>
            <label className="block text-sm font-medium text-gray-300 mb-2">Primary Color</label>
            <div className="flex gap-2">
              <input
                type="color"
                value={primaryColor}
                onChange={(e) => setPrimaryColor(e.target.value)}
                className="w-12 h-10 rounded border border-white/10 cursor-pointer"
              />
              <input
                type="text"
                value={primaryColor}
                onChange={(e) => setPrimaryColor(e.target.value)}
                className="flex-1 px-3 py-2 bg-[#0C0E12] border border-white/10 rounded-lg text-white text-sm focus:outline-none focus:border-indigo-500"
              />
            </div>
          </div>
          <div>
            <label className="block text-sm font-medium text-gray-300 mb-2">Secondary Color</label>
            <div className="flex gap-2">
              <input
                type="color"
                value={secondaryColor}
                onChange={(e) => setSecondaryColor(e.target.value)}
                className="w-12 h-10 rounded border border-white/10 cursor-pointer"
              />
              <input
                type="text"
                value={secondaryColor}
                onChange={(e) => setSecondaryColor(e.target.value)}
                className="flex-1 px-3 py-2 bg-[#0C0E12] border border-white/10 rounded-lg text-white text-sm focus:outline-none focus:border-indigo-500"
              />
            </div>
          </div>
        </div>
      </div>

      {/* Save Button */}
      <div className="flex items-center justify-between">
        <div className="text-xs text-gray-500">
          {save.isSuccess && <span className="text-green-400">✓ Settings saved successfully</span>}
          {save.isError && <span className="text-red-400">✗ Failed to save settings</span>}
        </div>
        <button
          type="button"
          onClick={handleSave}
          disabled={save.isPending}
          className="px-5 py-2.5 bg-indigo-600 hover:bg-indigo-700 text-white text-sm font-medium rounded-lg transition-colors disabled:opacity-50"
        >
          {save.isPending ? 'Saving...' : 'Save Settings'}
        </button>
      </div>
    </div>
  );
}
