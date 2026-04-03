import {ArticleCategory} from '../queries';

interface CategorySidebarProps {
  categories: ArticleCategory[];
  selectedId: number | null;
  onSelect: (id: number | null) => void;
}

export function CategorySidebar({categories, selectedId, onSelect}: CategorySidebarProps) {
  return (
    <div className="space-y-1">
      <button
        onClick={() => onSelect(null)}
        className={`w-full text-left px-3 py-2 text-sm rounded-md transition-colors ${
          selectedId === null
            ? 'bg-primary-50 text-primary-700 dark:bg-primary-900/20 dark:text-primary-400 font-medium'
            : 'text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700/50'
        }`}
      >
        All Articles
      </button>
      {categories.map(cat => (
        <button
          key={cat.id}
          onClick={() => onSelect(selectedId === cat.id ? null : cat.id)}
          className={`w-full text-left px-3 py-2 text-sm rounded-md transition-colors ${
            selectedId === cat.id
              ? 'bg-primary-50 text-primary-700 dark:bg-primary-900/20 dark:text-primary-400 font-medium'
              : 'text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700/50'
          }`}
        >
          {cat.name}
        </button>
      ))}
    </div>
  );
}
