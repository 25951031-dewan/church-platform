import { useState } from 'react';
import { Search } from 'lucide-react';
import { useChurchDirectory } from '../queries';
import { ChurchCard } from '../components/ChurchCard';

export function ChurchDirectoryPage() {
    const [search, setSearch] = useState('');
    const [city, setCity] = useState('');
    const [denomination, setDenomination] = useState('');

    const filters: Record<string, string> = {};
    if (search) filters.search = search;
    if (city) filters.city = city;
    if (denomination) filters.denomination = denomination;

    const { data, fetchNextPage, hasNextPage, isFetchingNextPage } = useChurchDirectory(filters);
    const churches = data?.pages.flatMap(p => p.data) ?? [];

    return (
        <div className="max-w-5xl mx-auto px-4 py-8">
            <div className="flex items-center justify-between mb-6">
                <h1 className="text-2xl font-bold">Church Directory</h1>
            </div>

            <div className="flex flex-wrap gap-3 mb-6">
                <div className="relative flex-1 min-w-48">
                    <Search size={14} className="absolute left-3 top-1/2 -translate-y-1/2 text-white/40" />
                    <input
                        type="text"
                        placeholder="Search churches..."
                        value={search}
                        onChange={e => setSearch(e.target.value)}
                        className="w-full pl-8 pr-3 py-2 text-sm rounded-lg bg-white/10 border border-white/10 focus:outline-none focus:border-indigo-500"
                    />
                </div>
                <input
                    type="text"
                    placeholder="City..."
                    value={city}
                    onChange={e => setCity(e.target.value)}
                    className="px-3 py-2 text-sm rounded-lg bg-white/10 border border-white/10 focus:outline-none focus:border-indigo-500 w-36"
                />
                <input
                    type="text"
                    placeholder="Denomination..."
                    value={denomination}
                    onChange={e => setDenomination(e.target.value)}
                    className="px-3 py-2 text-sm rounded-lg bg-white/10 border border-white/10 focus:outline-none focus:border-indigo-500 w-44"
                />
            </div>

            {churches.length === 0 ? (
                <p className="text-center text-white/40 py-16">No churches found.</p>
            ) : (
                <div className="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
                    {churches.map(church => (
                        <ChurchCard key={church.id} church={church} />
                    ))}
                </div>
            )}

            {hasNextPage && (
                <div className="flex justify-center mt-8">
                    <button
                        onClick={() => fetchNextPage()}
                        disabled={isFetchingNextPage}
                        className="px-6 py-2 text-sm rounded-full bg-white/10 hover:bg-white/20 transition-colors disabled:opacity-50"
                    >
                        {isFetchingNextPage ? 'Loading\u2026' : 'Load more'}
                    </button>
                </div>
            )}
        </div>
    );
}
