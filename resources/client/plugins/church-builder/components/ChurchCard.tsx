import { Link } from 'react-router';
import { MapPin, Users } from 'lucide-react';
import type { Church } from '../queries';

interface Props {
    church: Church;
}

export function ChurchCard({ church }: Props) {
    return (
        <Link
            to={`/churches/${church.id}`}
            className="block rounded-xl border border-white/10 bg-white/5 hover:bg-white/10 transition-colors overflow-hidden"
        >
            <div
                className="h-24 w-full"
                style={{ backgroundColor: church.primary_color ?? '#4F46E5' }}
            >
                {church.cover_photo_url && (
                    <img
                        src={church.cover_photo_url}
                        alt=""
                        className="h-full w-full object-cover"
                    />
                )}
            </div>

            <div className="p-4">
                <div className="flex items-start gap-3">
                    {church.logo_url ? (
                        <img
                            src={church.logo_url}
                            alt={church.name}
                            className="h-12 w-12 rounded-lg object-cover shrink-0 -mt-8 ring-2 ring-white/10"
                        />
                    ) : (
                        <div
                            className="h-12 w-12 rounded-lg shrink-0 -mt-8 ring-2 ring-white/10 flex items-center justify-center text-white font-bold text-lg"
                            style={{ backgroundColor: church.primary_color ?? '#4F46E5' }}
                        >
                            {church.name.charAt(0)}
                        </div>
                    )}

                    <div className="min-w-0 flex-1">
                        <div className="flex items-center gap-1.5 flex-wrap">
                            <h3 className="font-semibold text-sm truncate">{church.name}</h3>
                            {church.is_verified && (
                                <span className="text-xs bg-blue-500/20 text-blue-400 px-1.5 py-0.5 rounded-full">
                                    Verified
                                </span>
                            )}
                            {church.is_featured && (
                                <span className="text-xs bg-yellow-500/20 text-yellow-400 px-1.5 py-0.5 rounded-full">
                                    Featured
                                </span>
                            )}
                        </div>
                        {church.denomination && (
                            <p className="text-xs text-white/50 mt-0.5">{church.denomination}</p>
                        )}
                    </div>
                </div>

                {church.short_description && (
                    <p className="text-xs text-white/60 mt-2 line-clamp-2">
                        {church.short_description}
                    </p>
                )}

                <div className="flex items-center gap-3 mt-3 text-xs text-white/40">
                    {church.city && (
                        <span className="flex items-center gap-1">
                            <MapPin size={11} />
                            {church.city}
                            {church.state ? `, ${church.state}` : ''}
                        </span>
                    )}
                    {church.approved_members_count !== undefined && (
                        <span className="flex items-center gap-1">
                            <Users size={11} />
                            {church.approved_members_count}
                        </span>
                    )}
                </div>
            </div>
        </Link>
    );
}
