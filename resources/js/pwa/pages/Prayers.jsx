import React, { useEffect, useState } from 'react';
import { get, post } from '../../components/shared/api';
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
        <div className="my-2 p-3 bg-red-100 border border-red-300 text-red-700 rounded-lg text-sm">
            {message}
        </div>
    );
}

export default function Prayers() {
    const { user } = useAuth();
    const [prayers, setPrayers] = useState([]);
    const [loading, setLoading] = useState(true);
    const [error, setError] = useState(null);
    const [prayedIds, setPrayedIds] = useState([]);

    // Submit form state
    const [form, setForm] = useState({ request: '', name: '', anonymous: false });
    const [submitting, setSubmitting] = useState(false);
    const [submitError, setSubmitError] = useState(null);
    const [submitSuccess, setSubmitSuccess] = useState(false);

    useEffect(() => {
        setLoading(true);
        setError(null);
        get('/api/prayer-requests/public')
            .then((data) => {
                const list = Array.isArray(data) ? data : (data?.data || []);
                setPrayers(list);
            })
            .catch(() => setError('Failed to load prayer requests.'))
            .finally(() => setLoading(false));
    }, []);

    const handlePray = async (id) => {
        if (prayedIds.includes(id)) return;
        try {
            await post(`/api/prayer-requests/${id}/pray`, {});
            setPrayedIds((prev) => [...prev, id]);
        } catch {
            // silently ignore
        }
    };

    const handleSubmit = async (e) => {
        e.preventDefault();
        if (!form.request.trim()) return;
        setSubmitting(true);
        setSubmitError(null);
        setSubmitSuccess(false);
        try {
            await post('/api/prayer-requests', {
                request: form.request,
                name: form.anonymous ? '' : form.name,
                is_anonymous: form.anonymous,
            });
            setSubmitSuccess(true);
            setForm({ request: '', name: '', anonymous: false });
        } catch (err) {
            setSubmitError(err?.message || 'Failed to submit prayer request.');
        } finally {
            setSubmitting(false);
        }
    };

    return (
        <div className="px-4 py-4 space-y-6">
            <h1 className="text-xl font-bold text-gray-800">Prayer Requests</h1>

            {error && <ErrorBanner message={error} />}

            {/* Prayer list */}
            {loading ? (
                <Spinner />
            ) : prayers.length === 0 ? (
                <p className="text-center text-gray-400 py-4">No prayer requests yet.</p>
            ) : (
                <div className="space-y-3">
                    {prayers.map((prayer, i) => (
                        <div
                            key={prayer.id || i}
                            className="bg-white rounded-xl shadow-sm border border-gray-100 p-4"
                        >
                            <p className="text-gray-800 text-sm leading-relaxed">{prayer.request || prayer.body}</p>
                            <div className="flex items-center justify-between mt-3">
                                <div>
                                    <span className="text-xs text-gray-500 font-medium">
                                        {prayer.is_anonymous || !prayer.name
                                            ? 'Anonymous'
                                            : prayer.name}
                                    </span>
                                    {prayer.created_at && (
                                        <span className="text-xs text-gray-400 ml-2">
                                            · {new Date(prayer.created_at).toLocaleDateString()}
                                        </span>
                                    )}
                                </div>
                                <button
                                    onClick={() => handlePray(prayer.id)}
                                    disabled={prayedIds.includes(prayer.id)}
                                    className={`flex items-center gap-1 px-3 py-1 rounded-full text-xs font-medium focus:outline-none transition-colors ${
                                        prayedIds.includes(prayer.id)
                                            ? 'bg-indigo-100 text-indigo-600'
                                            : 'bg-gray-100 text-gray-600 hover:bg-indigo-100 hover:text-indigo-600'
                                    }`}
                                >
                                    🙏 {prayedIds.includes(prayer.id) ? 'Prayed' : 'Pray'}
                                </button>
                            </div>
                        </div>
                    ))}
                </div>
            )}

            {/* Submit form */}
            <div className="bg-white rounded-xl shadow-sm border border-gray-100 p-4">
                <h2 className="text-base font-bold text-gray-800 mb-3">Submit a Prayer Request</h2>

                {submitSuccess && (
                    <div className="mb-3 p-3 bg-green-50 border border-green-200 text-green-700 rounded-lg text-sm">
                        Your prayer request has been submitted. 🙏
                    </div>
                )}
                {submitError && <ErrorBanner message={submitError} />}

                <form onSubmit={handleSubmit} className="space-y-3">
                    <div>
                        <label className="block text-xs font-medium text-gray-600 mb-1">
                            Your Name (optional)
                        </label>
                        <input
                            type="text"
                            value={form.name}
                            onChange={(e) => setForm((f) => ({ ...f, name: e.target.value }))}
                            disabled={form.anonymous}
                            placeholder="Your name"
                            className="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-indigo-400 disabled:bg-gray-50 disabled:text-gray-400"
                        />
                    </div>

                    <label className="flex items-center gap-2 cursor-pointer">
                        <input
                            type="checkbox"
                            checked={form.anonymous}
                            onChange={(e) => setForm((f) => ({ ...f, anonymous: e.target.checked }))}
                            className="rounded text-indigo-600"
                        />
                        <span className="text-sm text-gray-600">Submit anonymously</span>
                    </label>

                    <div>
                        <label className="block text-xs font-medium text-gray-600 mb-1">
                            Prayer Request <span className="text-red-500">*</span>
                        </label>
                        <textarea
                            value={form.request}
                            onChange={(e) => setForm((f) => ({ ...f, request: e.target.value }))}
                            rows={4}
                            placeholder="Share your prayer request..."
                            required
                            className="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-indigo-400 resize-none"
                        />
                    </div>

                    <button
                        type="submit"
                        disabled={submitting || !form.request.trim()}
                        className="w-full py-2.5 bg-indigo-600 text-white text-sm font-semibold rounded-lg hover:bg-indigo-700 disabled:opacity-60 focus:outline-none"
                    >
                        {submitting ? 'Submitting...' : 'Submit Prayer Request'}
                    </button>
                </form>
            </div>
        </div>
    );
}
