import {BookCategory} from '../queries';

interface CategorySidebarProps {
  categories: BookCategory[];
  selectedId: number | null;
  onSelect: (id: number | null) => void;
}

export function CategorySidebar({categories, selectedId, onSelect}: CategorySidebarProps) {
  const roots = categories.filter(c => !c.parent_id);

  function getChildren(parentId: number): BookCategory[] {
    return categories.filter(c => c.parent_id === parentId);
  }

  function renderCategory(cat: BookCategory, depth: number = 0) {
    const children = getChildren(cat.id);
    const isSelected = selectedId === cat.id;

    return (
      <div key={cat.id}>
        <button
          onClick={() => onSelect(isSelected ? null : cat.id)}
          className={`w-full text-left px-3 py-2 text-sm rounded-md transition-colors ${
            isSelected
              ? 'bg-primary-50 text-primary-700 dark:bg-primary-900/20 dark:text-primary-400 font-medium'
              : 'text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700/50'
          }`}
          style={{paddingLeft: `${12 + depth * 16}px`}}
        >
          <span>{cat.name}</span>
          <span className="ml-auto text-xs text-gray-400">{cat.books_count}</span>
        </button>
        {children.map(child => renderCategory(child, depth + 1))}
      </div>
    );
  }

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
        All Books
      </button>
      {roots.map(cat => renderCategory(cat))}
    </div>
  );
}
