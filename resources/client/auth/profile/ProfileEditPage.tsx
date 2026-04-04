import React, { useState, Suspense, lazy } from 'react';
import { Tabs, TabsContent, TabsList, TabsTrigger } from '@ui/tabs';
import { User, Heart, Church, Shield, Eye } from 'lucide-react';
import { useAuth } from '@/hooks/useAuth';
import LoadingSpinner from '@/components/LoadingSpinner';

// Lazy load tab components for better performance
const BasicInfoTab = lazy(() => import('./tabs/BasicInfoTab'));
const SpiritualProfileTab = lazy(() => import('./tabs/SpiritualProfileTab'));
const MinistryRolesTab = lazy(() => import('./tabs/MinistryRolesTab'));
const PrivacySettingsTab = lazy(() => import('./tabs/PrivacySettingsTab'));

interface TabConfig {
  value: string;
  label: string;
  icon: React.ComponentType<{ className?: string }>;
  component: React.ComponentType;
}

const ProfileEditPage: React.FC = () => {
  const { user } = useAuth();
  const [activeTab, setActiveTab] = useState('basic');

  const tabs: TabConfig[] = [
    {
      value: 'basic',
      label: 'Basic Info',
      icon: User,
      component: BasicInfoTab
    },
    {
      value: 'spiritual',
      label: 'Spiritual Profile',
      icon: Heart,
      component: SpiritualProfileTab
    },
    {
      value: 'ministry',
      label: 'Ministry Roles',
      icon: Church,
      component: MinistryRolesTab
    },
    {
      value: 'privacy',
      label: 'Privacy',
      icon: Eye,
      component: PrivacySettingsTab
    }
  ];

  if (!user) {
    return <LoadingSpinner />;
  }

  return (
    <div className="max-w-4xl mx-auto p-6">
      {/* Header */}
      <div className="mb-8">
        <h1 className="text-3xl font-bold text-gray-900 dark:text-white">
          Profile Settings
        </h1>
        <p className="mt-2 text-gray-600 dark:text-gray-400">
          Manage your personal information and church profile
        </p>
      </div>

      {/* Progress Indicator */}
      {typeof user.profile_completeness === 'number' && (
        <div className="mb-6 p-4 bg-blue-50 dark:bg-blue-900/20 border border-blue-200 dark:border-blue-800 rounded-lg">
          <div className="flex items-center justify-between mb-2">
            <span className="text-sm font-medium text-blue-900 dark:text-blue-100">
              Profile Completeness
            </span>
            <span className="text-sm text-blue-700 dark:text-blue-300">
              {user.profile_completeness}%
            </span>
          </div>
          <div className="h-2 bg-blue-200 dark:bg-blue-900 rounded-full overflow-hidden">
            <div 
              className="h-full bg-blue-600 dark:bg-blue-400 transition-all duration-300"
              style={{ width: `${user.profile_completeness}%` }}
            />
          </div>
        </div>
      )}

      {/* Tabs */}
      <Tabs value={activeTab} onValueChange={setActiveTab} className="space-y-6">
        <TabsList className="grid w-full grid-cols-4">
          {tabs.map(({ value, label, icon: Icon }) => (
            <TabsTrigger
              key={value}
              value={value}
              className="flex items-center gap-2 px-3 py-2"
            >
              <Icon className="w-4 h-4" />
              <span className="hidden sm:inline">{label}</span>
            </TabsTrigger>
          ))}
        </TabsList>

        {tabs.map(({ value, component: Component }) => (
          <TabsContent key={value} value={value} className="mt-6">
            <Suspense fallback={<LoadingSpinner />}>
              <Component />
            </Suspense>
          </TabsContent>
        ))}
      </Tabs>
    </div>
  );
};

export default ProfileEditPage;