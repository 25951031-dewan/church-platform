import type {MeetingPlatform} from '../types';

interface Props {
  url: string;
  platform: MeetingPlatform;
  isLive?: boolean;
}

export function JoinMeetingButton({url, platform, isLive}: Props) {
  return (
    <a
      href={url}
      target="_blank"
      rel="noopener noreferrer"
      className={`inline-flex items-center justify-center rounded-lg px-4 py-2 text-sm font-semibold text-white ${
        isLive ? 'bg-red-600 hover:bg-red-700' : 'bg-primary-600 hover:bg-primary-700'
      }`}
      aria-label={`Join ${platform} meeting`}
    >
      Join Meeting
    </a>
  );
}
