import React, { useEffect, useState } from 'react';
import { useNavigate } from 'react-router-dom';
import { get } from '../../components/shared/api';
import { useAuth } from '../hooks/useAuth';

function Spinner() {
    return (
        <div className="flex justify-center items-center py-6">
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

function SectionHeader({ title, linkLabel, linkPath }) {
    const navigate = useNavigate();
    return (
        <div className="flex items-center justify-between mb-3">
            <h2 className="text-base font-bold text-gray-800">{title}</h2>
            {linkPath && (
                <button
                    onClick={() => navigate(linkPath)}
                    className="text-indigo-600 text-sm font-medium focus:outline-none hover:underline"
                >
                    {linkLabel || 'See all'}
                </button>
            )}
        </div>
    );
}

export default function Home() {
    const { user } = useAuth();
    const [settings, setSettings] = useState(null);
    const [verse, setVerse] = useState(null);
    const [events, setEvents] = useState([]);
    const [posts, setPosts] = useState([]);
    const [announcements, setAnnouncements] = useState([]);
    const [loading, setLoading] = useState(true);
    const [error, setError] = useState(null);

    useEffect(() => {
        setLoading(true);
        setError(null);

        Promise.allSettled([
            get('/api/settings'),
            get('/api/verses/today'),
            get('/api/events/upcoming'),
            get('/api/posts/published'),
            get('/api/announcements/active'),
        ]).then(([settingsRes, verseRes, eventsRes, postsRes, announcementsRes]) => {
            if (settingsRes.status === 'fulfilled') setSettings(settingsRes.value);
            if (verseRes.status === 'fulfilled') setVerse(verseRes.value);
            if (eventsRes.status === 'fulfilled') {
                const data = eventsRes.value;
                setEvents(Array.isArray(data) ? data.slice(0, 3) : (data?.data || []).slice(0, 3));
            }
            if (postsRes.status === 'fulfilled') {
                const data = postsRes.value;
                setPosts(Array.isArray(data) ? data.slice(0, 3) : (data?.data || []).slice(0, 3));
            }
            if (announcementsRes.status === 'fulfilled') {
                const data = announcementsRes.value;
                setAnnouncements(Array.isArray(data) ? data.slice(0, 2) : (data?.data || []).slice(0, 2));
            }
            setLoading(false);
        }).catch(() => {
            setError('Failed to load home content.');
            setLoading(false);
        });
    }, []);

    const churchName = settings?.site_name || window.CHURCH_NAME || 'Our Church';

    if (loading) return <Spinner />;

    return (
        <div className="px-4 py-4 space-y-6">
            {error && <ErrorBanner message={error} />}

            {/* Welcome Banner */}
            <div
                className="rounded-xl p-5 text-white shadow-md"
                style={{ backgroundColor: '#0C0E12' }}
            >
                <p className="text-sm opacity-75">Welcome back{user?.name ? `, ${user.name}` : ''}!</p>
                <h1 className="text-2xl font-bold mt-1">{churchName}</h1>
                <p className="text-sm opacity-75 mt-1">Stay connected with your community</p>
            </div>

            {/* Today's Verse */}
            {verse && (
                <div className="bg-indigo-50 border border-indigo-100 rounded-xl p-4 shadow-sm">
                    <p className="text-xs font-semibold text-indigo-500 uppercase tracking-wide mb-2">
                        Verse of the Day
                    </p>
                    <p className="text-gray-800 text-sm italic leading-relaxed">
                        "{verse.text || verse.verse || verse.content}"
                    </p>
                    {(verse.reference || verse.book) && (
                        <p className="text-indigo-600 text-xs font-semibold mt-2">
                            — {verse.reference || `${verse.book} ${verse.chapter}:${verse.verse_number}`}
                        </p>
                    )}
                </div>
            )}

            {/* Announcements */}
            {announcements.length > 0 && (
                <section>
                    <SectionHeader title="Announcements" />
                    <div className="space-y-3">
                        {announcements.map((a, i) => (
                            <div
                                key={a.id || i}
                                className="bg-yellow-50 border border-yellow-200 rounded-xl p-4 shadow-sm"
                            >
                                <p className="font-semibold text-yellow-800 text-sm">{a.title}</p>
                                {a.body && (
                                    <p className="text-yellow-700 text-xs mt-1 line-clamp-2">{a.body}</p>
                                )}
                            </div>
                        ))}
                    </div>
                </section>
            )}

            {/* Upcoming Events */}
            <section>
                <SectionHeader title="Upcoming Events" linkLabel="See all" linkPath="/events" />
                {events.length === 0 ? (
                    <p className="text-gray-500 text-sm">No upcoming events.</p>
                ) : (
                    <div className="space-y-3">
                        {events.map((event, i) => (
                            <div
                                key={event.id || i}
                                className="bg-white rounded-xl p-4 shadow-sm border border-gray-100 flex items-start gap-3"
                            >
                                <div className="flex-shrink-0 w-12 h-12 bg-indigo-100 rounded-lg flex flex-col items-center justify-center text-indigo-700">
                                    <span className="text-xs font-bold uppercase leading-none">
                                        {event.start_date
                                            ? new Date(event.start_date).toLocaleString('en', { month: 'short' })
                                            : '—'}
                                    </span>
                                    <span className="text-lg font-bold leading-none">
                                        {event.start_date ? new Date(event.start_date).getDate() : ''}
                                    </span>
                                </div>
                                <div className="flex-1 min-w-0">
                                    <p className="font-semibold text-gray-800 text-sm truncate">
                                        {event.name || event.title}
                                    </p>
                                    {event.location && (
                                        <p className="text-gray-500 text-xs mt-0.5 truncate">{event.location}</p>
                                    )}
                                </div>
                            </div>
                        ))}
                    </div>
                )}
            </section>

            {/* Recent Posts */}
            <section>
                <SectionHeader title="Recent Posts" linkLabel="See all" linkPath="/sermons" />
                {posts.length === 0 ? (
                    <p className="text-gray-500 text-sm">No recent posts.</p>
                ) : (
                    <div className="space-y-3">
                        {posts.map((post, i) => (
                            <div
                                key={post.id || i}
                                className="bg-white rounded-xl p-4 shadow-sm border border-gray-100"
                            >
                                <p className="font-semibold text-gray-800 text-sm">{post.title}</p>
                                {post.excerpt && (
                                    <p className="text-gray-500 text-xs mt-1 line-clamp-2">{post.excerpt}</p>
                                )}
                                {post.published_at && (
                                    <p className="text-gray-400 text-xs mt-2">
                                        {new Date(post.published_at).toLocaleDateString()}
                                    </p>
                                )}
                            </div>
                        ))}
                    </div>
                )}
            </section>
        </div>
    );
}
