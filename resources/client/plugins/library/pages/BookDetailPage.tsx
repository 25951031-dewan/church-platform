import {useParams, useNavigate} from 'react-router';
import {useBook, useTrackDownload} from '../queries';

export function BookDetailPage() {
  const {bookId} = useParams<{bookId: string}>();
  const navigate = useNavigate();
  const {data: book, isLoading} = useBook(bookId!);
  const trackDownload = useTrackDownload(bookId!);

  if (isLoading || !book) {
    return <div className="flex items-center justify-center h-64">Loading...</div>;
  }

  function handleDownload() {
    trackDownload.mutate(undefined, {
      onSuccess: (data) => {
        if (data.pdf_url) {
          window.open(data.pdf_url, '_blank');
        }
      },
    });
  }

  return (
    <div className="max-w-4xl mx-auto px-4 py-6">
      <button
        onClick={() => navigate('/library')}
        className="text-sm text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-200 mb-4 inline-flex items-center gap-1"
      >
        &larr; Back to Library
      </button>

      <div className="flex flex-col md:flex-row gap-8">
        {/* Cover */}
        <div className="w-full md:w-64 shrink-0">
          <div className="aspect-[3/4] bg-gray-100 dark:bg-gray-700 rounded-lg overflow-hidden">
            {book.cover ? (
              <img src={book.cover} alt={book.title} className="w-full h-full object-cover" />
            ) : (
              <div className="w-full h-full flex items-center justify-center text-gray-400">
                <svg className="w-16 h-16" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={1.5} d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253" />
                </svg>
              </div>
            )}
          </div>

          {/* Download button */}
          {book.has_pdf && book.can_download && (
            <button
              onClick={handleDownload}
              disabled={trackDownload.isPending}
              className="w-full mt-4 px-4 py-2.5 bg-primary-600 text-white rounded-lg hover:bg-primary-700 disabled:opacity-50 text-sm font-medium flex items-center justify-center gap-2"
            >
              <svg className="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
              </svg>
              {trackDownload.isPending ? 'Preparing...' : 'Download PDF'}
            </button>
          )}
        </div>

        {/* Details */}
        <div className="flex-1">
          <h1 className="text-3xl font-bold text-gray-900 dark:text-white">{book.title}</h1>
          <p className="text-lg text-gray-600 dark:text-gray-400 mt-1">by {book.author}</p>

          {book.category && (
            <span className="inline-block mt-3 bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-300 text-sm px-3 py-1 rounded-full">
              {book.category.name}
            </span>
          )}

          {/* Metadata grid */}
          <div className="mt-6 grid grid-cols-2 gap-4 text-sm">
            {book.publisher && (
              <div>
                <span className="text-gray-500 dark:text-gray-400">Publisher</span>
                <p className="text-gray-900 dark:text-white">{book.publisher}</p>
              </div>
            )}
            {book.published_year && (
              <div>
                <span className="text-gray-500 dark:text-gray-400">Year</span>
                <p className="text-gray-900 dark:text-white">{book.published_year}</p>
              </div>
            )}
            {book.pages_count && (
              <div>
                <span className="text-gray-500 dark:text-gray-400">Pages</span>
                <p className="text-gray-900 dark:text-white">{book.pages_count}</p>
              </div>
            )}
            {book.isbn && (
              <div>
                <span className="text-gray-500 dark:text-gray-400">ISBN</span>
                <p className="text-gray-900 dark:text-white">{book.isbn}</p>
              </div>
            )}
            <div>
              <span className="text-gray-500 dark:text-gray-400">Views</span>
              <p className="text-gray-900 dark:text-white">{book.view_count}</p>
            </div>
            <div>
              <span className="text-gray-500 dark:text-gray-400">Downloads</span>
              <p className="text-gray-900 dark:text-white">{book.download_count}</p>
            </div>
          </div>

          {/* Description */}
          {book.description && (
            <div className="mt-6">
              <h2 className="text-sm font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider mb-2">
                Description
              </h2>
              <div className="text-gray-700 dark:text-gray-300 leading-relaxed whitespace-pre-line">
                {book.description}
              </div>
            </div>
          )}
        </div>
      </div>
    </div>
  );
}
