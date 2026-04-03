import React, { useState, useEffect } from 'react';
import { Modal, DataTable, Pagination, FormField, Alert } from '../shared/CrudPanel';
import { get, post, put, del } from '../shared/api';

export default function DonationsManager() {
    const [items, setItems] = useState([]);
    const [meta, setMeta] = useState(null);
    const [alert, setAlert] = useState(null);
    const [loading, setLoading] = useState(false);
    const [hasApi, setHasApi] = useState(false);

    const fetchItems = async (page = 1) => {
        setLoading(true);
        try {
            const data = await get(`/api/donations?page=${page}`);
            setItems(data.data || []);
            setMeta(data);
            setHasApi(true);
        } catch (e) {
            // API endpoint may not exist yet - show placeholder
            setHasApi(false);
            setItems([]);
            setMeta(null);
        }
        setLoading(false);
    };

    useEffect(() => { fetchItems(); }, []);

    const columns = [
        { key: 'donor_name', label: 'Donor Name', render: (r) => r.donor_name || 'Anonymous' },
        {
            key: 'amount', label: 'Amount',
            render: (r) => {
                const amount = parseFloat(r.amount);
                return isNaN(amount) ? r.amount : `$${amount.toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 })}`;
            },
        },
        { key: 'method', label: 'Method', render: (r) => r.method || '-' },
        {
            key: 'date', label: 'Date',
            render: (r) => r.date ? new Date(r.date).toLocaleDateString() : (r.created_at ? new Date(r.created_at).toLocaleDateString() : '-'),
        },
        {
            key: 'status', label: 'Status',
            render: (r) => {
                const statusColors = {
                    completed: 'bg-green-100 text-green-800',
                    pending: 'bg-yellow-100 text-yellow-800',
                    failed: 'bg-red-100 text-red-800',
                    refunded: 'bg-gray-100 text-gray-800',
                };
                const color = statusColors[r.status] || 'bg-gray-100 text-gray-600';
                return (
                    <span className={`inline-flex px-2 py-0.5 rounded-full text-xs font-medium capitalize ${color}`}>
                        {r.status || 'N/A'}
                    </span>
                );
            },
        },
    ];

    // Placeholder when API is not available
    if (!loading && !hasApi) {
        return (
            <div>
                <div className="flex items-center justify-between mb-6">
                    <div>
                        <h2 className="text-2xl font-bold text-gray-800">Donations</h2>
                        <p className="text-sm text-gray-500 mt-1">Track and manage donations received by the church.</p>
                    </div>
                </div>
                <Alert {...alert} onClose={() => setAlert(null)} />
                <div className="bg-white rounded-xl shadow-sm border">
                    <div className="text-center py-20 px-6">
                        <div className="mx-auto w-16 h-16 bg-indigo-50 rounded-full flex items-center justify-center mb-4">
                            <svg className="w-8 h-8 text-indigo-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={1.5} d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                        </div>
                        <h3 className="text-lg font-semibold text-gray-900 mb-2">Donation Tracking Coming Soon</h3>
                        <p className="text-gray-500 max-w-sm mx-auto">
                            Donation tracking will be available soon. This feature will allow you to view and manage all donation records, generate reports, and track giving trends.
                        </p>
                    </div>
                </div>
            </div>
        );
    }

    return (
        <div>
            <div className="flex items-center justify-between mb-6">
                <div>
                    <h2 className="text-2xl font-bold text-gray-800">Donations</h2>
                    <p className="text-sm text-gray-500 mt-1">View donation records received by the church.</p>
                </div>
            </div>
            <Alert {...alert} onClose={() => setAlert(null)} />

            {/* Summary Stats */}
            {items.length > 0 && (
                <div className="grid grid-cols-1 sm:grid-cols-3 gap-4 mb-6">
                    <div className="bg-white rounded-xl shadow-sm border p-4">
                        <div className="text-sm font-medium text-gray-500">Total Records</div>
                        <div className="text-2xl font-bold text-gray-900 mt-1">{meta?.total || items.length}</div>
                    </div>
                    <div className="bg-white rounded-xl shadow-sm border p-4">
                        <div className="text-sm font-medium text-gray-500">Page Total</div>
                        <div className="text-2xl font-bold text-green-600 mt-1">
                            ${items.reduce((sum, i) => sum + (parseFloat(i.amount) || 0), 0).toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 })}
                        </div>
                    </div>
                    <div className="bg-white rounded-xl shadow-sm border p-4">
                        <div className="text-sm font-medium text-gray-500">Completed (this page)</div>
                        <div className="text-2xl font-bold text-indigo-600 mt-1">{items.filter(i => i.status === 'completed').length}</div>
                    </div>
                </div>
            )}

            <div className="bg-white rounded-xl shadow-sm border">
                {loading ? (
                    <div className="text-center py-12 text-gray-500">Loading donations...</div>
                ) : (
                    <DataTable columns={columns} data={items} />
                )}
                <Pagination meta={meta} onPageChange={fetchItems} />
            </div>
        </div>
    );
}
