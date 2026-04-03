import React, { useState, useEffect } from 'react';
import { Modal, DataTable, Pagination, FormField, Alert } from '../shared/CrudPanel';
import { get, post, put, del, patch, extractPaginatedData } from '../shared/api';

export default function ContactsManager() {
    const [items, setItems] = useState([]);
    const [meta, setMeta] = useState(null);
    const [showModal, setShowModal] = useState(false);
    const [replyingTo, setReplyingTo] = useState(null);
    const [form, setForm] = useState({ reply_message: '' });
    const [alert, setAlert] = useState(null);
    const [loading, setLoading] = useState(false);
    const [selectedContact, setSelectedContact] = useState(null);
    const [showDetailModal, setShowDetailModal] = useState(false);

    const fetchItems = async (page = 1) => {
        setLoading(true);
        try {
            const data = await get(`/api/contacts?page=${page}`);
            const { items, meta } = extractPaginatedData(data);
            setItems(items);
            setMeta(meta);
        } catch (e) {
            setAlert({ type: 'error', message: e.message });
        }
        setLoading(false);
    };

    useEffect(() => { fetchItems(); }, []);

    const handleChange = (e) => {
        setForm({ ...form, [e.target.name]: e.target.value });
    };

    const handleMarkRead = async (item) => {
        try {
            await patch(`/api/contacts/${item.id}/read`, {});
            setAlert({ type: 'success', message: 'Contact marked as read.' });
            fetchItems();
        } catch (e) {
            setAlert({ type: 'error', message: e.message });
        }
    };

    const openReply = (item) => {
        setReplyingTo(item);
        setForm({ reply_message: '' });
        setShowModal(true);
    };

    const handleReply = async (e) => {
        e.preventDefault();
        try {
            await post(`/api/contacts/${replyingTo.id}/reply`, { reply_message: form.reply_message });
            setAlert({ type: 'success', message: 'Reply sent successfully.' });
            setShowModal(false);
            setReplyingTo(null);
            fetchItems();
        } catch (e) {
            setAlert({ type: 'error', message: e.message });
        }
    };

    const handleDelete = async (item) => {
        if (!confirm('Are you sure you want to delete this contact submission?')) return;
        try {
            await del(`/api/contacts/${item.id}`);
            setAlert({ type: 'success', message: 'Contact deleted successfully.' });
            fetchItems();
        } catch (e) {
            setAlert({ type: 'error', message: e.message });
        }
    };

    const viewDetail = (item) => {
        setSelectedContact(item);
        setShowDetailModal(true);
    };

    const columns = [
        { key: 'name', label: 'Name' },
        { key: 'email', label: 'Email' },
        {
            key: 'subject', label: 'Subject',
            render: (r) => (r.subject || '').length > 40 ? r.subject.substring(0, 40) + '...' : r.subject,
        },
        {
            key: 'is_read', label: 'Status',
            render: (r) => (
                <span className={`inline-flex px-2 py-0.5 rounded-full text-xs font-medium ${r.is_read ? 'bg-green-100 text-green-800' : 'bg-yellow-100 text-yellow-800'}`}>
                    {r.is_read ? 'Read' : 'Unread'}
                </span>
            ),
        },
        {
            key: 'replied_at', label: 'Replied',
            render: (r) => (
                <span className={`inline-flex px-2 py-0.5 rounded-full text-xs font-medium ${r.replied_at ? 'bg-blue-100 text-blue-800' : 'bg-gray-100 text-gray-600'}`}>
                    {r.replied_at ? new Date(r.replied_at).toLocaleDateString() : 'No reply'}
                </span>
            ),
        },
        {
            key: 'created_at', label: 'Received',
            render: (r) => r.created_at ? new Date(r.created_at).toLocaleDateString() : '',
        },
    ];

    return (
        <div>
            <div className="flex items-center justify-between mb-6">
                <div>
                    <h2 className="text-2xl font-bold text-gray-800">Contact Submissions</h2>
                    <p className="text-sm text-gray-500 mt-1">Messages received from the public contact form.</p>
                </div>
            </div>
            <Alert {...alert} onClose={() => setAlert(null)} />
            <div className="bg-white rounded-xl shadow-sm border">
                <DataTable columns={columns} data={items} actions={(row) => (
                    <div className="flex gap-2">
                        <button onClick={() => viewDetail(row)} className="text-gray-600 hover:text-gray-800 text-sm font-medium">View</button>
                        {!row.is_read && (
                            <button onClick={() => handleMarkRead(row)} className="text-green-600 hover:text-green-800 text-sm font-medium">Mark Read</button>
                        )}
                        {!row.replied_at && (
                            <button onClick={() => openReply(row)} className="text-indigo-600 hover:text-indigo-800 text-sm font-medium">Reply</button>
                        )}
                        <button onClick={() => handleDelete(row)} className="text-red-600 hover:text-red-800 text-sm font-medium">Delete</button>
                    </div>
                )} />
                <Pagination meta={meta} onPageChange={fetchItems} />
            </div>

            {/* Reply Modal */}
            <Modal isOpen={showModal} onClose={() => { setShowModal(false); setReplyingTo(null); }} title="Reply to Contact">
                {replyingTo && (
                    <div>
                        <div className="bg-gray-50 rounded-lg p-4 mb-4">
                            <div className="grid grid-cols-2 gap-2 text-sm mb-3">
                                <div>
                                    <span className="font-medium text-gray-600">From:</span>
                                    <span className="ml-2 text-gray-800">{replyingTo.name}</span>
                                </div>
                                <div>
                                    <span className="font-medium text-gray-600">Email:</span>
                                    <span className="ml-2 text-gray-800">{replyingTo.email}</span>
                                </div>
                            </div>
                            <div className="text-sm mb-2">
                                <span className="font-medium text-gray-600">Subject:</span>
                                <span className="ml-2 text-gray-800">{replyingTo.subject}</span>
                            </div>
                            <div className="text-sm">
                                <span className="font-medium text-gray-600">Message:</span>
                                <p className="mt-1 text-gray-700 whitespace-pre-wrap">{replyingTo.message}</p>
                            </div>
                        </div>
                        <form onSubmit={handleReply} className="space-y-4">
                            <FormField
                                label="Your Reply"
                                name="reply_message"
                                type="textarea"
                                value={form.reply_message}
                                onChange={handleChange}
                                required
                                rows={6}
                                placeholder="Type your reply message..."
                            />
                            <div className="flex justify-end gap-3 pt-2">
                                <button type="button" onClick={() => { setShowModal(false); setReplyingTo(null); }} className="px-4 py-2 border border-gray-300 text-gray-700 rounded-lg hover:bg-gray-50">Cancel</button>
                                <button type="submit" className="px-4 py-2 bg-indigo-600 text-white rounded-lg hover:bg-indigo-700">Send Reply</button>
                            </div>
                        </form>
                    </div>
                )}
            </Modal>

            {/* Detail View Modal */}
            <Modal isOpen={showDetailModal} onClose={() => { setShowDetailModal(false); setSelectedContact(null); }} title="Contact Details">
                {selectedContact && (
                    <div className="space-y-4">
                        <div className="grid grid-cols-2 gap-4">
                            <div>
                                <label className="block text-xs font-medium text-gray-500 uppercase tracking-wider mb-1">Name</label>
                                <p className="text-sm text-gray-900">{selectedContact.name}</p>
                            </div>
                            <div>
                                <label className="block text-xs font-medium text-gray-500 uppercase tracking-wider mb-1">Email</label>
                                <p className="text-sm text-gray-900">{selectedContact.email}</p>
                            </div>
                            {selectedContact.phone && (
                                <div>
                                    <label className="block text-xs font-medium text-gray-500 uppercase tracking-wider mb-1">Phone</label>
                                    <p className="text-sm text-gray-900">{selectedContact.phone}</p>
                                </div>
                            )}
                            <div>
                                <label className="block text-xs font-medium text-gray-500 uppercase tracking-wider mb-1">Received</label>
                                <p className="text-sm text-gray-900">{selectedContact.created_at ? new Date(selectedContact.created_at).toLocaleString() : 'N/A'}</p>
                            </div>
                        </div>
                        <div>
                            <label className="block text-xs font-medium text-gray-500 uppercase tracking-wider mb-1">Subject</label>
                            <p className="text-sm text-gray-900">{selectedContact.subject}</p>
                        </div>
                        <div>
                            <label className="block text-xs font-medium text-gray-500 uppercase tracking-wider mb-1">Message</label>
                            <p className="text-sm text-gray-900 whitespace-pre-wrap bg-gray-50 rounded-lg p-3">{selectedContact.message}</p>
                        </div>
                        <div className="flex gap-4">
                            <div>
                                <span className={`inline-flex px-2 py-0.5 rounded-full text-xs font-medium ${selectedContact.is_read ? 'bg-green-100 text-green-800' : 'bg-yellow-100 text-yellow-800'}`}>
                                    {selectedContact.is_read ? 'Read' : 'Unread'}
                                </span>
                            </div>
                            <div>
                                <span className={`inline-flex px-2 py-0.5 rounded-full text-xs font-medium ${selectedContact.replied_at ? 'bg-blue-100 text-blue-800' : 'bg-gray-100 text-gray-600'}`}>
                                    {selectedContact.replied_at ? `Replied ${new Date(selectedContact.replied_at).toLocaleDateString()}` : 'No reply sent'}
                                </span>
                            </div>
                        </div>
                        <div className="flex justify-end gap-3 pt-2 border-t">
                            {!selectedContact.is_read && (
                                <button onClick={() => { handleMarkRead(selectedContact); setShowDetailModal(false); }} className="px-4 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700 text-sm">Mark as Read</button>
                            )}
                            {!selectedContact.replied_at && (
                                <button onClick={() => { setShowDetailModal(false); openReply(selectedContact); }} className="px-4 py-2 bg-indigo-600 text-white rounded-lg hover:bg-indigo-700 text-sm">Reply</button>
                            )}
                            <button onClick={() => setShowDetailModal(false)} className="px-4 py-2 border border-gray-300 text-gray-700 rounded-lg hover:bg-gray-50 text-sm">Close</button>
                        </div>
                    </div>
                )}
            </Modal>
        </div>
    );
}
