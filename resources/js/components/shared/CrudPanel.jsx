import React, { useState, useEffect, useRef, useCallback } from 'react';

export function Modal({ isOpen, onClose, title, children }) {
    if (!isOpen) return null;
    return (
        <div className="fixed inset-0 z-50 overflow-y-auto">
            <div className="flex items-center justify-center min-h-screen px-4">
                <div className="fixed inset-0 bg-black bg-opacity-50 transition-opacity" onClick={onClose}></div>
                <div className="bg-white rounded-2xl shadow-xl w-full max-w-2xl relative z-10 max-h-[90vh] overflow-y-auto">
                    <div className="flex items-center justify-between p-6 border-b">
                        <h3 className="text-xl font-bold text-gray-900">{title}</h3>
                        <button onClick={onClose} className="text-gray-400 hover:text-gray-600 text-2xl">&times;</button>
                    </div>
                    <div className="p-6">{children}</div>
                </div>
            </div>
        </div>
    );
}

export function DataTable({ columns, data, actions }) {
    if (!data || data.length === 0) {
        return <div className="text-center py-12 text-gray-500">No records found.</div>;
    }
    return (
        <div className="overflow-x-auto">
            <table className="w-full text-left">
                <thead>
                    <tr className="border-b border-gray-200">
                        {columns.map((col, i) => (
                            <th key={i} className="px-4 py-3 text-xs font-semibold text-gray-500 uppercase tracking-wider">{col.label}</th>
                        ))}
                        {actions && <th className="px-4 py-3 text-xs font-semibold text-gray-500 uppercase tracking-wider">Actions</th>}
                    </tr>
                </thead>
                <tbody className="divide-y divide-gray-100">
                    {data.map((row, ri) => (
                        <tr key={row.id || ri} className="hover:bg-gray-50">
                            {columns.map((col, ci) => (
                                <td key={ci} className="px-4 py-3 text-sm text-gray-700">
                                    {col.render ? col.render(row) : row[col.key]}
                                </td>
                            ))}
                            {actions && <td className="px-4 py-3 text-sm">{actions(row)}</td>}
                        </tr>
                    ))}
                </tbody>
            </table>
        </div>
    );
}

export function Pagination({ meta, onPageChange }) {
    if (!meta || meta.last_page <= 1) return null;
    return (
        <div className="flex items-center justify-between px-4 py-3 border-t">
            <span className="text-sm text-gray-600">
                Showing {meta.from}-{meta.to} of {meta.total}
            </span>
            <div className="flex gap-1">
                {Array.from({ length: meta.last_page }, (_, i) => i + 1).map(page => (
                    <button
                        key={page}
                        onClick={() => onPageChange(page)}
                        className={`px-3 py-1 rounded text-sm ${
                            page === meta.current_page
                                ? 'bg-indigo-600 text-white'
                                : 'bg-gray-100 text-gray-700 hover:bg-gray-200'
                        }`}
                    >
                        {page}
                    </button>
                ))}
            </div>
        </div>
    );
}

export function RichTextEditor({ name, value, onChange, placeholder }) {
    const editorRef = useRef(null);
    const toolbarRef = useRef(null);
    const isInternalChange = useRef(false);

    const execCmd = useCallback((cmd, val = null) => {
        editorRef.current?.focus();
        document.execCommand(cmd, false, val);
        syncContent();
    }, []);

    const syncContent = useCallback(() => {
        if (editorRef.current && !isInternalChange.current) {
            const html = editorRef.current.innerHTML;
            if (html !== value) {
                onChange({ target: { name, value: html } });
            }
        }
    }, [name, onChange, value]);

    useEffect(() => {
        if (editorRef.current && editorRef.current.innerHTML !== value) {
            isInternalChange.current = true;
            editorRef.current.innerHTML = value || '';
            isInternalChange.current = false;
        }
    }, [value]);

    const insertLink = () => {
        const url = prompt('Enter URL:');
        if (url) execCmd('createLink', url);
    };

    const insertImage = () => {
        const url = prompt('Enter image URL:');
        if (url) execCmd('insertImage', url);
    };

    const toolbarButtons = [
        { cmd: 'bold', icon: 'B', title: 'Bold', style: { fontWeight: 'bold' } },
        { cmd: 'italic', icon: 'I', title: 'Italic', style: { fontStyle: 'italic' } },
        { cmd: 'underline', icon: 'U', title: 'Underline', style: { textDecoration: 'underline' } },
        { cmd: 'strikeThrough', icon: 'S', title: 'Strikethrough', style: { textDecoration: 'line-through' } },
        { type: 'sep' },
        { cmd: 'formatBlock', val: 'H2', icon: 'H2', title: 'Heading 2' },
        { cmd: 'formatBlock', val: 'H3', icon: 'H3', title: 'Heading 3' },
        { cmd: 'formatBlock', val: 'P', icon: '&para;', title: 'Paragraph' },
        { cmd: 'formatBlock', val: 'BLOCKQUOTE', icon: '&#10077;', title: 'Quote' },
        { type: 'sep' },
        { cmd: 'insertUnorderedList', icon: '&#8226;', title: 'Bullet List' },
        { cmd: 'insertOrderedList', icon: '1.', title: 'Numbered List' },
        { type: 'sep' },
        { cmd: 'justifyLeft', icon: '&#9776;', title: 'Align Left' },
        { cmd: 'justifyCenter', icon: '&#9776;', title: 'Align Center' },
        { cmd: 'justifyRight', icon: '&#9776;', title: 'Align Right' },
        { type: 'sep' },
        { action: insertLink, icon: '&#128279;', title: 'Insert Link' },
        { action: insertImage, icon: '&#128247;', title: 'Insert Image' },
        { cmd: 'removeFormat', icon: '&#10006;', title: 'Clear Formatting' },
    ];

    return (
        <div className="mb-4">
            <div ref={toolbarRef} className="flex flex-wrap gap-0.5 p-1.5 bg-gray-50 border border-gray-300 border-b-0 rounded-t-lg">
                {toolbarButtons.map((btn, i) => {
                    if (btn.type === 'sep') return <div key={i} className="w-px h-6 bg-gray-300 mx-1 self-center" />;
                    return (
                        <button
                            key={i}
                            type="button"
                            title={btn.title}
                            onClick={(e) => { e.preventDefault(); btn.action ? btn.action() : execCmd(btn.cmd, btn.val); }}
                            className="w-8 h-8 flex items-center justify-center rounded text-sm text-gray-600 hover:bg-indigo-100 hover:text-indigo-700 transition-colors"
                            style={btn.style}
                            dangerouslySetInnerHTML={{ __html: btn.icon }}
                        />
                    );
                })}
            </div>
            <div
                ref={editorRef}
                contentEditable
                className="w-full min-h-[200px] max-h-[500px] overflow-y-auto rounded-b-lg border border-gray-300 px-4 py-3 text-sm text-gray-800 focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 outline-none bg-white"
                onInput={syncContent}
                onBlur={syncContent}
                data-placeholder={placeholder || 'Start writing...'}
                style={{ lineHeight: '1.6' }}
            />
        </div>
    );
}

export function FormField({ label, type = 'text', name, value, onChange, required, placeholder, options, rows }) {
    const id = `field-${name}`;
    if (type === 'richtext') {
        return (
            <div className="mb-4">
                <label className="block text-sm font-medium text-gray-700 mb-1">{label}</label>
                <RichTextEditor name={name} value={value} onChange={onChange} placeholder={placeholder} />
            </div>
        );
    }
    if (type === 'select') {
        return (
            <div className="mb-4">
                <label htmlFor={id} className="block text-sm font-medium text-gray-700 mb-1">{label}</label>
                <select id={id} name={name} value={value || ''} onChange={onChange} required={required}
                    className="w-full rounded-lg border border-gray-300 px-4 py-2.5 focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 outline-none">
                    <option value="">Select...</option>
                    {options?.map(opt => <option key={opt.value} value={opt.value}>{opt.label}</option>)}
                </select>
            </div>
        );
    }
    if (type === 'textarea') {
        return (
            <div className="mb-4">
                <label htmlFor={id} className="block text-sm font-medium text-gray-700 mb-1">{label}</label>
                <textarea id={id} name={name} value={value || ''} onChange={onChange} required={required}
                    placeholder={placeholder} rows={rows || 4}
                    className="w-full rounded-lg border border-gray-300 px-4 py-2.5 focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 outline-none" />
            </div>
        );
    }
    if (type === 'file') {
        return (
            <div className="mb-4">
                <label htmlFor={id} className="block text-sm font-medium text-gray-700 mb-1">{label}</label>
                <input id={id} type="file" name={name} onChange={onChange} required={required}
                    className="w-full text-sm text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded-lg file:border-0 file:text-sm file:font-semibold file:bg-indigo-50 file:text-indigo-700 hover:file:bg-indigo-100" />
            </div>
        );
    }
    if (type === 'checkbox') {
        return (
            <div className="mb-4 flex items-center gap-2">
                <input id={id} type="checkbox" name={name} checked={!!value} onChange={onChange}
                    className="rounded border-gray-300 text-indigo-600 focus:ring-indigo-500 h-4 w-4" />
                <label htmlFor={id} className="text-sm font-medium text-gray-700">{label}</label>
            </div>
        );
    }
    return (
        <div className="mb-4">
            <label htmlFor={id} className="block text-sm font-medium text-gray-700 mb-1">{label}</label>
            <input id={id} type={type} name={name} value={value || ''} onChange={onChange} required={required}
                placeholder={placeholder}
                className="w-full rounded-lg border border-gray-300 px-4 py-2.5 focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 outline-none" />
        </div>
    );
}

export function Alert({ type = 'success', message, onClose }) {
    if (!message) return null;
    const colors = {
        success: 'bg-green-50 text-green-800 border-green-200',
        error: 'bg-red-50 text-red-800 border-red-200',
        info: 'bg-blue-50 text-blue-800 border-blue-200',
    };
    return (
        <div className={`p-4 rounded-lg border mb-4 flex justify-between items-center ${colors[type]}`}>
            <span>{message}</span>
            {onClose && <button onClick={onClose} className="ml-4 text-lg">&times;</button>}
        </div>
    );
}
