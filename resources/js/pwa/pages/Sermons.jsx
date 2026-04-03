import React, { useState, useEffect } from 'react';
import { get } from '../../components/shared/api';
import { useAuth } from '../hooks/useAuth';

function Spinner() {
    return (
        <div className="flex justify-center items-center py-8">
            <div className="animate-spin rounded-full h-8 w-8 border-b-2 border-indigo-600"></div>
        </div>
    );
}

function ErrorBanner({ message }) {
    return (
        <div className="mx-4 my-2 p-3 bg-red-100 border border-red-300 text-red-700 rounded-lg text-sm">
            {message}
        </div>
    );
}

export default function Sermons() {
    const { user } = useAuth();
    const [sermons, setSermons] = useState([]);
    const [loading, setLoading] = useState(true);
    const [error, setError] = useState(null);
    const [search, setSearch] = useState('');

    useEffect(() => {
        setLoading(true);
        setError(null);
        const endpoint = user ? '/api/sermons' : '/api/sermons/featured';
        get(endpoint)
            .then((data) => {
                const list = Array.isArray(data) ? data : (data?.data || []);
                setSermons(list);
            })
            .catch(() => setError('Failed to load sermons.'))
            .finally(() => setLoading(false));
    }, [user]);

    const filtered = sermons.filter((s) =>
        (s.title || '').toLowerCase().includes(search.toLowerCase()) ||
        (s.speaker || s.preacher || '').toLowerCase().includes(search.toLowerCase())
    );

    return (
        <div className="px-4 py-4">
            {/* Search / Filter Bar */}
            <div className="bg-indigo-700 text-white -mx-4 -mt-4 px-4 pt-4 pb-4 mb-4">
                <h1 className="text-xl font-bold mb-3">Sermons</h1>
                <input
                    type="search"
                    placeholder="Search sermons or speaker..."
                    value={search}
                    onChange={(e) => setSearch(e.target.value)}
                    className="w-full px-4 py-2 rounded-lg text-gray-900 text-sm focus:outline-none"
                />
            </div>

            {error && <ErrorBanner message={error} />}
            {loading ? (
                <Spinner />
            ) : filtered.length === 0 ? (
                <p className="text-center text-gray-400 py-8">No sermons found.</p>
            ) : (
                <div className="space-y-3">
                    {filtered.map((sermon, i) => (
                        <div key={sermon.id || i} className="bg-white rounded-xl shadow-sm border border-gray-100 p-4">
                            <div className="flex items-start gap-3">
                                <div className="w-12 h-12 bg-indigo-100 rounded-lg flex items-center justify-center text-xl flex-shrink-0">
                                    {sermon.audio_url ? '🎵' : sermon.video_url ? '🎬' : '📖'}
                                </div>
                                <div className="flex-1 min-w-0">
                                    <h3 className="font-semibold text-gray-900 text-sm leading-tight">
                                        {sermon.title}
                                    </h3>
                                    {(sermon.speaker || sermon.preacher) && (
                                        <p className="text-xs text-indigo-600 mt-0.5">
                                            {sermon.speaker || sermon.preacher}
                                        </p>
                                    )}
                                    <div className="flex items-center gap-3 mt-0.5 flex-wrap">
                                        {(sermon.date || sermon.sermon_date) && (
                                            <p className="text-xs text-gray-400">
                                                {new Date(sermon.date || sermon.sermon_date).toLocaleDateString()}
                                            </p>
                                        )}
                                        {sermon.duration && (
                                            <p className="text-xs text-gray-400">⏱ {sermon.duration}</p>
                                        )}
                                    </div>
                                </div>
                            </div>
                            <div className="flex gap-2 mt-3">
                                {sermon.audio_url && (
                                    <a
                                        href={sermon.audio_url}
                                        target="_blank"
                                        rel="noreferrer"
                                        className="flex-1 text-center py-1.5 bg-indigo-600 text-white text-xs rounded-lg font-medium"
                                    >
                                        ▶ Listen
                                    </a>
                                )}
                                {sermon.video_url && (
                                    <a
                                        href={sermon.video_url}
                                        target="_blank"
                                        rel="noreferrer"
                                        className="flex-1 text-center py-1.5 bg-red-600 text-white text-xs rounded-lg font-medium"
                                    >
                                        ▶ Watch
                                    </a>
                                )}
                                {sermon.file_url && (
                                    <a
                                        href={sermon.file_url}
                                        download
                                        className="flex-1 text-center py-1.5 border border-gray-300 text-gray-700 text-xs rounded-lg font-medium"
                                    >
                                        ⬇ Download
                                    </a>
                                )}
                            </div>
                        </div>
                    ))}
                </div>
            )}
        </div>
    );
}
