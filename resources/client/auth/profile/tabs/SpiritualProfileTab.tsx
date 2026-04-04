import React, { useState, useEffect } from 'react';
import { useForm, useFieldArray } from 'react-hook-form';
import { zodResolver } from '@hookform/resolvers/zod';
import { z } from 'zod';
import { Heart, Save, Plus, Trash2, Calendar, Book, Pray } from 'lucide-react';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Textarea } from '@/components/ui/textarea';
import { Label } from '@/components/ui/label';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { Checkbox } from '@/components/ui/checkbox';
import { Badge } from '@/components/ui/badge';
import { useAuth } from '@/hooks/useAuth';
import { apiClient } from '@/lib/api-client';
import { toast } from 'react-hot-toast';

const spiritualProfileSchema = z.object({
  salvation_date: z.string().optional(),
  baptism_date: z.string().optional(),
  baptism_location: z.string().optional(),
  spiritual_gifts: z.array(z.string()).optional(),
  ministry_interests: z.array(z.string()).optional(),
  prayer_language: z.enum(['english', 'spanish', 'both', 'other']).optional(),
  prayer_requests_preference: z.enum(['receive_all', 'leadership_only', 'none']).optional(),
  spiritual_mentorship: z.enum(['seeking_mentor', 'willing_to_mentor', 'both', 'neither']).optional(),
  bible_reading_plan: z.string().optional(),
  favorite_bible_verse: z.string().optional(),
  spiritual_goals: z.string().max(1000).optional(),
  testimony: z.string().max(2000).optional(),
  custom_fields: z.array(z.object({
    field_id: z.number(),
    value: z.string()
  })).optional()
});

type SpiritualProfileFormData = z.infer<typeof spiritualProfileSchema>;

interface CustomField {
  id: number;
  field_name: string;
  field_type: 'text' | 'textarea' | 'select' | 'date' | 'boolean' | 'number';
  field_options?: string[];
  is_required: boolean;
  display_order: number;
}

const SpiritualProfileTab: React.FC = () => {
  const { user, updateUser } = useAuth();
  const [customFields, setCustomFields] = useState<CustomField[]>([]);
  const [isSaving, setIsSaving] = useState(false);
  const [isLoadingFields, setIsLoadingFields] = useState(true);

  const form = useForm<SpiritualProfileFormData>({
    resolver: zodResolver(spiritualProfileSchema),
    defaultValues: {
      salvation_date: user?.spiritual_profile?.salvation_date || '',
      baptism_date: user?.spiritual_profile?.baptism_date || '',
      baptism_location: user?.spiritual_profile?.baptism_location || '',
      spiritual_gifts: user?.spiritual_profile?.spiritual_gifts || [],
      ministry_interests: user?.spiritual_profile?.ministry_interests || [],
      prayer_language: user?.spiritual_profile?.prayer_language || undefined,
      prayer_requests_preference: user?.spiritual_profile?.prayer_requests_preference || undefined,
      spiritual_mentorship: user?.spiritual_profile?.spiritual_mentorship || undefined,
      bible_reading_plan: user?.spiritual_profile?.bible_reading_plan || '',
      favorite_bible_verse: user?.spiritual_profile?.favorite_bible_verse || '',
      spiritual_goals: user?.spiritual_profile?.spiritual_goals || '',
      testimony: user?.spiritual_profile?.testimony || '',
      custom_fields: []
    },
  });

  const { fields: customFieldValues, update: updateCustomField } = useFieldArray({
    control: form.control,
    name: 'custom_fields'
  });

  // Load custom fields on mount
  useEffect(() => {
    const loadCustomFields = async () => {
      try {
        const response = await apiClient.get('/user/custom-fields');
        setCustomFields(response.spiritual_fields || []);
        
        // Initialize custom field values
        const existingValues = response.field_values || [];
        const fieldValues = response.spiritual_fields?.map((field: CustomField) => {
          const existingValue = existingValues.find((v: any) => v.field_id === field.id);
          return {
            field_id: field.id,
            value: existingValue?.field_value || ''
          };
        }) || [];
        
        form.setValue('custom_fields', fieldValues);
      } catch (error) {
        console.error('Failed to load custom fields:', error);
      } finally {
        setIsLoadingFields(false);
      }
    };

    loadCustomFields();
  }, [form]);

  const onSubmit = async (data: SpiritualProfileFormData) => {
    setIsSaving(true);
    try {
      const response = await apiClient.put('/user/profile/spiritual', data);
      updateUser({
        ...user!,
        spiritual_profile: response.spiritual_profile
      });
      toast.success('Spiritual profile updated successfully');
    } catch (error) {
      toast.error('Failed to update spiritual profile');
    } finally {
      setIsSaving(false);
    }
  };

  const spiritualGiftsOptions = [
    'Teaching', 'Preaching', 'Leadership', 'Evangelism', 'Pastoral Care',
    'Hospitality', 'Music/Worship', 'Prayer', 'Administration', 'Mercy',
    'Helping', 'Giving', 'Prophecy', 'Healing', 'Discernment', 'Faith',
    'Knowledge', 'Wisdom', 'Encouragement', 'Intercession'
  ];

  const ministryInterestsOptions = [
    "Children's Ministry", 'Youth Ministry', 'Young Adults', 'Seniors Ministry',
    'Worship Team', 'Tech/Media', 'Outreach', 'Missions', 'Small Groups',
    'Counseling', 'Food Ministry', 'Facilities', 'Transportation',
    'Welcome Team', 'Prayer Ministry', 'Discipleship', 'Sports Ministry',
    'Arts & Crafts', 'Community Service', 'Special Events'
  ];

  const renderCustomField = (field: CustomField, index: number) => {
    const fieldValue = form.watch(`custom_fields.${index}.value`) || '';

    switch (field.field_type) {
      case 'text':
      case 'number':
        return (
          <Input
            type={field.field_type}
            value={fieldValue}
            onChange={(e) => updateCustomField(index, {
              field_id: field.id,
              value: e.target.value
            })}
            required={field.is_required}
          />
        );
      
      case 'textarea':
        return (
          <Textarea
            value={fieldValue}
            onChange={(e) => updateCustomField(index, {
              field_id: field.id,
              value: e.target.value
            })}
            rows={3}
            required={field.is_required}
          />
        );
      
      case 'date':
        return (
          <Input
            type="date"
            value={fieldValue}
            onChange={(e) => updateCustomField(index, {
              field_id: field.id,
              value: e.target.value
            })}
            required={field.is_required}
          />
        );
      
      case 'select':
        return (
          <Select
            value={fieldValue}
            onValueChange={(value) => updateCustomField(index, {
              field_id: field.id,
              value
            })}
            required={field.is_required}
          >
            <SelectTrigger>
              <SelectValue placeholder={`Select ${field.field_name.toLowerCase()}`} />
            </SelectTrigger>
            <SelectContent>
              {field.field_options?.map((option) => (
                <SelectItem key={option} value={option}>
                  {option}
                </SelectItem>
              ))}
            </SelectContent>
          </Select>
        );
      
      case 'boolean':
        return (
          <div className="flex items-center space-x-2">
            <Checkbox
              id={`custom_field_${field.id}`}
              checked={fieldValue === 'true'}
              onCheckedChange={(checked) => updateCustomField(index, {
                field_id: field.id,
                value: checked ? 'true' : 'false'
              })}
            />
            <Label htmlFor={`custom_field_${field.id}`}>
              Yes
            </Label>
          </div>
        );
      
      default:
        return null;
    }
  };

  if (isLoadingFields) {
    return (
      <div className="flex items-center justify-center py-8">
        <div className="w-6 h-6 border-2 border-blue-600 border-t-transparent rounded-full animate-spin mr-2" />
        <span>Loading spiritual profile...</span>
      </div>
    );
  }

  return (
    <form onSubmit={form.handleSubmit(onSubmit)} className="space-y-6">
      {/* Spiritual Journey */}
      <Card>
        <CardHeader>
          <CardTitle className="flex items-center gap-2">
            <Heart className="w-5 h-5 text-red-500" />
            Spiritual Journey
          </CardTitle>
        </CardHeader>
        <CardContent className="space-y-4">
          <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div>
              <Label htmlFor="salvation_date">Salvation Date</Label>
              <Input
                id="salvation_date"
                type="date"
                {...form.register('salvation_date')}
              />
            </div>
            <div>
              <Label htmlFor="baptism_date">Baptism Date</Label>
              <Input
                id="baptism_date"
                type="date"
                {...form.register('baptism_date')}
              />
            </div>
          </div>

          <div>
            <Label htmlFor="baptism_location">Baptism Location</Label>
            <Input
              id="baptism_location"
              {...form.register('baptism_location')}
              placeholder="Church or location where you were baptized"
            />
          </div>

          <div>
            <Label htmlFor="favorite_bible_verse">Favorite Bible Verse</Label>
            <Input
              id="favorite_bible_verse"
              {...form.register('favorite_bible_verse')}
              placeholder="e.g., John 3:16"
            />
          </div>
        </CardContent>
      </Card>

      {/* Spiritual Gifts & Interests */}
      <Card>
        <CardHeader>
          <CardTitle className="flex items-center gap-2">
            <Book className="w-5 h-5 text-blue-500" />
            Gifts & Ministry Interests
          </CardTitle>
        </CardHeader>
        <CardContent className="space-y-4">
          <div>
            <Label>Spiritual Gifts</Label>
            <div className="grid grid-cols-2 md:grid-cols-3 gap-2 mt-2">
              {spiritualGiftsOptions.map((gift) => (
                <label key={gift} className="flex items-center space-x-2">
                  <Checkbox
                    checked={form.watch('spiritual_gifts')?.includes(gift) || false}
                    onCheckedChange={(checked) => {
                      const current = form.getValues('spiritual_gifts') || [];
                      if (checked) {
                        form.setValue('spiritual_gifts', [...current, gift]);
                      } else {
                        form.setValue('spiritual_gifts', current.filter(g => g !== gift));
                      }
                    }}
                  />
                  <span className="text-sm">{gift}</span>
                </label>
              ))}
            </div>
          </div>

          <div>
            <Label>Ministry Interests</Label>
            <div className="grid grid-cols-2 md:grid-cols-3 gap-2 mt-2">
              {ministryInterestsOptions.map((interest) => (
                <label key={interest} className="flex items-center space-x-2">
                  <Checkbox
                    checked={form.watch('ministry_interests')?.includes(interest) || false}
                    onCheckedChange={(checked) => {
                      const current = form.getValues('ministry_interests') || [];
                      if (checked) {
                        form.setValue('ministry_interests', [...current, interest]);
                      } else {
                        form.setValue('ministry_interests', current.filter(i => i !== interest));
                      }
                    }}
                  />
                  <span className="text-sm">{interest}</span>
                </label>
              ))}
            </div>
          </div>
        </CardContent>
      </Card>

      {/* Prayer & Discipleship */}
      <Card>
        <CardHeader>
          <CardTitle className="flex items-center gap-2">
            <Pray className="w-5 h-5 text-purple-500" />
            Prayer & Discipleship
          </CardTitle>
        </CardHeader>
        <CardContent className="space-y-4">
          <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div>
              <Label htmlFor="prayer_language">Prayer Language Preference</Label>
              <Select
                value={form.watch('prayer_language')}
                onValueChange={(value) => form.setValue('prayer_language', value as any)}
              >
                <SelectTrigger>
                  <SelectValue placeholder="Select language" />
                </SelectTrigger>
                <SelectContent>
                  <SelectItem value="english">English</SelectItem>
                  <SelectItem value="spanish">Spanish</SelectItem>
                  <SelectItem value="both">Both English & Spanish</SelectItem>
                  <SelectItem value="other">Other</SelectItem>
                </SelectContent>
              </Select>
            </div>

            <div>
              <Label htmlFor="prayer_requests_preference">Prayer Requests</Label>
              <Select
                value={form.watch('prayer_requests_preference')}
                onValueChange={(value) => form.setValue('prayer_requests_preference', value as any)}
              >
                <SelectTrigger>
                  <SelectValue placeholder="Select preference" />
                </SelectTrigger>
                <SelectContent>
                  <SelectItem value="receive_all">Receive all prayer requests</SelectItem>
                  <SelectItem value="leadership_only">Leadership only</SelectItem>
                  <SelectItem value="none">None</SelectItem>
                </SelectContent>
              </Select>
            </div>
          </div>

          <div>
            <Label htmlFor="spiritual_mentorship">Mentorship Interest</Label>
            <Select
              value={form.watch('spiritual_mentorship')}
              onValueChange={(value) => form.setValue('spiritual_mentorship', value as any)}
            >
              <SelectTrigger>
                <SelectValue placeholder="Select interest" />
              </SelectTrigger>
              <SelectContent>
                <SelectItem value="seeking_mentor">Seeking a mentor</SelectItem>
                <SelectItem value="willing_to_mentor">Willing to mentor</SelectItem>
                <SelectItem value="both">Both seeking and willing</SelectItem>
                <SelectItem value="neither">Neither at this time</SelectItem>
              </SelectContent>
            </Select>
          </div>

          <div>
            <Label htmlFor="bible_reading_plan">Current Bible Reading Plan</Label>
            <Input
              id="bible_reading_plan"
              {...form.register('bible_reading_plan')}
              placeholder="e.g., One Year Bible, Chronological, Book study"
            />
          </div>
        </CardContent>
      </Card>

      {/* Personal Reflection */}
      <Card>
        <CardHeader>
          <CardTitle>Personal Reflection</CardTitle>
        </CardHeader>
        <CardContent className="space-y-4">
          <div>
            <Label htmlFor="spiritual_goals">Spiritual Goals</Label>
            <Textarea
              id="spiritual_goals"
              {...form.register('spiritual_goals')}
              placeholder="What are your spiritual growth goals for this year?"
              rows={3}
              error={form.formState.errors.spiritual_goals?.message}
            />
            <p className="text-xs text-gray-500 mt-1">
              {form.watch('spiritual_goals')?.length || 0}/1000 characters
            </p>
          </div>

          <div>
            <Label htmlFor="testimony">Your Testimony (Optional)</Label>
            <Textarea
              id="testimony"
              {...form.register('testimony')}
              placeholder="Share your testimony or spiritual journey (this may be shared with the community)"
              rows={4}
              error={form.formState.errors.testimony?.message}
            />
            <p className="text-xs text-gray-500 mt-1">
              {form.watch('testimony')?.length || 0}/2000 characters
            </p>
          </div>
        </CardContent>
      </Card>

      {/* Custom Church Fields */}
      {customFields.length > 0 && (
        <Card>
          <CardHeader>
            <CardTitle>Additional Information</CardTitle>
          </CardHeader>
          <CardContent className="space-y-4">
            {customFields.map((field, index) => (
              <div key={field.id}>
                <Label htmlFor={`custom_field_${field.id}`}>
                  {field.field_name}
                  {field.is_required && <span className="text-red-500 ml-1">*</span>}
                </Label>
                {renderCustomField(field, index)}
              </div>
            ))}
          </CardContent>
        </Card>
      )}

      {/* Save Button */}
      <div className="flex justify-end">
        <Button type="submit" disabled={isSaving} className="min-w-[120px]">
          {isSaving ? (
            <div className="w-4 h-4 border-2 border-white border-t-transparent rounded-full animate-spin mr-2" />
          ) : (
            <Save className="w-4 h-4 mr-2" />
          )}
          {isSaving ? 'Saving...' : 'Save Changes'}
        </Button>
      </div>
    </form>
  );
};

export default SpiritualProfileTab;