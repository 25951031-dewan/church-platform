import {Article} from '../queries';

interface ArticleCardProps {
  article: Article;
  onClick: (article: Article) => void;
}

export function ArticleCard({article, onClick}: ArticleCardProps) {
  const formattedDate = article.published_at
    ? new Date(article.published_at).toLocaleDateString('en-US', {
        month: 'short',
        day: 'numeric',
        year: 'numeric',
      })
    : null;

  return (
    <button
      onClick={() => onClick(article)}
      className="group text-left bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700 overflow-hidden hover:shadow-md transition-shadow"
    >
      {/* Cover image */}
      <div className="aspect-video bg-gray-100 dark:bg-gray-700 overflow-hidden">
        {article.cover_image ? (
          <img
            src={article.cover_image}
            alt={article.title}
            className="w-full h-full object-cover group-hover:scale-105 transition-transform"
          />
        ) : (
          <div className="w-full h-full flex items-center justify-center text-gray-400">
            <svg className="w-10 h-10" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={1.5} d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
            </svg>
          </div>
        )}
      </div>

      {/* Content */}
      <div className="p-4">
        {/* Meta row */}
        <div className="flex items-center gap-2 mb-2">
          {article.category && (
            <span className="text-xs font-medium text-primary-600 dark:text-primary-400 bg-primary-50 dark:bg-primary-900/20 px-2 py-0.5 rounded">
              {article.category.name}
            </span>
          )}
          {article.is_featured && (
            <span className="text-xs font-medium text-amber-600 dark:text-amber-400">
              Featured
            </span>
          )}
        </div>

        <h3 className="font-semibold text-gray-900 dark:text-white line-clamp-2 group-hover:text-primary-600 dark:group-hover:text-primary-400 transition-colors">
          {article.title}
        </h3>

        {article.excerpt && (
          <p className="text-sm text-gray-500 dark:text-gray-400 mt-1 line-clamp-2">
            {article.excerpt}
          </p>
        )}

        {/* Footer row */}
        <div className="flex items-center justify-between mt-3 text-xs text-gray-400">
          <div className="flex items-center gap-2">
            {article.author?.avatar ? (
              <img
                src={article.author.avatar}
                alt={article.author.name}
                className="w-5 h-5 rounded-full object-cover"
              />
            ) : (
              <div className="w-5 h-5 rounded-full bg-gray-200 dark:bg-gray-600" />
            )}
            <span>{article.author?.name}</span>
          </div>
          {formattedDate && <span>{formattedDate}</span>}
        </div>
      </div>
    </button>
  );
}
