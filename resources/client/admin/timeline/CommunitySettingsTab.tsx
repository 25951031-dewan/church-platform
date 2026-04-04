import React, { useState } from 'react';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Button } from '@/components/ui/button';
import { Switch } from '@/components/ui/switch';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Separator } from '@/components/ui/separator';
import { TimelineSettings } from './TimelineSettingsPage';

interface CommunitySettingsTabProps {
  settings: TimelineSettings;
  onUpdate: (settings: Partial<TimelineSettings>) => Promise<void>;
}

const CommunitySettingsTab: React.FC<CommunitySettingsTabProps> = ({ settings, onUpdate }) => {
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

  return (
    <div className="space-y-6">
      {/* Post Controls */}
      <Card>
        <CardHeader>
          <CardTitle className="text-lg">Post Controls</CardTitle>
        </CardHeader>
        <CardContent className="space-y-4">
          <div className="flex items-center justify-between">
            <div className="space-y-1">
              <Label htmlFor="posts_enabled">Enable Posts</Label>
              <p className="text-sm text-gray-600">Allow users to create posts on the timeline</p>
            </div>
            <Switch
              id="posts_enabled"
              checked={localSettings.posts_enabled}
              onCheckedChange={(checked) => handleSettingChange('posts_enabled', checked)}
            />
          </div>

          <Separator />

          <div className="flex items-center justify-between">
            <div className="space-y-1">
              <Label htmlFor="photo_posts_enabled">Enable Photo Posts</Label>
              <p className="text-sm text-gray-600">Allow users to upload photos in posts</p>
            </div>
            <Switch
              id="photo_posts_enabled"
              checked={localSettings.photo_posts_enabled}
              onCheckedChange={(checked) => handleSettingChange('photo_posts_enabled', checked)}
              disabled={!localSettings.posts_enabled}
            />
          </div>

          <div className="flex items-center justify-between">
            <div className="space-y-1">
              <Label htmlFor="video_posts_enabled">Enable Video Posts</Label>
              <p className="text-sm text-gray-600">Allow users to upload videos in posts</p>
            </div>
            <Switch
              id="video_posts_enabled"
              checked={localSettings.video_posts_enabled}
              onCheckedChange={(checked) => handleSettingChange('video_posts_enabled', checked)}
              disabled={!localSettings.posts_enabled}
            />
          </div>

          <div className="flex items-center justify-between">
            <div className="space-y-1">
              <Label htmlFor="announcement_posts_enabled">Enable Announcements</Label>
              <p className="text-sm text-gray-600">Allow creation of announcement posts</p>
            </div>
            <Switch
              id="announcement_posts_enabled"
              checked={localSettings.announcement_posts_enabled}
              onCheckedChange={(checked) => handleSettingChange('announcement_posts_enabled', checked)}
              disabled={!localSettings.posts_enabled}
            />
          </div>
        </CardContent>
      </Card>

      {/* Interaction Controls */}
      <Card>
        <CardHeader>
          <CardTitle className="text-lg">Interaction Controls</CardTitle>
        </CardHeader>
        <CardContent className="space-y-4">
          <div className="flex items-center justify-between">
            <div className="space-y-1">
              <Label htmlFor="comments_enabled">Enable Comments</Label>
              <p className="text-sm text-gray-600">Allow users to comment on posts</p>
            </div>
            <Switch
              id="comments_enabled"
              checked={localSettings.comments_enabled}
              onCheckedChange={(checked) => handleSettingChange('comments_enabled', checked)}
            />
          </div>

          <div className="flex items-center justify-between">
            <div className="space-y-1">
              <Label htmlFor="reactions_enabled">Enable Reactions</Label>
              <p className="text-sm text-gray-600">Allow users to react to posts (like, love, pray, etc.)</p>
            </div>
            <Switch
              id="reactions_enabled"
              checked={localSettings.reactions_enabled}
              onCheckedChange={(checked) => handleSettingChange('reactions_enabled', checked)}
            />
          </div>
        </CardContent>
      </Card>

      {/* Access Controls */}
      <Card>
        <CardHeader>
          <CardTitle className="text-lg">Access & Moderation</CardTitle>
        </CardHeader>
        <CardContent className="space-y-4">
          <div className="flex items-center justify-between">
            <div className="space-y-1">
              <Label htmlFor="public_posting">Allow Public Posting</Label>
              <p className="text-sm text-gray-600">Allow non-members to create posts</p>
            </div>
            <Switch
              id="public_posting"
              checked={localSettings.public_posting}
              onCheckedChange={(checked) => handleSettingChange('public_posting', checked)}
            />
          </div>

          <div className="flex items-center justify-between">
            <div className="space-y-1">
              <Label htmlFor="post_approval_required">Require Post Approval</Label>
              <p className="text-sm text-gray-600">Posts must be approved before appearing</p>
            </div>
            <Switch
              id="post_approval_required"
              checked={localSettings.post_approval_required}
              onCheckedChange={(checked) => handleSettingChange('post_approval_required', checked)}
            />
          </div>
        </CardContent>
      </Card>

      {/* Limits */}
      <Card>
        <CardHeader>
          <CardTitle className="text-lg">Posting Limits</CardTitle>
        </CardHeader>
        <CardContent className="space-y-4">
          <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div className="space-y-2">
              <Label htmlFor="daily_post_limit">Daily Post Limit</Label>
              <Input
                id="daily_post_limit"
                type="number"
                min="1"
                max="100"
                value={localSettings.daily_post_limit}
                onChange={(e) => handleSettingChange('daily_post_limit', parseInt(e.target.value))}
              />
              <p className="text-xs text-gray-600">Maximum posts per user per day</p>
            </div>

            <div className="space-y-2">
              <Label htmlFor="min_user_age_to_post">Min User Age (Days)</Label>
              <Input
                id="min_user_age_to_post"
                type="number"
                min="0"
                max="365"
                value={localSettings.min_user_age_to_post}
                onChange={(e) => handleSettingChange('min_user_age_to_post', parseInt(e.target.value))}
              />
              <p className="text-xs text-gray-600">Days since registration before posting</p>
            </div>

            <div className="space-y-2">
              <Label htmlFor="post_character_limit">Post Character Limit</Label>
              <Input
                id="post_character_limit"
                type="number"
                min="10"
                max="50000"
                value={localSettings.post_character_limit}
                onChange={(e) => handleSettingChange('post_character_limit', parseInt(e.target.value))}
              />
              <p className="text-xs text-gray-600">Maximum characters per post</p>
            </div>

            <div className="space-y-2">
              <Label htmlFor="comment_character_limit">Comment Character Limit</Label>
              <Input
                id="comment_character_limit"
                type="number"
                min="10"
                max="10000"
                value={localSettings.comment_character_limit}
                onChange={(e) => handleSettingChange('comment_character_limit', parseInt(e.target.value))}
              />
              <p className="text-xs text-gray-600">Maximum characters per comment</p>
            </div>
          </div>
        </CardContent>
      </Card>

      {/* Actions */}
      <div className="flex items-center gap-3">
        <Button onClick={handleSave} disabled={saving}>
          {saving ? 'Saving...' : 'Save Community Settings'}
        </Button>
        <Button variant="outline" onClick={handleReset}>
          Reset
        </Button>
      </div>
    </div>
  );
};

export default CommunitySettingsTab;