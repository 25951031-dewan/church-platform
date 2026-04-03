import React, { useState, useEffect } from 'react';
import { Alert } from '../shared/CrudPanel';
import { get, post } from '../shared/api';

export default function SitemapManager() {
    const [alert, setAlert] = useState(null);
    const [generating, setGenerating] = useState(false);
    const [stats, setStats] = useState(null);
    const [lastGenerated, setLastGenerated] = useState(null);
    const [sitemapContent, setSitemapContent] = useState('');
    const [showPreview, setShowPreview] = useState(false);

    useEffect(() => { loadStats(); }, []);

    const loadStats = async () => {
        try {
            const data = await get('/api/sitemap/stats');
            setStats(data.data || {});
            setLastGenerated(data.last_generated || null);
        } catch (e) { /* stats endpoint may not exist yet */ }
    };

    const generateSitemap = async () => {
        setGenerating(true);
        try {
            const data = await post('/api/sitemap/generate', {});
            setAlert({ type: 'success', message: `Sitemap generated successfully! ${data.url_count || 0} URLs indexed.` });
            setStats(data.stats || stats);
            setLastGenerated(data.generated_at || new Date().toISOString());
        } catch (e) {
            setAlert({ type: 'error', message: e.message });
        }
        setGenerating(false);
    };

    const previewSitemap = async () => {
        try {
            const res = await fetch('/sitemap.xml');
            const text = await res.text();
            setSitemapContent(text);
            setShowPreview(true);
        } catch (e) {
            setAlert({ type: 'error', message: 'Failed to load sitemap preview.' });
        }
    };

    const statCards = [
        { label: 'Posts', icon: 'fa-newspaper', count: stats?.posts || 0, color: 'bg-blue-50 text-blue-700' },
        { label: 'Pages', icon: 'fa-file-alt', count: stats?.pages || 0, color: 'bg-indigo-50 text-indigo-700' },
        { label: 'Sermons', icon: 'fa-microphone-alt', count: stats?.sermons || 0, color: 'bg-purple-50 text-purple-700' },
        { label: 'Events', icon: 'fa-calendar-alt', count: stats?.events || 0, color: 'bg-green-50 text-green-700' },
        { label: 'Books', icon: 'fa-book', count: stats?.books || 0, color: 'bg-yellow-50 text-yellow-700' },
        { label: 'Bible Studies', icon: 'fa-book-reader', count: stats?.bible_studies || 0, color: 'bg-orange-50 text-orange-700' },
        { label: 'Ministries', icon: 'fa-hands-helping', count: stats?.ministries || 0, color: 'bg-teal-50 text-teal-700' },
        { label: 'Testimonies', icon: 'fa-cross', count: stats?.testimonies || 0, color: 'bg-pink-50 text-pink-700' },
        { label: 'Galleries', icon: 'fa-images', count: stats?.galleries || 0, color: 'bg-cyan-50 text-cyan-700' },
        { label: 'Categories', icon: 'fa-tags', count: stats?.categories || 0, color: 'bg-gray-50 text-gray-700' },
    ];

    const totalUrls = statCards.reduce((sum, s) => sum + s.count, 0) + 1 + 11; // +1 homepage +11 static

    return (
        <div>
            <div className="flex items-center justify-between mb-6">
                <div>
                    <h2 className="text-2xl font-bold text-gray-800">Sitemap Generator</h2>
                    <p className="text-sm text-gray-500 mt-1">Generate and manage your XML sitemap for search engines</p>
                </div>
                <div className="flex gap-2">
                    <button onClick={previewSitemap} className="px-4 py-2 border border-gray-300 text-gray-700 rounded-lg text-sm font-medium hover:bg-gray-50">
                        <i className="fas fa-eye mr-2"></i>Preview
                    </button>
                    <button onClick={generateSitemap} disabled={generating} className="px-4 py-2 bg-indigo-600 text-white rounded-lg text-sm font-medium hover:bg-indigo-700 disabled:opacity-50">
                        <i className={`fas ${generating ? 'fa-spinner fa-spin' : 'fa-sync-alt'} mr-2`}></i>
                        {generating ? 'Generating...' : 'Generate Sitemap'}
                    </button>
                </div>
            </div>

            <Alert {...alert} onClose={() => setAlert(null)} />

            {/* Status Card */}
            <div className="bg-white rounded-xl shadow-sm border p-6 mb-6">
                <div className="flex items-center justify-between">
                    <div className="flex items-center gap-4">
                        <div className="w-12 h-12 bg-green-100 rounded-xl flex items-center justify-center">
                            <i className="fas fa-sitemap text-green-600 text-xl"></i>
                        </div>
                        <div>
                            <h3 className="text-lg font-semibold text-gray-900">Sitemap Status</h3>
                            <p className="text-sm text-gray-500">
                                {lastGenerated ? `Last generated: ${new Date(lastGenerated).toLocaleString()}` : 'Sitemap is auto-generated on each request'}
                            </p>
                        </div>
                    </div>
                    <div className="text-right">
                        <div className="text-2xl font-bold text-indigo-600">{totalUrls}</div>
                        <div className="text-xs text-gray-500">Total URLs</div>
                    </div>
                </div>
                <div className="mt-4 flex items-center gap-4 text-sm">
                    <a href="/sitemap.xml" target="_blank" rel="noopener noreferrer" className="text-indigo-600 hover:text-indigo-800 font-medium">
                        <i className="fas fa-external-link-alt mr-1"></i>View sitemap.xml
                    </a>
                    <span className="text-gray-300">|</span>
                    <span className="text-gray-500">
                        <i className="fas fa-link mr-1"></i>{window.location.origin}/sitemap.xml
                    </span>
                </div>
            </div>

            {/* Content Stats Grid */}
            <div className="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-5 gap-4 mb-6">
                {statCards.map(card => (
                    <div key={card.label} className="bg-white rounded-xl shadow-sm border p-4">
                        <div className="flex items-center gap-3">
                            <div className={`w-9 h-9 rounded-lg flex items-center justify-center ${card.color}`}>
                                <i className={`fas ${card.icon} text-sm`}></i>
                            </div>
                            <div>
                                <div className="text-lg font-bold text-gray-900">{card.count}</div>
                                <div className="text-xs text-gray-500">{card.label}</div>
                            </div>
                        </div>
                    </div>
                ))}
            </div>

            {/* SEO Tips */}
            <div className="bg-white rounded-xl shadow-sm border p-6 mb-6">
                <h3 className="text-lg font-semibold text-gray-900 mb-4"><i className="fas fa-lightbulb text-yellow-500 mr-2"></i>SEO Tips</h3>
                <div className="grid md:grid-cols-2 gap-4">
                    <div className="flex items-start gap-3">
                        <i className="fas fa-check-circle text-green-500 mt-0.5"></i>
                        <div>
                            <div className="text-sm font-medium text-gray-800">Submit to Google Search Console</div>
                            <div className="text-xs text-gray-500">Add your sitemap URL to Google Search Console for faster indexing</div>
                        </div>
                    </div>
                    <div className="flex items-start gap-3">
                        <i className="fas fa-check-circle text-green-500 mt-0.5"></i>
                        <div>
                            <div className="text-sm font-medium text-gray-800">Submit to Bing Webmaster</div>
                            <div className="text-xs text-gray-500">Also submit to Bing for broader search engine coverage</div>
                        </div>
                    </div>
                    <div className="flex items-start gap-3">
                        <i className="fas fa-check-circle text-green-500 mt-0.5"></i>
                        <div>
                            <div className="text-sm font-medium text-gray-800">Add to robots.txt</div>
                            <div className="text-xs text-gray-500">Include <code className="bg-gray-100 px-1 rounded">Sitemap: {window.location.origin}/sitemap.xml</code></div>
                        </div>
                    </div>
                    <div className="flex items-start gap-3">
                        <i className="fas fa-check-circle text-green-500 mt-0.5"></i>
                        <div>
                            <div className="text-sm font-medium text-gray-800">Keep content fresh</div>
                            <div className="text-xs text-gray-500">Regularly publish new posts and update existing content</div>
                        </div>
                    </div>
                </div>
            </div>

            {/* Sitemap Preview Modal */}
            {showPreview && (
                <div className="fixed inset-0 z-50 flex items-center justify-center bg-black bg-opacity-50" onClick={() => setShowPreview(false)}>
                    <div className="bg-white rounded-xl shadow-xl w-full max-w-4xl max-h-[80vh] flex flex-col m-4" onClick={e => e.stopPropagation()}>
                        <div className="flex items-center justify-between p-4 border-b">
                            <h3 className="text-lg font-semibold text-gray-900">Sitemap Preview</h3>
                            <button onClick={() => setShowPreview(false)} className="text-gray-400 hover:text-gray-600 text-xl">&times;</button>
                        </div>
                        <div className="flex-1 overflow-auto p-4">
                            <pre className="text-xs text-gray-700 bg-gray-50 p-4 rounded-lg overflow-x-auto whitespace-pre-wrap font-mono">{sitemapContent}</pre>
                        </div>
                    </div>
                </div>
            )}
        </div>
    );
}
