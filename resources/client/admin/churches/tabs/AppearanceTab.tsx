import React, { useState } from 'react';
import { useMutation, useQueryClient } from '@tanstack/react-query';
import { toast } from 'react-hot-toast';
import { FiImage, FiUpload, FiSettings } from 'react-icons/fi';

import { Button } from '@ui/button';
import { Label } from '@ui/label';
import { Input } from '@ui/input';

interface AppearanceTabProps {
  church: any;
  churchId: string;
}

export default function AppearanceTab({ church, churchId }: AppearanceTabProps) {
  const queryClient = useQueryClient();
  const [colors, setColors] = useState({
    primary_color: church.primary_color || '#4F46E5',
    secondary_color: church.secondary_color || '',
  });

  const [uploadingLogo, setUploadingLogo] = useState(false);
  const [uploadingCover, setUploadingCover] = useState(false);

  const updateColorsMutation = useMutation({
    mutationFn: async (data: any) => {
      const response = await fetch(`/api/churches/${churchId}/website/appearance`, {
        method: 'PUT',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify(data),
      });
      if (!response.ok) throw new Error('Failed to update appearance');
      return response.json();
    },
    onSuccess: () => {
      toast.success('Colors updated successfully');
      queryClient.invalidateQueries({ queryKey: ['church-website', churchId] });
    },
    onError: () => {
      toast.error('Failed to update colors');
    },
  });

  const uploadLogoMutation = useMutation({
    mutationFn: async (file: File) => {
      const formData = new FormData();
      formData.append('logo', file);

      const response = await fetch(`/api/churches/${churchId}/website/logo`, {
        method: 'POST',
        body: formData,
      });
      if (!response.ok) throw new Error('Failed to upload logo');
      return response.json();
    },
    onSuccess: () => {
      toast.success('Logo uploaded successfully');
      queryClient.invalidateQueries({ queryKey: ['church-website', churchId] });
    },
    onError: () => {
      toast.error('Failed to upload logo');
    },
    onSettled: () => {
      setUploadingLogo(false);
    },
  });

  const uploadCoverMutation = useMutation({
    mutationFn: async (file: File) => {
      const formData = new FormData();
      formData.append('cover_photo', file);

      const response = await fetch(`/api/churches/${churchId}/website/cover`, {
        method: 'POST',
        body: formData,
      });
      if (!response.ok) throw new Error('Failed to upload cover photo');
      return response.json();
    },
    onSuccess: () => {
      toast.success('Cover photo uploaded successfully');
      queryClient.invalidateQueries({ queryKey: ['church-website', churchId] });
    },
    onError: () => {
      toast.error('Failed to upload cover photo');
    },
    onSettled: () => {
      setUploadingCover(false);
    },
  });

  const handleColorSubmit = (e: React.FormEvent) => {
    e.preventDefault();
    updateColorsMutation.mutate(colors);
  };

  const handleColorChange = (field: string, value: string) => {
    setColors(prev => ({ ...prev, [field]: value }));
  };

  const handleLogoUpload = (e: React.ChangeEvent<HTMLInputElement>) => {
    const file = e.target.files?.[0];
    if (!file) return;

    if (file.size > 2 * 1024 * 1024) { // 2MB limit
      toast.error('Logo must be smaller than 2MB');
      return;
    }

    const allowedTypes = ['image/png', 'image/jpeg', 'image/webp'];
    if (!allowedTypes.includes(file.type)) {
      toast.error('Only PNG, JPG, and WebP images are allowed');
      return;
    }

    setUploadingLogo(true);
    uploadLogoMutation.mutate(file);
  };

  const handleCoverUpload = (e: React.ChangeEvent<HTMLInputElement>) => {
    const file = e.target.files?.[0];
    if (!file) return;

    if (file.size > 5 * 1024 * 1024) { // 5MB limit
      toast.error('Cover photo must be smaller than 5MB');
      return;
    }

    const allowedTypes = ['image/png', 'image/jpeg', 'image/webp'];
    if (!allowedTypes.includes(file.type)) {
      toast.error('Only PNG, JPG, and WebP images are allowed');
      return;
    }

    setUploadingCover(true);
    uploadCoverMutation.mutate(file);
  };

  const PRESET_COLORS = [
    '#4F46E5', // Indigo
    '#7C3AED', // Violet
    '#DC2626', // Red
    '#059669', // Emerald
    '#0284C7', // Sky
    '#CA8A04', // Yellow
    '#9333EA', // Purple
    '#1F2937', // Gray
  ];

  return (
    <div className="space-y-8">
      {/* Logo Upload */}
      <div className="space-y-4">
        <h3 className="text-lg font-medium text-gray-900">Church Logo</h3>
        
        <div className="flex items-start space-x-6">
          <div className="flex-shrink-0">
            <div className="w-32 h-32 border-2 border-dashed border-gray-300 rounded-lg flex items-center justify-center overflow-hidden">
              {church.logo_url ? (
                <img 
                  src={church.logo_url} 
                  alt="Church logo"
                  className="w-full h-full object-contain"
                />
              ) : (
                <div className="text-center">
                  <FiImage className="w-8 h-8 mx-auto text-gray-400 mb-2" />
                  <span className="text-sm text-gray-500">No logo</span>
                </div>
              )}
            </div>
          </div>
          
          <div className="flex-1 space-y-3">
            <div>
              <Label htmlFor="logo_upload">Upload New Logo</Label>
              <Input
                id="logo_upload"
                type="file"
                accept="image/png,image/jpeg,image/webp"
                onChange={handleLogoUpload}
                disabled={uploadingLogo}
              />
              {uploadingLogo && (
                <div className="flex items-center text-sm text-gray-600 mt-2">
                  <div className="animate-spin rounded-full h-4 w-4 border-b-2 border-primary mr-2" />
                  Uploading...
                </div>
              )}
            </div>
            <p className="text-sm text-gray-500">
              Recommended: Square image, 200x200px or larger. PNG, JPG, or WebP. Max 2MB.
            </p>
          </div>
        </div>
      </div>

      {/* Cover Photo Upload */}
      <div className="space-y-4">
        <h3 className="text-lg font-medium text-gray-900">Cover Photo</h3>
        
        <div className="space-y-4">
          <div className="w-full h-48 border-2 border-dashed border-gray-300 rounded-lg flex items-center justify-center overflow-hidden">
            {church.cover_photo_url ? (
              <img 
                src={church.cover_photo_url} 
                alt="Church cover"
                className="w-full h-full object-cover"
              />
            ) : (
              <div className="text-center">
                <FiImage className="w-12 h-12 mx-auto text-gray-400 mb-3" />
                <span className="text-gray-500">No cover photo</span>
              </div>
            )}
          </div>
          
          <div className="space-y-3">
            <div>
              <Label htmlFor="cover_upload">Upload New Cover Photo</Label>
              <Input
                id="cover_upload"
                type="file"
                accept="image/png,image/jpeg,image/webp"
                onChange={handleCoverUpload}
                disabled={uploadingCover}
              />
              {uploadingCover && (
                <div className="flex items-center text-sm text-gray-600 mt-2">
                  <div className="animate-spin rounded-full h-4 w-4 border-b-2 border-primary mr-2" />
                  Uploading...
                </div>
              )}
            </div>
            <p className="text-sm text-gray-500">
              Recommended: Wide landscape image, 1200x400px or larger. PNG, JPG, or WebP. Max 5MB.
            </p>
          </div>
        </div>
      </div>

      {/* Brand Colors */}
      <form onSubmit={handleColorSubmit} className="space-y-6">
        <h3 className="text-lg font-medium text-gray-900">Brand Colors</h3>
        
        <div className="space-y-6">
          <div>
            <Label htmlFor="primary_color">Primary Color *</Label>
            <div className="space-y-3">
              <div className="flex items-center space-x-3">
                <div 
                  className="w-12 h-12 rounded-lg border border-gray-300 shadow-sm"
                  style={{ backgroundColor: colors.primary_color }}
                />
                <Input
                  id="primary_color"
                  type="color"
                  value={colors.primary_color}
                  onChange={(e) => handleColorChange('primary_color', e.target.value)}
                  className="w-20 h-12"
                />
                <Input
                  value={colors.primary_color}
                  onChange={(e) => handleColorChange('primary_color', e.target.value)}
                  placeholder="#4F46E5"
                  className="font-mono"
                />
              </div>
              
              {/* Preset Colors */}
              <div>
                <p className="text-sm text-gray-600 mb-2">Quick picks:</p>
                <div className="flex items-center space-x-2">
                  {PRESET_COLORS.map(color => (
                    <button
                      key={color}
                      type="button"
                      onClick={() => handleColorChange('primary_color', color)}
                      className={`w-8 h-8 rounded-md border-2 ${
                        colors.primary_color === color 
                          ? 'border-gray-900 ring-2 ring-gray-300' 
                          : 'border-gray-300 hover:border-gray-400'
                      }`}
                      style={{ backgroundColor: color }}
                      title={color}
                    />
                  ))}
                </div>
              </div>
            </div>
            <p className="text-sm text-gray-500 mt-2">
              This color will be used for buttons, links, and accents on your church page.
            </p>
          </div>

          <div>
            <Label htmlFor="secondary_color">Secondary Color (Optional)</Label>
            <div className="flex items-center space-x-3">
              <div 
                className="w-12 h-12 rounded-lg border border-gray-300 shadow-sm"
                style={{ 
                  backgroundColor: colors.secondary_color || '#f3f4f6',
                  backgroundImage: !colors.secondary_color ? 'linear-gradient(45deg, #ccc 25%, transparent 25%), linear-gradient(-45deg, #ccc 25%, transparent 25%), linear-gradient(45deg, transparent 75%, #ccc 75%), linear-gradient(-45deg, transparent 75%, #ccc 75%)' : undefined,
                  backgroundSize: !colors.secondary_color ? '8px 8px' : undefined,
                  backgroundPosition: !colors.secondary_color ? '0 0, 0 4px, 4px -4px, -4px 0px' : undefined
                }}
              />
              <Input
                id="secondary_color"
                type="color"
                value={colors.secondary_color || '#000000'}
                onChange={(e) => handleColorChange('secondary_color', e.target.value)}
                className="w-20 h-12"
              />
              <Input
                value={colors.secondary_color}
                onChange={(e) => handleColorChange('secondary_color', e.target.value)}
                placeholder="#6B7280"
                className="font-mono"
              />
              {colors.secondary_color && (
                <Button
                  type="button"
                  variant="ghost"
                  size="sm"
                  onClick={() => handleColorChange('secondary_color', '')}
                  className="text-gray-500"
                >
                  Clear
                </Button>
              )}
            </div>
            <p className="text-sm text-gray-500 mt-2">
              Optional color for secondary elements and backgrounds.
            </p>
          </div>
        </div>

        {/* Color Preview */}
        <div className="bg-gray-50 rounded-lg p-4 space-y-3">
          <h4 className="text-sm font-medium text-gray-900">Preview</h4>
          <div className="flex items-center space-x-4">
            <div 
              className="px-4 py-2 rounded-md text-white font-medium"
              style={{ backgroundColor: colors.primary_color }}
            >
              Primary Button
            </div>
            <div 
              className="px-4 py-2 rounded-md border font-medium"
              style={{ 
                borderColor: colors.primary_color,
                color: colors.primary_color 
              }}
            >
              Secondary Button
            </div>
            <div className="text-sm" style={{ color: colors.primary_color }}>
              Link Text
            </div>
          </div>
        </div>

        <div className="flex justify-end pt-6 border-t border-gray-200">
          <Button
            type="submit"
            loading={updateColorsMutation.isPending}
            className="w-full md:w-auto"
          >
            Save Appearance Settings
          </Button>
        </div>
      </form>
    </div>
  );
}