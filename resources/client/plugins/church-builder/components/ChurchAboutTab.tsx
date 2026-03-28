import type { Church } from '../queries';

interface Props {
    church: Church;
}

export function ChurchAboutTab({ church }: Props) {
    return (
        <div className="space-y-6">
            {church.description && (
                <section>
                    <h3 className="text-sm font-semibold text-white/50 uppercase tracking-wider mb-2">About</h3>
                    <p className="text-sm leading-relaxed">{church.description}</p>
                </section>
            )}

            {(church.mission_statement || church.vision_statement) && (
                <section className="grid gap-4 sm:grid-cols-2">
                    {church.mission_statement && (
                        <div className="rounded-lg border border-white/10 bg-white/5 p-4">
                            <h4 className="text-xs font-semibold text-white/50 uppercase tracking-wider mb-1">Mission</h4>
                            <p className="text-sm">{church.mission_statement}</p>
                        </div>
                    )}
                    {church.vision_statement && (
                        <div className="rounded-lg border border-white/10 bg-white/5 p-4">
                            <h4 className="text-xs font-semibold text-white/50 uppercase tracking-wider mb-1">Vision</h4>
                            <p className="text-sm">{church.vision_statement}</p>
                        </div>
                    )}
                </section>
            )}

            <section>
                <h3 className="text-sm font-semibold text-white/50 uppercase tracking-wider mb-2">Contact</h3>
                <dl className="space-y-1.5 text-sm">
                    {church.address && <div><dt className="inline text-white/50">Address: </dt><dd className="inline">{church.address}{church.city ? `, ${church.city}` : ''}{church.state ? `, ${church.state}` : ''}</dd></div>}
                    {church.phone && <div><dt className="inline text-white/50">Phone: </dt><dd className="inline">{church.phone}</dd></div>}
                    {church.email && <div><dt className="inline text-white/50">Email: </dt><dd className="inline">{church.email}</dd></div>}
                    {church.website && <div><dt className="inline text-white/50">Website: </dt><dd className="inline"><a href={church.website} target="_blank" rel="noopener noreferrer" className="text-indigo-400 hover:underline">{church.website}</a></dd></div>}
                </dl>
            </section>

            {church.service_hours && Object.keys(church.service_hours).length > 0 && (
                <section>
                    <h3 className="text-sm font-semibold text-white/50 uppercase tracking-wider mb-2">Service Times</h3>
                    <dl className="space-y-1 text-sm">
                        {Object.entries(church.service_hours).map(([day, time]) => (
                            <div key={day} className="flex justify-between">
                                <dt className="text-white/50 capitalize">{day}</dt>
                                <dd>{time}</dd>
                            </div>
                        ))}
                    </dl>
                </section>
            )}

            <div className="flex gap-3 text-xs text-white/40">
                {church.denomination && <span>Denomination: {church.denomination}</span>}
                {church.year_founded && <span>Founded: {church.year_founded}</span>}
            </div>
        </div>
    );
}
