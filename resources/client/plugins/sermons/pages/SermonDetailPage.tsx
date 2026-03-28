import React from 'react';
import {Link, useParams} from 'react-router';
import {useSermon} from '../queries';
import {SermonPlayer} from '../components/SermonPlayer';
import {ReactionBar} from '@app/common/components/ReactionBar';
import {CommentThread} from '@app/common/components/CommentThread';

export function SermonDetailPage() {
  const {sermonId} = useParams<{sermonId: string}>();
  const {data: sermon, isLoading} = useSermon(sermonId!);

  if (isLoading) {
    return <div className="max-w-3xl mx-auto px-4 py-12 text-center text-gray-400">Loading...</div>;
  }

  if (!sermon) {
    return <div className="max-w-3xl mx-auto px-4 py-12 text-center text-gray-400">Sermon not found.</div>;
  }

  return (
    <div className="max-w-3xl mx-auto px-4 py-6">
      <SermonPlayer sermon={sermon} />

      <h1 className="text-3xl font-bold text-gray-900 mb-3">{sermon.title}</h1>

      <div className="flex flex-wrap items-center gap-3 text-sm text-gray-500 mb-4">
        <span className="font-medium text-gray-700">{sermon.speaker}</span>
        {sermon.sermon_date && (
          <span>{new Date(sermon.sermon_date).toLocaleDateString()}</span>
        )}
        {sermon.duration_minutes && <span>{sermon.duration_minutes}m</span>}
        {sermon.scripture_reference && (
          <span className="italic">{sermon.scripture_reference}</span>
        )}
      </div>

      <div className="flex flex-wrap gap-2 mb-6">
        {sermon.sermon_series && (
          <Link
            to={`/sermon-series/${sermon.sermon_series.id}`}
            className="text-xs bg-purple-100 text-purple-700 px-3 py-1 rounded-full hover:bg-purple-200"
          >
            Series: {sermon.sermon_series.name}
          </Link>
        )}
        {sermon.category && (
          <span className="text-xs bg-gray-100 text-gray-600 px-3 py-1 rounded-full">
            {sermon.category}
          </span>
        )}
      </div>

      {sermon.description && (
        <p className="text-gray-600 leading-relaxed mb-6 whitespace-pre-wrap">
          {sermon.description}
        </p>
      )}

      {sermon.pdf_notes && (
        <div className="mb-6">
          <a
            href={sermon.pdf_notes}
            target="_blank"
            rel="noopener noreferrer"
            className="inline-flex items-center gap-2 text-sm text-blue-600 hover:underline"
          >
            <span>📄</span>
            <span>Download Notes</span>
          </a>
        </div>
      )}

      <ReactionBar reactableId={sermon.id} reactableType="sermon" />

      <div className="mt-8">
        <CommentThread commentableId={sermon.id} commentableType="sermon" />
      </div>
    </div>
  );
}
