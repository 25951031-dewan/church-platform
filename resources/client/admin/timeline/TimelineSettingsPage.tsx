import React, { useState, useEffect } from 'react';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Tabs, TabsContent, TabsList, TabsTrigger } from '@/components/ui/tabs';
import { Button } from '@/components/ui/button';
import { Badge } from '@/components/ui/badge';
import { toast } from 'react-hot-toast';
import CommunitySettingsTab from './CommunitySettingsTab';
import MediaSettingsTab from './MediaSettingsTab';
import DailyVerseTab from './DailyVerseTab';
import { apiClient } from '@/lib/api-client';

export interface TimelineSettings {
  // Community Controls
  posts_enabled: boolean;
  photo_posts_enabled: boolean;
  video_posts_enabled: boolean;
  announcement_posts_enabled: boolean;
  comments_enabled: boolean;
  reactions_enabled: boolean;
  public_posting: boolean;
  post_approval_required: boolean;
  
  // Media Limits
  max_photo_size: number;
  max_video_size: number;
  allowed_photo_types: string;
  allowed_video_types: string;
  max_photos_per_post: number;
  max_videos_per_post: number;
  
  // Posting Controls
  daily_post_limit: number;
  comment_character_limit: number;
  post_character_limit: number;
  min_user_age_to_post: number;
  
  // Daily Verse Settings
  daily_verse_enabled: boolean;
  show_verse_on_feed: boolean;
  verse_translation: string;
  verse_reflection_enabled: boolean;
}

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

const TimelineSettingsPage: React.FC = () => {
  const [communitySettings, setCommunitySettings] = useState<TimelineSettings | null>(null);
  const [mediaSettings, setMediaSettings] = useState<TimelineSettings | null>(null);
  const [verseSettings, setVerseSettings] = useState<TimelineSettings | null>(null);
  const [recentVerses, setRecentVerses] = useState<DailyVerse[]>([]);
  const [todaysVerse, setTodaysVerse] = useState<DailyVerse | null>(null);
  const [verseStats, setVerseStats] = useState<VerseStats | null>(null);
  const [loading, setLoading] = useState(true);
  const [activeTab, setActiveTab] = useState('community');

  const loadCommunitySettings = async () => {
    try {
      const response = await apiClient.get('/admin/timeline/settings/community');
      setCommunitySettings(response.settings);
    } catch (error) {
      toast.error('Failed to load community settings');
      console.error(error);
    }
  };

  const loadMediaSettings = async () => {
    try {
      const response = await apiClient.get('/admin/timeline/settings/media');
      setMediaSettings(response.settings);
    } catch (error) {
      toast.error('Failed to load media settings');
      console.error(error);
    }
  };

  const loadVerseSettings = async () => {
    try {
      const response = await apiClient.get('/admin/timeline/settings/daily-verse');
      setVerseSettings(response.settings);
      setRecentVerses(response.recent_verses);
      setTodaysVerse(response.todays_verse);
      setVerseStats(response.stats);
    } catch (error) {
      toast.error('Failed to load verse settings');
      console.error(error);
    }
  };

  useEffect(() => {
    const loadAllSettings = async () => {
      setLoading(true);
      await Promise.all([
        loadCommunitySettings(),
        loadMediaSettings(),
        loadVerseSettings()
      ]);
      setLoading(false);
    };

    loadAllSettings();
  }, []);

  const updateCommunitySettings = async (settings: Partial<TimelineSettings>) => {
    try {
      await apiClient.post('/admin/timeline/settings/community', { settings });
      await loadCommunitySettings();
      toast.success('Community settings updated successfully');
    } catch (error) {
      toast.error('Failed to update community settings');
      console.error(error);
    }
  };

  const updateMediaSettings = async (settings: Partial<TimelineSettings>) => {
    try {
      await apiClient.post('/admin/timeline/settings/media', { settings });
      await loadMediaSettings();
      toast.success('Media settings updated successfully');
    } catch (error) {
      toast.error('Failed to update media settings');
      console.error(error);
    }
  };

  const updateVerseSettings = async (settings: Partial<TimelineSettings>) => {
    try {
      await apiClient.post('/admin/timeline/settings/daily-verse', { settings });
      await loadVerseSettings();
      toast.success('Daily verse settings updated successfully');
    } catch (error) {
      toast.error('Failed to update verse settings');
      console.error(error);
    }
  };

  if (loading) {
    return (
      <div className="flex items-center justify-center min-h-[400px]">
        <div className="animate-spin rounded-full h-8 w-8 border-b-2 border-blue-600"></div>
      </div>
    );
  }

  return (
    <div className="max-w-7xl mx-auto p-6">
      {/* Header */}
      <div className="mb-8">
        <h1 className="text-3xl font-bold text-gray-900">Timeline Settings</h1>
        <p className="mt-2 text-gray-600">
          Manage community posts, media settings, and daily verses for your church timeline
        </p>
      </div>

      {/* Quick Stats */}
      <div className="grid grid-cols-1 md:grid-cols-4 gap-4 mb-8">
        <Card>
          <CardContent className="p-4">
            <div className="flex items-center justify-between">
              <div>
                <p className="text-sm font-medium text-gray-600">Posts Enabled</p>
                <p className="text-2xl font-bold">
                  {communitySettings?.posts_enabled ? (
                    <Badge variant="success">On</Badge>
                  ) : (
                    <Badge variant="destructive">Off</Badge>
                  )}
                </p>
              </div>
            </div>
          </CardContent>
        </Card>

        <Card>
          <CardContent className="p-4">
            <div className="flex items-center justify-between">
              <div>
                <p className="text-sm font-medium text-gray-600">Daily Verses</p>
                <p className="text-2xl font-bold">{verseStats?.total_verses || 0}</p>
              </div>
            </div>
          </CardContent>
        </Card>

        <Card>
          <CardContent className="p-4">
            <div className="flex items-center justify-between">
              <div>
                <p className="text-sm font-medium text-gray-600">Max Photo Size</p>
                <p className="text-2xl font-bold">
                  {Math.round((mediaSettings?.max_photo_size || 0) / 1024 / 1024)}MB
                </p>
              </div>
            </div>
          </CardContent>
        </Card>

        <Card>
          <CardContent className="p-4">
            <div className="flex items-center justify-between">
              <div>
                <p className="text-sm font-medium text-gray-600">Post Limit/Day</p>
                <p className="text-2xl font-bold">{communitySettings?.daily_post_limit || 0}</p>
              </div>
            </div>
          </CardContent>
        </Card>
      </div>

      {/* Settings Tabs */}
      <Card>
        <CardHeader>
          <CardTitle>Timeline Configuration</CardTitle>
        </CardHeader>
        <CardContent>
          <Tabs value={activeTab} onValueChange={setActiveTab} className="space-y-6">
            <TabsList className="grid w-full grid-cols-3">
              <TabsTrigger value="community">Community Settings</TabsTrigger>
              <TabsTrigger value="media">Media Settings</TabsTrigger>
              <TabsTrigger value="verse">Daily Verses</TabsTrigger>
            </TabsList>

            <TabsContent value="community" className="space-y-6">
              {communitySettings && (
                <CommunitySettingsTab
                  settings={communitySettings}
                  onUpdate={updateCommunitySettings}
                />
              )}
            </TabsContent>

            <TabsContent value="media" className="space-y-6">
              {mediaSettings && (
                <MediaSettingsTab
                  settings={mediaSettings}
                  onUpdate={updateMediaSettings}
                />
              )}
            </TabsContent>

            <TabsContent value="verse" className="space-y-6">
              {verseSettings && (
                <DailyVerseTab
                  settings={verseSettings}
                  recentVerses={recentVerses}
                  todaysVerse={todaysVerse}
                  stats={verseStats}
                  onUpdate={updateVerseSettings}
                  onRefresh={loadVerseSettings}
                />
              )}
            </TabsContent>
          </Tabs>
        </CardContent>
      </Card>
    </div>
  );
};

export default TimelineSettingsPage;