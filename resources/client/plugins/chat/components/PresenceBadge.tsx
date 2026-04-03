interface Props {
  isOnline: boolean;
  size?: 'sm' | 'md' | 'lg';
}

const sizeClasses = {
  sm: 'w-2 h-2',
  md: 'w-3 h-3',
  lg: 'w-4 h-4',
};

/**
 * Green/gray dot indicating online/offline status.
 */
export function PresenceBadge({ isOnline, size = 'md' }: Props) {
  return (
    <span
      className={`${sizeClasses[size]} rounded-full ${
        isOnline ? 'bg-green-500' : 'bg-gray-300'
      } ring-2 ring-white`}
      title={isOnline ? 'Online' : 'Offline'}
    />
  );
}
