import {useState} from 'react';
import {useNavigate} from 'react-router';
import {useBooks, useBookCategories, Book} from '../queries';
import {BookCard} from '../components/BookCard';
import {CategorySidebar} from '../components/CategorySidebar';

export function LibraryCatalogPage() {
  const navigate = useNavigate();
  const [search, setSearch] = useState('');
  const [categoryId, setCategoryId] = useState<number | null>(null);
  const [viewMode, setViewMode] = useState<'grid' | 'list'>('grid');

  const params: Record<string, string | number | boolean> = {};
  if (search) params.search = search;
  if (categoryId) params.category_id = categoryId;

  const {data, fetchNextPage, hasNextPage, isFetchingNextPage, isLoading} = useBooks(params);
  const {data: categories} = useBookCategories();

  const books = data?.pages.flatMap((page: any) => page.data) ?? [];

  function handleBookClick(book: Book) {
    navigate(`/library/${book.id}`);
  }

  return (
    <div className="max-w-7xl mx-auto px-4 py-6">
      <div className="flex items-center justify-between mb-6">
        <h1 className="text-2xl font-bold text-gray-900 dark:text-white">Library</h1>
        <div className="flex items-center gap-3">
          {/* View toggle */}
          <div className="flex border border-gray-300 dark:border-gray-600 rounded-lg overflow-hidden">
            <button
              onClick={() => setViewMode('grid')}
              className={`px-3 py-2 text-sm ${viewMode === 'grid' ? 'bg-primary-50 text-primary-700 dark:bg-primary-900/20' : 'text-gray-500 hover:bg-gray-50 dark:hover:bg-gray-700/50'}`}
            >
              Grid
            </button>
            <button
              onClick={() => setViewMode('list')}
              className={`px-3 py-2 text-sm ${viewMode === 'list' ? 'bg-primary-50 text-primary-700 dark:bg-primary-900/20' : 'text-gray-500 hover:bg-gray-50 dark:hover:bg-gray-700/50'}`}
            >
              List
            </button>
          </div>
          <div className="relative">
            <input
              type="text"
              placeholder="Search books..."
              value={search}
              onChange={e => setSearch(e.target.value)}
              className="pl-9 pr-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-800 text-sm text-gray-900 dark:text-white placeholder-gray-400 focus:ring-2 focus:ring-primary-500 focus:border-transparent"
            />
            <svg className="absolute left-3 top-2.5 w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
            </svg>
          </div>
        </div>
      </div>

      <div className="flex gap-6">
        {/* Category sidebar */}
        {categories && categories.length > 0 && (
          <aside className="w-56 shrink-0 hidden md:block">
            <h2 className="text-sm font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider mb-3">
              Categories
            </h2>
            <CategorySidebar
              categories={categories}
              selectedId={categoryId}
              onSelect={setCategoryId}
            />
          </aside>
        )}

        {/* Book grid/list */}
        <div className="flex-1">
          {isLoading ? (
            <div className="text-center py-12 text-gray-500">Loading books...</div>
          ) : books.length === 0 ? (
            <div className="text-center py-12 text-gray-500">No books found.</div>
          ) : (
            <>
              {viewMode === 'grid' ? (
                <div className="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-4 xl:grid-cols-5 gap-4">
                  {books.map((book: Book) => (
                    <BookCard key={book.id} book={book} onClick={handleBookClick} />
                  ))}
                </div>
              ) : (
                <div className="space-y-3">
                  {books.map((book: Book) => (
                    <button
                      key={book.id}
                      onClick={() => handleBookClick(book)}
                      className="w-full flex items-center gap-4 p-3 bg-white dark:bg-gray-800 rounded-lg border border-gray-200 dark:border-gray-700 hover:shadow-sm transition-shadow text-left"
                    >
                      <div className="w-12 h-16 bg-gray-100 dark:bg-gray-700 rounded overflow-hidden shrink-0">
                        {book.cover && <img src={book.cover} alt="" className="w-full h-full object-cover" />}
                      </div>
                      <div className="flex-1 min-w-0">
                        <h3 className="font-semibold text-sm text-gray-900 dark:text-white truncate">{book.title}</h3>
                        <p className="text-xs text-gray-500 dark:text-gray-400">{book.author}</p>
                      </div>
                      {book.category && (
                        <span className="text-xs bg-gray-100 dark:bg-gray-700 px-2 py-0.5 rounded text-gray-600 dark:text-gray-300">
                          {book.category.name}
                        </span>
                      )}
                    </button>
                  ))}
                </div>
              )}

              {hasNextPage && (
                <div className="flex justify-center mt-8">
                  <button
                    onClick={() => fetchNextPage()}
                    disabled={isFetchingNextPage}
                    className="px-6 py-2 bg-primary-600 text-white rounded-lg hover:bg-primary-700 disabled:opacity-50 text-sm font-medium"
                  >
                    {isFetchingNextPage ? 'Loading...' : 'Load More'}
                  </button>
                </div>
              )}
            </>
          )}
        </div>
      </div>
    </div>
  );
}
