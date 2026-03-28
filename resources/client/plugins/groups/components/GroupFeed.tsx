import {PostFeed} from '@app/plugins/timeline/components/PostFeed';
import {PostComposer} from '@app/plugins/timeline/components/PostComposer';

interface GroupFeedProps {
  groupId: number | string;
  isMember: boolean;
}

export function GroupFeed({groupId, isMember}: GroupFeedProps) {
  return (
    <div className="space-y-4">
      {isMember && <PostComposer groupId={groupId} />}
      <PostFeed groupId={groupId} />
    </div>
  );
}
