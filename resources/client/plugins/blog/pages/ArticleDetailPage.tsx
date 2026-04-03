// Requires: npm install dompurify @types/dompurify
import DOMPurify from 'dompurify';
import {useParams, useNavigate} from 'react-router';
import {useArticle} from '../queries';

export function ArticleDetailPage() {
  const {slug} = useParams<{slug: string}>();
  const navigate = useNavigate();
  const {data: article, isLoading} = useArticle(slug!);

  if (isLoading || !article) {
    return <div className="flex items-center justify-center h-64">Loading...</div>;
  }

  const formattedDate = article.published_at
    ? new Date(article.published_at).toLocaleDateString('en-US', {
        month: 'long',
        day: 'numeric',
        year: 'numeric',
      })
    : null;

  const safeContent = DOMPurify.sanitize(article.content ?? '');

  return (
    <div className="max-w-3xl mx-auto px-4 py-6">
      <button
        onClick={() => navigate('/blog')}
        className="text-sm text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-200 mb-6 inline-flex items-center gap-1"
      >
        &larr; Back to Blog
      </button>

      {/* Cover image */}
      {article.cover_image && (
        <div className="aspect-video rounded-xl overflow-hidden mb-8">
          <img src={article.cover_image} alt={article.title} className="w-full h-full object-cover" />
        </div>
      )}

      {/* Category + tags */}
      <div className="flex flex-wrap items-center gap-2 mb-4">
        {article.category && (
          <span className="text-sm font-medium text-primary-600 dark:text-primary-400 bg-primary-50 dark:bg-primary-900/20 px-3 py-1 rounded-full">
            {article.category.name}
          </span>
        )}
        {article.tags.map(tag => (
          <span
            key={tag.id}
            className="text-xs text-gray-500 dark:text-gray-400 bg-gray-100 dark:bg-gray-700 px-2 py-0.5 rounded-full"
          >
            #{tag.name}
          </span>
        ))}
      </div>

      {/* Title */}
      <h1 className="text-3xl font-bold text-gray-900 dark:text-white leading-tight">
        {article.title}
      </h1>

      {/* Author + date + views */}
      <div className="flex items-center justify-between mt-4 pb-6 border-b border-gray-200 dark:border-gray-700">
        <div className="flex items-center gap-3">
          {article.author?.avatar ? (
            <img
              src={article.author.avatar}
              alt={article.author.name}
              className="w-9 h-9 rounded-full object-cover"
            />
          ) : (
            <div className="w-9 h-9 rounded-full bg-gray-200 dark:bg-gray-600 flex items-center justify-center text-sm font-medium text-gray-500">
              {article.author?.name?.[0]}
            </div>
          )}
          <div>
            <p className="text-sm font-medium text-gray-900 dark:text-white">
              {article.author?.name}
            </p>
            {formattedDate && (
              <p className="text-xs text-gray-500 dark:text-gray-400">{formattedDate}</p>
            )}
          </div>
        </div>
        <div className="flex items-center gap-1 text-xs text-gray-400">
          <svg className="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
            <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
          </svg>
          <span>{article.view_count} views</span>
        </div>
      </div>

      {/* Article content — sanitized Tiptap HTML */}
      <div
        className="prose dark:prose-invert max-w-none mt-8"
        dangerouslySetInnerHTML={{__html: safeContent}}
      />
    </div>
  );
}
