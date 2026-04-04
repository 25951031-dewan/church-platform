import React, { useState, useEffect } from 'react';
import { Shield, Smartphone, Copy, Check, ArrowLeft, AlertTriangle } from 'lucide-react';
import { Card, CardContent, CardHeader, CardTitle } from '@ui/card';
import { Button } from '@ui/button';
import { Input } from '@ui/input';
import { Label } from '@ui/label';
import { Badge } from '@ui/badge';
import { useAuth } from '@/hooks/useAuth';
import { apiClient } from '@/lib/api-client';
import { toast } from 'react-hot-toast';

interface TwoFactorSetupProps {
  onBack?: () => void;
}

type SetupStep = 'overview' | 'setup' | 'verify' | 'enabled' | 'backup-codes';

const TwoFactorSetup: React.FC<TwoFactorSetupProps> = ({ onBack }) => {
  const { user, updateUser } = useAuth();
  const [step, setStep] = useState<SetupStep>(user?.two_factor_enabled ? 'enabled' : 'overview');
  const [qrCode, setQrCode] = useState<string>('');
  const [secret, setSecret] = useState<string>('');
  const [verificationCode, setVerificationCode] = useState<string>('');
  const [backupCodes, setBackupCodes] = useState<string[]>([]);
  const [isLoading, setIsLoading] = useState(false);
  const [copiedSecret, setCopiedSecret] = useState(false);

  const handleEnable2FA = async () => {
    setIsLoading(true);
    try {
      const response = await apiClient.get('/user/2fa/qr-code');
      setQrCode(response.qr_code);
      setSecret(response.secret);
      setStep('setup');
    } catch (error) {
      toast.error('Failed to generate QR code');
    } finally {
      setIsLoading(false);
    }
  };

  const handleVerify = async () => {
    if (verificationCode.length !== 6) {
      toast.error('Please enter a 6-digit code');
      return;
    }

    setIsLoading(true);
    try {
      const response = await apiClient.post('/user/2fa/enable', { 
        code: verificationCode 
      });
      
      updateUser({ ...user!, two_factor_enabled: true });
      setBackupCodes(response.backup_codes || []);
      setStep('backup-codes');
      toast.success('Two-factor authentication enabled successfully');
    } catch (error) {
      toast.error('Invalid verification code. Please try again.');
    } finally {
      setIsLoading(false);
    }
  };

  const handleDisable2FA = async () => {
    setIsLoading(true);
    try {
      await apiClient.post('/user/2fa/disable');
      updateUser({ ...user!, two_factor_enabled: false });
      setStep('overview');
      toast.success('Two-factor authentication disabled');
    } catch (error) {
      toast.error('Failed to disable 2FA');
    } finally {
      setIsLoading(false);
    }
  };

  const copySecret = () => {
    navigator.clipboard.writeText(secret);
    setCopiedSecret(true);
    toast.success('Secret copied to clipboard');
    setTimeout(() => setCopiedSecret(false), 2000);
  };

  const copyBackupCodes = () => {
    navigator.clipboard.writeText(backupCodes.join('\n'));
    toast.success('Backup codes copied to clipboard');
  };

  const renderStepContent = () => {
    switch (step) {
      case 'overview':
        return (
          <Card>
            <CardHeader>
              <CardTitle className="flex items-center gap-2">
                <Shield className="w-5 h-5" />
                Enable Two-Factor Authentication
              </CardTitle>
            </CardHeader>
            <CardContent className="space-y-4">
              <div className="p-4 bg-blue-50 dark:bg-blue-900/20 border border-blue-200 dark:border-blue-800 rounded-lg">
                <h4 className="font-medium text-blue-900 dark:text-blue-100 mb-2">
                  What is Two-Factor Authentication?
                </h4>
                <p className="text-sm text-blue-800 dark:text-blue-200 mb-3">
                  2FA adds an extra layer of security by requiring a second form of verification when signing in.
                </p>
                <div className="space-y-1 text-sm text-blue-700 dark:text-blue-300">
                  <p>• Protects your account even if your password is compromised</p>
                  <p>• Uses your smartphone as the second factor</p>
                  <p>• Works with Google Authenticator and similar apps</p>
                </div>
              </div>
              
              <div className="flex justify-between items-center">
                <Button onClick={handleEnable2FA} disabled={isLoading}>
                  {isLoading ? 'Setting up...' : 'Enable 2FA'}
                </Button>
                {onBack && (
                  <Button variant="outline" onClick={onBack}>
                    <ArrowLeft className="w-4 h-4 mr-2" />
                    Back
                  </Button>
                )}
              </div>
            </CardContent>
          </Card>
        );

      case 'setup':
        return (
          <Card>
            <CardHeader>
              <CardTitle>Scan QR Code</CardTitle>
            </CardHeader>
            <CardContent className="space-y-6">
              <div className="text-center">
                <p className="text-sm text-gray-600 dark:text-gray-400 mb-4">
                  Scan this QR code with Google Authenticator or a similar app:
                </p>
                <div className="inline-block p-4 bg-white rounded-lg">
                  <img src={qrCode} alt="2FA QR Code" className="w-48 h-48" />
                </div>
              </div>

              <div>
                <Label htmlFor="manual-secret">Or enter this code manually:</Label>
                <div className="flex items-center gap-2 mt-1">
                  <Input
                    id="manual-secret"
                    value={secret}
                    readOnly
                    className="font-mono text-sm"
                  />
                  <Button
                    variant="outline"
                    size="sm"
                    onClick={copySecret}
                    disabled={copiedSecret}
                  >
                    {copiedSecret ? (
                      <Check className="w-4 h-4" />
                    ) : (
                      <Copy className="w-4 h-4" />
                    )}
                  </Button>
                </div>
              </div>

              <div className="flex gap-3">
                <Button
                  variant="outline"
                  onClick={() => setStep('overview')}
                  disabled={isLoading}
                >
                  Back
                </Button>
                <Button 
                  onClick={() => setStep('verify')}
                  disabled={isLoading}
                >
                  Continue
                </Button>
              </div>
            </CardContent>
          </Card>
        );

      case 'verify':
        return (
          <Card>
            <CardHeader>
              <CardTitle>Verify Setup</CardTitle>
            </CardHeader>
            <CardContent className="space-y-4">
              <div>
                <p className="text-sm text-gray-600 dark:text-gray-400 mb-4">
                  Enter the 6-digit code from your authenticator app to verify the setup:
                </p>
                <Label htmlFor="verification-code">Verification Code</Label>
                <Input
                  id="verification-code"
                  type="text"
                  placeholder="000000"
                  maxLength={6}
                  value={verificationCode}
                  onChange={(e) => setVerificationCode(e.target.value.replace(/\D/g, ''))}
                  className="text-center text-lg font-mono"
                />
              </div>

              <div className="flex gap-3">
                <Button
                  variant="outline"
                  onClick={() => setStep('setup')}
                  disabled={isLoading}
                >
                  Back
                </Button>
                <Button 
                  onClick={handleVerify}
                  disabled={isLoading || verificationCode.length !== 6}
                >
                  {isLoading ? 'Verifying...' : 'Verify & Enable'}
                </Button>
              </div>
            </CardContent>
          </Card>
        );

      case 'backup-codes':
        return (
          <Card>
            <CardHeader>
              <CardTitle className="flex items-center gap-2">
                <AlertTriangle className="w-5 h-5 text-amber-500" />
                Save Your Backup Codes
              </CardTitle>
            </CardHeader>
            <CardContent className="space-y-4">
              <div className="p-4 bg-amber-50 dark:bg-amber-900/20 border border-amber-200 dark:border-amber-800 rounded-lg">
                <p className="text-sm text-amber-800 dark:text-amber-200 mb-3">
                  <strong>Important:</strong> Save these backup codes in a safe place. 
                  You can use them to access your account if you lose your phone.
                </p>
                <div className="grid grid-cols-2 gap-2 font-mono text-sm">
                  {backupCodes.map((code, index) => (
                    <div key={index} className="p-2 bg-white dark:bg-gray-800 rounded border">
                      {code}
                    </div>
                  ))}
                </div>
                <Button
                  variant="outline"
                  size="sm"
                  onClick={copyBackupCodes}
                  className="mt-3"
                >
                  <Copy className="w-4 h-4 mr-2" />
                  Copy All Codes
                </Button>
              </div>

              <Button onClick={() => setStep('enabled')} className="w-full">
                I've Saved My Backup Codes
              </Button>
            </CardContent>
          </Card>
        );

      case 'enabled':
        return (
          <Card>
            <CardHeader>
              <CardTitle className="flex items-center gap-2">
                <Shield className="w-5 h-5 text-green-500" />
                Two-Factor Authentication Enabled
              </CardTitle>
            </CardHeader>
            <CardContent className="space-y-4">
              <div className="p-4 bg-green-50 dark:bg-green-900/20 border border-green-200 dark:border-green-800 rounded-lg">
                <div className="flex items-center gap-2 mb-2">
                  <Badge variant="success">Active</Badge>
                  <span className="text-sm text-green-800 dark:text-green-200">
                    Your account is now protected with 2FA
                  </span>
                </div>
                <p className="text-sm text-green-700 dark:text-green-300">
                  You'll need to enter a code from your authenticator app each time you sign in.
                </p>
              </div>

              <div className="space-y-2">
                <h4 className="font-medium text-gray-900 dark:text-white">
                  What's next?
                </h4>
                <ul className="text-sm text-gray-600 dark:text-gray-400 space-y-1">
                  <li>• Keep your backup codes in a safe place</li>
                  <li>• Make sure your authenticator app is backed up</li>
                  <li>• Test signing out and back in to verify it works</li>
                </ul>
              </div>

              <div className="flex gap-3">
                <Button
                  variant="destructive"
                  onClick={handleDisable2FA}
                  disabled={isLoading}
                  size="sm"
                >
                  {isLoading ? 'Disabling...' : 'Disable 2FA'}
                </Button>
                {onBack && (
                  <Button variant="outline" onClick={onBack}>
                    <ArrowLeft className="w-4 h-4 mr-2" />
                    Back to Security
                  </Button>
                )}
              </div>
            </CardContent>
          </Card>
        );

      default:
        return null;
    }
  };

  return (
    <div className="max-w-md mx-auto">
      {renderStepContent()}
    </div>
  );
};

export default TwoFactorSetup;