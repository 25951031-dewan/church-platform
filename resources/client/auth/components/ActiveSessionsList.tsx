import React, { useState, useEffect } from 'react';
import { Smartphone, Monitor, Tablet, MapPin, Calendar, LogOut } from 'lucide-react';
import { Card, CardContent, CardHeader, CardTitle } from '@ui/card';
import { Button } from '@ui/button';
import { Badge } from '@ui/badge';
import { useAuth } from '@/hooks/useAuth';
import { apiClient } from '@app/common/http/api-client';
import { toast } from 'react-hot-toast';

interface ActiveSession {
  id: string;
  device_name: string;
  device_type: 'mobile' | 'desktop' | 'tablet';
  ip_address: string;
  location?: string;
  user_agent: string;
  is_current: boolean;
  last_activity: string;
  created_at: string;
}

interface ActiveSessionsListProps {
  onBack?: () => void;
}

const ActiveSessionsList: React.FC<ActiveSessionsListProps> = ({ onBack }) => {
  const { user } = useAuth();
  const [sessions, setSessions] = useState<ActiveSession[]>([]);
  const [isLoading, setIsLoading] = useState(true);
  const [revokingSession, setRevokingSession] = useState<string | null>(null);
  const [revokingAll, setRevokingAll] = useState(false);

  useEffect(() => {
    loadSessions();
  }, []);

  const loadSessions = async () => {
    setIsLoading(true);
    try {
      const response = await apiClient.get('/user/sessions');
      setSessions(response.sessions || []);
    } catch (error) {
      toast.error('Failed to load sessions');
    } finally {
      setIsLoading(false);
    }
  };

  const revokeSession = async (sessionId: string) => {
    setRevokingSession(sessionId);
    try {
      await apiClient.delete(`/user/sessions/${sessionId}`);
      setSessions(sessions.filter(session => session.id !== sessionId));
      toast.success('Session terminated successfully');
    } catch (error) {
      toast.error('Failed to terminate session');
    } finally {
      setRevokingSession(null);
    }
  };

  const revokeAllOtherSessions = async () => {
    setRevokingAll(true);
    try {
      const response = await apiClient.delete('/user/sessions');
      await loadSessions(); // Reload to get updated list
      toast.success(`${response.revoked_count || 0} sessions terminated`);
    } catch (error) {
      toast.error('Failed to terminate sessions');
    } finally {
      setRevokingAll(false);
    }
  };

  const getDeviceIcon = (deviceType: string) => {
    switch (deviceType) {
      case 'mobile':
        return <Smartphone className="w-5 h-5" />;
      case 'tablet':
        return <Tablet className="w-5 h-5" />;
      default:
        return <Monitor className="w-5 h-5" />;
    }
  };

  const formatLastActivity = (dateString: string) => {
    const date = new Date(dateString);
    const now = new Date();
    const diffInMinutes = Math.floor((now.getTime() - date.getTime()) / (1000 * 60));
    
    if (diffInMinutes < 1) return 'Just now';
    if (diffInMinutes < 60) return `${diffInMinutes} minutes ago`;
    if (diffInMinutes < 1440) return `${Math.floor(diffInMinutes / 60)} hours ago`;
    return `${Math.floor(diffInMinutes / 1440)} days ago`;
  };

  const parseUserAgent = (userAgent: string) => {
    // Simple user agent parsing - you might want to use a library for this
    const browser = userAgent.includes('Chrome') ? 'Chrome' : 
                   userAgent.includes('Firefox') ? 'Firefox' :
                   userAgent.includes('Safari') ? 'Safari' :
                   userAgent.includes('Edge') ? 'Edge' : 'Unknown';
    
    const os = userAgent.includes('Windows') ? 'Windows' :
               userAgent.includes('Macintosh') ? 'macOS' :
               userAgent.includes('Linux') ? 'Linux' :
               userAgent.includes('iPhone') ? 'iOS' :
               userAgent.includes('Android') ? 'Android' : 'Unknown';
    
    return `${browser} on ${os}`;
  };

  if (isLoading) {
    return (
      <div className="flex items-center justify-center py-8">
        <div className="w-6 h-6 border-2 border-blue-600 border-t-transparent rounded-full animate-spin mr-2" />
        <span>Loading active sessions...</span>
      </div>
    );
  }

  const currentSession = sessions.find(session => session.is_current);
  const otherSessions = sessions.filter(session => !session.is_current);

  return (
    <div className="max-w-4xl mx-auto space-y-6">
      {/* Header */}
      <div className="flex items-center justify-between">
        <div>
          <h2 className="text-2xl font-bold text-gray-900 dark:text-white">
            Active Sessions
          </h2>
          <p className="text-gray-600 dark:text-gray-400">
            Manage devices that are signed into your account
          </p>
        </div>
        {onBack && (
          <Button variant="outline" onClick={onBack}>
            Back to Security
          </Button>
        )}
      </div>

      {/* Current Session */}
      <Card>
        <CardHeader>
          <CardTitle className="flex items-center gap-2">
            <div className="w-3 h-3 bg-green-500 rounded-full"></div>
            Current Session
          </CardTitle>
        </CardHeader>
        <CardContent>
          {currentSession ? (
            <div className="flex items-start gap-4">
              <div className="p-3 bg-green-100 dark:bg-green-900/30 rounded-lg">
                {getDeviceIcon(currentSession.device_type)}
              </div>
              <div className="flex-1">
                <div className="flex items-center gap-2 mb-1">
                  <h4 className="font-medium text-gray-900 dark:text-white">
                    {currentSession.device_name || 'This Device'}
                  </h4>
                  <Badge variant="success">Current</Badge>
                </div>
                <p className="text-sm text-gray-600 dark:text-gray-400 mb-1">
                  {parseUserAgent(currentSession.user_agent)}
                </p>
                <div className="flex items-center gap-4 text-xs text-gray-500 dark:text-gray-500">
                  <div className="flex items-center gap-1">
                    <MapPin className="w-3 h-3" />
                    <span>{currentSession.location || currentSession.ip_address}</span>
                  </div>
                  <div className="flex items-center gap-1">
                    <Calendar className="w-3 h-3" />
                    <span>Active {formatLastActivity(currentSession.last_activity)}</span>
                  </div>
                </div>
              </div>
            </div>
          ) : (
            <p className="text-gray-500 dark:text-gray-500">No current session found</p>
          )}
        </CardContent>
      </Card>

      {/* Other Sessions */}
      {otherSessions.length > 0 && (
        <Card>
          <CardHeader>
            <div className="flex items-center justify-between">
              <CardTitle>Other Sessions</CardTitle>
              <Button
                variant="outline"
                size="sm"
                onClick={revokeAllOtherSessions}
                disabled={revokingAll}
              >
                {revokingAll ? 'Terminating...' : 'Terminate All'}
              </Button>
            </div>
          </CardHeader>
          <CardContent>
            <div className="space-y-4">
              {otherSessions.map((session) => (
                <div key={session.id} className="flex items-start gap-4 p-4 border border-gray-200 dark:border-gray-700 rounded-lg">
                  <div className="p-3 bg-gray-100 dark:bg-gray-800 rounded-lg">
                    {getDeviceIcon(session.device_type)}
                  </div>
                  <div className="flex-1">
                    <h4 className="font-medium text-gray-900 dark:text-white mb-1">
                      {session.device_name || 'Unknown Device'}
                    </h4>
                    <p className="text-sm text-gray-600 dark:text-gray-400 mb-2">
                      {parseUserAgent(session.user_agent)}
                    </p>
                    <div className="flex items-center gap-4 text-xs text-gray-500 dark:text-gray-500">
                      <div className="flex items-center gap-1">
                        <MapPin className="w-3 h-3" />
                        <span>{session.location || session.ip_address}</span>
                      </div>
                      <div className="flex items-center gap-1">
                        <Calendar className="w-3 h-3" />
                        <span>Last active {formatLastActivity(session.last_activity)}</span>
                      </div>
                    </div>
                  </div>
                  <Button
                    variant="outline"
                    size="sm"
                    onClick={() => revokeSession(session.id)}
                    disabled={revokingSession === session.id}
                  >
                    {revokingSession === session.id ? (
                      <div className="w-4 h-4 border-2 border-gray-400 border-t-transparent rounded-full animate-spin" />
                    ) : (
                      <LogOut className="w-4 h-4" />
                    )}
                  </Button>
                </div>
              ))}
            </div>
          </CardContent>
        </Card>
      )}

      {otherSessions.length === 0 && (
        <Card>
          <CardContent className="p-8 text-center">
            <div className="w-16 h-16 bg-green-100 dark:bg-green-900/30 rounded-full flex items-center justify-center mx-auto mb-4">
              <Smartphone className="w-8 h-8 text-green-600 dark:text-green-400" />
            </div>
            <h3 className="font-medium text-gray-900 dark:text-white mb-2">
              Only one active session
            </h3>
            <p className="text-sm text-gray-600 dark:text-gray-400">
              You're only signed in on this device. Great security practice!
            </p>
          </CardContent>
        </Card>
      )}

      {/* Security Tips */}
      <Card>
        <CardHeader>
          <CardTitle>Security Tips</CardTitle>
        </CardHeader>
        <CardContent>
          <div className="space-y-3">
            <div className="flex items-start gap-3">
              <div className="w-2 h-2 rounded-full mt-2 bg-blue-500"></div>
              <div>
                <p className="text-sm font-medium text-gray-900 dark:text-white">
                  Review sessions regularly
                </p>
                <p className="text-xs text-gray-600 dark:text-gray-400">
                  Check this page periodically to ensure all sessions are recognized.
                </p>
              </div>
            </div>
            
            <div className="flex items-start gap-3">
              <div className="w-2 h-2 rounded-full mt-2 bg-amber-500"></div>
              <div>
                <p className="text-sm font-medium text-gray-900 dark:text-white">
                  Sign out of public devices
                </p>
                <p className="text-xs text-gray-600 dark:text-gray-400">
                  Always sign out when using shared or public computers.
                </p>
              </div>
            </div>
            
            <div className="flex items-start gap-3">
              <div className="w-2 h-2 rounded-full mt-2 bg-red-500"></div>
              <div>
                <p className="text-sm font-medium text-gray-900 dark:text-white">
                  Terminate suspicious sessions
                </p>
                <p className="text-xs text-gray-600 dark:text-gray-400">
                  If you see an unrecognized device or location, terminate that session immediately.
                </p>
              </div>
            </div>
          </div>
        </CardContent>
      </Card>
    </div>
  );
};

export default ActiveSessionsList;