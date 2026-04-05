import React, { useState } from 'react';
import { Shield, Eye, EyeOff, Users, Globe, Lock, Mail, Phone, MessageCircle } from 'lucide-react';
import { useForm } from 'react-hook-form';
import { zodResolver } from '@hookform/resolvers/zod';
import { z } from 'zod';
import { Card, CardContent, CardHeader, CardTitle } from '@ui/card';
import { Button } from '@ui/button';
import { Label } from '@ui/label';
import { Switch } from '@ui/switch';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@ui/select';
import { Textarea } from '@ui/textarea';
import { Badge } from '@ui/badge';
import { toast } from 'react-hot-toast';
import { apiClient } from '@app/common/http/api-client';

const privacySettingsSchema = z.object({
  profile_visibility: z.enum(['public', 'members_only', 'private']).default('members_only'),
  show_email: z.boolean().default(false),
  show_phone: z.boolean().default(false),
  show_address: z.boolean().default(false),
  show_birthday: z.enum(['public', 'month_day', 'hidden']).default('month_day'),
  show_ministry_roles: z.boolean().default(true),
  show_spiritual_profile: z.boolean().default(true),
  show_prayer_requests: z.boolean().default(true),
  allow_direct_messages: z.boolean().default(true),
  allow_prayer_notifications: z.boolean().default(true),
  allow_event_invites: z.boolean().default(true),
  allow_ministry_recommendations: z.boolean().default(true),
  search_visibility: z.boolean().default(true),
  directory_visibility: z.boolean().default(true),
  social_sharing: z.boolean().default(false),
  data_export_allowed: z.boolean().default(true),
  marketing_communications: z.boolean().default(false),
  blocked_users: z.array(z.string()).default([]),
  privacy_notes: z.string().optional()
});

type PrivacySettingsFormData = z.infer<typeof privacySettingsSchema>;

interface PrivacySettingsTabProps {
  onSave?: (data: PrivacySettingsFormData) => Promise<void>;
  initialData?: Partial<PrivacySettingsFormData>;
}

const PrivacySettingsTab: React.FC<PrivacySettingsTabProps> = ({ onSave, initialData }) => {
  const [isSaving, setIsSaving] = useState(false);
  const [activeSection, setActiveSection] = useState<string | null>(null);

  const form = useForm<PrivacySettingsFormData>({
    resolver: zodResolver(privacySettingsSchema),
    defaultValues: {
      profile_visibility: 'members_only',
      show_email: false,
      show_phone: false,
      show_address: false,
      show_birthday: 'month_day',
      show_ministry_roles: true,
      show_spiritual_profile: true,
      show_prayer_requests: true,
      allow_direct_messages: true,
      allow_prayer_notifications: true,
      allow_event_invites: true,
      allow_ministry_recommendations: true,
      search_visibility: true,
      directory_visibility: true,
      social_sharing: false,
      data_export_allowed: true,
      marketing_communications: false,
      blocked_users: [],
      privacy_notes: '',
      ...initialData
    }
  });

  const handleSubmit = async (data: PrivacySettingsFormData) => {
    setIsSaving(true);
    try {
      if (onSave) {
        await onSave(data);
      } else {
        await apiClient.put('/user/privacy-settings', data);
      }
      toast.success('Privacy settings updated successfully');
    } catch (error) {
      toast.error('Failed to update privacy settings');
    } finally {
      setIsSaving(false);
    }
  };

  const getVisibilityIcon = (visibility: string) => {
    switch (visibility) {
      case 'public':
        return <Globe className="w-4 h-4 text-green-500" />;
      case 'members_only':
        return <Users className="w-4 h-4 text-blue-500" />;
      case 'private':
        return <Lock className="w-4 h-4 text-red-500" />;
      default:
        return <Eye className="w-4 h-4" />;
    }
  };

  const getVisibilityText = (visibility: string) => {
    switch (visibility) {
      case 'public':
        return 'Anyone can see this information';
      case 'members_only':
        return 'Only church members can see this';
      case 'private':
        return 'Only you can see this information';
      default:
        return '';
    }
  };

  const PrivacyCard: React.FC<{
    title: string;
    description: string;
    icon: React.ReactNode;
    children: React.ReactNode;
  }> = ({ title, description, icon, children }) => (
    <Card className="relative">
      <CardHeader>
        <CardTitle className="flex items-center gap-2">
          {icon}
          {title}
        </CardTitle>
        <p className="text-sm text-gray-600 dark:text-gray-400">{description}</p>
      </CardHeader>
      <CardContent className="space-y-4">
        {children}
      </CardContent>
    </Card>
  );

  const SettingRow: React.FC<{
    label: string;
    description: string;
    control: React.ReactNode;
    warning?: boolean;
  }> = ({ label, description, control, warning }) => (
    <div className={`flex items-center justify-between p-3 rounded-lg border ${
      warning ? 'border-amber-200 bg-amber-50 dark:border-amber-800 dark:bg-amber-900/20' : 
      'border-gray-200 dark:border-gray-700'
    }`}>
      <div className="flex-1">
        <div className="font-medium text-gray-900 dark:text-white">{label}</div>
        <div className="text-sm text-gray-600 dark:text-gray-400">{description}</div>
      </div>
      <div className="ml-4">
        {control}
      </div>
    </div>
  );

  return (
    <form onSubmit={form.handleSubmit(handleSubmit)} className="space-y-6">
      {/* Profile Visibility */}
      <PrivacyCard
        title="Profile Visibility"
        description="Control who can see your profile information"
        icon={<Eye className="w-5 h-5" />}
      >
        <div>
          <Label htmlFor="profile_visibility">Default Profile Visibility</Label>
          <Select
            value={form.watch('profile_visibility')}
            onValueChange={(value: 'public' | 'members_only' | 'private') => 
              form.setValue('profile_visibility', value)}
          >
            <SelectTrigger>
              <SelectValue />
            </SelectTrigger>
            <SelectContent>
              <SelectItem value="public">
                <div className="flex items-center gap-2">
                  <Globe className="w-4 h-4 text-green-500" />
                  Public - Anyone can see
                </div>
              </SelectItem>
              <SelectItem value="members_only">
                <div className="flex items-center gap-2">
                  <Users className="w-4 h-4 text-blue-500" />
                  Church Members Only
                </div>
              </SelectItem>
              <SelectItem value="private">
                <div className="flex items-center gap-2">
                  <Lock className="w-4 h-4 text-red-500" />
                  Private - Only me
                </div>
              </SelectItem>
            </SelectContent>
          </Select>
          <div className="flex items-center gap-2 mt-2 text-sm text-gray-600 dark:text-gray-400">
            {getVisibilityIcon(form.watch('profile_visibility'))}
            {getVisibilityText(form.watch('profile_visibility'))}
          </div>
        </div>

        <div className="space-y-3">
          <h4 className="font-medium text-gray-900 dark:text-white">Contact Information</h4>
          
          <SettingRow
            label="Show Email Address"
            description="Allow others to see and contact you via email"
            control={
              <Switch
                checked={form.watch('show_email')}
                onCheckedChange={(checked) => form.setValue('show_email', checked)}
              />
            }
          />

          <SettingRow
            label="Show Phone Number"
            description="Allow others to see and contact you via phone"
            control={
              <Switch
                checked={form.watch('show_phone')}
                onCheckedChange={(checked) => form.setValue('show_phone', checked)}
              />
            }
            warning
          />

          <SettingRow
            label="Show Address"
            description="Display your home address on your profile"
            control={
              <Switch
                checked={form.watch('show_address')}
                onCheckedChange={(checked) => form.setValue('show_address', checked)}
              />
            }
            warning
          />

          <div className="p-3 rounded-lg border border-gray-200 dark:border-gray-700">
            <div className="flex items-center justify-between">
              <div>
                <div className="font-medium text-gray-900 dark:text-white">Birthday Visibility</div>
                <div className="text-sm text-gray-600 dark:text-gray-400">Choose how much of your birthday to show</div>
              </div>
              <Select
                value={form.watch('show_birthday')}
                onValueChange={(value: 'public' | 'month_day' | 'hidden') => 
                  form.setValue('show_birthday', value)}
              >
                <SelectTrigger className="w-40">
                  <SelectValue />
                </SelectTrigger>
                <SelectContent>
                  <SelectItem value="public">Full Date</SelectItem>
                  <SelectItem value="month_day">Month & Day</SelectItem>
                  <SelectItem value="hidden">Hidden</SelectItem>
                </SelectContent>
              </Select>
            </div>
          </div>
        </div>
      </PrivacyCard>

      {/* Ministry & Activity Sharing */}
      <PrivacyCard
        title="Ministry & Activity Sharing"
        description="Control what ministry and activity information is visible"
        icon={<Users className="w-5 h-5" />}
      >
        <div className="space-y-3">
          <SettingRow
            label="Show Ministry Roles"
            description="Display your current and past ministry positions"
            control={
              <Switch
                checked={form.watch('show_ministry_roles')}
                onCheckedChange={(checked) => form.setValue('show_ministry_roles', checked)}
              />
            }
          />

          <SettingRow
            label="Show Spiritual Profile"
            description="Display your spiritual gifts, interests, and testimony"
            control={
              <Switch
                checked={form.watch('show_spiritual_profile')}
                onCheckedChange={(checked) => form.setValue('show_spiritual_profile', checked)}
              />
            }
          />

          <SettingRow
            label="Show Prayer Requests"
            description="Allow others to see prayer requests you've shared"
            control={
              <Switch
                checked={form.watch('show_prayer_requests')}
                onCheckedChange={(checked) => form.setValue('show_prayer_requests', checked)}
              />
            }
          />
        </div>
      </PrivacyCard>

      {/* Communication Preferences */}
      <PrivacyCard
        title="Communication Preferences"
        description="Control how others can contact and interact with you"
        icon={<MessageCircle className="w-5 h-5" />}
      >
        <div className="space-y-3">
          <SettingRow
            label="Allow Direct Messages"
            description="Let other members send you private messages"
            control={
              <Switch
                checked={form.watch('allow_direct_messages')}
                onCheckedChange={(checked) => form.setValue('allow_direct_messages', checked)}
              />
            }
          />

          <SettingRow
            label="Prayer Notifications"
            description="Receive notifications when people pray for your requests"
            control={
              <Switch
                checked={form.watch('allow_prayer_notifications')}
                onCheckedChange={(checked) => form.setValue('allow_prayer_notifications', checked)}
              />
            }
          />

          <SettingRow
            label="Event Invitations"
            description="Allow event organizers to invite you to church events"
            control={
              <Switch
                checked={form.watch('allow_event_invites')}
                onCheckedChange={(checked) => form.setValue('allow_event_invites', checked)}
              />
            }
          />

          <SettingRow
            label="Ministry Recommendations"
            description="Receive suggestions for ministry opportunities based on your profile"
            control={
              <Switch
                checked={form.watch('allow_ministry_recommendations')}
                onCheckedChange={(checked) => form.setValue('allow_ministry_recommendations', checked)}
              />
            }
          />
        </div>
      </PrivacyCard>

      {/* Discovery & Search */}
      <PrivacyCard
        title="Discovery & Search"
        description="Control how others can find you"
        icon={<Globe className="w-5 h-5" />}
      >
        <div className="space-y-3">
          <SettingRow
            label="Search Visibility"
            description="Allow your profile to appear in member search results"
            control={
              <Switch
                checked={form.watch('search_visibility')}
                onCheckedChange={(checked) => form.setValue('search_visibility', checked)}
              />
            }
          />

          <SettingRow
            label="Member Directory"
            description="Include your profile in the church member directory"
            control={
              <Switch
                checked={form.watch('directory_visibility')}
                onCheckedChange={(checked) => form.setValue('directory_visibility', checked)}
              />
            }
          />

          <SettingRow
            label="Social Media Sharing"
            description="Allow your activity to be shared on social media"
            control={
              <Switch
                checked={form.watch('social_sharing')}
                onCheckedChange={(checked) => form.setValue('social_sharing', checked)}
              />
            }
            warning
          />
        </div>
      </PrivacyCard>

      {/* Data & Marketing */}
      <PrivacyCard
        title="Data & Marketing"
        description="Control data usage and marketing communications"
        icon={<Shield className="w-5 h-5" />}
      >
        <div className="space-y-3">
          <SettingRow
            label="Data Export"
            description="Allow yourself to export your personal data"
            control={
              <Switch
                checked={form.watch('data_export_allowed')}
                onCheckedChange={(checked) => form.setValue('data_export_allowed', checked)}
              />
            }
          />

          <SettingRow
            label="Marketing Communications"
            description="Receive newsletters, updates, and promotional content"
            control={
              <Switch
                checked={form.watch('marketing_communications')}
                onCheckedChange={(checked) => form.setValue('marketing_communications', checked)}
              />
            }
          />
        </div>
      </PrivacyCard>

      {/* Privacy Notes */}
      <Card>
        <CardHeader>
          <CardTitle>Additional Privacy Notes</CardTitle>
          <p className="text-sm text-gray-600 dark:text-gray-400">
            Add any specific privacy concerns or requests for the church administration
          </p>
        </CardHeader>
        <CardContent>
          <Textarea
            {...form.register('privacy_notes')}
            placeholder="Any additional privacy concerns or special requests..."
            rows={4}
          />
        </CardContent>
      </Card>

      {/* Privacy Summary */}
      <Card className="border-blue-200 bg-blue-50 dark:border-blue-800 dark:bg-blue-900/20">
        <CardHeader>
          <CardTitle className="flex items-center gap-2 text-blue-900 dark:text-blue-100">
            <Shield className="w-5 h-5" />
            Privacy Summary
          </CardTitle>
        </CardHeader>
        <CardContent>
          <div className="grid grid-cols-1 md:grid-cols-2 gap-4 text-sm">
            <div>
              <h4 className="font-medium text-blue-900 dark:text-blue-100 mb-2">Visible to Others:</h4>
              <ul className="space-y-1 text-blue-800 dark:text-blue-200">
                {form.watch('show_email') && <li>• Email address</li>}
                {form.watch('show_phone') && <li>• Phone number</li>}
                {form.watch('show_address') && <li>• Home address</li>}
                {form.watch('show_ministry_roles') && <li>• Ministry roles</li>}
                {form.watch('show_spiritual_profile') && <li>• Spiritual profile</li>}
              </ul>
            </div>
            <div>
              <h4 className="font-medium text-blue-900 dark:text-blue-100 mb-2">Communications Allowed:</h4>
              <ul className="space-y-1 text-blue-800 dark:text-blue-200">
                {form.watch('allow_direct_messages') && <li>• Direct messages</li>}
                {form.watch('allow_event_invites') && <li>• Event invitations</li>}
                {form.watch('allow_prayer_notifications') && <li>• Prayer notifications</li>}
                {form.watch('marketing_communications') && <li>• Marketing emails</li>}
              </ul>
            </div>
          </div>
        </CardContent>
      </Card>

      {/* Save Button */}
      <div className="flex justify-end">
        <Button type="submit" disabled={isSaving}>
          {isSaving ? 'Saving...' : 'Save Privacy Settings'}
        </Button>
      </div>
    </form>
  );
};

export default PrivacySettingsTab;