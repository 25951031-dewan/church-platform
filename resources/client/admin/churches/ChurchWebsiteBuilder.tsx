import React, { useState, useEffect } from 'react';
import { useParams, useNavigate } from 'react-router-dom';
import { useQuery, useMutation, useQueryClient } from '@tanstack/react-query';
import { toast } from 'react-hot-toast';
import { 
  FiSettings, 
  FiEdit, 
  FiPalette, 
  FiSearch, 
  FiSave, 
  FiEye 
} from 'react-icons/fi';

import { Button } from '@/components/ui/Button';
import { Tabs, TabsContent, TabsList, TabsTrigger } from '@/components/ui/Tabs';
import GeneralTab from './tabs/GeneralTab';
import AboutTab from './tabs/AboutTab';
import AppearanceTab from './tabs/AppearanceTab';
import SeoTab from './tabs/SeoTab';

export default function ChurchWebsiteBuilder() {
  const { churchId } = useParams<{ churchId: string }>();
  const navigate = useNavigate();
  const queryClient = useQueryClient();
  const [activeTab, setActiveTab] = useState('general');

  // Fetch church data for website builder
  const { data: church, isLoading } = useQuery({
    queryKey: ['church-website', churchId],
    queryFn: async () => {
      const response = await fetch(`/api/churches/${churchId}/website`);
      if (!response.ok) throw new Error('Failed to load church data');
      const data = await response.json();
      return data.church;
    },
    enabled: !!churchId,
  });

  const handlePreview = () => {
    if (church?.slug) {
      window.open(`/church/${church.slug}`, '_blank');
    }
  };

  if (isLoading) {
    return (
      <div className="flex items-center justify-center h-64">
        <div className="animate-spin rounded-full h-8 w-8 border-b-2 border-primary"></div>
      </div>
    );
  }

  if (!church) {
    return (
      <div className="text-center py-8">
        <h2 className="text-xl font-semibold text-gray-900">Church not found</h2>
        <p className="text-gray-600 mt-2">You don't have permission to edit this church.</p>
      </div>
    );
  }

  return (
    <div className="max-w-6xl mx-auto p-6">
      {/* Header */}
      <div className="flex items-center justify-between mb-8">
        <div>
          <h1 className="text-2xl font-bold text-gray-900">Website Builder</h1>
          <p className="text-gray-600">Customize your church's public page</p>
        </div>
        <div className="flex items-center space-x-3">
          <Button
            variant="outline"
            onClick={handlePreview}
            icon={<FiEye />}
          >
            Preview Site
          </Button>
        </div>
      </div>

      {/* Church Info Bar */}
      <div className="bg-white rounded-lg shadow-sm border p-4 mb-6">
        <div className="flex items-center space-x-4">
          {church.logo_url && (
            <img 
              src={church.logo_url} 
              alt={church.name}
              className="w-12 h-12 rounded-lg object-cover"
            />
          )}
          <div>
            <h2 className="font-semibold text-gray-900">{church.name}</h2>
            <p className="text-sm text-gray-600">
              {church.city && church.state && `${church.city}, ${church.state}`}
            </p>
            {church.slug && (
              <p className="text-sm text-blue-600 font-mono">
                {window.location.origin}/church/{church.slug}
              </p>
            )}
          </div>
        </div>
      </div>

      {/* Tabbed Interface */}
      <div className="bg-white rounded-lg shadow-sm border">
        <Tabs value={activeTab} onValueChange={setActiveTab}>
          <TabsList className="border-b border-gray-200 bg-gray-50 rounded-t-lg">
            <TabsTrigger 
              value="general" 
              className="flex items-center space-x-2"
            >
              <FiSettings className="w-4 h-4" />
              <span>General Settings</span>
            </TabsTrigger>
            <TabsTrigger 
              value="about" 
              className="flex items-center space-x-2"
            >
              <FiEdit className="w-4 h-4" />
              <span>About & History</span>
            </TabsTrigger>
            <TabsTrigger 
              value="appearance" 
              className="flex items-center space-x-2"
            >
              <FiPalette className="w-4 h-4" />
              <span>Appearance</span>
            </TabsTrigger>
            <TabsTrigger 
              value="seo" 
              className="flex items-center space-x-2"
            >
              <FiSearch className="w-4 h-4" />
              <span>SEO & Social</span>
            </TabsTrigger>
          </TabsList>

          <div className="p-6">
            <TabsContent value="general">
              <GeneralTab church={church} churchId={churchId!} />
            </TabsContent>

            <TabsContent value="about">
              <AboutTab church={church} churchId={churchId!} />
            </TabsContent>

            <TabsContent value="appearance">
              <AppearanceTab church={church} churchId={churchId!} />
            </TabsContent>

            <TabsContent value="seo">
              <SeoTab church={church} churchId={churchId!} />
            </TabsContent>
          </div>
        </Tabs>
      </div>
    </div>
  );
}