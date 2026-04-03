import {useMemo} from 'react';
import {useMeetings} from './useMeetings';

export function useUpcomingMeetings() {
  const query = useMeetings({filter: 'upcoming'});

  const meetings = useMemo(
    () => query.data?.pages.flatMap(page => page.pagination?.data ?? []) ?? [],
    [query.data],
  );

  return {...query, meetings};
}
