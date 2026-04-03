import React from 'react';
import { useNavigate } from 'react-router-dom';

export default function Community() {
    const navigate = useNavigate();

    return (
        <div className="px-4 py-8 flex flex-col items-center">
            <div className="w-full max-w-md bg-white rounded-xl shadow-sm border border-gray-100 p-8 text-center">
                <div className="text-5xl mb-4">👥</div>
                <h1 className="text-xl font-bold text-gray-800 mb-2">Community</h1>
                <div className="inline-block bg-indigo-100 text-indigo-700 text-xs font-semibold px-3 py-1 rounded-full mb-4">
                    Coming Soon
                </div>
                <p className="text-gray-500 text-sm leading-relaxed mb-6">
                    The community module is coming soon. Stay connected with your church family
                    through posts, groups, and discussions.
                </p>
                <button
                    onClick={() => navigate('/churches')}
                    className="w-full py-2.5 bg-indigo-600 text-white text-sm font-semibold rounded-lg hover:bg-indigo-700 focus:outline-none"
                >
                    Browse Church Directory
                </button>
            </div>
        </div>
    );
}
