import { useMutation, useQuery } from '@tanstack/react-query';
import axios from 'axios';
import grapesjs from 'grapesjs';
import 'grapesjs/dist/css/grapes.min.css';
import gjsBlocksBasic from 'grapesjs-blocks-basic';
import gjsPresetWebpage from 'grapesjs-preset-webpage';
import { useEffect, useRef, useState } from 'react';
import { useNavigate, useParams } from 'react-router-dom';

// ─── Church-specific block definitions ───────────────────────────────────────

const CHURCH_BLOCKS = [
    {
        id:       'sermon-embed',
        label:    'Sermon Embed',
        category: 'Church',
        content:  `<div data-block="sermon-embed" class="church-block sermon-embed">
                     <h3>Latest Sermon</h3>
                     <p><em>Sermon content loads here</em></p>
                   </div>`,
    },
    {
        id:       'event-list',
        label:    'Event List',
        category: 'Church',
        content:  `<div data-block="event-list" class="church-block event-list">
                     <h3>Upcoming Events</h3>
                     <ul><li><em>Events load here</em></li></ul>
                   </div>`,
    },
    {
        id:       'prayer-wall',
        label:    'Prayer Wall',
        category: 'Church',
        content:  `<div data-block="prayer-wall" class="church-block prayer-wall">
                     <h3>Prayer Requests</h3>
                     <p><em>Prayer requests load here</em></p>
                   </div>`,
    },
    {
        id:       'verse-card',
        label:    'Verse Card',
        category: 'Church',
        content:  `<div data-block="verse-card" class="church-block verse-card"
                        style="padding:24px;background:#f0f7ff;border-left:4px solid #2563eb;border-radius:8px;">
                     <p style="font-style:italic;font-size:1.1em;">"For God so loved the world…"</p>
                     <p style="font-size:0.85em;color:#64748b;">John 3:16</p>
                   </div>`,
    },
    {
        id:       'contact-form',
        label:    'Contact Form',
        category: 'Church',
        content:  `<form data-block="contact-form" class="church-block contact-form">
                     <h3>Contact Us</h3>
                     <input type="text"  placeholder="Your name"    style="display:block;width:100%;margin-bottom:8px;padding:8px;border:1px solid #ccc;border-radius:4px;" />
                     <input type="email" placeholder="Your email"   style="display:block;width:100%;margin-bottom:8px;padding:8px;border:1px solid #ccc;border-radius:4px;" />
                     <textarea           placeholder="Your message" style="display:block;width:100%;margin-bottom:8px;padding:8px;border:1px solid #ccc;border-radius:4px;" rows="4"></textarea>
                     <button type="submit" style="padding:10px 24px;background:#2563eb;color:#fff;border:none;border-radius:4px;cursor:pointer;">Send</button>
                   </form>`,
    },
];

// ─── Component ────────────────────────────────────────────────────────────────

export default function PageBuilder() {
    const { id }           = useParams();
    const navigate         = useNavigate();
    const editorRef        = useRef(null);
    const containerRef     = useRef(null);
    const [ready, setReady] = useState(false);
    const [saving, setSaving] = useState(false);
    const [saveMsg, setSaveMsg] = useState('');

    const { data: page, isLoading } = useQuery({
        queryKey: ['admin-page-builder', id],
        queryFn:  () => axios.get(`/api/v1/admin/pages/${id}/builder`).then(r => r.data),
        enabled:  !!id,
    });

    const saveMutation = useMutation({
        mutationFn: (payload) => axios.put(`/api/v1/admin/pages/${id}/builder`, payload),
        onSuccess:  () => {
            setSaveMsg('Saved!');
            setTimeout(() => setSaveMsg(''), 2500);
        },
        onError: () => setSaveMsg('Save failed.'),
        onSettled: () => setSaving(false),
    });

    // Initialise GrapesJS once data is loaded
    useEffect(() => {
        if (! page || ! containerRef.current || editorRef.current) return;

        const editor = grapesjs.init({
            container:     containerRef.current,
            fromElement:   false,
            storageManager: false,   // we handle saving manually
            plugins:       [gjsPresetWebpage, gjsBlocksBasic],
            pluginsOpts:   {
                [gjsPresetWebpage]: {},
                [gjsBlocksBasic]:   { flexGrid: true },
            },
            canvas: {
                styles: [
                    'https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700&display=swap',
                ],
            },
        });

        // Register church-specific blocks
        CHURCH_BLOCKS.forEach(block => editor.BlockManager.add(block.id, block));

        // Load saved state if available
        if (page.builder_data) {
            editor.loadProjectData(page.builder_data);
        } else if (page.builder_html) {
            editor.setComponents(page.builder_html);
            if (page.builder_css) editor.setStyle(page.builder_css);
        }

        editorRef.current = editor;
        setReady(true);

        return () => {
            editor.destroy();
            editorRef.current = null;
        };
    }, [page]);

    function handleSave() {
        if (! editorRef.current) return;
        setSaving(true);
        setSaveMsg('');

        const editor = editorRef.current;

        saveMutation.mutate({
            builder_data: editor.getProjectData(),
            builder_html: editor.getHtml(),
            builder_css:  editor.getCss(),
        });
    }

    if (isLoading) {
        return (
            <div className="flex h-screen items-center justify-center text-gray-400">
                Loading editor…
            </div>
        );
    }

    return (
        <div className="flex h-screen flex-col">
            {/* Toolbar */}
            <div className="flex items-center justify-between border-b border-gray-200 bg-white px-4 py-2 shadow-sm">
                <div className="flex items-center gap-3">
                    <button
                        type="button"
                        onClick={() => navigate(-1)}
                        className="text-sm text-gray-500 hover:text-gray-700"
                    >
                        ← Back
                    </button>
                    <span className="text-sm font-medium text-gray-900">
                        {page?.title ?? 'Page Builder'}
                    </span>
                </div>

                <div className="flex items-center gap-3">
                    {saveMsg && (
                        <span className={`text-sm ${saveMsg === 'Saved!' ? 'text-green-600' : 'text-red-500'}`}>
                            {saveMsg}
                        </span>
                    )}
                    <button
                        type="button"
                        onClick={handleSave}
                        disabled={saving || ! ready}
                        className="rounded-lg bg-blue-600 px-4 py-1.5 text-sm font-medium text-white hover:bg-blue-700 disabled:opacity-50"
                    >
                        {saving ? 'Saving…' : 'Save'}
                    </button>
                </div>
            </div>

            {/* GrapesJS mounts here — it takes the full remaining height */}
            <div ref={containerRef} className="flex-1 overflow-hidden" />
        </div>
    );
}
