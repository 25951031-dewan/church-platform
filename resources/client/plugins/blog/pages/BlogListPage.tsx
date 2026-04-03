import {useState} from 'react';
import {useNavigate} from 'react-router';
import {useArticles, useArticleCategories, useTags, Article} from '../queries';
import {ArticleCard} from '../components/ArticleCard';
import {CategorySidebar} from '../components/CategorySidebar';

export function BlogListPage() {
  const navigate = useNavigate();
  const [search, setSearch] = useState('');
  const [categoryId, setCategoryId] = useState<number | null>(null);
  const [selectedTag, setSelectedTag] = useState<string | null>(null);

  const params: Record<string, string | number | boolean> = {};
  if (search) params.search = search;
  if (categoryId) params.category_id = categoryId;
  if (selectedTag) params.tag = selectedTag;

  const {data, fetchNextPage, hasNextPage, isFetchingNextPage, isLoading} = useArticles(params);
  const {data: categories} = useArticleCategories();
  const {data: tags} = useTags();

  const articles = data?.pages.flatMap((page: any) => page.pagination?.data ?? page.data ?? []) ?? [];
  const featured = articles.filter((a: Article) => a.is_featured);
  const regular = articles.filter((a: Article) => !a.is_featured);

  function handleArticleClick(article: Article) {
    navigate(`/blog/${article.slug}`);
  }

  return (
    <div className="max-w-7xl mx-auto px-4 py-6">
      {/* Header */}
      <div className="flex items-center justify-between mb-6">
        <h1 className="text-2xl font-bold text-gray-900 dark:text-white">Blog</h1>
        <div className="relative">
          <input
            type="text"
            placeholder="Search articles..."
            value={search}
            onChange={e => setSearch(e.target.value)}
            className="pl-9 pr-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-800 text-sm text-gray-900 dark:text-white placeholder-gray-400 focus:ring-2 focus:ring-primary-500 focus:border-transparent"
          />
          <svg className="absolute left-3 top-2.5 w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
          </svg>
        </div>
      </div>

      {/* Tag filter chips */}
      {tags && tags.length > 0 && (
        <div className="flex flex-wrap gap-2 mb-6">
          <button
            onClick={() => setSelectedTag(null)}
            className={`px-3 py-1 text-xs rounded-full border transition-colors ${
              selectedTag === null
                ? 'bg-primary-600 text-white border-primary-600'
                : 'bg-white dark:bg-gray-800 text-gray-600 dark:text-gray-400 border-gray-300 dark:border-gray-600 hover:border-primary-400'
            }`}
          >
            All
          </button>
          {tags.map(tag => (
            <button
              key={tag.id}
              onClick={() => setSelectedTag(selectedTag === tag.slug ? null : tag.slug)}
              className={`px-3 py-1 text-xs rounded-full border transition-colors ${
                selectedTag === tag.slug
                  ? 'bg-primary-600 text-white border-primary-600'
                  : 'bg-white dark:bg-gray-800 text-gray-600 dark:text-gray-400 border-gray-300 dark:border-gray-600 hover:border-primary-400'
              }`}
            >
              #{tag.name}
            </button>
          ))}
        </div>
      )}

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

        {/* Main content */}
        <div className="flex-1">
          {isLoading ? (
            <div className="text-center py-12 text-gray-500">Loading articles...</div>
          ) : articles.length === 0 ? (
            <div className="text-center py-12 text-gray-500">No articles found.</div>
          ) : (
            <>
              {/* Featured banner */}
              {featured.length > 0 && !search && !categoryId && !selectedTag && (
                <div className="mb-8">
                  <h2 className="text-sm font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider mb-3">
                    Featured
                  </h2>
                  <div className="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    {featured.map((article: Article) => (
                      <ArticleCard key={article.id} article={article} onClick={handleArticleClick} />
                    ))}
                  </div>
                </div>
              )}

              {/* Regular articles grid */}
              <div className="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
                {(featured.length > 0 && !search && !categoryId && !selectedTag ? regular : articles).map(
                  (article: Article) => (
                    <ArticleCard key={article.id} article={article} onClick={handleArticleClick} />
                  )
                )}
              </div>

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
