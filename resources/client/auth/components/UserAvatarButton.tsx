import React from 'react';
import { User } from 'lucide-react';
import { useAuth } from '@app/common/auth/use-auth';
import UserDropdown from './UserDropdown';

interface UserAvatarButtonProps {
  className?: string;
}

const UserAvatarButton: React.FC<UserAvatarButtonProps> = ({ className }) => {
  const { user } = useAuth();
  const [isDropdownOpen, setIsDropdownOpen] = React.useState(false);

  if (!user) {
    return (
      <div className={`w-8 h-8 rounded-full bg-gray-200 dark:bg-gray-700 ${className || ''}`} />
    );
  }

  return (
    <div className="relative">
      <button
        onClick={() => setIsDropdownOpen(!isDropdownOpen)}
        className={`relative w-8 h-8 rounded-full bg-gray-200 dark:bg-gray-700 overflow-hidden transition-all hover:ring-2 hover:ring-blue-500 hover:ring-offset-2 dark:hover:ring-offset-gray-900 ${isDropdownOpen ? 'ring-2 ring-blue-500 ring-offset-2 dark:ring-offset-gray-900' : ''} ${className || ''}`}
        aria-label="User menu"
      >
        {user.profile_photo ? (
          <img
            src={user.profile_photo}
            alt={user.name}
            className="w-full h-full object-cover"
          />
        ) : (
          <div className="w-full h-full bg-gradient-to-br from-blue-500 to-purple-600 flex items-center justify-center">
            <span className="text-white text-sm font-semibold">
              {user.name.charAt(0).toUpperCase()}
            </span>
          </div>
        )}
        
        {/* Online indicator */}
        <div className="absolute -bottom-0.5 -right-0.5 w-3 h-3 bg-green-400 border-2 border-white dark:border-gray-900 rounded-full" />
      </button>

      <UserDropdown 
        isOpen={isDropdownOpen} 
        onClose={() => setIsDropdownOpen(false)} 
      />
    </div>
  );
};

export default UserAvatarButton;