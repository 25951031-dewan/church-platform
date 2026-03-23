<?php
namespace Plugins\Event\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Plugins\Event\Models\Event;
use Plugins\Post\Models\Post;

class EventPostController extends Controller
{
    /** GET /api/v1/events/{id}/posts */
    public function index(int $id): JsonResponse
    {
        Event::findOrFail($id); // ensure event exists

        $posts = Post::where('type', 'event_post')
            ->where('event_id', $id)  // uses the generated/indexed column
            ->with(['author:id,name,avatar'])
            ->withCount(['comments', 'reactions'])
            ->latest('published_at')
            ->paginate(20);

        return response()->json($posts);
    }

    /** POST /api/v1/events/{id}/posts */
    public function store(Request $request, int $id): JsonResponse
    {
        Event::findOrFail($id);

        $data = $request->validate([
            'body' => ['required', 'string', 'max:5000'],
        ]);

        $post = Post::create([
            'user_id'      => $request->user()->id,
            'type'         => 'event_post',
            'body'         => $data['body'],
            'meta'         => ['event_id' => $id],
            'status'       => 'published',
            'published_at' => now(),
        ]);

        // SQLite (test env): manually set the plain event_id column since generated columns aren't supported
        if (DB::getDriverName() === 'sqlite') {
            DB::table('social_posts')->where('id', $post->id)->update(['event_id' => $id]);
        }

        return response()->json($post->load('author:id,name,avatar'), 201);
    }
}
