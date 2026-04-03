import React, { useEffect, useState } from 'react';
import { get } from '../../components/shared/api';

function Spinner() {
    return (
        <div className="flex justify-center items-center py-8">
            <div className="animate-spin rounded-full h-8 w-8 border-b-2 border-indigo-600"></div>
        </div>
    );
}

function ErrorBanner({ message }) {
    return (
        <div className="my-2 p-3 bg-red-100 border border-red-300 text-red-700 rounded-lg text-sm">
            {message}
        </div>
    );
}

function ChurchModal({ church, onClose }) {
    if (!church) return null;
    return (
        <div
            className="fixed inset-0 z-50 flex items-end justify-center bg-black bg-opacity-50"
            onClick={onClose}
        >
            <div
                className="bg-white rounded-t-2xl w-full max-w-lg p-6 pb-8 max-h-[80vh] overflow-y-auto"
                onClick={(e) => e.stopPropagation()}
            >
                <div className="flex items-center justify-between mb-4">
                    <h2 className="text-lg font-bold text-gray-900">{church.name}</h2>
                    <button
                        onClick={onClose}
                        className="text-gray-400 hover:text-gray-600 text-2xl leading-none focus:outline-none"
                        aria-label="Close"
                    >
                        &times;
                    </button>
                </div>

                {church.logo_url && (
                    <img
                        src={church.logo_url}
                        alt={church.name}
                        className="w-20 h-20 object-contain rounded-xl mx-auto mb-4 border border-gray-100"
                    />
                )}

                <div className="space-y-3 text-sm text-gray-700">
                    {church.denomination && (
                        <div>
                            <span className="font-medium text-gray-500">Denomination:</span>{' '}
                            {church.denomination}
                        </div>
                    )}
                    {(church.city || church.state || church.country) && (
                        <div>
                            <span className="font-medium text-gray-500">Location:</span>{' '}
                            {[church.city, church.state, church.country].filter(Boolean).join(', ')}
                        </div>
                    )}
                    {church.address && (
                        <div>
                            <span className="font-medium text-gray-500">Address:</span>{' '}
                            {church.address}
                        </div>
                    )}
                    {church.phone && (
                        <div>
                            <span className="font-medium text-gray-500">Phone:</span>{' '}
                            <a href={`tel:${church.phone}`} className="text-indigo-600">{church.phone}</a>
                        </div>
                    )}
                    {church.email && (
                        <div>
                            <span className="font-medium text-gray-500">Email:</span>{' '}
                            <a href={`mailto:${church.email}`} className="text-indigo-600">{church.email}</a>
                        </div>
                    )}
                    {church.website && (
                        <div>
                            <span className="font-medium text-gray-500">Website:</span>{' '}
                            <a href={church.website} target="_blank" rel="noreferrer" className="text-indigo-600">
                                {church.website}
                            </a>
                        </div>
                    )}
                    {church.description && (
                        <div>
                            <span className="font-medium text-gray-500 block mb-1">About:</span>
                            <p className="text-gray-600 leading-relaxed">{church.description}</p>
                        </div>
                    )}
                </div>
            </div>
        </div>
    );
}

export default function ChurchDirectory() {
    const [churches, setChurches] = useState([]);
    const [filtered, setFiltered] = useState([]);
    const [loading, setLoading] = useState(true);
    const [error, setError] = useState(null);
    const [search, setSearch] = useState('');
    const [denomination, setDenomination] = useState('');
    const [selectedChurch, setSelectedChurch] = useState(null);

    useEffect(() => {
        setLoading(true);
        setError(null);
        get('/api/churches')
            .then((data) => {
                const list = Array.isArray(data) ? data : (data?.data || []);
                setChurches(list);
                setFiltered(list);
            })
            .catch(() => setError('Failed to load church directory.'))
            .finally(() => setLoading(false));
    }, []);

    useEffect(() => {
        let list = churches;
        if (search.trim()) {
            const q = search.toLowerCase();
            list = list.filter(
                (c) =>
                    (c.name || '').toLowerCase().includes(q) ||
                    (c.city || '').toLowerCase().includes(q)
            );
        }
        if (denomination.trim()) {
            const d = denomination.toLowerCase();
            list = list.filter((c) => (c.denomination || '').toLowerCase().includes(d));
        }
        setFiltered(list);
    }, [search, denomination, churches]);

    const denominations = [...new Set(churches.map((c) => c.denomination).filter(Boolean))].sort();

    return (
        <div className="px-4 py-4">
            <h1 className="text-xl font-bold text-gray-800 mb-4">Church Directory</h1>

            {/* Filters */}
            <div className="space-y-2 mb-4">
                <input
                    type="text"
                    placeholder="Search by name or city..."
                    value={search}
                    onChange={(e) => setSearch(e.target.value)}
                    className="w-full px-4 py-2 rounded-lg border border-gray-300 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-400"
                />
                {denominations.length > 0 && (
                    <select
                        value={denomination}
                        onChange={(e) => setDenomination(e.target.value)}
                        className="w-full px-4 py-2 rounded-lg border border-gray-300 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-400 bg-white"
                    >
                        <option value="">All Denominations</option>
                        {denominations.map((d) => (
                            <option key={d} value={d}>{d}</option>
                        ))}
                    </select>
                )}
            </div>

            {error && <ErrorBanner message={error} />}

            {loading ? (
                <Spinner />
            ) : filtered.length === 0 ? (
                <p className="text-center text-gray-400 py-8">No churches found.</p>
            ) : (
                <div className="space-y-3">
                    {filtered.map((church, i) => (
                        <button
                            key={church.id || i}
                            onClick={() => setSelectedChurch(church)}
                            className="w-full text-left bg-white rounded-xl shadow-sm border border-gray-100 p-4 flex items-center gap-4 hover:border-indigo-200 focus:outline-none transition-colors"
                        >
                            {church.logo_url ? (
                                <img
                                    src={church.logo_url}
                                    alt={church.name}
                                    className="w-12 h-12 rounded-lg object-contain border border-gray-100 flex-shrink-0"
                                />
                            ) : (
                                <div className="w-12 h-12 rounded-lg bg-indigo-100 flex items-center justify-center flex-shrink-0 text-xl">
                                    ⛪
                                </div>
                            )}
                            <div className="flex-1 min-w-0">
                                <p className="font-semibold text-gray-900 text-sm truncate">{church.name}</p>
                                {church.city && (
                                    <p className="text-gray-500 text-xs mt-0.5 truncate">
                                        📍 {[church.city, church.state].filter(Boolean).join(', ')}
                                    </p>
                                )}
                                {church.denomination && (
                                    <span className="inline-block mt-1 text-xs bg-indigo-50 text-indigo-600 px-2 py-0.5 rounded-full">
                                        {church.denomination}
                                    </span>
                                )}
                            </div>
                            <span className="text-gray-400 text-lg">›</span>
                        </button>
                    ))}
                </div>
            )}

            {selectedChurch && (
                <ChurchModal church={selectedChurch} onClose={() => setSelectedChurch(null)} />
            )}
        </div>
    );
}
