import React, { useState, useRef } from 'react';
import { Card, CardContent, CardHeader, CardTitle } from '@ui/card';
import { Button } from '@ui/button';
import { Switch } from '@ui/switch';
import { Input } from '@ui/input';
import { Label } from '@ui/label';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@ui/select';
import { Separator } from '@ui/separator';
import { Badge } from '@ui/badge';
import { Upload, Download, FileText, Calendar, Book, Heart } from 'lucide-react';
import { toast } from 'react-hot-toast';
import { TimelineSettings } from './TimelineSettingsPage';
import { apiClient } from '@app/common/http/api-client';

interface DailyVerse {
  id: number;
  verse_date: string;
  reference: string;
  text: string;
  translation: string;
  reflection?: string;
  is_active: boolean;
}

interface VerseStats {
  total_verses: number;
  active_verses: number;
  future_verses: number;
}

interface DailyVerseTabProps {
  settings: TimelineSettings;
  recentVerses: DailyVerse[];
  todaysVerse: DailyVerse | null;
  stats: VerseStats | null;
  onUpdate: (settings: Partial<TimelineSettings>) => Promise<void>;
  onRefresh: () => Promise<void>;
}

const DailyVerseTab: React.FC<DailyVerseTabProps> = ({
  settings,
  recentVerses,
  todaysVerse,
  stats,
  onUpdate,
  onRefresh
}) => {
  const [localSettings, setLocalSettings] = useState(settings);
  const [saving, setSaving] = useState(false);
  const [importing, setImporting] = useState(false);
  const [exporting, setExporting] = useState(false);
  const fileInputRef = useRef<HTMLInputElement>(null);

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

  const handleImport = async (event: React.ChangeEvent<HTMLInputElement>) => {
    const file = event.target.files?.[0];
    if (!file) return;

    const formData = new FormData();
    formData.append('file', file);

    setImporting(true);
    try {
      const response = await apiClient.post('/admin/timeline/daily-verses/import', formData, {
        headers: {
          'Content-Type': 'multipart/form-data',
        },
      });

      toast.success(`Successfully imported ${response.created_count} verses`);
      
      if (response.error_count > 0) {
        toast.error(`${response.error_count} verses had errors`);
      }

      await onRefresh();
    } catch (error: any) {
      toast.error(error.response?.data?.message || 'Failed to import verses');
    } finally {
      setImporting(false);
      if (fileInputRef.current) {
        fileInputRef.current.value = '';
      }
    }
  };

  const handleExport = async () => {
    setExporting(true);
    try {
      const response = await fetch('/api/admin/timeline/daily-verses/export');
      const blob = await response.blob();
      
      const url = window.URL.createObjectURL(blob);
      const a = document.createElement('a');
      a.style.display = 'none';
      a.href = url;
      a.download = 'daily_verses_export.csv';
      document.body.appendChild(a);
      a.click();
      window.URL.revokeObjectURL(url);
      
      toast.success('Verses exported successfully');
    } catch (error) {
      toast.error('Failed to export verses');
    } finally {
      setExporting(false);
    }
  };

  const handleDownloadSample = async () => {
    try {
      const response = await fetch('/api/admin/timeline/daily-verses/sample');
      const blob = await response.blob();
      
      const url = window.URL.createObjectURL(blob);
      const a = document.createElement('a');
      a.style.display = 'none';
      a.href = url;
      a.download = 'daily_verses_sample.csv';
      document.body.appendChild(a);
      a.click();
      window.URL.revokeObjectURL(url);
      
      toast.success('Sample CSV downloaded');
    } catch (error) {
      toast.error('Failed to download sample CSV');
    }
  };

  return (
    <div className="space-y-6">
      {/* Verse Settings */}
      <Card>
        <CardHeader>
          <CardTitle className="text-lg">Daily Verse Configuration</CardTitle>
        </CardHeader>
        <CardContent className="space-y-4">
          <div className="flex items-center justify-between">
            <div className="space-y-1">
              <Label htmlFor="daily_verse_enabled">Enable Daily Verses</Label>
              <p className="text-sm text-gray-600">Show daily Bible verses on timeline</p>
            </div>
            <Switch
              id="daily_verse_enabled"
              checked={localSettings.daily_verse_enabled}
              onCheckedChange={(checked) => handleSettingChange('daily_verse_enabled', checked)}
            />
          </div>

          <Separator />

          <div className="flex items-center justify-between">
            <div className="space-y-1">
              <Label htmlFor="show_verse_on_feed">Show on Feed</Label>
              <p className="text-sm text-gray-600">Display verse prominently on timeline feed</p>
            </div>
            <Switch
              id="show_verse_on_feed"
              checked={localSettings.show_verse_on_feed}
              onCheckedChange={(checked) => handleSettingChange('show_verse_on_feed', checked)}
              disabled={!localSettings.daily_verse_enabled}
            />
          </div>

          <div className="flex items-center justify-between">
            <div className="space-y-1">
              <Label htmlFor="verse_reflection_enabled">Enable Reflections</Label>
              <p className="text-sm text-gray-600">Allow daily reflections/devotions with verses</p>
            </div>
            <Switch
              id="verse_reflection_enabled"
              checked={localSettings.verse_reflection_enabled}
              onCheckedChange={(checked) => handleSettingChange('verse_reflection_enabled', checked)}
              disabled={!localSettings.daily_verse_enabled}
            />
          </div>

          <div className="space-y-2">
            <Label htmlFor="verse_translation">Default Translation</Label>
            <Select
              value={localSettings.verse_translation}
              onValueChange={(value) => handleSettingChange('verse_translation', value)}
              disabled={!localSettings.daily_verse_enabled}
            >
              <SelectTrigger className="w-48">
                <SelectValue placeholder="Select translation" />
              </SelectTrigger>
              <SelectContent>
                <SelectItem value="NIV">NIV - New International Version</SelectItem>
                <SelectItem value="ESV">ESV - English Standard Version</SelectItem>
                <SelectItem value="NASB">NASB - New American Standard</SelectItem>
                <SelectItem value="KJV">KJV - King James Version</SelectItem>
                <SelectItem value="NKJV">NKJV - New King James Version</SelectItem>
                <SelectItem value="CSB">CSB - Christian Standard Bible</SelectItem>
                <SelectItem value="NLT">NLT - New Living Translation</SelectItem>
              </SelectContent>
            </Select>
          </div>
        </CardContent>
      </Card>

      {/* Today's Verse */}
      {todaysVerse && (
        <Card>
          <CardHeader>
            <CardTitle className="text-lg flex items-center gap-2">
              <Calendar className="w-5 h-5" />
              Today's Verse
            </CardTitle>
          </CardHeader>
          <CardContent>
            <div className="bg-blue-50 border border-blue-200 rounded-lg p-4">
              <div className="flex items-start gap-3">
                <Book className="w-6 h-6 text-blue-600 mt-1 flex-shrink-0" />
                <div className="space-y-2">
                  <div className="flex items-center gap-2">
                    <h4 className="font-semibold text-blue-900">{todaysVerse.reference}</h4>
                    <Badge variant="secondary">{todaysVerse.translation}</Badge>
                  </div>
                  <p className="text-blue-800">{todaysVerse.text}</p>
                  {todaysVerse.reflection && (
                    <div className="mt-3 p-3 bg-blue-100 rounded border-l-4 border-blue-400">
                      <p className="text-blue-900 text-sm italic">{todaysVerse.reflection}</p>
                    </div>
                  )}
                </div>
              </div>
            </div>
          </CardContent>
        </Card>
      )}

      {/* Verse Stats */}
      {stats && (
        <Card>
          <CardHeader>
            <CardTitle className="text-lg">Verse Statistics</CardTitle>
          </CardHeader>
          <CardContent>
            <div className="grid grid-cols-1 md:grid-cols-3 gap-4">
              <div className="text-center p-4 bg-green-50 rounded-lg">
                <div className="text-2xl font-bold text-green-700">{stats.total_verses}</div>
                <div className="text-sm text-green-600">Total Verses</div>
              </div>
              <div className="text-center p-4 bg-blue-50 rounded-lg">
                <div className="text-2xl font-bold text-blue-700">{stats.active_verses}</div>
                <div className="text-sm text-blue-600">Active Verses</div>
              </div>
              <div className="text-center p-4 bg-purple-50 rounded-lg">
                <div className="text-2xl font-bold text-purple-700">{stats.future_verses}</div>
                <div className="text-sm text-purple-600">Future Verses</div>
              </div>
            </div>
          </CardContent>
        </Card>
      )}

      {/* CSV Management */}
      <Card>
        <CardHeader>
          <CardTitle className="text-lg">Verse Management</CardTitle>
        </CardHeader>
        <CardContent className="space-y-4">
          <div className="grid grid-cols-1 md:grid-cols-3 gap-4">
            <Button
              variant="outline"
              onClick={() => fileInputRef.current?.click()}
              disabled={importing}
              className="w-full"
            >
              <Upload className="w-4 h-4 mr-2" />
              {importing ? 'Importing...' : 'Import CSV'}
            </Button>

            <Button
              variant="outline"
              onClick={handleExport}
              disabled={exporting}
              className="w-full"
            >
              <Download className="w-4 h-4 mr-2" />
              {exporting ? 'Exporting...' : 'Export CSV'}
            </Button>

            <Button
              variant="outline"
              onClick={handleDownloadSample}
              className="w-full"
            >
              <FileText className="w-4 h-4 mr-2" />
              Sample CSV
            </Button>
          </div>

          <input
            ref={fileInputRef}
            type="file"
            accept=".csv,.txt"
            onChange={handleImport}
            className="hidden"
          />

          <div className="bg-gray-50 border rounded-lg p-4">
            <h4 className="font-medium text-gray-900 mb-2">CSV Format</h4>
            <p className="text-sm text-gray-600 mb-2">
              Your CSV file should include the following columns:
            </p>
            <ul className="text-xs text-gray-600 space-y-1">
              <li><code>verse_date</code> - Date in YYYY-MM-DD format</li>
              <li><code>reference</code> - Bible reference (e.g., "John 3:16")</li>
              <li><code>text</code> - The verse text</li>
              <li><code>translation</code> - Bible translation (NIV, ESV, etc.)</li>
              <li><code>reflection</code> - Optional reflection/devotion</li>
              <li><code>is_active</code> - true or false</li>
            </ul>
          </div>
        </CardContent>
      </Card>

      {/* Recent Verses */}
      {recentVerses.length > 0 && (
        <Card>
          <CardHeader>
            <CardTitle className="text-lg">Recent Verses</CardTitle>
          </CardHeader>
          <CardContent>
            <div className="space-y-3">
              {recentVerses.slice(0, 5).map((verse) => (
                <div
                  key={verse.id}
                  className="flex items-start justify-between p-3 border rounded-lg"
                >
                  <div className="flex-1">
                    <div className="flex items-center gap-2 mb-1">
                      <span className="font-medium text-sm">{verse.reference}</span>
                      <Badge variant="outline" className="text-xs">
                        {verse.translation}
                      </Badge>
                      <span className="text-xs text-gray-500">{verse.verse_date}</span>
                    </div>
                    <p className="text-sm text-gray-700 line-clamp-2">{verse.text}</p>
                  </div>
                  <div className="ml-3">
                    {verse.is_active ? (
                      <Badge variant="success">Active</Badge>
                    ) : (
                      <Badge variant="secondary">Inactive</Badge>
                    )}
                  </div>
                </div>
              ))}
            </div>
          </CardContent>
        </Card>
      )}

      {/* Actions */}
      <div className="flex items-center gap-3">
        <Button onClick={handleSave} disabled={saving}>
          {saving ? 'Saving...' : 'Save Verse Settings'}
        </Button>
        <Button variant="outline" onClick={handleReset}>
          Reset
        </Button>
      </div>
    </div>
  );
};

export default DailyVerseTab;