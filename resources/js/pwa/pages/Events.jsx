import React, { useEffect, useState } from 'react';
import { get, post } from '../../components/shared/api';
import { useAuth } from '../hooks/useAuth';
import { useNavigate } from 'react-router-dom';

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

function formatDate(dateStr) {
    if (!dateStr) return '';
    const d = new Date(dateStr);
    return d.toLocaleDateString(undefined, { weekday: 'short', month: 'short', day: 'numeric', year: 'numeric' });
}

function formatTime(dateStr) {
    if (!dateStr) return '';
    const d = new Date(dateStr);
    return d.toLocaleTimeString(undefined, { hour: '2-digit', minute: '2-digit' });
}

export default function Events() {
    const { user } = useAuth();
    const navigate = useNavigate();
    const [events, setEvents] = useState([]);
    const [loading, setLoading] = useState(true);
    const [error, setError] = useState(null);
    const [registering, setRegistering] = useState(null);
    const [registeredIds, setRegisteredIds] = useState([]);
    const [regError, setRegError] = useState(null);

    useEffect(() => {
        setLoading(true);
        setError(null);
        get('/api/events/upcoming')
            .then((data) => {
                const list = Array.isArray(data) ? data : (data?.data || []);
                setEvents(list);
            })
            .catch(() => setError('Failed to load events.'))
            .finally(() => setLoading(false));
    }, []);

    const handleRegister = async (eventId) => {
        if (!user) {
            navigate('/login');
            return;
        }
        setRegistering(eventId);
        setRegError(null);
        try {
            await post(`/api/events/${eventId}/register`, {});
            setRegisteredIds((prev) => [...prev, eventId]);
        } catch (err) {
            setRegError(err?.message || 'Registration failed. Please try again.');
        } finally {
            setRegistering(null);
        }
    };

    return (
        <div className="px-4 py-4">
            <h1 className="text-xl font-bold text-gray-800 mb-4">Upcoming Events</h1>

            {error && <ErrorBanner message={error} />}
            {regError && <ErrorBanner message={regError} />}

            {loading ? (
                <Spinner />
            ) : events.length === 0 ? (
                <p className="text-center text-gray-400 py-8">No upcoming events.</p>
            ) : (
                <div className="space-y-4">
                    {events.map((event, i) => (
                        <div
                            key={event.id || i}
                            className="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden"
                        >
                            {/* Date strip */}
                            <div className="bg-indigo-600 text-white px-4 py-2 flex items-center justify-between">
                                <span className="text-sm font-semibold">
                                    {formatDate(event.start_date || event.date)}
                                </span>
                                {(event.start_date || event.date) && (
                                    <span className="text-xs opacity-80">
                                        {formatTime(event.start_date || event.date)}
                                    </span>
                                )}
                            </div>

                            <div className="p-4">
                                <h3 className="font-bold text-gray-900 text-base leading-snug">
                                    {event.name || event.title}
                                </h3>

                                {event.location && (
                                    <p className="text-gray-500 text-sm mt-1">
                                        📍 {event.location}
                                    </p>
                                )}

                                {event.description && (
                                    <p className="text-gray-600 text-sm mt-2 line-clamp-2">
                                        {event.description}
                                    </p>
                                )}

                                <div className="mt-3">
                                    {registeredIds.includes(event.id) ? (
                                        <span className="inline-block px-4 py-1.5 bg-green-100 text-green-700 text-sm rounded-lg font-medium">
                                            ✓ Registered
                                        </span>
                                    ) : (
                                        <button
                                            onClick={() => handleRegister(event.id)}
                                            disabled={registering === event.id}
                                            className="px-4 py-1.5 bg-indigo-600 text-white text-sm rounded-lg font-medium hover:bg-indigo-700 disabled:opacity-60 focus:outline-none"
                                        >
                                            {registering === event.id ? 'Registering...' : 'Register'}
                                        </button>
                                    )}
                                </div>
                            </div>
                        </div>
                    ))}
                </div>
            )}
        </div>
    );
}
