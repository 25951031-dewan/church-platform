interface Props {
  typingUsers: { userId: number; userName: string }[];
}

/**
 * Typing indicator showing who is typing.
 */
export function TypingIndicator({ typingUsers }: Props) {
  if (typingUsers.length === 0) return null;

  const names = typingUsers.map((u) => u.userName);
  let text = '';

  if (names.length === 1) {
    text = `${names[0]} is typing...`;
  } else if (names.length === 2) {
    text = `${names[0]} and ${names[1]} are typing...`;
  } else {
    text = `${names.slice(0, 2).join(', ')} and ${names.length - 2} more are typing...`;
  }

  return (
    <div className="px-4 py-2 text-sm text-gray-500 italic flex items-center gap-2">
      {/* Animated dots */}
      <span className="flex space-x-1">
        <span className="w-2 h-2 bg-gray-400 rounded-full animate-bounce" style={{ animationDelay: '0ms' }} />
        <span className="w-2 h-2 bg-gray-400 rounded-full animate-bounce" style={{ animationDelay: '150ms' }} />
        <span className="w-2 h-2 bg-gray-400 rounded-full animate-bounce" style={{ animationDelay: '300ms' }} />
      </span>
      <span>{text}</span>
    </div>
  );
}
