import { useState } from 'react';
import { useParams } from 'react-router';
import { useChurch, useJoinChurch, useLeaveChurch } from '../queries';
import { ChurchAboutTab } from '../components/ChurchAboutTab';
import { ChurchMembersTab } from '../components/ChurchMembersTab';
import { ChurchPagesTab } from '../components/ChurchPagesTab';

type Tab = 'about' | 'members' | 'pages';

export function ChurchProfilePage() {
    const { churchId } = useParams<{ churchId: string }>();
    const { data: church, isLoading } = useChurch(Number(churchId));
    const [tab, setTab] = useState<Tab>('about');
    const joinChurch = useJoinChurch();
    const leaveChurch = useLeaveChurch();

    if (isLoading) {
        return <div className="flex justify-center py-16 text-white/40">Loading\u2026</div>;
    }
    if (!church) {
        return <div className="flex justify-center py-16 text-white/40">Church not found.</div>;
    }

    const isMember = !!church.current_user_membership;

    return (
        <div className="max-w-4xl mx-auto">
            {/* Cover photo */}
            <div
                className="h-48 w-full rounded-b-xl overflow-hidden"
                style={{ backgroundColor: church.primary_color ?? '#4F46E5' }}
            >
                {church.cover_photo_url && (
                    <img src={church.cover_photo_url} alt="" className="h-full w-full object-cover" />
                )}
            </div>

            {/* Header */}
            <div className="px-4 pb-4">
                <div className="flex items-end gap-4 -mt-8 mb-4">
                    <div
                        className="h-20 w-20 rounded-xl ring-4 ring-[#0f0f0f] overflow-hidden shrink-0"
                        style={{ backgroundColor: church.primary_color ?? '#4F46E5' }}
                    >
                        {church.logo_url ? (
                            <img src={church.logo_url} alt={church.name} className="h-full w-full object-cover" />
                        ) : (
                            <div className="h-full w-full flex items-center justify-center text-white text-2xl font-bold">
                                {church.name.charAt(0)}
                            </div>
                        )}
                    </div>
                    <div className="flex-1 min-w-0 pb-1">
                        <div className="flex items-center gap-2 flex-wrap">
                            <h1 className="text-xl font-bold">{church.name}</h1>
                            {church.is_verified && (
                                <span className="text-xs bg-blue-500/20 text-blue-400 px-2 py-0.5 rounded-full">
                                    Verified
                                </span>
                            )}
                            {church.is_featured && (
                                <span className="text-xs bg-yellow-500/20 text-yellow-400 px-2 py-0.5 rounded-full">
                                    Featured
                                </span>
                            )}
                        </div>
                        <p className="text-sm text-white/50">
                            {church.approved_members_count ?? 0} members
                            {church.denomination ? ` \u00b7 ${church.denomination}` : ''}
                        </p>
                    </div>
                    <div className="pb-1 shrink-0">
                        {isMember ? (
                            <button
                                onClick={() => leaveChurch.mutate(church.id)}
                                disabled={leaveChurch.isPending}
                                className="text-sm px-4 py-2 rounded-full border border-white/20 hover:bg-white/10 transition-colors"
                            >
                                Leave
                            </button>
                        ) : (
                            <button
                                onClick={() => joinChurch.mutate(church.id)}
                                disabled={joinChurch.isPending}
                                className="text-sm px-4 py-2 rounded-full bg-indigo-600 hover:bg-indigo-700 text-white transition-colors"
                            >
                                Join
                            </button>
                        )}
                    </div>
                </div>

                {/* Tabs */}
                <div className="flex border-b border-white/10 mb-6">
                    {(['about', 'members', 'pages'] as Tab[]).map(t => (
                        <button
                            key={t}
                            onClick={() => setTab(t)}
                            className={`px-4 py-2 text-sm capitalize transition-colors border-b-2 -mb-px ${
                                tab === t
                                    ? 'border-indigo-500 text-white'
                                    : 'border-transparent text-white/50 hover:text-white/80'
                            }`}
                        >
                            {t}
                        </button>
                    ))}
                </div>

                {tab === 'about' && <ChurchAboutTab church={church} />}
                {tab === 'members' && <ChurchMembersTab church={church} />}
                {tab === 'pages' && <ChurchPagesTab church={church} />}
            </div>
        </div>
    );
}
