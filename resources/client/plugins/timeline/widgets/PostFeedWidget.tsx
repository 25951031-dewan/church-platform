import { PostComposer } from '../components/PostComposer';
import { PostFeed } from '../components/PostFeed';

interface PostFeedWidgetProps {
  config?: {
    show_composer?: boolean;
    group_id?: number | string;
  };
}

export function PostFeedWidget({ config = {} }: PostFeedWidgetProps) {
  const {
    show_composer = true,
    group_id
  } = config;

  return (
    <div className="space-y-4">
      {show_composer && <PostComposer groupId={group_id} />}
      <PostFeed groupId={group_id} />
    </div>
  );
}