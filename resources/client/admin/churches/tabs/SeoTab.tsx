import React, { useState } from 'react';
import { useMutation, useQueryClient } from '@tanstack/react-query';
import { toast } from 'react-hot-toast';
import { FiSearch, FiFacebook, FiTwitter, FiInstagram, FiYoutube } from 'react-icons/fi';

import { Button } from '@/components/ui/Button';
import { Input } from '@/components/ui/Input';
import { Label } from '@/components/ui/Label';
import { Textarea } from '@/components/ui/Textarea';

interface SeoTabProps {
  church: any;
  churchId: string;
}

export default function SeoTab({ church, churchId }: SeoTabProps) {
  const queryClient = useQueryClient();
  const [formData, setFormData] = useState({
    meta_title: church.meta_title || '',
    meta_description: church.meta_description || '',
    facebook_url: church.facebook_url || '',
    instagram_url: church.instagram_url || '',
    youtube_url: church.youtube_url || '',
    twitter_url: church.twitter_url || '',
    tiktok_url: church.tiktok_url || '',
  });

  const updateMutation = useMutation({
    mutationFn: async (data: any) => {
      const response = await fetch(`/api/churches/${churchId}/website/seo`, {
        method: 'PUT',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify(data),
      });
      if (!response.ok) throw new Error('Failed to update SEO settings');
      return response.json();
    },
    onSuccess: () => {
      toast.success('SEO & social media settings updated successfully');
      queryClient.invalidateQueries(['church-website', churchId]);
    },
    onError: () => {
      toast.error('Failed to update SEO settings');
    },
  });

  const handleSubmit = (e: React.FormEvent) => {
    e.preventDefault();
    updateMutation.mutate(formData);
  };

  const handleInputChange = (field: string, value: string) => {
    setFormData(prev => ({ ...prev, [field]: value }));
  };

  const generateMetaTitle = () => {
    if (church.name) {
      const title = `${church.name} - ${church.denomination || 'Church'}${church.city ? ` in ${church.city}` : ''}`;
      handleInputChange('meta_title', title);
    }
  };

  const generateMetaDescription = () => {
    if (church.name && church.short_description) {
      const description = `${church.short_description} Visit ${church.name}${church.city ? ` in ${church.city}` : ''} for worship, community, and spiritual growth.`;
      handleInputChange('meta_description', description.substring(0, 160));
    }
  };

  const socialPlatforms = [
    {
      key: 'facebook_url',
      label: 'Facebook',
      icon: FiFacebook,
      placeholder: 'https://facebook.com/yourchurch',
      color: 'text-blue-600'
    },
    {
      key: 'instagram_url',
      label: 'Instagram',
      icon: FiInstagram,
      placeholder: 'https://instagram.com/yourchurch',
      color: 'text-pink-600'
    },
    {
      key: 'youtube_url',
      label: 'YouTube',
      icon: FiYoutube,
      placeholder: 'https://youtube.com/@yourchurch',
      color: 'text-red-600'
    },
    {
      key: 'twitter_url',
      label: 'Twitter/X',
      icon: FiTwitter,
      placeholder: 'https://twitter.com/yourchurch',
      color: 'text-gray-900'
    },
    {
      key: 'tiktok_url',
      label: 'TikTok',
      icon: FiSearch, // Using search icon as placeholder for TikTok
      placeholder: 'https://tiktok.com/@yourchurch',
      color: 'text-gray-900'
    },
  ];

  return (
    <form onSubmit={handleSubmit} className="space-y-8">
      {/* SEO Settings */}
      <div className="space-y-6">
        <div className="flex items-center justify-between">
          <h3 className="text-lg font-medium text-gray-900">Search Engine Optimization</h3>
          <FiSearch className="text-gray-400" />
        </div>
        
        <div className="space-y-4">
          <div>
            <div className="flex items-center justify-between">
              <Label htmlFor="meta_title">Meta Title</Label>
              <Button
                type="button"
                variant="ghost"
                size="sm"
                onClick={generateMetaTitle}
                className="text-blue-600 hover:text-blue-700"
              >
                Auto-generate
              </Button>
            </div>
            <Input
              id="meta_title"
              value={formData.meta_title}
              onChange={(e) => handleInputChange('meta_title', e.target.value)}
              placeholder={church.name || 'Your Church Name'}
              maxLength={60}
            />
            <div className="flex items-center justify-between text-sm mt-1">
              <p className="text-gray-500">
                Appears as the clickable headline in search results
              </p>
              <span className={`${formData.meta_title.length > 60 ? 'text-red-500' : 'text-gray-500'}`}>
                {formData.meta_title.length}/60
              </span>
            </div>
          </div>

          <div>
            <div className="flex items-center justify-between">
              <Label htmlFor="meta_description">Meta Description</Label>
              <Button
                type="button"
                variant="ghost"
                size="sm"
                onClick={generateMetaDescription}
                className="text-blue-600 hover:text-blue-700"
              >
                Auto-generate
              </Button>
            </div>
            <Textarea
              id="meta_description"
              value={formData.meta_description}
              onChange={(e) => handleInputChange('meta_description', e.target.value)}
              placeholder="Brief description of your church that appears in search results..."
              rows={3}
              maxLength={160}
            />
            <div className="flex items-center justify-between text-sm mt-1">
              <p className="text-gray-500">
                Appears as the description snippet under your title in search results
              </p>
              <span className={`${formData.meta_description.length > 160 ? 'text-red-500' : 'text-gray-500'}`}>
                {formData.meta_description.length}/160
              </span>
            </div>
          </div>
        </div>

        {/* SEO Preview */}
        <div className="bg-gray-50 rounded-lg p-4">
          <h4 className="text-sm font-medium text-gray-900 mb-3">Search Result Preview</h4>
          <div className="bg-white rounded border p-3">
            <div className="text-blue-600 text-lg leading-tight hover:underline cursor-pointer">
              {formData.meta_title || church.name || 'Your Church Name'}
            </div>
            <div className="text-green-700 text-sm">
              {window.location.origin}/church/{church.slug || 'your-church'}
            </div>
            <div className="text-gray-600 text-sm mt-1">
              {formData.meta_description || church.short_description || 'Your church description will appear here...'}
            </div>
          </div>
        </div>
      </div>

      {/* Social Media Links */}
      <div className="space-y-6">
        <h3 className="text-lg font-medium text-gray-900">Social Media Profiles</h3>
        
        <div className="space-y-4">
          {socialPlatforms.map(platform => {
            const IconComponent = platform.icon;
            return (
              <div key={platform.key}>
                <Label htmlFor={platform.key} className="flex items-center space-x-2">
                  <IconComponent className={`w-4 h-4 ${platform.color}`} />
                  <span>{platform.label}</span>
                </Label>
                <Input
                  id={platform.key}
                  type="url"
                  value={formData[platform.key as keyof typeof formData]}
                  onChange={(e) => handleInputChange(platform.key, e.target.value)}
                  placeholder={platform.placeholder}
                />
              </div>
            );
          })}
        </div>

        <div className="bg-blue-50 border border-blue-200 rounded-lg p-4">
          <h4 className="text-sm font-medium text-blue-900 mb-2">Why add social media links?</h4>
          <ul className="text-sm text-blue-800 space-y-1">
            <li>• Helps people find and connect with your church online</li>
            <li>• Improves your church's visibility in local search results</li>
            <li>• Makes it easy for visitors to follow your latest updates</li>
            <li>• Shows your church is active and engaging with the community</li>
          </ul>
        </div>
      </div>

      {/* Technical SEO Info */}
      <div className="space-y-4 pt-6 border-t border-gray-200">
        <h4 className="text-md font-medium text-gray-900">Technical Information</h4>
        <div className="grid grid-cols-1 md:grid-cols-2 gap-4 text-sm">
          <div className="bg-gray-50 rounded-lg p-3">
            <p className="font-medium text-gray-900 mb-1">Canonical URL</p>
            <p className="text-gray-600 font-mono break-all">
              {window.location.origin}/church/{church.slug || 'your-church'}
            </p>
          </div>
          <div className="bg-gray-50 rounded-lg p-3">
            <p className="font-medium text-gray-900 mb-1">Schema.org Type</p>
            <p className="text-gray-600">
              Organization, Place of Worship, Local Business
            </p>
          </div>
        </div>
      </div>

      {/* Submit Button */}
      <div className="flex justify-end pt-6 border-t border-gray-200">
        <Button
          type="submit"
          loading={updateMutation.isPending}
          className="w-full md:w-auto"
        >
          Save SEO & Social Settings
        </Button>
      </div>
    </form>
  );
}