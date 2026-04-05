import React, { useState, useRef, useEffect } from 'react';
import { User, Shield, Smartphone, Heart, Settings, HelpCircle, LogOut, Church, Camera } from 'lucide-react';
import { useAuth } from '@app/common/auth/use-auth';
import { Link } from 'react-router';

interface UserDropdownProps {
  isOpen: boolean;
  onClose: () => void;
}

interface MenuItem {
  icon: React.ComponentType<{ className?: string }>;
  label: string;
  href?: string;
  onClick?: () => void;
  badge?: string;
  className?: string;
}

const UserDropdown: React.FC<UserDropdownProps> = ({ isOpen, onClose }) => {
  const { user, signOut } = useAuth();
  const dropdownRef = useRef<HTMLDivElement>(null);

  // Close dropdown on outside click
  useEffect(() => {
    const handleClickOutside = (event: MouseEvent) => {
      if (dropdownRef.current && !dropdownRef.current.contains(event.target as Node)) {
        onClose();
      }
    };

    if (isOpen) {
      document.addEventListener('mousedown', handleClickOutside);
      return () => document.removeEventListener('mousedown', handleClickOutside);
    }
  }, [isOpen, onClose]);

  const menuItems: MenuItem[] = [
    {
      icon: User,
      label: 'Profile & Bio',
      href: '/profile/edit'
    },
    {
      icon: Heart,
      label: 'Spiritual Profile',
      href: '/profile/spiritual',
      badge: user?.spiritual_profile ? '✓' : undefined
    },
    {
      icon: Shield,
      label: 'Security & 2FA',
      href: '/profile/security',
      badge: user?.two_factor_enabled ? '2FA' : undefined
    },
    {
      icon: Smartphone,
      label: 'Active Sessions',
      href: '/profile/sessions'
    },
    {
      icon: Church,
      label: 'Ministry Roles',
      href: '/profile/ministry'
    }
  ];

  const handleSignOut = () => {
    signOut();
    onClose();
  };

  if (!isOpen || !user) return null;

  return (
    <div
      ref={dropdownRef}
      className="absolute right-0 top-12 w-80 bg-white dark:bg-gray-900 rounded-lg shadow-xl border border-gray-200 dark:border-gray-700 z-50 overflow-hidden"
    >
      {/* Profile Header */}
      <div className="p-4 bg-gradient-to-r from-blue-500 to-purple-600 text-white">
        <div className="flex items-center gap-3">
          <div className="relative">
            <div className="w-12 h-12 rounded-full bg-white/20 flex items-center justify-center overflow-hidden">
              {user.profile_photo ? (
                <img
                  src={user.profile_photo}
                  alt={user.name}
                  className="w-full h-full object-cover"
                />
              ) : (
                <span className="text-lg font-semibold">
                  {user.name.charAt(0).toUpperCase()}
                </span>
              )}
            </div>
            <button className="absolute -bottom-1 -right-1 w-5 h-5 bg-white text-gray-600 rounded-full flex items-center justify-center hover:bg-gray-50 transition-colors">
              <Camera className="w-3 h-3" />
            </button>
          </div>
          
          <div className="flex-1 min-w-0">
            <h3 className="font-semibold text-white truncate">
              {user.name}
            </h3>
            <p className="text-blue-100 text-sm truncate">
              {user.email}
            </p>
            <div className="flex items-center gap-2 mt-1">
              <span className="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-white/20 text-white">
                {user.role || 'Member'}
              </span>
              {user.two_factor_enabled && (
                <span className="inline-flex items-center px-1.5 py-0.5 rounded text-xs font-medium bg-green-500/20 text-green-100">
                  2FA
                </span>
              )}
            </div>
          </div>
        </div>
        
        {/* Profile Completeness */}
        {typeof user.profile_completeness === 'number' && user.profile_completeness < 100 && (
          <div className="mt-3">
            <div className="flex justify-between text-sm text-blue-100">
              <span>Profile Completeness</span>
              <span>{user.profile_completeness}%</span>
            </div>
            <div className="mt-1 h-2 bg-white/20 rounded-full overflow-hidden">
              <div 
                className="h-full bg-white transition-all duration-300"
                style={{ width: `${user.profile_completeness}%` }}
              />
            </div>
          </div>
        )}
      </div>

      {/* Menu Items */}
      <div className="py-2">
        {menuItems.map((item, index) => (
          <MenuItem key={index} item={item} onClose={onClose} />
        ))}
        
        <div className="border-t border-gray-200 dark:border-gray-700 my-2" />
        
        <MenuItem 
          item={{
            icon: Settings,
            label: 'Settings',
            href: '/settings'
          }}
          onClose={onClose}
        />
        
        <MenuItem 
          item={{
            icon: HelpCircle,
            label: 'Help & Support',
            href: '/help'
          }}
          onClose={onClose}
        />
        
        <div className="border-t border-gray-200 dark:border-gray-700 my-2" />
        
        <MenuItem 
          item={{
            icon: LogOut,
            label: 'Sign Out',
            onClick: handleSignOut,
            className: 'text-red-600 dark:text-red-400 hover:text-red-700 hover:bg-red-50 dark:hover:bg-red-900/20'
          }}
          onClose={onClose}
        />
      </div>
    </div>
  );
};

// Optimized MenuItem component to prevent re-renders
const MenuItem = React.memo<{
  item: MenuItem;
  onClose: () => void;
}>(({ item, onClose }) => {
  const handleClick = () => {
    if (item.onClick) {
      item.onClick();
    }
    onClose();
  };

  if (item.href && !item.onClick) {
    return (
      <Link
        to={item.href}
        onClick={onClose}
        className={`w-full flex items-center gap-3 px-4 py-2.5 text-left text-sm text-gray-700 dark:text-gray-200 hover:bg-gray-50 dark:hover:bg-gray-800 transition-colors ${item.className || ''}`}
      >
        <item.icon className="w-4 h-4 flex-shrink-0" />
        <span className="flex-1">{item.label}</span>
        {item.badge && (
          <span className="inline-flex items-center px-1.5 py-0.5 rounded text-xs font-medium bg-blue-100 text-blue-800 dark:bg-blue-900/50 dark:text-blue-200">
            {item.badge}
          </span>
        )}
      </Link>
    );
  }

  return (
    <button
      onClick={handleClick}
      className={`w-full flex items-center gap-3 px-4 py-2.5 text-left text-sm text-gray-700 dark:text-gray-200 hover:bg-gray-50 dark:hover:bg-gray-800 transition-colors ${item.className || ''}`}
    >
      <item.icon className="w-4 h-4 flex-shrink-0" />
      <span className="flex-1">{item.label}</span>
      {item.badge && (
        <span className="inline-flex items-center px-1.5 py-0.5 rounded text-xs font-medium bg-blue-100 text-blue-800 dark:bg-blue-900/50 dark:text-blue-200">
          {item.badge}
        </span>
      )}
    </button>
  );
});

MenuItem.displayName = 'MenuItem';

export default UserDropdown;