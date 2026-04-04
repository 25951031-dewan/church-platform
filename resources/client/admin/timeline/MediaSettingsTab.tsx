import React, { useState } from 'react';
import { Card, CardContent, CardHeader, CardTitle } from '@ui/card';
import { Button } from '@ui/button';
import { Input } from '@ui/input';
import { Label } from '@ui/label';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@ui/select';
import { TimelineSettings } from './TimelineSettingsPage';

interface MediaSettingsTabProps {
  settings: TimelineSettings;
  onUpdate: (settings: Partial<TimelineSettings>) => Promise<void>;
}

const MediaSettingsTab: React.FC<MediaSettingsTabProps> = ({ settings, onUpdate }) => {
  const [localSettings, setLocalSettings] = useState(settings);
  const [saving, setSaving] = useState(false);

  const handleSettingChange = (key: keyof TimelineSettings, value: any) => {
    setLocalSettings(prev => ({
      ...prev,
      [key]: value
    }));
  };

  const handleSave = async () => {
    setSaving(true);
    try {
      await onUpdate(localSettings);
    } finally {
      setSaving(false);
    }
  };

  const handleReset = () => {
    setLocalSettings(settings);
  };

  const formatFileSize = (bytes: number): string => {
    const mb = bytes / 1024 / 1024;
    return `${mb.toFixed(0)} MB`;
  };

  const parseFileSize = (sizeString: string): number => {
    const mb = parseInt(sizeString);
    return mb * 1024 * 1024;
  };

  return (
    <div className="space-y-6">
      {/* Photo Settings */}
      <Card>
        <CardHeader>
          <CardTitle className="text-lg">Photo Settings</CardTitle>
        </CardHeader>
        <CardContent className="space-y-4">
          <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div className="space-y-2">
              <Label htmlFor="max_photo_size">Maximum Photo Size</Label>
              <Select
                value={localSettings.max_photo_size.toString()}
                onValueChange={(value) => handleSettingChange('max_photo_size', parseInt(value))}
              >
                <SelectTrigger>
                  <SelectValue placeholder="Select max photo size" />
                </SelectTrigger>
                <SelectContent>
                  <SelectItem value="1048576">1 MB</SelectItem>
                  <SelectItem value="2097152">2 MB</SelectItem>
                  <SelectItem value="5242880">5 MB</SelectItem>
                  <SelectItem value="10485760">10 MB</SelectItem>
                  <SelectItem value="20971520">20 MB</SelectItem>
                  <SelectItem value="52428800">50 MB</SelectItem>
                </SelectContent>
              </Select>
              <p className="text-xs text-gray-600">
                Current: {formatFileSize(localSettings.max_photo_size)}
              </p>
            </div>

            <div className="space-y-2">
              <Label htmlFor="max_photos_per_post">Max Photos per Post</Label>
              <Input
                id="max_photos_per_post"
                type="number"
                min="1"
                max="20"
                value={localSettings.max_photos_per_post}
                onChange={(e) => handleSettingChange('max_photos_per_post', parseInt(e.target.value))}
              />
              <p className="text-xs text-gray-600">Maximum photos allowed in a single post</p>
            </div>
          </div>

          <div className="space-y-2">
            <Label htmlFor="allowed_photo_types">Allowed Photo Types</Label>
            <Input
              id="allowed_photo_types"
              value={localSettings.allowed_photo_types}
              onChange={(e) => handleSettingChange('allowed_photo_types', e.target.value)}
              placeholder="jpg,jpeg,png,webp"
            />
            <p className="text-xs text-gray-600">
              Comma-separated list of allowed file extensions
            </p>
          </div>
        </CardContent>
      </Card>

      {/* Video Settings */}
      <Card>
        <CardHeader>
          <CardTitle className="text-lg">Video Settings</CardTitle>
        </CardHeader>
        <CardContent className="space-y-4">
          <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div className="space-y-2">
              <Label htmlFor="max_video_size">Maximum Video Size</Label>
              <Select
                value={localSettings.max_video_size.toString()}
                onValueChange={(value) => handleSettingChange('max_video_size', parseInt(value))}
              >
                <SelectTrigger>
                  <SelectValue placeholder="Select max video size" />
                </SelectTrigger>
                <SelectContent>
                  <SelectItem value="10485760">10 MB</SelectItem>
                  <SelectItem value="26214400">25 MB</SelectItem>
                  <SelectItem value="52428800">50 MB</SelectItem>
                  <SelectItem value="104857600">100 MB</SelectItem>
                  <SelectItem value="209715200">200 MB</SelectItem>
                  <SelectItem value="524288000">500 MB</SelectItem>
                </SelectContent>
              </Select>
              <p className="text-xs text-gray-600">
                Current: {formatFileSize(localSettings.max_video_size)}
              </p>
            </div>

            <div className="space-y-2">
              <Label htmlFor="max_videos_per_post">Max Videos per Post</Label>
              <Input
                id="max_videos_per_post"
                type="number"
                min="1"
                max="5"
                value={localSettings.max_videos_per_post}
                onChange={(e) => handleSettingChange('max_videos_per_post', parseInt(e.target.value))}
              />
              <p className="text-xs text-gray-600">Maximum videos allowed in a single post</p>
            </div>
          </div>

          <div className="space-y-2">
            <Label htmlFor="allowed_video_types">Allowed Video Types</Label>
            <Input
              id="allowed_video_types"
              value={localSettings.allowed_video_types}
              onChange={(e) => handleSettingChange('allowed_video_types', e.target.value)}
              placeholder="mp4,webm,mov"
            />
            <p className="text-xs text-gray-600">
              Comma-separated list of allowed file extensions
            </p>
          </div>
        </CardContent>
      </Card>

      {/* Media Guidelines */}
      <Card>
        <CardHeader>
          <CardTitle className="text-lg">Media Guidelines</CardTitle>
        </CardHeader>
        <CardContent>
          <div className="bg-blue-50 border border-blue-200 rounded-lg p-4">
            <h4 className="font-medium text-blue-900 mb-2">Recommended Settings</h4>
            <ul className="text-sm text-blue-800 space-y-1">
              <li>• Photo size: 5MB or less for optimal loading times</li>
              <li>• Video size: 50MB or less for mobile-friendly viewing</li>
              <li>• Photo formats: JPG, PNG, WebP for best compatibility</li>
              <li>• Video formats: MP4 for universal browser support</li>
              <li>• Consider church's internet bandwidth when setting limits</li>
            </ul>
          </div>

          <div className="mt-4 bg-yellow-50 border border-yellow-200 rounded-lg p-4">
            <h4 className="font-medium text-yellow-900 mb-2">Storage Considerations</h4>
            <ul className="text-sm text-yellow-800 space-y-1">
              <li>• Larger files consume more server storage space</li>
              <li>• Higher limits may slow down upload/download speeds</li>
              <li>• Consider implementing content moderation for large files</li>
              <li>• Monitor storage usage and adjust limits as needed</li>
            </ul>
          </div>
        </CardContent>
      </Card>

      {/* Actions */}
      <div className="flex items-center gap-3">
        <Button onClick={handleSave} disabled={saving}>
          {saving ? 'Saving...' : 'Save Media Settings'}
        </Button>
        <Button variant="outline" onClick={handleReset}>
          Reset
        </Button>
      </div>
    </div>
  );
};

export default MediaSettingsTab;