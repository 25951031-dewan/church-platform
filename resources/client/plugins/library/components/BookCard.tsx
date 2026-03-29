import {Book} from '../queries';

interface BookCardProps {
  book: Book;
  onClick: (book: Book) => void;
}

export function BookCard({book, onClick}: BookCardProps) {
  return (
    <button
      onClick={() => onClick(book)}
      className="group text-left bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700 overflow-hidden hover:shadow-md transition-shadow"
    >
      {/* Cover */}
      <div className="aspect-[3/4] bg-gray-100 dark:bg-gray-700 overflow-hidden">
        {book.cover ? (
          <img
            src={book.cover}
            alt={book.title}
            className="w-full h-full object-cover group-hover:scale-105 transition-transform"
          />
        ) : (
          <div className="w-full h-full flex items-center justify-center text-gray-400">
            <svg className="w-12 h-12" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={1.5} d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253" />
            </svg>
          </div>
        )}
      </div>

      {/* Info */}
      <div className="p-3">
        <h3 className="font-semibold text-sm text-gray-900 dark:text-white line-clamp-2">
          {book.title}
        </h3>
        <p className="text-xs text-gray-500 dark:text-gray-400 mt-1">{book.author}</p>
        <div className="flex items-center gap-3 mt-2 text-xs text-gray-400">
          {book.category && (
            <span className="bg-gray-100 dark:bg-gray-700 px-2 py-0.5 rounded">
              {book.category.name}
            </span>
          )}
          {book.is_featured && (
            <span className="text-amber-500 font-medium">Featured</span>
          )}
        </div>
      </div>
    </button>
  );
}
