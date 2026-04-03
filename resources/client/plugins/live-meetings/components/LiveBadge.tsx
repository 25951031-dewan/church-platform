export function LiveBadge() {
  return (
    <span className="inline-flex items-center gap-1 rounded-full bg-red-100 px-2 py-0.5 text-xs font-semibold text-red-600 dark:bg-red-900/30 dark:text-red-300">
      <span className="h-2 w-2 animate-pulse rounded-full bg-red-500" />
      Live
    </span>
  );
}
