import { PostComposer } from '../components/PostComposer';
import { PostFeed } from '../components/PostFeed';

export function NewsfeedPage() {
  return (
    <div className="max-w-2xl mx-auto py-6 px-4 space-y-4">
      <PostComposer />
      <PostFeed />
    </div>
  );
}
