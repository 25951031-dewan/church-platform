import { useQuery } from '@tanstack/react-query';
import axios from 'axios';
import DOMPurify from 'dompurify';
import { useState } from 'react';

interface Faq {
    id: number;
    question: string;
    answer: string;
    sort_order: number;
}

interface FaqCategory {
    id: number;
    name: string;
    description: string | null;
    published_faqs: Faq[];
}

function SafeHtml({ html }: { html: string }) {
    return (
        <div
            className="prose prose-sm max-w-none pb-4 text-gray-600"
            // eslint-disable-next-line react/no-danger
            dangerouslySetInnerHTML={{ __html: DOMPurify.sanitize(html) }}
        />
    );
}

function AccordionItem({ faq }: { faq: Faq }) {
    const [open, setOpen] = useState(false);

    return (
        <div className="border-b border-gray-200 last:border-0">
            <button
                type="button"
                onClick={() => setOpen(!open)}
                className="flex w-full items-center justify-between py-4 text-left text-sm font-medium text-gray-900 hover:text-blue-600 focus:outline-none"
            >
                <span>{faq.question}</span>
                <svg
                    className={`ml-4 h-5 w-5 shrink-0 text-gray-400 transition-transform duration-200 ${open ? 'rotate-180' : ''}`}
                    viewBox="0 0 20 20" fill="currentColor"
                >
                    <path fillRule="evenodd" clipRule="evenodd"
                        d="M5.23 7.21a.75.75 0 011.06.02L10 11.168l3.71-3.938a.75.75 0 111.08 1.04l-4.25 4.5a.75.75 0 01-1.08 0l-4.25-4.5a.75.75 0 01.02-1.06z"
                    />
                </svg>
            </button>

            {open && <SafeHtml html={faq.answer} />}
        </div>
    );
}

export default function FaqPage() {
    const { data: categories = [], isLoading, isError } = useQuery<FaqCategory[]>({
        queryKey: ['faq'],
        queryFn:  () => axios.get('/api/v1/faq').then(r => r.data),
    });

    if (isLoading) {
        return (
            <div className="flex items-center justify-center py-24 text-gray-400">
                Loading…
            </div>
        );
    }

    if (isError) {
        return (
            <div className="py-12 text-center text-sm text-red-500">
                Failed to load FAQs. Please try again.
            </div>
        );
    }

    if (categories.length === 0) {
        return (
            <div className="py-12 text-center text-sm text-gray-400">
                No FAQs available yet.
            </div>
        );
    }

    return (
        <div className="mx-auto max-w-3xl px-4 py-12 sm:px-6">
            <h1 className="mb-10 text-3xl font-bold text-gray-900">
                Frequently Asked Questions
            </h1>

            <div className="space-y-10">
                {categories.map(category => (
                    <section key={category.id}>
                        <h2 className="mb-1 text-lg font-semibold text-gray-900">
                            {category.name}
                        </h2>

                        {category.description && (
                            <p className="mb-4 text-sm text-gray-500">{category.description}</p>
                        )}

                        <div className="rounded-lg border border-gray-200 bg-white px-4">
                            {category.published_faqs.length === 0 ? (
                                <p className="py-4 text-sm text-gray-400">No questions in this category.</p>
                            ) : (
                                category.published_faqs.map(faq => (
                                    <AccordionItem key={faq.id} faq={faq} />
                                ))
                            )}
                        </div>
                    </section>
                ))}
            </div>
        </div>
    );
}
