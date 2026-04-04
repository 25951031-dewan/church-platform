import React, { useState, useEffect } from 'react';
import { Shield, Smartphone, Key, LogOut, AlertCircle } from 'lucide-react';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Button } from '@/components/ui/button';
import { Badge } from '@/components/ui/badge';
import { useAuth } from '@/hooks/useAuth';
import { apiClient } from '@/lib/api-client';
import { toast } from 'react-hot-toast';
import TwoFactorSetup from '../../components/TwoFactorSetup';
import ChangePasswordForm from '../../components/ChangePasswordForm';
import ActiveSessionsList from '../../components/ActiveSessionsList';

const SecurityPage: React.FC = () => {
  const { user } = useAuth();
  const [activeComponent, setActiveComponent] = useState<'overview' | '2fa' | 'password' | 'sessions'>('overview');

  const renderContent = () => {
    switch (activeComponent) {
      case '2fa':
        return <TwoFactorSetup onBack={() => setActiveComponent('overview')} />;
      case 'password':
        return <ChangePasswordForm onBack={() => setActiveComponent('overview')} />;
      case 'sessions':
        return <ActiveSessionsList onBack={() => setActiveComponent('overview')} />;
      default:
        return renderOverview();
    }
  };

  const renderOverview = () => (
    <div className="space-y-6">
      {/* Security Status */}
      <Card>
        <CardHeader>
          <CardTitle className="flex items-center gap-2">
            <Shield className="w-5 h-5" />
            Security Status
          </CardTitle>
        </CardHeader>
        <CardContent>
          <div className="flex items-center justify-between p-4 bg-green-50 dark:bg-green-900/20 border border-green-200 dark:border-green-800 rounded-lg">
            <div className="flex items-center gap-3">
              <div className="w-3 h-3 bg-green-500 rounded-full"></div>
              <div>
                <p className="font-medium text-green-900 dark:text-green-100">
                  Account Secure
                </p>
                <p className="text-sm text-green-700 dark:text-green-300">
                  Your account is protected with strong security measures
                </p>
              </div>
            </div>
            <Badge variant="success">
              {user?.two_factor_enabled ? 'Enhanced' : 'Standard'}
            </Badge>
          </div>
        </CardContent>
      </Card>

      {/* Security Features */}
      <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
        {/* Two-Factor Authentication */}
        <Card className="cursor-pointer hover:shadow-md transition-shadow" onClick={() => setActiveComponent('2fa')}>
          <CardContent className="p-6">
            <div className="flex items-start justify-between">
              <div className="flex items-start gap-3">
                <div className="p-2 bg-blue-100 dark:bg-blue-900/30 rounded-lg">
                  <Shield className="w-5 h-5 text-blue-600 dark:text-blue-400" />
                </div>
                <div>
                  <h3 className="font-semibold text-gray-900 dark:text-white mb-1">
                    Two-Factor Authentication
                  </h3>
                  <p className="text-sm text-gray-600 dark:text-gray-400 mb-2">
                    Add an extra layer of security to your account
                  </p>
                  <Badge variant={user?.two_factor_enabled ? 'success' : 'secondary'}>
                    {user?.two_factor_enabled ? 'Enabled' : 'Disabled'}
                  </Badge>
                </div>
              </div>
            </div>
          </CardContent>
        </Card>

        {/* Password */}
        <Card className="cursor-pointer hover:shadow-md transition-shadow" onClick={() => setActiveComponent('password')}>
          <CardContent className="p-6">
            <div className="flex items-start gap-3">
              <div className="p-2 bg-amber-100 dark:bg-amber-900/30 rounded-lg">
                <Key className="w-5 h-5 text-amber-600 dark:text-amber-400" />
              </div>
              <div>
                <h3 className="font-semibold text-gray-900 dark:text-white mb-1">
                  Password
                </h3>
                <p className="text-sm text-gray-600 dark:text-gray-400 mb-2">
                  Update your password regularly for security
                </p>
                <p className="text-xs text-gray-500 dark:text-gray-500">
                  Last changed: {user?.password_changed_at || 'Never'}
                </p>
              </div>
            </div>
          </CardContent>
        </Card>

        {/* Active Sessions */}
        <Card className="cursor-pointer hover:shadow-md transition-shadow" onClick={() => setActiveComponent('sessions')}>
          <CardContent className="p-6">
            <div className="flex items-start gap-3">
              <div className="p-2 bg-green-100 dark:bg-green-900/30 rounded-lg">
                <Smartphone className="w-5 h-5 text-green-600 dark:text-green-400" />
              </div>
              <div>
                <h3 className="font-semibold text-gray-900 dark:text-white mb-1">
                  Active Sessions
                </h3>
                <p className="text-sm text-gray-600 dark:text-gray-400 mb-2">
                  Manage devices signed into your account
                </p>
                <p className="text-xs text-gray-500 dark:text-gray-500">
                  {user?.active_sessions_count || 1} active session{(user?.active_sessions_count || 1) !== 1 ? 's' : ''}
                </p>
              </div>
            </div>
          </CardContent>
        </Card>

        {/* Account Recovery */}
        <Card>
          <CardContent className="p-6">
            <div className="flex items-start gap-3">
              <div className="p-2 bg-purple-100 dark:bg-purple-900/30 rounded-lg">
                <AlertCircle className="w-5 h-5 text-purple-600 dark:text-purple-400" />
              </div>
              <div>
                <h3 className="font-semibold text-gray-900 dark:text-white mb-1">
                  Account Recovery
                </h3>
                <p className="text-sm text-gray-600 dark:text-gray-400 mb-2">
                  Ensure you can recover your account if needed
                </p>
                <div className="flex items-center gap-2">
                  <Badge variant={user?.email_verified ? 'success' : 'destructive'}>
                    Email {user?.email_verified ? 'Verified' : 'Unverified'}
                  </Badge>
                  {user?.phone && (
                    <Badge variant={user?.phone_verified ? 'success' : 'secondary'}>
                      Phone {user?.phone_verified ? 'Verified' : 'Unverified'}
                    </Badge>
                  )}
                </div>
              </div>
            </div>
          </CardContent>
        </Card>
      </div>

      {/* Security Tips */}
      <Card>
        <CardHeader>
          <CardTitle>Security Recommendations</CardTitle>
        </CardHeader>
        <CardContent>
          <div className="space-y-3">
            <div className="flex items-start gap-3">
              <div className={`w-2 h-2 rounded-full mt-2 ${user?.two_factor_enabled ? 'bg-green-500' : 'bg-amber-500'}`}></div>
              <div>
                <p className="text-sm font-medium text-gray-900 dark:text-white">
                  Enable Two-Factor Authentication
                </p>
                <p className="text-xs text-gray-600 dark:text-gray-400">
                  {user?.two_factor_enabled 
                    ? 'Great! Your account is protected with 2FA.' 
                    : 'Add an extra layer of security to prevent unauthorized access.'
                  }
                </p>
              </div>
            </div>
            
            <div className="flex items-start gap-3">
              <div className="w-2 h-2 rounded-full mt-2 bg-blue-500"></div>
              <div>
                <p className="text-sm font-medium text-gray-900 dark:text-white">
                  Use a Strong Password
                </p>
                <p className="text-xs text-gray-600 dark:text-gray-400">
                  Use a unique password with a mix of letters, numbers, and symbols.
                </p>
              </div>
            </div>
            
            <div className="flex items-start gap-3">
              <div className="w-2 h-2 rounded-full mt-2 bg-purple-500"></div>
              <div>
                <p className="text-sm font-medium text-gray-900 dark:text-white">
                  Review Active Sessions
                </p>
                <p className="text-xs text-gray-600 dark:text-gray-400">
                  Regularly check for unrecognized devices and sign them out.
                </p>
              </div>
            </div>
          </div>
        </CardContent>
      </Card>
    </div>
  );

  return (
    <div className="max-w-4xl mx-auto p-6">
      {/* Header */}
      <div className="mb-8">
        <h1 className="text-3xl font-bold text-gray-900 dark:text-white">
          Security Settings
        </h1>
        <p className="mt-2 text-gray-600 dark:text-gray-400">
          Manage your account security and login preferences
        </p>
      </div>

      {/* Content */}
      {renderContent()}
    </div>
  );
};

export default SecurityPage;