interface Props {
  count: number;
}

/**
 * Badge showing unread message count.
 */
export function UnreadBadge({ count }: Props) {
  if (count === 0) return null;

  return (
    <span className="bg-red-500 text-white text-xs font-bold rounded-full px-2 py-0.5 min-w-[20px] text-center">
      {count > 99 ? '99+' : count}
    </span>
  );
}
