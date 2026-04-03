import React, { useState, useEffect } from 'react';
import { get, post } from '../shared/api';

function StatusBadge({ connected, label }) {
    return (
        <span className={`inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-xs font-semibold ${
            connected ? 'bg-green-100 text-green-700' : 'bg-red-100 text-red-700'
        }`}>
            <span className={`w-2 h-2 rounded-full ${connected ? 'bg-green-500' : 'bg-red-500'}`}></span>
            {label}
        </span>
    );
}

function StepResult({ step }) {
    return (
        <div className={`flex items-start gap-3 px-4 py-3 rounded-lg border ${
            step.success ? 'bg-green-50 border-green-200' : 'bg-red-50 border-red-200'
        }`}>
            <div className="flex-shrink-0 mt-0.5">
                {step.success ? (
                    <i className="fas fa-check-circle text-green-500"></i>
                ) : (
                    <i className="fas fa-times-circle text-red-500"></i>
                )}
            </div>
            <div className="flex-1 min-w-0">
                <div className="text-sm font-semibold text-gray-800">{step.step}</div>
                <div className="text-xs text-gray-600 mt-0.5 break-all">{step.message}</div>
            </div>
        </div>
    );
}

export default function SystemManager() {
    const [status, setStatus] = useState(null);
    const [loading, setLoading] = useState(true);
    const [actionLoading, setActionLoading] = useState(null);
    const [output, setOutput] = useState(null);
    const [deploySteps, setDeploySteps] = useState(null);
    const [alert, setAlert] = useState(null);

    useEffect(() => {
        loadStatus();
    }, []);

    const loadStatus = async () => {
        setLoading(true);
        try {
            const data = await get('/api/system/status');
            setStatus(data.data);
        } catch (e) {
            setAlert({ type: 'error', message: 'Failed to load system status: ' + e.message });
        }
        setLoading(false);
    };

    const runAction = async (endpoint, label) => {
        setActionLoading(label);
        setOutput(null);
        setDeploySteps(null);
        setAlert(null);
        try {
            const data = await post('/api/system/' + endpoint);
            if (data.data?.steps) {
                setDeploySteps(data.data.steps);
            } else if (data.data?.output || data.data?.pull_output || data.data?.changelog) {
                setOutput(data.data.output || data.data.pull_output || '');
                if (data.data.changelog) {
                    setOutput((prev) => (prev || '') + '\n\nChangelog:\n' + data.data.changelog);
                }
            }
            setAlert({
                type: data.success ? 'success' : 'error',
                message: data.message,
            });
            // Refresh status after action
            loadStatus();
        } catch (e) {
            setAlert({ type: 'error', message: label + ' failed: ' + e.message });
        }
        setActionLoading(null);
    };

    if (loading) {
        return (
            <div className="flex items-center justify-center py-20">
                <svg className="animate-spin h-8 w-8 text-indigo-600" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                    <circle className="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" strokeWidth="4"></circle>
                    <path className="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
                </svg>
                <span className="ml-3 text-gray-500 text-sm">Loading system info...</span>
            </div>
        );
    }

    const git = status?.git || {};
    const server = status?.server || {};
    const db = status?.database || {};

    return (
        <div>
            {/* Header */}
            <div className="flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4 mb-6">
                <div>
                    <h2 className="text-2xl font-bold text-gray-800">
                        <i className="fas fa-server text-indigo-600 mr-2"></i>
                        System &amp; Deploy
                    </h2>
                    <p className="text-sm text-gray-500 mt-1">
                        Update, deploy, and manage your church platform.
                    </p>
                </div>
                <button
                    onClick={loadStatus}
                    className="px-4 py-2 border border-gray-300 text-gray-700 rounded-lg text-sm font-medium hover:bg-gray-50 flex items-center gap-2"
                >
                    <i className="fas fa-sync-alt"></i> Refresh
                </button>
            </div>

            {/* Alert */}
            {alert && (
                <div className={`mb-6 px-4 py-3 rounded-lg border flex items-start gap-3 ${
                    alert.type === 'success' ? 'bg-green-50 border-green-200 text-green-800' : 'bg-red-50 border-red-200 text-red-800'
                }`}>
                    <i className={`fas mt-0.5 ${alert.type === 'success' ? 'fa-check-circle text-green-500' : 'fa-exclamation-circle text-red-500'}`}></i>
                    <div className="flex-1 text-sm">{alert.message}</div>
                    <button onClick={() => setAlert(null)} className="text-gray-400 hover:text-gray-600">
                        <i className="fas fa-times"></i>
                    </button>
                </div>
            )}

            {/* Git Status + Quick Actions */}
            <div className="grid grid-cols-1 lg:grid-cols-3 gap-6 mb-6">
                {/* Git Info Card */}
                <div className="lg:col-span-2 bg-white rounded-xl border border-gray-200 p-6 shadow-sm">
                    <div className="flex items-center justify-between mb-4">
                        <h3 className="text-lg font-semibold text-gray-800">
                            <i className="fab fa-git-alt text-orange-600 mr-2"></i>
                            Repository Status
                        </h3>
                        {git.available ? (
                            git.behind > 0 ? (
                                <span className="inline-flex items-center gap-1.5 px-3 py-1 bg-amber-100 text-amber-700 rounded-full text-xs font-semibold">
                                    <i className="fas fa-arrow-down"></i>
                                    {git.behind} update{git.behind !== 1 ? 's' : ''} available
                                </span>
                            ) : (
                                <StatusBadge connected={true} label="Up to date" />
                            )
                        ) : (
                            <StatusBadge connected={false} label="No Git" />
                        )}
                    </div>

                    {git.available ? (
                        <div className="grid grid-cols-2 gap-4">
                            <div>
                                <label className="text-xs font-medium text-gray-500 uppercase tracking-wider">Branch</label>
                                <p className="text-sm font-mono text-gray-800 mt-1">
                                    <i className="fas fa-code-branch text-indigo-500 mr-1"></i> {git.branch}
                                </p>
                            </div>
                            <div>
                                <label className="text-xs font-medium text-gray-500 uppercase tracking-wider">Commit</label>
                                <p className="text-sm font-mono text-gray-800 mt-1">
                                    <i className="fas fa-hashtag text-gray-400 mr-1"></i> {git.commit}
                                </p>
                            </div>
                            <div className="col-span-2">
                                <label className="text-xs font-medium text-gray-500 uppercase tracking-wider">Last Commit</label>
                                <p className="text-sm text-gray-700 mt-1 truncate">{git.last_message}</p>
                                <p className="text-xs text-gray-400 mt-0.5">{git.last_date}</p>
                            </div>
                        </div>
                    ) : (
                        <p className="text-sm text-gray-500">Git is not configured for this project.</p>
                    )}
                </div>

                {/* Environment Card */}
                <div className="bg-white rounded-xl border border-gray-200 p-6 shadow-sm">
                    <h3 className="text-lg font-semibold text-gray-800 mb-4">
                        <i className="fas fa-info-circle text-blue-600 mr-2"></i>
                        Environment
                    </h3>
                    <div className="space-y-3">
                        <div className="flex justify-between text-sm">
                            <span className="text-gray-500">Laravel</span>
                            <span className="font-mono text-gray-800">{status?.laravel_version}</span>
                        </div>
                        <div className="flex justify-between text-sm">
                            <span className="text-gray-500">PHP</span>
                            <span className="font-mono text-gray-800">{status?.php_version}</span>
                        </div>
                        <div className="flex justify-between text-sm">
                            <span className="text-gray-500">Env</span>
                            <span className={`font-mono font-semibold ${status?.environment === 'production' ? 'text-green-600' : 'text-amber-600'}`}>
                                {status?.environment}
                            </span>
                        </div>
                        <div className="flex justify-between text-sm">
                            <span className="text-gray-500">Debug</span>
                            <span className={`font-semibold ${status?.debug_mode ? 'text-red-600' : 'text-green-600'}`}>
                                {status?.debug_mode ? 'ON' : 'OFF'}
                            </span>
                        </div>
                        <div className="flex justify-between text-sm">
                            <span className="text-gray-500">Database</span>
                            <StatusBadge connected={db.connected} label={db.connected ? db.driver : 'Error'} />
                        </div>
                        <div className="flex justify-between text-sm">
                            <span className="text-gray-500">Disk Free</span>
                            <span className="font-mono text-gray-800">{server.disk_free}</span>
                        </div>
                    </div>
                </div>
            </div>

            {/* Action Buttons */}
            <div className="bg-white rounded-xl border border-gray-200 p-6 shadow-sm mb-6">
                <h3 className="text-lg font-semibold text-gray-800 mb-4">
                    <i className="fas fa-rocket text-purple-600 mr-2"></i>
                    Quick Actions
                </h3>

                <div className="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-6 gap-3">
                    {/* Full Deploy */}
                    <button
                        onClick={() => {
                            if (confirm('Run full deploy? This will: pull code, run migrations, build assets, and optimize.')) {
                                runAction('deploy', 'Deploy');
                            }
                        }}
                        disabled={actionLoading !== null}
                        className="col-span-2 md:col-span-3 lg:col-span-2 flex items-center justify-center gap-2 px-4 py-4 bg-gradient-to-r from-indigo-600 to-purple-600 text-white rounded-xl font-semibold hover:from-indigo-700 hover:to-purple-700 disabled:opacity-50 disabled:cursor-not-allowed transition-all shadow-md hover:shadow-lg"
                    >
                        {actionLoading === 'Deploy' ? (
                            <svg className="animate-spin h-5 w-5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                <circle className="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" strokeWidth="4"></circle>
                                <path className="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
                            </svg>
                        ) : (
                            <i className="fas fa-rocket"></i>
                        )}
                        {actionLoading === 'Deploy' ? 'Deploying...' : 'Full Deploy'}
                    </button>

                    {/* Git Pull */}
                    <button
                        onClick={() => runAction('git-pull', 'Git Pull')}
                        disabled={actionLoading !== null}
                        className="flex flex-col items-center gap-2 px-4 py-4 bg-orange-50 border border-orange-200 text-orange-700 rounded-xl font-medium hover:bg-orange-100 disabled:opacity-50 disabled:cursor-not-allowed transition-all"
                    >
                        {actionLoading === 'Git Pull' ? (
                            <svg className="animate-spin h-5 w-5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                <circle className="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" strokeWidth="4"></circle>
                                <path className="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
                            </svg>
                        ) : (
                            <i className="fab fa-git-alt text-xl"></i>
                        )}
                        <span className="text-xs">{actionLoading === 'Git Pull' ? 'Pulling...' : 'Git Pull'}</span>
                    </button>

                    {/* Migrate */}
                    <button
                        onClick={() => runAction('migrate', 'Migrate')}
                        disabled={actionLoading !== null}
                        className="flex flex-col items-center gap-2 px-4 py-4 bg-blue-50 border border-blue-200 text-blue-700 rounded-xl font-medium hover:bg-blue-100 disabled:opacity-50 disabled:cursor-not-allowed transition-all"
                    >
                        {actionLoading === 'Migrate' ? (
                            <svg className="animate-spin h-5 w-5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                <circle className="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" strokeWidth="4"></circle>
                                <path className="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
                            </svg>
                        ) : (
                            <i className="fas fa-database text-xl"></i>
                        )}
                        <span className="text-xs">{actionLoading === 'Migrate' ? 'Running...' : 'Migrate'}</span>
                    </button>

                    {/* Build Assets */}
                    <button
                        onClick={() => runAction('build', 'Build')}
                        disabled={actionLoading !== null}
                        className="flex flex-col items-center gap-2 px-4 py-4 bg-green-50 border border-green-200 text-green-700 rounded-xl font-medium hover:bg-green-100 disabled:opacity-50 disabled:cursor-not-allowed transition-all"
                    >
                        {actionLoading === 'Build' ? (
                            <svg className="animate-spin h-5 w-5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                <circle className="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" strokeWidth="4"></circle>
                                <path className="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
                            </svg>
                        ) : (
                            <i className="fab fa-npm text-xl"></i>
                        )}
                        <span className="text-xs">{actionLoading === 'Build' ? 'Building...' : 'Build Assets'}</span>
                    </button>

                    {/* Clear Cache */}
                    <button
                        onClick={() => runAction('clear-cache', 'Clear Cache')}
                        disabled={actionLoading !== null}
                        className="flex flex-col items-center gap-2 px-4 py-4 bg-amber-50 border border-amber-200 text-amber-700 rounded-xl font-medium hover:bg-amber-100 disabled:opacity-50 disabled:cursor-not-allowed transition-all"
                    >
                        {actionLoading === 'Clear Cache' ? (
                            <svg className="animate-spin h-5 w-5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                <circle className="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" strokeWidth="4"></circle>
                                <path className="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
                            </svg>
                        ) : (
                            <i className="fas fa-broom text-xl"></i>
                        )}
                        <span className="text-xs">{actionLoading === 'Clear Cache' ? 'Clearing...' : 'Clear Cache'}</span>
                    </button>

                    {/* Optimize */}
                    <button
                        onClick={() => runAction('optimize', 'Optimize')}
                        disabled={actionLoading !== null}
                        className="flex flex-col items-center gap-2 px-4 py-4 bg-purple-50 border border-purple-200 text-purple-700 rounded-xl font-medium hover:bg-purple-100 disabled:opacity-50 disabled:cursor-not-allowed transition-all"
                    >
                        {actionLoading === 'Optimize' ? (
                            <svg className="animate-spin h-5 w-5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                <circle className="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" strokeWidth="4"></circle>
                                <path className="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
                            </svg>
                        ) : (
                            <i className="fas fa-bolt text-xl"></i>
                        )}
                        <span className="text-xs">{actionLoading === 'Optimize' ? 'Optimizing...' : 'Optimize'}</span>
                    </button>
                </div>
            </div>

            {/* Deploy Steps Output */}
            {deploySteps && (
                <div className="bg-white rounded-xl border border-gray-200 p-6 shadow-sm mb-6">
                    <h3 className="text-lg font-semibold text-gray-800 mb-4">
                        <i className="fas fa-list-check text-indigo-600 mr-2"></i>
                        Deploy Results
                    </h3>
                    <div className="space-y-2">
                        {deploySteps.map((step, i) => (
                            <StepResult key={i} step={step} />
                        ))}
                    </div>
                </div>
            )}

            {/* Command Output */}
            {output && (
                <div className="bg-white rounded-xl border border-gray-200 p-6 shadow-sm mb-6">
                    <h3 className="text-lg font-semibold text-gray-800 mb-3">
                        <i className="fas fa-terminal text-gray-600 mr-2"></i>
                        Output
                    </h3>
                    <pre className="bg-gray-900 text-green-400 p-4 rounded-lg text-xs overflow-x-auto max-h-80 overflow-y-auto font-mono whitespace-pre-wrap">
                        {output}
                    </pre>
                </div>
            )}

            {/* Server Details */}
            <div className="bg-white rounded-xl border border-gray-200 p-6 shadow-sm">
                <h3 className="text-lg font-semibold text-gray-800 mb-4">
                    <i className="fas fa-microchip text-teal-600 mr-2"></i>
                    Server Details
                </h3>
                <div className="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-4">
                    {[
                        { label: 'OS', value: server.os, icon: 'fa-linux fab' },
                        { label: 'Web Server', value: server.server_software, icon: 'fas fa-globe' },
                        { label: 'Disk Free', value: server.disk_free, icon: 'fas fa-hdd' },
                        { label: 'Disk Total', value: server.disk_total, icon: 'fas fa-hdd' },
                        { label: 'Memory Limit', value: server.memory_limit, icon: 'fas fa-memory' },
                        { label: 'Max Exec Time', value: server.max_execution_time + 's', icon: 'fas fa-clock' },
                        { label: 'Upload Max', value: server.upload_max_filesize, icon: 'fas fa-upload' },
                        { label: 'Cache Driver', value: status?.cache_driver, icon: 'fas fa-layer-group' },
                        { label: 'Queue Driver', value: status?.queue_driver, icon: 'fas fa-tasks' },
                        { label: 'Session Driver', value: status?.session_driver, icon: 'fas fa-cookie' },
                    ].map((item, i) => (
                        <div key={i} className="bg-gray-50 rounded-lg px-4 py-3">
                            <div className="flex items-center gap-2 mb-1">
                                <i className={`${item.icon} text-gray-400 text-xs`}></i>
                                <span className="text-xs text-gray-500 font-medium uppercase tracking-wider">{item.label}</span>
                            </div>
                            <p className="text-sm font-mono text-gray-800 truncate">{item.value || 'N/A'}</p>
                        </div>
                    ))}
                </div>
            </div>
        </div>
    );
}
