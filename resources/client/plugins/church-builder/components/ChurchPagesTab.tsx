import { useState } from 'react';
import { useChurchPage, useChurchPages } from '../queries';
import type { Church } from '../queries';

interface Props {
    church: Church;
}

export function ChurchPagesTab({ church }: Props) {
    const { data: pages = [] } = useChurchPages(church.id);
    const [selectedPageId, setSelectedPageId] = useState<number | null>(
        pages[0]?.id ?? null
    );
    const { data: page } = useChurchPage(church.id, selectedPageId ?? 0);

    if (pages.length === 0) {
        return <p className="text-sm text-white/40 text-center py-8">No pages yet.</p>;
    }

    return (
        <div className="flex gap-6">
            <nav className="w-48 shrink-0 space-y-1">
                {pages.map(p => (
                    <button
                        key={p.id}
                        onClick={() => setSelectedPageId(p.id)}
                        className={`w-full text-left text-sm px-3 py-2 rounded-lg transition-colors ${
                            selectedPageId === p.id
                                ? 'bg-indigo-600 text-white'
                                : 'text-white/60 hover:bg-white/10'
                        }`}
                    >
                        {p.title}
                    </button>
                ))}
            </nav>
            <div className="flex-1 min-w-0">
                {page ? (
                    <div className="prose prose-invert prose-sm max-w-none">
                        <h2>{page.title}</h2>
                        <div dangerouslySetInnerHTML={{ __html: page.body ?? '' }} />
                    </div>
                ) : (
                    <p className="text-sm text-white/40">Select a page.</p>
                )}
            </div>
        </div>
    );
}
